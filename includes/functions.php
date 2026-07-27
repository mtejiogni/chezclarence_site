<?php
/**
 * includes/functions.php
 * ─────────────────────────────────────────────────────────────
 * Fonctions d'accès aux données du site public. Toutes les
 * données affichées (paramètres, menu, services, slides du hero,
 * statistiques, valeurs) proviennent désormais de la base CMS
 * dédiée à ce site (voir database/schema.sql) et sont modifiables
 * par l'administrateur via /admin, sans toucher au code.
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/database.php';

/** Échappement HTML court, à utiliser systématiquement en sortie. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Charge la ligne unique de la table `parametres`.
 * Renvoie toujours un tableau complet (valeurs de secours si la
 * table est vide ou la connexion indisponible).
 */
function get_parametres(): array
{
    static $parametres = null;
    if ($parametres !== null) {
        return $parametres;
    }

    $defaults = [
        'id' => 0,
        'entreprise' => 'Chez Clarence',
        'nom_restaurant' => 'Chez Clarence',
        'slogan' => 'Restaurant · Snack · Grill — depuis 1990',
        'description' => "Une cuisine généreuse, des grillades préparées avec passion et un accueil qui vous fait sentir chez vous.",
        'logo' => null,
        'adresse' => 'Akwa, Douala, Cameroun',
        'latitude' => '4.0511',
        'longitude' => '9.7679',
        'telephone' => '', 'telephone2' => '', 'email' => '',
        'ville' => 'Douala',
        'horaires' => 'Tous les jours · 11h00 – 23h00',
        'whatsapp' => '', 'message_whatsapp' => 'Bonjour, je vous contacte depuis votre site web.',
        'facebook' => '', 'instagram' => '', 'tiktok' => '',
        'devise' => 'FCFA', 'mention_legale' => '',
    ];

    try {
        $row = get_pdo()->query('SELECT * FROM parametres ORDER BY id ASC LIMIT 1')->fetch();
        $parametres = $row ? array_merge($defaults, array_filter($row, fn ($v) => $v !== null && $v !== '')) : $defaults;
    } catch (Throwable $ex) {
        error_log('[chez-clarence] get_parametres() : ' . $ex->getMessage());
        $parametres = $defaults;
    }

    return $parametres;
}

/**
 * Chemin de base du site (avec slash final), déterminé dynamiquement à
 * partir du script actuellement exécuté — pour que les URLs générées
 * (photo_url, etc.) fonctionnent aussi bien depuis une page du site
 * public (ex: /index.php) que depuis le back-office (ex: /admin/menus.php),
 * et quel que soit le sous-dossier dans lequel le site est déployé
 * (racine du domaine, ou ex: /chezclarence/).
 */
function site_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '/';

    // Si le script courant se trouve dans /admin/ (ou un de ses
    // sous-dossiers), la racine du site est un niveau au-dessus.
    if (preg_match('#^(.*)/admin(/|$)#', $script, $m)) {
        $base = $m[1] . '/';
    } else {
        $base = rtrim(dirname($script), '/') . '/';
    }

    // Normalise un éventuel double slash (ex: si le site est à la racine)
    $base = preg_replace('#/+#', '/', $base);

    return $base;
}

/** URL publique d'un fichier uploadé (logo, photo de plat, image de slide...). */
function photo_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return site_base_path() . 'uploads/' . ltrim($path, '/');
}

/** Nettoie un numéro de téléphone pour un lien wa.me (chiffres uniquement). */
function clean_phone(?string $phone): string
{
    return preg_replace('/\D+/', '', $phone ?? '');
}

/** Construit un lien wa.me avec un message pré-rempli. */
function whatsapp_link(string $message = ''): string
{
    $p = get_parametres();
    $numero = clean_phone($p['whatsapp'] ?: $p['telephone']);
    if (!$numero) {
        return '#';
    }
    $texte = $message ?: ($p['message_whatsapp'] ?: 'Bonjour, je vous contacte depuis votre site web.');
    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($texte);
}

/** Lien Google Maps pour lancer un itinéraire GPS vers le restaurant. */
function itineraire_link(): string
{
    $p = get_parametres();
    if (empty($p['latitude']) || empty($p['longitude'])) {
        return '#';
    }
    return 'https://www.google.com/maps/dir/?api=1&destination=' . $p['latitude'] . ',' . $p['longitude'];
}

/** Catégories actives, triées, avec leurs plats actifs. */
function get_categories_with_menus(): array
{
    try {
        $pdo = get_pdo();

        $categories = $pdo->query("
            SELECT * FROM categories WHERE statut = 'Activé' ORDER BY ordre ASC, intitule ASC
        ")->fetchAll();

        $menuStmt = $pdo->prepare("
            SELECT * FROM menus WHERE categorie_id = :id AND statut = 'Activé' ORDER BY ordre ASC, intitule ASC
        ");

        $resultat = [];
        foreach ($categories as $cat) {
            $menuStmt->execute(['id' => $cat['id']]);
            $menus = $menuStmt->fetchAll();
            if (!$menus) {
                continue;
            }
            foreach ($menus as &$m) {
                $m['prix'] = (float) $m['prix'];
                $m['etoiles'] = (int) $m['etoiles'];
            }
            unset($m);
            $cat['menus'] = $menus;
            $resultat[] = $cat;
        }
        return $resultat;
    } catch (Throwable $ex) {
        error_log('[chez-clarence] get_categories_with_menus() : ' . $ex->getMessage());
        return [];
    }
}

/** Services actifs, triés, avec leurs points (un par ligne) et leur lien WhatsApp prêt à l'emploi. */
function get_services(): array
{
    try {
        $rows = get_pdo()->query("SELECT * FROM services WHERE statut = 'Activé' ORDER BY ordre ASC")->fetchAll();
    } catch (Throwable $ex) {
        error_log('[chez-clarence] get_services() : ' . $ex->getMessage());
        return [];
    }

    foreach ($rows as &$s) {
        $s['points'] = array_values(array_filter(array_map('trim', explode("\n", (string) $s['points']))));
        $s['lien_whatsapp'] = whatsapp_link((string) $s['message_whatsapp']);
    }
    unset($s);

    return $rows;
}

/** Un service précis par son slug (utilisé si besoin d'une page dédiée). */
function get_service_by_slug(string $slug): ?array
{
    foreach (get_services() as $s) {
        if ($s['slug'] === $slug) {
            return $s;
        }
    }
    return null;
}

/** Slides actifs du hero, avec les textes de $parametres injectés si {{placeholders}} présents. */
function get_hero_slides(): array
{
    $p = get_parametres();
    $remplacer = function (?string $texte) use ($p) {
        if (!$texte) {
            return $texte;
        }
        return strtr($texte, [
            '{{nom_restaurant}}' => $p['nom_restaurant'],
            '{{slogan}}' => $p['slogan'],
            '{{description}}' => $p['description'],
            '{{ville}}' => $p['ville'],
        ]);
    };

    try {
        $rows = get_pdo()->query("SELECT * FROM hero_slides WHERE statut = 'Activé' ORDER BY ordre ASC")->fetchAll();
    } catch (Throwable $ex) {
        error_log('[chez-clarence] get_hero_slides() : ' . $ex->getMessage());
        return [];
    }

    foreach ($rows as &$slide) {
        $slide['titre'] = $remplacer($slide['titre']);
        $slide['sous_titre'] = $remplacer($slide['sous_titre']);
        $slide['description'] = $remplacer($slide['description']);

        $slide['bouton1_lien'] = resoudre_lien_bouton($slide['bouton1_type'], $slide['bouton1_valeur']);
        $slide['bouton2_lien'] = resoudre_lien_bouton($slide['bouton2_type'], $slide['bouton2_valeur']);
    }
    unset($slide);

    return $rows;
}

/** Transforme (type, valeur) d'un bouton de slide en URL exploitable. */
function resoudre_lien_bouton(string $type, ?string $valeur): string
{
    return match ($type) {
        'whatsapp' => whatsapp_link((string) $valeur),
        'url' => (string) $valeur,
        default => $valeur ?: '#', // 'ancre' : déjà au format #id
    };
}

/** Statistiques actives (compteurs animés), avec calcul automatique optionnel. */
function get_stats(): array
{
    try {
        $rows = get_pdo()->query("SELECT * FROM statistiques WHERE statut = 'Activé' ORDER BY ordre ASC")->fetchAll();
    } catch (Throwable $ex) {
        error_log('[chez-clarence] get_stats() : ' . $ex->getMessage());
        return [];
    }

    foreach ($rows as &$s) {
        if ($s['calcul_auto'] === 'annees_depuis_1990') {
            $s['valeur'] = max(1, (int) date('Y') - 1990);
        }
        $s['valeur'] = (int) $s['valeur'];
    }
    unset($s);

    return $rows;
}

/** Valeurs du restaurant (section "À propos") actives et triées. */
function get_valeurs(): array
{
    try {
        return get_pdo()->query("SELECT * FROM valeurs WHERE statut = 'Activé' ORDER BY ordre ASC")->fetchAll();
    } catch (Throwable $ex) {
        error_log('[chez-clarence] get_valeurs() : ' . $ex->getMessage());
        return [];
    }
}

/**
 * Vérifie si le site a été correctement installé : la base de données
 * est joignable, et au moins un compte administrateur existe.
 * Utilisée par index.php pour rediriger automatiquement vers l'assistant
 * d'installation tant que ce n'est pas le cas.
 */
function site_est_installe(): bool
{
    try {
        $pdo = get_pdo();
        $compte = $pdo->query('SELECT COUNT(*) FROM administrateurs')->fetchColumn();
        return ((int) $compte) > 0;
    } catch (Throwable $ex) {
        // Base injoignable, .env mal configuré, ou schema.sql pas encore
        // importé (table "administrateurs" inexistante) : dans tous les
        // cas, le site n'est pas installé.
        return false;
    }
}