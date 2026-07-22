<?php
require_once __DIR__ . '/includes/functions.php';
$p = get_parametres();
$current = '';
$page_title = 'Page introuvable — ' . $p['nom_restaurant'];
http_response_code(404);
?>
<!doctype html>
<html lang="fr">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="antialiased">
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="hero-slide" style="min-height:100vh;">
  <div class="hero-pattern"></div>
  <div class="relative max-w-2xl mx-auto px-5 text-center py-32">
    <span class="font-display text-brand-600 text-8xl">404</span>
    <h1 class="font-display text-white text-3xl sm:text-4xl mt-4">Cette page a été mangée !</h1>
    <p class="text-white/60 mt-4 leading-relaxed">
      La page que vous cherchez n'existe pas ou plus. Retournez à l'accueil ou contactez-nous directement.
    </p>
    <div class="mt-9 flex flex-wrap justify-center gap-3">
      <a href="index.php" class="btn-cta inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-7 py-4 rounded-full transition">
        <i class="fa-solid fa-house"></i> <span>Retour à l'accueil</span>
      </a>
      <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" class="btn-cta inline-flex items-center gap-2 border-2 border-white/30 text-white hover:border-white font-bold px-7 py-4 rounded-full transition">
        <i class="fa-brands fa-whatsapp"></i> <span>Nous écrire</span>
      </a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
