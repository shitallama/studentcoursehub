<?php
function protectPage($allowedRoles) {
    if (session_status() === PHP_SESSION_NONE) { 
        session_start(); 
    }

    // 1. If not logged in, send to root login
    if (!isset($_SESSION['role'])) {
        header("Location: ../staff-login.php?error=not_logged_in");
        exit;
    }

    // 2. If role is not allowed, send them to their designated dashboard
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        if ($_SESSION['role'] === 'staff') {
            header("Location: staff-dashboard.php?error=unauthorized");
        } else {
            header("Location: dashboard.php?error=unauthorized");
        }
        exit;
    }
}
?>