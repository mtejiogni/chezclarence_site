<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$stat = ['valeur' => 0, 'suffixe' => '+', 'label' => '', 'icone' => 'fa-star', 'calcul_auto' => 'non', 'ordre' => 0, 'statut' => 'Activé'];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM statistiques WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $trouve = $stmt->fetch();
    if (!$trouve) {
        flash_set('error', 'Statistique introuvable.');
        header('Location: stats.php');
        exit;
    }
    $stat = $trouve;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'valeur' => (int) ($_POST['valeur'] ?? 0),
        'suffixe' => trim($_POST['suffixe'] ?? ''),
        'label' => trim($_POST['label'] ?? ''),
        'icone' => trim($_POST['icone'] ?? 'fa-star'),
        'calcul_auto' => $_POST['calcul_auto'] === 'annees_depuis_1990' ? 'annees_depuis_1990' : 'non',
        'ordre' => (int) ($_POST['ordre'] ?? 0),
        'statut' => in_array($_POST['statut'] ?? '', ['Activé', 'Désactivé'], true) ? $_POST['statut'] : 'Activé',
    ];

    if ($data['label'] === '') {
        flash_set('error', 'Le libellé est obligatoire.');
        header('Location: stat-form.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    if ($id) {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $data['id'] = $id;
        $pdo->prepare("UPDATE statistiques SET $set WHERE id = :id")->execute($data);
        flash_set('success', 'Statistique mise à jour.');
    } else {
        $colonnes = implode(', ', array_keys($data));
        $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
        $pdo->prepare("INSERT INTO statistiques ($colonnes) VALUES ($valeurs)")->execute($data);
        flash_set('success', 'Statistique créée.');
    }
    header('Location: stats.php');
    exit;
}

$admin_title = $id ? 'Modifier la statistique' : 'Nouvelle statistique';
$admin_current = 'stats';
require __DIR__ . '/includes/layout-top.php';
?>

<a href="stats.php" class="inline-flex items-center gap-2 text-sm font-semibold text-ink/50 hover:text-brand-600 mb-5"><i class="fa-solid fa-arrow-left"></i> Retour aux statistiques</a>

<form method="post" class="admin-card p-7 max-w-xl space-y-5">
  <?= csrf_field() ?>

  <div>
    <label class="admin-label">Calcul de la valeur</label>
    <select name="calcul_auto" id="calcul-select" class="admin-select">
      <option value="non" <?= $stat['calcul_auto'] === 'non' ? 'selected' : '' ?>>Valeur fixe (saisie manuellement)</option>
      <option value="annees_depuis_1990" <?= $stat['calcul_auto'] === 'annees_depuis_1990' ? 'selected' : '' ?>>Calcul automatique : nombre d'années depuis 1990</option>
    </select>
  </div>
  <div id="valeur-manuelle" style="<?= $stat['calcul_auto'] !== 'non' ? 'display:none;' : '' ?>">
    <label class="admin-label">Valeur</label>
    <input type="number" name="valeur" class="admin-input" value="<?= (int) $stat['valeur'] ?>">
  </div>
  <div>
    <label class="admin-label">Suffixe (facultatif)</label>
    <input type="text" name="suffixe" class="admin-input" value="<?= e($stat['suffixe']) ?>" placeholder="Ex : + ou  min">
  </div>
  <div>
    <label class="admin-label">Libellé</label>
    <input type="text" name="label" required class="admin-input" value="<?= e($stat['label']) ?>" placeholder="Ex : Clients satisfaits">
  </div>
  <div>
    <label class="admin-label">Icône</label>
    <div class="icon-field-wrap">
      <span class="icon-field-preview"><i id="stat-icone-preview" class="fa-solid <?= e($stat['icone']) ?>"></i></span>
      <input type="text" name="icone" id="stat-icone" class="admin-input" value="<?= e($stat['icone']) ?>" placeholder="fa-users">
      <button type="button" class="icon-picker-btn" data-icon-picker="#stat-icone"><i class="fa-solid fa-icons"></i> Choisir</button>
    </div>
    <div class="icon-picker-panel hidden" id="stat-icone-panel">
      <input type="text" class="admin-input" data-icon-search placeholder="Rechercher (ex : clients, récompense...)">
      <div class="icon-picker-grid" data-icon-grid></div>
    </div>
  </div>
  <div class="grid sm:grid-cols-2 gap-5">
    <div>
      <label class="admin-label">Ordre d'affichage</label>
      <input type="number" name="ordre" class="admin-input" value="<?= (int) $stat['ordre'] ?>">
    </div>
    <div>
      <label class="admin-label">Statut</label>
      <select name="statut" class="admin-select">
        <option value="Activé" <?= $stat['statut'] === 'Activé' ? 'selected' : '' ?>>Activé</option>
        <option value="Désactivé" <?= $stat['statut'] === 'Désactivé' ? 'selected' : '' ?>>Désactivé</option>
      </select>
    </div>
  </div>

  <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
  </button>
</form>

<script>
  document.getElementById('calcul-select').addEventListener('change', function () {
    document.getElementById('valeur-manuelle').style.display = this.value === 'non' ? '' : 'none';
  });
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>