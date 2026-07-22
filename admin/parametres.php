<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_login();

$pdo = get_pdo();
$p = get_parametres();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_logo'])) {
    csrf_verify();

    if (!empty($p['logo'])) {
        $cheminAbsolu = __DIR__ . '/../uploads/' . $p['logo'];
        if (is_file($cheminAbsolu)) {
            @unlink($cheminAbsolu);
        }
        $pdo->prepare('UPDATE parametres SET logo = NULL WHERE id = :id')->execute(['id' => $p['id']]);
    }

    flash_set('success', 'Logo supprimé.');
    header('Location: parametres.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $champs = [
        'entreprise', 'nom_restaurant', 'slogan', 'description',
        'adresse', 'latitude', 'longitude', 'telephone', 'telephone2', 'email',
        'ville', 'horaires', 'whatsapp', 'message_whatsapp',
        'facebook', 'instagram', 'tiktok', 'devise', 'mention_legale',
    ];

    $data = [];
    foreach ($champs as $champ) {
        $data[$champ] = trim($_POST[$champ] ?? '');
    }
    // Le numéro WhatsApp est nettoyé : chiffres uniquement
    $data['whatsapp'] = preg_replace('/\D+/', '', $data['whatsapp']);

    $upload = handle_upload('logo', 'logo', $p['logo'] ?? null);
    if (!$upload['ok']) {
        flash_set('error', $upload['error']);
        header('Location: parametres.php');
        exit;
    }
    if ($upload['path']) {
        $data['logo'] = $upload['path'];
    }

    if (empty($p['id'])) {
        $colonnes = implode(', ', array_keys($data));
        $valeurs = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
        $pdo->prepare("INSERT INTO parametres ($colonnes) VALUES ($valeurs)")->execute($data);
    } else {
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $data['id'] = $p['id'];
        $pdo->prepare("UPDATE parametres SET $set WHERE id = :id")->execute($data);
    }

    flash_set('success', 'Paramètres enregistrés avec succès.');
    header('Location: parametres.php');
    exit;
}

$admin_title = 'Paramètres généraux';
$admin_current = 'parametres';
require __DIR__ . '/includes/layout-top.php';
?>

<form method="post" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
  <?= csrf_field() ?>

  <div class="admin-card p-7">
    <h2 class="font-display text-lg text-ink mb-5"><i class="fa-solid fa-store text-brand-600 mr-2"></i>Identité</h2>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="admin-label">Nom affiché du restaurant</label>
        <input type="text" name="nom_restaurant" required class="admin-input" value="<?= e($p['nom_restaurant']) ?>">
      </div>
      <div>
        <label class="admin-label">Raison sociale / entreprise</label>
        <input type="text" name="entreprise" class="admin-input" value="<?= e($p['entreprise']) ?>">
      </div>
      <div class="sm:col-span-2">
        <label class="admin-label">Slogan</label>
        <input type="text" name="slogan" id="param-slogan" maxlength="200" data-maxlength-counter="200" class="admin-input" value="<?= e($p['slogan']) ?>" placeholder="Ex : Restaurant · Snack · Grill — depuis 1990">
        <p class="admin-hint text-right" data-counter-for="param-slogan">0 / 200</p>
      </div>
      <div class="sm:col-span-2">
        <label class="admin-label">Description (section « À propos » et méta-description)</label>
        <textarea name="description" id="param-description" rows="4" maxlength="600" data-maxlength-counter="600" class="admin-textarea"><?= e($p['description']) ?></textarea>
        <p class="admin-hint text-right" data-counter-for="param-description">0 / 600</p>
      </div>
      <div class="sm:col-span-2">
        <label class="admin-label">Logo</label>
        <div class="flex items-center gap-4">
          <div class="image-preview-frame">
            <img id="logo-preview" src="<?= !empty($p['logo']) ? e(photo_url($p['logo'])) : '' ?>" class="<?= empty($p['logo']) ? 'hidden' : '' ?>" alt="">
            <i class="fa-solid fa-store text-gray-300 text-xl <?= !empty($p['logo']) ? 'hidden' : '' ?>" id="logo-placeholder"></i>
          </div>
          <div>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="admin-input" data-preview="#logo-preview" id="logo-input">
            <p class="image-preview-badge hidden" id="logo-preview-badge"><i class="fa-solid fa-check"></i> Nouveau logo sélectionné</p>
            <p class="admin-hint">Format carré recommandé (PNG, JPG ou WEBP, 4 Mo max).</p>
            <?php if (!empty($p['logo'])): ?>
              <button type="submit" form="form-supprimer-logo" class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-red-600 hover:text-red-700">
                <i class="fa-solid fa-trash"></i> Supprimer le logo actuel
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="admin-card p-7">
    <h2 class="font-display text-lg text-ink mb-5"><i class="fa-solid fa-location-dot text-brand-600 mr-2"></i>Coordonnées</h2>
    <div class="grid sm:grid-cols-2 gap-5">
      <div class="sm:col-span-2">
        <label class="admin-label">Adresse complète</label>
        <input type="text" name="adresse" class="admin-input" value="<?= e($p['adresse']) ?>">
      </div>
      <div>
        <label class="admin-label">Latitude</label>
        <input type="text" name="latitude" class="admin-input" value="<?= e($p['latitude']) ?>" placeholder="Ex : 4.0511">
      </div>
      <div>
        <label class="admin-label">Longitude</label>
        <input type="text" name="longitude" class="admin-input" value="<?= e($p['longitude']) ?>" placeholder="Ex : 9.7679">
        <p class="admin-hint">Trouvez ces coordonnées en cherchant votre restaurant sur Google Maps → clic droit → « Plus d'infos sur cet endroit ».</p>
      </div>
      <div>
        <label class="admin-label">Ville</label>
        <input type="text" name="ville" class="admin-input" value="<?= e($p['ville']) ?>">
      </div>
      <div>
        <label class="admin-label">Horaires d'ouverture</label>
        <input type="text" name="horaires" class="admin-input" value="<?= e($p['horaires']) ?>" placeholder="Ex : Tous les jours · 11h00 – 23h00">
      </div>
      <div>
        <label class="admin-label">Téléphone principal</label>
        <input type="text" name="telephone" class="admin-input" value="<?= e($p['telephone']) ?>">
      </div>
      <div>
        <label class="admin-label">Téléphone secondaire</label>
        <input type="text" name="telephone2" class="admin-input" value="<?= e($p['telephone2']) ?>">
      </div>
      <div class="sm:col-span-2">
        <label class="admin-label">E-mail</label>
        <input type="email" name="email" class="admin-input" value="<?= e($p['email']) ?>">
      </div>
    </div>
  </div>

  <div class="admin-card p-7">
    <h2 class="font-display text-lg text-ink mb-5"><i class="fa-brands fa-whatsapp text-success-600 mr-2"></i>WhatsApp</h2>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="admin-label">Numéro WhatsApp</label>
        <input type="text" name="whatsapp" class="admin-input" value="<?= e($p['whatsapp']) ?>" placeholder="237699000000">
        <p class="admin-hint">Format international, chiffres uniquement, sans « + » ni espace.</p>
      </div>
      <div>
        <label class="admin-label">Message par défaut</label>
        <input type="text" name="message_whatsapp" class="admin-input" value="<?= e($p['message_whatsapp']) ?>">
      </div>
    </div>
  </div>

  <div class="admin-card p-7">
    <h2 class="font-display text-lg text-ink mb-5"><i class="fa-solid fa-share-nodes text-brand-600 mr-2"></i>Réseaux sociaux &amp; caisse</h2>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="admin-label">Lien Facebook</label>
        <input type="url" name="facebook" class="admin-input" value="<?= e($p['facebook']) ?>" placeholder="https://facebook.com/...">
      </div>
      <div>
        <label class="admin-label">Lien Instagram</label>
        <input type="url" name="instagram" class="admin-input" value="<?= e($p['instagram']) ?>" placeholder="https://instagram.com/...">
      </div>
      <div>
        <label class="admin-label">Lien TikTok</label>
        <input type="url" name="tiktok" class="admin-input" value="<?= e($p['tiktok']) ?>" placeholder="https://tiktok.com/@...">
      </div>
      <div>
        <label class="admin-label">Devise</label>
        <input type="text" name="devise" class="admin-input" value="<?= e($p['devise']) ?>" placeholder="FCFA">
      </div>
      <div class="sm:col-span-2">
        <label class="admin-label">Mention légale (pied de page)</label>
        <input type="text" name="mention_legale" class="admin-input" value="<?= e($p['mention_legale']) ?>">
      </div>
    </div>
  </div>

  <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-xl transition">
    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer les paramètres
  </button>
</form>

<!-- Formulaire séparé (un <form> ne peut pas être imbriqué dans un autre) :
     le bouton "Supprimer le logo actuel" ci-dessus le cible via l'attribut
     form="form-supprimer-logo". -->
<form method="post" id="form-supprimer-logo" class="js-confirm-delete hidden" data-nom="le logo actuel">
  <?= csrf_field() ?>
  <input type="hidden" name="supprimer_logo" value="1">
</form>

<script>
  var logoInput = document.getElementById('logo-input');
  if (logoInput) {
    logoInput.addEventListener('change', function () {
      document.getElementById('logo-placeholder').classList.add('hidden');
    });
  }
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>