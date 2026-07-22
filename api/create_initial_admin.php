<?php
if (PHP_SAPI !== 'cli') {
    echo "This script may only be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/db.php';

$username = getenv('INITIAL_ADMIN_USERNAME') ?: null;
$email = getenv('INITIAL_ADMIN_EMAIL') ?: null;
$password = getenv('INITIAL_ADMIN_PASSWORD') ?: null;
$firstName = getenv('INITIAL_ADMIN_FIRST_NAME') ?: 'Super';
$lastName = getenv('INITIAL_ADMIN_LAST_NAME') ?: 'Admin';

if (!$username || !$email || !$password) {
    echo "Please set INITIAL_ADMIN_USERNAME, INITIAL_ADMIN_EMAIL, and INITIAL_ADMIN_PASSWORD in your environment or .env file.\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "INITIAL_ADMIN_EMAIL must be a valid email address.\n";
    exit(1);
}

if (strlen($password) < 10) {
    echo "INITIAL_ADMIN_PASSWORD must be at least 10 characters.\n";
    exit(1);
}

$stmt = $pdo->prepare('SELECT id FROM admin_users WHERE deleted_at IS NULL AND (username = :username OR email = :email) LIMIT 1');
$stmt->execute([':username' => $username, ':email' => $email]);
$existing = $stmt->fetch();
if ($existing) {
    echo "An admin account with that username or email already exists.\n";
    exit(0);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO admin_users (first_name, last_name, username, email, password_hash, role, status, created_at, updated_at) VALUES (:first_name, :last_name, :username, :email, :hash, "super_admin", "active", NOW(), NOW())');
$stmt->execute([
    ':first_name' => $firstName,
    ':last_name' => $lastName,
    ':username' => $username,
    ':email' => $email,
    ':hash' => $hash,
]);

echo "Super Admin account created successfully.\n";
