<?php
/**
 * config/database.php
 * ─────────────────────────────────────────────────────────────
 * Connexion PDO à la base MySQL. Ce site public n'écrit JAMAIS
 * dans la base — il ne fait que LIRE les paramètres, catégories
 * et menus pour affichage. Toute action du visiteur (commander,
 * réserver, contacter) passe par WhatsApp, jamais par une
 * insertion en base depuis ce site.
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/env.php';

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $db   = env('DB_DATABASE', 'chezclarence_cms');
    $user = env('DB_USERNAME', 'root');
    $pass = env('DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        if (env('APP_ENV', 'local') === 'local') {
            die('Connexion à la base de données impossible : ' . htmlspecialchars($e->getMessage()));
        }
        error_log('[chez-clarence] Connexion DB échouée : ' . $e->getMessage());
        die('Le site est momentanément indisponible. Merci de réessayer dans quelques instants.');
    }

    return $pdo;
}
