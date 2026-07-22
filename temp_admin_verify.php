<?php
require_once __DIR__ . '/api/db.php';
$username = 'tonton123';
$query = 'SELECT id, first_name, last_name, username, email, role, status, password_hash FROM admin_users WHERE username = :username OR email = :username LIMIT 1';
echo "QUERY=[$query]\n";
$stmt = $pdo->prepare($query);
var_export($stmt);
$stmt->execute([':username' => $username]);
$admin = $stmt->fetch();
print_r($admin);
