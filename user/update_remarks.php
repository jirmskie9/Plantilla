<?php
session_start();
include '../dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$record_id = isset($_POST['record_id']) ? $_POST['record_id'] : null;
$remarks = isset($_POST['remarks']) ? $_POST['remarks'] : null;

// Validate input
if (!$record_id || !$remarks) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Validate remarks value
$allowed_remarks = ['Not Yet for Filling up', 'On-Hold', 'On Process'];
if (!in_array($remarks, $allowed_remarks)) {
    echo json_encode(['success' => false, 'message' => 'Invalid remarks value']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update remarks in database
    $query = "UPDATE records SET remarks = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $remarks, $record_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update remarks');
    }

    // Log the activity
    $activity = "Updated remarks for record ID: $record_id to: $remarks";
    $log_query = "INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'update', ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param('iss', $_SESSION['user_id'], $activity, $ip_address);
    
    if (!$log_stmt->execute()) {
        throw new Exception('Failed to log activity');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Remarks updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Close statements
    if (isset($stmt)) $stmt->close();
    if (isset($log_stmt)) $log_stmt->close();
    $conn->close();
}
?> 