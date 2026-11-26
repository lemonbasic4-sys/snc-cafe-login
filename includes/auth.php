<?php
declare(strict_types=1);

/**
 * Server-side session, CSRF, and role-based authorization helpers.
 */

function snc_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('snc_cafe_session');
    session_start();
}

function snc_csrf_token(): string
{
    snc_start_session();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function snc_verify_csrf(?string $token): bool
{
    snc_start_session();
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function snc_current_user(): ?array
{
    snc_start_session();
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }
    return $_SESSION['user'];
}

function snc_base_path(): string
{
    $projectRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    $docRoot = str_replace('\\', '/', (string) realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));

    if ($docRoot !== '' && str_starts_with(strtolower($projectRoot), strtolower($docRoot))) {
        $relative = substr($projectRoot, strlen($docRoot));
        return rtrim($relative, '/');
    }

    return '';
}

function snc_url(string $path): string
{
    $base = snc_base_path();
    $clean = ltrim($path, '/');
    return ($base !== '' ? $base : '') . '/' . $clean;
}

function snc_role_home(string $role): string
{
    return match ($role) {
        'admin' => 'admin/dashboard.php',
        'cashier' => 'cashier/index.php',
        'kitchen' => 'kitchen/index.php',
        default => 'index.php',
    };
}

function snc_portal_allows_role(string $portal, string $role): bool
{
    if ($portal === 'admin') {
        return $role === 'admin';
    }
    if ($portal === 'staff') {
        return in_array($role, ['cashier', 'kitchen'], true);
    }
    return false;
}

function snc_require_auth(array $allowedRoles = []): array
{
    $user = snc_current_user();
    if ($user === null) {
        header('Location: ' . snc_url('index.php'));
        exit;
    }

    if ($allowedRoles !== [] && !in_array($user['role'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Denied</title></head><body>';
        echo '<h1>403 — Access Denied</h1><p>Your account role does not have permission to view this page.</p>';
        echo '<p><a href="' . htmlspecialchars(snc_url(snc_role_home((string) ($user['role'] ?? ''))), ENT_QUOTES, 'UTF-8') . '">Return to your workspace</a></p>';
        echo '</body></html>';
        exit;
    }

    return $user;
}

function snc_log_auth_event(
    PDO $pdo,
    ?int $userId,
    string $usernameAttempted,
    ?string $portalSelected,
    string $eventType
): void {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

    $stmt = $pdo->prepare(
        'INSERT INTO auth_audit_logs (user_id, username_attempted, portal_selected, event_type, ip_address, user_agent)
         VALUES (:user_id, :username_attempted, :portal_selected, :event_type, :ip_address, :user_agent)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':username_attempted' => substr($usernameAttempted, 0, 60),
        ':portal_selected' => in_array($portalSelected, ['admin', 'staff'], true) ? $portalSelected : null,
        ':event_type' => $eventType,
        ':ip_address' => substr($ip, 0, 45),
        ':user_agent' => $userAgent,
    ]);
}

function snc_set_flash(string $type, string $message, string $title = ''): void
{
    snc_start_session();
    $_SESSION['snc_flash'] = [
        'type' => in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info',
        'title' => $title,
        'message' => $message,
    ];
}

function snc_pop_flash(): ?array
{
    snc_start_session();
    if (empty($_SESSION['snc_flash']) || !is_array($_SESSION['snc_flash'])) {
        return null;
    }
    $flash = $_SESSION['snc_flash'];
    unset($_SESSION['snc_flash']);
    return $flash;
}
