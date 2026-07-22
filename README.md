# Chez Clarence — Site vitrine autonome + CMS

Site public de présentation du restaurant, **indépendant de l'application de gestion interne** (celle-ci reste utilisée en local par le personnel). Ce site est **hébergé en ligne** et **entièrement gérable par l'administrateur du restaurant sans compétence technique**, via un panneau d'administration dédié.

Stack : **PHP** (MVC léger, sans framework) + **MySQL** + **HTML/CSS/JavaScript**, avec **Tailwind CSS**, **jQuery**, **Chosen**, **SweetAlert2**, **AOS** et **Swiper**, tous installés via **npm**.

---

## Sommaire

1. [Prérequis](#1-prérequis)
2. [Architecture du projet](#2-architecture-du-projet)
3. [Comment le CMS fonctionne](#3-comment-le-cms-fonctionne)
4. [Installation](#4-installation)
5. [Premier lancement (créer le compte administrateur)](#5-premier-lancement)
6. [Ce que l'administrateur peut modifier](#6-ce-que-ladministrateur-peut-modifier)
7. [Utilisateurs & rôles](#7-utilisateurs--rôles)
8. [Sécurité](#8-sécurité)
9. [Déploiement en production](#9-déploiement-en-production)
10. [Limites assumées](#10-limites-assumées)

---

## 1. Prérequis

- **PHP ≥ 8.1**, avec les extensions :
  - `pdo_mysql` (connexion à la base de données)
  - `mbstring` (chaînes de caractères accentuées — nom du restaurant, description...)
  - `fileinfo` (vérification du type réel des images envoyées depuis le CMS)

  > Ces trois extensions sont activées par défaut chez la quasi-totalité des hébergeurs mutualisés (cPanel, Plesk...). En local, vérifiez avec `php -m | grep -E "pdo_mysql|mbstring|fileinfo"`.
- **MySQL ≥ 5.7** ou **MariaDB ≥ 10.3**.
- **Node.js ≥ 18** et npm — uniquement pour compiler les assets (Tailwind CSS + copie des librairies). Pas requis sur le serveur final si vous déployez les fichiers déjà compilés (voir [§8](#8-déploiement-en-production)).

## 2. Architecture du projet

```
chezclarence-site/
│
├── index.php                     ─┐
├── services.php                    │  PAGES PUBLIQUES
├── 404.php                       ─┘  (aucune écriture en base, 100% lecture)
│
├── admin/                        PANNEAU D'ADMINISTRATION (CMS)
│   ├── install.php                 Premier lancement : création du 1er compte
│   ├── login.php / logout.php      Authentification par session
│   ├── dashboard.php               Vue d'ensemble
│   ├── parametres.php              Identité, coordonnées, WhatsApp, réseaux sociaux
│   ├── categories.php / categorie-form.php     CRUD catégories du menu
│   ├── menus.php / menu-form.php               CRUD plats
│   ├── services.php / service-form.php         CRUD services annexes
│   ├── hero.php / hero-form.php                CRUD slides de la bannière d'accueil
│   ├── stats.php / stat-form.php               CRUD compteurs animés
│   ├── valeurs.php / valeur-form.php           CRUD "valeurs" du restaurant
│   ├── utilisateurs.php / utilisateur-form.php CRUD comptes du back-office (Administrateur uniquement)
│   ├── compte.php                  Profil + mot de passe de l'admin connecté
│   └── includes/
│       ├── auth.php                Session, CSRF, garde d'accès, flash messages
│       ├── upload.php              Upload d'images sécurisé et réutilisable
│       ├── layout-top.php          Sidebar + en-tête communs
│       └── layout-bottom.php       Scripts + fermeture communs
│
├── includes/                     PARTAGÉ ENTRE PAGES PUBLIQUES
│   ├── functions.php               Accès aux données (SELECT uniquement) + utilitaires
│   ├── head.php                    Balises <head> communes (meta, CSS, favicon)
│   ├── header.php                  Barre de navigation (logo, menu, lien WhatsApp)
│   ├── footer.php                  Pied de page (liens, réseaux sociaux, coordonnées)
│   ├── chatbot.php                 Widget flottant de chat WhatsApp (bulle en bas à droite)
│   └── svg/                        6 illustrations SVG animées dessinées sur mesure
│       ├── hero-plate.php            Assiette fumante — slide hero "Commander"
│       ├── hero-table.php            Table dressée — slide hero "Réserver"
│       ├── hero-traiteur.php         Buffet — slide hero "Traiteur"
│       ├── hero-contact.php          Bulles de conversation — slide hero "Contact"
│       ├── about-chef.php            Illustration du chef — section "À propos"
│       └── category-icon.php         Icône générique si une catégorie n'a pas de photo
│
├── config/
│   ├── env.php                   Chargeur .env minimal (sans dépendance)
│   └── database.php              Connexion PDO partagée (site public + admin)
│
├── database/
│   └── schema.sql                 Schéma complet + données de démonstration
│                                   (auto-corrige l'encodage UTF-8 à l'import, voir §4)
│
├── uploads/                       Images envoyées depuis le CMS
│   ├── .htaccess                   Empêche l'exécution de scripts dans ce dossier
│   ├── logo/  categories/  menus/  hero/     (un sous-dossier par type d'image)
│
├── assets/
│   ├── css/custom.css              Styles et animations du site public
│   ├── css/admin.css               Styles du panneau d'administration
│   ├── css/tailwind.css            Généré par `npm run build:css`
│   ├── js/main.js                  Interactions du site public
│   ├── img/favicon.svg             Favicon de secours si aucun logo n'est renseigné
│   └── vendor/                     Librairies npm copiées automatiquement (voir §4)
│
├── src/input.css                 Source Tailwind (3 directives @tailwind)
├── scripts/copy-vendor.js        Copie les librairies npm vers assets/vendor/
├── package.json / tailwind.config.js
├── .env.example                  Modèle de configuration (à copier en .env)
├── .htaccess                     Sécurité + performances (Apache)
└── .gitignore                    Exclut node_modules, assets générés, .env, uploads
```

**Principe directeur** : les pages publiques (`index.php`, `services.php`) ne font **jamais** d'écriture en base — elles lisent uniquement, via les fonctions de `includes/functions.php`. Toute modification de contenu passe **exclusivement** par `admin/`, protégé par authentification. Cette séparation stricte lecture/écriture limite la surface d'attaque du site public.

## 3. Comment le CMS fonctionne

Chaque bloc de contenu du site public correspond à une table de la base de données, éditable depuis une section dédiée du panneau d'administration :

| Table (base de données) | Section du CMS | Bloc du site affecté |
|---|---|---|
| `parametres` | Paramètres généraux | Coordonnées, WhatsApp, logo, réseaux sociaux, pied de page |
| `hero_slides` | Slides d'accueil | Bannière défilante en haut de page |
| `valeurs` | Nos valeurs | 4 cartes de la section « À propos » |
| `statistiques` | Statistiques | Compteurs animés (années d'expérience, clients...) |
| `categories` | Catégories | Cartes de catégories interactives du menu |
| `menus` | Plats | Grille de plats filtrable |
| `services` | Nos services | Aperçus sur l'accueil + détail sur `services.php` |

Aucun code n'a besoin d'être modifié pour changer un texte, une image, un prix ou activer/désactiver un élément : tout se fait par formulaire, avec confirmation avant toute suppression.

## 4. Installation

```bash
# 1. Dépendances npm (Tailwind, FontAwesome, jQuery, SweetAlert2, Chosen, AOS, Swiper)
npm install
# → copie automatiquement les librairies dans assets/vendor/ (postinstall)

# 2. Compiler Tailwind CSS
npm run build:css

# 3. Créer la base de données et l'importer
mysql -u root -p -e "CREATE DATABASE chezclarence_cms CHARACTER SET utf8mb4"
mysql -u root -p --default-character-set=utf8mb4 chezclarence_cms < database/schema.sql

# 4. Configurer la connexion
cp .env.example .env
# puis éditez .env avec vos identifiants MySQL réels
```

> **Import via phpMyAdmin plutôt qu'en ligne de commande ?** Le fichier `database/schema.sql` contient déjà `SET NAMES utf8mb4;` en première ligne utile, donc l'import fonctionne correctement même sans l'option `--default-character-set` — cette option est indiquée ci-dessus par prudence pour un import en ligne de commande, mais n'est plus strictement nécessaire. Sans cette ligne `SET NAMES`, un client MySQL configuré par défaut en `latin1` corromprait silencieusement les valeurs accentuées (`Activé`, `Désactivé`), provoquant une erreur `Data truncated for column 'statut'` dès la première création de contenu — ce piège a été rencontré et corrigé pendant les tests de ce projet.

## 5. Premier lancement

1. Ouvrez `http://votre-domaine/admin/` (ou `/admin/login.php`).
2. Comme aucun compte n'existe encore, vous êtes automatiquement redirigé vers **`/admin/install.php`**.
3. Renseignez le nom, l'e-mail et le mot de passe du tout premier administrateur.
4. Vous êtes connecté immédiatement et redirigé vers le tableau de bord.

> Volontairement, **aucun mot de passe par défaut n'est fourni dans `schema.sql`** : c'est vous qui définissez le premier mot de passe, pour éviter tout compte connu par défaut sur un site en production.

Une fois ce premier compte créé, `install.php` se désactive automatiquement (il redirige vers `login.php` si un compte existe déjà).

## 6. Ce que l'administrateur peut modifier

Sans toucher au code, depuis `/admin` :

- **Identité** : nom, slogan, description, logo.
- **Coordonnées** : adresse, latitude/longitude (carte + GPS), téléphones, e-mail, ville, horaires.
- **WhatsApp** : numéro et message par défaut (tous les boutons du site en dépendent).
- **Réseaux sociaux** : Facebook, Instagram, TikTok.
- **Bannière d'accueil** : ajouter/modifier/réordonner/désactiver des slides, avec leurs boutons d'action (WhatsApp, ancre de page, ou lien externe) et leur illustration. Le titre, sous-titre et texte d'un slide peuvent inclure `{{nom_restaurant}}`, `{{slogan}}`, `{{description}}` ou `{{ville}}` : ces variables sont automatiquement remplacées par les valeurs saisies dans Paramètres généraux, pour ne jamais avoir à dupliquer l'information.
- **Carte du restaurant** : catégories et plats (nom, description, prix, photo, note en étoiles, badge « Populaire », statut actif/inactif, ordre d'affichage).
- **Services** : titre, résumé, description complète, liste de points clés, message WhatsApp dédié.
- **Statistiques** et **valeurs** affichées en page d'accueil.
- **Son propre profil** et mot de passe.
- **Les icônes** de chaque service/statistique/valeur, via un sélecteur visuel (recherche par mot-clé français, aucun code Font Awesome à connaître par cœur).

## 7. Utilisateurs & rôles

Le back-office distingue deux rôles (colonne `role` de `administrateurs`) :

| Rôle | Accès |
|---|---|
| **Administrateur** | Tout ce qui précède, **plus** la gestion des comptes utilisateurs (`/admin/utilisateurs.php`) |
| **Éditeur** | Tout le contenu du site (menu, services, paramètres, slides...), **sauf** la gestion des utilisateurs — page et lien de menu inaccessibles, y compris par URL directe |

Un administrateur crée les autres comptes depuis **Utilisateurs** dans le menu (visible uniquement par les administrateurs), en choisissant le rôle et un mot de passe initial à transmettre à la personne concernée par un canal sûr.

**Garde-fous intégrés** pour ne jamais se retrouver bloqué hors du back-office :
- Un compte ne peut pas se supprimer lui-même.
- Impossible de supprimer, désactiver ou rétrograder le **dernier** administrateur actif du site (la validation vérifie qu'il en resterait au moins un après l'action).
- Désactiver un compte (`actif = 0`) l'empêche de se connecter sans le supprimer définitivement — utile pour un départ temporaire.

## 8. Sécurité

- Mots de passe hachés avec `password_hash()` (jamais stockés en clair).
- Sessions PHP natives, régénérées à chaque connexion.
- **Jeton CSRF** obligatoire sur tous les formulaires d'administration.
- Uploads d'images validés par type MIME réel (pas seulement l'extension), taille limitée à 4 Mo, noms de fichiers regénérés.
- Dossier `uploads/` protégé par `.htaccess` contre l'exécution de scripts.
- Le site public est en lecture seule sur la base : aucune route publique n'écrit en base de données.
- Confirmation obligatoire (SweetAlert2) avant toute suppression dans le CMS.
- Accès à la gestion des utilisateurs vérifié **côté serveur** (`require_administrateur()`), pas seulement masqué dans l'interface.

## 9. Déploiement en production

```bash
npm ci
npm run build       # copie les vendors + compile Tailwind en minifié
```

Uploadez l'ensemble du dossier (hors `node_modules/`), avec :

- `.env` configuré avec les vrais identifiants de base de données (jamais versionné).
- PHP ≥ 8.1 avec les extensions `pdo_mysql`, `mbstring` et `fileinfo` activées (voir [§1](#1-prérequis)).
- Le dossier `uploads/` accessible en écriture par le serveur web (`chmod 755` ou équivalent — certains hébergeurs mutualisés exigent `775`).
- HTTPS actif.
- `APP_ENV=production` dans `.env` pour masquer les erreurs détaillées aux visiteurs.

## 10. Limites assumées

- Les rôles se limitent à deux niveaux (Administrateur / Éditeur) ; pas de permissions plus fines par module (ex. un Éditeur qui n'aurait accès qu'au menu et pas aux paramètres) — à envisager plus tard si le besoin apparaît.
- Les « GIF animés » demandés dans la version précédente ont été remplacés par des **illustrations SVG animées en CSS** : un vrai fichier GIF ne peut pas être généré par un modèle de langage, mais le rendu obtenu est équivalent, plus léger et plus net.
- Le calcul « nombre de commandes → note en étoiles » de l'ancienne version (reliée à l'application interne) n'existe plus ici : la note de chaque plat est désormais définie manuellement par l'administrateur dans le CMS, puisque ce site n'a plus accès aux données de commandes réelles.