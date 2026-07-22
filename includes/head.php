<?php
/**
 * includes/head.php
 * Attend éventuellement $page_title et $page_description définis
 * par la page appelante avant l'include.
 */
$p = get_parametres();
$title = $page_title ?? ($p['nom_restaurant'] . ' — ' . $p['slogan']);
$description = $page_description ?? $p['description'];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="theme-color" content="#111111">
<link rel="icon" href="<?= e(photo_url($p['logo']) ?? 'assets/img/favicon.svg') ?>">

<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:type" content="restaurant.restaurant">
<?php if (!empty($p['logo'])): ?>
<meta property="og:image" content="<?= e(photo_url($p['logo'])) ?>">
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- Font Awesome (npm : @fortawesome/fontawesome-free) -->
<link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
<!-- AOS — animations au scroll (npm : aos) -->
<link rel="stylesheet" href="assets/vendor/aos/aos.css">
<!-- Swiper — slider du hero (npm : swiper) -->
<link rel="stylesheet" href="assets/vendor/swiper/swiper-bundle.min.css">
<!-- Chosen — listes déroulantes stylées (npm : chosen-js) -->
<link rel="stylesheet" href="assets/vendor/chosen/chosen.min.css">
<!-- SweetAlert2 — confirmations/alertes (npm : sweetalert2) -->
<link rel="stylesheet" href="assets/vendor/sweetalert2/sweetalert2.min.css">

<!-- Tailwind CSS compilé (npm run build:css) -->
<link rel="stylesheet" href="assets/css/tailwind.css">
<!-- Styles et animations personnalisés -->
<link rel="stylesheet" href="assets/css/custom.css">
