<?php
require_once __DIR__ . '/includes/functions.php';

$p          = get_parametres();
$categories = get_categories_with_menus();
$services   = get_services();
$current    = 'accueil';

$tousLesPrix = [];
foreach ($categories as $cat) {
    foreach ($cat['menus'] as $m) {
        $tousLesPrix[] = $m['prix'];
    }
}
$prixMax = $tousLesPrix ? (int) ceil(max($tousLesPrix) / 500) * 500 : 10000;
?>
<!doctype html>
<html lang="fr">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body class="antialiased">

<!-- Préchargeur -->
<div id="preloader">
  <div class="preloader-flame"><span></span><span></span></div>
  <p class="text-white/60 text-xs uppercase tracking-[0.3em] font-semibold">Chez Clarence</p>
</div>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- ============================================================
     HERO — SLIDER (Swiper)
============================================================ -->
<?php $heroSlides = get_hero_slides(); ?>
<section id="accueil" class="hero-swiper swiper">
  <div class="swiper-wrapper">
    <?php foreach ($heroSlides as $slide): ?>
    <div class="swiper-slide">
      <div class="hero-slide">
        <div class="hero-pattern"></div>
        <div class="relative max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-2 gap-14 items-center w-full py-10">
          <div>
            <?php if (!empty($slide['badge'])): ?>
              <span class="hero-badge"><i class="fa-solid fa-fire"></i> <?= e($slide['badge']) ?></span>
            <?php endif; ?>
            <h1 class="font-display text-white text-[clamp(2.3rem,6vw,4.8rem)] leading-[1] mt-6">
              <?= $slide['titre'] // contenu géré par l'admin, peut inclure un <br> volontaire ?>
            </h1>
            <?php if (!empty($slide['sous_titre'])): ?>
              <p class="text-brand-400 font-display text-xl sm:text-2xl mt-3 tracking-wide"><?= e($slide['sous_titre']) ?></p>
            <?php endif; ?>
            <?php if (!empty($slide['description'])): ?>
              <p class="text-white/70 text-base sm:text-lg mt-5 max-w-lg leading-relaxed"><?= e($slide['description']) ?></p>
            <?php endif; ?>

            <div class="mt-9 flex flex-wrap gap-3.5">
              <?php if (!empty($slide['bouton1_texte'])): ?>
                <a href="<?= e($slide['bouton1_lien']) ?>" <?= $slide['bouton1_type'] !== 'ancre' ? 'target="_blank" rel="noopener"' : '' ?>
                   class="btn-cta inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-7 py-4 rounded-full shadow-glow transition hover:-translate-y-0.5">
                  <?php if ($slide['bouton1_type'] === 'whatsapp'): ?><i class="fa-brands fa-whatsapp text-lg"></i><?php endif; ?>
                  <span><?= e($slide['bouton1_texte']) ?></span>
                </a>
              <?php endif; ?>
              <?php if (!empty($slide['bouton2_texte'])): ?>
                <a href="<?= e($slide['bouton2_lien']) ?>" <?= $slide['bouton2_type'] !== 'ancre' ? 'target="_blank" rel="noopener"' : '' ?>
                   class="btn-cta inline-flex items-center gap-2 border-2 border-white/30 text-white hover:border-white font-bold px-7 py-4 rounded-full transition">
                  <span><?= e($slide['bouton2_texte']) ?></span>
                </a>
              <?php endif; ?>
            </div>
          </div>
          <div class="relative flex justify-center">
            <?php if ($slide['illustration'] === 'personnalisee' && !empty($slide['image'])): ?>
              <img src="<?= e(photo_url($slide['image'])) ?>" alt="" class="w-full max-w-md rounded-3xl shadow-2xl">
            <?php else: ?>
              <?php include __DIR__ . '/includes/svg/hero-' . basename($slide['illustration']) . '.php'; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (!$heroSlides): ?>
    <div class="swiper-slide">
      <div class="hero-slide">
        <div class="hero-pattern"></div>
        <div class="relative max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-2 gap-14 items-center w-full py-10">
          <div>
            <h1 class="font-display text-white text-[clamp(2.3rem,6vw,4.8rem)] leading-[1] mt-6"><?= e($p['nom_restaurant']) ?></h1>
            <p class="text-white/70 text-base sm:text-lg mt-5 max-w-lg leading-relaxed"><?= e($p['description']) ?></p>
            <div class="mt-9 flex flex-wrap gap-3.5">
              <a href="<?= e(whatsapp_link('Bonjour, je souhaite passer une commande.')) ?>" target="_blank" rel="noopener"
                 class="btn-cta inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-7 py-4 rounded-full shadow-glow transition">
                <i class="fa-brands fa-whatsapp text-lg"></i> <span>Commander maintenant</span>
              </a>
            </div>
          </div>
          <div class="relative flex justify-center"><?php include __DIR__ . '/includes/svg/hero-plate.php'; ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="swiper-pagination"></div>
  <div class="swiper-button-next"></div>
  <div class="swiper-button-prev"></div>
</section>

<!-- ============================================================
     MARQUEE — bandeau défilant
============================================================ -->
<div class="bg-brand-600 text-white py-3 overflow-hidden">
  <div class="marquee-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <div class="marquee-item"><i class="fa-solid fa-fire"></i> Grillades maison</div>
      <div class="marquee-item"><i class="fa-solid fa-motorcycle"></i> Livraison rapide</div>
      <div class="marquee-item"><i class="fa-solid fa-star"></i> Note moyenne excellente</div>
      <div class="marquee-item"><i class="fa-solid fa-calendar-check"></i> Réservation en un message</div>
      <div class="marquee-item"><i class="fa-solid fa-champagne-glasses"></i> Service traiteur disponible</div>
      <div class="marquee-item"><i class="fa-solid fa-clock"></i> <?= e($p['horaires']) ?></div>
    <?php endfor; ?>
  </div>
</div>

<!-- ============================================================
     À PROPOS
============================================================ -->
<section id="apropos" class="py-24 sm:py-32 bg-white overflow-hidden">
  <div class="max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-2 gap-16 items-center">

    <div class="relative flex justify-center" data-aos="fade-right">
      <?php include __DIR__ . '/includes/svg/about-chef.php'; ?>
    </div>

    <div data-aos="fade-left">
      <span class="text-xs font-bold uppercase tracking-[0.25em] text-brand-600"><?= e($p['entreprise'] ?: 'Notre maison') ?></span>
      <h2 class="font-display text-4xl sm:text-5xl text-ink mt-3 leading-tight">
        Une cuisine préparée<br>avec cœur, depuis <?= e($p['ville']) ?>
      </h2>
      <p class="text-ink/70 text-base sm:text-lg mt-6 leading-relaxed">
        <?= nl2br(e($p['description'])) ?>
      </p>

      <div class="mt-10 grid sm:grid-cols-2 gap-4">
        <?php foreach (get_valeurs() as $i => $v): ?>
          <div class="flex items-start gap-3.5 bg-brand-50/60 border border-brand-100 rounded-2xl p-4" data-aos="zoom-in" data-aos-delay="<?= $i * 80 ?>">
            <span class="shrink-0 w-11 h-11 rounded-xl bg-ink text-brand-500 grid place-items-center text-lg">
              <i class="fa-solid <?= e($v['icone']) ?>"></i>
            </span>
            <div>
              <p class="font-bold text-ink"><?= e($v['titre']) ?></p>
              <p class="text-sm text-ink/60 mt-0.5"><?= e($v['texte']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-10 flex flex-wrap gap-3.5">
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite passer une commande.')) ?>" target="_blank" rel="noopener"
           class="btn-cta inline-flex items-center gap-2 bg-ink hover:bg-ink-soft text-white font-bold px-6 py-3.5 rounded-full transition">
          <i class="fa-brands fa-whatsapp"></i> <span>Commander</span>
        </a>
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite réserver une table.')) ?>" target="_blank" rel="noopener"
           class="btn-cta inline-flex items-center gap-2 border-2 border-ink/15 text-ink hover:border-ink font-bold px-6 py-3.5 rounded-full transition">
          <span>Réserver</span>
        </a>
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite en savoir plus sur le service traiteur.')) ?>" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 text-brand-600 hover:text-brand-700 font-bold px-3 py-3.5">
          <span>Service traiteur →</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     STATISTIQUES — compteurs animés
============================================================ -->
<section class="bg-ink py-16 relative overflow-hidden">
  <div class="hero-pattern"></div>
  <div class="max-w-7xl mx-auto px-5 sm:px-8 relative grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
    <?php foreach (get_stats() as $i => $s): ?>
      <div data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
        <i class="fa-solid <?= e($s['icone']) ?> text-brand-500 text-2xl mb-3"></i>
        <div class="font-display text-4xl sm:text-5xl text-white counter-num" data-target="<?= (int) $s['valeur'] ?>" data-suffix="<?= e($s['suffixe']) ?>">0</div>
        <p class="text-white/50 text-sm mt-1 font-medium"><?= e($s['label']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============================================================
     MENU — catégories interactives + grille filtrable
============================================================ -->
<section id="menu" class="py-24 sm:py-32 bg-gray-50">
  <div class="max-w-7xl mx-auto px-5 sm:px-8">
    <div class="text-center max-w-2xl mx-auto" data-aos="fade-up">
      <span class="text-xs font-bold uppercase tracking-[0.25em] text-brand-600">Notre carte</span>
      <h2 class="font-display text-4xl sm:text-5xl text-ink mt-3">Explorez nos catégories</h2>
      <p class="text-ink/60 mt-4">Survolez une catégorie pour la découvrir, puis filtrez la carte complète par note ou par budget.</p>
    </div>

    <?php if ($categories): ?>
    <!-- Cartes de catégories (flip 3D) -->
    <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($categories as $i => $cat): ?>
        <div class="cat-card" data-aos="zoom-in" data-aos-delay="<?= $i * 80 ?>" data-scroll-to-category="<?= e($cat['intitule']) ?>">
          <div class="cat-card-inner">
            <div class="cat-face cat-face-front">
              <?php if (!empty($cat['photo'])): ?>
                <img src="<?= e(photo_url($cat['photo'])) ?>" alt="<?= e($cat['intitule']) ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-ink/85 via-ink/10 to-transparent"></div>
              <?php else: ?>
                <div class="w-full h-full bg-gradient-to-br from-ink to-ink-soft flex items-center justify-center">
                  <?php include __DIR__ . '/includes/svg/category-icon.php'; ?>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-ink/85 via-transparent to-transparent"></div>
              <?php endif; ?>
              <div class="absolute inset-x-0 bottom-0 p-5">
                <p class="font-display text-white text-xl"><?= e($cat['intitule']) ?></p>
                <p class="text-white/60 text-xs mt-1"><?= count($cat['menus']) ?> plat<?= count($cat['menus']) > 1 ? 's' : '' ?></p>
              </div>
            </div>
            <div class="cat-face cat-face-back bg-brand-600 text-white p-6 flex flex-col justify-center items-center text-center">
              <i class="fa-solid fa-utensils text-3xl mb-3"></i>
              <p class="font-display text-lg mb-1"><?= e($cat['intitule']) ?></p>
              <p class="text-white/85 text-xs leading-relaxed line-clamp-3"><?= e($cat['description'] ?: 'Découvrez notre sélection.') ?></p>
              <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide bg-white/15 rounded-full px-4 py-2">
                Voir les plats <i class="fa-solid fa-arrow-right"></i>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Filtres -->
    <div class="mt-16 flex flex-col gap-5" data-aos="fade-up">
      <div class="flex flex-wrap justify-center gap-2.5">
        <button class="menu-cat-btn is-active px-4 py-2 rounded-full text-sm font-bold border-2 border-ink bg-ink text-white transition" data-categorie="Tous">Tous les plats</button>
        <?php foreach ($categories as $cat): ?>
          <button class="menu-cat-btn px-4 py-2 rounded-full text-sm font-bold border-2 border-gray-200 text-ink/70 hover:border-ink transition" data-categorie="<?= e($cat['intitule']) ?>"><?= e($cat['intitule']) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-6 sm:gap-10 bg-white border border-gray-200 rounded-2xl px-6 py-5 shadow-sm">
        <div class="flex items-center gap-2">
          <span class="text-ink/60 text-sm font-semibold mr-1">Note minimum :</span>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button class="menu-star-btn p-0.5" data-stars="<?= $i ?>" aria-label="<?= $i ?> étoiles minimum">
              <svg viewBox="0 0 24 24" class="w-5 h-5 star-empty"><path d="M12 2l2.9 6.26L21.5 9l-5 4.64L17.8 21 12 17.3 6.2 21l1.3-7.36-5-4.64 6.6-.74z"/></svg>
            </button>
          <?php endfor; ?>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-72">
          <span class="text-ink/60 text-sm font-semibold whitespace-nowrap">Prix max :</span>
          <input type="range" id="menu-price-range" min="0" max="<?= $prixMax ?>" step="100" value="<?= $prixMax ?>" class="w-full accent-brand-600">
          <span id="menu-price-label" class="text-brand-600 text-sm font-bold whitespace-nowrap"><?= number_format($prixMax, 0, ',', ' ') ?> <?= e($p['devise']) ?></span>
        </div>
      </div>
    </div>

    <!-- Grille des plats -->
    <div id="menu-grid" class="mt-14 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($categories as $cat): foreach ($cat['menus'] as $menu): ?>
        <article class="menu-card bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col"
                 data-categorie="<?= e($cat['intitule']) ?>" data-etoiles="<?= (int) $menu['etoiles'] ?>" data-prix="<?= (float) $menu['prix'] ?>"
                 data-aos="fade-up">
          <div class="h-44 bg-gray-100 relative overflow-hidden">
            <?php if (!empty($menu['photo'])): ?>
              <img src="<?= e(photo_url($menu['photo'])) ?>" alt="<?= e($menu['intitule']) ?>" class="menu-card-img w-full h-full object-cover">
            <?php else: ?>
              <div class="menu-card-img w-full h-full grid place-items-center bg-gradient-to-br from-brand-50 to-brand-100">
                <i class="fa-solid fa-utensils text-brand-400 text-3xl"></i>
              </div>
            <?php endif; ?>
            <span class="absolute top-3 left-3 bg-white/95 text-brand-700 text-[11px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full border border-brand-100">
              <?= e($cat['intitule']) ?>
            </span>
          </div>
          <div class="p-5 flex flex-col grow">
            <div class="flex items-start justify-between gap-3">
              <h3 class="font-bold text-base text-ink leading-snug"><?= e($menu['intitule']) ?></h3>
              <span class="text-brand-600 font-extrabold whitespace-nowrap"><?= number_format($menu['prix'], 0, ',', ' ') ?> <?= e($p['devise']) ?></span>
            </div>
            <p class="mt-1.5 text-sm text-ink/55 leading-relaxed grow"><?= e($menu['description'] ?: 'Une spécialité préparée maison.') ?></p>
            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
              <div class="flex items-center gap-0.5">
                <?php for ($n = 1; $n <= 5; $n++): ?>
                  <svg viewBox="0 0 24 24" class="w-4 h-4 <?= $n <= $menu['etoiles'] ? 'star-fill' : 'star-empty' ?>"><path d="M12 2l2.9 6.26L21.5 9l-5 4.64L17.8 21 12 17.3 6.2 21l1.3-7.36-5-4.64 6.6-.74z"/></svg>
                <?php endfor; ?>
              </div>
              <a href="<?= e(whatsapp_link('Bonjour, je souhaite commander : ' . $menu['intitule'] . ' — ' . number_format($menu['prix'], 0, ',', ' ') . ' ' . $p['devise'])) ?>" target="_blank" rel="noopener"
                 class="inline-flex items-center gap-1.5 bg-ink hover:bg-brand-600 text-white text-sm font-bold px-4 py-2 rounded-full transition">
                <i class="fa-brands fa-whatsapp"></i> Commander
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; endforeach; ?>
    </div>
    <p id="menu-empty" class="text-center text-ink/50 py-16" style="display:none;">Aucun plat ne correspond à ces filtres pour le moment.</p>
    <p class="text-center text-ink/40 text-sm mt-6"><span id="menu-count"><?= array_sum(array_map(fn($c) => count($c['menus']), $categories)) ?></span> plat(s) affiché(s)</p>

    <?php else: ?>
      <div class="mt-16 text-center bg-white border border-gray-200 rounded-2xl p-14">
        <i class="fa-solid fa-utensils text-4xl text-brand-300 mb-4"></i>
        <p class="text-ink/60">La carte sera bientôt disponible en ligne. Contactez-nous directement sur WhatsApp pour la découvrir !</p>
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite connaître votre carte.')) ?>" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 bg-brand-600 text-white font-bold px-6 py-3 rounded-full">
          <i class="fa-brands fa-whatsapp"></i> Demander la carte
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============================================================
     APERÇU DES SERVICES
============================================================ -->
<section id="services-apercu" class="py-24 sm:py-32 bg-white">
  <div class="max-w-7xl mx-auto px-5 sm:px-8">
    <div class="text-center max-w-2xl mx-auto" data-aos="fade-up">
      <span class="text-xs font-bold uppercase tracking-[0.25em] text-brand-600">Au-delà de l'assiette</span>
      <h2 class="font-display text-4xl sm:text-5xl text-ink mt-3">Nos services</h2>
      <p class="text-ink/60 mt-4">Pour vos événements, vos cadeaux ou vos repas d'entreprise, nous nous adaptons à vos envies.</p>
    </div>

    <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($services as $i => $s): ?>
        <div class="service-card tilt-card bg-white rounded-2xl p-7 border border-gray-200 shadow-sm" data-aos="fade-up" data-aos-delay="<?= $i * 70 ?>">
          <span class="service-icon inline-grid w-14 h-14 place-items-center rounded-2xl bg-brand-50 text-brand-600 text-xl">
            <i class="fa-solid <?= e($s['icone']) ?>"></i>
          </span>
          <h3 class="font-display text-xl text-ink mt-5"><?= e($s['titre']) ?></h3>
          <p class="text-sm text-ink/55 mt-2 leading-relaxed"><?= e($s['resume']) ?></p>
          <a href="services.php#<?= e($s['slug']) ?>" class="mt-5 inline-flex items-center gap-1.5 text-brand-600 font-bold text-sm hover:text-brand-700">
            En savoir plus <i class="fa-solid fa-arrow-right text-xs"></i>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-12" data-aos="fade-up">
      <a href="services.php" class="btn-cta inline-flex items-center gap-2 bg-ink hover:bg-brand-600 text-white font-bold px-7 py-4 rounded-full transition">
        <i class="fa-solid fa-concierge-bell"></i> <span>Découvrir tous nos services</span>
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     LOCALISATION
============================================================ -->
<section id="localisation" class="py-24 sm:py-32 bg-gray-50">
  <div class="max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-2 gap-12 items-stretch">
    <div class="flex flex-col justify-center" data-aos="fade-right">
      <span class="text-xs font-bold uppercase tracking-[0.25em] text-brand-600">Nous trouver</span>
      <h2 class="font-display text-4xl sm:text-5xl text-ink mt-3">Passez nous voir</h2>
      <p class="text-ink/60 mt-5 leading-relaxed text-lg"><?= e($p['adresse']) ?></p>

      <ul class="mt-6 space-y-3 text-ink/75 text-sm">
        <?php if (!empty($p['horaires'])): ?>
          <li class="flex items-center gap-3"><i class="fa-solid fa-clock text-brand-600 w-5"></i> <?= e($p['horaires']) ?></li>
        <?php endif; ?>
        <?php if (!empty($p['telephone'])): ?>
          <li class="flex items-center gap-3"><i class="fa-solid fa-phone text-brand-600 w-5"></i> <?= e($p['telephone']) ?><?= $p['telephone2'] ? ' · ' . e($p['telephone2']) : '' ?></li>
        <?php endif; ?>
      </ul>

      <div class="mt-9 flex flex-wrap gap-3.5">
        <a href="<?= e(itineraire_link()) ?>" target="_blank" rel="noopener"
           class="btn-cta inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-7 py-4 rounded-full shadow-glow transition">
          <i class="fa-solid fa-location-arrow"></i> <span>Lancer l'itinéraire GPS</span>
        </a>
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite réserver une table.')) ?>" target="_blank" rel="noopener"
           class="btn-cta inline-flex items-center gap-2 border-2 border-ink/15 text-ink hover:border-ink font-bold px-7 py-4 rounded-full transition">
          <span>Réserver une table</span>
        </a>
      </div>
    </div>

    <div class="rounded-2xl overflow-hidden min-h-[24rem] shadow-xl border border-gray-200" data-aos="fade-left">
      <?php if (!empty($p['latitude']) && !empty($p['longitude'])): ?>
        <iframe class="w-full h-full min-h-[24rem]" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=<?= e($p['latitude']) ?>,<?= e($p['longitude']) ?>&z=16&output=embed"></iframe>
      <?php else: ?>
        <div class="w-full h-full min-h-[24rem] bg-white grid place-items-center text-ink/40 text-sm">Localisation à venir</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     CONTACT
============================================================ -->
<section id="contact" class="py-24 sm:py-32 bg-white">
  <div class="max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-5 gap-12">
    <div class="lg:col-span-2" data-aos="fade-right">
      <span class="text-xs font-bold uppercase tracking-[0.25em] text-brand-600">Contact</span>
      <h2 class="font-display text-4xl text-ink mt-3">Parlons de votre prochain repas</h2>
      <p class="text-ink/60 mt-4 leading-relaxed">Une question, une envie particulière ? Écrivez-nous, nous répondons rapidement sur WhatsApp.</p>

      <div class="mt-8 space-y-4 text-sm">
        <?php if (!empty($p['adresse'])): ?><div class="flex gap-3"><i class="fa-solid fa-location-dot text-brand-600 mt-0.5"></i><span><?= e($p['adresse']) ?></span></div><?php endif; ?>
        <?php if (!empty($p['telephone'])): ?><div class="flex gap-3"><i class="fa-solid fa-phone text-brand-600 mt-0.5"></i><span><?= e($p['telephone']) ?></span></div><?php endif; ?>
        <?php if (!empty($p['email'])): ?><div class="flex gap-3"><i class="fa-solid fa-envelope text-brand-600 mt-0.5"></i><span><?= e($p['email']) ?></span></div><?php endif; ?>
        <?php if (!empty($p['horaires'])): ?><div class="flex gap-3"><i class="fa-solid fa-clock text-brand-600 mt-0.5"></i><span><?= e($p['horaires']) ?></span></div><?php endif; ?>
      </div>
    </div>

    <div class="lg:col-span-3" data-aos="fade-left">
      <form id="contact-form" class="bg-gray-50 rounded-2xl p-7 sm:p-8 border border-gray-200 space-y-5">
        <div class="grid sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-bold text-ink mb-1.5">Nom</label>
            <input type="text" name="nom" required placeholder="Votre nom" class="form-field">
          </div>
          <div>
            <label class="block text-sm font-bold text-ink mb-1.5">Téléphone</label>
            <input type="tel" name="telephone" required placeholder="+237 6xx xx xx xx" class="form-field">
          </div>
        </div>
        <div>
          <label class="block text-sm font-bold text-ink mb-1.5">Sujet</label>
          <select name="sujet" class="js-chosen">
            <option>Commande</option>
            <option>Réservation de table</option>
            <option>Service traiteur</option>
            <option>Privatisation</option>
            <option>Autre demande</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-ink mb-1.5">Message</label>
          <textarea name="message" required rows="4" placeholder="Votre message..." class="form-field"></textarea>
        </div>
        <button type="submit" class="btn-cta w-full inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-4 rounded-xl transition">
          <i class="fa-brands fa-whatsapp"></i> <span>Envoyer sur WhatsApp</span>
        </button>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>