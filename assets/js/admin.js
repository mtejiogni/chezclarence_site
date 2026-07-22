/* ══════════════════════════════════════════════════════════════
   admin/assets/js/admin.js — interactions du panneau d'administration
   ══════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* Bibliothèque d'icônes proposées dans le sélecteur — un sous-
     ensemble curaté de Font Awesome 6 (style solid), pertinent pour
     un restaurant : cuisine, service, événements, valeurs, chiffres. */
  var ICON_LIBRARY = [
    ['fa-utensils', 'Couverts'], ['fa-bowl-food', 'Bol de plat'], ['fa-fire', 'Feu'],
    ['fa-fire-burner', 'Grill / braise'], ['fa-leaf', 'Feuille, fraîcheur'], ['fa-mug-hot', 'Boisson chaude'],
    ['fa-mug-saucer', 'Café'], ['fa-wine-glass', 'Verre à vin'], ['fa-champagne-glasses', 'Trinquer'],
    ['fa-martini-glass', 'Cocktail'], ['fa-martini-glass-citrus', 'Cocktail citron'], ['fa-wine-bottle', 'Bouteille de vin'],
    ['fa-bottle-water', 'Bouteille d\'eau'], ['fa-pizza-slice', 'Pizza'], ['fa-burger', 'Burger'],
    ['fa-bread-slice', 'Pain'], ['fa-ice-cream', 'Glace'], ['fa-fish', 'Poisson'],
    ['fa-drumstick-bite', 'Poulet / viande'], ['fa-bacon', 'Bacon'], ['fa-egg', 'Œuf'],
    ['fa-cheese', 'Fromage'], ['fa-apple-whole', 'Fruit'], ['fa-lemon', 'Citron'],
    ['fa-carrot', 'Légume'], ['fa-pepper-hot', 'Épicé'], ['fa-cookie', 'Cookie'],
    ['fa-cookie-bite', 'Dessert'], ['fa-candy-cane', 'Sucrerie'], ['fa-blender', 'Mixeur'],
    ['fa-kitchen-set', 'Cuisine équipée'],
    ['fa-key', 'Clé / privatisation'], ['fa-gift', 'Cadeau'], ['fa-briefcase', 'Entreprise'],
    ['fa-motorcycle', 'Livraison'], ['fa-truck-fast', 'Livraison rapide'], ['fa-calendar-check', 'Réservation'],
    ['fa-calendar-days', 'Calendrier'], ['fa-concierge-bell', 'Service'], ['fa-handshake', 'Partenariat'],
    ['fa-users', 'Groupe de personnes'], ['fa-user-group', 'Équipe'], ['fa-people-group', 'Communauté'],
    ['fa-store', 'Boutique / restaurant'], ['fa-house', 'Maison'], ['fa-location-dot', 'Localisation'],
    ['fa-map-location-dot', 'Carte'], ['fa-clock', 'Horaire'], ['fa-bell', 'Notification'],
    ['fa-phone', 'Téléphone'], ['fa-envelope', 'E-mail'], ['fa-comments', 'Discussion'],
    ['fa-comment-dots', 'Message'], ['fa-headset', 'Support client'], ['fa-box', 'Colis'],
    ['fa-boxes-stacked', 'Stock'], ['fa-receipt', 'Reçu'], ['fa-cash-register', 'Caisse'],
    ['fa-credit-card', 'Paiement'], ['fa-money-bill-wave', 'Argent'], ['fa-wallet', 'Portefeuille'],
    ['fa-ticket', 'Ticket'], ['fa-tags', 'Promotion'], ['fa-percent', 'Réduction'],
    ['fa-champagne-glasses', 'Événement festif'],
    ['fa-star', 'Étoile'], ['fa-award', 'Récompense'], ['fa-trophy', 'Trophée'],
    ['fa-medal', 'Médaille'], ['fa-thumbs-up', 'Approbation'], ['fa-heart', 'Cœur'],
    ['fa-shield-halved', 'Sécurité, garantie'], ['fa-gem', 'Qualité premium'], ['fa-crown', 'Excellence'],
    ['fa-bolt', 'Rapidité'], ['fa-circle-check', 'Validé'], ['fa-check', 'Coche'],
    ['fa-hand-holding-heart', 'Bienveillance'], ['fa-face-smile', 'Satisfaction'], ['fa-sun', 'Soleil'],
    ['fa-seedling', 'Origine locale'], ['fa-recycle', 'Écoresponsable'], ['fa-earth-africa', 'International'],
    ['fa-droplet', 'Fraîcheur, eau'], ['fa-hourglass-half', 'Attente'], ['fa-stopwatch', 'Chronomètre'],
    ['fa-chart-simple', 'Statistique'], ['fa-chart-line', 'Croissance'], ['fa-chart-column', 'Graphique'],
    ['fa-globe', 'Monde'], ['fa-flag', 'Repère'], ['fa-cake-candles', 'Anniversaire'],
    ['fa-wifi', 'Wi-Fi'], ['fa-parking', 'Parking'], ['fa-umbrella-beach', 'Terrasse'],
    ['fa-music', 'Musique'], ['fa-camera', 'Photo'], ['fa-image', 'Image'],
    ['fa-video', 'Vidéo'], ['fa-microphone', 'Animation'], ['fa-volume-high', 'Son'],
    ['fa-palette', 'Décoration'], ['fa-broom', 'Propreté'], ['fa-spray-can-sparkles', 'Hygiène'],
    ['fa-hand-sparkles', 'Propreté des mains'], ['fa-shirt', 'Tenue du personnel'],
  ];

  document.addEventListener('DOMContentLoaded', function () {
    initTableFilters();
    initSortableTables();
    initImagePreviews();
    initSlugPreview();
    initPasswordToggles();
    initCharCounters();
    initIconPickers();
  });

  /* ── Recherche + filtre statut en direct sur les tableaux ──────
     Structure attendue :
     <div data-table-filter>
       <input data-filter-search>
       <select data-filter-status>...</select>
       <span data-filter-count></span>
       <table><tbody> <tr data-row data-statut="Activé"> ... </tr> </tbody></table>
       <p data-filter-empty style="display:none">Aucun résultat</p>
     </div>
  ──────────────────────────────────────────────────────────── */
  function initTableFilters() {
    document.querySelectorAll('[data-table-filter]').forEach(function (container) {
      var search = container.querySelector('[data-filter-search]');
      var status = container.querySelector('[data-filter-status]');
      var countEl = container.querySelector('[data-filter-count]');
      var emptyEl = container.querySelector('[data-filter-empty]');
      var rows = Array.prototype.slice.call(container.querySelectorAll('[data-row]'));

      if (!rows.length) return;

      function apply() {
        var term = (search ? search.value : '').trim().toLowerCase();
        var wantedStatus = status ? status.value : '';
        var visible = 0;

        rows.forEach(function (row) {
          var text = row.dataset.search || row.textContent;
          var matchesText = !term || text.toLowerCase().indexOf(term) !== -1;
          var matchesStatus = !wantedStatus || row.dataset.statut === wantedStatus;
          var show = matchesText && matchesStatus;
          row.style.display = show ? '' : 'none';
          if (show) visible++;
        });

        if (countEl) countEl.textContent = visible;
        if (emptyEl) emptyEl.style.display = visible === 0 ? '' : 'none';
      }

      if (search) search.addEventListener('input', apply);
      if (status) status.addEventListener('change', apply);
      apply();
    });
  }

  /* ── Tri de tableau au clic sur l'en-tête ──────────────────────
     <th data-sort="text|number">Colonne</th>
     La cellule <td> correspondante peut porter data-sort-value
     pour un tri basé sur une autre valeur que le texte affiché.
  ──────────────────────────────────────────────────────────── */
  function initSortableTables() {
    document.querySelectorAll('table').forEach(function (table) {
      var headers = table.querySelectorAll('th[data-sort]');
      if (!headers.length) return;

      var tbody = table.querySelector('tbody');

      headers.forEach(function (th, index) {
        th.classList.add('th-sortable');
        var icon = document.createElement('i');
        icon.className = 'fa-solid fa-sort ml-1.5 text-[10px] opacity-40';
        th.appendChild(icon);

        th.addEventListener('click', function () {
          var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-row]'));
          if (!rows.length) return;

          var ascending = th.dataset.sortDir !== 'asc';
          headers.forEach(function (h) {
            delete h.dataset.sortDir;
            var i = h.querySelector('i');
            if (i) i.className = 'fa-solid fa-sort ml-1.5 text-[10px] opacity-40';
          });
          th.dataset.sortDir = ascending ? 'asc' : 'desc';
          icon.className = 'fa-solid ' + (ascending ? 'fa-sort-up' : 'fa-sort-down') + ' ml-1.5 text-[10px] text-brand-600';

          var type = th.dataset.sort;
          rows.sort(function (a, b) {
            var cellA = a.children[index];
            var cellB = b.children[index];
            var valA = (cellA.dataset.sortValue || cellA.textContent || '').trim();
            var valB = (cellB.dataset.sortValue || cellB.textContent || '').trim();

            if (type === 'number') {
              valA = parseFloat(valA.replace(/[^\d.-]/g, '')) || 0;
              valB = parseFloat(valB.replace(/[^\d.-]/g, '')) || 0;
              return ascending ? valA - valB : valB - valA;
            }
            valA = valA.toLowerCase();
            valB = valB.toLowerCase();
            if (valA < valB) return ascending ? -1 : 1;
            if (valA > valB) return ascending ? 1 : -1;
            return 0;
          });

          rows.forEach(function (row) { tbody.appendChild(row); });
        });
      });
    });
  }

  /* ── Aperçu d'image avant envoi ─────────────────────────────── */
  function initImagePreviews() {
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
      var targetSelector = input.getAttribute('data-preview');
      var target = document.querySelector(targetSelector);
      if (!target) return;

      input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;

        if (!file.type.match(/^image\/(png|jpeg|jpg|webp)$/)) {
          if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Format non supporté', text: 'Utilisez une image JPG, PNG ou WEBP.', confirmButtonColor: '#EA580C' });
          }
          input.value = '';
          return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
          if (target.tagName === 'IMG') {
            target.src = e.target.result;
            target.classList.remove('hidden');
          }
          var badge = document.querySelector(targetSelector + '-badge');
          if (badge) { badge.textContent = 'Nouvelle image sélectionnée'; badge.classList.remove('hidden'); }
        };
        reader.readAsDataURL(file);
      });
    });
  }

  /* ── Aperçu du slug généré en direct (formulaire service) ─────── */
  function initSlugPreview() {
    var source = document.querySelector('[data-slug-source]');
    var preview = document.querySelector('[data-slug-preview]');
    var manualField = document.querySelector('[data-slug-field]');
    if (!source || !preview) return;

    function slugify(str) {
      return str
        .toString()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'votre-service';
    }

    function update() {
      var manual = manualField ? manualField.value.trim() : '';
      preview.textContent = manual ? slugify(manual) : slugify(source.value);
    }

    source.addEventListener('input', update);
    if (manualField) manualField.addEventListener('input', update);
    update();
  }

  /* ── Afficher/masquer le mot de passe ──────────────────────────
     <button data-toggle-password="#id-du-champ"><i class="fa-solid fa-eye"></i></button>
  ──────────────────────────────────────────────────────────── */
  function initPasswordToggles() {
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
      var field = document.querySelector(btn.getAttribute('data-toggle-password'));
      if (!field) return;
      btn.addEventListener('click', function () {
        var showing = field.type === 'text';
        field.type = showing ? 'password' : 'text';
        var icon = btn.querySelector('i');
        if (icon) icon.className = showing ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
      });
    });
  }

  /* ── Compteur de caractères sur les champs limités ─────────────
     <textarea data-maxlength-counter="200"></textarea>
     <span data-counter-for="id-du-champ"></span>
  ──────────────────────────────────────────────────────────── */
  function initCharCounters() {
    document.querySelectorAll('[data-maxlength-counter]').forEach(function (field) {
      var max = parseInt(field.getAttribute('data-maxlength-counter'), 10);
      var counter = document.querySelector('[data-counter-for="' + field.id + '"]');
      if (!counter) return;

      function update() {
        var len = field.value.length;
        counter.textContent = len + ' / ' + max;
        counter.classList.toggle('text-red-600', len > max);
        counter.classList.toggle('font-bold', len > max);
      }
      field.addEventListener('input', update);
      update();
    });
  }

  /* ── Sélecteur d'icônes Font Awesome ────────────────────────────
     Structure attendue :
     <div class="icon-field-wrap">
       <span class="icon-field-preview"><i id="xxx-preview" class="fa-solid fa-star"></i></span>
       <input type="text" id="xxx-icone" name="icone" value="fa-star">
       <button type="button" data-icon-picker="#xxx-icone">Choisir</button>
     </div>
     <div class="icon-picker-panel hidden" id="xxx-icone-panel">
       <input type="text" data-icon-search>
       <div class="icon-picker-grid" data-icon-grid></div>
     </div>
  ──────────────────────────────────────────────────────────── */
  function initIconPickers() {
    document.querySelectorAll('[data-icon-picker]').forEach(function (btn) {
      var inputSelector = btn.getAttribute('data-icon-picker');
      var input = document.querySelector(inputSelector);
      var panel = document.querySelector(inputSelector + '-panel');
      var preview = document.querySelector(inputSelector + '-preview');
      if (!input || !panel) return;

      var grid = panel.querySelector('[data-icon-grid]');
      var search = panel.querySelector('[data-icon-search]');
      var built = false;

      function setIcon(code) {
        input.value = code;
        if (preview) preview.className = 'fa-solid ' + code;
        grid.querySelectorAll('.icon-picker-cell').forEach(function (cell) {
          cell.classList.toggle('is-selected', cell.dataset.code === code);
        });
      }

      function buildGrid() {
        if (built) return;
        built = true;
        var current = input.value.trim();

        ICON_LIBRARY.forEach(function (entry) {
          var code = entry[0];
          var label = entry[1];
          var cell = document.createElement('button');
          cell.type = 'button';
          cell.className = 'icon-picker-cell';
          cell.dataset.code = code;
          cell.dataset.label = label.toLowerCase();
          cell.title = label + ' (' + code + ')';
          if (code === current) cell.classList.add('is-selected');
          cell.innerHTML = '<i class="fa-solid ' + code + '"></i>';
          cell.addEventListener('click', function () {
            setIcon(code);
            panel.classList.add('hidden');
          });
          grid.appendChild(cell);
        });
      }

      function filterGrid() {
        var term = (search.value || '').trim().toLowerCase();
        var visible = 0;
        grid.querySelectorAll('.icon-picker-cell').forEach(function (cell) {
          var match = !term || cell.dataset.code.indexOf(term) !== -1 || cell.dataset.label.indexOf(term) !== -1;
          cell.style.display = match ? '' : 'none';
          if (match) visible++;
        });
        var emptyMsg = panel.querySelector('.icon-picker-empty');
        if (!emptyMsg) {
          emptyMsg = document.createElement('p');
          emptyMsg.className = 'icon-picker-empty';
          emptyMsg.textContent = 'Aucune icône ne correspond à cette recherche.';
          grid.parentNode.appendChild(emptyMsg);
        }
        emptyMsg.style.display = visible === 0 ? '' : 'none';
      }

      btn.addEventListener('click', function () {
        var willOpen = panel.classList.contains('hidden');
        document.querySelectorAll('.icon-picker-panel').forEach(function (p) { p.classList.add('hidden'); });
        if (willOpen) {
          buildGrid();
          panel.classList.remove('hidden');
          search.value = '';
          filterGrid();
          search.focus();
        }
      });

      if (search) search.addEventListener('input', filterGrid);

      // Garde l'aperçu synchronisé si l'utilisateur tape le code à la main
      input.addEventListener('input', function () {
        if (preview) preview.className = 'fa-solid ' + (input.value.trim() || 'fa-star');
      });

      document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
          panel.classList.add('hidden');
        }
      });
    });
  }
})();