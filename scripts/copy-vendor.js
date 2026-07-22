/**
 * scripts/copy-vendor.js
 * ─────────────────────────────────────────────────────────────
 * Ce site n'utilise pas de bundler (webpack/vite) : c'est un site
 * PHP/HTML statique classique. Les librairies installées via npm
 * doivent donc être copiées "telles quelles" dans assets/vendor/
 * pour être chargées directement via <script src="..."> / <link>.
 *
 * Lancé automatiquement après `npm install` (postinstall) et via
 * `npm run copy:vendor`.
 * ─────────────────────────────────────────────────────────────
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const nm = path.join(root, 'node_modules');
const dest = path.join(root, 'assets', 'vendor');

function copy(src, destRelative) {
  const from = path.join(nm, src);
  const to = path.join(dest, destRelative);
  if (!fs.existsSync(from)) {
    console.warn('[copy-vendor] introuvable, ignoré :', src);
    return;
  }
  fs.mkdirSync(path.dirname(to), { recursive: true });
  fs.copyFileSync(from, to);
  console.log('[copy-vendor] ' + destRelative);
}

function copyDir(src, destRelative) {
  const from = path.join(nm, src);
  const to = path.join(dest, destRelative);
  if (!fs.existsSync(from)) {
    console.warn('[copy-vendor] dossier introuvable, ignoré :', src);
    return;
  }
  fs.mkdirSync(to, { recursive: true });
  for (const entry of fs.readdirSync(from, { withFileTypes: true })) {
    const s = path.join(from, entry.name);
    const d = path.join(to, entry.name);
    if (entry.isDirectory()) {
      fs.cpSync(s, d, { recursive: true });
    } else {
      fs.copyFileSync(s, d);
    }
  }
  console.log('[copy-vendor] dossier ' + destRelative + '/');
}

// jQuery
copy('jquery/dist/jquery.min.js', 'jquery/jquery.min.js');

// SweetAlert2
copy('sweetalert2/dist/sweetalert2.all.min.js', 'sweetalert2/sweetalert2.all.min.js');
copy('sweetalert2/dist/sweetalert2.min.css', 'sweetalert2/sweetalert2.min.css');

// Chosen
copy('chosen-js/chosen.jquery.min.js', 'chosen/chosen.jquery.min.js');
copy('chosen-js/chosen.min.css', 'chosen/chosen.min.css');
copy('chosen-js/chosen-sprite.png', 'chosen/chosen-sprite.png');
copy('chosen-js/chosen-sprite@2x.png', 'chosen/chosen-sprite@2x.png');

// AOS (Animate On Scroll)
copy('aos/dist/aos.js', 'aos/aos.js');
copy('aos/dist/aos.css', 'aos/aos.css');

// Swiper (slider du hero)
copy('swiper/swiper-bundle.min.js', 'swiper/swiper-bundle.min.js');
copy('swiper/swiper-bundle.min.css', 'swiper/swiper-bundle.min.css');

// Font Awesome (icônes)
copyDir('@fortawesome/fontawesome-free/webfonts', 'fontawesome/webfonts');
copy('@fortawesome/fontawesome-free/css/all.min.css', 'fontawesome/css/all.min.css');
copy('@fortawesome/fontawesome-free/js/all.min.js', 'fontawesome/js/all.min.js');

console.log('\n✔ Librairies copiées dans assets/vendor/');
