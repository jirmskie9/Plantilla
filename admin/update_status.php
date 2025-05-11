<?php
session_start();
require '../dbconnection.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate input
$record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($record_id <= 0 || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Validate status value
$valid_statuses = ['Pending', 'On-Hold', 'On Process', 'Completed', 'Deliberated'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    // Update the record
    $stmt = $conn->prepare("UPDATE records SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $record_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'update', ?, ?)");
        $description = "Updated status to '$status' for record ID: $record_id";
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $log_stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip_address);
        $log_stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        throw new Exception("Failed to update status");
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 