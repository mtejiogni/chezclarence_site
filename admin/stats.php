<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $pdo->prepare('DELETE FROM statistiques WHERE id = :id')->execute(['id' => (int) $_POST['delete_id']]);
    flash_set('success', 'Statistique supprimée.');
    header('Location: stats.php');
    exit;
}

$stats = $pdo->query('SELECT * FROM statistiques ORDER BY ordre ASC')->fetchAll();
$nbActifs = count(array_filter($stats, fn ($s) => $s['statut'] === 'Activé'));

$admin_title = 'Statistiques (compteurs animés)';
$admin_current = 'stats';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="flex items-center justify-between mb-6">
  <p class="text-ink/50 text-sm max-w-xl">Les chiffres clés affichés en bandeau sous la section « À propos » du site, avec animation de comptage.</p>
  <a href="stat-form.php" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition whitespace-nowrap">
    <i class="fa-solid fa-plus mr-1"></i> Nouvelle statistique
  </a>
</div>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-chart-simple text-sm"></i></span><div><div class="num"><?= count($stats) ?></div><div class="label">Compteurs au total</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-success-50 text-success-600 grid place-items-center"><i class="fa-solid fa-circle-check text-sm"></i></span><div><div class="num"><?= $nbActifs ?></div><div class="label">Visibles sur le site</div></div></div>
</div>

<div data-table-filter>
  <div class="filter-bar mb-4">
    <div class="filter-search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" data-filter-search class="admin-input" placeholder="Rechercher une statistique...">
    </div>
    <select data-filter-status class="admin-select" style="width:auto;">
      <option value="">Tous les statuts</option>
      <option value="Activé">Activé</option>
      <option value="Désactivé">Désactivé</option>
    </select>
    <span class="filter-count-pill"><b data-filter-count><?= count($stats) ?></b> résultat(s)</span>
  </div>

  <div class="admin-card overflow-x-auto">
    <table class="admin-table">
      <thead><tr><th></th><th data-sort="number">Valeur affichée</th><th data-sort="text">Libellé</th><th data-sort="text">Calcul auto</th><th data-sort="text">Statut</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($stats as $s): ?>
          <?php $valeurAffichee = $s['calcul_auto'] === 'annees_depuis_1990' ? (date('Y') - 1990) : (int) $s['valeur']; ?>
          <tr data-row data-statut="<?= e($s['statut']) ?>" data-search="<?= e($s['label']) ?>">
            <td><span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid <?= e($s['icone']) ?>"></i></span></td>
            <td data-sort-value="<?= $valeurAffichee ?>" class="font-bold text-ink"><?= $valeurAffichee ?><?= e($s['suffixe']) ?></td>
            <td><?= e($s['label']) ?></td>
            <td><?= $s['calcul_auto'] === 'non' ? '<span class="text-ink/30">—</span>' : '<span class="badge badge-brand">Années depuis 1990</span>' ?></td>
            <td><span class="badge <?= $s['statut'] === 'Activé' ? 'badge-success' : 'badge-muted' ?>"><?= e($s['statut']) ?></span></td>
            <td class="text-right">
              <div class="flex justify-end gap-2">
                <a href="stat-form.php?id=<?= (int) $s['id'] ?>" class="icon-btn icon-btn-edit"><i class="fa-solid fa-pen"></i></a>
                <form method="post" class="js-confirm-delete" data-nom="<?= e($s['label']) ?>">
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

    <?php if (!$stats): ?>
      <div class="empty-state">
        <i class="fa-solid fa-chart-simple"></i>
        <p>Aucune statistique pour le moment.</p>
      </div>
    <?php endif; ?>
    <p data-filter-empty class="empty-state" style="display:none;">
      <i class="fa-solid fa-magnifying-glass"></i>
      Aucune statistique ne correspond à ces filtres.
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>