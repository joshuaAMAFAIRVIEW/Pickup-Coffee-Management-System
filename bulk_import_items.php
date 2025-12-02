<?php
/**
 * Bulk Import Items from Google Sheets
 * Reads from a public Google Spreadsheet and imports items
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

// Google Spreadsheet URL (public, anyone with link can view)
$spreadsheetId = '1PxmVtCPTl82B2UQw72oD7rhMTef0Lpr1gyp47Uk1gzQ';
$sheetName = 'Sheet1'; // Change if your sheet has a different name
$csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:csv&sheet={$sheetName}";

// Apps Script Web App URL for deleting rows (you'll need to deploy this)
// Replace with your actual deployed web app URL
$appsScriptUrl = 'https://script.google.com/macros/s/AKfycbzwB2HU5NYur44R4Jomo0Ld8fzTbo5GmE9rLAKezp2uiD61H_lw4wU4A68huV88UG4MMw/exec'; // e.g., https://script.google.com/macros/s/xxx/exec

$pdo = $GLOBALS['pdo'];

// Check if this is confirmation (POST with confirmed=true)
$input = json_decode(file_get_contents('php://input'), true);
$confirmed = ($input['confirmed'] ?? false) === true;

if (!$confirmed) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Import must be confirmed. Use preview_bulk_import.php first.'
    ]);
    exit;
}

try {
    // Fetch categories from database (case-insensitive lookup)
    $categoriesStmt = $pdo->query('SELECT id, name FROM categories');
    $categories = [];
    while ($row = $categoriesStmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[strtoupper(trim($row['name']))] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
    
    // Fetch category modifiers
    $modifiersStmt = $pdo->query('SELECT category_id, key_name, label FROM category_modifiers ORDER BY category_id, id');
    $categoryModifiers = [];
    while ($row = $modifiersStmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($categoryModifiers[$row['category_id']])) {
            $categoryModifiers[$row['category_id']] = [];
        }
        $categoryModifiers[$row['category_id']][] = [
            'key_name' => $row['key_name'],
            'label' => $row['label']
        ];
    }
    
    // Download CSV from Google Sheets
    $csvContent = @file_get_contents($csvUrl);
    
    if ($csvContent === false) {
        throw new Exception('Failed to fetch data from Google Sheets. Make sure the spreadsheet is accessible.');
    }
    
    // Parse CSV
    $rows = array_map('str_getcsv', explode("\n", $csvContent));
    
    if (count($rows) < 2) {
        throw new Exception('Spreadsheet is empty or has no data rows');
    }
    
    // Get headers (row 1)
    $headers = array_map('trim', $rows[0]);
    
    // Process data (starting from row 2, which is index 1)
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    $details = [];
    $rowsToDelete = []; // Track successfully imported rows
    
    $pdo->beginTransaction();
    
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        $rowNum = $i + 1; // Actual row number in spreadsheet
        
        // Get category from first column
        $categoryName = trim($row[0] ?? '');
        
        if (empty($categoryName)) {
            $details[] = "Row {$rowNum}: Skipped - No category specified";
            $skipped++;
            continue;
        }
        
        // Find category (case-insensitive)
        $categoryKey = strtoupper($categoryName);
        if (!isset($categories[$categoryKey])) {
            $details[] = "Row {$rowNum}: Error - Category '{$categoryName}' not found in database";
            $errors++;
            continue;
        }
        
        $categoryId = $categories[$categoryKey]['id'];
        $categoryNameDb = $categories[$categoryKey]['name'];
        
        // Get modifiers for this category
        $modifiers = $categoryModifiers[$categoryId] ?? [];
        
        // Create a map of category modifier keys (case-insensitive)
        $categoryModifierKeys = [];
        foreach ($modifiers as $modifier) {
            $categoryModifierKeys[strtoupper($modifier['key_name'])] = $modifier['key_name'];
        }
        
        // Build attributes array from row data
        // Only include fields that:
        // 1. Have a value (not empty)
        // 2. Match the category's modifiers OR are generic fields
        $attributes = [];
        for ($col = 1; $col < count($headers); $col++) {
            $headerName = strtoupper(trim($headers[$col]));
            $value = trim($row[$col] ?? '');
            
            // Skip empty values - this allows different categories to use different fields
            if (empty($value)) {
                continue;
            }
            
            // Check if this field matches a modifier for this category
            $matchedKey = null;
            
            // First, try exact match with category modifiers
            if (isset($categoryModifierKeys[$headerName])) {
                $matchedKey = $categoryModifierKeys[$headerName];
            } else {
                // Try to find case-insensitive match in modifiers
                foreach ($modifiers as $modifier) {
                    if (strtoupper($modifier['key_name']) === $headerName) {
                        $matchedKey = $modifier['key_name'];
                        break;
                    }
                }
            }
            
            // If matched with category modifier, use the exact key name from DB
            if ($matchedKey) {
                $attributes[$matchedKey] = $value;
            } else {
                // For fields not in category modifiers, still include them
                // This allows flexibility for shared fields like S/N, MODEL, etc.
                $attributes[$headerName] = $value;
            }
        }
        
        // Skip row if no attributes were captured
        if (empty($attributes)) {
            $details[] = "Row {$rowNum}: Skipped - No valid data found for category '{$categoryNameDb}'";
            $skipped++;
            continue;
        }
        
        // Generate display name from attributes or use S/N
        $displayName = $attributes['S_N'] ?? 
                      $attributes['s_n'] ?? 
                      $attributes['SERIAL_NUMBER'] ?? 
                      $attributes['serial_number'] ??
                      $attributes['MODEL'] ??
                      $attributes['model'] ??
                      ($categoryNameDb . ' - ' . $rowNum);
        
        // Check for duplicate S/N in database
        $serialNumber = $attributes['S_N'] ?? 
                       $attributes['s_n'] ?? 
                       $attributes['SERIAL_NUMBER'] ?? 
                       $attributes['serial_number'] ?? null;
        
        if ($serialNumber) {
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE JSON_EXTRACT(attributes, "$.S_N") = :sn OR JSON_EXTRACT(attributes, "$.s_n") = :sn OR JSON_EXTRACT(attributes, "$.SERIAL_NUMBER") = :sn OR JSON_EXTRACT(attributes, "$.serial_number") = :sn');
            $checkStmt->execute([':sn' => $serialNumber]);
            $duplicateCount = $checkStmt->fetchColumn();
            
            if ($duplicateCount > 0) {
                $details[] = "Row {$rowNum}: Skipped - Duplicate S/N '{$serialNumber}' already exists";
                $skipped++;
                continue;
            }
        }
        
        // Insert item into database
        try {
            $stmt = $pdo->prepare('
                INSERT INTO items (category_id, display_name, attributes, status, item_condition, assigned_user_id) 
                VALUES (:category_id, :display_name, :attributes, "available", "Brand New", NULL)
            ');
            
            $stmt->execute([
                ':category_id' => $categoryId,
                ':display_name' => $displayName,
                ':attributes' => json_encode($attributes)
            ]);
            
            $imported++;
            $rowsToDelete[] = $rowNum; // Mark this row for deletion
            $details[] = "Row {$rowNum}: ✓ Imported '{$displayName}' to category '{$categoryNameDb}'";
        } catch (PDOException $e) {
            $errors++;
            $details[] = "Row {$rowNum}: ✗ Database error - " . $e->getMessage();
        }
    }
    
    $pdo->commit();
    
    // Delete successfully imported rows from Google Sheets
    if (count($rowsToDelete) > 0 && $appsScriptUrl !== 'YOUR_APPS_SCRIPT_URL_HERE') {
        try {
            $deletePayload = json_encode([
                'action' => 'deleteRows',
                'spreadsheetId' => $spreadsheetId,
                'sheetName' => $sheetName,
                'rows' => $rowsToDelete
            ]);
            
            $ch = curl_init($appsScriptUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $deletePayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $details[] = "✓ Deleted " . count($rowsToDelete) . " rows from spreadsheet";
            } else {
                $details[] = "⚠ Warning: Could not delete rows from spreadsheet (HTTP {$httpCode})";
            }
        } catch (Exception $e) {
            $details[] = "⚠ Warning: Failed to delete rows from spreadsheet - " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($rows) - 1, // Exclude header row
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors,
        'details' => $details
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'total' => 0,
        'imported' => 0,
        'skipped' => 0,
        'errors' => 1,
        'details' => [$e->getMessage()]
    ]);
}
