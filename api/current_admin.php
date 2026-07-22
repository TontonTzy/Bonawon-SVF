<?php
define('API_REQUEST', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_utils.php';

require_login();
$current = get_current_admin();
if (!$current) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit();
}

echo json_encode([
    'status' => 'success',
    'data' => [
        'id' => $current['id'],
        'username' => $current['username'],
        'role' => $current['role'],
        'first_name' => $current['first_name'],
        'last_name' => $current['last_name'],
    ],
]);
