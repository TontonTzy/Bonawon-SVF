<?php
// Centralized Database Configuration for Local XAMPP or Aiven Cloud MySQL

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Connection Settings
// You can set environment variables or edit these values directly for Aiven Cloud MySQL
$db_host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: "kafka-28ed9776-judilla-18ba.b.aivencloud.com"; // e.g. mysql-123456-your-project.aivencloud.com
$db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: "16873";      // e.g. 11111 (Aiven custom port)
$db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: "defaultdb"; // Aiven default db is 'defaultdb' or 'svf_parish_db'
$db_user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: "avnadmin";      // e.g. avnadmin
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : "AVNS_j93FpbMA_ggpgaF46Do");
$db_ssl  = getenv('DB_SSL') !== false ? filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN) : ($db_host !== "localhost" && $db_host !== "127.0.0.1");

// Path to SSL CA certificate (Aiven requires SSL; ca.pem can be downloaded from Aiven console)
$ca_cert_path = getenv('DB_SSL_CA') ?: __DIR__ . '/ca.pem';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 3,
];

// Aiven MySQL requires SSL/TLS encryption
if ($db_ssl) {
    if (file_exists($ca_cert_path)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca_cert_path;
    } else {
        // Disable server certificate verification if ca.pem is not present locally
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed. Please verify your Aiven or database configuration.",
        "details" => $e->getMessage()
    ]);
    exit();
}

