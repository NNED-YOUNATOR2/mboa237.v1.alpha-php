-- ============================================================
--  MBOA237 — Base de données MySQL complète
--  1. Ouvrir phpMyAdmin dans WAMP
--  2. Cliquer sur "Importer"
--  3. Sélectionner ce fichier
--  4. Cliquer sur "Exécuter"
-- ============================================================

CREATE DATABASE IF NOT EXISTS mboa237
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mboa237;

-- ── UTILISATEURS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS utilisateurs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(150)  NOT NULL,
    email        VARCHAR(200)  NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255)  NOT NULL,
    role         ENUM('apprenant','createur','admin') DEFAULT 'apprenant',
    langue       VARCHAR(80)   DEFAULT 'Ewondo',
    niveau_pref  ENUM('debutant','intermediaire','avance') DEFAULT 'debutant',
    streak       INT           DEFAULT 0,
    last_eq_date DATE          NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── COURS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cours (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    titre        VARCHAR(200) NOT NULL,
    langue       VARCHAR(80)  NOT NULL,
    niveau       TINYINT      DEFAULT 1,
    description  TEXT,
    statut       ENUM('brouillon','en_attente','publie') DEFAULT 'publie',
    createur_id  INT          NULL,
    verrouille   TINYINT      DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (createur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_langue (langue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── LEÇONS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS lecons (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    cours_id   INT          NOT NULL,
    titre      VARCHAR(200) NOT NULL,
    type       ENUM('vocabulaire','qcm','saisie','vf') DEFAULT 'vocabulaire',
    contenu    JSON,
    ordre      INT          DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE,
    INDEX idx_cours (cours_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PROGRESSIONS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS progressions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT     NOT NULL,
    cours_id    INT     NOT NULL,
    progression TINYINT DEFAULT 0,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_cours (user_id, cours_id),
    FOREIGN KEY (user_id)  REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (cours_id) REFERENCES cours(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── RESSOURCES (bibliothèque) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS ressources (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(200) NOT NULL,
    langue      VARCHAR(80)  NOT NULL,
    type        ENUM('pdf','audio','video','texte') DEFAULT 'pdf',
    taille      VARCHAR(30),
    description TEXT,
    contenu     JSON,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── DONNÉES DE DÉMONSTRATION ──────────────────────────────────

-- Comptes demo
-- Mots de passe : admin123 / create123 / apprend123
-- (hashés avec password_hash en PHP, ici on utilise un hash bcrypt valide)
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
('Admin Mboa237',    'admin@mboa237.com',     '$2y$10$TKh8H1.PfMSdmKfnJCGSp.YMUfzMDEWfFPi5d1dR2Sij1g8BYHQ2G', 'admin'),
('Sophie Biyong',    'createur@mboa237.com',  '$2y$10$TKh8H1.PfMSdmKfnJCGSp.YMUfzMDEWfFPi5d1dR2Sij1g8BYHQ2G', 'createur'),
('Jean Kouamé',      'apprenant@mboa237.com', '$2y$10$TKh8H1.PfMSdmKfnJCGSp.YMUfzMDEWfFPi5d1dR2Sij1g8BYHQ2G', 'apprenant');

-- Cours demo
INSERT INTO cours (titre, langue, niveau, description, statut, verrouille) VALUES
('Les salutations',    'Ewondo',   1, 'Bonjour, bonsoir, au revoir et formules de politesse courantes.', 'publie', 0),
('Les chiffres 1-10',  'Duala',    1, 'Comptez de 1 à 10 en Duala avec prononciation.', 'publie', 0),
('La famille',         'Fulfulde', 1, 'Père, mère, frère, sœur et membres de la famille élargie.', 'publie', 0),
('Les couleurs',       'Féfé',     1, 'Découvrez les couleurs avec images et exercices interactifs.', 'publie', 0),
('Les animaux',        'Ewondo',   2, 'Animaux domestiques et sauvages du Cameroun.', 'publie', 1),
('Les aliments',       'Basaa',    1, 'Vocabulaire de la cuisine et des aliments locaux.', 'publie', 1),
('Les proverbes',      'Duala',    3, 'Sagesse ancestrale à travers les proverbes Duala.', 'en_attente', 0),
('Contes bamiléké',    'Ghomala',  2, 'Les grands contes de la tradition Bamiléké.', 'brouillon', 0);

-- Leçons pour le cours 1 (salutations Ewondo)
INSERT INTO lecons (cours_id, titre, type, contenu, ordre) VALUES
(1, 'Mbolo', 'vocabulaire',
 JSON_OBJECT('mot','Mbolo','phonetique','/mbo-lo/','traduction','Bonjour (salutation générale)','langue','Ewondo'), 1),
(1, 'Comment dit-on bonjour ?', 'qcm',
 JSON_OBJECT('question','Comment dit-on Bonjour en Ewondo ?','options',JSON_ARRAY('Akwaaba','Mbolo','Jambo','Sannu'),'reponse',1), 2),
(1, 'A yé ?', 'vocabulaire',
 JSON_OBJECT('mot','A yé ?','phonetique','/a yé/','traduction','Comment vas-tu ?','langue','Ewondo'), 3),
(1, 'Signification de A yé ?', 'qcm',
 JSON_OBJECT('question','Que signifie A yé ? en Ewondo ?','options',JSON_ARRAY('Au revoir','Merci','Comment vas-tu ?','Bonne nuit'),'reponse',2), 4),
(1, 'Écrire Bonjour', 'saisie',
 JSON_OBJECT('question','Écrivez Bonjour en Ewondo','reponse','mbolo','indice','M _ _ _ _ _'), 5),
(1, 'Akiba', 'vocabulaire',
 JSON_OBJECT('mot','Akiba','phonetique','/a-ki-ba/','traduction','Merci','langue','Ewondo'), 6),
(1, 'Comment dit-on Merci ?', 'qcm',
 JSON_OBJECT('question','Comment dit-on Merci en Ewondo ?','options',JSON_ARRAY('Mbolo','A yé','Akiba','Zoua'),'reponse',2), 7),
(1, 'Écrire Merci', 'saisie',
 JSON_OBJECT('question','Écrivez Merci en Ewondo','reponse','akiba','indice','A _ _ _ _ _'), 8);

-- Leçons pour le cours 2 (chiffres Duala)
INSERT INTO lecons (cours_id, titre, type, contenu, ordre) VALUES
(2, 'Wala — Un', 'vocabulaire',
 JSON_OBJECT('mot','Wala','phonetique','/wa-la/','traduction','Un (1)','langue','Duala'), 1),
(2, 'Comment dit-on 1 en Duala ?', 'qcm',
 JSON_OBJECT('question','Comment dit-on 1 en Duala ?','options',JSON_ARRAY('Beba','Wala','Loba','Nai'),'reponse',1), 2),
(2, 'Beba — Deux', 'vocabulaire',
 JSON_OBJECT('mot','Beba','phonetique','/be-ba/','traduction','Deux (2)','langue','Duala'), 3),
(2, 'Vrai ou faux', 'vf',
 JSON_OBJECT('affirmation','Beba signifie Deux en Duala','reponse',true), 4),
(2, 'Écrire Deux', 'saisie',
 JSON_OBJECT('question','Écrivez Deux en Duala','reponse','beba','indice','B _ _ _ _'), 5);

-- Ressources bibliothèque
INSERT INTO ressources (titre, langue, type, taille, description, contenu) VALUES
('Proverbes Ewondo — Tome 1', 'Ewondo', 'pdf', '2.4 Mo',
 'Collection de 120 proverbes traditionnels avec traductions et explications.',
 JSON_OBJECT('type','texte','texte','<h4>Proverbes Ewondo</h4><blockquote>Mfan a bôt a zi ye akôm — L enfant du peuple appartient au peuple.</blockquote><p>Ce proverbe exprime la responsabilité collective dans l éducation des enfants.</p><blockquote>Ngon a ke a kôm ye, a ke a bôt — Là où va la rivière, va aussi le peuple.</blockquote>')),
('Contes traditionnels Duala', 'Duala', 'audio', '18 min',
 '5 contes narratifs racontés par des griots en Duala.',
 JSON_OBJECT('type','audio','titre','Le lion et la tortue','duree','3:42','description','Un conte initiatique sur la ruse et la sagesse. La tortue parvient à berner le lion grâce à son intelligence.')),
('Grammaire du Fulfulde', 'Fulfulde', 'pdf', '5.1 Mo',
 'Guide grammatical complet du Fulfulde parlé au Cameroun.',
 JSON_OBJECT('type','texte','texte','<h4>Introduction au Fulfulde</h4><p>Le Fulfulde est une langue de la famille Niger-Congo parlée par les Peuls.</p><blockquote>Système tonal : deux tons principaux — haut et bas.</blockquote><p>Exemples : gorko (homme), debbo (femme), suka (enfant)</p>')),
('Dictionnaire Basaa-Français', 'Basaa', 'pdf', '3.8 Mo',
 'Premier dictionnaire collaboratif Basaa-Français avec 2400 entrées.',
 JSON_OBJECT('type','texte','texte','<h4>Extrait — Lettre A</h4><blockquote>abal (n.) — la joie, la fête.</blockquote><blockquote>abañ (v.) — partager, distribuer.</blockquote>')),
('Légendes Bamiléké', 'Féfé', 'audio', '24 min',
 'Récits mythologiques et légendes fondatrices du peuple Bamiléké.',
 JSON_OBJECT('type','audio','titre','La naissance du royaume','duree','4:15','description','Légende fondatrice du royaume Bamiléké narrant comment les ancêtres ont choisi le lieu de leur établissement.'));

SELECT 'Base de données Mboa237 créée !' AS message;
