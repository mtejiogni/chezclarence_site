<?php
require_once __DIR__ . '/includes/functions.php';

// Redirige automatiquement vers l'assistant d'installation tant que le
// site n'a pas encore de base de données et compte administrateur.
if (!site_est_installe()) {
    header('Location: admin/install.php');
    exit;
}


$p        = get_parametres();
$services = get_services();
$current  = 'services';

$page_title       = 'Nos services — ' . $p['nom_restaurant'];
$page_description = "Privatisation, service traiteur, carte cadeau, livraison, événements et formules entreprise : découvrez tous les services de {$p['nom_restaurant']}.";
?>
<!doctype html>
<html lang="fr">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="antialiased">

<div id="preloader">
  <div class="preloader-flame"><span></span><span></span></div>
  <p class="text-white/60 text-xs uppercase tracking-[0.3em] font-semibold">Chez Clarence</p>
</div>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- ============================================================
     BANNIÈRE
============================================================ -->
<section class="hero-slide" style="min-height:60vh;">
  <div class="hero-pattern"></div>
  <div class="relative max-w-5xl mx-auto px-5 sm:px-8 text-center pt-32 pb-20">
    <span class="hero-badge"><i class="fa-solid fa-concierge-bell"></i> Au-delà de l'assiette</span>
    <h1 class="font-display text-white text-[clamp(2.6rem,7vw,4.6rem)] leading-[1] mt-6">Nos services</h1>
    <p class="text-white/70 text-base sm:text-lg mt-5 max-w-2xl mx-auto leading-relaxed">
      Événements privés, traiteur, cartes cadeaux, livraison ou formules entreprise : chaque service est pensé pour s'adapter précisément à votre besoin. Un devis vous est proposé gratuitement sur WhatsApp.
    </p>

    <!-- Navigation rapide -->
    <div class="mt-9 flex flex-wrap justify-center gap-2.5">
      <?php foreach ($services as $s): ?>
        <a href="#<?= e($s['slug']) ?>" class="inline-flex items-center gap-2 bg-white/10 hover:bg-brand-600 border border-white/15 hover:border-brand-600 text-white text-sm font-semibold px-4 py-2 rounded-full transition">
          <i class="fa-solid <?= e($s['icone']) ?> text-brand-500"></i> <?= e($s['titre']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     DÉTAIL DE CHAQUE SERVICE
============================================================ -->
<?php foreach ($services as $i => $s): $alt = $i % 2 === 1; ?>
<section id="<?= e($s['slug']) ?>" class="py-20 sm:py-28 scroll-mt-24 <?= $alt ? 'bg-gray-50' : 'bg-white' ?>">
  <div class="max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-2 gap-14 items-center">

    <div class="<?= $alt ? 'lg:order-2' : '' ?>" data-aos="<?= $alt ? 'fade-left' : 'fade-right' ?>">
      <span class="service-icon inline-grid w-16 h-16 place-items-center rounded-2xl bg-brand-50 text-brand-600 text-2xl mb-6">
        <i class="fa-solid <?= e($s['icone']) ?>"></i>
      </span>
      <h2 class="font-display text-3xl sm:text-4xl text-ink"><?= e($s['titre']) ?></h2>
      <p class="text-ink/65 text-base sm:text-lg mt-5 leading-relaxed"><?= e($s['description']) ?></p>

      <ul class="mt-7 space-y-3">
        <?php foreach ($s['points'] as $point): ?>
          <li class="flex items-start gap-3 text-sm sm:text-base text-ink/75">
            <span class="shrink-0 w-6 h-6 rounded-full bg-success-50 text-success-600 grid place-items-center mt-0.5">
              <i class="fa-solid fa-check text-xs"></i>
            </span>
            <?= e($point) ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="mt-9 flex flex-wrap gap-3">
        <a href="<?= e($s['lien_whatsapp']) ?>" target="_blank" rel="noopener"
           class="btn-cta inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-7 py-4 rounded-full shadow-glow transition hover:-translate-y-0.5">
          <i class="fa-brands fa-whatsapp text-lg"></i> <span>Demander un devis gratuit</span>
        </a>
        <a href="index.php#menu" class="inline-flex items-center gap-2 text-ink/70 hover:text-brand-600 font-bold px-3 py-4">
          Voir notre carte →
        </a>
      </div>
    </div>

    <div class="<?= $alt ? 'lg:order-1' : '' ?> relative flex justify-center" data-aos="zoom-in">
      <div class="w-full max-w-md aspect-square rounded-[2.5rem] bg-gradient-to-br from-ink to-ink-soft relative overflow-hidden shadow-2xl">
        <div class="hero-pattern"></div>
        <div class="absolute inset-0 flex items-center justify-center">
          <span class="w-40 h-40 rounded-full bg-brand-600/15 grid place-items-center float-1">
            <span class="w-28 h-28 rounded-full bg-brand-600 grid place-items-center text-white text-5xl">
              <i class="fa-solid <?= e($s['icone']) ?>"></i>
            </span>
          </span>
        </div>
        <span class="absolute top-8 right-8 w-14 h-14 rounded-full bg-white/10 float-2"></span>
        <span class="absolute bottom-10 left-10 w-10 h-10 rounded-full bg-brand-500/30 float-3"></span>
        <span class="absolute bottom-8 right-14 w-6 h-6 rounded-full bg-success-500/50 float-1"></span>
      </div>
      <span class="absolute -bottom-5 <?= $alt ? '-left-5' : '-right-5' ?> bg-white rounded-2xl shadow-xl border border-gray-100 px-5 py-4 flex items-center gap-3">
        <span class="w-10 h-10 rounded-full bg-success-50 grid place-items-center text-success-600"><i class="fa-solid fa-comment-dots"></i></span>
        <span class="text-sm">
          <span class="block font-bold text-ink">Devis sous 1h</span>
          <span class="block text-ink/50 text-xs">Réponse rapide sur WhatsApp</span>
        </span>
      </span>
    </div>
  </div>
</section>
<?php endforeach; ?>

<!-- ============================================================
     APPEL À L'ACTION FINAL
============================================================ -->
<section class="py-20 bg-ink relative overflow-hidden">
  <div class="hero-pattern"></div>
  <div class="max-w-3xl mx-auto px-5 sm:px-8 text-center relative">
    <h2 class="font-display text-3xl sm:text-4xl text-white">Un projet en tête ?</h2>
    <p class="text-white/60 mt-4">Décrivez-nous votre besoin, nous revenons vers vous avec une proposition sur mesure.</p>
    <a href="<?= e(whatsapp_link("Bonjour, j'ai un projet à vous soumettre.")) ?>" target="_blank" rel="noopener"
       class="btn-cta mt-8 inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-4 rounded-full shadow-glow transition hover:-translate-y-0.5">
      <i class="fa-brands fa-whatsapp text-lg"></i> <span>Discuter de mon projet</span>
    </a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>