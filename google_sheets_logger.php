<?php
/**
 * Google Sheets Logger
 * Logs equipment releases and returns to Google Sheets
 */

/**
 * Log data to Google Sheets via Apps Script Web App
 * 
 * @param string $type 'release' or 'return'
 * @param array $data Data to log
 */
function logToGoogleSheets($type, $data) {
    // Google Apps Script Web App URL
    // Replace this with your actual deployed web app URL
    $webAppUrl = 'https://script.google.com/macros/s/AKfycbzwB2HU5NYur44R4Jomo0Ld8fzTbo5GmE9rLAKezp2uiD61H_lw4wU4A68huV88UG4MMw/exec';
    
    // If no URL configured, skip logging
    if ($webAppUrl === 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE') {
        error_log("Google Sheets logging skipped - Web App URL not configured");
        return false;
    }
    
    try {
        $payload = [
            'type' => $type,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Debug logging
        error_log("Google Sheets Payload: " . json_encode($payload));
        
        $ch = curl_init($webAppUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Debug response
        error_log("Google Sheets Response Code: " . $httpCode);
        error_log("Google Sheets Response Body: " . $response);
        
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Successfully logged to Google Sheets: " . $type);
            return true;
        } else {
            error_log("Failed to log to Google Sheets. HTTP Code: " . $httpCode);
            return false;
        }
    } catch (Exception $e) {
        error_log("Error logging to Google Sheets: " . $e->getMessage());
        return false;
    }
}
