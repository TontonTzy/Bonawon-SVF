<?php
define('API_REQUEST', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_utils.php';
require_super_admin();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    try {
        $stmt = $pdo->prepare('SELECT id, first_name, last_name, username, email, role, status, last_login_at, created_at FROM admin_users WHERE deleted_at IS NULL ORDER BY created_at DESC');
        $stmt->execute();
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to load accounts: ' . $e->getMessage()]);
        exit();
    }
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (!validate_csrf_token($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit();
}

$action = strtolower(trim($input['action'] ?? ''));
$adminId = intval($input['id'] ?? 0);
$current = get_current_admin();

function validate_admin_payload(array $data, bool $isNew = true): array {
    $errors = [];
    if (empty($data['first_name'])) {
        $errors[] = 'First name is required.';
    }
    if (empty($data['last_name'])) {
        $errors[] = 'Last name is required.';
    }
    if (empty($data['username']) || !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $data['username'])) {
        $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, underscores, dashes, or periods.';
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if ($isNew && empty($data['password'])) {
        $errors[] = 'Password is required for a new administrator.';
    }
    if (!empty($data['password']) && strlen($data['password']) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    }
    if (!empty($data['password']) && ($data['password'] !== ($data['password_confirm'] ?? ''))) {
        $errors[] = 'Password and confirmation must match.';
    }
    return $errors;
}

function has_unique_admin_values(PDO $pdo, string $username, string $email, int $excludeId = 0): array {
    $stmt = $pdo->prepare('SELECT id, username, email FROM admin_users WHERE deleted_at IS NULL AND (username = :username OR email = :email) AND id != :excludeId');
    $stmt->execute([':username' => $username, ':email' => $email, ':excludeId' => $excludeId]);
    return $stmt->fetchAll();
}

if ($action === 'create' || $action === 'update') {
    $errors = validate_admin_payload($input, $action === 'create');
    if ($errors) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit();
    }

    $conflicts = has_unique_admin_values($pdo, $input['username'], $input['email'], $action === 'update' ? $adminId : 0);
    foreach ($conflicts as $conflict) {
        if ($conflict['username'] === $input['username']) {
            $errors[] = 'Username already exists.';
        }
        if ($conflict['email'] === $input['email']) {
            $errors[] = 'Email address already exists.';
        }
    }
    if ($errors) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit();
    }

    $passwordHash = null;
    if (!empty($input['password'])) {
        $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT);
    }

    if ($action === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO admin_users (first_name, last_name, username, email, password_hash, role, status, created_at, updated_at) VALUES (:first_name, :last_name, :username, :email, :password_hash, :role, :status, NOW(), NOW())');
            $stmt->execute([
                ':first_name' => $input['first_name'],
                ':last_name' => $input['last_name'],
                ':username' => $input['username'],
                ':email' => $input['email'],
                ':password_hash' => $passwordHash,
                ':role' => $input['role'],
                ':status' => $input['status'],
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Administrator created successfully.']);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create admin account: ' . $e->getMessage()]);
            exit();
        }
    }

    if ($action === 'update') {
        if ($adminId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing administrator ID.']);
            exit();
        }

        if ($current['id'] === $adminId && $input['status'] === 'inactive') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'You cannot deactivate your own account while logged in.']);
            exit();
        }

        if ($current['id'] === $adminId && $current['role'] !== 'super_admin' && $input['role'] === 'super_admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid role change.']);
            exit();
        }

        if ($input['role'] !== 'super_admin' && $current['id'] === $adminId && $current['role'] === 'super_admin' && $input['status'] !== 'active') {
            if (is_last_active_super_admin($adminId)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Cannot deactivate the last active Super Admin account.']);
                exit();
            }
        }

        try {
            $sql = 'UPDATE admin_users SET first_name = :first_name, last_name = :last_name, username = :username, email = :email, role = :role, status = :status, updated_at = NOW()';
            $params = [
                ':first_name' => $input['first_name'],
                ':last_name' => $input['last_name'],
                ':username' => $input['username'],
                ':email' => $input['email'],
                ':role' => $input['role'],
                ':status' => $input['status'],
                ':id' => $adminId,
            ];
            if ($passwordHash) {
                $sql .= ', password_hash = :password_hash';
                $params[':password_hash'] = $passwordHash;
            }
            $sql .= ' WHERE id = :id AND deleted_at IS NULL';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['status' => 'success', 'message' => 'Administrator updated successfully.']);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update admin account: ' . $e->getMessage()]);
            exit();
        }
    }
}

if ($action === 'delete') {
    if ($adminId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing administrator ID.']);
        exit();
    }

    if ($current['id'] === $adminId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account while logged in.']);
        exit();
    }

    if (is_last_active_super_admin($adminId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete the last active Super Admin account.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare('UPDATE admin_users SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $adminId]);
        echo json_encode(['status' => 'success', 'message' => 'Administrator deleted successfully.']);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete admin account: ' . $e->getMessage()]);
        exit();
    }
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
