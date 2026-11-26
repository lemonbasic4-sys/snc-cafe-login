<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

snc_start_session();

$redirectPortal = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && snc_verify_csrf($_POST['csrf_token'] ?? null)) {
    $user = snc_current_user();
    if ($user !== null) {
        $role = (string) ($user['role'] ?? '');
        $redirectPortal = in_array($role, ['cashier', 'kitchen'], true) ? 'staff' : 'admin';

        try {
            $pdo = snc_get_db();
            snc_log_auth_event($pdo, (int) $user['id'], (string) $user['username'], null, 'logout');
        } catch (Throwable $e) {
            // Proceed with session destruction even if audit logging fails.
        }
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }
    session_destroy();
    snc_set_flash('info', 'You have been signed out.', 'Signed Out');
}

header('Location: ' . snc_url('index.php?portal=' . $redirectPortal));
exit;
