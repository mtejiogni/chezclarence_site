<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $id = (int) $_POST['delete_id'];
    $pdo->prepare('DELETE FROM services WHERE id = :id')->execute(['id' => $id]);
    flash_set('success', 'Service supprimé.');
    header('Location: services.php');
    exit;
}

$services = $pdo->query('SELECT * FROM services ORDER BY ordre ASC')->fetchAll();
$nbActifs = count(array_filter($services, fn ($s) => $s['statut'] === 'Activé'));

$admin_title = 'Nos services';
$admin_current = 'services';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="flex items-center justify-between mb-6">
  <p class="text-ink/50 text-sm max-w-xl">Privatisation, traiteur, carte cadeau... chaque service apparaît en aperçu sur l'accueil et en détail sur la page « Services » du site.</p>
  <a href="service-form.php" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition whitespace-nowrap">
    <i class="fa-solid fa-plus mr-1"></i> Nouveau service
  </a>
</div>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-concierge-bell text-sm"></i></span><div><div class="num"><?= count($services) ?></div><div class="label">Services au total</div></div></div>
  <div class="stat-mini"><span class="w-9 h-9 rounded-lg bg-success-50 text-success-600 grid place-items-center"><i class="fa-solid fa-circle-check text-sm"></i></span><div><div class="num"><?= $nbActifs ?></div><div class="label">Visibles sur le site</div></div></div>
</div>

<div data-table-filter>
  <div class="filter-bar mb-4">
    <div class="filter-search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" data-filter-search class="admin-input" placeholder="Rechercher un service...">
    </div>
    <select data-filter-status class="admin-select" style="width:auto;">
      <option value="">Tous les statuts</option>
      <option value="Activé">Activé</option>
      <option value="Désactivé">Désactivé</option>
    </select>
    <span class="filter-count-pill"><b data-filter-count><?= count($services) ?></b> résultat(s)</span>
  </div>

  <div class="admin-card overflow-x-auto">
    <table class="admin-table">
      <thead><tr><th></th><th data-sort="text">Service</th><th data-sort="text">Résumé</th><th data-sort="number">Ordre</th><th data-sort="text">Statut</th><th class="text-right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr data-row data-statut="<?= e($s['statut']) ?>" data-search="<?= e($s['titre'] . ' ' . $s['resume']) ?>">
            <td><span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid <?= e($s['icone']) ?>"></i></span></td>
            <td>
              <p class="font-bold text-ink"><?= e($s['titre']) ?></p>
              <p class="text-xs text-ink/40">/services.php#<?= e($s['slug']) ?></p>
            </td>
            <td class="text-ink/60 text-sm max-w-xs"><?= e($s['resume']) ?></td>
            <td data-sort-value="<?= (int) $s['ordre'] ?>"><?= (int) $s['ordre'] ?></td>
            <td><span class="badge <?= $s['statut'] === 'Activé' ? 'badge-success' : 'badge-muted' ?>"><?= e($s['statut']) ?></span></td>
            <td class="text-right">
              <div class="flex justify-end gap-2">
                <a href="../services.php#<?= e($s['slug']) ?>" target="_blank" class="icon-btn icon-btn-edit" style="background:#F3F4F6;color:#6B7280;" title="Voir sur le site"><i class="fa-solid fa-eye"></i></a>
                <a href="service-form.php?id=<?= (int) $s['id'] ?>" class="icon-btn icon-btn-edit"><i class="fa-solid fa-pen"></i></a>
                <form method="post" class="js-confirm-delete" data-nom="<?= e($s['titre']) ?>">
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

    <?php if (!$services): ?>
      <div class="empty-state">
        <i class="fa-solid fa-concierge-bell"></i>
        <p>Aucun service pour le moment.</p>
      </div>
    <?php endif; ?>
    <p data-filter-empty class="empty-state" style="display:none;">
      <i class="fa-solid fa-magnifying-glass"></i>
      Aucun service ne correspond à ces filtres.
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>