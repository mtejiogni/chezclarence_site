<?php
require_once __DIR__ . '/includes/auth.php';

if (admin_count() === 0) {
    header('Location: install.php');
    exit;
}
if (admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$erreur = null;
$flash = flash_get();
$blocageRestant = limitation_est_bloque('connexion');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    $blocage = limitation_est_bloque('connexion', $email);

    if ($blocage !== null) {
        $erreur = 'Trop de tentatives incorrectes. Réessayez dans ' . ceil($blocage / 60) . ' minute(s).';
    } elseif (admin_attempt_login($email, $motDePasse)) {
        limitation_reinitialiser('connexion', $email);
        header('Location: dashboard.php');
        exit;
    } else {
        limitation_enregistrer_echec('connexion', $email, 5, 15, 15);
        $erreur = 'E-mail ou mot de passe incorrect.';
    }
}

$p = get_parametres();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — Administration <?= e($p['nom_restaurant']) ?></title>
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/tailwind.css">
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    /* ── Styles propres à la page de connexion ─────────────────── */
    html, body { height: 100%; }
    .login-shell { min-height: 100vh; }

    .login-brand-panel {
      position: relative;
      overflow: hidden;
      background: linear-gradient(160deg, rgb(var(--ink)) 0%, #1a1a1a 55%, rgb(var(--ink)) 100%);
    }
    .login-heading {
      font-size: clamp(1.65rem, 2.4vw + 1rem, 2.75rem);
      line-height: 1.15;
    }
    .login-brand-name {
      min-width: 0;
      overflow-wrap: break-word;
    }
    .login-brand-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 85% 15%, rgba(234, 88, 12, 0.30), transparent 45%),
        radial-gradient(circle at 8% 90%, rgba(234, 88, 12, 0.18), transparent 40%);
      pointer-events: none;
    }
    .login-brand-orb {
      position: absolute;
      border-radius: 999px;
      background: rgba(234, 88, 12, 0.14);
      pointer-events: none;
    }
    .login-feature {
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }
    .login-feature-icon {
      width: 38px;
      height: 38px;
      flex-shrink: 0;
      border-radius: 0.7rem;
      display: grid;
      place-items: center;
      background: rgba(234, 88, 12, 0.15);
      color: rgb(var(--brand-400));
    }

    .login-field { position: relative; }
    .login-field-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #9CA3AF;
      font-size: 0.85rem;
      transition: color 0.15s ease;
      pointer-events: none;
    }
    .login-field-input { padding-left: 2.6rem; }
    .login-field-input:focus + .login-field-icon,
    .login-field:focus-within .login-field-icon { color: rgb(var(--brand-600)); }
    .password-field-wrap .login-field-input { padding-right: 2.6rem; }

    .capslock-warning {
      display: none;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      color: #B45309;
      background: #FFFBEB;
      border: 1px solid #FDE68A;
      border-radius: 0.6rem;
      padding: 0.45rem 0.7rem;
      margin-top: 0.5rem;
    }
    .capslock-warning.show { display: flex; }

    @keyframes login-shake {
      10%, 90% { transform: translateX(-1px); }
      20%, 80% { transform: translateX(2px); }
      30%, 50%, 70% { transform: translateX(-4px); }
      40%, 60% { transform: translateX(4px); }
    }
    .login-shake { animation: login-shake 0.45s cubic-bezier(.36,.07,.19,.97) both; }

    .login-submit-btn[disabled] { opacity: 0.75; cursor: not-allowed; }

    @media (prefers-reduced-motion: reduce) {
      .login-shake { animation: none; }
    }
  </style>
</head>
<body class="admin-body">

<div class="login-shell grid lg:grid-cols-2">

  <div class="login-brand-panel hidden lg:flex flex-col justify-between p-8 lg:p-10 xl:p-12 text-white relative">
    <span class="login-brand-orb w-64 h-64 -top-16 -right-16 float-2"></span>
    <span class="login-brand-orb w-40 h-40 bottom-10 -left-10 float-3"></span>

    <div class="relative z-10">
      <div class="flex items-center gap-3 mb-16">
        <?php if (!empty($p['logo'])): ?>
          <img src="<?= e(photo_url($p['logo'])) ?>" class="w-11 h-11 rounded-xl object-cover ring-2 ring-brand-600/40" alt="">
        <?php else: ?>
          <span class="w-11 h-11 rounded-xl bg-brand-600 grid place-items-center font-display text-lg"><?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?></span>
        <?php endif; ?>
        <span class="font-display text-xl tracking-wide login-brand-name truncate"><?= e($p['nom_restaurant']) ?></span>
      </div>

      <h1 class="font-display login-heading mb-5">
        Gérez votre restaurant en toute simplicité
      </h1>
      <p class="text-white/55 text-base leading-relaxed max-w-sm mb-12">
        Ce panneau vous permet de mettre à jour votre carte, vos photos et vos informations, sans aucune connaissance technique.
      </p>

      <div class="space-y-6 max-w-sm">
        <div class="login-feature">
          <span class="login-feature-icon"><i class="fa-solid fa-utensils"></i></span>
          <div>
            <p class="font-bold text-sm text-white">Votre carte à jour en un instant</p>
            <p class="text-white/45 text-sm mt-0.5">Plats, prix et photos modifiables en quelques clics.</p>
          </div>
        </div>
        <div class="login-feature">
          <span class="login-feature-icon"><i class="fa-solid fa-images"></i></span>
          <div>
            <p class="font-bold text-sm text-white">Un site toujours vivant</p>
            <p class="text-white/45 text-sm mt-0.5">Bannière, services et statistiques personnalisables.</p>
          </div>
        </div>
        <div class="login-feature">
          <span class="login-feature-icon"><i class="fa-solid fa-users-gear"></i></span>
          <div>
            <p class="font-bold text-sm text-white">Toute votre équipe, un accès adapté</p>
            <p class="text-white/45 text-sm mt-0.5">Administrateurs et éditeurs, chacun avec ses droits.</p>
          </div>
        </div>
      </div>
    </div>

    <p class="relative z-10 text-white/30 text-xs">© <?= date('Y') ?> <?= e($p['nom_restaurant']) ?>. Back-office privé.</p>
  </div>

  <div class="flex items-center justify-center p-4 sm:p-6 lg:p-10">
    <div class="w-full max-w-md">

      <div class="text-center mb-6 lg:hidden">
        <?php if (!empty($p['logo'])): ?>
          <img src="<?= e(photo_url($p['logo'])) ?>" class="w-16 h-16 rounded-2xl object-cover mx-auto mb-3 ring-2 ring-brand-600/30" alt="">
        <?php else: ?>
          <span class="inline-grid w-16 h-16 place-items-center rounded-2xl bg-ink text-brand-500 text-2xl mb-3 mx-auto"><?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?></span>
        <?php endif; ?>
        <h1 class="font-display text-2xl text-ink break-words px-2"><?= e($p['nom_restaurant']) ?></h1>
        <p class="text-ink/50 text-sm mt-1">Panneau d'administration du site</p>
      </div>

      <div class="hidden lg:block mb-8">
        <h2 class="font-display text-2xl text-ink">Connexion</h2>
        <p class="text-ink/50 text-sm mt-1">Accédez à votre panneau d'administration.</p>
      </div>

      <div class="admin-card p-5 sm:p-7 shadow-sm <?= $erreur ? 'login-shake' : '' ?>">
        <?php if ($flash): ?>
          <div class="mb-5 <?= $flash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-success-50 border-success-100 text-success-700' ?> border text-sm rounded-xl p-4 flex items-start gap-2">
            <i class="fa-solid <?= $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?> mt-0.5 shrink-0"></i> <span class="min-w-0 break-words"><?= e($flash['message']) ?></span>
          </div>
        <?php endif; ?>
        <?php if ($erreur): ?>
          <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 flex items-start gap-2">
            <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0"></i> <span class="min-w-0 break-words"><?= e($erreur) ?></span>
          </div>
        <?php elseif ($blocageRestant !== null): ?>
          <div class="mb-5 bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl p-4 flex items-start gap-2">
            <i class="fa-solid fa-clock mt-0.5 shrink-0"></i>
            <span class="min-w-0 break-words">Trop de tentatives récentes depuis cet appareil. Réessayez dans <?= ceil($blocageRestant / 60) ?> minute(s).</span>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-4" id="login-form">
          <?= csrf_field() ?>

          <div>
            <label class="admin-label">Adresse e-mail</label>
            <div class="login-field">
              <input type="email" name="email" required autofocus class="admin-input login-field-input" placeholder="vous@chezclarence.cm" value="<?= isset($email) ? e($email) : '' ?>">
              <i class="fa-solid fa-envelope login-field-icon"></i>
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between">
              <label class="admin-label mb-0">Mot de passe</label>
              <a href="mot-de-passe-oublie.php" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Mot de passe oublié ?</a>
            </div>
            <div class="password-field-wrap login-field mt-1.5">
              <input type="password" name="mot_de_passe" id="login-password" required class="admin-input login-field-input" placeholder="••••••••">
              <i class="fa-solid fa-lock login-field-icon"></i>
              <button type="button" class="password-toggle-btn" data-toggle-password="#login-password" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye"></i></button>
            </div>
            <p class="capslock-warning" id="capslock-warning">
              <i class="fa-solid fa-triangle-exclamation"></i> Verrouillage majuscules activé
            </p>
          </div>

          <button type="submit" class="login-submit-btn w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-right-to-bracket"></i> <span>Se connecter</span>
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-ink/40 mt-5">
        <a href="../index.php" class="hover:text-brand-600"><i class="fa-solid fa-arrow-left mr-1"></i> Retour au site</a>
      </p>
    </div>
  </div>
</div>

<script src="../assets/js/admin.js"></script>
<script>
  (function () {
    var pwd = document.getElementById('login-password');
    var warning = document.getElementById('capslock-warning');
    if (!pwd || !warning) return;

    function checkCapsLock(e) {
      if (typeof e.getModifierState !== 'function') return;
      warning.classList.toggle('show', e.getModifierState('CapsLock'));
    }
    pwd.addEventListener('keyup', checkCapsLock);
    pwd.addEventListener('keydown', checkCapsLock);
    pwd.addEventListener('blur', function () { warning.classList.remove('show'); });
  })();

  (function () {
    var form = document.getElementById('login-form');
    if (!form) return;
    form.addEventListener('submit', function () {
      var btn = form.querySelector('.login-submit-btn');
      if (!btn || btn.disabled) return;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Connexion en cours...</span>';
    });
  })();
</script>
</body>
</html>