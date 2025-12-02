<?php
// Helper functions for role checks and output
require_once __DIR__ . '/auth_check.php';

function require_role(array $roles)
{
    if (!isset($_SESSION['user'])) {
        // Check if this is an AJAX/API request expecting JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        // Check if this is an AJAX/API request expecting JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden - Insufficient permissions']);
            exit;
        }
        http_response_code(403);
        echo '<h3>403 Forbidden</h3><p>You do not have permission to access this page.</p>';
        exit;
    }
}

function user_has_role($role_or_roles)
{
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    $user_role = $_SESSION['user']['role'] ?? '';
    
    if (is_array($role_or_roles)) {
        return in_array($user_role, $role_or_roles, true);
    }
    
    return $user_role === $role_or_roles;
}

function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
