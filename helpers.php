<?php
// Helper functions for role checks and output
require_once __DIR__ . '/auth_check.php';

function require_role(array $roles)
{
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        echo '<h3>403 Forbidden</h3><p>You do not have permission to access this page.</p>';
        exit;
    }
}

function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
