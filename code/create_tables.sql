-- Projet Old Dating - Création des tables PostgreSQL

-- TABLE 1 : ADMINISTRATEUR
CREATE TABLE IF NOT EXISTS administrateur (
    id_admin        SERIAL          PRIMARY KEY,
    nom             VARCHAR(50)     NOT NULL,
    prenom          VARCHAR(50)     NOT NULL,
    login           VARCHAR(50)     NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255)    NOT NULL
);

-- TABLE 2 : ADHERENT
CREATE TABLE IF NOT EXISTS adherent (
    id_adherent             SERIAL          PRIMARY KEY,
    nom                     VARCHAR(50)     NOT NULL,
    prenom                  VARCHAR(50)     NOT NULL,
    telephone               VARCHAR(15)     NOT NULL,
    pseudo                  VARCHAR(20)     NOT NULL UNIQUE,
    avatar                  VARCHAR(255)    DEFAULT NULL,
    mot_de_passe            VARCHAR(255)    NOT NULL,
    date_creation           DATE            NOT NULL,
    date_derniere_connexion TIMESTAMP       DEFAULT NULL,
    statut_chat             VARCHAR(20)     NOT NULL DEFAULT 'en_ligne'
                            CHECK (statut_chat IN ('en_ligne','absent','ne_pas_deranger')),
    actif                   BOOLEAN         NOT NULL DEFAULT TRUE,
    id_admin                INT             NOT NULL,
    CONSTRAINT fk_adherent_admin
        FOREIGN KEY (id_admin) REFERENCES administrateur(id_admin)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- TABLE 3 : EVENEMENT
CREATE TABLE IF NOT EXISTS evenement (
    id_evenement    SERIAL          PRIMARY KEY,
    titre           VARCHAR(100)    NOT NULL,
    description     TEXT            DEFAULT NULL,
    date_evenement  DATE            NOT NULL,
    heure_debut     TIME            NOT NULL,
    heure_fin       TIME            DEFAULT NULL,
    lieu            VARCHAR(255)    NOT NULL,
    capacite_max    INT             NOT NULL DEFAULT 20,
    type            VARCHAR(20)     NOT NULL DEFAULT 'rencontre'
                    CHECK (type IN ('rencontre','activite')),
    categorie       VARCHAR(50)     DEFAULT NULL
);

-- TABLE 4 : INSCRIPTION
CREATE TABLE IF NOT EXISTS inscription (
    id_adherent     INT         NOT NULL,
    id_evenement    INT         NOT NULL,
    date_inscription DATE       NOT NULL,
    sms_envoye      BOOLEAN     NOT NULL DEFAULT FALSE,
    statut          VARCHAR(20) NOT NULL DEFAULT 'confirme'
                    CHECK (statut IN ('confirme','annule')),
    PRIMARY KEY (id_adherent, id_evenement),
    CONSTRAINT fk_inscription_adherent
        FOREIGN KEY (id_adherent) REFERENCES adherent(id_adherent)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_inscription_evenement
        FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- TABLE 5 : CONVERSATION
CREATE TABLE IF NOT EXISTS conversation (
    id_conversation SERIAL      PRIMARY KEY,
    date_creation   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- TABLE 6 : PARTICIPE
CREATE TABLE IF NOT EXISTS participe (
    id_adherent     INT         NOT NULL,
    id_conversation INT         NOT NULL,
    date_entree     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_adherent, id_conversation),
    CONSTRAINT fk_participe_adherent
        FOREIGN KEY (id_adherent) REFERENCES adherent(id_adherent)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_participe_conversation
        FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- TABLE 7 : MESSAGE
CREATE TABLE IF NOT EXISTS message (
    id_message      SERIAL      PRIMARY KEY,
    id_conversation INT         NOT NULL,
    id_expediteur   INT         NOT NULL,
    contenu         TEXT        NOT NULL,
    date_envoi      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    type_message    VARCHAR(20) NOT NULL DEFAULT 'normal'
                    CHECK (type_message IN ('normal','anniversaire','fetes')),
    lu              BOOLEAN     NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_message_conversation
        FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_expediteur
        FOREIGN KEY (id_expediteur) REFERENCES adherent(id_adherent)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- TABLE 8 : ENTREE_AGENDA
CREATE TABLE IF NOT EXISTS entree_agenda (
    id_entree       SERIAL          PRIMARY KEY,
    id_adherent     INT             NOT NULL,
    titre           VARCHAR(100)    NOT NULL,
    date_entree     DATE            NOT NULL,
    heure           TIME            DEFAULT NULL,
    note            TEXT            DEFAULT NULL,
    type_entree     VARCHAR(20)     NOT NULL DEFAULT 'autre'
                    CHECK (type_entree IN ('rdv','anniversaire','autre')),
    CONSTRAINT fk_entree_adherent
        FOREIGN KEY (id_adherent) REFERENCES adherent(id_adherent)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Donnees de test

-- Compte administrateur par defaut
INSERT INTO administrateur (nom, prenom, login, mot_de_passe)
VALUES ('TRAN', 'Louis', 'admin', 'REMPLACER_PAR_HASH');

-- Comptes adherents de test
INSERT INTO adherent (nom, prenom, telephone, pseudo, mot_de_passe, date_creation, statut_chat, actif, id_admin)
VALUES
    ('Dupont', 'Marie', '0601020342', 'Marie75', 'REMPLACER_PAR_HASH', '2026-03-16', 'en_ligne', TRUE, 1),
    ('Martin', 'Jean-Pierre', '0609080706', 'JeanPierre72', 'REMPLACER_PAR_HASH', '2026-03-16', 'en_ligne', TRUE, 1),
    ('Moreau', 'Suzanne', '0607060504', 'Suzanne68', 'REMPLACER_PAR_HASH', '2026-03-20', 'absent', TRUE, 1),
    ('Petit', 'Robert', '0603040506', 'RobertP', 'REMPLACER_PAR_HASH', '2026-03-22', 'ne_pas_deranger', TRUE, 1);

-- Événements test
INSERT INTO evenement (titre, description, date_evenement, heure_debut, heure_fin, lieu, capacite_max, type, categorie)
VALUES
    ('Après-midi rencontre – Groupe Printemps', 'Après-midi conviviale.', '2026-06-04', '14:00:00', '17:00:00', 'Salle des fêtes, Paris 15e', 20, 'rencontre', NULL),
    ('Café-rencontre du mercredi', 'Café et discussion.', '2026-06-11', '15:00:00', '17:00:00', 'Foyer des seniors, Paris 12e', 15, 'rencontre', NULL),
    ('Chorale du jeudi matin', 'Chorale hebdomadaire.', '2026-06-05', '10:00:00', '11:30:00', 'Centre social Les Lilas', 30, 'activite', 'chants'),
    ('Belote & Coinche', 'Tournoi débutant.', '2026-06-05', '14:30:00', '17:00:00', 'Salon du foyer', 8, 'activite', 'jeux_cartes'),
    ('Initiation danse de salon', 'Valse et tango.', '2026-06-10', '15:00:00', '16:30:00', 'Vincennes', 20, 'activite', 'danses'),
    ('Atelier peinture aquarelle', 'Découverte peinture.', '2026-06-24', '14:00:00', '16:00:00', 'Mairie du 5e', 12, 'activite', 'autre');

-- Inscriptions test
INSERT INTO inscription (id_adherent, id_evenement, date_inscription, sms_envoye, statut)
VALUES (1, 1, '2026-05-28', TRUE, 'confirme'), (2, 1, '2026-05-29', TRUE, 'confirme'), (1, 3, '2026-05-30', TRUE, 'confirme');

-- Conversation test Marie ↔ Jean-Pierre
INSERT INTO conversation (date_creation) VALUES ('2026-05-30 10:00:00');
INSERT INTO participe (id_adherent, id_conversation, date_entree) VALUES (1, 1, '2026-05-30 10:00:00');
INSERT INTO participe (id_adherent, id_conversation, date_entree) VALUES (2, 1, '2026-05-30 10:00:00');

-- Messages test
INSERT INTO message (id_conversation, id_expediteur, contenu, date_envoi, type_message, lu)
VALUES
    (1, 2, 'Bonjour Marie ! Vous venez à l''après-midi rencontre mercredi ?', '2026-05-31 11:38:00', 'normal', TRUE),
    (1, 1, 'Oui, j''y serai avec plaisir !', '2026-05-31 11:40:00', 'normal', TRUE),
    (1, 2, 'Parfait ! J''ai aussi invité Suzanne et Robert.', '2026-05-31 11:41:00', 'normal', TRUE),
    (1, 1, 'Merveilleux ! À mercredi alors', '2026-05-31 11:42:00', 'normal', FALSE);

-- Agenda test
INSERT INTO entree_agenda (id_adherent, titre, date_entree, heure, note, type_entree)
VALUES
    (1, 'Rendez-vous médecin', '2026-06-18', '10:30:00', 'Apporter la carte vitale', 'rdv'),
    (1, 'Anniversaire de Jean-Pierre', '2026-06-12', NULL, 'Ne pas oublier !', 'anniversaire');
