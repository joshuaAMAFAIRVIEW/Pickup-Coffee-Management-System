<?php
require_once __DIR__ . '/config.php';

$sql = "CREATE TABLE IF NOT EXISTS supervisor_removal_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supervisor_user_id INT UNSIGNED NOT NULL,
    store_id INT,
    store_name VARCHAR(255),
    removed_by_user_id INT UNSIGNED NOT NULL,
    reason TEXT,
    removal_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (supervisor_user_id) REFERENCES users(id),
    FOREIGN KEY (removed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "Table supervisor_removal_notifications created successfully\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
