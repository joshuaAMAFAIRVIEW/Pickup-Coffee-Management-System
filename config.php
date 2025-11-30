<?php
// Database configuration - adjust to match your XAMPP MySQL settings
// If you are using the default XAMPP MySQL, the default user is 'root' with an empty password.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'inventory_system');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // In production show a friendly error and log the real error.
    die('Database connection failed: ' . $e->getMessage());
}
