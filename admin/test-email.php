<?php
/**
 * admin/test-email.php
 * ─────────────────────────────────────────────────────────────
 * Outil de diagnostic, réservé aux utilisateurs connectés : envoie
 * un e-mail de test à leur propre adresse et affiche le résultat
 * exact (succès ou échec). Contrairement à mot-de-passe-oublie.php,
 * aucune précaution anti-énumération n'est nécessaire ici puisque le
 * destinataire est déjà connu (l'utilisateur connecté teste sa
 * propre boîte mail).
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
require_login();

$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_test'])) {
    csrf_verify();

    $p = get_parametres();
    $sujet = 'E-mail de test — ' . $p['nom_restaurant'];
    $corps = '
        <div style="font-family:Arial,sans-serif;font-size:15px;color:#111;line-height:1.6;">
            <p>Bonjour ' . e(current_admin_nom()) . ',</p>
            <p>Cet e-mail confirme que l\'envoi automatique fonctionne correctement sur ce serveur.</p>
            <p style="color:#666;font-size:13px;">Envoyé depuis l\'outil de diagnostic du back-office, le ' . e(date('d/m/Y à H:i')) . '.</p>
        </div>
    ';

    $succes = send_email($_SESSION['admin_email'], $sujet, $corps);
    $resultat = [
        'succes' => $succes,
        'destinataire' => $_SESSION['admin_email'],
    ];
}

$admin_title = 'Test d\'envoi d\'e-mail';
$admin_current = 'compte';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="max-w-xl">
  <div class="admin-card p-7">
    <h2 class="font-display text-lg text-ink mb-1"><i class="fa-solid fa-paper-plane text-brand-600 mr-2"></i>Tester l'envoi d'e-mail</h2>
    <p class="text-ink/55 text-sm leading-relaxed mb-6">
      Vérifie que le serveur peut réellement envoyer des e-mails (utilisé pour la récupération de mot de passe). Un message de test sera envoyé à votre propre adresse : <strong><?= e($_SESSION['admin_email']) ?></strong>.
    </p>

    <?php if ($resultat): ?>
      <?php if ($resultat['succes']): ?>
        <div class="mb-5 bg-success-50 border border-success-100 text-success-700 text-sm rounded-xl p-4">
          <i class="fa-solid fa-circle-check mr-1"></i>
          Le serveur a accepté l'envoi vers <?= e($resultat['destinataire']) ?>. Vérifiez votre boîte mail (et vos courriers indésirables) dans les prochaines minutes.
          <p class="text-success-700/70 text-xs mt-2">Un envoi "accepté" ne garantit pas toujours une livraison — si rien n'arrive après 10 minutes, la piste la plus probable est un serveur d'envoi (sendmail/postfix) non configuré chez votre hébergeur : contactez son support technique.</p>
        </div>
      <?php else: ?>
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4">
          <i class="fa-solid fa-circle-exclamation mr-1"></i>
          Échec : le serveur a refusé ou n'a pas pu envoyer l'e-mail vers <?= e($resultat['destinataire']) ?>.
          <p class="text-red-700/70 text-xs mt-2">Cause la plus fréquente : aucun agent d'envoi (sendmail/postfix) n'est configuré sur ce serveur. Contactez le support de votre hébergeur, ou demandez le passage à un envoi SMTP (Gmail, etc.).</p>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="envoyer_test" value="1">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
        <i class="fa-solid fa-paper-plane mr-1.5"></i> Envoyer un e-mail de test
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>