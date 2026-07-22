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
if (!validate_csrf_token($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit();
}

logout_admin();

echo json_encode(['status' => 'success', 'message' => 'Logged out successfully', 'redirect' => '/login.html']);
