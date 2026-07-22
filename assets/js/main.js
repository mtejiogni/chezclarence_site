/* ══════════════════════════════════════════════════════════════
   main.js — interactions du site Chez Clarence
   ══════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initPreloader();
    initHeaderScroll();
    initMobileMenu();
    initBackToTop();
    initChatbot();
    initSwiperHero();
    initAOS();
    initCategoryCards();
    initMenuFilters();
    initCounters();
    initContactForm();
    initChosen();
    initRevealTilt();
  });

  /* ── Préchargeur ─────────────────────────────────────────── */
  function initPreloader() {
    var pre = document.getElementById('preloader');
    if (!pre) return;
    window.addEventListener('load', function () {
      setTimeout(function () { pre.classList.add('loaded'); }, 350);
    });
    // Filet de sécurité si l'événement load tarde (images lourdes)
    setTimeout(function () { pre.classList.add('loaded'); }, 3500);
  }

  /* ── Header sticky ───────────────────────────────────────── */
  function initHeaderScroll() {
    var header = document.getElementById('site-header');
    if (!header) return;
    function onScroll() {
      header.classList.toggle('scrolled', window.scrollY > 30);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Menu mobile ─────────────────────────────────────────── */
  function initMobileMenu() {
    var burger = document.getElementById('btn-burger');
    var menu = document.getElementById('mobile-menu');
    if (!burger || !menu) return;

    function close() {
      burger.classList.remove('open');
      menu.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('overflow-hidden');
    }

    burger.addEventListener('click', function () {
      var willOpen = !menu.classList.contains('open');
      burger.classList.toggle('open', willOpen);
      menu.classList.toggle('open', willOpen);
      burger.setAttribute('aria-expanded', String(willOpen));
      document.body.classList.toggle('overflow-hidden', willOpen);
    });

    menu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', close);
    });
  }

  /* ── Retour en haut ──────────────────────────────────────── */
  function initBackToTop() {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', function () {
      btn.classList.toggle('show', window.scrollY > 500);
    }, { passive: true });
    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ── Chatbot WhatsApp ────────────────────────────────────── */
  function initChatbot() {
    var toggle = document.getElementById('chatbot-toggle');
    var panel = document.getElementById('chatbot-panel');
    var close = document.getElementById('chatbot-close');
    var pulse = document.getElementById('chatbot-pulse');
    if (!toggle || !panel) return;

    function open() {
      panel.classList.add('open');
      if (pulse) pulse.style.display = 'none';
    }
    function hide() { panel.classList.remove('open'); }

    toggle.addEventListener('click', function () {
      panel.classList.contains('open') ? hide() : open();
    });
    if (close) close.addEventListener('click', hide);

    document.addEventListener('click', function (e) {
      if (!panel.contains(e.target) && !toggle.contains(e.target)) hide();
    });

    // Ouvre automatiquement une fois après quelques secondes pour attirer l'attention
    var alreadyPrompted = sessionStorage.getItem('cc_chat_prompted');
    if (!alreadyPrompted) {
      setTimeout(function () {
        open();
        sessionStorage.setItem('cc_chat_prompted', '1');
      }, 9000);
    }
  }

  /* ── Swiper (hero) ───────────────────────────────────────── */
  function initSwiperHero() {
    var el = document.querySelector('.hero-swiper');
    if (!el || typeof Swiper === 'undefined') return;

    new Swiper('.hero-swiper', {
      loop: true,
      speed: 900,
      effect: 'fade',
      fadeEffect: { crossFade: true },
      autoplay: { delay: 5500, disableOnInteraction: false },
      pagination: { el: '.hero-swiper .swiper-pagination', clickable: true },
      navigation: {
        nextEl: '.hero-swiper .swiper-button-next',
        prevEl: '.hero-swiper .swiper-button-prev',
      },
    });
  }

  /* ── AOS (animations au scroll) ──────────────────────────── */
  function initAOS() {
    if (typeof AOS === 'undefined') return;
    AOS.init({ once: true, duration: 700, easing: 'ease-out-cubic', offset: 60 });
  }

  /* ── Cartes de catégorie (flip tactile) ──────────────────── */
  function initCategoryCards() {
    document.querySelectorAll('.cat-card').forEach(function (card) {
      card.addEventListener('click', function () {
        // Sur mobile (sans hover), un tap retourne la carte
        if (window.matchMedia('(hover: none)').matches) {
          card.classList.toggle('flipped');
        }
      });
    });
  }

  /* ── Filtres du menu (catégorie / étoiles / prix) ────────── */
  function initMenuFilters() {
    var grid = document.getElementById('menu-grid');
    if (!grid) return;

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.menu-card'));
    var catButtons = document.querySelectorAll('.menu-cat-btn');
    var starButtons = document.querySelectorAll('.menu-star-btn');
    var priceRange = document.getElementById('menu-price-range');
    var priceLabel = document.getElementById('menu-price-label');
    var emptyState = document.getElementById('menu-empty');
    var countLabel = document.getElementById('menu-count');

    var state = { categorie: 'Tous', etoiles: 0, prixMax: priceRange ? parseInt(priceRange.max, 10) : Infinity };

    function applyFilters() {
      var visible = 0;
      cards.forEach(function (card) {
        var cat = card.dataset.categorie;
        var etoiles = parseInt(card.dataset.etoiles, 10) || 0;
        var prix = parseFloat(card.dataset.prix) || 0;

        var match = (state.categorie === 'Tous' || cat === state.categorie)
          && (state.etoiles === 0 || etoiles >= state.etoiles)
          && (prix <= state.prixMax);

        card.style.display = match ? '' : 'none';
        if (match) visible++;
      });

      if (emptyState) emptyState.style.display = visible === 0 ? 'block' : 'none';
      if (countLabel) countLabel.textContent = visible;
    }

    catButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        catButtons.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        state.categorie = btn.dataset.categorie;
        applyFilters();
      });
    });

    starButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var val = parseInt(btn.dataset.stars, 10);
        state.etoiles = state.etoiles === val ? 0 : val;
        starButtons.forEach(function (b) {
          b.classList.toggle('is-active', parseInt(b.dataset.stars, 10) <= state.etoiles && state.etoiles !== 0);
        });
        applyFilters();
      });
    });

    if (priceRange) {
      priceRange.addEventListener('input', function () {
        state.prixMax = parseInt(priceRange.value, 10);
        if (priceLabel) {
          priceLabel.textContent = Number(state.prixMax).toLocaleString('fr-FR') + ' ' + (window.SITE ? window.SITE.devise : 'FCFA');
        }
        applyFilters();
      });
    }

    applyFilters();

    // Boutons "voir la carte" des catégories -> filtre + scroll
    document.querySelectorAll('[data-scroll-to-category]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cat = btn.getAttribute('data-scroll-to-category');
        var target = Array.prototype.slice.call(catButtons).find(function (b) { return b.dataset.categorie === cat; });
        if (target) target.click();
        var menuSection = document.getElementById('menu');
        if (menuSection) menuSection.scrollIntoView({ behavior: 'smooth' });
      });
    });
  }

  /* ── Compteurs animés ────────────────────────────────────── */
  function initCounters() {
    var counters = document.querySelectorAll('.counter-num');
    if (!counters.length || !('IntersectionObserver' in window)) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.4 });

    counters.forEach(function (el) { observer.observe(el); });
  }

  function animateCounter(el) {
    var target = parseFloat(el.dataset.target || el.textContent) || 0;
    var duration = 1600;
    var start = null;

    function step(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var value = Math.floor(eased * target);
      el.textContent = value.toLocaleString('fr-FR') + (el.dataset.suffix || '');
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target.toLocaleString('fr-FR') + (el.dataset.suffix || '');
    }
    requestAnimationFrame(step);
  }

  /* ── Formulaire de contact -> WhatsApp ───────────────────── */
  function initContactForm() {
    var form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var nom = form.nom.value.trim();
      var telephone = form.telephone.value.trim();
      var sujet = form.sujet.value;
      var message = form.message.value.trim();

      if (!nom || !telephone || !message) {
        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Champs manquants',
            text: 'Merci de renseigner votre nom, votre téléphone et votre message.',
            confirmButtonColor: '#EA580C',
          });
        } else {
          alert('Merci de renseigner votre nom, votre téléphone et votre message.');
        }
        return;
      }

      var numero = window.SITE ? window.SITE.whatsappNumero : '';
      var texte = 'Bonjour, je suis ' + nom + '.%0ASujet : ' + sujet + '.%0ATéléphone : ' + telephone + '.%0AMessage : ' + encodeURIComponent(message);
      var url = 'https://wa.me/' + numero + '?text=' + texte;

      if (window.Swal) {
        Swal.fire({
          icon: 'success',
          title: 'Message prêt !',
          text: 'Vous allez être redirigé vers WhatsApp pour envoyer votre message.',
          confirmButtonColor: '#EA580C',
          confirmButtonText: 'Continuer',
          timer: 2200,
        }).then(function () {
          window.open(url, '_blank');
        });
      } else {
        window.open(url, '_blank');
      }

      form.reset();
    });
  }

  /* ── Chosen (select stylé du formulaire de contact) ──────── */
  function initChosen() {
    if (typeof window.jQuery === 'undefined' || !jQuery.fn.chosen) return;
    jQuery('.js-chosen').chosen({
      disable_search_threshold: 10,
      width: '100%',
      no_results_text: 'Aucun résultat pour',
    });
  }

  /* ── Léger effet tilt 3D sur les cartes au survol (desktop) ─ */
  function initRevealTilt() {
    if (window.matchMedia('(hover: none)').matches) return;
    document.querySelectorAll('.tilt-card').forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        var rect = card.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width - 0.5;
        var y = (e.clientY - rect.top) / rect.height - 0.5;
        card.style.transform = 'perspective(800px) rotateY(' + (x * 8) + 'deg) rotateX(' + (y * -8) + 'deg) translateY(-4px)';
      });
      card.addEventListener('mouseleave', function () {
        card.style.transform = '';
      });
    });
  }
})();
