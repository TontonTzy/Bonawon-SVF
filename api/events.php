<?php
// API Endpoint: GET /api/events.php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

$month = isset($_GET['month']) ? strtoupper(trim($_GET['month'])) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;

try {
    $sql = "SELECT id, title, category, date_str as date, day, month, year, dow, time, description, location FROM events WHERE is_active = 1";
    $params = [];

    if ($month) {
        $sql .= " AND UPPER(month) = :month";
        $params[':month'] = $month;
    }

    if ($year) {
        $sql .= " AND year = :year";
        $params[':year'] = $year;
    }

    $sql .= " ORDER BY year ASC, FIELD(UPPER(month), 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC') ASC, day ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "count" => count($events),
        "data" => $events
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to retrieve events: " . $e->getMessage()
    ]);
}
