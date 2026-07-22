<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $pdo->prepare('DELETE FROM valeurs WHERE id = :id')->execute(['id' => (int) $_POST['delete_id']]);
    flash_set('success', 'Valeur supprimée.');
    header('Location: valeurs.php');
    exit;
}

$valeurs = $pdo->query('SELECT * FROM valeurs ORDER BY ordre ASC')->fetchAll();
$nbActives = count(array_filter($valeurs, fn ($v) => $v['statut'] === 'Activé'));

$admin_title = 'Nos valeurs';
$admin_current = 'valeurs';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="flex items-center justify-between mb-6">
  <p class="text-ink/50 text-sm max-w-xl">Les 4 petites cartes (Fraîcheur, Savoir-faire...) affichées dans la section « À propos » du site.</p>
  <a href="valeur-form.php" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition whitespace-nowrap">
    <i class="fa-solid fa-plus mr-1"></i> Nouvelle valeur
  </a>
</div>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-heart text-sm"></i></span><div><div class="num"><?= count($valeurs) ?></div><div class="label">Valeurs au total</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-success-50 text-success-600 grid place-items-center"><i class="fa-solid fa-circle-check text-sm"></i></span><div><div class="num"><?= $nbActives ?></div><div class="label">Visibles sur le site</div></div></div>
</div>

<div data-table-filter>
  <div class="filter-bar mb-4">
    <div class="filter-search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" data-filter-search class="admin-input" placeholder="Rechercher une valeur...">
    </div>
    <select data-filter-status class="admin-select" style="width:auto;">
      <option value="">Tous les statuts</option>
      <option value="Activé">Activé</option>
      <option value="Désactivé">Désactivé</option>
    </select>
    <span class="filter-count-pill"><b data-filter-count><?= count($valeurs) ?></b> résultat(s)</span>
  </div>

  <div class="admin-card overflow-x-auto">
    <table class="admin-table">
      <thead><tr><th></th><th data-sort="text">Titre</th><th data-sort="text">Texte</th><th data-sort="number">Ordre</th><th data-sort="text">Statut</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($valeurs as $v): ?>
          <tr data-row data-statut="<?= e($v['statut']) ?>" data-search="<?= e($v['titre'] . ' ' . $v['texte']) ?>">
            <td><span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid <?= e($v['icone']) ?>"></i></span></td>
            <td class="font-bold text-ink"><?= e($v['titre']) ?></td>
            <td class="text-ink/60 text-sm"><?= e($v['texte']) ?></td>
            <td data-sort-value="<?= (int) $v['ordre'] ?>"><?= (int) $v['ordre'] ?></td>
            <td><span class="badge <?= $v['statut'] === 'Activé' ? 'badge-success' : 'badge-muted' ?>"><?= e($v['statut']) ?></span></td>
            <td class="text-right">
              <div class="flex justify-end gap-2">
                <a href="valeur-form.php?id=<?= (int) $v['id'] ?>" class="icon-btn icon-btn-edit"><i class="fa-solid fa-pen"></i></a>
                <form method="post" class="js-confirm-delete" data-nom="<?= e($v['titre']) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="delete_id" value="<?= (int) $v['id'] ?>">
                  <button type="submit" class="icon-btn icon-btn-delete"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (!$valeurs): ?>
      <div class="empty-state">
        <i class="fa-solid fa-heart"></i>
        <p>Aucune valeur pour le moment.</p>
      </div>
    <?php endif; ?>
    <p data-filter-empty class="empty-state" style="display:none;">
      <i class="fa-solid fa-magnifying-glass"></i>
      Aucune valeur ne correspond à ces filtres.
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>