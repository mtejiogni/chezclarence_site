<?php
/**
 * admin/includes/mailer.php
 * ─────────────────────────────────────────────────────────────
 * Envoi d'e-mails minimal via la fonction mail() native de PHP —
 * aucune dépendance externe (pas de Composer/PHPMailer). Fonctionne
 * sans configuration sur la plupart des hébergeurs mutualisés
 * (cPanel, Plesk...), À CONDITION qu'un agent d'envoi (sendmail,
 * postfix...) soit réellement configuré sur le serveur — ce que
 * mail() ne garantit jamais et ne signale pas clairement en cas
 * d'échec.
 *
 * Si votre hébergeur bloque mail() ou que les e-mails finissent en
 * spam : remplacez uniquement le corps de send_email() par un envoi
 * SMTP (ex. PHPMailer). C'est la SEULE fonction à modifier — rien
 * d'autre dans le projet n'appelle mail() directement.
 * ─────────────────────────────────────────────────────────────
 */

/**
 * Envoie un e-mail HTML simple.
 *
 * @return bool true si le serveur a accepté l'envoi (ne garantit pas
 *              la délivrabilité — mail() ne renvoie jamais d'erreur
 *              détaillée, c'est une limite connue de cette fonction).
 *              Tout échec est journalisé (error_log) pour rester
 *              diagnosticable sans jamais l'afficher au visiteur —
 *              afficher "échec d'envoi" révélerait qu'un compte
 *              existe (ou non) pour l'adresse testée.
 */
function send_email(string $to, string $subject, string $htmlBody): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("[mailer] Adresse invalide, envoi annulé : {$to}");
        return false;
    }

    $domaine = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $from = 'no-reply@' . $domaine;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . $from . "\r\n";

    // Encodage RFC 2047 nécessaire pour un sujet accentué (français)
    $sujetEncode = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $succes = @mail($to, $sujetEncode, $htmlBody, $headers);

    if (!$succes) {
        // Journalisé côté serveur uniquement — jamais montré au visiteur.
        // Consultable via error_log (souvent visible depuis cPanel/Plesk
        // sous "Erreurs" ou dans le fichier configuré par error_log de
        // votre hébergeur).
        error_log(sprintf(
            '[mailer] Échec d\'envoi vers %s (sujet: %s) — vérifiez que sendmail/postfix est configuré sur ce serveur.',
            $to,
            $subject
        ));
    }

    return $succes;
}