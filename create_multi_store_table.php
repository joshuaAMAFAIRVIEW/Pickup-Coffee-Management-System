<?php
require_once __DIR__ . '/config.php';

// Create supervisor_store_assignments table for many-to-many relationship
$sql1 = "CREATE TABLE IF NOT EXISTS supervisor_store_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supervisor_user_id INT UNSIGNED NOT NULL,
    store_id INT NOT NULL,
    assigned_by_user_id INT UNSIGNED NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_assignment (supervisor_user_id, store_id, is_active),
    FOREIGN KEY (supervisor_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Update supervisor_assignment_requests to not conflict
$sql2 = "ALTER TABLE supervisor_assignment_requests 
         DROP INDEX IF EXISTS unique_pending_request";

try {
    $pdo->exec($sql1);
    echo "Table supervisor_store_assignments created successfully\n";
    
    try {
        $pdo->exec($sql2);
        echo "Removed unique constraint from supervisor_assignment_requests\n";
    } catch (PDOException $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    echo "\nAll changes applied successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
