<?php
session_start();
include '../dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $code = $_POST['code'] ?? '';
    $description = $_POST['description'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $status = $_POST['status'] ?? 'active';

    // Validate input
    if (empty($id) || empty($code) || empty($description) || empty($department) || empty($position)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Check if code already exists for another record
    $checkStmt = $conn->prepare("SELECT id FROM organizational_codes WHERE code = ? AND id != ?");
    $checkStmt->bind_param("si", $code, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Code already exists']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // Prepare and execute the update statement
    $stmt = $conn->prepare("UPDATE organizational_codes SET code = ?, description = ?, department = ?, position = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $code, $description, $department, $position, $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating organizational code']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 