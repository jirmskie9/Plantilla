<?php
session_start();
require '../../dbconnection.php';
header('Content-Type: application/json');

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get record ID from POST data
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid record ID']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Update the record to set archive flag
    $stmt = $conn->prepare("UPDATE records SET archive = 1, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        // Log the activity
        $activity_stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, activity_type, description, ip_address) 
            VALUES (?, 'archive', ?, ?)
        ");
        
        $description = "Archived record ID: $id";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_id = $_SESSION['user_id'] ?? 1;
        
        $activity_stmt->bind_param('iss', $user_id, $description, $ip_address);
        $activity_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Record archived successfully'
        ]);
    } else {
        throw new Exception('Failed to archive record: ' . $stmt->error);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 