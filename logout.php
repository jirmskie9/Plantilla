<?php
session_start();
include 'dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Prepare the description string first
    $description = "User {$_SESSION['username']} logged out";
    
    // Log the logout activity
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'logout', ?, ?)");
    $stmt->bind_param("iss", $_SESSION['user_id'], $description, $_SERVER['REMOTE_ADDR']);
    $stmt->execute();
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: login.php');
    exit();
} catch (Exception $e) {
    // If there's an error, clear session and redirect
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
