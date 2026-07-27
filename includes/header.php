<?php
/** includes/header.php — attend $p (parametres) déjà chargé et $current défini par la page. */
$current = $current ?? 'accueil';
?>
<header id="site-header" class="fixed top-0 inset-x-0 z-50 transition-all duration-300 bg-transparent">
  <nav class="max-w-7xl mx-auto px-5 sm:px-8 h-20 flex items-center justify-between gap-4">
    <a href="index.php" class="flex items-center gap-3 shrink-0 group min-w-0">
      <?php if (!empty($p['logo'])): ?>
        <img src="<?= e(photo_url($p['logo'])) ?>" alt="<?= e($p['nom_restaurant']) ?>" class="h-11 w-11 rounded-xl object-cover ring-2 ring-brand-600/40 group-hover:ring-brand-600 transition shrink-0">
      <?php else: ?>
        <span class="h-11 w-11 rounded-xl bg-ink text-brand-500 grid place-items-center font-display text-xl ring-2 ring-brand-600/40 shrink-0">
          <?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?>
        </span>
      <?php endif; ?>
      <span class="font-display text-xl sm:text-2xl tracking-wide nav-brand-text truncate">
        <?= e($p['nom_restaurant']) ?>
      </span>
    </a>

    <ul class="hidden lg:flex items-center gap-8 font-semibold text-sm nav-links shrink-0">
      <li><a href="index.php#accueil" class="nav-link <?= $current === 'accueil' ? 'nav-link-active' : '' ?>">Accueil</a></li>
      <li><a href="index.php#apropos" class="nav-link">Le restaurant</a></li>
      <li><a href="index.php#menu" class="nav-link">Notre carte</a></li>
      <li><a href="services.php" class="nav-link <?= $current === 'services' ? 'nav-link-active' : '' ?>">Services</a></li>
      <li><a href="index.php#localisation" class="nav-link">Nous trouver</a></li>
      <li><a href="index.php#contact" class="nav-link">Contact</a></li>
    </ul>

    <a href="<?= e(whatsapp_link('Bonjour, je souhaite passer une commande.')) ?>" target="_blank" rel="noopener"
       class="hidden lg:inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-full text-sm shadow-glow transition hover:-translate-y-0.5 shrink-0">
      <i class="fa-brands fa-whatsapp text-base"></i> Commander
    </a>

    <button
      id="btn-burger"
      class="lg:hidden shrink-0 w-11 h-11 rounded-full bg-ink hover:bg-ink-soft text-white grid place-items-center transition"
      aria-label="Ouvrir le menu"
      aria-expanded="false"
      aria-controls="mobile-menu"
    >
      <i class="fa-solid fa-bars text-lg" id="btn-burger-icon"></i>
    </button>
  </nav>

  <!-- Menu mobile -->
  <div id="mobile-menu" class="lg:hidden fixed inset-0 top-20 bg-ink/98 backdrop-blur-xl translate-x-full transition-transform duration-300 ease-out overflow-y-auto">
    <div class="flex items-center gap-3 px-6 pt-6 pb-2">
      <?php if (!empty($p['logo'])): ?>
        <img src="<?= e(photo_url($p['logo'])) ?>" alt="" class="w-10 h-10 rounded-lg object-cover ring-2 ring-brand-600/40 shrink-0">
      <?php else: ?>
        <span class="w-10 h-10 rounded-lg bg-brand-600 grid place-items-center font-display text-white shrink-0"><?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?></span>
      <?php endif; ?>
      <span class="font-display text-white text-lg tracking-wide truncate"><?= e($p['nom_restaurant']) ?></span>
    </div>

    <ul class="flex flex-col gap-1 px-6 py-6 text-lg font-semibold text-white">
      <li><a href="index.php#accueil" class="mobile-link"><i class="fa-solid fa-house w-6 text-brand-500"></i> Accueil</a></li>
      <li><a href="index.php#apropos" class="mobile-link"><i class="fa-solid fa-book-open w-6 text-brand-500"></i> Le restaurant</a></li>
      <li><a href="index.php#menu" class="mobile-link"><i class="fa-solid fa-utensils w-6 text-brand-500"></i> Notre carte</a></li>
      <li><a href="services.php" class="mobile-link"><i class="fa-solid fa-concierge-bell w-6 text-brand-500"></i> Services</a></li>
      <li><a href="index.php#localisation" class="mobile-link"><i class="fa-solid fa-location-dot w-6 text-brand-500"></i> Nous trouver</a></li>
      <li><a href="index.php#contact" class="mobile-link"><i class="fa-solid fa-envelope w-6 text-brand-500"></i> Contact</a></li>
    </ul>
    <div class="px-6 pb-10">
      <a href="<?= e(whatsapp_link('Bonjour, je souhaite passer une commande.')) ?>" target="_blank" rel="noopener"
         class="flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-4 rounded-full transition">
        <i class="fa-brands fa-whatsapp text-lg"></i> Commander sur WhatsApp
      </a>
    </div>
  </div>
</header>