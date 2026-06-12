<?php
// fonctions_besoin3_4.php - Fonctions Besoin 3 (Chat) et Besoin 4 (Admin)
// Projet Old Dating - BUT1 Informatique

require_once 'config.php';


// FCT1 - Chat entre adhérents

function getConversationsAdherent(int $idAdherent): array
{
    $pdo = getConnexion();

    $sql = "SELECT
                c.id_conversation,
                c.date_creation,
                -- Infos interlocuteur
                a.id_adherent   AS id_interlocuteur,
                a.pseudo        AS pseudo_interlocuteur,
                a.avatar        AS avatar_interlocuteur,
                a.statut_chat   AS statut_interlocuteur,
                -- Dernier message
                (SELECT m.contenu
                 FROM message m
                 WHERE m.id_conversation = c.id_conversation
                 ORDER BY m.date_envoi DESC LIMIT 1
                ) AS dernier_message,
                (SELECT m.date_envoi
                 FROM message m
                 WHERE m.id_conversation = c.id_conversation
                 ORDER BY m.date_envoi DESC LIMIT 1
                ) AS date_dernier_message,
                -- Nb messages non lus de l'interlocuteur
                (SELECT COUNT(*)
                 FROM message m
                 WHERE m.id_conversation = c.id_conversation
                 AND m.id_expediteur != :id1
                 AND m.lu = FALSE
                ) AS nb_non_lus
            FROM conversation c
            JOIN participe p1 ON p1.id_conversation = c.id_conversation
                              AND p1.id_adherent = :id2
            JOIN participe p2 ON p2.id_conversation = c.id_conversation
                              AND p2.id_adherent != :id3
            JOIN adherent a   ON a.id_adherent = p2.id_adherent
            ORDER BY date_dernier_message DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id1' => $idAdherent, ':id2' => $idAdherent, ':id3' => $idAdherent]);
    return $stmt->fetchAll();
}

function getMessagesConversation(int $idConversation): array
{
    $pdo = getConnexion();
    $sql = "SELECT m.id_message, m.contenu, m.date_envoi,
                   m.type_message, m.lu, m.id_expediteur,
                   a.pseudo AS pseudo_expediteur,
                   a.avatar AS avatar_expediteur
            FROM message m
            JOIN adherent a ON a.id_adherent = m.id_expediteur
            WHERE m.id_conversation = :id
            ORDER BY m.date_envoi ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idConversation]);
    return $stmt->fetchAll();
}

function envoyerMessage(
    int $idConversation,
    int $idExpediteur,
    string $contenu,
    string $typeMessage = 'normal'
): int {
    $pdo = getConnexion();
    $sql = "INSERT INTO message
                (id_conversation, id_expediteur, contenu,
                 date_envoi, type_message, lu)
            VALUES
                (:idc, :ide, :contenu, NOW(), :type, FALSE)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':idc'     => $idConversation,
        ':ide'     => $idExpediteur,
        ':contenu' => $contenu,
        ':type'    => $typeMessage,
    ]);
    return (int) $pdo->lastInsertId('message_id_message_seq');
}

// Crée une conversation si elle n'existe pas, sinon retourne l'existante
function creerOuRecupererConversation(int $idAdherent1, int $idAdherent2): int
{
    $pdo = getConnexion();

    $sql = "SELECT p1.id_conversation
            FROM participe p1
            JOIN participe p2 ON p1.id_conversation = p2.id_conversation
            WHERE p1.id_adherent = :id1
            AND p2.id_adherent   = :id2
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id1' => $idAdherent1, ':id2' => $idAdherent2]);
    $existing = $stmt->fetch();

    if ($existing) {
        return (int) $existing['id_conversation'];
    }

    $sqlConv = "INSERT INTO conversation (date_creation) VALUES (NOW())";
    $pdo->exec($sqlConv);
    $idConversation = (int) $pdo->lastInsertId('conversation_id_conversation_seq');

    $sqlPart = "INSERT INTO participe (id_adherent, id_conversation, date_entree)
                VALUES (:id, :idc, NOW())";
    $stmtPart = $pdo->prepare($sqlPart);
    $stmtPart->execute([':id' => $idAdherent1, ':idc' => $idConversation]);
    $stmtPart->execute([':id' => $idAdherent2, ':idc' => $idConversation]);

    return $idConversation;
}

function marquerMessagesLus(int $idConversation, int $idAdherent): void
{
    $pdo = getConnexion();
    // messages de l'interlocuteur uniquement
    $sql = "UPDATE message
            SET lu = TRUE
            WHERE id_conversation = :idc
            AND id_expediteur != :ida
            AND lu = FALSE";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':idc' => $idConversation, ':ida' => $idAdherent]);
}


// FCT2 - Messages festifs

function envoyerMessageFestif(
    int $idConversation,
    int $idExpediteur,
    string $typeMessage
): int {
    $messages = [
        'anniversaire' => 'Joyeux anniversaire ! Je vous souhaite une belle journée remplie de joie et de bonheur. 🎂🎉',
        'fetes'        => 'Bonnes fêtes ! Que cette période vous apporte beaucoup de bonheur et de chaleur. 🎄🌟',
    ];

    $contenu = $messages[$typeMessage] ?? 'Meilleurs voeux !';
    return envoyerMessage($idConversation, $idExpediteur, $contenu, $typeMessage);
}


// FCT3 - Statut chat
// (mettreAJourStatutChat est dans fonctions_besoin1.php)

function getStatutChat(int $idAdherent): string
{
    $pdo = getConnexion();
    $sql = "SELECT statut_chat FROM adherent WHERE id_adherent = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idAdherent]);
    $result = $stmt->fetch();
    return $result ? $result['statut_chat'] : 'absent';
}


// TS2 - Créer un compte adhérent

function creerCompteAdherent(
    int $idAdmin,
    string $nom,
    string $prenom,
    string $telephone
): array {
    $pdo = getConnexion();

    // pseudo temporaire basé sur le prénom
    $pseudoBase = strtolower(
        preg_replace('/[^a-zA-Z0-9]/', '', $prenom)
    );
    $pseudo = $pseudoBase . rand(10, 99);

    // vérification unicité du pseudo
    $tentatives = 0;
    while (!pseudoDisponibleAdmin($pseudo) && $tentatives < 10) {
        $pseudo = $pseudoBase . rand(100, 999);
        $tentatives++;
    }

    // mot de passe temporaire (8 chars)
    $motDePasseTemp = substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'), 0, 8);
    $hash = password_hash($motDePasseTemp, PASSWORD_DEFAULT);

    $sql = "INSERT INTO adherent
                (nom, prenom, telephone, pseudo, mot_de_passe,
                 date_creation, statut_chat, actif, id_admin)
            VALUES
                (:nom, :prenom, :tel, :pseudo, :mdp,
                 CURRENT_DATE, 'en_ligne', TRUE, :idadmin)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom'     => $nom,
        ':prenom'  => $prenom,
        ':tel'     => $telephone,
        ':pseudo'  => $pseudo,
        ':mdp'     => $hash,
        ':idadmin' => $idAdmin,
    ]);
    $idAdherent = (int) $pdo->lastInsertId('adherent_id_adherent_seq');

    // SMS avec identifiants (simulation)
    $logsDir = __DIR__ . '/logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0700, true);
    }
    $logFile = $logsDir . '/sms_' . date('Ymd') . '.log';
    $logLine = sprintf(
        "[%s] SMS envoyé au %s : Bienvenue sur Old Dating ! Votre pseudo : %s, mot de passe : %s\n",
        date('Y-m-d H:i:s'), $telephone, $pseudo, $motDePasseTemp
    );
    $fd = fopen($logFile, 'a');
    if ($fd) {
        chmod($logFile, 0600);
        fwrite($fd, $logLine);
        fclose($fd);
    }

    return [
        'succes'      => true,
        'pseudo'      => $pseudo,
        'mdp_temp'    => $motDePasseTemp,
        'message'     => "Compte créé pour $prenom $nom. SMS envoyé au $telephone.",
        'id_adherent' => $idAdherent,
    ];
}

function pseudoDisponibleAdmin(string $pseudo): bool
{
    $pdo = getConnexion();
    $sql = "SELECT COUNT(*) AS nb FROM adherent WHERE pseudo = :pseudo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pseudo' => $pseudo]);
    return (int) $stmt->fetch()['nb'] === 0;
}


// TS3 - Supprimer un compte

function getAdherentsInactifs(): array
{
    $pdo = getConnexion();
    $sql = "SELECT id_adherent, nom, prenom, pseudo, telephone,
                   date_derniere_connexion,
                   EXTRACT(MONTH FROM AGE(NOW(), date_derniere_connexion))
                       AS mois_inactif
            FROM adherent
            WHERE actif = TRUE
            AND date_derniere_connexion IS NOT NULL
            AND date_derniere_connexion < NOW() - INTERVAL '6 months'
            ORDER BY date_derniere_connexion ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function supprimerCompteAdherent(int $idAdherent, int $idAdmin): bool
{
    $pdo = getConnexion();

    // récupération des infos pour le SMS
    $sqlInfo = "SELECT prenom, telephone FROM adherent WHERE id_adherent = :id";
    $stmtInfo = $pdo->prepare($sqlInfo);
    $stmtInfo->execute([':id' => $idAdherent]);
    $adherent = $stmtInfo->fetch();

    if (!$adherent) return false;

    // SMS avant suppression (simulation)
    $logsDir = __DIR__ . '/logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0700, true);
    }
    $logFile = $logsDir . '/sms_' . date('Ymd') . '.log';
    $logLine = sprintf(
        "[%s] SMS envoyé au %s : Votre compte Old Dating a été supprimé suite à une inactivité prolongée.\n",
        date('Y-m-d H:i:s'), $adherent['telephone']
    );
    $fd = fopen($logFile, 'a');
    if ($fd) {
        chmod($logFile, 0600);
        fwrite($fd, $logLine);
        fclose($fd);
    }

    // ON DELETE CASCADE supprime les données liées (inscriptions, messages, agenda)
    $sqlDel = "DELETE FROM adherent WHERE id_adherent = :id";
    $stmtDel = $pdo->prepare($sqlDel);
    $stmtDel->execute([':id' => $idAdherent]);
    return $stmtDel->rowCount() > 0;
}

function desactiverCompteAdherent(int $idAdherent): bool
{
    $pdo = getConnexion();
    $sql = "UPDATE adherent SET actif = FALSE WHERE id_adherent = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idAdherent]);
    return $stmt->rowCount() > 0;
}

function getTousLesAdherents(): array
{
    $pdo = getConnexion();
    $sql = "SELECT a.id_adherent, a.nom, a.prenom, a.pseudo,
                   a.telephone, a.date_creation,
                   a.date_derniere_connexion, a.statut_chat, a.actif,
                   (SELECT COUNT(*) FROM inscription i
                    WHERE i.id_adherent = a.id_adherent
                    AND i.statut = 'confirme') AS nb_inscriptions,
                   (SELECT COUNT(*) FROM message m
                    WHERE m.id_expediteur = a.id_adherent) AS nb_messages
            FROM adherent a
            ORDER BY a.nom ASC, a.prenom ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getStatistiquesAdmin(): array
{
    $pdo = getConnexion();

    // nb adhérents actifs
    $sqlActifs = "SELECT COUNT(*) AS nb FROM adherent WHERE actif = TRUE";
    $nbActifs = $pdo->query($sqlActifs)->fetch()['nb'];

    // nb adhérents inactifs depuis +6 mois
    $sqlInactifs = "SELECT COUNT(*) AS nb FROM adherent
                    WHERE actif = TRUE
                    AND date_derniere_connexion < NOW() - INTERVAL '6 months'";
    $nbInactifs = $pdo->query($sqlInactifs)->fetch()['nb'];

    // nb événements ce mois
    $sqlEvenements = "SELECT COUNT(*) AS nb FROM evenement
                      WHERE EXTRACT(MONTH FROM date_evenement) = EXTRACT(MONTH FROM NOW())
                      AND EXTRACT(YEAR FROM date_evenement) = EXTRACT(YEAR FROM NOW())";
    $nbEvenements = $pdo->query($sqlEvenements)->fetch()['nb'];

    // nb événements complets ce mois
    $sqlComplets = "SELECT COUNT(*) AS nb FROM evenement e
                    WHERE EXTRACT(MONTH FROM e.date_evenement) = EXTRACT(MONTH FROM NOW())
                    AND (SELECT COUNT(*) FROM inscription i
                         WHERE i.id_evenement = e.id_evenement
                         AND i.statut = 'confirme') >= e.capacite_max";
    $nbComplets = $pdo->query($sqlComplets)->fetch()['nb'];

    return [
        'nb_actifs'     => $nbActifs,
        'nb_inactifs'   => $nbInactifs,
        'nb_evenements' => $nbEvenements,
        'nb_complets'   => $nbComplets,
    ];
}
