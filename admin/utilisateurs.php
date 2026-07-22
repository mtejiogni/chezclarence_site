<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_administrateur();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $id = (int) $_POST['delete_id'];

    if ($id === (int) $_SESSION['admin_id']) {
        flash_set('error', 'Vous ne pouvez pas supprimer votre propre compte. Demandez à un autre administrateur de le faire si besoin.');
        header('Location: utilisateurs.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT role, actif FROM administrateurs WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $cible = $stmt->fetch();

    if ($cible && $cible['role'] === 'Administrateur' && (int) $cible['actif'] === 1 && nb_administrateurs_actifs($id) === 0) {
        flash_set('error', "Impossible de supprimer ce compte : c'est le dernier administrateur actif du site.");
        header('Location: utilisateurs.php');
        exit;
    }

    $pdo->prepare('DELETE FROM administrateurs WHERE id = :id')->execute(['id' => $id]);
    flash_set('success', 'Utilisateur supprimé.');
    header('Location: utilisateurs.php');
    exit;
}

$utilisateurs = $pdo->query('SELECT * FROM administrateurs ORDER BY role ASC, nom ASC')->fetchAll();
$nbAdmins = count(array_filter($utilisateurs, fn ($u) => $u['role'] === 'Administrateur'));
$nbEditeurs = count(array_filter($utilisateurs, fn ($u) => $u['role'] === 'Éditeur'));
$nbActifs = count(array_filter($utilisateurs, fn ($u) => (int) $u['actif'] === 1));

$admin_title = 'Utilisateurs du back-office';
$admin_current = 'utilisateurs';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="flex items-center justify-between mb-6">
  <p class="text-ink/50 text-sm max-w-xl">
    Donnez accès au back-office à votre équipe. Un <b>Administrateur</b> a accès à tout, y compris à la gestion des utilisateurs. Un <b>Éditeur</b> peut gérer tout le contenu du site (menu, services, paramètres...) mais pas les comptes.
  </p>
  <a href="utilisateur-form.php" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition whitespace-nowrap">
    <i class="fa-solid fa-user-plus mr-1"></i> Nouvel utilisateur
  </a>
</div>

<div class="grid sm:grid-cols-3 gap-4 mb-6">
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-user-shield text-sm"></i></span><div><div class="num"><?= $nbAdmins ?></div><div class="label">Administrateur(s)</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 grid place-items-center"><i class="fa-solid fa-pen text-sm"></i></span><div><div class="num"><?= $nbEditeurs ?></div><div class="label">Éditeur(s)</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-success-50 text-success-600 grid place-items-center"><i class="fa-solid fa-circle-check text-sm"></i></span><div><div class="num"><?= $nbActifs ?></div><div class="label">Comptes actifs</div></div></div>
</div>

<div data-table-filter>
  <div class="filter-bar mb-4">
    <div class="filter-search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" data-filter-search class="admin-input" placeholder="Rechercher un utilisateur...">
    </div>
    <select data-filter-status class="admin-select" style="width:auto;">
      <option value="">Tous les rôles</option>
      <option value="Administrateur">Administrateur</option>
      <option value="Éditeur">Éditeur</option>
    </select>
    <span class="filter-count-pill"><b data-filter-count><?= count($utilisateurs) ?></b> résultat(s)</span>
  </div>

  <div class="admin-card overflow-x-auto">
    <table class="admin-table">
      <thead>
        <tr><th data-sort="text">Utilisateur</th><th data-sort="text">Rôle</th><th data-sort="text">Statut</th><th data-sort="text">Dernière connexion</th><th class="text-right">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($utilisateurs as $u): ?>
          <?php $estMoi = (int) $u['id'] === (int) $_SESSION['admin_id']; ?>
          <tr data-row data-statut="<?= e($u['role']) ?>" data-search="<?= e($u['nom'] . ' ' . $u['email']) ?>">
            <td>
              <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-full bg-ink text-brand-500 grid place-items-center font-display text-sm shrink-0"><?= e(mb_strtoupper(mb_substr($u['nom'], 0, 1))) ?></span>
                <div>
                  <p class="font-bold text-ink flex items-center gap-2"><?= e($u['nom']) ?> <?php if ($estMoi): ?><span class="badge badge-muted">Vous</span><?php endif; ?></p>
                  <p class="text-xs text-ink/45"><?= e($u['email']) ?></p>
                </div>
              </div>
            </td>
            <td><span class="badge <?= $u['role'] === 'Administrateur' ? 'badge-brand' : 'badge-info' ?>"><i class="fa-solid <?= $u['role'] === 'Administrateur' ? 'fa-user-shield' : 'fa-pen' ?> mr-1"></i><?= e($u['role']) ?></span></td>
            <td><span class="badge <?= (int) $u['actif'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $u['actif'] === 1 ? 'Actif' : 'Désactivé' ?></span></td>
            <td class="text-sm text-ink/60"><?= $u['derniere_connexion'] ? date('d/m/Y à H:i', strtotime($u['derniere_connexion'])) : 'Jamais connecté' ?></td>
            <td class="text-right">
              <div class="flex justify-end gap-2">
                <a href="utilisateur-form.php?id=<?= (int) $u['id'] ?>" class="icon-btn icon-btn-edit"><i class="fa-solid fa-pen"></i></a>
                <?php if (!$estMoi): ?>
                  <form method="post" class="js-confirm-delete" data-nom="<?= e($u['nom']) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delete_id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="icon-btn icon-btn-delete"><i class="fa-solid fa-trash"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (!$utilisateurs): ?>
      <div class="empty-state">
        <i class="fa-solid fa-users"></i>
        <p>Aucun utilisateur pour le moment.</p>
      </div>
    <?php endif; ?>
    <p data-filter-empty class="empty-state" style="display:none;">
      <i class="fa-solid fa-magnifying-glass"></i>
      Aucun utilisateur ne correspond à ces filtres.
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>