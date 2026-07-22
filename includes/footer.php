<?php /** includes/footer.php — attend $p (parametres) déjà chargé. */ ?>
<footer class="bg-ink text-white/70 pt-20 pb-8 relative overflow-hidden">
  <div class="absolute inset-0 opacity-[0.04] bg-flame-pattern pointer-events-none"></div>

  <div class="max-w-7xl mx-auto px-5 sm:px-8 relative">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 pb-14 border-b border-white/10">
      <div>
        <div class="flex items-center gap-3 mb-4">
          <?php if (!empty($p['logo'])): ?>
            <img src="<?= e(photo_url($p['logo'])) ?>" alt="" class="w-11 h-11 rounded-xl object-cover ring-2 ring-brand-600/50">
          <?php endif; ?>
          <span class="font-display text-2xl text-white tracking-wide"><?= e($p['nom_restaurant']) ?></span>
        </div>
        <p class="text-sm leading-relaxed text-white/50">
          <?= e(mb_strimwidth($p['description'] ?? '', 0, 150, '…')) ?>
        </p>
        <div class="mt-5 flex gap-3">
          <a href="#" class="footer-social" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" class="footer-social" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="footer-social" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
          <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" class="footer-social footer-social-whatsapp" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>

      <div>
        <p class="font-bold text-white mb-4">Liens rapides</p>
        <ul class="space-y-2.5 text-sm">
          <li><a href="index.php#apropos" class="footer-link">Le restaurant</a></li>
          <li><a href="index.php#menu" class="footer-link">Notre carte</a></li>
          <li><a href="services.php" class="footer-link">Nos services</a></li>
          <li><a href="index.php#localisation" class="footer-link">Nous trouver</a></li>
          <li><a href="index.php#contact" class="footer-link">Contact</a></li>
        </ul>
      </div>

      <div>
        <p class="font-bold text-white mb-4">Nos services</p>
        <ul class="space-y-2.5 text-sm">
          <?php foreach (array_slice(get_services(), 0, 5) as $s): ?>
            <li><a href="services.php#<?= e($s['slug']) ?>" class="footer-link"><?= e($s['titre']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <p class="font-bold text-white mb-4">Contact</p>
        <ul class="space-y-3 text-sm text-white/50">
          <?php if (!empty($p['adresse'])): ?><li class="flex gap-2.5"><i class="fa-solid fa-location-dot mt-1 text-brand-500"></i><span><?= e($p['adresse']) ?></span></li><?php endif; ?>
          <?php if (!empty($p['telephone'])): ?><li class="flex gap-2.5"><i class="fa-solid fa-phone mt-1 text-brand-500"></i><span><?= e($p['telephone']) ?></span></li><?php endif; ?>
          <?php if (!empty($p['email'])): ?><li class="flex gap-2.5"><i class="fa-solid fa-envelope mt-1 text-brand-500"></i><span><?= e($p['email']) ?></span></li><?php endif; ?>
          <?php if (!empty($p['horaires'])): ?><li class="flex gap-2.5"><i class="fa-solid fa-clock mt-1 text-brand-500"></i><span><?= e($p['horaires']) ?></span></li><?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="pt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-white/40">
      <span>© <?= date('Y') ?> <?= e($p['nom_restaurant']) ?>. Tous droits réservés.</span>
      <span><?= e($p['mention_legale'] ?: 'Site conçu avec ❤️ pour les amoureux de bonne cuisine.') ?></span>
    </div>
  </div>
</footer>

<?php include __DIR__ . '/chatbot.php'; ?>

<!-- jQuery (npm : jquery) -->
<script src="assets/vendor/jquery/jquery.min.js"></script>
<!-- Chosen (npm : chosen-js) -->
<script src="assets/vendor/chosen/chosen.jquery.min.js"></script>
<!-- SweetAlert2 (npm : sweetalert2) -->
<script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<!-- AOS (npm : aos) -->
<script src="assets/vendor/aos/aos.js"></script>
<!-- Swiper (npm : swiper) -->
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

<!-- Données PHP exposées au JavaScript -->
<script>
  window.SITE = {
    whatsappNumero: <?= json_encode(clean_phone($p['whatsapp'] ?: $p['telephone'])) ?>,
    devise: <?= json_encode($p['devise'] ?: 'FCFA') ?>,
    nom: <?= json_encode($p['nom_restaurant']) ?>
  };
</script>

<!-- Script principal du site -->
<script src="assets/js/main.js"></script>