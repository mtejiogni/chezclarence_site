<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_login();

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$slide = [
    'badge' => '', 'titre' => '', 'sous_titre' => '', 'description' => '',
    'bouton1_texte' => '', 'bouton1_type' => 'whatsapp', 'bouton1_valeur' => '',
    'bouton2_texte' => '', 'bouton2_type' => 'ancre', 'bouton2_valeur' => '',
    'illustration' => 'plate', 'image' => null, 'ordre' => 0, 'statut' => 'Activé',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM hero_slides WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $trouve = $stmt->fetch();
    if (!$trouve) {
        flash_set('error', 'Slide introuvable.');
        header('Location: hero.php');
        exit;
    }
    $slide = $trouve;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'badge' => trim($_POST['badge'] ?? ''),
        'titre' => trim($_POST['titre'] ?? ''),
        'sous_titre' => trim($_POST['sous_titre'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'bouton1_texte' => trim($_POST['bouton1_texte'] ?? ''),
        'bouton1_type' => in_array($_POST['bouton1_type'] ?? '', ['whatsapp', 'ancre', 'url'], true) ? $_POST['bouton1_type'] : 'whatsapp',
        'bouton1_valeur' => trim($_POST['bouton1_valeur'] ?? ''),
        'bouton2_texte' => trim($_POST['bouton2_texte'] ?? ''),
        'bouton2_type' => in_array($_POST['bouton2_type'] ?? '', ['whatsapp', 'ancre', 'url'], true) ? $_POST['bouton2_type'] : 'ancre',
        'bouton2_valeur' => trim($_POST['bouton2_valeur'] ?? ''),
        'illustration' => in_array($_POST['illustration'] ?? '', ['plate', 'table', 'traiteur', 'contact', 'personnalisee'], true) ? $_POST['illustration'] : 'plate',
        'ordre' => (int) ($_POST['ordre'] ?? 0),
        'statut' => in_array($_POST['statut'] ?? '', ['Activé', 'Désactivé'], true) ? $_POST['statut'] : 'Activé',
    ];

    if ($data['titre'] === '') {
        flash_set('error', 'Le titre du slide est obligatoire.');
        header('Location: hero-form.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    if ($data['illustration'] === 'personnalisee') {
        $upload = handle_upload('image', 'hero', $slide['image'] ?? null);
        if (!$upload['ok']) {
            flash_set('error', $upload['error']);
            header('Location: hero-form.php' . ($id ? "?id=$id" : ''));
            exit;
        }
        if ($upload['path']) {
            $data['image'] = $upload['path'];
        }
    }

    if ($id) {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $data['id'] = $id;
        $pdo->prepare("UPDATE hero_slides SET $set WHERE id = :id")->execute($data);
        flash_set('success', 'Slide mis à jour.');
    } else {
        $colonnes = implode(', ', array_keys($data));
        $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
        $pdo->prepare("INSERT INTO hero_slides ($colonnes) VALUES ($valeurs)")->execute($data);
        flash_set('success', 'Slide créé.');
    }

    header('Location: hero.php');
    exit;
}

$admin_title = $id ? 'Modifier le slide' : 'Nouveau slide';
$admin_current = 'hero';
require __DIR__ . '/includes/layout-top.php';
?>

<a href="hero.php" class="inline-flex items-center gap-2 text-sm font-semibold text-ink/50 hover:text-brand-600 mb-5"><i class="fa-solid fa-arrow-left"></i> Retour aux slides</a>

<form method="post" enctype="multipart/form-data" class="admin-card p-7 max-w-2xl space-y-5">
  <?= csrf_field() ?>

  <div>
    <label class="admin-label">Petit texte au-dessus du titre (badge)</label>
    <input type="text" name="badge" class="admin-input" value="<?= e($slide['badge']) ?>" placeholder="Ex : Douala · Grillades & Cuisine locale">
  </div>
  <div>
    <label class="admin-label">Titre principal</label>
    <input type="text" name="titre" required class="admin-input" value="<?= e($slide['titre']) ?>" placeholder="Astuce : {{nom_restaurant}}, {{slogan}} et {{description}} sont remplacés automatiquement">
    <p class="admin-hint">Vous pouvez utiliser <code>&lt;br&gt;</code> pour forcer un retour à la ligne, comme dans « Réservez votre table&lt;br&gt;en un message ».</p>
  </div>
  <div>
    <label class="admin-label">Sous-titre (optionnel)</label>
    <input type="text" name="sous_titre" class="admin-input" value="<?= e($slide['sous_titre']) ?>">
  </div>
  <div>
    <label class="admin-label">Texte descriptif</label>
    <textarea name="description" rows="3" class="admin-textarea"><?= e($slide['description']) ?></textarea>
  </div>

  <div class="border-t border-gray-100 pt-5">
    <p class="font-bold text-sm text-ink mb-3">Bouton principal (orange)</p>
    <div class="grid sm:grid-cols-3 gap-4">
      <input type="text" name="bouton1_texte" class="admin-input" placeholder="Texte du bouton" value="<?= e($slide['bouton1_texte']) ?>">
      <select name="bouton1_type" class="admin-select">
        <option value="whatsapp" <?= $slide['bouton1_type'] === 'whatsapp' ? 'selected' : '' ?>>Message WhatsApp</option>
        <option value="ancre" <?= $slide['bouton1_type'] === 'ancre' ? 'selected' : '' ?>>Aller vers une section (#ancre)</option>
        <option value="url" <?= $slide['bouton1_type'] === 'url' ? 'selected' : '' ?>>Lien externe / page</option>
      </select>
      <input type="text" name="bouton1_valeur" class="admin-input" placeholder="Message, #ancre ou URL" value="<?= e($slide['bouton1_valeur']) ?>">
    </div>
  </div>

  <div class="border-t border-gray-100 pt-5">
    <p class="font-bold text-sm text-ink mb-3">Bouton secondaire (contour blanc)</p>
    <div class="grid sm:grid-cols-3 gap-4">
      <input type="text" name="bouton2_texte" class="admin-input" placeholder="Texte du bouton" value="<?= e($slide['bouton2_texte']) ?>">
      <select name="bouton2_type" class="admin-select">
        <option value="whatsapp" <?= $slide['bouton2_type'] === 'whatsapp' ? 'selected' : '' ?>>Message WhatsApp</option>
        <option value="ancre" <?= $slide['bouton2_type'] === 'ancre' ? 'selected' : '' ?>>Aller vers une section (#ancre)</option>
        <option value="url" <?= $slide['bouton2_type'] === 'url' ? 'selected' : '' ?>>Lien externe / page</option>
      </select>
      <input type="text" name="bouton2_valeur" class="admin-input" placeholder="Message, #ancre ou URL" value="<?= e($slide['bouton2_valeur']) ?>">
    </div>
  </div>

  <div class="border-t border-gray-100 pt-5">
    <label class="admin-label">Illustration</label>
    <select name="illustration" id="illustration-select" class="admin-select">
      <option value="plate" <?= $slide['illustration'] === 'plate' ? 'selected' : '' ?>>Assiette fumante (dessinée)</option>
      <option value="table" <?= $slide['illustration'] === 'table' ? 'selected' : '' ?>>Table dressée (dessinée)</option>
      <option value="traiteur" <?= $slide['illustration'] === 'traiteur' ? 'selected' : '' ?>>Buffet traiteur (dessinée)</option>
      <option value="contact" <?= $slide['illustration'] === 'contact' ? 'selected' : '' ?>>Bulle WhatsApp (dessinée)</option>
      <option value="personnalisee" <?= $slide['illustration'] === 'personnalisee' ? 'selected' : '' ?>>Photo personnalisée (upload)</option>
    </select>
    <div id="illustration-upload" class="mt-3" style="<?= $slide['illustration'] === 'personnalisee' ? '' : 'display:none;' ?>">
      <div class="flex items-center gap-4">
        <div class="image-preview-frame">
          <img id="hero-image-preview" src="<?= !empty($slide['image']) ? e(photo_url($slide['image'])) : '' ?>" class="<?= empty($slide['image']) ? 'hidden' : '' ?>" alt="">
          <i class="fa-solid fa-image text-gray-300 text-xl <?= !empty($slide['image']) ? 'hidden' : '' ?>" id="hero-image-placeholder"></i>
        </div>
        <div>
          <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="admin-input" data-preview="#hero-image-preview" id="hero-image-input">
          <p class="image-preview-badge hidden" id="hero-image-preview-badge"><i class="fa-solid fa-check"></i> Nouvelle image sélectionnée</p>
        </div>
      </div>
    </div>
  </div>

  <div class="grid sm:grid-cols-2 gap-5">
    <div>
      <label class="admin-label">Ordre d'affichage</label>
      <input type="number" name="ordre" class="admin-input" value="<?= (int) $slide['ordre'] ?>">
    </div>
    <div>
      <label class="admin-label">Statut</label>
      <select name="statut" class="admin-select">
        <option value="Activé" <?= $slide['statut'] === 'Activé' ? 'selected' : '' ?>>Activé (visible)</option>
        <option value="Désactivé" <?= $slide['statut'] === 'Désactivé' ? 'selected' : '' ?>>Désactivé (masqué)</option>
      </select>
    </div>
  </div>

  <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
  </button>
</form>

<script>
  document.getElementById('illustration-select').addEventListener('change', function () {
    document.getElementById('illustration-upload').style.display = this.value === 'personnalisee' ? '' : 'none';
  });
  var heroImageInput = document.getElementById('hero-image-input');
  if (heroImageInput) {
    heroImageInput.addEventListener('change', function () {
      document.getElementById('hero-image-placeholder').classList.add('hidden');
    });
  }
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>