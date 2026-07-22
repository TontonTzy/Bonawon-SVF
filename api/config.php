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
// Use the Aiven values you provided here, or override them with environment variables.
$db_host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: "mysql-786bccf-judilla-18ba.f.aivencloud.com";
$db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: "16860";
$db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'defaultdb';
$db_user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: "avnadmin";
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : "AVNS_XQxiQiF1P_7qcqs29HW");
$db_ssl  = getenv('DB_SSL') !== false ? filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN) : true;

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

if (empty($db_name)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Aiven database name is missing. Set DB_NAME or MYSQLDATABASE to your actual database name in Aiven.",
        "details" => "Example: svf_parish_db or defaultdb"
    ]);
    exit();
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed. Please verify your Aiven host, port, database name, user, and password.",
        "details" => $e->getMessage()
    ]);
    exit();
}

