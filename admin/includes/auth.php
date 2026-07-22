<?php
/**
 * admin/includes/auth.php
 * ─────────────────────────────────────────────────────────────
 * Authentification par session pour le panneau d'administration.
 * Aucune dépendance externe : sessions PHP natives + mots de
 * passe hachés avec password_hash()/password_verify().
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('cc_admin_session');
    session_start();
}

/** Nombre de comptes administrateurs existants (sert au premier lancement). */
function admin_count(): int
{
    try {
        return (int) get_pdo()->query('SELECT COUNT(*) FROM administrateurs')->fetchColumn();
    } catch (Throwable $ex) {
        error_log('[admin] admin_count() : ' . $ex->getMessage());
        return 0;
    }
}

/** Tente une connexion. Renvoie true si réussie. */
function admin_attempt_login(string $email, string $password): bool
{
    $stmt = get_pdo()->prepare('SELECT * FROM administrateurs WHERE email = :email AND actif = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['mot_de_passe'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_nom'] = $admin['nom'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];

    $maj = get_pdo()->prepare('UPDATE administrateurs SET derniere_connexion = NOW() WHERE id = :id');
    $maj->execute(['id' => $admin['id']]);

    return true;
}

function admin_logout(): void
{
    $_SESSION = [];

    // Efface aussi le cookie de session côté navigateur, pas seulement
    // les données côté serveur — sinon un cookie périmé traîne inutilement.
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();

    // Nouvelle session vierge, uniquement pour porter le message de
    // confirmation jusqu'à la page de connexion.
    session_start();
    session_regenerate_id(true);
    flash_set('success', 'Vous avez été déconnecté avec succès.');
}

function admin_is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** À placer en haut de chaque page protégée du back-office. */
function require_login(): void
{
    if (admin_count() === 0) {
        header('Location: install.php');
        exit;
    }
    if (!admin_is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    // Auto-guérison : si le rôle n'est pas (ou plus) présent dans la
    // session — typiquement une session ouverte avant l'ajout de la
    // gestion des rôles, ou après une modification faite par un autre
    // administrateur — on recharge les informations depuis la base au
    // lieu de laisser current_admin_role() retomber silencieusement
    // sur son repli le moins privilégié.
    if (!isset($_SESSION['admin_role'])) {
        refresh_admin_session();
    }
}

/**
 * Recharge nom, e-mail et rôle depuis la base pour l'administrateur
 * actuellement en session. Déconnecte automatiquement si le compte a
 * été supprimé ou désactivé entre-temps.
 */
function refresh_admin_session(): void
{
    if (empty($_SESSION['admin_id'])) {
        return;
    }

    $stmt = get_pdo()->prepare('SELECT nom, email, role, actif FROM administrateurs WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || (int) $admin['actif'] !== 1) {
        admin_logout();
        header('Location: login.php');
        exit;
    }

    $_SESSION['admin_nom'] = $admin['nom'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
}

function current_admin_nom(): string
{
    return $_SESSION['admin_nom'] ?? 'Administrateur';
}

/**
 * Rôle de l'administrateur connecté : 'Administrateur' ou 'Éditeur'.
 * Le repli sur 'Éditeur' (rôle le moins privilégié) est volontaire :
 * en cas de donnée manquante, on préfère restreindre l'accès plutôt
 * que de l'accorder par erreur. En pratique, require_login() garantit
 * que la session est toujours à jour sur toute page protégée.
 */
function current_admin_role(): string
{
    return $_SESSION['admin_role'] ?? 'Éditeur';
}

function is_administrateur(): bool
{
    return current_admin_role() === 'Administrateur';
}

/**
 * À placer en plus de require_login() sur les pages réservées aux
 * administrateurs (gestion des utilisateurs, notamment).
 */
function require_administrateur(): void
{
    if (!is_administrateur()) {
        flash_set('error', "Cette section est réservée aux administrateurs.");
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Nombre de comptes actifs ayant le rôle Administrateur, en excluant
 * éventuellement un identifiant (utile pour vérifier qu'une action ne
 * laisserait pas le site sans aucun administrateur actif).
 */
function nb_administrateurs_actifs(?int $excludeId = null): int
{
    $sql = "SELECT COUNT(*) FROM administrateurs WHERE role = 'Administrateur' AND actif = 1";
    $params = [];
    if ($excludeId) {
        $sql .= ' AND id != :exclude';
        $params['exclude'] = $excludeId;
    }
    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/* ── Jeton CSRF ──────────────────────────────────────────────── */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Session expirée ou requête invalide. Merci de recharger la page et réessayer.');
    }
}

/* ── Messages flash (confirmation/erreur après redirection) ──── */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}