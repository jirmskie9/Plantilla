<?php
session_start();
require '../../dbconnection.php';
header('Content-Type: application/json');

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    // Validate input
    if (!isset($_FILES['file']) || !isset($_POST['month'])) {
        throw new Exception('Missing required fields');
    }

    $file = $_FILES['file'];
    $month = $_POST['month'];
    $userId = $_SESSION['user_id'];

    // Validate file type
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only CSV and Excel files are allowed.');
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = '../../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $fileName = uniqid('upload_') . '_' . $file['name'];
    $filePath = $uploadDir . $fileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Record file upload in database
    $stmt = $conn->prepare("
        INSERT INTO file_uploads (user_id, file_name, file_path, file_type, file_size, month)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssis", $userId, $file['name'], $filePath, $file['type'], $file['size'], $month);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to record file upload');
    }

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
