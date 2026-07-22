<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    admin_logout();
    header('Location: login.php');
    exit;
}

// Accès direct en GET (lien partagé, préchargement du navigateur, robot...) :
// on ne déconnecte personne sans confirmation explicite via le formulaire.
header('Location: ' . (admin_is_logged_in() ? 'dashboard.php' : 'login.php'));
exit;