<?php
require_once __DIR__ . '/includes/auth.php';

// Cet assistant ne fonctionne QUE si aucun administrateur n'existe encore.
if (admin_count() > 0) {
    header('Location: login.php');
    exit;
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

$p = get_parametres();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installation — Administration <?= e($p['nom_restaurant']) ?></title>
  <link rel="stylesheet" href="../assets/vendor/fontawesome/all.min.css">
  <link rel="stylesheet" href="../assets/css/tailwind.css">
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body min-h-screen grid place-items-center px-4">
  <div class="w-full max-w-lg">
    <div class="text-center mb-6">
      <span class="inline-grid w-14 h-14 place-items-center rounded-2xl bg-ink text-brand-500 text-2xl mb-3"><i class="fa-solid fa-rocket"></i></span>
      <h1 class="font-display text-2xl text-ink">Bienvenue !</h1>
      <p class="text-ink/50 text-sm mt-1">Créez le tout premier compte administrateur du site.</p>
    </div>

    <div class="admin-card p-7 shadow-sm">
      <?php if ($erreurs): ?>
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4">
          <ul class="list-disc list-inside space-y-1">
            <?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <?= csrf_field() ?>
        <div>
          <label class="admin-label">Votre nom</label>
          <input type="text" name="nom" required class="admin-input" value="<?= e($_POST['nom'] ?? '') ?>" placeholder="Ex : Clarence">
        </div>
        <div>
          <label class="admin-label">Adresse e-mail</label>
          <input type="email" name="email" required class="admin-input" value="<?= e($_POST['email'] ?? '') ?>" placeholder="vous@chezclarence.cm">
        </div>
        <div>
          <label class="admin-label">Mot de passe</label>
          <div class="password-field-wrap">
            <input type="password" name="mot_de_passe" id="install-password" required minlength="8" class="admin-input" placeholder="8 caractères minimum">
            <button type="button" class="password-toggle-btn" data-toggle-password="#install-password" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>
        <div>
          <label class="admin-label">Confirmer le mot de passe</label>
          <div class="password-field-wrap">
            <input type="password" name="confirmation" id="install-confirmation" required minlength="8" class="admin-input">
            <button type="button" class="password-toggle-btn" data-toggle-password="#install-confirmation" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl transition">
          Créer mon compte et accéder au back-office
        </button>
      </form>
    </div>
    <p class="text-center text-xs text-ink/40 mt-5">Cette page ne sera plus accessible une fois le premier compte créé.</p>
  </div>
  <script src="../assets/js/admin.js"></script>
</body>
</html>