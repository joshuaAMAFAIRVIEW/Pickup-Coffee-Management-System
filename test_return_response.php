<?php
/**
 * Test what return_equipment.php is actually outputting
 */

// Simulate a POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['assignment_id'] = 4; // Use the ID from your test
$_POST['return_condition'] = 'damaged';
$_POST['damage_details'] = 'test';

// Capture the output
ob_start();
include 'return_equipment.php';
$output = ob_get_clean();

// Show what was captured
echo "=== RAW OUTPUT ===\n";
echo "Length: " . strlen($output) . " bytes\n";
echo "First 100 chars: " . substr($output, 0, 100) . "\n";
echo "Last 100 chars: " . substr($output, -100) . "\n";
echo "\n=== FULL OUTPUT ===\n";
echo $output;
echo "\n\n=== HEX DUMP (first 200 bytes) ===\n";
echo bin2hex(substr($output, 0, 200));
