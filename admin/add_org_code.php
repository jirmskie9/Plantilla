<?php
session_start();
include '../dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $description = $_POST['description'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $status = $_POST['status'] ?? 'active';

    // Validate input
    if (empty($code) || empty($description) || empty($department) || empty($position)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Check if code already exists
    $checkStmt = $conn->prepare("SELECT id FROM organizational_codes WHERE code = ?");
    $checkStmt->bind_param("s", $code);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Code already exists']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO organizational_codes (code, description, department, position, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $code, $description, $department, $position, $status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding organizational code']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 