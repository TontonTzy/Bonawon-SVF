<?php
require_once __DIR__ . '/env.php';

$db_host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'mysql-786bccf-judilla-18ba.f.aivencloud.com';
$db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '16860';
$db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'defaultdb';
$db_user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'avnadmin';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : 'AVNS_XQxiQiF1P_7qcqs29HW');
$db_ssl = getenv('DB_SSL') !== false ? filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN) : true;
$ca_cert_path = getenv('DB_SSL_CA') ?: __DIR__ . '/ca.pem';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 3,
];

if ($db_ssl) {
    if (file_exists($ca_cert_path)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca_cert_path;
    } else {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    if (defined('API_REQUEST') && API_REQUEST) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed. Please verify your Aiven host, port, database name, user, and password.',
            'details' => $e->getMessage(),
        ]);
    } else {
        echo '<pre>Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    exit();
}
