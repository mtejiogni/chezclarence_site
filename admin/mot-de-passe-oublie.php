<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$erreur = null;
$envoye = false;
$emailSaisi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $emailSaisi = trim($_POST['email'] ?? '');

    if (!filter_var($emailSaisi, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse e-mail invalide.';
    } else {
        $blocage = limitation_est_bloque('reinitialisation', $emailSaisi);

        if ($blocage !== null) {
            $erreur = 'Trop de tentatives récentes. Réessayez dans ' . ceil($blocage / 60) . ' minute(s).';
        } else {
            $stmt = get_pdo()->prepare('SELECT id, nom FROM administrateurs WHERE email = :email AND actif = 1');
            $stmt->execute(['email' => $emailSaisi]);
            $admin = $stmt->fetch();

            // Comptabilisée pour CET e-mail et cette IP dans tous les cas
            // (compte trouvé ou non) : c'est ce qui limite un sondage
            // automatisé de plusieurs adresses, maintenant que la réponse
            // révèle explicitement si un compte existe.
            limitation_enregistrer_echec('reinitialisation', $emailSaisi, 3, 8, 60);

            if (!$admin) {
                $erreur = "Aucun compte administrateur actif n'est associé à cette adresse e-mail.";
            } else {
                $token = create_password_reset_token((int) $admin['id']);
                $lien = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
                    . rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/reinitialiser-mot-de-passe.php?token=' . urlencode($token);

                $p = get_parametres();
                $sujet = 'Réinitialisation de votre mot de passe — ' . $p['nom_restaurant'];
                $corps = '
                    <div style="font-family:Arial,sans-serif;font-size:15px;color:#111;line-height:1.6;">
                        <p>Bonjour ' . e($admin['nom']) . ',</p>
                        <p>Une demande de réinitialisation de mot de passe a été effectuée pour votre compte administrateur du site <strong>' . e($p['nom_restaurant']) . '</strong>.</p>
                        <p style="margin:24px 0;">
                            <a href="' . e($lien) . '" style="background:#EA580C;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;display:inline-block;">
                                Choisir un nouveau mot de passe
                            </a>
                        </p>
                        <p style="color:#666;font-size:13px;">Ce lien est valable 1 heure et ne peut être utilisé qu\'une seule fois.</p>
                        <p style="color:#666;font-size:13px;">Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet e-mail sans risque : votre mot de passe actuel reste inchangé.</p>
                    </div>
                ';
                send_email($emailSaisi, $sujet, $corps);
                $envoye = true;
            }
        }
    }
}

$p = get_parametres();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mot de passe oublié — Administration <?= e($p['nom_restaurant']) ?></title>
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/tailwind.css">
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body min-h-screen grid place-items-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <?php if (!empty($p['logo'])): ?>
        <img src="<?= e(photo_url($p['logo'])) ?>" class="w-16 h-16 rounded-2xl object-cover mx-auto mb-3 ring-2 ring-brand-600/30" alt="">
      <?php else: ?>
        <span class="inline-grid w-16 h-16 place-items-center rounded-2xl bg-ink text-brand-500 text-2xl mb-3 mx-auto"><?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?></span>
      <?php endif; ?>
      <h1 class="font-display text-2xl text-ink"><?= e($p['nom_restaurant']) ?></h1>
      <p class="text-ink/50 text-sm mt-1">Mot de passe oublié</p>
    </div>

    <div class="admin-card p-7 shadow-sm">
      <?php if ($envoye): ?>
        <div class="text-center py-4">
          <span class="inline-grid w-14 h-14 place-items-center rounded-full bg-success-50 text-success-600 text-2xl mb-4">
            <i class="fa-solid fa-envelope-circle-check"></i>
          </span>
          <h2 class="font-display text-lg text-ink mb-2">Vérifiez votre boîte mail</h2>
          <p class="text-ink/55 text-sm leading-relaxed">
            Un e-mail contenant un lien de réinitialisation vient d'être envoyé à <strong><?= e($emailSaisi) ?></strong>. Le lien est valable 1 heure.
          </p>
          <p class="text-ink/40 text-xs mt-4">Pensez à vérifier vos courriers indésirables si vous ne le voyez pas d'ici quelques minutes.</p>
        </div>
      <?php else: ?>
        <?php if ($erreur): ?>
          <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 flex items-start gap-2">
            <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0"></i> <span><?= e($erreur) ?></span>
          </div>
        <?php endif; ?>

        <p class="text-ink/55 text-sm mb-5">Indiquez l'adresse e-mail de votre compte administrateur : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>
        <form method="post" class="space-y-4">
          <?= csrf_field() ?>
          <div>
            <label class="admin-label">Adresse e-mail</label>
            <input type="email" name="email" required autofocus class="admin-input" placeholder="vous@chezclarence.cm" value="<?= e($emailSaisi) ?>">
          </div>
          <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl transition">
            Envoyer le lien de réinitialisation
          </button>
        </form>
      <?php endif; ?>
    </div>

    <p class="text-center text-xs text-ink/40 mt-5">
      <a href="login.php" class="hover:text-brand-600"><i class="fa-solid fa-arrow-left mr-1"></i> Retour à la connexion</a>
    </p>
  </div>
  <script src="../assets/js/admin.js"></script>
</body>
</html>