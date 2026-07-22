<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $id = (int) $_POST['delete_id'];

    $stmt = $pdo->prepare('SELECT image FROM hero_slides WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $image = $stmt->fetchColumn();

    $pdo->prepare('DELETE FROM hero_slides WHERE id = :id')->execute(['id' => $id]);
    if ($image) {
        @unlink(__DIR__ . '/../uploads/' . $image);
    }

    flash_set('success', 'Slide supprimé.');
    header('Location: hero.php');
    exit;
}

$slides = $pdo->query('SELECT * FROM hero_slides ORDER BY ordre ASC')->fetchAll();
$nbActifs = count(array_filter($slides, fn ($s) => $s['statut'] === 'Activé'));

$admin_title = "Slides de la bannière d'accueil";
$admin_current = 'hero';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="flex items-center justify-between mb-6">
  <p class="text-ink/50 text-sm max-w-xl">Le grand visuel défilant tout en haut du site. Chaque slide peut mettre en avant une action différente (commander, réserver, traiteur...). L'ordre ci-dessous est celui du carrousel.</p>
  <a href="hero-form.php" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition whitespace-nowrap">
    <i class="fa-solid fa-plus mr-1"></i> Nouveau slide
  </a>
</div>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-images text-sm"></i></span><div><div class="num"><?= count($slides) ?></div><div class="label">Slides au total</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-success-50 text-success-600 grid place-items-center"><i class="fa-solid fa-circle-check text-sm"></i></span><div><div class="num"><?= $nbActifs ?></div><div class="label">Visibles sur le site</div></div></div>
</div>

<div data-table-filter>
  <div class="filter-bar mb-4">
    <div class="filter-search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" data-filter-search class="admin-input" placeholder="Rechercher un slide...">
    </div>
    <select data-filter-status class="admin-select" style="width:auto;">
      <option value="">Tous les statuts</option>
      <option value="Activé">Activé</option>
      <option value="Désactivé">Désactivé</option>
    </select>
    <span class="filter-count-pill"><b data-filter-count><?= count($slides) ?></b> résultat(s)</span>
  </div>

  <div class="admin-card overflow-x-auto">
    <table class="admin-table">
      <thead><tr><th data-sort="number">#</th><th data-sort="text">Titre</th><th data-sort="text">Illustration</th><th data-sort="number">Ordre</th><th data-sort="text">Statut</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($slides as $i => $s): ?>
          <tr data-row data-statut="<?= e($s['statut']) ?>" data-search="<?= e(strip_tags($s['titre']) . ' ' . $s['badge']) ?>">
            <td data-sort-value="<?= $i + 1 ?>" class="text-ink/40 font-bold"><?= $i + 1 ?></td>
            <td>
              <p class="font-bold text-ink"><?= e(strip_tags($s['titre'])) ?></p>
              <p class="text-xs text-ink/45"><?= e($s['badge']) ?></p>
            </td>
            <td class="capitalize text-sm text-ink/60"><?= e($s['illustration']) ?></td>
            <td data-sort-value="<?= (int) $s['ordre'] ?>"><?= (int) $s['ordre'] ?></td>
            <td><span class="badge <?= $s['statut'] === 'Activé' ? 'badge-success' : 'badge-muted' ?>"><?= e($s['statut']) ?></span></td>
            <td class="text-right">
              <div class="flex justify-end gap-2">
                <a href="hero-form.php?id=<?= (int) $s['id'] ?>" class="icon-btn icon-btn-edit"><i class="fa-solid fa-pen"></i></a>
                <form method="post" class="js-confirm-delete" data-nom="ce slide">
                  <?= csrf_field() ?>
                  <input type="hidden" name="delete_id" value="<?= (int) $s['id'] ?>">
                  <button type="submit" class="icon-btn icon-btn-delete"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (!$slides): ?>
      <div class="empty-state">
        <i class="fa-solid fa-images"></i>
        <p>Aucun slide pour le moment. Le site affichera un slide par défaut.</p>
      </div>
    <?php endif; ?>
    <p data-filter-empty class="empty-state" style="display:none;">
      <i class="fa-solid fa-magnifying-glass"></i>
      Aucun slide ne correspond à ces filtres.
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>