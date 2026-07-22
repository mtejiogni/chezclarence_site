<?php
/**
 * admin/includes/upload.php
 * Gère l'upload d'une image (logo, photo de plat/catégorie, image de slide),
 * avec validation du type et de la taille, nom de fichier unique, et
 * suppression optionnelle de l'ancien fichier remplacé.
 */

/**
 * @param string      $fieldName Nom du champ <input type="file">
 * @param string      $subfolder Sous-dossier de uploads/ (ex: 'menus')
 * @param string|null $ancien    Chemin relatif de l'ancien fichier à supprimer, le cas échéant
 * @return array{ok:bool, path:?string, error:?string}
 */
function handle_upload(string $fieldName, string $subfolder, ?string $ancien = null): array
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'error' => null]; // aucun fichier envoyé : rien à faire
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => "Erreur lors de l'envoi du fichier (code {$file['error']})."];
    }

    $maxSize = 4 * 1024 * 1024; // 4 Mo
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'path' => null, 'error' => 'Le fichier est trop volumineux (4 Mo maximum).'];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'path' => null, 'error' => 'Format non autorisé (JPG, PNG ou WEBP uniquement).'];
    }

    $dir = __DIR__ . '/../../uploads/' . $subfolder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $nomFichier = uniqid($subfolder . '_', true) . '.' . $allowed[$mime];
    $cheminAbsolu = $dir . '/' . $nomFichier;

    if (!move_uploaded_file($file['tmp_name'], $cheminAbsolu)) {
        return ['ok' => false, 'path' => null, 'error' => "Impossible d'enregistrer le fichier sur le serveur."];
    }

    // Supprime l'ancien fichier remplacé, s'il existe et appartient bien à uploads/
    if ($ancien) {
        $ancienAbsolu = __DIR__ . '/../../uploads/' . ltrim($ancien, '/');
        if (is_file($ancienAbsolu)) {
            @unlink($ancienAbsolu);
        }
    }

    return ['ok' => true, 'path' => $subfolder . '/' . $nomFichier, 'error' => null];
}
