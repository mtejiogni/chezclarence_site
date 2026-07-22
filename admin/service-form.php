<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$service = [
    'slug' => '', 'icone' => 'fa-star', 'titre' => '', 'resume' => '', 'description' => '',
    'points' => '', 'message_whatsapp' => '', 'ordre' => 0, 'statut' => 'Activé',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $trouve = $stmt->fetch();
    if (!$trouve) {
        flash_set('error', 'Service introuvable.');
        header('Location: services.php');
        exit;
    }
    $service = $trouve;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $titre = trim($_POST['titre'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $titre));
    $slug = trim($slug, '-');

    $data = [
        'slug' => $slug,
        'icone' => trim($_POST['icone'] ?? 'fa-star'),
        'titre' => $titre,
        'resume' => trim($_POST['resume'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'points' => trim($_POST['points'] ?? ''),
        'message_whatsapp' => trim($_POST['message_whatsapp'] ?? ''),
        'ordre' => (int) ($_POST['ordre'] ?? 0),
        'statut' => in_array($_POST['statut'] ?? '', ['Activé', 'Désactivé'], true) ? $_POST['statut'] : 'Activé',
    ];

    if ($titre === '' || $slug === '') {
        $erreur = 'Le titre du service est obligatoire.';
    } else {
        // Vérifie l'unicité du slug
        $check = $pdo->prepare('SELECT id FROM services WHERE slug = :slug AND id != :id');
        $check->execute(['slug' => $slug, 'id' => $id ?: 0]);
        if ($check->fetch()) {
            $data['slug'] .= '-' . substr(uniqid(), -4);
        }

        if ($id) {
            $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
            $data['id'] = $id;
            $pdo->prepare("UPDATE services SET $set WHERE id = :id")->execute($data);
            flash_set('success', 'Service mis à jour.');
        } else {
            $colonnes = implode(', ', array_keys($data));
            $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
            $pdo->prepare("INSERT INTO services ($colonnes) VALUES ($valeurs)")->execute($data);
            flash_set('success', 'Service créé.');
        }
        header('Location: services.php');
        exit;
    }
}

$admin_title = $id ? 'Modifier le service' : 'Nouveau service';
$admin_current = 'services';
require __DIR__ . '/includes/layout-top.php';
?>

<a href="services.php" class="inline-flex items-center gap-2 text-sm font-semibold text-ink/50 hover:text-brand-600 mb-5"><i class="fa-solid fa-arrow-left"></i> Retour aux services</a>

<?php if ($erreur): ?>
  <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 max-w-2xl"><?= e($erreur) ?></div>
<?php endif; ?>

<form method="post" class="admin-card p-7 max-w-2xl space-y-5">
  <?= csrf_field() ?>

  <div class="grid sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
      <label class="admin-label">Titre du service</label>
      <input type="text" name="titre" required id="service-titre" data-slug-source class="admin-input" value="<?= e($service['titre']) ?>" placeholder="Ex : Service traiteur">
    </div>
    <div>
      <label class="admin-label">Icône</label>
      <div class="icon-field-wrap">
        <span class="icon-field-preview"><i id="service-icone-preview" class="fa-solid <?= e($service['icone']) ?>"></i></span>
        <input type="text" name="icone" id="service-icone" class="admin-input" value="<?= e($service['icone']) ?>" placeholder="fa-utensils">
        <button type="button" class="icon-picker-btn" data-icon-picker="#service-icone"><i class="fa-solid fa-icons"></i> Choisir</button>
      </div>
      <div class="icon-picker-panel hidden" id="service-icone-panel">
        <input type="text" class="admin-input" data-icon-search placeholder="Rechercher (ex : livraison, cadeau, fête...)">
        <div class="icon-picker-grid" data-icon-grid></div>
      </div>
      <p class="admin-hint">Choisissez une icône dans la liste, ou tapez directement un code parmi <a href="https://fontawesome.com/search?o=r&s=solid" target="_blank" class="text-brand-600 underline">fontawesome.com</a> (style « solid »), sans le préfixe « fa-solid ».</p>
    </div>
    <div>
      <label class="admin-label">Identifiant d'ancre (optionnel)</label>
      <input type="text" name="slug" id="service-slug" data-slug-field class="admin-input" value="<?= e($service['slug']) ?>" placeholder="Laisser vide pour générer automatiquement">
      <div class="slug-preview"><i class="fa-solid fa-link"></i> /services.php#<span data-slug-preview>votre-service</span></div>
    </div>
    <div class="sm:col-span-2">
      <label class="admin-label">Résumé (affiché en aperçu sur l'accueil)</label>
      <input type="text" name="resume" id="service-resume" maxlength="120" data-maxlength-counter="120" class="admin-input" value="<?= e($service['resume']) ?>">
      <p class="admin-hint text-right" data-counter-for="service-resume">0 / 120</p>
    </div>
    <div class="sm:col-span-2">
      <label class="admin-label">Description complète (page Services)</label>
      <textarea name="description" rows="4" class="admin-textarea"><?= e($service['description']) ?></textarea>
    </div>
    <div class="sm:col-span-2">
      <label class="admin-label">Points clés (un par ligne)</label>
      <textarea name="points" rows="4" class="admin-textarea" placeholder="Un avantage par ligne"><?= e($service['points']) ?></textarea>
    </div>
    <div class="sm:col-span-2">
      <label class="admin-label">Message WhatsApp pré-rempli pour ce service</label>
      <input type="text" name="message_whatsapp" class="admin-input" value="<?= e($service['message_whatsapp']) ?>">
    </div>
    <div>
      <label class="admin-label">Ordre d'affichage</label>
      <input type="number" name="ordre" class="admin-input" value="<?= (int) $service['ordre'] ?>">
    </div>
    <div>
      <label class="admin-label">Statut</label>
      <select name="statut" class="admin-select">
        <option value="Activé" <?= $service['statut'] === 'Activé' ? 'selected' : '' ?>>Activé (visible)</option>
        <option value="Désactivé" <?= $service['statut'] === 'Désactivé' ? 'selected' : '' ?>>Désactivé (masqué)</option>
      </select>
    </div>
  </div>

  <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
  </button>
</form>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>