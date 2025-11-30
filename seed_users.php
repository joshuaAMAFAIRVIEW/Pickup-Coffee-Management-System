<?php
// Run this script ONCE after importing db_schema.sql to create default users.
// Usage (browser): http://localhost/Pickup%20Coffee/seed_users.php
// Or CLI: php seed_users.php

require_once __DIR__ . '/config.php';

$users = [
    ['username' => 'admin', 'password' => 'Admin@123', 'role' => 'admin'],
    ['username' => 'manager', 'password' => 'Manager@123', 'role' => 'manager'],
    ['username' => 'staff', 'password' => 'Staff@123', 'role' => 'staff'],
];

try {
    foreach ($users as $u) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$u['username']]);
        if ($stmt->fetch()) {
            echo "User '{$u['username']}' already exists.\n";
            continue;
        }

        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
        $ins->execute([$u['username'], $hash, $u['role']]);
        echo "Created user: {$u['username']} (password: {$u['password']})\n";
    }

    echo "\nDone. Please change default passwords after first login.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
