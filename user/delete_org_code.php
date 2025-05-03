<?php
session_start();
include '../dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;

    // Validate input
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    // Check if record exists
    $checkStmt = $conn->prepare("SELECT id FROM organizational_codes WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // Prepare and execute the delete statement
    $stmt = $conn->prepare("DELETE FROM organizational_codes WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting organizational code']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 