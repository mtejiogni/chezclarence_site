-- ══════════════════════════════════════════════════════════════
-- CHEZ CLARENCE — Site vitrine autonome
-- Schéma de la base de données du CMS
-- ──────────────────────────────────────────────────────────────
-- Cette base est INDÉPENDANTE de celle de l'application de
-- gestion interne (utilisée en local par le personnel). Elle ne
-- sert QUE le site public + son panneau d'administration.
--
-- Installation :
--   1. Créez une base MySQL (voir CREATE DATABASE ci-dessous)
--   2. Importez ce fichier entièrement :
--        mysql -u root -p chezclarence_cms < database/schema.sql
--   3. Renseignez .env avec le nom de cette base
--   4. Ouvrez /admin/install.php pour créer le premier compte
--      administrateur (aucun mot de passe n'est pré-rempli dans
--      ce script, pour des raisons de sécurité).
-- ══════════════════════════════════════════════════════════════
DROP DATABASE IF EXISTS chezclarence_site;

CREATE DATABASE chezclarence_site
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE chezclarence_site;

-- Sécurité indispensable : sans cette ligne, un client mysql dont le charset
-- par défaut n'est pas UTF-8 (ex: certains clients en latin1 par défaut)
-- corrompra silencieusement les valeurs accentuées (ex: 'Activé') dès cet
-- import, provoquant ensuite des erreurs "Data truncated for column 'statut'"
-- au moindre enregistrement créé depuis le site.
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

-- ──────────────────────────────────────────────────────────────
-- Comptes d'administration du CMS
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS administrateurs;
CREATE TABLE administrateurs (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom                 VARCHAR(100) NOT NULL,
  email               VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe        VARCHAR(255) NOT NULL COMMENT 'hashé avec password_hash() — jamais en clair',
  role                VARCHAR(30)  NOT NULL DEFAULT 'Administrateur',
  actif               TINYINT(1)   NOT NULL DEFAULT 1,
  derniere_connexion  DATETIME NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Volontairement vide : le premier compte est créé via /admin/install.php

-- ──────────────────────────────────────────────────────────────
-- Jetons de réinitialisation de mot de passe (lien envoyé par e-mail)
-- ──────────────────────────────────────────────────────────────
-- Le jeton lui-même n'est jamais stocké en clair (même principe que
-- pour les mots de passe) : seul son hachage SHA-256 est en base. Un
-- jeton compromis dans une fuite de base ne serait donc pas
-- directement réutilisable.
DROP TABLE IF EXISTS reinitialisations_mot_de_passe;
CREATE TABLE reinitialisations_mot_de_passe (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NOT NULL,
  token_hash  VARCHAR(64)  NOT NULL COMMENT 'sha256 du jeton envoyé par e-mail',
  expire_le   DATETIME     NOT NULL,
  utilise     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_token_hash (token_hash),
  KEY idx_admin_id (admin_id),
  CONSTRAINT fk_reinit_admin FOREIGN KEY (admin_id) REFERENCES administrateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────
-- Limitation des tentatives (connexion + demandes de réinitialisation)
-- ──────────────────────────────────────────────────────────────
-- Une seule table réutilisable pour les deux contextes, distingués
-- par la colonne "contexte". Compte les échecs par e-mail ET par
-- adresse IP séparément (une IP partagée — bureau, box familiale —
-- ne doit pas bloquer instantanément tout le monde après une seule
-- erreur d'un des occupants).
DROP TABLE IF EXISTS limitation_tentatives;
CREATE TABLE limitation_tentatives (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contexte            VARCHAR(30)  NOT NULL COMMENT 'connexion | reinitialisation',
  identifiant         VARCHAR(150) NOT NULL COMMENT 'e-mail (en minuscules) ou adresse IP',
  type                ENUM('email','ip') NOT NULL,
  tentatives          INT UNSIGNED NOT NULL DEFAULT 1,
  derniere_tentative  DATETIME     NOT NULL,
  bloque_jusqua       DATETIME     NULL,
  UNIQUE KEY uniq_cle (contexte, identifiant, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────
-- Paramètres généraux du restaurant (ligne unique)
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS parametres;
CREATE TABLE parametres (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entreprise          VARCHAR(150) DEFAULT NULL,
  nom_restaurant      VARCHAR(150) NOT NULL DEFAULT 'Chez Clarence',
  slogan              VARCHAR(200) DEFAULT NULL,
  description         TEXT DEFAULT NULL,
  logo                VARCHAR(255) DEFAULT NULL,
  adresse             VARCHAR(255) DEFAULT NULL,
  latitude            VARCHAR(50)  DEFAULT NULL,
  longitude           VARCHAR(50)  DEFAULT NULL,
  telephone           VARCHAR(30)  DEFAULT NULL,
  telephone2          VARCHAR(30)  DEFAULT NULL,
  email               VARCHAR(150) DEFAULT NULL,
  ville               VARCHAR(100) DEFAULT 'Douala',
  horaires            VARCHAR(200) DEFAULT NULL,
  whatsapp            VARCHAR(30)  DEFAULT NULL COMMENT 'format international, chiffres uniquement, ex: 237699000000',
  message_whatsapp    VARCHAR(255) DEFAULT NULL COMMENT 'message par défaut du bouton WhatsApp générique',
  facebook            VARCHAR(255) DEFAULT NULL,
  instagram           VARCHAR(255) DEFAULT NULL,
  tiktok              VARCHAR(255) DEFAULT NULL,
  devise              VARCHAR(10)  DEFAULT 'FCFA',
  mention_legale      VARCHAR(255) DEFAULT NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO parametres (
  entreprise, nom_restaurant, slogan, description, adresse, latitude, longitude,
  telephone, ville, horaires, whatsapp, message_whatsapp, devise
) VALUES (
  'Chez Clarence', 'Chez Clarence', 'Restaurant · Snack · Grill — depuis 1990',
  'Une cuisine généreuse, des grillades préparées avec passion et un accueil qui vous fait sentir chez vous.',
  'Akwa, Douala, Cameroun', '4.0511', '9.7679',
  '237699000000', 'Douala', 'Tous les jours · 11h00 – 23h00',
  '237699000000', 'Bonjour, je vous contacte depuis votre site web.', 'FCFA'
);

-- ──────────────────────────────────────────────────────────────
-- Catégories du menu
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  intitule      VARCHAR(100) NOT NULL,
  description   TEXT DEFAULT NULL,
  photo         VARCHAR(255) DEFAULT NULL,
  ordre         INT NOT NULL DEFAULT 0,
  statut        ENUM('Activé','Désactivé') NOT NULL DEFAULT 'Activé',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (intitule, description, ordre) VALUES
  ('Grillades', 'Viandes et poissons grillés à la braise, préparés minute.', 1),
  ('Plats locaux', 'Les grands classiques de la cuisine camerounaise.', 2),
  ('Entrées', 'Pour bien commencer le repas.', 3),
  ('Boissons', 'Jus frais, sodas et boissons locales.', 4);

-- ──────────────────────────────────────────────────────────────
-- Plats (menus)
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS menus;
CREATE TABLE menus (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categorie_id  INT UNSIGNED NOT NULL,
  intitule      VARCHAR(150) NOT NULL,
  description   TEXT DEFAULT NULL,
  prix          DECIMAL(10,2) NOT NULL DEFAULT 0,
  photo         VARCHAR(255) DEFAULT NULL,
  etoiles       TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'note affichée, de 1 à 5 — gérée manuellement par l’administrateur',
  populaire     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'met le plat en avant (badge "Populaire")',
  ordre         INT NOT NULL DEFAULT 0,
  statut        ENUM('Activé','Désactivé') NOT NULL DEFAULT 'Activé',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_menus_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
  CONSTRAINT chk_menus_etoiles CHECK (etoiles BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO menus (categorie_id, intitule, description, prix, etoiles, populaire, ordre) VALUES
  (1, 'Poulet braisé', 'Poulet fermier mariné aux épices locales, grillé à la braise.', 4500, 5, 1, 1),
  (1, 'Brochettes de bœuf', 'Brochettes marinées, servies avec oignons et piment.', 3000, 4, 0, 2),
  (1, 'Poisson braisé', 'Poisson frais du jour, grillé et servi avec sa sauce.', 5000, 5, 1, 3),
  (2, 'Ndolé', 'Ndolé traditionnel aux arachides, viande et crevettes.', 3500, 5, 1, 1),
  (2, 'Poulet DG', 'Poulet sauté aux légumes et plantains mûrs.', 5500, 4, 0, 2),
  (3, 'Beignets haricots', 'Beignets moelleux accompagnés de haricots.', 1500, 3, 0, 1),
  (4, 'Jus de gingembre', 'Jus de gingembre frais fait maison.', 1000, 4, 0, 1),
  (4, 'Bissap', 'Boisson rafraîchissante à base de fleurs d’hibiscus.', 1000, 4, 0, 2);

-- ──────────────────────────────────────────────────────────────
-- Services annexes (privatisation, traiteur, etc.)
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS services;
CREATE TABLE services (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug              VARCHAR(80) NOT NULL UNIQUE COMMENT 'utilisé dans l’URL (services.php#slug)',
  icone             VARCHAR(50) NOT NULL DEFAULT 'fa-star' COMMENT 'nom de classe Font Awesome, ex: fa-key',
  titre             VARCHAR(150) NOT NULL,
  resume            VARCHAR(255) DEFAULT NULL,
  description       TEXT DEFAULT NULL,
  points            TEXT DEFAULT NULL COMMENT 'un point clé par ligne',
  message_whatsapp  VARCHAR(255) DEFAULT NULL,
  ordre             INT NOT NULL DEFAULT 0,
  statut            ENUM('Activé','Désactivé') NOT NULL DEFAULT 'Activé',
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO services (slug, icone, titre, resume, description, points, message_whatsapp, ordre) VALUES
  ('privatisation', 'fa-key', 'Privatisation du restaurant', 'Réservez toute la salle pour vos événements privés.',
   'Anniversaires, réunions d\'entreprise, cérémonies familiales : privatisez tout ou partie du restaurant et profitez d\'un service dédié, d\'une décoration sur mesure et d\'un menu adapté au nombre de convives.',
   'Salle privée ou partielle\nMenu personnalisable\nDécoration sur demande\nService dédié pendant tout l\'événement',
   'Bonjour, je souhaite privatiser le restaurant pour un événement. Pouvez-vous me communiquer vos disponibilités et tarifs ?', 1),

  ('traiteur', 'fa-utensils', 'Service traiteur', 'Nos plats livrés et dressés où que vous soyez.',
   'Mariages, deuils, séminaires, fêtes de famille : notre équipe se déplace avec son savoir-faire pour régaler vos invités, du dressage des buffets jusqu\'au service à table.',
   'Buffets pour tous types d\'événements\nLivraison et dressage sur site\nMenus dégustation sur demande\nDevis gratuit et sans engagement',
   'Bonjour, je souhaite un devis pour une prestation traiteur. Voici les détails de mon événement : ', 2),

  ('carte-cadeau', 'fa-gift', 'Carte cadeau', 'Offrez un moment gourmand à vos proches.',
   'Faites plaisir sans vous tromper : nos cartes cadeaux, valables sur toute la carte, se personnalisent selon le montant et l\'occasion.',
   'Montant libre\nValable sur toute la carte\nRemise en main propre ou numérique\nIdéal pour toutes les occasions',
   'Bonjour, je souhaite offrir une carte cadeau. Pouvez-vous me renseigner sur les montants disponibles ?', 3),

  ('livraison', 'fa-motorcycle', 'Livraison express', 'Vos plats livrés chauds, partout en ville.',
   'Commandez depuis chez vous ou votre bureau : nos livreurs vous apportent vos plats préférés rapidement, en toute fraîcheur.',
   'Livraison rapide en ville\nEmballages qui gardent la chaleur\nSuivi de commande via WhatsApp\nPaiement à la livraison',
   'Bonjour, je souhaite me faire livrer. Voici mon adresse et ma commande : ', 4),

  ('evenements', 'fa-champagne-glasses', 'Organisation d\'événements', 'Décoration et animation pour vos réceptions.',
   'Baptêmes, anniversaires, cérémonies de fin d\'année : nous prenons en charge la décoration, l\'animation et la restauration pour une réception clé en main.',
   'Décoration thématique\nCoordination le jour J\nFormules tout compris\nAccompagnement de A à Z',
   'Bonjour, je souhaite organiser un événement chez vous. Pouvez-vous m\'aider à planifier tout cela ?', 5),

  ('entreprise', 'fa-briefcase', 'Formule groupe & entreprise', 'Déjeuners d\'affaires et séminaires sur mesure.',
   'Des menus adaptés à votre budget et à votre emploi du temps pour vos déjeuners d\'affaires, séminaires et formations, avec facturation groupée possible.',
   'Menus groupe à prix négocié\nFacturation unique possible\nEspace calme pour vos échanges\nHoraires flexibles',
   'Bonjour, je souhaite une offre pour un déjeuner d\'affaires / séminaire d\'entreprise.', 6);

-- ──────────────────────────────────────────────────────────────
-- Slides du hero (bannière d'accueil en carrousel)
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS hero_slides;
CREATE TABLE hero_slides (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  badge               VARCHAR(100) DEFAULT NULL COMMENT 'petit texte au-dessus du titre',
  titre               VARCHAR(200) NOT NULL,
  sous_titre          VARCHAR(255) DEFAULT NULL,
  description         TEXT DEFAULT NULL,
  bouton1_texte       VARCHAR(80) DEFAULT NULL,
  bouton1_type        ENUM('whatsapp','ancre','url') NOT NULL DEFAULT 'whatsapp',
  bouton1_valeur      VARCHAR(255) DEFAULT NULL COMMENT 'message WhatsApp, #ancre ou URL selon le type',
  bouton2_texte       VARCHAR(80) DEFAULT NULL,
  bouton2_type        ENUM('whatsapp','ancre','url') NOT NULL DEFAULT 'ancre',
  bouton2_valeur      VARCHAR(255) DEFAULT NULL,
  illustration        ENUM('plate','table','traiteur','contact','personnalisee') NOT NULL DEFAULT 'plate'
                        COMMENT 'plate/table/traiteur/contact = illustrations dessinées incluses ; personnalisee = utiliser le champ image',
  image               VARCHAR(255) DEFAULT NULL,
  ordre               INT NOT NULL DEFAULT 0,
  statut              ENUM('Activé','Désactivé') NOT NULL DEFAULT 'Activé',
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO hero_slides (badge, titre, sous_titre, description, bouton1_texte, bouton1_type, bouton1_valeur, bouton2_texte, bouton2_type, bouton2_valeur, illustration, ordre) VALUES
  ('Douala · Grillades & Cuisine locale', '{{nom_restaurant}}', '{{slogan}}', '{{description}}',
   'Commander maintenant', 'whatsapp', 'Bonjour, je souhaite passer une commande.', 'Voir la carte', 'ancre', '#menu', 'plate', 1),

  ('Réservation instantanée', 'Réservez votre table<br>en un message', NULL,
   'Dîner en famille, entre amis ou en tête-à-tête : dites-nous simplement l\'heure et le nombre de convives, on s\'occupe du reste.',
   'Réserver une table', 'whatsapp', 'Bonjour, je souhaite réserver une table.', 'Nous trouver', 'ancre', '#localisation', 'table', 2),

  ('Service traiteur & événements', 'Vos événements,<br>notre savoir-faire', NULL,
   'Mariages, séminaires, anniversaires : nous dressons vos buffets et régalons vos invités, où que vous soyez.',
   'Demander un devis', 'whatsapp', 'Bonjour, je souhaite un devis pour une prestation traiteur.', 'Tous nos services', 'url', 'services.php', 'traiteur', 3),

  ('Une question ?', 'Parlons-en<br>sur WhatsApp', NULL,
   'Une envie particulière, une question sur nos plats ou nos horaires ? Notre équipe vous répond en quelques minutes.',
   'Nous écrire', 'whatsapp', '', 'Formulaire de contact', 'ancre', '#contact', 'contact', 4);

-- ──────────────────────────────────────────────────────────────
-- Statistiques / compteurs animés (section chiffres clés)
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS statistiques;
CREATE TABLE statistiques (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  valeur        INT NOT NULL DEFAULT 0,
  suffixe       VARCHAR(10) DEFAULT '',
  label         VARCHAR(150) NOT NULL,
  icone         VARCHAR(50) NOT NULL DEFAULT 'fa-star',
  calcul_auto   ENUM('non','annees_depuis_1990') NOT NULL DEFAULT 'non'
                  COMMENT 'si != non, la valeur est recalculée automatiquement à l’affichage',
  ordre         INT NOT NULL DEFAULT 0,
  statut        ENUM('Activé','Désactivé') NOT NULL DEFAULT 'Activé'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO statistiques (valeur, suffixe, label, icone, calcul_auto, ordre) VALUES
  (35, '+', 'Années d\'expérience', 'fa-award', 'annees_depuis_1990', 1),
  (60, '+', 'Plats au menu', 'fa-utensils', 'non', 2),
  (15000, '+', 'Clients satisfaits', 'fa-users', 'non', 3),
  (30, ' min', 'Livraison moyenne', 'fa-motorcycle', 'non', 4);

-- ──────────────────────────────────────────────────────────────
-- Valeurs du restaurant (section "À propos")
-- ──────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS valeurs;
CREATE TABLE valeurs (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titre     VARCHAR(100) NOT NULL,
  texte     VARCHAR(255) DEFAULT NULL,
  icone     VARCHAR(50) NOT NULL DEFAULT 'fa-star',
  ordre     INT NOT NULL DEFAULT 0,
  statut    ENUM('Activé','Désactivé') NOT NULL DEFAULT 'Activé'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO valeurs (titre, texte, icone, ordre) VALUES
  ('Fraîcheur', 'Produits sélectionnés chaque jour.', 'fa-leaf', 1),
  ('Savoir-faire', 'Recettes maîtrisées depuis 1990.', 'fa-fire-burner', 2),
  ('Hospitalité', 'Un accueil qui vous fait sentir chez vous.', 'fa-heart', 3),
  ('Rapidité', 'Commande et livraison sans attente.', 'fa-bolt', 4);

SET FOREIGN_KEY_CHECKS = 1;