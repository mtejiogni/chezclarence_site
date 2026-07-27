<?php
/**
 * admin/index.php
 * ─────────────────────────────────────────────────────────────
 * Point d'entrée du back-office. Ne construit et n'affiche jamais
 * de contenu lui-même — il redirige immédiatement vers la page
 * appropriée selon l'état du site :
 *
 *   1. Aucun compte administrateur en base   → install.php
 *   2. Un compte existe mais personne connecté → login.php
 *   3. Une session valide est déjà ouverte     → dashboard.php
 *
 * Ce fichier réutilise volontairement les mêmes fonctions que
 * require_login() (admin_count(), admin_is_logged_in()) plutôt que
 * de dupliquer la logique, pour ne jamais se désynchroniser d'elle.
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/includes/auth.php';

if (admin_count() === 0) {
    header('Location: install.php');
    exit;
}

if (admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;