<?php
session_start();
require '../../dbconnection.php';
require '../../includes/FileUploader.php';
header('Content-Type: application/json');

// Check user permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    // Validate input
    if (!isset($_FILES['file'])) {
        throw new Exception('No file was uploaded');
    }

    $file = $_FILES['file'];
    $month = $_POST['month'] ?? null;
    $userId = $_SESSION['user_id'];

    // Initialize file uploader
    $uploader = new FileUploader($conn, $userId);
    
    // Upload file
    $result = $uploader->upload($file, $month);
    
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
