<?php
// API Endpoint: POST /api/contact.php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

// Support both JSON input and standard form-urlencoded POST
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input && !empty($_POST)) {
    $input = $_POST;
}

$name = isset($input['name']) ? trim($input['name']) : (isset($input['from_name']) ? trim($input['from_name']) : '');
$email = isset($input['email']) ? trim($input['email']) : (isset($input['from_email']) ? trim($input['from_email']) : '');
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Please fill in all required fields (Name, Email, Subject, Message)."
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Please provide a valid email address."
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (:name, :email, :phone, :subject, :message)");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':subject' => $subject,
        ':message' => $message
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Message sent and recorded successfully!",
        "id" => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to record message: " . $e->getMessage()
    ]);
}
