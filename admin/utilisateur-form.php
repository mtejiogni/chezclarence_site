<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_administrateur();

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$utilisateur = ['nom' => '', 'email' => '', 'role' => 'Éditeur', 'actif' => 1];
$estMoi = false;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM administrateurs WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $trouve = $stmt->fetch();
    if (!$trouve) {
        flash_set('error', 'Utilisateur introuvable.');
        header('Location: utilisateurs.php');
        exit;
    }
    $utilisateur = $trouve;
    $estMoi = $id === (int) $_SESSION['admin_id'];
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['Administrateur', 'Éditeur'], true) ? $_POST['role'] : 'Éditeur';
    $actif = isset($_POST['actif']) ? 1 : 0;
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if ($nom === '') {
        $erreurs[] = 'Le nom est obligatoire.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Adresse e-mail invalide.';
    } else {
        $check = $pdo->prepare('SELECT id FROM administrateurs WHERE email = :email AND id != :id');
        $check->execute(['email' => $email, 'id' => $id ?: 0]);
        if ($check->fetch()) {
            $erreurs[] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        }
    }

    if (!$id) {
        // Création : mot de passe obligatoire
        if (strlen($motDePasse) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($motDePasse !== $confirmation) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }
    } elseif ($motDePasse !== '') {
        // Modification : mot de passe optionnel, mais validé s'il est renseigné
        if (strlen($motDePasse) < 8) {
            $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($motDePasse !== $confirmation) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }
    }

    // Empêche de retirer le dernier administrateur actif du site (rôle, désactivation, ou les deux)
    if ($id) {
        $stmt2 = $pdo->prepare('SELECT role, actif FROM administrateurs WHERE id = :id');
        $stmt2->execute(['id' => $id]);
        $etatActuel = $stmt2->fetch();

        $etaitAdminActif = $etatActuel && $etatActuel['role'] === 'Administrateur' && (int) $etatActuel['actif'] === 1;
        $resteraAdminActif = ($role === 'Administrateur' && $actif === 1);

        if ($etaitAdminActif && !$resteraAdminActif && nb_administrateurs_actifs($id) === 0) {
            $erreurs[] = "Impossible d'enregistrer : ce compte est le dernier administrateur actif du site. Créez ou promouvez un autre administrateur avant de modifier celui-ci.";
        }
    }

    if (!$erreurs) {
        $data = ['nom' => $nom, 'email' => $email, 'role' => $role, 'actif' => $actif];

        if ($id) {
            if ($motDePasse !== '') {
                $data['mot_de_passe'] = password_hash($motDePasse, PASSWORD_DEFAULT);
            }
            $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
            $data['id'] = $id;
            $pdo->prepare("UPDATE administrateurs SET $set WHERE id = :id")->execute($data);
            flash_set('success', 'Utilisateur mis à jour.');

            if ($estMoi) {
                $_SESSION['admin_nom'] = $nom;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_role'] = $role;
            }
        } else {
            $data['mot_de_passe'] = password_hash($motDePasse, PASSWORD_DEFAULT);
            $colonnes = implode(', ', array_keys($data));
            $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
            $pdo->prepare("INSERT INTO administrateurs ($colonnes) VALUES ($valeurs)")->execute($data);
            flash_set('success', "Utilisateur créé. Communiquez-lui son mot de passe par un moyen sûr.");
        }

        header('Location: utilisateurs.php');
        exit;
    }
}

$admin_title = $id ? 'Modifier un utilisateur' : 'Nouvel utilisateur';
$admin_current = 'utilisateurs';
require __DIR__ . '/includes/layout-top.php';
?>

<a href="utilisateurs.php" class="inline-flex items-center gap-2 text-sm font-semibold text-ink/50 hover:text-brand-600 mb-5"><i class="fa-solid fa-arrow-left"></i> Retour aux utilisateurs</a>

<?php if ($erreurs): ?>
  <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 max-w-2xl">
    <ul class="list-disc list-inside space-y-1"><?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" class="admin-card p-7 max-w-2xl space-y-5">
  <?= csrf_field() ?>

  <div class="grid sm:grid-cols-2 gap-5">
    <div>
      <label class="admin-label">Nom complet</label>
      <input type="text" name="nom" required class="admin-input" value="<?= e($utilisateur['nom']) ?>" placeholder="Ex : Sarah Mballa">
    </div>
    <div>
      <label class="admin-label">Adresse e-mail</label>
      <input type="email" name="email" required class="admin-input" value="<?= e($utilisateur['email']) ?>" placeholder="sarah@chezclarence.cm">
    </div>

    <div>
      <label class="admin-label">Rôle</label>
      <select name="role" class="admin-select" <?= $estMoi && nb_administrateurs_actifs($id) === 0 ? 'disabled' : '' ?>>
        <option value="Éditeur" <?= $utilisateur['role'] === 'Éditeur' ? 'selected' : '' ?>>Éditeur — gère le contenu du site</option>
        <option value="Administrateur" <?= $utilisateur['role'] === 'Administrateur' ? 'selected' : '' ?>>Administrateur — accès complet, y compris les utilisateurs</option>
      </select>
      <?php if ($estMoi && nb_administrateurs_actifs($id) === 0): ?>
        <input type="hidden" name="role" value="Administrateur">
        <p class="admin-hint text-red-600">Vous êtes le dernier administrateur actif : ce rôle ne peut pas être changé depuis ce compte.</p>
      <?php else: ?>
        <p class="admin-hint">Un Éditeur ne peut pas accéder à cette page de gestion des utilisateurs.</p>
      <?php endif; ?>
    </div>

    <div>
      <label class="admin-label">Statut du compte</label>
      <div class="flex items-center gap-2.5 h-[46px]">
        <input type="checkbox" id="actif" name="actif" value="1" <?= !empty($utilisateur['actif']) ? 'checked' : '' ?> <?= $estMoi && nb_administrateurs_actifs($id) === 0 ? 'disabled' : '' ?> class="w-4 h-4 accent-brand-600">
        <label for="actif" class="text-sm font-semibold text-ink">Compte actif (peut se connecter)</label>
      </div>
    </div>

    <div class="sm:col-span-2 border-t border-gray-100 pt-5">
      <label class="admin-label"><?= $id ? 'Nouveau mot de passe' : 'Mot de passe' ?></label>
      <div class="password-field-wrap">
        <input type="password" name="mot_de_passe" id="user-password" <?= $id ? '' : 'required' ?> minlength="8" class="admin-input" placeholder="<?= $id ? 'Laisser vide pour ne pas le changer' : '8 caractères minimum' ?>">
        <button type="button" class="password-toggle-btn" data-toggle-password="#user-password"><i class="fa-solid fa-eye"></i></button>
      </div>
    </div>
    <div class="sm:col-span-2">
      <label class="admin-label">Confirmer le mot de passe</label>
      <div class="password-field-wrap">
        <input type="password" name="confirmation" id="user-confirmation" <?= $id ? '' : 'required' ?> minlength="8" class="admin-input">
        <button type="button" class="password-toggle-btn" data-toggle-password="#user-confirmation"><i class="fa-solid fa-eye"></i></button>
      </div>
    </div>
  </div>

  <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
  </button>
</form>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>