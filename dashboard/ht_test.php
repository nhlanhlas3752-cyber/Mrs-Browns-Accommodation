<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo '<pre>';

// Test 1: config + DB
try {
    require_once dirname(__DIR__) . '/backend/config.php';
    require_once dirname(__DIR__) . '/backend/db.php';
    $pdo = db();
    echo "DB: OK\n";

    // Check users table
    $u = $pdo->query("SELECT id, name, email, role, last_login FROM users LIMIT 1")->fetch();
    echo "User row: " . json_encode($u) . "\n";

    // Check login_attempts table
    $la = $pdo->query("SHOW TABLES LIKE 'login_attempts'")->fetch();
    echo "login_attempts table: " . ($la ? "EXISTS" : "MISSING") . "\n";

    // Check audit_log table
    $al = $pdo->query("SHOW TABLES LIKE 'audit_log'")->fetch();
    echo "audit_log table: " . ($al ? "EXISTS" : "MISSING") . "\n";

} catch (Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

// Test 2: session
session_start();
echo "Session save path: " . session_save_path() . "\n";
echo "Session started: OK\n";

// Test 3: password verify
$hash = '$2y$12$test';
echo "PHP version: " . PHP_VERSION . "\n";

echo '</pre>';
