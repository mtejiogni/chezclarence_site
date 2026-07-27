<?php
require_once __DIR__ . '/includes/auth.php';

if (admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$adminId = verifier_token_reinitialisation($token);

$erreurs = [];
$succes = false;

if ($adminId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (strlen($motDePasse) < 8) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($motDePasse !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (!$erreurs) {
        get_pdo()->prepare('UPDATE administrateurs SET mot_de_passe = :mdp WHERE id = :id')
            ->execute(['mdp' => password_hash($motDePasse, PASSWORD_DEFAULT), 'id' => $adminId]);
        consommer_token_reinitialisation($token);
        $succes = true;
    }
}

$p = get_parametres();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouveau mot de passe — Administration <?= e($p['nom_restaurant']) ?></title>
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
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
      <p class="text-ink/50 text-sm mt-1">Choisir un nouveau mot de passe</p>
    </div>

    <div class="admin-card p-7 shadow-sm">
      <?php if (!$adminId && !$succes): ?>
        <div class="text-center py-4">
          <span class="inline-grid w-14 h-14 place-items-center rounded-full bg-red-50 text-red-600 text-2xl mb-4">
            <i class="fa-solid fa-link-slash"></i>
          </span>
          <h2 class="font-display text-lg text-ink mb-2">Lien invalide ou expiré</h2>
          <p class="text-ink/55 text-sm leading-relaxed">
            Ce lien de réinitialisation n'est plus valable — il a peut-être déjà été utilisé, ou plus d'une heure s'est écoulée depuis son envoi.
          </p>
          <a href="mot-de-passe-oublie.php" class="inline-flex items-center gap-2 mt-5 bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
            Demander un nouveau lien
          </a>
        </div>

      <?php elseif ($succes): ?>
        <div class="text-center py-4">
          <span class="inline-grid w-14 h-14 place-items-center rounded-full bg-success-50 text-success-600 text-2xl mb-4">
            <i class="fa-solid fa-circle-check"></i>
          </span>
          <h2 class="font-display text-lg text-ink mb-2">Mot de passe mis à jour</h2>
          <p class="text-ink/55 text-sm leading-relaxed mb-5">Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
          <a href="login.php" class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
            Se connecter
          </a>
        </div>

      <?php else: ?>
        <?php if ($erreurs): ?>
          <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4">
            <ul class="list-disc list-inside space-y-1"><?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div>
            <label class="admin-label">Nouveau mot de passe</label>
            <div class="password-field-wrap">
              <input type="password" name="mot_de_passe" id="new-password" required minlength="8" class="admin-input" placeholder="8 caractères minimum">
              <button type="button" class="password-toggle-btn" data-toggle-password="#new-password"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <div>
            <label class="admin-label">Confirmer le mot de passe</label>
            <div class="password-field-wrap">
              <input type="password" name="confirmation" id="new-password-confirm" required minlength="8" class="admin-input">
              <button type="button" class="password-toggle-btn" data-toggle-password="#new-password-confirm"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl transition">
            Enregistrer le nouveau mot de passe
          </button>
        </form>
      <?php endif; ?>
    </div>

    <p class="text-center text-xs text-ink/40 mt-5">
      <a href="login.php" class="hover:text-brand-600"><i class="fa-solid fa-arrow-left mr-1"></i> Retour à la connexion</a>
    </p>
  </div>
  <script src="../assets/js/admin.js"></script>
</body>
</html>