<?php
/**
 * Add operation_hours column to stores table
 * Area managers will be able to update this field for their stores
 */
require_once __DIR__ . '/config.php';

try {
    // Check if column already exists
    $checkStmt = $pdo->query("SHOW COLUMNS FROM stores LIKE 'operation_hours'");
    if ($checkStmt->rowCount() > 0) {
        echo "Column 'operation_hours' already exists in stores table.\n";
        exit;
    }
    
    // Add operation_hours column
    $pdo->exec("
        ALTER TABLE stores 
        ADD COLUMN operation_hours VARCHAR(100) NULL DEFAULT NULL 
        AFTER address
    ");
    
    echo "✓ Successfully added 'operation_hours' column to stores table.\n";
    echo "  Area managers can now set operation hours for their stores.\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
