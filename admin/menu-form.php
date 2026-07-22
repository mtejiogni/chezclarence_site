<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_login();

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$menu = [
    'categorie_id' => '', 'intitule' => '', 'description' => '', 'prix' => '',
    'photo' => null, 'etoiles' => 5, 'populaire' => 0, 'ordre' => 0, 'statut' => 'Activé',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $trouve = $stmt->fetch();
    if (!$trouve) {
        flash_set('error', 'Plat introuvable.');
        header('Location: menus.php');
        exit;
    }
    $menu = $trouve;
}

$categories = $pdo->query('SELECT id, intitule FROM categories ORDER BY ordre ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'categorie_id' => (int) ($_POST['categorie_id'] ?? 0),
        'intitule' => trim($_POST['intitule'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'prix' => (float) str_replace(',', '.', $_POST['prix'] ?? '0'),
        'etoiles' => max(1, min(5, (int) ($_POST['etoiles'] ?? 5))),
        'populaire' => isset($_POST['populaire']) ? 1 : 0,
        'ordre' => (int) ($_POST['ordre'] ?? 0),
        'statut' => in_array($_POST['statut'] ?? '', ['Activé', 'Désactivé'], true) ? $_POST['statut'] : 'Activé',
    ];

    if ($data['intitule'] === '' || !$data['categorie_id']) {
        flash_set('error', 'Le nom du plat et sa catégorie sont obligatoires.');
        header('Location: menu-form.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    $upload = handle_upload('photo', 'menus', $menu['photo'] ?? null);
    if (!$upload['ok']) {
        flash_set('error', $upload['error']);
        header('Location: menu-form.php' . ($id ? "?id=$id" : ''));
        exit;
    }
    if ($upload['path']) {
        $data['photo'] = $upload['path'];
    }

    if ($id) {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $data['id'] = $id;
        $pdo->prepare("UPDATE menus SET $set WHERE id = :id")->execute($data);
        flash_set('success', 'Plat mis à jour.');
    } else {
        $colonnes = implode(', ', array_keys($data));
        $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
        $pdo->prepare("INSERT INTO menus ($colonnes) VALUES ($valeurs)")->execute($data);
        flash_set('success', 'Plat ajouté à la carte.');
    }

    header('Location: menus.php');
    exit;
}

$admin_title = $id ? 'Modifier le plat' : 'Nouveau plat';
$admin_current = 'menus';
require __DIR__ . '/includes/layout-top.php';
?>

<a href="menus.php" class="inline-flex items-center gap-2 text-sm font-semibold text-ink/50 hover:text-brand-600 mb-5"><i class="fa-solid fa-arrow-left"></i> Retour aux plats</a>

<?php if (!$categories): ?>
  <div class="admin-card p-8 text-center text-ink/50 max-w-2xl">
    Créez d'abord une <a href="categories.php" class="text-brand-600 font-semibold">catégorie</a> avant d'ajouter un plat.
  </div>
<?php else: ?>
<div class="grid lg:grid-cols-3 gap-6 max-w-5xl">
  <form method="post" enctype="multipart/form-data" id="menu-form" class="admin-card p-7 space-y-5 lg:col-span-2">
    <?= csrf_field() ?>

    <div class="grid sm:grid-cols-2 gap-5">
      <div class="sm:col-span-2">
        <label class="admin-label">Nom du plat</label>
        <input type="text" name="intitule" required id="menu-intitule" class="admin-input" value="<?= e($menu['intitule']) ?>" placeholder="Ex : Poulet braisé">
      </div>
      <div class="sm:col-span-2">
        <label class="admin-label">Catégorie</label>
        <select name="categorie_id" required class="admin-select">
          <option value="">— Choisir —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $menu['categorie_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['intitule']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="admin-label">Description</label>
        <textarea name="description" rows="3" id="menu-description" maxlength="300" data-maxlength-counter="300" class="admin-textarea"><?= e($menu['description']) ?></textarea>
        <p class="admin-hint text-right" data-counter-for="menu-description">0 / 300</p>
      </div>
      <div>
        <label class="admin-label">Prix (<?= e(get_parametres()['devise']) ?>)</label>
        <input type="number" step="1" min="0" name="prix" required class="admin-input" value="<?= e((string) $menu['prix']) ?>">
      </div>
      <div>
        <label class="admin-label">Note affichée (étoiles)</label>
        <select name="etoiles" class="admin-select">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>" <?= (int) $menu['etoiles'] === $i ? 'selected' : '' ?>><?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div>
        <label class="admin-label">Ordre d'affichage</label>
        <input type="number" name="ordre" class="admin-input" value="<?= (int) $menu['ordre'] ?>">
      </div>
      <div>
        <label class="admin-label">Statut</label>
        <select name="statut" class="admin-select">
          <option value="Activé" <?= $menu['statut'] === 'Activé' ? 'selected' : '' ?>>Activé (visible)</option>
          <option value="Désactivé" <?= $menu['statut'] === 'Désactivé' ? 'selected' : '' ?>>Désactivé (masqué)</option>
        </select>
      </div>
      <div class="sm:col-span-2 flex items-center gap-2.5 pt-1">
        <input type="checkbox" id="populaire" name="populaire" value="1" <?= !empty($menu['populaire']) ? 'checked' : '' ?> class="w-4 h-4 accent-brand-600">
        <label for="populaire" class="text-sm font-semibold text-ink">Mettre en avant avec le badge « Populaire »</label>
      </div>
    </div>

    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
      <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
    </button>
  </form>

  <div class="admin-card p-7 h-fit">
    <label class="admin-label">Photo du plat</label>
    <div class="image-preview-frame w-full" style="height:180px;">
      <img id="menu-photo-preview" src="<?= !empty($menu['photo']) ? e(photo_url($menu['photo'])) : '' ?>" class="<?= empty($menu['photo']) ? 'hidden' : '' ?>" alt="">
      <i class="fa-solid fa-utensils text-gray-300 text-2xl <?= !empty($menu['photo']) ? 'hidden' : '' ?>" id="menu-photo-placeholder"></i>
    </div>
    <input type="file" name="photo" form="menu-form" accept="image/png,image/jpeg,image/webp" class="admin-input mt-3" data-preview="#menu-photo-preview" id="menu-photo-input">
    <p class="image-preview-badge hidden" id="menu-photo-preview-badge"><i class="fa-solid fa-check"></i> Nouvelle image sélectionnée</p>
    <p class="admin-hint mt-2">JPG, PNG ou WEBP — 4 Mo maximum. Une photo appétissante augmente nettement les commandes.</p>
  </div>
</div>

<script>
  document.getElementById('menu-photo-input').addEventListener('change', function () {
    document.getElementById('menu-photo-placeholder').classList.add('hidden');
  });
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>