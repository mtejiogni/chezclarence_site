<?php
$erreurChargement = null;
try {
    require_once __DIR__ . '/includes/auth.php';
} catch (Throwable $ex) {
    // Si même le chargement échoue (base totalement injoignable, .env
    // absent...), on n'interrompt pas la page : le panneau de
    // vérifications ci-dessous, entièrement autonome, prend le relais
    // pour expliquer précisément ce qui manque.
    $erreurChargement = $ex->getMessage();
}

if (!$erreurChargement && function_exists('admin_count') && admin_count() > 0) {
    header('Location: login.php');
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   VÉRIFICATIONS SERVEUR — entièrement autonomes (ne dépendent
   d'aucune fonction du reste du projet), pour rester fiables même
   si la configuration n'est pas encore en place.
   ═══════════════════════════════════════════════════════════════ */

function lire_env_local(): array
{
    $chemin = __DIR__ . '/../.env';
    $valeurs = [];
    if (is_file($chemin)) {
        foreach (file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
            $ligne = trim($ligne);
            if ($ligne === '' || str_starts_with($ligne, '#') || !str_contains($ligne, '=')) {
                continue;
            }
            [$cle, $valeur] = explode('=', $ligne, 2);
            $valeurs[trim($cle)] = trim($valeur, " \t\n\r\0\x0B\"'");
        }
    }
    return $valeurs;
}

$env = lire_env_local();
$envPresent = is_file(__DIR__ . '/../.env');

$verifications = [];

$verifications[] = [
    'label' => 'Version de PHP (8.1 minimum requis)',
    'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'detail' => 'Détectée : PHP ' . PHP_VERSION,
];

foreach ([
    'pdo_mysql' => 'connexion à la base de données',
    'mbstring' => 'caractères accentués (noms, descriptions...)',
    'fileinfo' => 'vérification des images envoyées depuis le CMS',
] as $ext => $usage) {
    $verifications[] = [
        'label' => "Extension PHP « {$ext} » — {$usage}",
        'ok' => extension_loaded($ext),
        'detail' => extension_loaded($ext) ? 'Activée' : 'Non activée — à demander à votre hébergeur',
    ];
}

$verifications[] = [
    'label' => 'Fichier de configuration .env',
    'ok' => $envPresent,
    'detail' => $envPresent ? 'Trouvé' : 'Introuvable — copiez .env.example en .env et renseignez vos identifiants MySQL',
];

$dbHote = $env['DB_HOST'] ?? '127.0.0.1';
$dbPort = $env['DB_PORT'] ?? '3306';
$dbNom = $env['DB_DATABASE'] ?? '';
$dbUtilisateur = $env['DB_USERNAME'] ?? '';
$dbMotDePasse = $env['DB_PASSWORD'] ?? '';

$pdoServeur = null;
$dbOk = false;
$dbErreur = null;

if ($envPresent && $dbUtilisateur !== '') {
    try {
        $pdoServeur = new PDO(
            "mysql:host={$dbHote};port={$dbPort};charset=utf8mb4",
            $dbUtilisateur,
            $dbMotDePasse,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dbOk = true;
    } catch (Throwable $ex) {
        $dbErreur = $ex->getMessage();
    }
}

$verifications[] = [
    'label' => 'Connexion au serveur MySQL (' . e($dbHote) . ':' . e($dbPort) . ')',
    'ok' => $dbOk,
    'detail' => $dbOk ? 'Connexion réussie' : ('Échec — vérifiez DB_HOST, DB_USERNAME et DB_PASSWORD dans .env' . ($dbErreur ? ' (' . $dbErreur . ')' : '')),
];

/**
 * Vérifie qu'un dossier est réellement accessible en écriture, en y
 * écrivant pour de vrai un petit fichier temporaire puis en le
 * supprimant — is_writable() seul est connu pour être peu fiable sous
 * Windows (héritage d'ACL NTFS différent du modèle de permissions
 * POSIX utilisé sous Linux/macOS), donc un test d'écriture réel est
 * la seule méthode fiable quel que soit le système d'exploitation.
 */
function dossier_est_inscriptible(string $chemin): bool
{
    if (!is_dir($chemin)) {
        return false;
    }
    $fichierTest = rtrim($chemin, '/\\') . DIRECTORY_SEPARATOR . '.ecriture_test_' . uniqid();
    $succes = @file_put_contents($fichierTest, 'test') !== false;
    if ($succes) {
        @unlink($fichierTest);
    }
    return $succes;
}

$dossierUploads = __DIR__ . '/../uploads';
$uploadsOk = dossier_est_inscriptible($dossierUploads);
$verifications[] = [
    'label' => 'Dossier uploads/ accessible en écriture',
    'ok' => $uploadsOk,
    'detail' => $uploadsOk
        ? 'OK (vérifié par écriture réelle)'
        : 'Non accessible en écriture — sous Linux/macOS : chmod 755 ou 775 ; sous Windows : autorisez l\'écriture pour l\'utilisateur du serveur web (IIS_IUSRS ou équivalent) dans les propriétés du dossier',
];

/* ── La base ciblée par .env existe-t-elle et y a-t-on accès ? ──
   Distinct de la vérification précédente : un serveur MySQL peut
   accepter les identifiants sans que la base précise existe encore
   — c'est le cas le plus fréquent sur un hébergement mutualisé, où
   la base doit d'abord être créée depuis le panneau d'hébergement
   (cPanel, Plesk...) avant que ce script ne puisse y installer quoi
   que ce soit : l'utilisateur MySQL fourni par ce type d'hébergeur
   n'a presque jamais le droit de créer une base lui-même. */
$dbSpecifiqueOk = false;
$dbSpecifiqueErreur = null;
if ($dbOk && $dbNom !== '') {
    try {
        new PDO(
            "mysql:host={$dbHote};port={$dbPort};dbname={$dbNom};charset=utf8mb4",
            $dbUtilisateur,
            $dbMotDePasse,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dbSpecifiqueOk = true;
    } catch (Throwable $ex) {
        $dbSpecifiqueErreur = $ex->getMessage();
    }
}
$verifications[] = [
    'label' => 'La base « ' . e($dbNom ?: '(non définie)') . ' » existe et est accessible',
    'ok' => $dbSpecifiqueOk,
    'detail' => $dbSpecifiqueOk
        ? 'OK'
        : 'Introuvable ou inaccessible — créez-la depuis le panneau de votre hébergeur (cPanel, Plesk...) si ce n\'est pas déjà fait' . ($dbSpecifiqueErreur ? ' (' . $dbSpecifiqueErreur . ')' : ''),
];

/**
 * Retire du script SQL toute instruction de niveau "base de données"
 * (DROP DATABASE, CREATE DATABASE, USE) avant exécution — cet
 * installateur ne doit agir QUE dans la base déjà provisionnée et
 * ciblée par .env (voir vérification ci-dessus), jamais en créer ou
 * en supprimer une lui-même. Cela élimine aussi tout risque qu'une
 * instruction destructrice (DROP DATABASE) soit un jour exécutable
 * simplement en cliquant un bouton depuis le navigateur.
 */
function schema_sans_instructions_base(string $sql): string
{
    $sql = preg_replace('/\bDROP\s+DATABASE\b.*?;/is', '', $sql);
    $sql = preg_replace('/\bCREATE\s+DATABASE\b.*?;/is', '', $sql);
    $sql = preg_replace('/\bUSE\s+[^\s;]+\s*;/i', '', $sql);
    return $sql;
}

/* ── Les tables existent-elles déjà dans la base ciblée par .env ? ── */
$tablesExistent = false;
if ($dbSpecifiqueOk) {
    try {
        $stmt = $pdoServeur->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = 'administrateurs'"
        );
        $stmt->execute(['db' => $dbNom]);
        $tablesExistent = ((int) $stmt->fetchColumn()) > 0;
    } catch (Throwable $ex) {
        $tablesExistent = false;
    }
}

$verifications[] = [
    'label' => 'Tables installées dans « ' . e($dbNom ?: '(non définie)') . ' »',
    'ok' => $tablesExistent,
    'detail' => $tablesExistent ? 'La table administrateurs existe déjà' : 'Pas encore installées',
];

/* ── Installation automatique des tables (bouton "Installer") ── */
$installMessage = null;
$installErreur = null;

if ($dbSpecifiqueOk && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['installer_bdd'])) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['_csrf'] ?? '')) {
        $installErreur = "Session expirée, merci de recharger la page et réessayer.";
    } else {
        $cheminSchema = __DIR__ . '/../database/schema.sql';
        if (!is_file($cheminSchema)) {
            $installErreur = "Fichier database/schema.sql introuvable sur le serveur.";
        } else {
            try {
                // Connexion dédiée avec exécution multi-requêtes activée —
                // utilisée UNIQUEMENT ici pour importer le schéma en un
                // seul appel, DIRECTEMENT dans la base déjà provisionnée
                // (dbname précisé dans le DSN). Le reste du site continue
                // d'utiliser une connexion classique (une requête à la
                // fois), plus sûre.
                $pdoInstall = new PDO(
                    "mysql:host={$dbHote};port={$dbPort};dbname={$dbNom};charset=utf8mb4",
                    $dbUtilisateur,
                    $dbMotDePasse,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
                    ]
                );
                $sql = schema_sans_instructions_base(file_get_contents($cheminSchema));
                $pdoInstall->exec($sql);

                $verif = $pdoServeur->prepare(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = 'administrateurs'"
                );
                $verif->execute(['db' => $dbNom]);
                $tablesExistent = ((int) $verif->fetchColumn()) > 0;

                if ($tablesExistent) {
                    $installMessage = "Les tables ont été installées avec succès dans « {$dbNom} ».";
                } else {
                    $installErreur = "Le script s'est exécuté sans erreur, mais la table administrateurs n'apparaît toujours pas. Vérifiez le contenu de database/schema.sql.";
                }
            } catch (Throwable $ex) {
                $installErreur = "Échec de l'installation : " . $ex->getMessage();
            }
        }
    }
}

/* ═══════════════════════════════════════════════════════════════
   CRÉATION DU COMPTE ADMINISTRATEUR — inchangé, sauf qu'il ne
   s'exécute désormais que si les tables sont bien en place.
   ═══════════════════════════════════════════════════════════════ */

$erreurs = [];

if ($tablesExistent && !$erreurChargement && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_compte'])) {
    csrf_verify();

    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if ($nom === '') $erreurs[] = 'Le nom est obligatoire.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Adresse e-mail invalide.';
    if (strlen($motDePasse) < 8) $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($motDePasse !== $confirmation) $erreurs[] = 'Les deux mots de passe ne correspondent pas.';

    if (!$erreurs) {
        $stmt = get_pdo()->prepare(
            'INSERT INTO administrateurs (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mdp, :role)'
        );
        $stmt->execute([
            'nom' => $nom,
            'email' => $email,
            'mdp' => password_hash($motDePasse, PASSWORD_DEFAULT),
            'role' => 'Administrateur',
        ]);

        admin_attempt_login($email, $motDePasse);
        header('Location: dashboard.php');
        exit;
    }
}

$nomRestaurant = 'votre restaurant';
if (!$erreurChargement && function_exists('get_parametres')) {
    try {
        $p = get_parametres();
        $nomRestaurant = $p['nom_restaurant'] ?? $nomRestaurant;
    } catch (Throwable $ex) {
        // reste sur la valeur par défaut
    }
}

/* ── Étape courante de l'assistant (calculée depuis l'état réel du
   serveur, pas depuis un choix de l'utilisateur — on ne peut pas
   "avancer" tant qu'une étape n'est pas réellement complétée). ── */
$etapeActuelle = !$dbOk ? 1 : (!$tablesExistent ? 2 : 3);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installation — Administration <?= e($nomRestaurant) ?></title>
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/tailwind.css">
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    html, body { height: 100%; }
    .install-shell { min-height: 100vh; }

    .install-brand-panel {
      position: relative;
      overflow: hidden;
      background: linear-gradient(160deg, rgb(var(--ink)) 0%, #1a1a1a 55%, rgb(var(--ink)) 100%);
    }
    .install-brand-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 85% 15%, rgba(234, 88, 12, 0.30), transparent 45%),
        radial-gradient(circle at 8% 90%, rgba(234, 88, 12, 0.18), transparent 40%);
      pointer-events: none;
    }
    .install-brand-orb {
      position: absolute;
      border-radius: 999px;
      background: rgba(234, 88, 12, 0.14);
      pointer-events: none;
    }
    .install-heading {
      font-size: clamp(1.65rem, 2.4vw + 1rem, 2.75rem);
      line-height: 1.15;
    }
    .install-badge {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
      color: rgb(var(--brand-400)); border: 1px solid rgba(234, 88, 12, 0.4);
      background: rgba(234, 88, 12, 0.08); border-radius: 999px; padding: 6px 14px;
    }
    .install-feature { display: flex; align-items: flex-start; gap: 14px; }
    .install-feature-icon {
      width: 38px; height: 38px; flex-shrink: 0; border-radius: 0.7rem;
      display: grid; place-items: center; background: rgba(234, 88, 12, 0.15); color: rgb(var(--brand-400));
    }
    .install-rocket {
      width: 64px; height: 64px; border-radius: 1.1rem; display: grid; place-items: center;
      background: rgb(var(--brand-600)); color: #fff; font-size: 1.6rem;
      animation: install-rocket-float 3s ease-in-out infinite;
    }
    @keyframes install-rocket-float {
      0%, 100% { transform: translateY(0) rotate(-4deg); }
      50% { transform: translateY(-8px) rotate(4deg); }
    }

    .install-field { position: relative; }
    .install-field-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: #9CA3AF; font-size: 0.85rem; transition: color 0.15s ease; pointer-events: none;
    }
    .install-field-input { padding-left: 2.6rem; }
    .install-field-input:focus + .install-field-icon,
    .install-field:focus-within .install-field-icon { color: rgb(var(--brand-600)); }
    .password-field-wrap .install-field-input { padding-right: 2.6rem; }

    .password-strength { margin-top: 0.6rem; }
    .password-strength-track { display: flex; gap: 4px; height: 6px; }
    .password-strength-segment { flex: 1; border-radius: 999px; background: #E5E7EB; transition: background 0.25s ease; }
    .password-strength-label { font-size: 0.72rem; font-weight: 700; margin-top: 0.4rem; transition: color 0.2s ease; }

    .password-match {
      display: none; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700;
      margin-top: 0.5rem; border-radius: 0.6rem; padding: 0.4rem 0.7rem;
    }
    .password-match.show { display: flex; }
    .password-match.match { color: rgb(var(--success-700)); background: rgb(var(--success-50)); }
    .password-match.no-match { color: #B91C1C; background: #FEF2F2; }

    .capslock-warning {
      display: none; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700;
      color: #B45309; background: #FFFBEB; border: 1px solid #FDE68A;
      border-radius: 0.6rem; padding: 0.45rem 0.7rem; margin-top: 0.5rem;
    }
    .capslock-warning.show { display: flex; }

    .install-submit-btn[disabled] { opacity: 0.75; cursor: not-allowed; }

    /* ── Indicateur d'étapes (assistant façon Joomla/WordPress) ──── */
    .install-stepper {
      display: flex; align-items: flex-start; justify-content: center;
      margin-bottom: 1.5rem;
    }
    .install-step { display: flex; flex-direction: column; align-items: center; gap: 6px; flex: 0 0 auto; }
    .install-step-circle {
      width: 34px; height: 34px; border-radius: 999px; display: grid; place-items: center;
      font-weight: 700; font-size: 0.85rem; background: #E5E7EB; color: #9CA3AF;
      transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
    }
    .install-step.done .install-step-circle { background: rgb(var(--success-600)); color: #fff; }
    .install-step.active .install-step-circle {
      background: rgb(var(--brand-600)); color: #fff; box-shadow: 0 0 0 4px rgb(var(--brand-100));
    }
    .install-step-label {
      font-size: 0.65rem; font-weight: 700; color: #9CA3AF; white-space: nowrap;
      text-transform: uppercase; letter-spacing: 0.02em;
    }
    .install-step.done .install-step-label,
    .install-step.active .install-step-label { color: rgb(var(--ink)); }
    .install-step-line {
      flex: 1 1 20px; height: 2px; background: #E5E7EB; margin: 17px 4px 0;
      min-width: 16px; transition: background 0.3s ease;
    }
    .install-step-line.done { background: rgb(var(--success-600)); }

    /* Sur les très petits écrans, on garde uniquement les cercles :
       les libellés ("Vérifications", "Base de données"...) prennent
       trop de place à côté les uns des autres en dessous de ~380px. */
    @media (max-width: 380px) {
      .install-step-label { display: none; }
      .install-step-line { flex-basis: 14px; }
    }

    /* ── Panneau de vérifications serveur ─────────────────────── */
    .check-list { list-style: none; margin: 0; padding: 0; }
    .check-item {
      display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
      padding: 10px 0; border-bottom: 1px solid #F3F4F6; font-size: 0.85rem;
    }
    .check-item:last-child { border-bottom: none; }
    .check-badge {
      display: inline-flex; align-items: center; gap: 6px; font-size: 0.72rem; font-weight: 700;
      padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0;
    }
    .check-badge.ok { background: rgb(var(--success-50)); color: rgb(var(--success-700)); }
    .check-badge.ko { background: #FEF2F2; color: #B91C1C; }
    .check-detail { font-size: 0.75rem; color: #9CA3AF; margin-top: 2px; }

    /* Résumé repliable des vérifications (étapes 2 et 3) */
    .install-recap summary { list-style: none; }
    .install-recap summary::-webkit-details-marker { display: none; }
    .install-recap[open] .install-recap-chevron { transform: rotate(180deg); }
    .install-recap-chevron { transition: transform 0.2s ease; }

    @media (prefers-reduced-motion: reduce) {
      .install-rocket { animation: none; }
    }
  </style>
</head>
<body class="admin-body">

<div class="install-shell grid lg:grid-cols-2">

  <div class="install-brand-panel hidden lg:flex flex-col justify-between p-8 lg:p-10 xl:p-12 text-white relative">
    <span class="install-brand-orb w-64 h-64 -top-16 -right-16 float-2"></span>
    <span class="install-brand-orb w-40 h-40 bottom-10 -left-10 float-3"></span>

    <div class="relative z-10">
      <span class="install-badge mb-8"><i class="fa-solid fa-sparkles"></i> Premier lancement</span>

      <h1 class="font-display install-heading mb-5">
        Bienvenue sur le back-office de <?= e($nomRestaurant) ?>
      </h1>
      <p class="text-white/55 text-base leading-relaxed max-w-sm mb-12">
        Quelques vérifications automatiques, puis la création de votre compte : votre site sera prêt en quelques minutes.
      </p>

      <div class="space-y-6 max-w-sm">
        <div class="install-feature">
          <span class="install-feature-icon"><i class="fa-solid fa-server"></i></span>
          <div>
            <p class="font-bold text-sm text-white">Vérification du serveur</p>
            <p class="text-white/45 text-sm mt-0.5">PHP, extensions, connexion à la base — tout est contrôlé automatiquement.</p>
          </div>
        </div>
        <div class="install-feature">
          <span class="install-feature-icon"><i class="fa-solid fa-database"></i></span>
          <div>
            <p class="font-bold text-sm text-white">Installation des tables</p>
            <p class="text-white/45 text-sm mt-0.5">En un clic, sans ligne de commande à taper.</p>
          </div>
        </div>
        <div class="install-feature">
          <span class="install-feature-icon"><i class="fa-solid fa-user-shield"></i></span>
          <div>
            <p class="font-bold text-sm text-white">Votre compte administrateur</p>
            <p class="text-white/45 text-sm mt-0.5">Créé une seule fois, avec tous les droits.</p>
          </div>
        </div>
      </div>
    </div>

    <p class="relative z-10 text-white/30 text-xs">Cette page se désactive automatiquement dès qu'un compte existe.</p>
  </div>

  <div class="flex items-center justify-center p-4 sm:p-6 lg:p-10">
    <div class="w-full max-w-md">

      <div class="text-center mb-6 lg:hidden">
        <span class="install-rocket inline-grid mb-3"><i class="fa-solid fa-rocket"></i></span>
        <h1 class="font-display text-2xl text-ink break-words px-2">Bienvenue !</h1>
        <p class="text-ink/50 text-sm mt-1">Installation du back-office.</p>
      </div>

      <!-- ═══ Indicateur d'étapes ═══ -->
      <?php
        $etape1Classe = $etapeActuelle > 1 ? 'done' : ($etapeActuelle === 1 ? 'active' : '');
        $etape2Classe = $etapeActuelle > 2 ? 'done' : ($etapeActuelle === 2 ? 'active' : '');
        $etape3Classe = $etapeActuelle === 3 ? 'active' : '';
      ?>
      <div class="install-stepper">
        <div class="install-step <?= $etape1Classe ?>">
          <span class="install-step-circle"><?= $etapeActuelle > 1 ? '<i class="fa-solid fa-check"></i>' : '1' ?></span>
          <span class="install-step-label">Vérifications</span>
        </div>
        <span class="install-step-line <?= $etapeActuelle > 1 ? 'done' : '' ?>"></span>
        <div class="install-step <?= $etape2Classe ?>">
          <span class="install-step-circle"><?= $etapeActuelle > 2 ? '<i class="fa-solid fa-check"></i>' : '2' ?></span>
          <span class="install-step-label">Base de données</span>
        </div>
        <span class="install-step-line <?= $etapeActuelle > 2 ? 'done' : '' ?>"></span>
        <div class="install-step <?= $etape3Classe ?>">
          <span class="install-step-circle">3</span>
          <span class="install-step-label">Compte</span>
        </div>
      </div>

      <?php $nbOk = count(array_filter($verifications, fn ($v) => $v['ok'])); $nbTotal = count($verifications); ?>

      <!-- ═══ Panneau de vérifications serveur ═══ -->
      <?php if ($etapeActuelle === 1): ?>
        <div class="admin-card p-5 sm:p-6 shadow-sm mb-5">
          <h2 class="font-display text-base text-ink mb-1 flex items-center gap-2">
            <i class="fa-solid fa-clipboard-check text-brand-600"></i> Vérifications du serveur
          </h2>
          <?php $osLisible = ['Windows' => 'Windows', 'Darwin' => 'macOS', 'Linux' => 'Linux', 'BSD' => 'BSD', 'Solaris' => 'Solaris'][PHP_OS_FAMILY] ?? PHP_OS_FAMILY; ?>
          <p class="text-ink/40 text-xs mb-3">
            Système détecté : <?= e($osLisible) ?> · PHP <?= e(PHP_VERSION) ?> · <?= e(php_sapi_name()) ?>
          </p>
          <ul class="check-list">
            <?php foreach ($verifications as $v): ?>
              <li class="check-item">
                <div>
                  <div><?= e($v['label']) ?></div>
                  <div class="check-detail"><?= e($v['detail']) ?></div>
                </div>
                <span class="check-badge <?= $v['ok'] ? 'ok' : 'ko' ?>">
                  <i class="fa-solid <?= $v['ok'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                  <?= $v['ok'] ? 'OK' : 'À corriger' ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <!-- Étape des vérifications déjà passée : résumé replié, pour ne
             pas encombrer l'écran — repliable/dépliable sans JavaScript
             (élément natif <details>). -->
        <details class="admin-card p-4 sm:p-5 shadow-sm mb-5 install-recap">
          <summary class="flex items-center justify-between cursor-pointer select-none">
            <span class="flex items-center gap-2 text-sm font-semibold text-ink">
              <i class="fa-solid fa-circle-check text-success-600"></i> Vérifications du serveur — <?= $nbOk ?>/<?= $nbTotal ?> réussies
            </span>
            <i class="fa-solid fa-chevron-down text-ink/30 text-xs install-recap-chevron"></i>
          </summary>
          <ul class="check-list mt-3">
            <?php foreach ($verifications as $v): ?>
              <li class="check-item">
                <div><?= e($v['label']) ?></div>
                <span class="check-badge <?= $v['ok'] ? 'ok' : 'ko' ?>">
                  <i class="fa-solid <?= $v['ok'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                  <?= $v['ok'] ? 'OK' : 'À corriger' ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>

      <?php if (!$dbOk): ?>
        <!-- Base injoignable : rien d'autre n'est possible tant que ce n'est pas corrigé -->
        <div class="admin-card p-5 sm:p-6 shadow-sm bg-red-50 border-red-200">
          <p class="text-red-700 text-sm font-semibold flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> Corrigez les points ci-dessus puis rechargez cette page.
          </p>
        </div>

      <?php elseif (!$dbSpecifiqueOk): ?>
        <!-- Serveur MySQL joignable, mais la base précise n'existe pas (ou identifiants
             sans accès à celle-ci) : ce script ne peut pas la créer lui-même, il faut
             passer par le panneau de l'hébergeur. -->
        <div class="admin-card p-5 sm:p-6 shadow-sm bg-amber-50 border-amber-200">
          <p class="text-amber-800 text-sm font-semibold flex items-center gap-2 mb-2">
            <i class="fa-solid fa-circle-info"></i> La base « <?= e($dbNom ?: '(non définie)') ?> » n'existe pas encore
          </p>
          <p class="text-amber-800/80 text-sm leading-relaxed">
            Sur la plupart des hébergements, une base de données doit d'abord être créée depuis le panneau de gestion (cPanel, Plesk, ou l'équivalent chez votre hébergeur) — ce script n'a volontairement pas le droit d'en créer une lui-même. Créez-la, vérifiez que <code class="bg-white/60 px-1.5 py-0.5 rounded text-xs">DB_DATABASE</code> dans <code class="bg-white/60 px-1.5 py-0.5 rounded text-xs">.env</code> correspond exactement à son nom, puis rechargez cette page.
          </p>
        </div>

      <?php elseif (!$tablesExistent): ?>
        <!-- Base existante et accessible, mais tables absentes : proposer l'installation automatique -->
        <div class="admin-card p-5 sm:p-7 shadow-sm">
          <?php if ($installErreur): ?>
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4">
              <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= e($installErreur) ?>
            </div>
          <?php endif; ?>

          <h2 class="font-display text-lg text-ink mb-2">Installer les tables</h2>
          <p class="text-ink/55 text-sm leading-relaxed mb-5">
            La base <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs"><?= e($dbNom) ?></code> existe mais est vide.
            Ce bouton y crée les tables nécessaires à partir de <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">database/schema.sql</code>, sans ligne de commande.
          </p>

          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="installer_bdd" value="1">
            <button type="submit" id="install-db-btn" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
              <i class="fa-solid fa-database"></i> <span>Installer les tables maintenant</span>
            </button>
          </form>
        </div>

      <?php else: ?>

        <!-- Tables prêtes : formulaire de création du compte -->
        <?php if ($installMessage): ?>
          <div class="mb-5 bg-success-50 border border-success-100 text-success-700 text-sm rounded-xl p-4">
            <i class="fa-solid fa-circle-check mr-1"></i> <?= e($installMessage) ?>
          </div>
        <?php endif; ?>

        <div class="hidden lg:block mb-6">
          <h2 class="font-display text-xl text-ink">Créer mon compte</h2>
          <p class="text-ink/50 text-sm mt-1">Dernière étape avant d'accéder au back-office.</p>
        </div>

        <div class="admin-card p-5 sm:p-7 shadow-sm">
          <?php if ($erreurs): ?>
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4">
              <ul class="list-disc list-inside space-y-1">
                <?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" class="space-y-4" id="install-form">
            <?= csrf_field() ?>
            <input type="hidden" name="creer_compte" value="1">

            <div>
              <label class="admin-label">Votre nom</label>
              <div class="install-field">
                <input type="text" name="nom" required class="admin-input install-field-input" value="<?= e($_POST['nom'] ?? '') ?>" placeholder="Ex : Clarence">
                <i class="fa-solid fa-user install-field-icon"></i>
              </div>
            </div>

            <div>
              <label class="admin-label">Adresse e-mail</label>
              <div class="install-field">
                <input type="email" name="email" required class="admin-input install-field-input" value="<?= e($_POST['email'] ?? '') ?>" placeholder="vous@chezclarence.cm">
                <i class="fa-solid fa-envelope install-field-icon"></i>
              </div>
            </div>

            <div>
              <label class="admin-label">Mot de passe</label>
              <div class="password-field-wrap install-field">
                <input type="password" name="mot_de_passe" id="install-password" required minlength="8" class="admin-input install-field-input" placeholder="8 caractères minimum">
                <i class="fa-solid fa-lock install-field-icon"></i>
                <button type="button" class="password-toggle-btn" data-toggle-password="#install-password" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
              </div>

              <div class="password-strength" id="password-strength">
                <div class="password-strength-track">
                  <span class="password-strength-segment" data-seg="1"></span>
                  <span class="password-strength-segment" data-seg="2"></span>
                  <span class="password-strength-segment" data-seg="3"></span>
                  <span class="password-strength-segment" data-seg="4"></span>
                </div>
                <p class="password-strength-label" id="password-strength-label"></p>
              </div>

              <p class="capslock-warning" id="capslock-warning">
                <i class="fa-solid fa-triangle-exclamation"></i> Verrouillage majuscules activé
              </p>
            </div>

            <div>
              <label class="admin-label">Confirmer le mot de passe</label>
              <div class="password-field-wrap install-field">
                <input type="password" name="confirmation" id="install-confirmation" required minlength="8" class="admin-input install-field-input">
                <i class="fa-solid fa-lock install-field-icon"></i>
                <button type="button" class="password-toggle-btn" data-toggle-password="#install-confirmation" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
              </div>
              <p class="password-match" id="password-match">
                <i class="fa-solid" id="password-match-icon"></i> <span id="password-match-text"></span>
              </p>
            </div>

            <button type="submit" id="install-submit-btn" class="install-submit-btn w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
              <i class="fa-solid fa-rocket"></i> <span>Créer mon compte et accéder au back-office</span>
            </button>
          </form>
        </div>
      <?php endif; ?>

      <p class="text-center text-xs text-ink/40 mt-5">
        <i class="fa-solid fa-lock mr-1"></i> Cette page ne sera plus accessible une fois le premier compte créé.
      </p>
    </div>
  </div>
</div>

<script src="../assets/js/admin.js"></script>
<script>
  (function () {
    var pwd = document.getElementById('install-password');
    var warning = document.getElementById('capslock-warning');
    if (!pwd || !warning) return;
    function checkCapsLock(e) {
      if (typeof e.getModifierState !== 'function') return;
      warning.classList.toggle('show', e.getModifierState('CapsLock'));
    }
    pwd.addEventListener('keyup', checkCapsLock);
    pwd.addEventListener('keydown', checkCapsLock);
    pwd.addEventListener('blur', function () { warning.classList.remove('show'); });
  })();

  (function () {
    var pwd = document.getElementById('install-password');
    var segments = document.querySelectorAll('#password-strength .password-strength-segment');
    var label = document.getElementById('password-strength-label');
    if (!pwd || !label) return;

    var niveaux = [
      { texte: '', couleur: '#E5E7EB' },
      { texte: 'Très faible', couleur: '#DC2626' },
      { texte: 'Faible', couleur: '#F97316' },
      { texte: 'Correct', couleur: '#F59E0B' },
      { texte: 'Fort', couleur: '#059669' },
      { texte: 'Excellent', couleur: '#059669' },
    ];

    function evaluer(valeur) {
      var score = 0;
      if (valeur.length >= 8) score++;
      if (valeur.length >= 12) score++;
      if (/[A-Z]/.test(valeur)) score++;
      if (/[0-9]/.test(valeur)) score++;
      if (/[^A-Za-z0-9]/.test(valeur)) score++;
      return Math.min(score, 5);
    }

    pwd.addEventListener('input', function () {
      var valeur = pwd.value;
      var score = valeur.length === 0 ? 0 : evaluer(valeur);
      var actif = Math.ceil((score / 5) * 4);
      var niveau = niveaux[score] || niveaux[0];

      segments.forEach(function (seg, i) {
        seg.style.background = (i < actif && valeur.length > 0) ? niveau.couleur : '#E5E7EB';
      });
      label.textContent = valeur.length === 0 ? '' : niveau.texte;
      label.style.color = niveau.couleur;
    });
  })();

  (function () {
    var pwd = document.getElementById('install-password');
    var confirm = document.getElementById('install-confirmation');
    var wrap = document.getElementById('password-match');
    var icon = document.getElementById('password-match-icon');
    var text = document.getElementById('password-match-text');
    if (!pwd || !confirm || !wrap) return;

    function verifier() {
      if (confirm.value.length === 0) {
        wrap.classList.remove('show', 'match', 'no-match');
        return;
      }
      var correspond = pwd.value === confirm.value;
      wrap.classList.add('show');
      wrap.classList.toggle('match', correspond);
      wrap.classList.toggle('no-match', !correspond);
      icon.className = 'fa-solid ' + (correspond ? 'fa-circle-check' : 'fa-circle-xmark');
      text.textContent = correspond ? 'Les mots de passe correspondent' : 'Les mots de passe ne correspondent pas encore';
    }

    pwd.addEventListener('input', verifier);
    confirm.addEventListener('input', verifier);
  })();

  (function () {
    var form = document.getElementById('install-form');
    var btn = document.getElementById('install-submit-btn');
    if (!form || !btn) return;
    form.addEventListener('submit', function () {
      if (btn.disabled) return;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Création de votre compte...</span>';
    });
  })();

  (function () {
    var btn = document.getElementById('install-db-btn');
    if (!btn) return;
    btn.closest('form').addEventListener('submit', function () {
      if (btn.disabled) return;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Installation en cours...</span>';
    });
  })();
</script>
</body>
</html>