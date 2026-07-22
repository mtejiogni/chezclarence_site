<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT * FROM administrateurs WHERE id = :id');
$stmt->execute(['id' => $_SESSION['admin_id']]);
$admin = $stmt->fetch();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'profil') {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nom === '') $erreurs[] = 'Le nom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Adresse e-mail invalide.';

        if (!$erreurs) {
            $pdo->prepare('UPDATE administrateurs SET nom = :nom, email = :email WHERE id = :id')
                ->execute(['nom' => $nom, 'email' => $email, 'id' => $admin['id']]);
            $_SESSION['admin_nom'] = $nom;
            $_SESSION['admin_email'] = $email;
            flash_set('success', 'Profil mis à jour.');
            header('Location: compte.php');
            exit;
        }
    }

    if ($action === 'mot_de_passe') {
        $actuel = $_POST['mot_de_passe_actuel'] ?? '';
        $nouveau = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        if (!password_verify($actuel, $admin['mot_de_passe'])) $erreurs[] = 'Mot de passe actuel incorrect.';
        if (strlen($nouveau) < 8) $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        if ($nouveau !== $confirmation) $erreurs[] = 'Les deux mots de passe ne correspondent pas.';

        if (!$erreurs) {
            $pdo->prepare('UPDATE administrateurs SET mot_de_passe = :mdp WHERE id = :id')
                ->execute(['mdp' => password_hash($nouveau, PASSWORD_DEFAULT), 'id' => $admin['id']]);
            flash_set('success', 'Mot de passe modifié avec succès.');
            header('Location: compte.php');
            exit;
        }
    }
}

$admin_title = 'Mon compte';
$admin_current = 'compte';
require __DIR__ . '/includes/layout-top.php';
?>

<?php if ($erreurs): ?>
  <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 max-w-xl">
    <ul class="list-disc list-inside space-y-1"><?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6 max-w-4xl">
  <form method="post" class="admin-card p-7 space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="profil">
    <h2 class="font-display text-lg text-ink"><i class="fa-solid fa-id-badge text-brand-600 mr-2"></i>Mon profil</h2>
    <div>
      <label class="admin-label">Rôle</label>
      <div><span class="badge <?= current_admin_role() === 'Administrateur' ? 'badge-brand' : 'badge-info' ?>"><i class="fa-solid <?= current_admin_role() === 'Administrateur' ? 'fa-user-shield' : 'fa-pen' ?> mr-1"></i><?= e(current_admin_role()) ?></span></div>
      <?php if (is_administrateur()): ?>
        <p class="admin-hint">Géré depuis <a href="utilisateurs.php" class="text-brand-600 underline">Utilisateurs</a>.</p>
      <?php else: ?>
        <p class="admin-hint">Seul un administrateur peut modifier les rôles.</p>
      <?php endif; ?>
    </div>
    <div>
      <label class="admin-label">Nom</label>
      <input type="text" name="nom" required class="admin-input" value="<?= e($admin['nom']) ?>">
    </div>
    <div>
      <label class="admin-label">E-mail</label>
      <input type="email" name="email" required class="admin-input" value="<?= e($admin['email']) ?>">
    </div>
    <button type="submit" class="bg-ink hover:bg-ink-soft text-white font-bold px-6 py-3 rounded-xl transition text-sm">
      Mettre à jour le profil
    </button>
  </form>

  <form method="post" class="admin-card p-7 space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="mot_de_passe">
    <h2 class="font-display text-lg text-ink"><i class="fa-solid fa-lock text-brand-600 mr-2"></i>Changer mon mot de passe</h2>
    <div>
      <label class="admin-label">Mot de passe actuel</label>
      <div class="password-field-wrap">
        <input type="password" name="mot_de_passe_actuel" id="pwd-actuel" required class="admin-input">
        <button type="button" class="password-toggle-btn" data-toggle-password="#pwd-actuel"><i class="fa-solid fa-eye"></i></button>
      </div>
    </div>
    <div>
      <label class="admin-label">Nouveau mot de passe</label>
      <div class="password-field-wrap">
        <input type="password" name="nouveau_mot_de_passe" id="pwd-nouveau" required minlength="8" class="admin-input">
        <button type="button" class="password-toggle-btn" data-toggle-password="#pwd-nouveau"><i class="fa-solid fa-eye"></i></button>
      </div>
    </div>
    <div>
      <label class="admin-label">Confirmer le nouveau mot de passe</label>
      <div class="password-field-wrap">
        <input type="password" name="confirmation" id="pwd-confirmation" required minlength="8" class="admin-input">
        <button type="button" class="password-toggle-btn" data-toggle-password="#pwd-confirmation"><i class="fa-solid fa-eye"></i></button>
      </div>
    </div>
    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
      Changer le mot de passe
    </button>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>