<?php
// fonctions_besoin1.php - Fonctions Besoin 1 : connexion, calendrier, agenda
// Projet Old Dating - BUT1 Informatique

require_once 'config.php';

// FCT1 - Connexion

function connecterAdherent(string $pseudo, string $motDePasse): array|false
{
    $pdo = getConnexion();
    $sql = "SELECT id_adherent, nom, prenom, pseudo, avatar,
                   mot_de_passe, statut_chat, actif
            FROM adherent
            WHERE pseudo = :pseudo";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pseudo' => $pseudo]);
    $adherent = $stmt->fetch();

    if (!$adherent) {
        return false;
    }
    if (!$adherent['actif']) {
        return false;
    }
    if (!password_verify($motDePasse, $adherent['mot_de_passe'])) {
        return false;
    }

    mettreAJourDerniereConnexion($adherent['id_adherent']);
    unset($adherent['mot_de_passe']);

    return $adherent;
}

function mettreAJourDerniereConnexion(int $idAdherent): void
{
    $pdo = getConnexion();
    $sql = "UPDATE adherent
            SET date_derniere_connexion = NOW()
            WHERE id_adherent = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idAdherent]);
}

function connecterAdministrateur(string $login, string $motDePasse): array|false
{
    $pdo = getConnexion();
    $sql = "SELECT id_admin, nom, prenom, login, mot_de_passe
            FROM administrateur
            WHERE login = :login";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':login' => $login]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($motDePasse, $admin['mot_de_passe'])) {
        return false;
    }

    unset($admin['mot_de_passe']);
    return $admin;
}

function mettreAJourStatutChat(int $idAdherent, string $statut): void
{
    $statutsValides = ['en_ligne', 'absent', 'ne_pas_deranger'];
    if (!in_array($statut, $statutsValides)) {
        return;
    }

    $pdo = getConnexion();
    $sql = "UPDATE adherent
            SET statut_chat = :statut
            WHERE id_adherent = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':statut' => $statut, ':id' => $idAdherent]);
}


// FCT2 - Calendrier des rencontres

function getEvenementsRencontreDuMois(int $mois, int $annee): array
{
    $pdo = getConnexion();
    $sql = "SELECT id_evenement, titre, description, date_evenement,
                   heure_debut, heure_fin, lieu, capacite_max,
                   (SELECT COUNT(*)
                    FROM inscription i
                    WHERE i.id_evenement = e.id_evenement
                    AND i.statut = 'confirme') AS nb_inscrits
            FROM evenement e
            WHERE type = 'rencontre'
            AND EXTRACT(MONTH FROM date_evenement) = :mois
            AND EXTRACT(YEAR FROM date_evenement)  = :annee
            ORDER BY date_evenement ASC, heure_debut ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':mois' => $mois, ':annee' => $annee]);
    return $stmt->fetchAll();
}

function getEvenementParId(int $idEvenement): array|false
{
    $pdo = getConnexion();
    $sql = "SELECT e.*,
                   (SELECT COUNT(*)
                    FROM inscription i
                    WHERE i.id_evenement = e.id_evenement
                    AND i.statut = 'confirme') AS nb_inscrits
            FROM evenement e
            WHERE e.id_evenement = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idEvenement]);
    return $stmt->fetch();
}

function estDejaInscrit(int $idAdherent, int $idEvenement): bool
{
    $pdo = getConnexion();
    $sql = "SELECT COUNT(*) AS nb
            FROM inscription
            WHERE id_adherent = :ida
            AND id_evenement  = :ide
            AND statut = 'confirme'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ida' => $idAdherent, ':ide' => $idEvenement]);
    $result = $stmt->fetch();
    return $result['nb'] > 0;
}


// FCT3 - Calendrier des activités

function getActivitesDuMois(int $mois, int $annee, ?string $categorie = null): array
{
    $pdo = getConnexion();

    if ($categorie !== null) {
        $sql = "SELECT e.*,
                       (SELECT COUNT(*)
                        FROM inscription i
                        WHERE i.id_evenement = e.id_evenement
                        AND i.statut = 'confirme') AS nb_inscrits
                FROM evenement e
                WHERE e.type = 'activite'
                AND e.categorie = :categorie
                AND EXTRACT(MONTH FROM e.date_evenement) = :mois
                AND EXTRACT(YEAR FROM e.date_evenement)  = :annee
                ORDER BY e.date_evenement ASC, e.heure_debut ASC";
        $params = [':categorie' => $categorie, ':mois' => $mois, ':annee' => $annee];
    } else {
        $sql = "SELECT e.*,
                       (SELECT COUNT(*)
                        FROM inscription i
                        WHERE i.id_evenement = e.id_evenement
                        AND i.statut = 'confirme') AS nb_inscrits
                FROM evenement e
                WHERE e.type = 'activite'
                AND EXTRACT(MONTH FROM e.date_evenement) = :mois
                AND EXTRACT(YEAR FROM e.date_evenement)  = :annee
                ORDER BY e.date_evenement ASC, e.heure_debut ASC";
        $params = [':mois' => $mois, ':annee' => $annee];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCategoriesActivites(): array
{
    $pdo = getConnexion();
    $sql = "SELECT DISTINCT categorie
            FROM evenement
            WHERE type = 'activite'
            AND categorie IS NOT NULL
            ORDER BY categorie ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


// FCT4 - Agenda personnel

function getAgendaAdherentDuMois(int $idAdherent, int $mois, int $annee): array
{
    $pdo = getConnexion();

    $sqlEntrees = "SELECT
                       'agenda'            AS source,
                       id_entree           AS id,
                       titre,
                       date_entree         AS date_item,
                       heure,
                       note,
                       type_entree         AS type
                   FROM entree_agenda
                   WHERE id_adherent = :id
                   AND EXTRACT(MONTH FROM date_entree) = :mois
                   AND EXTRACT(YEAR FROM date_entree)  = :annee";

    $sqlInscriptions = "SELECT
                            'inscription'       AS source,
                            e.id_evenement      AS id,
                            e.titre,
                            e.date_evenement    AS date_item,
                            e.heure_debut       AS heure,
                            e.lieu              AS note,
                            e.type              AS type
                        FROM inscription ins
                        JOIN evenement e ON ins.id_evenement = e.id_evenement
                        WHERE ins.id_adherent = :id
                        AND ins.statut = 'confirme'
                        AND EXTRACT(MONTH FROM e.date_evenement) = :mois
                        AND EXTRACT(YEAR FROM e.date_evenement)  = :annee";

    // UNION pour fusionner entrées perso + inscriptions, triées par date
    $sql = "($sqlEntrees) UNION ALL ($sqlInscriptions)
            ORDER BY date_item ASC, heure ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idAdherent, ':mois' => $mois, ':annee' => $annee]);
    return $stmt->fetchAll();
}

function ajouterEntreeAgenda(
    int $idAdherent,
    string $titre,
    string $date,
    ?string $heure,
    ?string $note,
    string $type = 'autre'
): int {
    $pdo = getConnexion();
    $sql = "INSERT INTO entree_agenda
                (id_adherent, titre, date_entree, heure, note, type_entree)
            VALUES
                (:id, :titre, :date, :heure, :note, :type)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'    => $idAdherent,
        ':titre' => $titre,
        ':date'  => $date,
        ':heure' => $heure,
        ':note'  => $note,
        ':type'  => $type,
    ]);
    return (int) $pdo->lastInsertId('entree_agenda_id_entree_seq');
}
