<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$valeur = ['titre' => '', 'texte' => '', 'icone' => 'fa-star', 'ordre' => 0, 'statut' => 'Activé'];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM valeurs WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $trouve = $stmt->fetch();
    if (!$trouve) {
        flash_set('error', 'Valeur introuvable.');
        header('Location: valeurs.php');
        exit;
    }
    $valeur = $trouve;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'titre' => trim($_POST['titre'] ?? ''),
        'texte' => trim($_POST['texte'] ?? ''),
        'icone' => trim($_POST['icone'] ?? 'fa-star'),
        'ordre' => (int) ($_POST['ordre'] ?? 0),
        'statut' => in_array($_POST['statut'] ?? '', ['Activé', 'Désactivé'], true) ? $_POST['statut'] : 'Activé',
    ];

    if ($data['titre'] === '') {
        flash_set('error', 'Le titre est obligatoire.');
        header('Location: valeur-form.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    if ($id) {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $data['id'] = $id;
        $pdo->prepare("UPDATE valeurs SET $set WHERE id = :id")->execute($data);
        flash_set('success', 'Valeur mise à jour.');
    } else {
        $colonnes = implode(', ', array_keys($data));
        $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
        $pdo->prepare("INSERT INTO valeurs ($colonnes) VALUES ($valeurs)")->execute($data);
        flash_set('success', 'Valeur créée.');
    }
    header('Location: valeurs.php');
    exit;
}

$admin_title = $id ? 'Modifier la valeur' : 'Nouvelle valeur';
$admin_current = 'valeurs';
require __DIR__ . '/includes/layout-top.php';
?>

<a href="valeurs.php" class="inline-flex items-center gap-2 text-sm font-semibold text-ink/50 hover:text-brand-600 mb-5"><i class="fa-solid fa-arrow-left"></i> Retour aux valeurs</a>

<form method="post" class="admin-card p-7 max-w-xl space-y-5">
  <?= csrf_field() ?>
  <div>
    <label class="admin-label">Titre</label>
    <input type="text" name="titre" required class="admin-input" value="<?= e($valeur['titre']) ?>" placeholder="Ex : Fraîcheur">
  </div>
  <div>
    <label class="admin-label">Texte</label>
    <input type="text" name="texte" class="admin-input" value="<?= e($valeur['texte']) ?>" placeholder="Ex : Produits sélectionnés chaque jour.">
  </div>
  <div>
    <label class="admin-label">Icône</label>
    <div class="icon-field-wrap">
      <span class="icon-field-preview"><i id="valeur-icone-preview" class="fa-solid <?= e($valeur['icone']) ?>"></i></span>
      <input type="text" name="icone" id="valeur-icone" class="admin-input" value="<?= e($valeur['icone']) ?>" placeholder="fa-leaf">
      <button type="button" class="icon-picker-btn" data-icon-picker="#valeur-icone"><i class="fa-solid fa-icons"></i> Choisir</button>
    </div>
    <div class="icon-picker-panel hidden" id="valeur-icone-panel">
      <input type="text" class="admin-input" data-icon-search placeholder="Rechercher (ex : fraîcheur, rapidité...)">
      <div class="icon-picker-grid" data-icon-grid></div>
    </div>
  </div>
  <div class="grid sm:grid-cols-2 gap-5">
    <div>
      <label class="admin-label">Ordre d'affichage</label>
      <input type="number" name="ordre" class="admin-input" value="<?= (int) $valeur['ordre'] ?>">
    </div>
    <div>
      <label class="admin-label">Statut</label>
      <select name="statut" class="admin-select">
        <option value="Activé" <?= $valeur['statut'] === 'Activé' ? 'selected' : '' ?>>Activé</option>
        <option value="Désactivé" <?= $valeur['statut'] === 'Désactivé' ? 'selected' : '' ?>>Désactivé</option>
      </select>
    </div>
  </div>

  <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
  </button>
</form>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>