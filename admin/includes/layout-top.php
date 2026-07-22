<?php
/**
 * admin/includes/layout-top.php
 * Attend : $admin_title (titre de la page), $admin_current (clé du menu actif).
 */
$admin_current = $admin_current ?? '';
$flash = flash_get();
$p = get_parametres();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($admin_title ?? 'Administration') ?> — Back-office <?= e($p['nom_restaurant']) ?></title>
  <link rel="icon" href="../assets/img/favicon.svg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../assets/vendor/sweetalert2/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/tailwind.css">
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

<button id="admin-burger" class="lg:hidden fixed top-4 left-4 z-50 w-10 h-10 rounded-lg bg-ink text-white grid place-items-center shadow-lg">
  <i class="fa-solid fa-bars"></i>
</button>

<div id="admin-sidebar-backdrop" class="admin-sidebar-backdrop"></div>

<aside class="admin-sidebar" id="admin-sidebar">
  <div class="flex items-center gap-3 px-5 py-6 border-b border-white/10">
    <?php if (!empty($p['logo'])): ?>
      <img src="<?= e(photo_url($p['logo'])) ?>" class="w-10 h-10 rounded-lg object-cover shrink-0" alt="">
    <?php else: ?>
      <span class="w-10 h-10 rounded-lg bg-brand-600 grid place-items-center font-display shrink-0"><?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?></span>
    <?php endif; ?>
    <div class="min-w-0">
      <p class="font-bold text-sm leading-tight truncate"><?= e($p['nom_restaurant']) ?></p>
      <p class="text-white/40 text-xs">Administration du site</p>
    </div>
  </div>

  <nav class="flex-1 overflow-y-auto py-4 admin-sidebar-nav">
    <a href="dashboard.php" class="admin-sidebar-link <?= $admin_current === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>

    <p class="px-5 mt-5 mb-1 text-[11px] uppercase tracking-wider text-white/30 font-bold">Contenu du site</p>
    <a href="parametres.php" class="admin-sidebar-link <?= $admin_current === 'parametres' ? 'active' : '' ?>"><i class="fa-solid fa-sliders"></i> Paramètres généraux</a>
    <a href="hero.php" class="admin-sidebar-link <?= $admin_current === 'hero' ? 'active' : '' ?>"><i class="fa-solid fa-images"></i> Slides d'accueil</a>
    <a href="valeurs.php" class="admin-sidebar-link <?= $admin_current === 'valeurs' ? 'active' : '' ?>"><i class="fa-solid fa-heart"></i> Nos valeurs</a>
    <a href="stats.php" class="admin-sidebar-link <?= $admin_current === 'stats' ? 'active' : '' ?>"><i class="fa-solid fa-chart-simple"></i> Statistiques</a>

    <p class="px-5 mt-5 mb-1 text-[11px] uppercase tracking-wider text-white/30 font-bold">Carte du restaurant</p>
    <a href="categories.php" class="admin-sidebar-link <?= $admin_current === 'categories' ? 'active' : '' ?>"><i class="fa-solid fa-layer-group"></i> Catégories</a>
    <a href="menus.php" class="admin-sidebar-link <?= $admin_current === 'menus' ? 'active' : '' ?>"><i class="fa-solid fa-utensils"></i> Plats</a>

    <p class="px-5 mt-5 mb-1 text-[11px] uppercase tracking-wider text-white/30 font-bold">Services</p>
    <a href="services.php" class="admin-sidebar-link <?= $admin_current === 'services' ? 'active' : '' ?>"><i class="fa-solid fa-concierge-bell"></i> Nos services</a>

    <?php if (is_administrateur()): ?>
    <p class="px-5 mt-5 mb-1 text-[11px] uppercase tracking-wider text-white/30 font-bold">Équipe</p>
    <a href="utilisateurs.php" class="admin-sidebar-link <?= $admin_current === 'utilisateurs' ? 'active' : '' ?>"><i class="fa-solid fa-users-gear"></i> Utilisateurs</a>
    <?php endif; ?>

    <p class="px-5 mt-5 mb-1 text-[11px] uppercase tracking-wider text-white/30 font-bold">Compte</p>
    <a href="compte.php" class="admin-sidebar-link <?= $admin_current === 'compte' ? 'active' : '' ?>"><i class="fa-solid fa-user-gear"></i> Mon compte</a>
    <a href="../index.php" target="_blank" class="admin-sidebar-link"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le site</a>
    <form method="post" action="logout.php" class="js-confirm-logout">
      <?= csrf_field() ?>
      <button type="submit" class="admin-sidebar-link"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</button>
    </form>
  </nav>

  <div class="flex items-center gap-3 px-5 py-4 border-t border-white/10">
    <span class="w-9 h-9 rounded-full bg-brand-600/20 text-brand-400 grid place-items-center font-display text-sm shrink-0"><?= e(mb_strtoupper(mb_substr(current_admin_nom(), 0, 1))) ?></span>
    <div class="min-w-0 text-xs text-white/40">
      Connecté en tant que<br>
      <span class="text-white font-semibold truncate block"><?= e(current_admin_nom()) ?></span>
      <span class="text-brand-400"><?= e(current_admin_role()) ?></span>
    </div>
  </div>
</aside>

<main class="admin-main">
  <header class="bg-white border-b border-gray-200 px-6 lg:px-10 py-5 flex items-center justify-between sticky top-0 z-30">
    <div class="pl-12 lg:pl-0">
      <h1 class="font-display text-xl lg:text-2xl text-ink"><?= e($admin_title ?? '') ?></h1>
    </div>
    <a href="../index.php" target="_blank" class="hidden sm:inline-flex items-center gap-2 text-xs font-bold text-ink/60 hover:text-brand-600 border border-gray-200 rounded-full px-4 py-2">
      <i class="fa-solid fa-eye"></i> Aperçu du site
    </a>
  </header>

  <div class="p-6 lg:p-10">
    <?php if ($flash): ?>
      <div class="mb-6 rounded-xl px-5 py-4 flex items-center gap-3 <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-success-50 text-success-700 border border-success-100' ?>">
        <i class="fa-solid <?= $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <span class="text-sm font-semibold"><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>