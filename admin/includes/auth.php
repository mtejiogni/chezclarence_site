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

/* ═══════════════════════════════════════════════════════════════
   LIMITATION DES TENTATIVES (connexion + demandes de réinitialisation)
   ═══════════════════════════════════════════════════════════════ */

/** Adresse IP du visiteur actuel. */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Renvoie le nombre de secondes restantes avant déblocage pour le
 * contexte donné ('connexion' ou 'reinitialisation'), ou null si rien
 * n'est bloqué. Vérifie à la fois l'e-mail visé (si fourni) et l'IP
 * du visiteur — le blocage le plus long des deux s'applique.
 */
function limitation_est_bloque(string $contexte, ?string $email = null): ?int
{
    $valeurs = [limitation_bloque_depuis($contexte, client_ip(), 'ip')];
    if ($email) {
        $valeurs[] = limitation_bloque_depuis($contexte, mb_strtolower($email), 'email');
    }
    $valeurs = array_filter($valeurs, fn ($v) => $v !== null);
    return $valeurs ? max($valeurs) : null;
}

function limitation_bloque_depuis(string $contexte, string $identifiant, string $type): ?int
{
    $stmt = get_pdo()->prepare('
        SELECT bloque_jusqua FROM limitation_tentatives
        WHERE contexte = :c AND identifiant = :id AND type = :type
    ');
    $stmt->execute(['c' => $contexte, 'id' => $identifiant, 'type' => $type]);
    $bloqueJusqua = $stmt->fetchColumn();

    if (!$bloqueJusqua) {
        return null;
    }
    $restant = strtotime($bloqueJusqua) - time();
    return $restant > 0 ? $restant : null;
}

/**
 * Enregistre une tentative échouée pour l'IP courante (et l'e-mail visé,
 * si fourni) — déclenche un blocage temporaire une fois le seuil atteint.
 * La fenêtre glisse automatiquement : si la dernière tentative remonte
 * à plus de $blocageMinutes, le compteur repart de zéro plutôt que de
 * s'accumuler indéfiniment sur plusieurs jours.
 */
function limitation_enregistrer_echec(
    string $contexte,
    ?string $email,
    int $seuilEmail,
    int $seuilIp,
    int $blocageMinutes = 15
): void {
    $pdo = get_pdo();
    $cibles = [[client_ip(), 'ip', $seuilIp]];
    if ($email) {
        $cibles[] = [mb_strtolower($email), 'email', $seuilEmail];
    }

    foreach ($cibles as [$identifiant, $type, $seuil]) {
        $stmt = $pdo->prepare('
            SELECT tentatives, derniere_tentative FROM limitation_tentatives
            WHERE contexte = :c AND identifiant = :id AND type = :type
        ');
        $stmt->execute(['c' => $contexte, 'id' => $identifiant, 'type' => $type]);
        $ligne = $stmt->fetch();

        $expire = $ligne && strtotime($ligne['derniere_tentative']) < strtotime("-{$blocageMinutes} minutes");
        $nouveau = (!$ligne || $expire) ? 1 : ((int) $ligne['tentatives'] + 1);
        $bloqueJusqua = $nouveau >= $seuil ? date('Y-m-d H:i:s', strtotime("+{$blocageMinutes} minutes")) : null;

        $pdo->prepare('
            INSERT INTO limitation_tentatives (contexte, identifiant, type, tentatives, derniere_tentative, bloque_jusqua)
            VALUES (:c, :id, :type, :n1, NOW(), :b1)
            ON DUPLICATE KEY UPDATE tentatives = :n2, derniere_tentative = NOW(), bloque_jusqua = :b2
        ')->execute([
            'c' => $contexte, 'id' => $identifiant, 'type' => $type,
            'n1' => $nouveau, 'b1' => $bloqueJusqua,
            'n2' => $nouveau, 'b2' => $bloqueJusqua,
        ]);
    }
}

/** Réinitialise le compteur (IP + e-mail) après une action réussie. */
function limitation_reinitialiser(string $contexte, ?string $email = null): void
{
    $pdo = get_pdo();
    $pdo->prepare('DELETE FROM limitation_tentatives WHERE contexte = :c AND identifiant = :id AND type = "ip"')
        ->execute(['c' => $contexte, 'id' => client_ip()]);
    if ($email) {
        $pdo->prepare('DELETE FROM limitation_tentatives WHERE contexte = :c AND identifiant = :id AND type = "email"')
            ->execute(['c' => $contexte, 'id' => mb_strtolower($email)]);
    }
}

/* ═══════════════════════════════════════════════════════════════
   RÉCUPÉRATION DE MOT DE PASSE
   ═══════════════════════════════════════════════════════════════ */

/**
 * Crée un jeton de réinitialisation pour un administrateur (valable 1h)
 * et invalide les demandes précédentes non utilisées pour ce compte.
 * Renvoie le jeton EN CLAIR (à mettre dans le lien envoyé par e-mail) —
 * seul son hachage est conservé en base.
 */
function create_password_reset_token(int $adminId): string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    $pdo = get_pdo();
    $pdo->prepare('UPDATE reinitialisations_mot_de_passe SET utilise = 1 WHERE admin_id = :id AND utilise = 0')
        ->execute(['id' => $adminId]);

    $pdo->prepare('
        INSERT INTO reinitialisations_mot_de_passe (admin_id, token_hash, expire_le)
        VALUES (:id, :hash, :expire)
    ')->execute([
        'id' => $adminId,
        'hash' => $hash,
        'expire' => date('Y-m-d H:i:s', strtotime('+1 hour')),
    ]);

    return $token;
}

/** Vérifie un jeton reçu par e-mail. Renvoie l'id admin si valide, sinon null. */
function verifier_token_reinitialisation(string $token): ?int
{
    if ($token === '') {
        return null;
    }
    $hash = hash('sha256', $token);
    $stmt = get_pdo()->prepare('
        SELECT admin_id FROM reinitialisations_mot_de_passe
        WHERE token_hash = :hash AND utilise = 0 AND expire_le > NOW()
        LIMIT 1
    ');
    $stmt->execute(['hash' => $hash]);
    $adminId = $stmt->fetchColumn();
    return $adminId ? (int) $adminId : null;
}

/** Marque un jeton comme utilisé, pour qu'il ne serve qu'une seule fois. */
function consommer_token_reinitialisation(string $token): void
{
    $hash = hash('sha256', $token);
    get_pdo()->prepare('UPDATE reinitialisations_mot_de_passe SET utilise = 1 WHERE token_hash = :hash')
        ->execute(['hash' => $hash]);
}