<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $id = (int) $_POST['delete_id'];

    $stmt = $pdo->prepare('SELECT photo FROM menus WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $photo = $stmt->fetchColumn();

    $pdo->prepare('DELETE FROM menus WHERE id = :id')->execute(['id' => $id]);
    if ($photo) {
        @unlink(__DIR__ . '/../uploads/' . $photo);
    }

    flash_set('success', 'Plat supprimé.');
    header('Location: menus.php');
    exit;
}

$categorieFiltre = isset($_GET['categorie']) ? (int) $_GET['categorie'] : 0;

$sql = "SELECT m.*, c.intitule AS categorie_nom FROM menus m
        JOIN categories c ON c.id = m.categorie_id";
$params = [];
if ($categorieFiltre) {
    $sql .= " WHERE m.categorie_id = :cat";
    $params['cat'] = $categorieFiltre;
}
$sql .= " ORDER BY c.ordre ASC, m.ordre ASC, m.intitule ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$menus = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, intitule FROM categories ORDER BY ordre ASC')->fetchAll();
$devise = get_parametres()['devise'];

$nbActifs = count(array_filter($menus, fn ($m) => $m['statut'] === 'Activé'));
$nbPopulaires = count(array_filter($menus, fn ($m) => (int) $m['populaire'] === 1));
$prixMoyen = $menus ? array_sum(array_column($menus, 'prix')) / count($menus) : 0;

$admin_title = 'Plats du menu';
$admin_current = 'menus';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
  <div class="flex items-center gap-3">
    <p class="text-ink/50 text-sm">Filtrer par catégorie :</p>
    <select onchange="window.location='menus.php' + (this.value ? '?categorie=' + this.value : '')" class="admin-select w-auto">
      <option value="">Toutes les catégories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $categorieFiltre === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['intitule']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <a href="menu-form.php" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition">
    <i class="fa-solid fa-plus mr-1"></i> Nouveau plat
  </a>
</div>

<?php if (!$categories): ?>
  <div class="admin-card p-8 text-center text-ink/50">
    Créez d'abord une <a href="categories.php" class="text-brand-600 font-semibold">catégorie</a> avant d'ajouter des plats.
  </div>
<?php else: ?>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-utensils text-sm"></i></span><div><div class="num"><?= count($menus) ?></div><div class="label">Plats affichés</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-success-50 text-success-600 grid place-items-center"><i class="fa-solid fa-circle-check text-sm"></i></span><div><div class="num"><?= $nbActifs ?></div><div class="label">Actifs sur le site</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-fire text-sm"></i></span><div><div class="num"><?= $nbPopulaires ?></div><div class="label">Mis en avant</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-coins text-sm"></i></span><div><div class="num"><?= number_format($prixMoyen, 0, ',', ' ') ?></div><div class="label">Prix moyen (<?= e($devise) ?>)</div></div></div>
</div>

<div data-table-filter>
  <div class="filter-bar mb-4">
    <div class="filter-search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" data-filter-search class="admin-input" placeholder="Rechercher un plat...">
    </div>
    <select data-filter-status class="admin-select" style="width:auto;">
      <option value="">Tous les statuts</option>
      <option value="Activé">Activé</option>
      <option value="Désactivé">Désactivé</option>
    </select>
    <span class="filter-count-pill"><b data-filter-count><?= count($menus) ?></b> résultat(s)</span>
  </div>

  <div class="admin-card overflow-x-auto">
    <table class="admin-table">
      <thead>
        <tr><th></th><th data-sort="text">Plat</th><th data-sort="text">Catégorie</th><th data-sort="number">Prix</th><th data-sort="number">Note</th><th data-sort="text">Statut</th><th class="text-right">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($menus as $m): ?>
          <tr data-row data-statut="<?= e($m['statut']) ?>" data-search="<?= e($m['intitule'] . ' ' . $m['categorie_nom'] . ' ' . $m['description']) ?>">
            <td>
              <?php if (!empty($m['photo'])): ?>
                <img src="<?= e(photo_url($m['photo'])) ?>" class="admin-thumb" alt="">
              <?php else: ?>
                <span class="admin-thumb grid place-items-center text-ink/20"><i class="fa-solid fa-utensils"></i></span>
              <?php endif; ?>
            </td>
            <td>
              <p class="font-bold text-ink flex items-center gap-2">
                <?= e($m['intitule']) ?>
                <?php if ($m['populaire']): ?><span class="badge badge-brand"><i class="fa-solid fa-fire"></i> Populaire</span><?php endif; ?>
              </p>
              <p class="text-xs text-ink/45 line-clamp-1 max-w-xs"><?= e($m['description']) ?></p>
            </td>
            <td><?= e($m['categorie_nom']) ?></td>
            <td data-sort-value="<?= (float) $m['prix'] ?>" class="font-semibold"><?= number_format((float) $m['prix'], 0, ',', ' ') ?> <?= e($devise) ?></td>
            <td data-sort-value="<?= (int) $m['etoiles'] ?>">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa-solid fa-star text-xs" style="color:<?= $i <= $m['etoiles'] ? '#F59E0B' : '#E5E7EB' ?>;"></i>
              <?php endfor; ?>
            </td>
            <td><span class="badge <?= $m['statut'] === 'Activé' ? 'badge-success' : 'badge-muted' ?>"><?= e($m['statut']) ?></span></td>
            <td class="text-right">
              <div class="flex justify-end gap-2">
                <a href="menu-form.php?id=<?= (int) $m['id'] ?>" class="icon-btn icon-btn-edit"><i class="fa-solid fa-pen"></i></a>
                <form method="post" class="js-confirm-delete" data-nom="<?= e($m['intitule']) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="delete_id" value="<?= (int) $m['id'] ?>">
                  <button type="submit" class="icon-btn icon-btn-delete"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (!$menus): ?>
      <div class="empty-state">
        <i class="fa-solid fa-utensils"></i>
        <p>Aucun plat pour le moment.</p>
      </div>
    <?php endif; ?>
    <p data-filter-empty class="empty-state" style="display:none;">
      <i class="fa-solid fa-magnifying-glass"></i>
      Aucun plat ne correspond à ces filtres.
    </p>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>