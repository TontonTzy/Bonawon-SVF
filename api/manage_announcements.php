<?php
// API Endpoint: /api/manage_announcements.php
// Handles Announcement Creation, Image Uploads, Updates, and Deletions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_utils.php';
require_login();

$target_dir = __DIR__ . '/../images/uploads/';
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Valid Announcement ID is required."]);
            exit();
        }

        try {
            $stmt = $pdo->prepare("SELECT image FROM announcements WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $imagePath = $row['image'] ?? '';

            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($imagePath && strpos($imagePath, 'images/uploads/') === 0) {
                $imageFile = realpath(__DIR__ . '/../' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath));
                $uploadsDir = realpath(__DIR__ . '/../images/uploads');
                if ($imageFile && $uploadsDir && str_starts_with($imageFile, $uploadsDir) && file_exists($imageFile)) {
                    @unlink($imageFile);
                }
            }

            echo json_encode(["status" => "success", "message" => "Announcement deleted successfully"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to delete: " . $e->getMessage()]);
        }
        exit();
    }

    // Save (Create or Update)
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'PARISH ANNOUNCEMENT');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '#');
    $existing_image = trim($_POST['existing_image'] ?? 'images/mamamarylindogon.jpg');
    $image_path = $existing_image;

    if (empty($title) || empty($description)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Title and Description are required."]);
        exit();
    }

    // Handle File Upload if provided
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'announcement_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $destPath = $target_dir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_path = 'images/uploads/' . $newFileName;
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Failed to save uploaded image file."]);
                exit();
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF."]);
            exit();
        }
    }

    if ($id > 0) {
        // UPDATE
        try {
            $stmt = $pdo->prepare("UPDATE announcements SET title = :title, category = :category, description = :description, image = :image, link = :link WHERE id = :id");
            $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':category' => $category,
                ':description' => $description,
                ':image' => $image_path,
                ':link' => $link
            ]);
            echo json_encode([
                "status" => "success",
                "message" => "Announcement updated successfully",
                "image" => $image_path
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update announcement: " . $e->getMessage()]);
        }
    } else {
        // CREATE
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, category, description, image, link) VALUES (:title, :category, :description, :image, :link)");
            $stmt->execute([
                ':title' => $title,
                ':category' => $category,
                ':description' => $description,
                ':image' => $image_path,
                ':link' => $link
            ]);
            echo json_encode([
                "status" => "success",
                "message" => "Announcement created successfully",
                "id" => $pdo->lastInsertId(),
                "image" => $image_path
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to create announcement: " . $e->getMessage()]);
        }
    }
} elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = intval($input['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Valid Announcement ID is required."]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(["status" => "success", "message" => "Announcement deleted successfully"]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to delete: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
