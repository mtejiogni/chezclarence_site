<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();
$compteurs = [
    'categories' => (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'menus' => (int) $pdo->query("SELECT COUNT(*) FROM menus")->fetchColumn(),
    'services' => (int) $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn(),
    'hero' => (int) $pdo->query("SELECT COUNT(*) FROM hero_slides")->fetchColumn(),
];

$dernierMenus = $pdo->query("
    SELECT m.intitule, m.photo, m.prix, m.statut, m.created_at, c.intitule AS categorie_nom
    FROM menus m JOIN categories c ON c.id = m.categorie_id
    ORDER BY m.created_at DESC LIMIT 5
")->fetchAll();

$devise = get_parametres()['devise'];

$admin_title = 'Tableau de bord';
$admin_current = 'dashboard';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
  <a href="categories.php" class="stat-card block">
    <div class="flex items-center justify-between">
      <span class="num"><?= $compteurs['categories'] ?></span>
      <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-layer-group"></i></span>
    </div>
    <p class="text-ink/50 text-sm mt-2 font-medium">Catégories de menu</p>
  </a>
  <a href="menus.php" class="stat-card block">
    <div class="flex items-center justify-between">
      <span class="num"><?= $compteurs['menus'] ?></span>
      <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-utensils"></i></span>
    </div>
    <p class="text-ink/50 text-sm mt-2 font-medium">Plats au menu</p>
  </a>
  <a href="services.php" class="stat-card block">
    <div class="flex items-center justify-between">
      <span class="num"><?= $compteurs['services'] ?></span>
      <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-concierge-bell"></i></span>
    </div>
    <p class="text-ink/50 text-sm mt-2 font-medium">Services proposés</p>
  </a>
  <a href="hero.php" class="stat-card block">
    <div class="flex items-center justify-between">
      <span class="num"><?= $compteurs['hero'] ?></span>
      <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="fa-solid fa-images"></i></span>
    </div>
    <p class="text-ink/50 text-sm mt-2 font-medium">Slides d'accueil</p>
  </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">
  <div class="admin-card p-7 lg:col-span-2">
    <h2 class="font-display text-lg text-ink mb-1">Bienvenue, <?= e(current_admin_nom()) ?> 👋</h2>
    <p class="text-ink/55 text-sm leading-relaxed mb-6">
      Toutes les informations affichées sur le site public (textes, photos, plats, services, statistiques...) se modifient depuis ce panneau, sans aucune connaissance technique. Les changements sont visibles immédiatement sur le site.
    </p>

    <div class="grid sm:grid-cols-2 gap-4">
      <a href="parametres.php" class="flex items-center gap-3 border border-gray-200 rounded-xl p-4 hover:border-brand-300 hover:bg-brand-50/40 transition">
        <span class="w-10 h-10 rounded-lg bg-ink text-brand-500 grid place-items-center"><i class="fa-solid fa-sliders"></i></span>
        <div><p class="font-bold text-sm text-ink">Paramètres du restaurant</p><p class="text-xs text-ink/50">Coordonnées, WhatsApp, réseaux sociaux</p></div>
      </a>
      <a href="menu-form.php" class="flex items-center gap-3 border border-gray-200 rounded-xl p-4 hover:border-brand-300 hover:bg-brand-50/40 transition">
        <span class="w-10 h-10 rounded-lg bg-ink text-brand-500 grid place-items-center"><i class="fa-solid fa-plus"></i></span>
        <div><p class="font-bold text-sm text-ink">Ajouter un plat</p><p class="text-xs text-ink/50">Complétez votre carte en quelques secondes</p></div>
      </a>
      <a href="hero-form.php" class="flex items-center gap-3 border border-gray-200 rounded-xl p-4 hover:border-brand-300 hover:bg-brand-50/40 transition">
        <span class="w-10 h-10 rounded-lg bg-ink text-brand-500 grid place-items-center"><i class="fa-solid fa-images"></i></span>
        <div><p class="font-bold text-sm text-ink">Nouveau slide</p><p class="text-xs text-ink/50">Mettez en avant une offre du moment</p></div>
      </a>
      <a href="../index.php" target="_blank" class="flex items-center gap-3 border border-gray-200 rounded-xl p-4 hover:border-brand-300 hover:bg-brand-50/40 transition">
        <span class="w-10 h-10 rounded-lg bg-ink text-brand-500 grid place-items-center"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
        <div><p class="font-bold text-sm text-ink">Voir le site public</p><p class="text-xs text-ink/50">Ouvre le site dans un nouvel onglet</p></div>
      </a>
    </div>
  </div>

  <div class="admin-card p-7">
    <h2 class="font-display text-base text-ink mb-4 flex items-center gap-2"><i class="fa-solid fa-clock-rotate-left text-brand-600"></i> Derniers plats ajoutés</h2>
    <?php if (!$dernierMenus): ?>
      <p class="text-sm text-ink/40">Aucun plat pour le moment.</p>
    <?php else: ?>
      <ul class="space-y-3">
        <?php foreach ($dernierMenus as $m): ?>
          <li class="flex items-center gap-3">
            <?php if (!empty($m['photo'])): ?>
              <img src="<?= e(photo_url($m['photo'])) ?>" class="admin-thumb" alt="">
            <?php else: ?>
              <span class="admin-thumb grid place-items-center text-ink/20"><i class="fa-solid fa-utensils"></i></span>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-ink truncate"><?= e($m['intitule']) ?></p>
              <p class="text-xs text-ink/45"><?= e($m['categorie_nom']) ?> · <?= number_format((float) $m['prix'], 0, ',', ' ') ?> <?= e($devise) ?></p>
            </div>
            <span class="badge <?= $m['statut'] === 'Activé' ? 'badge-success' : 'badge-muted' ?> shrink-0"><?= e($m['statut']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <a href="menus.php" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
        Voir tous les plats <i class="fa-solid fa-arrow-right text-xs"></i>
      </a>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>