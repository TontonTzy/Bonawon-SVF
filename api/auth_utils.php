<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

function start_secure_session() {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        session_name('svf_admin_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    if (empty($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }

    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        logout_admin();
        return;
    }

    $_SESSION['last_activity'] = time();
}

function get_current_admin() {
    start_secure_session();
    return $_SESSION['admin_user'] ?? null;
}

function is_admin_logged_in() {
    start_secure_session();
    return !empty($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id']) && ($_SESSION['admin_user']['status'] === 'active');
}

function require_login() {
    start_secure_session();
    if (!is_admin_logged_in()) {
        if (defined('API_REQUEST') && API_REQUEST) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized request.']);
            exit();
        }
        header('Location: /login.html');
        exit();
    }
}

function require_super_admin() {
    require_login();
    $user = get_current_admin();
    if (!$user || $user['role'] !== 'super_admin') {
        if (defined('API_REQUEST') && API_REQUEST) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden.']);
            exit();
        }
        header('Location: /admin.php');
        exit();
    }
}

function login_admin(array $admin, bool $remember = false) {
    start_secure_session();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['admin_user'] = [
        'id' => $admin['id'],
        'first_name' => $admin['first_name'],
        'last_name' => $admin['last_name'],
        'username' => $admin['username'],
        'email' => $admin['email'],
        'role' => $admin['role'],
        'status' => $admin['status'],
    ];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

    if ($remember) {
        setcookie('svf_admin_remember', '1', time() + 60 * 60 * 24 * 30, '/', $_SERVER['HTTP_HOST'] ?? '', isset($_SERVER['HTTPS']), true);
        setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30, '/', $_SERVER['HTTP_HOST'] ?? '', isset($_SERVER['HTTPS']), true);
    }
}

function logout_admin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    setcookie('svf_admin_remember', '', time() - 42000, '/');
    session_destroy();
}

function generate_csrf_token() {
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    start_secure_session();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_admin_by_username_or_email(string $login) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE (username = :login OR email = :login2) AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([':login' => $login, ':login2' => $login]);
    return $stmt->fetch();
}

function update_login_attempts(array $admin, bool $success) {
    global $pdo;
    if (!$admin || empty($admin['id'])) {
        return;
    }

    if ($success) {
        $stmt = $pdo->prepare('UPDATE admin_users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $admin['id']]);
        return;
    }

    $failed = intval($admin['failed_login_attempts'] ?? 0) + 1;
    $lockedUntil = null;
    if ($failed >= 5) {
        $lockedUntil = date('Y-m-d H:i:s', time() + 15 * 60);
    }

    $stmt = $pdo->prepare('UPDATE admin_users SET failed_login_attempts = :failed, locked_until = :locked_until WHERE id = :id');
    $stmt->execute([':failed' => $failed, ':locked_until' => $lockedUntil, ':id' => $admin['id']]);
}

function is_admin_locked(array $admin) {
    if (empty($admin['locked_until'])) {
        return false;
    }
    return strtotime($admin['locked_until']) > time();
}

function get_current_admin_display_name() {
    $admin = get_current_admin();
    if (!$admin) {
        return '';
    }
    return trim($admin['first_name'] . ' ' . $admin['last_name']);
}

function is_last_active_super_admin(int $excludeId = null): bool {
    global $pdo;
    $sql = 'SELECT COUNT(*) as total FROM admin_users WHERE role = "super_admin" AND status = "active" AND deleted_at IS NULL';
    $params = [];
    if ($excludeId !== null) {
        $sql .= ' AND id != :excludeId';
        $params[':excludeId'] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return intval($row['total'] ?? 0) <= 1;
}

function require_csrf() {
    $token = null;
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    } elseif (!empty($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }

    if (!validate_csrf_token($token)) {
        if (defined('API_REQUEST') && API_REQUEST) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
            exit();
        }
        echo '<p>Invalid CSRF token.</p>';
        exit();
    }
}
