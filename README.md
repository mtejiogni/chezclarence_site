# Chez Clarence — Site vitrine autonome + CMS

Site internet du restaurant, avec un panneau d'administration intégré qui permet à **n'importe qui, sans connaissance en programmation**, de mettre à jour le contenu du site (menu, photos, coordonnées, horaires...) depuis un navigateur.

Ce site est **indépendant** de l'application de gestion interne utilisée en local par le personnel (caisse, commandes...) — les deux ne partagent ni base de données, ni code.

**Technologies utilisées** : PHP + MySQL pour la partie serveur, Tailwind CSS pour le style, et quelques librairies JavaScript (jQuery, SweetAlert2, AOS, Swiper) installées via npm. Aucun framework lourd (pas de Laravel, Symfony...) — le code reste simple à lire et à modifier.

---

## Sommaire

1. [Démarrage rapide](#1-démarrage-rapide)
2. [Prérequis](#2-prérequis)
3. [Architecture du projet](#3-architecture-du-projet)
4. [Comment le CMS fonctionne](#4-comment-le-cms-fonctionne)
5. [Installation détaillée](#5-installation-détaillée)
6. [Ce que l'administrateur peut modifier](#6-ce-que-ladministrateur-peut-modifier)
7. [Utilisateurs & rôles](#7-utilisateurs--rôles)
8. [Mot de passe oublié](#8-mot-de-passe-oublié)
9. [Sécurité](#9-sécurité)
10. [Déploiement en production](#10-déploiement-en-production)
11. [Questions fréquentes / Dépannage](#11-questions-fréquentes--dépannage)
12. [Limites assumées](#12-limites-assumées)

---

## 1. Démarrage rapide

Pour les pressés — la version longue et les explications suivent plus bas.

```bash
# 1. Installer les dépendances et compiler le CSS
npm install
npm run build:css

# 2. Copier le modèle de configuration et le remplir avec vos identifiants MySQL
cp .env.example .env

# 3. Ouvrir le site dans un navigateur
#    → /admin/ vous guide automatiquement à travers le reste
#      (vérifications du serveur, création des tables, création
#      de votre compte) : plus besoin de taper la moindre commande SQL.
```

C'est tout. La suite de ce document explique chaque étape en détail, plus la sécurité, le dépannage, etc.

## 2. Prérequis

- **PHP 8.1 ou plus récent**, avec 3 extensions activées :
  - `pdo_mysql` — indispensable pour parler à la base de données
  - `mbstring` — indispensable pour les caractères accentués (« é », « à »...)
  - `fileinfo` — sert à vérifier que les images envoyées sont bien des images

  > Ces trois extensions sont activées **par défaut** chez presque tous les hébergeurs (cPanel, Plesk...). L'installateur du site (`/admin/install.php`) les vérifie automatiquement et vous dit clairement si l'une d'elles manque.

- **MySQL 5.7+** ou **MariaDB 10.3+** — la base de données. Sur un hébergement mutualisé, elle se crée depuis le panneau de gestion de votre hébergeur (cPanel, Plesk...), pas depuis ce projet.
- **Node.js 18+** et **npm** — uniquement pour préparer les fichiers CSS/JS avant la mise en ligne. Une fois cette préparation faite sur votre ordinateur, **le serveur final n'a pas besoin de Node.js du tout**.

## 3. Architecture du projet

```
chezclarence-site/
│
├── index.php                     ─┐
├── services.php                    │  PAGES PUBLIQUES
├── 404.php                       ─┘  (aucune écriture en base, 100% lecture)
│
├── admin/                        PANNEAU D'ADMINISTRATION (CMS)
│   ├── index.php                   Point d'entrée : redirige automatiquement
│   │                                vers install/login/dashboard selon la situation
│   ├── install.php                 Assistant d'installation (vérifications serveur +
│   │                                création des tables + premier compte)
│   ├── login.php / logout.php      Connexion / déconnexion
│   ├── mot-de-passe-oublie.php     Demande de réinitialisation par e-mail
│   ├── reinitialiser-mot-de-passe.php  Choix du nouveau mot de passe
│   ├── test-email.php              Vérifie que l'envoi d'e-mail fonctionne sur le serveur
│   ├── dashboard.php               Vue d'ensemble
│   ├── parametres.php              Identité, coordonnées, WhatsApp, réseaux sociaux
│   ├── categories.php / categorie-form.php     Catégories du menu
│   ├── menus.php / menu-form.php               Plats
│   ├── services.php / service-form.php         Services annexes
│   ├── hero.php / hero-form.php                Bannière d'accueil
│   ├── stats.php / stat-form.php               Compteurs animés
│   ├── valeurs.php / valeur-form.php           "Valeurs" du restaurant
│   ├── utilisateurs.php / utilisateur-form.php Comptes du back-office (Administrateur uniquement)
│   ├── compte.php                  Profil + mot de passe de l'admin connecté
│   └── includes/
│       ├── auth.php                Connexion, sécurité, limitation des tentatives,
│       │                            récupération de mot de passe
│       ├── mailer.php              Envoi d'e-mails (mot de passe oublié)
│       ├── upload.php              Réception sécurisée des images envoyées
│       ├── layout-top.php          Menu latéral + en-tête communs
│       └── layout-bottom.php       Scripts + fermeture communs
│
├── includes/                     PARTAGÉ ENTRE PAGES PUBLIQUES
│   ├── functions.php               Accès aux données (lecture uniquement) + utilitaires
│   ├── head.php                    Balises <head> communes (titre, styles, favicon)
│   ├── header.php                  Barre de navigation (logo, menu, bouton WhatsApp)
│   ├── footer.php                  Pied de page (liens, réseaux sociaux, coordonnées)
│   ├── chatbot.php                 Bulle de chat WhatsApp flottante
│   └── svg/                        6 illustrations dessinées sur mesure, animées en CSS
│
├── config/
│   ├── env.php                     Lit le fichier .env
│   └── database.php                Connexion à la base de données, partagée par tout le site
│
├── database/
│   └── schema.sql                  Description complète de la base + données d'exemple
│
├── uploads/                        Images envoyées depuis le CMS
│   ├── .htaccess                    Empêche l'exécution de scripts dans ce dossier
│   └── logo/  categories/  menus/  hero/     (un sous-dossier par type d'image)
│
├── assets/
│   ├── css/custom.css               Styles et animations du site public
│   ├── css/admin.css                Styles du panneau d'administration
│   ├── css/tailwind.css             Généré automatiquement — ne pas modifier à la main
│   ├── js/main.js                   Interactions du site public
│   ├── js/admin.js                  Interactions du panneau d'administration
│   ├── img/favicon.svg              Icône de secours si aucun logo n'est renseigné
│   └── vendor/                      Librairies copiées automatiquement par npm
│
├── src/input.css                   Point de départ de Tailwind CSS
├── scripts/copy-vendor.js          Copie les librairies npm vers assets/vendor/
├── package.json / tailwind.config.js
├── .env.example                    Modèle de configuration à copier en .env
├── .htaccess                       Sécurité et performance (serveur Apache)
└── .gitignore                      Fichiers volontairement exclus du suivi Git
```

**Principe important** : les pages publiques (`index.php`, `services.php`) ne font **jamais** d'écriture en base de données — elles se contentent de lire, via les fonctions de `includes/functions.php`. Toute modification passe **exclusivement** par `/admin`, qui exige d'être connecté. Cette séparation limite fortement ce qu'un visiteur malintentionné pourrait tenter.

## 4. Comment le CMS fonctionne

Chaque bloc affiché sur le site correspond à une table de la base de données, modifiable depuis une page dédiée du panneau d'administration — aucune ligne de code à toucher :

| Ce que vous modifiez dans `/admin` | Ce qui change sur le site |
|---|---|
| Paramètres généraux | Coordonnées, WhatsApp, logo, réseaux sociaux, pied de page |
| Slides d'accueil | La grande bannière défilante en haut de la page d'accueil |
| Nos valeurs | Les 4 petites cartes de la section « À propos » |
| Statistiques | Les chiffres animés (années d'expérience, clients...) |
| Catégories | Les cartes de catégories du menu |
| Plats | La grille de plats, avec filtres |
| Services | Les aperçus sur l'accueil + le détail sur la page Services |

Chaque modification prend effet **immédiatement** sur le site public dès l'enregistrement.

## 5. Installation détaillée

### Étape 1 — Préparer les fichiers CSS et JavaScript

Sur votre ordinateur (pas besoin sur le serveur final) :

```bash
npm install        # installe Tailwind CSS + les librairies, les copie automatiquement
npm run build:css  # génère assets/css/tailwind.css
```

### Étape 2 — Créer la base de données

Sur la plupart des hébergements, cette étape se fait **depuis le panneau de votre hébergeur** (cPanel, Plesk...), pas en ligne de commande : créez une base MySQL vide, en encodage `utf8mb4`, et notez son nom, son utilisateur et son mot de passe.

> En local (WampServer, XAMPP, MAMP...), vous pouvez aussi la créer en une commande :
> ```bash
> mysql -u root -p -e "CREATE DATABASE chezclarence_cms CHARACTER SET utf8mb4"
> ```

### Étape 3 — Renseigner la configuration

```bash
cp .env.example .env
```

Ouvrez `.env` dans un éditeur de texte et remplissez `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD` avec les identifiants de l'étape précédente.

### Étape 4 — Laisser l'assistant faire le reste

Ouvrez `http://votre-domaine/admin/` dans un navigateur. Vous êtes accueilli par un assistant en 3 étapes :

1. **Vérifications du serveur** — PHP, extensions, connexion à la base : tout est contrôlé automatiquement, avec une explication claire si quelque chose manque.
2. **Base de données** — un bouton **« Installer les tables maintenant »** crée toutes les tables nécessaires à partir de `database/schema.sql`, **sans une seule commande à taper**.
3. **Compte administrateur** — vous choisissez votre nom, votre e-mail et votre mot de passe (avec un indicateur de force en direct). Vous êtes connecté automatiquement juste après.

> Volontairement, aucun mot de passe par défaut n'est fourni nulle part : c'est vous qui définissez le tout premier, pour qu'aucun site en production n'ait jamais un identifiant connu à l'avance.

Une fois ce compte créé, l'assistant se désactive tout seul (il redirige vers la page de connexion si un compte existe déjà) — il ne peut pas être relancé par erreur.

## 6. Ce que l'administrateur peut modifier

Sans toucher au code, depuis `/admin` :

- **Identité** : nom, slogan, description, logo.
- **Coordonnées** : adresse, position GPS (carte + itinéraire), téléphones, e-mail, ville, horaires.
- **WhatsApp** : numéro et message par défaut — tous les boutons du site en dépendent.
- **Réseaux sociaux** : Facebook, Instagram, TikTok.
- **Bannière d'accueil** : ajouter/modifier/réordonner/désactiver des slides, avec leurs boutons d'action (WhatsApp, aller vers une section, ou lien externe) et leur illustration. Le titre et le texte peuvent inclure `{{nom_restaurant}}`, `{{slogan}}`, `{{description}}` ou `{{ville}}`, remplacés automatiquement par les valeurs saisies dans les Paramètres généraux.
- **Carte du restaurant** : catégories et plats (nom, description, prix, photo, note en étoiles, badge « Populaire », visible/masqué, ordre d'affichage).
- **Services** : titre, résumé, description complète, liste de points clés, message WhatsApp dédié.
- **Statistiques** et **valeurs** de la page d'accueil.
- **Son propre profil** et mot de passe.
- **Les icônes** de chaque service/statistique/valeur, via un sélecteur visuel avec recherche en français — aucun code technique à connaître.

## 7. Utilisateurs & rôles

Le back-office distingue deux niveaux d'accès :

| Rôle | Accès |
|---|---|
| **Administrateur** | Tout ce qui précède, **plus** la gestion des comptes utilisateurs |
| **Éditeur** | Tout le contenu du site, **sauf** la gestion des utilisateurs |

Un administrateur crée les autres comptes depuis **Utilisateurs** dans le menu (visible uniquement par les administrateurs), en choisissant un rôle et un mot de passe initial à transmettre à la personne concernée par un moyen sûr (pas par e-mail en clair, idéalement).

**Garde-fous automatiques**, pour ne jamais se retrouver bloqué hors du site :
- Un compte ne peut pas se supprimer lui-même.
- Impossible de supprimer, désactiver ou rétrograder le **dernier** administrateur actif.
- Désactiver un compte l'empêche de se connecter, sans le supprimer définitivement — utile pour un départ temporaire.

## 8. Mot de passe oublié

Depuis la page de connexion, le lien **« Mot de passe oublié ? »** permet de recevoir par e-mail un lien pour en choisir un nouveau, valable 1 heure et utilisable une seule fois.

**Cette fonctionnalité dépend de la configuration de votre serveur** : elle utilise l'envoi d'e-mail standard de PHP, qui doit être activé chez votre hébergeur pour fonctionner (c'est le cas chez la grande majorité d'entre eux). Pour vérifier que ça fonctionne réellement sur votre serveur, une fois connecté, ouvrez **`/admin/test-email.php`** : il vous envoie un e-mail de test à votre propre adresse et vous dit immédiatement si ça a fonctionné.

> Si le test échoue, contactez le support technique de votre hébergeur en leur demandant si la fonction `mail()` de PHP est activée pour votre compte — c'est la cause la plus fréquente.

## 9. Sécurité

- Mots de passe hachés avec `password_hash()` — jamais stockés en clair.
- Sessions régénérées à chaque connexion, cookie de session effacé à la déconnexion.
- **Jeton CSRF** obligatoire sur tous les formulaires du panneau d'administration.
- **Limitation des tentatives de connexion** : après plusieurs échecs (5 par e-mail, 15 par appareil), un blocage temporaire de 15 minutes se déclenche automatiquement — protège contre les essais automatisés de mots de passe.
- **Récupération de mot de passe** sécurisée : le lien envoyé par e-mail n'est jamais stocké en clair en base (seul son empreinte l'est), expire au bout d'une heure, et ne peut servir qu'une seule fois.
- Uploads d'images vérifiés par leur contenu réel (pas seulement leur extension), limités à 4 Mo, renommés automatiquement.
- Dossier `uploads/` protégé contre l'exécution de scripts.
- Le site public n'écrit jamais en base de données — aucune page publique ne peut modifier le contenu.
- Confirmation obligatoire avant toute suppression dans le CMS.
- L'accès à la gestion des utilisateurs est vérifié **côté serveur**, pas seulement caché dans l'interface — impossible d'y accéder en devinant l'adresse.

## 10. Déploiement en production

```bash
npm ci
npm run build       # copie les librairies + compile Tailwind en version minifiée
```

Envoyez l'ensemble du dossier sur votre hébergeur (tout sauf `node_modules/`, qui n'est utile que sur votre ordinateur), avec :

- `.env` rempli avec vos vrais identifiants de base de données (ne le versionnez jamais dans Git).
- PHP 8.1+ avec `pdo_mysql`, `mbstring` et `fileinfo` activées.
- Le dossier `uploads/` accessible en écriture par le serveur (`chmod 755`, ou `775` chez certains hébergeurs).
- HTTPS activé.
- `APP_ENV=production` dans `.env`, pour ne pas afficher les détails techniques des erreurs aux visiteurs.

Une fois en ligne, ouvrez `/admin/` : l'assistant d'installation prend le relais pour créer les tables et votre compte, exactement comme en local.

## 11. Questions fréquentes / Dépannage

**« Data truncated for column 'statut' » lors de l'import de la base**
→ Le client MySQL utilisé n'était pas en UTF-8. Le fichier `schema.sql` contient déjà `SET NAMES utf8mb4;` pour éviter ce piège lors d'un import via ligne de commande — mais si vous importez autrement (un outil tiers, par exemple), assurez-vous qu'il utilise bien l'encodage UTF-8.

**Le CSS ne semble pas à jour après une modification**
→ `assets/css/tailwind.css` est un fichier **généré**, jamais modifié à la main. Après tout changement de style ou de configuration, relancez `npm run build:css`, puis rechargez la page en navigation privée pour écarter le cache du navigateur.

**Erreur liée à `mb_substr` ou aux caractères accentués**
→ L'extension PHP `mbstring` n'est pas activée sur le serveur. L'assistant d'installation (`/admin/install.php`) le signale désormais clairement dès son premier écran.

**L'e-mail de récupération de mot de passe n'arrive jamais**
→ Testez avec `/admin/test-email.php` une fois connecté (voir [§8](#8-mot-de-passe-oublié)). Si le test échoue, c'est une question de configuration de votre hébergeur, pas du code du site.

**« Trop de tentatives, réessayez dans X minutes » alors que je tape le bon mot de passe**
→ La limitation des tentatives protège contre les essais automatisés — si vous avez enchaîné plusieurs erreurs de frappe, elle se déclenche aussi pour vous. Patientez le temps indiqué, ou utilisez « Mot de passe oublié » pour en choisir un nouveau sans attendre.

**L'installateur ne propose pas de créer la base de données lui-même**
→ C'est volontaire : sur la quasi-totalité des hébergements mutualisés, votre utilisateur MySQL n'a jamais le droit de créer une base — seulement d'agir à l'intérieur d'une base déjà existante. Créez-la depuis le panneau de votre hébergeur, l'assistant s'occupe du reste.

**J'ai oublié le mot de passe de mon unique compte administrateur, et l'e-mail ne fonctionne pas**
→ En dernier recours, un accès direct à la base de données (phpMyAdmin ou équivalent) permet de mettre à jour manuellement la colonne `mot_de_passe` de la table `administrateurs` avec un nouveau hachage, généré par exemple avec `password_hash('votre-nouveau-mot-de-passe', PASSWORD_DEFAULT)` dans un petit script PHP temporaire.

## 12. Limites assumées

- Les rôles se limitent à deux niveaux (Administrateur / Éditeur) ; pas de permissions plus fines par module (par exemple un Éditeur qui n'aurait accès qu'au menu et pas aux paramètres) — envisageable plus tard si le besoin apparaît.
- La récupération de mot de passe dépend de la fonction `mail()` de PHP, qui n'offre aucune garantie de délivrabilité ni message d'erreur détaillé en cas d'échec — `test-email.php` permet de vérifier concrètement si elle fonctionne sur votre serveur.
- Le calcul « nombre de commandes → note en étoiles » de l'ancienne version (reliée à l'application interne) n'existe plus ici : la note de chaque plat est définie manuellement dans le CMS, puisque ce site n'a pas accès aux données de commandes réelles.