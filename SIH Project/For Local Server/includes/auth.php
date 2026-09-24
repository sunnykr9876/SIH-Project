<?php
// htdocs/includes/auth.php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Force user to login if they try to access a protected page
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}

// Check user role
function hasRole($role) {
    return (isset($_SESSION['role']) && $_SESSION['role'] === $role);
}
?>