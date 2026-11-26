<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');

function snc_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    snc_json_response(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

snc_start_session();

$csrfToken = $_POST['csrf_token'] ?? null;
if (!snc_verify_csrf(is_string($csrfToken) ? $csrfToken : null)) {
    snc_json_response(403, ['ok' => false, 'message' => 'Session expired. Please refresh the page and try again.']);
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$portal = trim((string) ($_POST['portal'] ?? 'admin'));

if (!in_array($portal, ['admin', 'staff'], true)) {
    snc_json_response(400, ['ok' => false, 'message' => 'Invalid login portal selected.']);
}

if ($username === '' || $password === '') {
    snc_json_response(422, ['ok' => false, 'message' => 'Please enter both username and password.']);
}

try {
    $pdo = snc_get_db();
} catch (Throwable $e) {
    snc_json_response(503, ['ok' => false, 'message' => 'Authentication service is temporarily unavailable.']);
}

$stmt = $pdo->prepare(
    'SELECT id, username, full_name, role, password_hash, is_active
     FROM users
     WHERE username = :username
     LIMIT 1'
);
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    snc_log_auth_event($pdo, $user ? (int) $user['id'] : null, $username, $portal, 'login_failed');
    snc_json_response(401, ['ok' => false, 'message' => 'Invalid username or password.']);
}

if ((int) $user['is_active'] !== 1) {
    snc_log_auth_event($pdo, (int) $user['id'], $username, $portal, 'inactive_account');
    snc_json_response(403, ['ok' => false, 'message' => 'This account is inactive. Please contact the administrator.']);
}

$role = (string) $user['role'];
if (!snc_portal_allows_role($portal, $role)) {
    snc_log_auth_event($pdo, (int) $user['id'], $username, $portal, 'portal_mismatch');
    $message = $portal === 'admin'
        ? 'Staff accounts must sign in through the Staff Portal.'
        : 'Admin accounts must sign in through the Admin Portal.';
    snc_json_response(403, ['ok' => false, 'message' => $message]);
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (int) $user['id'],
    'username' => (string) $user['username'],
    'full_name' => (string) $user['full_name'],
    'role' => $role,
];

$updateStmt = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
$updateStmt->execute([':id' => (int) $user['id']]);

snc_log_auth_event($pdo, (int) $user['id'], $username, $portal, 'login_success');
snc_set_flash('success', 'Welcome back, ' . (string) $user['full_name'] . '.', 'Signed In');

snc_json_response(200, [
    'ok' => true,
    'message' => 'Signed in successfully.',
    'redirect' => snc_role_home($role),
]);
