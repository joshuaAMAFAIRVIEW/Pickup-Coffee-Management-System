<?php
/**
 * Preview Bulk Import Items from Google Sheets
 * Shows what will be imported before actually importing
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

// Google Spreadsheet URL (public, anyone with link can view)
$spreadsheetId = '1PxmVtCPTl82B2UQw72oD7rhMTef0Lpr1gyp47Uk1gzQ';
$sheetName = 'Sheet1';
$csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:csv&sheet={$sheetName}";

$pdo = $GLOBALS['pdo'];

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
    
    // Preview data
    $previewItems = [];
    $duplicates = [];
    $invalidRows = [];
    
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $rowNum = $i + 1; // Excel row number (header is row 1)
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Get category from first column
        $categoryNameInput = strtoupper(trim($row[0] ?? ''));
        
        if (empty($categoryNameInput)) {
            $invalidRows[] = [
                'row' => $rowNum,
                'reason' => 'Category is missing'
            ];
            continue;
        }
        
        // Look up category
        if (!isset($categories[$categoryNameInput])) {
            $invalidRows[] = [
                'row' => $rowNum,
                'reason' => "Category '{$row[0]}' not found"
            ];
            continue;
        }
        
        $categoryId = $categories[$categoryNameInput]['id'];
        $categoryNameDb = $categories[$categoryNameInput]['name'];
        
        // Get modifiers for this category
        $modifiers = $categoryModifiers[$categoryId] ?? [];
        
        // Create a map of category modifier keys (case-insensitive)
        $categoryModifierKeys = [];
        foreach ($modifiers as $modifier) {
            $categoryModifierKeys[strtoupper($modifier['key_name'])] = $modifier['key_name'];
        }
        
        // Build attributes array from row data
        $attributes = [];
        for ($col = 1; $col < count($headers); $col++) {
            $headerName = strtoupper(trim($headers[$col]));
            $value = trim($row[$col] ?? '');
            
            if (empty($value)) {
                continue;
            }
            
            // Check if this field matches a modifier for this category
            $matchedKey = null;
            
            if (isset($categoryModifierKeys[$headerName])) {
                $matchedKey = $categoryModifierKeys[$headerName];
            } else {
                foreach ($modifiers as $modifier) {
                    if (strtoupper($modifier['key_name']) === $headerName) {
                        $matchedKey = $modifier['key_name'];
                        break;
                    }
                }
            }
            
            if ($matchedKey) {
                $attributes[$matchedKey] = $value;
            } else {
                $attributes[$headerName] = $value;
            }
        }
        
        if (empty($attributes)) {
            $invalidRows[] = [
                'row' => $rowNum,
                'reason' => 'No valid data found'
            ];
            continue;
        }
        
        // Generate display name
        $displayName = $attributes['S_N'] ?? 
                      $attributes['s_n'] ?? 
                      $attributes['SERIAL_NUMBER'] ?? 
                      $attributes['serial_number'] ??
                      $attributes['MODEL'] ??
                      $attributes['model'] ??
                      ($categoryNameDb . ' - ' . $rowNum);
        
        // Check for duplicate S/N
        $serialNumber = $attributes['S_N'] ?? 
                       $attributes['s_n'] ?? 
                       $attributes['SERIAL_NUMBER'] ?? 
                       $attributes['serial_number'] ?? null;
        
        $isDuplicate = false;
        if ($serialNumber) {
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE JSON_EXTRACT(attributes, "$.S_N") = :sn OR JSON_EXTRACT(attributes, "$.s_n") = :sn OR JSON_EXTRACT(attributes, "$.SERIAL_NUMBER") = :sn OR JSON_EXTRACT(attributes, "$.serial_number") = :sn');
            $checkStmt->execute([':sn' => $serialNumber]);
            $duplicateCount = $checkStmt->fetchColumn();
            
            if ($duplicateCount > 0) {
                $isDuplicate = true;
                $duplicates[] = [
                    'row' => $rowNum,
                    'sn' => $serialNumber,
                    'category' => $categoryNameDb,
                    'name' => $displayName
                ];
                continue; // Skip duplicates in preview
            }
        }
        
        // Add to preview
        $previewItems[] = [
            'row' => $rowNum,
            'category' => $categoryNameDb,
            'name' => $displayName,
            'sn' => $serialNumber ?? 'N/A',
            'attributes' => $attributes
        ];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $previewItems,
        'duplicates' => $duplicates,
        'invalid' => $invalidRows,
        'summary' => [
            'total' => count($previewItems),
            'duplicates' => count($duplicates),
            'invalid' => count($invalidRows)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
