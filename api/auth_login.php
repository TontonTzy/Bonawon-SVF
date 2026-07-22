<?php
define('API_REQUEST', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$remember = !empty($input['remember']);

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid username/email or password.']);
    exit();
}

$admin = get_admin_by_username_or_email($username);
if (!$admin || $admin['status'] !== 'active' || is_admin_locked($admin) || !password_verify($password, $admin['password_hash'])) {
    if ($admin) {
        update_login_attempts($admin, false);
    }
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid username/email or password.']);
    exit();
}

if (password_needs_rehash($admin['password_hash'], PASSWORD_BCRYPT)) {
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id');
    $stmt->execute([':hash' => $newHash, ':id' => $admin['id']]);
}

update_login_attempts($admin, true);
$stmt = $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id');
$stmt->execute([':id' => $admin['id']]);
login_admin($admin, $remember);

echo json_encode(['status' => 'success', 'message' => 'Authenticated successfully', 'redirect' => 'admin.html']);
