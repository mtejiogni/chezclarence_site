<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $id = (int) $_POST['delete_id'];

    $stmt = $pdo->prepare('SELECT photo FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $photo = $stmt->fetchColumn();

    $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);

    if ($photo) {
        @unlink(__DIR__ . '/../uploads/' . $photo);
    }

    flash_set('success', 'Catégorie supprimée (les plats associés ont également été retirés).');
    header('Location: categories.php');
    exit;
}

$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM menus m WHERE m.categorie_id = c.id) AS nb_plats
    FROM categories c ORDER BY ordre ASC, intitule ASC
")->fetchAll();

$nbActives = count(array_filter($categories, fn ($c) => $c['statut'] === 'Activé'));
$nbPlatsTotal = array_sum(array_column($categories, 'nb_plats'));

$admin_title = 'Catégories du menu';
$admin_current = 'categories';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
  <p class="text-ink/50 text-sm max-w-xl">Organisez votre carte en catégories (Grillades, Boissons...). L'ordre défini ici est celui affiché sur le site.</p>
  <a href="categorie-form.php" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition whitespace-nowrap">
    <i class="fa-solid fa-plus mr-1"></i> Nouvelle catégorie
  </a>
</div>

<div class="grid sm:grid-cols-3 gap-4 mb-6">
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-layer-group text-sm"></i></span><div><div class="num"><?= count($categories) ?></div><div class="label">Catégories au total</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-success-50 text-success-600 grid place-items-center"><i class="fa-solid fa-circle-check text-sm"></i></span><div><div class="num"><?= $nbActives ?></div><div class="label">Actives sur le site</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-utensils text-sm"></i></span><div><div class="num"><?= $nbPlatsTotal ?></div><div class="label">Plats répartis dedans</div></div></div>
</div>

<div data-table-filter>
  <div class="filter-bar mb-4">
    <div class="filter-search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" data-filter-search class="admin-input" placeholder="Rechercher une catégorie...">
    </div>
    <select data-filter-status class="admin-select" style="width:auto;">
      <option value="">Tous les statuts</option>
      <option value="Activé">Activé</option>
      <option value="Désactivé">Désactivé</option>
    </select>
    <span class="filter-count-pill"><b data-filter-count><?= count($categories) ?></b> résultat(s)</span>
  </div>

  <div class="admin-card overflow-x-auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th></th>
          <th data-sort="text">Catégorie</th>
          <th data-sort="number">Plats</th>
          <th data-sort="number">Ordre</th>
          <th data-sort="text">Statut</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr data-row data-statut="<?= e($cat['statut']) ?>" data-search="<?= e($cat['intitule'] . ' ' . $cat['description']) ?>">
            <td>
              <?php if (!empty($cat['photo'])): ?>
                <img src="<?= e(photo_url($cat['photo'])) ?>" class="admin-thumb" alt="">
              <?php else: ?>
                <span class="admin-thumb grid place-items-center text-ink/20"><i class="fa-solid fa-image"></i></span>
              <?php endif; ?>
            </td>
            <td>
              <p class="font-bold text-ink"><?= e($cat['intitule']) ?></p>
              <p class="text-xs text-ink/45 line-clamp-1 max-w-xs"><?= e($cat['description']) ?></p>
            </td>
            <td data-sort-value="<?= (int) $cat['nb_plats'] ?>"><?= (int) $cat['nb_plats'] ?></td>
            <td data-sort-value="<?= (int) $cat['ordre'] ?>"><?= (int) $cat['ordre'] ?></td>
            <td>
              <span class="badge <?= $cat['statut'] === 'Activé' ? 'badge-success' : 'badge-muted' ?>"><?= e($cat['statut']) ?></span>
            </td>
            <td class="text-right">
              <div class="flex justify-end gap-2">
                <a href="categorie-form.php?id=<?= (int) $cat['id'] ?>" class="icon-btn icon-btn-edit"><i class="fa-solid fa-pen"></i></a>
                <form method="post" class="js-confirm-delete" data-nom="<?= e($cat['intitule']) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="delete_id" value="<?= (int) $cat['id'] ?>">
                  <button type="submit" class="icon-btn icon-btn-delete"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (!$categories): ?>
      <div class="empty-state">
        <i class="fa-solid fa-layer-group"></i>
        <p>Aucune catégorie pour le moment — créez-en une pour commencer à construire votre carte.</p>
      </div>
    <?php endif; ?>
    <p data-filter-empty class="empty-state" style="display:none;">
      <i class="fa-solid fa-magnifying-glass"></i>
      Aucune catégorie ne correspond à ces filtres.
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>