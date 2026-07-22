<?php
// API Endpoint: /api/manage_events.php
// Supports POST (create), PUT (update), DELETE (delete) for events
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_utils.php';
require_login();

$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    // CREATE EVENT
    $title = trim($input['title'] ?? '');
    $category = trim($input['category'] ?? 'PARISH EVENT');
    $date_str = trim($input['date_str'] ?? '');
    $day = intval($input['day'] ?? 1);
    $month = strtoupper(trim($input['month'] ?? 'JAN'));
    $year = intval($input['year'] ?? 2026);
    $dow = strtoupper(trim($input['dow'] ?? 'SUN'));
    $time = trim($input['time'] ?? '');
    $description = trim($input['description'] ?? '');
    $location = trim($input['location'] ?? 'Parish Church');

    if (empty($title) || empty($month) || empty($day)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Title, Month, and Day are required."]);
        exit();
    }

    if (empty($date_str)) {
        $date_str = $month . ' ' . str_pad($day, 2, '0', STR_PAD_LEFT);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, category, date_str, day, month, year, dow, time, description, location) VALUES (:title, :category, :date_str, :day, :month, :year, :dow, :time, :description, :location)");
        $stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':date_str' => $date_str,
            ':day' => $day,
            ':month' => $month,
            ':year' => $year,
            ':dow' => $dow,
            ':time' => $time,
            ':description' => $description,
            ':location' => $location
        ]);

        $newEventId = $pdo->lastInsertId();

        echo json_encode([
            "status" => "success",
            "message" => "Event created successfully",
            "id" => $newEventId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to create event: " . $e->getMessage()]);
    }

} elseif ($method === 'PUT') {
    // UPDATE EVENT
    $id = intval($input['id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $category = trim($input['category'] ?? 'PARISH EVENT');
    $date_str = trim($input['date_str'] ?? '');
    $day = intval($input['day'] ?? 1);
    $month = strtoupper(trim($input['month'] ?? 'JAN'));
    $year = intval($input['year'] ?? 2026);
    $dow = strtoupper(trim($input['dow'] ?? 'SUN'));
    $time = trim($input['time'] ?? '');
    $description = trim($input['description'] ?? '');
    $location = trim($input['location'] ?? 'Parish Church');

    if ($id <= 0 || empty($title)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Valid Event ID and Title are required."]);
        exit();
    }

    if (empty($date_str)) {
        $date_str = $month . ' ' . str_pad($day, 2, '0', STR_PAD_LEFT);
    }

    try {
        $stmt = $pdo->prepare("UPDATE events SET title = :title, category = :category, date_str = :date_str, day = :day, month = :month, year = :year, dow = :dow, time = :time, description = :description, location = :location WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':category' => $category,
            ':date_str' => $date_str,
            ':day' => $day,
            ':month' => $month,
            ':year' => $year,
            ':dow' => $dow,
            ':time' => $time,
            ':description' => $description,
            ':location' => $location
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Event updated successfully"
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update event: " . $e->getMessage()]);
    }

} elseif ($method === 'POST' && (strtolower(trim($_POST['action'] ?? $input['action'] ?? '')) === 'delete')) {
        // DELETE EVENT via POST fallback
        $id = intval($_POST['id'] ?? $input['id'] ?? $_REQUEST['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Valid Event ID is required."]);
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode([
                "status" => "success",
                "message" => "Event deleted successfully"
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to delete event: " . $e->getMessage()]);
        }

    } elseif ($method === 'DELETE') {
        // DELETE EVENT
        $id = intval($input['id'] ?? $_GET['id'] ?? $_REQUEST['id'] ?? 0);
        if ($id <= 0 && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $override = strtoupper(trim($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']));
            if ($override === 'DELETE') {
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw, true);
                $id = intval($decoded['id'] ?? $id);
            }
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Valid Event ID is required."]);
            exit();
        }

    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode([
            "status" => "success",
            "message" => "Event deleted successfully"
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to delete event: " . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
