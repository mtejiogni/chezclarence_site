<?php
/**
 * config/env.php
 * ─────────────────────────────────────────────────────────────
 * Chargeur minimal de fichier .env, sans dépendance Composer.
 * Ce site est volontairement autonome (pas de framework) : ce
 * petit parseur suffit largement à nos besoins (quelques
 * variables de connexion à la base de données).
 * ─────────────────────────────────────────────────────────────
 */

function load_env(string $path): void
{
    static $loaded = false;
    if ($loaded || !is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Retire d'éventuels guillemets englobants
        if (strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }

    $loaded = true;
}

/**
 * Récupère une variable d'environnement avec valeur par défaut.
 */
function env(string $key, $default = null)
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

load_env(__DIR__ . '/../.env');
