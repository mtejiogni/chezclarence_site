<?php
/**
 * admin/includes/mailer.php
 * ─────────────────────────────────────────────────────────────
 * Envoi d'e-mails minimal via la fonction mail() native de PHP —
 * aucune dépendance externe (pas de Composer/PHPMailer). Fonctionne
 * sans configuration sur la plupart des hébergeurs mutualisés
 * (cPanel, Plesk...).
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
 */
function send_email(string $to, string $subject, string $htmlBody): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domaine = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $from = 'no-reply@' . $domaine;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . $from . "\r\n";

    // Encodage RFC 2047 nécessaire pour un sujet accentué (français)
    $sujetEncode = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    return @mail($to, $sujetEncode, $htmlBody, $headers);
}