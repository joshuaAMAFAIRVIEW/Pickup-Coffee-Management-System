<?php
/**
 * Fetch Release/Return logs from Google Sheets
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_role(['admin', 'manager']);

header('Content-Type: application/json');

$spreadsheetId = '1PxmVtCPTl82B2UQw72oD7rhMTef0Lpr1gyp47Uk1gzQ';
$type = $_GET['type'] ?? 'release'; // 'release' or 'return'
$sheetName = $type === 'release' ? 'Release' : 'Return';

// Construct CSV URL for Google Sheets
$csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:csv&sheet={$sheetName}";

try {
    // Fetch CSV data
    $ch = curl_init($csvUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $csvData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($csvData)) {
        throw new Exception('Failed to fetch data from Google Sheets');
    }
    
    // Parse CSV
    $lines = str_getcsv($csvData, "\n");
    $data = [];
    $headers = [];
    
    foreach ($lines as $index => $line) {
        $row = str_getcsv($line);
        
        if ($index === 0) {
            // First row is headers
            $headers = $row;
            continue;
        }
        
        // Skip empty rows or section headers
        if (empty($row[0]) || strpos($row[0], '===') !== false) {
            continue;
        }
        
        // Combine headers with row data
        $rowData = [];
        foreach ($headers as $i => $header) {
            $rowData[$header] = $row[$i] ?? '';
        }
        
        $data[] = $rowData;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
