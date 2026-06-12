<?php
// fonctions_besoin2.php - Fonctions Besoin 2 : inscriptions et profil
// Projet Old Dating - BUT1 Informatique

require_once 'config.php';

// FCT1 & FCT2 - Inscription à un événement

function inscrireAdherent(int $idAdherent, int $idEvenement): array
{
    $pdo = getConnexion();

    // vérification doublon
    $sqlCheck = "SELECT COUNT(*) AS nb
                 FROM inscription
                 WHERE id_adherent = :ida
                 AND id_evenement  = :ide
                 AND statut = 'confirme'";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':ida' => $idAdherent, ':ide' => $idEvenement]);
    if ($stmtCheck->fetch()['nb'] > 0) {
        return [
            'succes'  => false,
            'message' => 'Vous êtes déjà inscrit(e) à cet événement.'
        ];
    }

    // vérification capacité
    $sqlCapacite = "SELECT capacite_max,
                           (SELECT COUNT(*)
                            FROM inscription i
                            WHERE i.id_evenement = e.id_evenement
                            AND i.statut = 'confirme') AS nb_inscrits
                    FROM evenement e
                    WHERE id_evenement = :ide";
    $stmtCap = $pdo->prepare($sqlCapacite);
    $stmtCap->execute([':ide' => $idEvenement]);
    $evenement = $stmtCap->fetch();

    if (!$evenement) {
        return ['succes' => false, 'message' => 'Événement introuvable.'];
    }
    if ($evenement['nb_inscrits'] >= $evenement['capacite_max']) {
        return ['succes' => false, 'message' => 'Cet événement est complet.'];
    }

    $sqlInsert = "INSERT INTO inscription
                      (id_adherent, id_evenement, date_inscription,
                       sms_envoye, statut)
                  VALUES
                      (:ida, :ide, CURRENT_DATE, FALSE, 'confirme')";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([':ida' => $idAdherent, ':ide' => $idEvenement]);

    // SMS de confirmation (simulation)
    $smsSent = envoyerSmsFictif($idAdherent, $idEvenement);

    if ($smsSent) {
        $sqlSms = "UPDATE inscription
                   SET sms_envoye = TRUE
                   WHERE id_adherent = :ida AND id_evenement = :ide";
        $stmtSms = $pdo->prepare($sqlSms);
        $stmtSms->execute([':ida' => $idAdherent, ':ide' => $idEvenement]);
    }

    return [
        'succes'  => true,
        'message' => 'Inscription confirmée ! Un SMS a été envoyé.'
    ];
}

function annulerInscription(int $idAdherent, int $idEvenement): bool
{
    $pdo = getConnexion();
    $sql = "UPDATE inscription
            SET statut = 'annule'
            WHERE id_adherent = :ida
            AND id_evenement  = :ide
            AND statut = 'confirme'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ida' => $idAdherent, ':ide' => $idEvenement]);
    return $stmt->rowCount() > 0;
}

function getInscriptionsAdherent(int $idAdherent): array
{
    $pdo = getConnexion();
    $sql = "SELECT e.id_evenement, e.titre, e.date_evenement,
                   e.heure_debut, e.lieu, e.type, e.categorie,
                   ins.date_inscription, ins.sms_envoye
            FROM inscription ins
            JOIN evenement e ON ins.id_evenement = e.id_evenement
            WHERE ins.id_adherent = :id
            AND ins.statut = 'confirme'
            ORDER BY e.date_evenement ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idAdherent]);
    return $stmt->fetchAll();
}

// Simulation envoi SMS - journalisation dans un fichier local
function envoyerSmsFictif(int $idAdherent, int $idEvenement): bool
{
    $pdo = getConnexion();

    $sqlAdh = "SELECT prenom, telephone FROM adherent WHERE id_adherent = :id";
    $stmtAdh = $pdo->prepare($sqlAdh);
    $stmtAdh->execute([':id' => $idAdherent]);
    $adherent = $stmtAdh->fetch();

    $sqlEv = "SELECT titre, date_evenement, heure_debut
              FROM evenement WHERE id_evenement = :id";
    $stmtEv = $pdo->prepare($sqlEv);
    $stmtEv->execute([':id' => $idEvenement]);
    $evenement = $stmtEv->fetch();

    if (!$adherent || !$evenement) {
        return false;
    }

    $message = sprintf(
        "[Old Dating] Bonjour %s ! Votre inscription à \"%s\" le %s à %s est confirmée. A bientôt !",
        $adherent['prenom'],
        $evenement['titre'],
        date('d/m/Y', strtotime($evenement['date_evenement'])),
        substr($evenement['heure_debut'], 0, 5)
    );

    $log = sprintf(
        "[%s] SMS vers %s : %s\n",
        date('Y-m-d H:i:s'),
        $adherent['telephone'],
        $message
    );
    file_put_contents(__DIR__ . '/logs/sms_log.txt', $log, FILE_APPEND);

    return true;
}


// FCT3 - Pseudo et avatar

function pseudoDisponible(string $pseudo, ?int $idAdherent = null): bool
{
    $pdo = getConnexion();

    if ($idAdherent !== null) {
        // exclure l'adhérent courant si modification de profil
        $sql = "SELECT COUNT(*) AS nb FROM adherent
                WHERE pseudo = :pseudo AND id_adherent != :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pseudo' => $pseudo, ':id' => $idAdherent]);
    } else {
        $sql = "SELECT COUNT(*) AS nb FROM adherent WHERE pseudo = :pseudo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pseudo' => $pseudo]);
    }

    return $stmt->fetch()['nb'] === 0;
}

function mettreAJourProfil(
    int $idAdherent,
    string $pseudo,
    ?string $avatar
): array {
    if (!pseudoDisponible($pseudo, $idAdherent)) {
        return [
            'succes'  => false,
            'message' => 'Ce pseudo est déjà pris. Essayez un autre !'
        ];
    }

    $pdo = getConnexion();
    $sql = "UPDATE adherent
            SET pseudo = :pseudo, avatar = :avatar
            WHERE id_adherent = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':pseudo' => $pseudo,
        ':avatar' => $avatar,
        ':id'     => $idAdherent,
    ]);

    // SMS confirmation profil
    envoyerSmsProfilFictif($idAdherent, $pseudo);

    return [
        'succes'  => true,
        'message' => 'Profil mis à jour ! Un SMS de confirmation a été envoyé.'
    ];
}

function envoyerSmsProfilFictif(int $idAdherent, string $pseudo): void
{
    $pdo = getConnexion();
    $sql = "SELECT prenom, telephone FROM adherent WHERE id_adherent = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idAdherent]);
    $adherent = $stmt->fetch();

    if (!$adherent) return;

    $message = sprintf(
        "[Old Dating] Bonjour %s ! Votre profil a été créé avec le pseudo \"%s\". Bienvenue !",
        $adherent['prenom'],
        $pseudo
    );

    $log = sprintf(
        "[%s] SMS vers %s : %s\n",
        date('Y-m-d H:i:s'),
        $adherent['telephone'],
        $message
    );
    file_put_contents(__DIR__ . '/logs/sms_log.txt', $log, FILE_APPEND);
}
