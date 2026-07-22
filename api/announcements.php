<?php
// API Endpoint: GET /api/announcements.php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, title, category, description, image, link, created_at FROM announcements WHERE is_active = 1 ORDER BY id DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "count" => count($announcements),
        "data" => $announcements
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to retrieve announcements: " . $e->getMessage()
    ]);
}
