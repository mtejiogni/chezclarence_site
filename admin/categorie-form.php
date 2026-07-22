<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_login();

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$categorie = ['intitule' => '', 'description' => '', 'photo' => null, 'ordre' => 0, 'statut' => 'Activé'];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $trouve = $stmt->fetch();
    if (!$trouve) {
        flash_set('error', 'Catégorie introuvable.');
        header('Location: categories.php');
        exit;
    }
    $categorie = $trouve;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'intitule' => trim($_POST['intitule'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'ordre' => (int) ($_POST['ordre'] ?? 0),
        'statut' => in_array($_POST['statut'] ?? '', ['Activé', 'Désactivé'], true) ? $_POST['statut'] : 'Activé',
    ];

    if ($data['intitule'] === '') {
        flash_set('error', "Le nom de la catégorie est obligatoire.");
        header('Location: categorie-form.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    $upload = handle_upload('photo', 'categories', $categorie['photo'] ?? null);
    if (!$upload['ok']) {
        flash_set('error', $upload['error']);
        header('Location: categorie-form.php' . ($id ? "?id=$id" : ''));
        exit;
    }
    if ($upload['path']) {
        $data['photo'] = $upload['path'];
    }

    if ($id) {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $data['id'] = $id;
        $pdo->prepare("UPDATE categories SET $set WHERE id = :id")->execute($data);
        flash_set('success', 'Catégorie mise à jour.');
    } else {
        $colonnes = implode(', ', array_keys($data));
        $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
        $pdo->prepare("INSERT INTO categories ($colonnes) VALUES ($valeurs)")->execute($data);
        flash_set('success', 'Catégorie créée.');
    }

    header('Location: categories.php');
    exit;
}

$admin_title = $id ? 'Modifier la catégorie' : 'Nouvelle catégorie';
$admin_current = 'categories';
require __DIR__ . '/includes/layout-top.php';
?>

<a href="categories.php" class="inline-flex items-center gap-2 text-sm font-semibold text-ink/50 hover:text-brand-600 mb-5"><i class="fa-solid fa-arrow-left"></i> Retour aux catégories</a>

<form method="post" enctype="multipart/form-data" class="admin-card p-7 max-w-2xl space-y-5">
  <?= csrf_field() ?>
  <div>
    <label class="admin-label">Nom de la catégorie</label>
    <input type="text" name="intitule" required class="admin-input" value="<?= e($categorie['intitule']) ?>" placeholder="Ex : Grillades" maxlength="100" id="cat-intitule" data-maxlength-counter="100">
    <p class="admin-hint text-right" data-counter-for="cat-intitule">0 / 100</p>
  </div>
  <div>
    <label class="admin-label">Description courte</label>
    <textarea name="description" rows="3" class="admin-textarea"><?= e($categorie['description']) ?></textarea>
  </div>
  <div>
    <label class="admin-label">Photo (visible au dos de la carte animée sur le site)</label>
    <div class="flex items-center gap-4">
      <div class="image-preview-frame">
        <img id="cat-photo-preview" src="<?= !empty($categorie['photo']) ? e(photo_url($categorie['photo'])) : '' ?>" class="<?= empty($categorie['photo']) ? 'hidden' : '' ?>" alt="">
        <i class="fa-solid fa-image text-gray-300 text-xl <?= !empty($categorie['photo']) ? 'hidden' : '' ?>" id="cat-photo-placeholder"></i>
      </div>
      <div>
        <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="admin-input" data-preview="#cat-photo-preview">
        <p class="image-preview-badge hidden" id="cat-photo-preview-badge"><i class="fa-solid fa-check"></i> Nouvelle image sélectionnée</p>
      </div>
    </div>
  </div>
  <div class="grid sm:grid-cols-2 gap-5">
    <div>
      <label class="admin-label">Ordre d'affichage</label>
      <input type="number" name="ordre" class="admin-input" value="<?= (int) $categorie['ordre'] ?>">
      <p class="admin-hint">Les catégories sont affichées de la plus petite à la plus grande valeur.</p>
    </div>
    <div>
      <label class="admin-label">Statut</label>
      <select name="statut" class="admin-select">
        <option value="Activé" <?= $categorie['statut'] === 'Activé' ? 'selected' : '' ?>>Activé (visible sur le site)</option>
        <option value="Désactivé" <?= $categorie['statut'] === 'Désactivé' ? 'selected' : '' ?>>Désactivé (masqué)</option>
      </select>
    </div>
  </div>

  <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
  </button>
</form>

<script>
  document.querySelector('input[name="photo"]').addEventListener('change', function () {
    document.getElementById('cat-photo-placeholder').classList.add('hidden');
  });
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>