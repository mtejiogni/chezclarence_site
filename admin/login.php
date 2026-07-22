<?php
require_once __DIR__ . '/includes/auth.php';

if (admin_count() === 0) {
    header('Location: install.php');
    exit;
}
if (admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$erreur = null;
$flash = flash_get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if (admin_attempt_login($email, $motDePasse)) {
        header('Location: dashboard.php');
        exit;
    }
    $erreur = 'E-mail ou mot de passe incorrect.';
}

$p = get_parametres();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — Administration <?= e($p['nom_restaurant']) ?></title>
  <link rel="stylesheet" href="../assets/vendor/fontawesome/all.min.css">
  <link rel="stylesheet" href="../assets/css/tailwind.css">
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body min-h-screen grid place-items-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <?php if (!empty($p['logo'])): ?>
        <img src="<?= e(photo_url($p['logo'])) ?>" class="w-16 h-16 rounded-2xl object-cover mx-auto mb-3 ring-2 ring-brand-600/30" alt="">
      <?php else: ?>
        <span class="inline-grid w-16 h-16 place-items-center rounded-2xl bg-ink text-brand-500 text-2xl mb-3 mx-auto"><?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?></span>
      <?php endif; ?>
      <h1 class="font-display text-2xl text-ink"><?= e($p['nom_restaurant']) ?></h1>
      <p class="text-ink/50 text-sm mt-1">Panneau d'administration du site</p>
    </div>

    <div class="admin-card p-7 shadow-sm">
      <?php if ($flash): ?>
        <div class="mb-5 <?= $flash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-success-50 border-success-100 text-success-700' ?> border text-sm rounded-xl p-4 flex items-center gap-2">
          <i class="fa-solid <?= $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i> <?= e($flash['message']) ?>
        </div>
      <?php endif; ?>
      <?php if ($erreur): ?>
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 flex items-center gap-2">
          <i class="fa-solid fa-circle-exclamation"></i> <?= e($erreur) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="admin-label">Adresse e-mail</label>
          <input type="email" name="email" required autofocus class="admin-input" placeholder="vous@chezclarence.cm">
        </div>
        <div>
          <label class="admin-label">Mot de passe</label>
          <div class="password-field-wrap">
            <input type="password" name="mot_de_passe" id="login-password" required class="admin-input" placeholder="••••••••">
            <button type="button" class="password-toggle-btn" data-toggle-password="#login-password" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl transition">
          Se connecter
        </button>
      </form>
    </div>
    <p class="text-center text-xs text-ink/40 mt-5">
      <a href="../index.php" class="hover:text-brand-600">← Retour au site</a>
    </p>
  </div>
  <script src="../assets/js/admin.js"></script>
</body>
</html>