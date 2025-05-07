<?php 

//logout.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();
function base_url($path = '') {
    // Detect protocol (http or https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

    // Get host (e.g., localhost, example.com)
    $host = $_SERVER['HTTP_HOST'];

    // Get current folder (e.g., /SmartLib/)
    $script_name = $_SERVER['SCRIPT_NAME']; // e.g., /SmartLib/admin/logout.php
    $script_dir = dirname($script_name);    // e.g., /SmartLib/admin
    $base_folder = explode('/', trim($script_dir, '/'))[0]; // SmartLib
    $base_path = '/' . $base_folder . '/'; // SmartLib path

    // Combine all parts
    return $protocol . $host . $base_path . ltrim($path, '/');
}

// Start a new session for flash messages
session_start();
$_SESSION['success'] = "You have been successfully logged out.";


// Redirect to login page
header("Location: " . base_url("index.php"));
exit();
?>