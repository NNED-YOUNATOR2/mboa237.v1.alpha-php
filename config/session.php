<?php
// ============================================================
//  config/session.php — Sessions + constantes globales
// ============================================================

define('APP_NAME',    'Mboa237');
define('APP_VERSION', '1.0.0');

// BASE_URL détecté automatiquement — fonctionne sur WAMP et en production
if (!defined('BASE_URL')) {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '/'));
    $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $base    = '/' . trim(str_replace($docRoot, '', $appRoot), '/');
    define('BASE_URL', $scheme . '://' . $host . $base);
}

// Démarrer la session si pas encore démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7,  // 7 jours
        'path'     => '/',
        'secure'   => false,      // true en production HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Helpers session ──────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function currentRole(): string {
    return $_SESSION['user']['role'] ?? '';
}

function requireLogin(string $redirect = null): void {
    if (!isLoggedIn()) {
        header('Location: ' . ($redirect ?? BASE_URL . '/connexion.php'));
        exit;
    }
}

function requireRole(array $roles, string $redirect = null): void {
    requireLogin();
    if (!in_array(currentRole(), $roles)) {
        header('Location: ' . ($redirect ?? BASE_URL . '/index.php'));
        exit;
    }
}

function redirectIfLoggedIn(): void {
    if (!isLoggedIn()) return;
    $routes = [
        'admin'    => BASE_URL . '/admin/dashboard.php',
        'createur' => BASE_URL . '/createur/dashboard.php',
        'apprenant'=> BASE_URL . '/apprenant/dashboard.php',
    ];
    $role = currentRole();
    header('Location: ' . ($routes[$role] ?? BASE_URL . '/index.php'));
    exit;
}

// ── Helpers flash messages ───────────────────────────────────

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $colors = ['success' => '#e8f5e9', 'error' => '#ffebee', 'info' => '#e3f2fd'];
    $borders = ['success' => '#4caf50', 'error' => '#f44336', 'info' => '#2196f3'];
    $bg  = $colors[$flash['type']]  ?? '#f5f5f5';
    $brd = $borders[$flash['type']] ?? '#ccc';
    return sprintf(
        '<div style="background:%s;border-left:4px solid %s;padding:.9rem 1.2rem;border-radius:8px;margin-bottom:1.2rem;font-size:.92rem;">%s</div>',
        $bg, $brd, htmlspecialchars($flash['msg'])
    );
}

// ── Helpers utilitaires ──────────────────────────────────────

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $url): void {
    // Si l'URL est relative (pas de http/https et pas de /),
    // on la rend absolue en se basant sur le dossier de la page courante
    if (!str_starts_with($url, 'http') && !str_starts_with($url, '/')) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir    = rtrim(dirname($script), '/');
        $url    = $dir . '/' . $url;
    }
    header('Location: ' . $url);
    exit;
}
