<?php
require_once __DIR__ . '/config.php';

echo "Adding supervisor_role column and equipment_condition_logs table...\n\n";

try {
    // 1. Add supervisor_role column to supervisor_store_assignments if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM supervisor_store_assignments LIKE 'supervisor_role'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE supervisor_store_assignments 
            ADD COLUMN supervisor_role ENUM('store_supervisor', 'oic') DEFAULT 'store_supervisor' AFTER assigned_by_user_id
        ");
        echo "✓ Added 'supervisor_role' column to supervisor_store_assignments table\n";
    } else {
        echo "- 'supervisor_role' column already exists\n";
    }
    
    // 2. Create equipment_condition_logs table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS equipment_condition_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT NOT NULL,
            equipment_id INT NOT NULL,
            condition_type ENUM('in', 'out') NOT NULL COMMENT 'in = assignment, out = removal',
            condition_status ENUM('working', 'damaged', 'stolen_missing') NOT NULL,
            recorded_by INT NOT NULL,
            recorded_at DATETIME NOT NULL,
            notes TEXT NULL,
            INDEX idx_assignment (assignment_id),
            INDEX idx_equipment (equipment_id),
            INDEX idx_recorded_by (recorded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created or verified 'equipment_condition_logs' table\n";
    
    echo "\n✅ Database schema updated successfully!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
