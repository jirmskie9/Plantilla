<?php
session_start();
require '../../dbconnection.php';
header('Content-Type: application/json');

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    // Validate input
    if (!isset($_POST['file_id'])) {
        throw new Exception('Missing file ID');
    }

    $fileId = $_POST['file_id'];
    $userId = $_SESSION['user_id'];

    // Get file information
    $stmt = $conn->prepare("
        SELECT file_path 
        FROM file_uploads 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $fileId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('File not found or unauthorized');
    }

    $file = $result->fetch_assoc();

    // Delete file from filesystem
    if (file_exists($file['file_path'])) {
        if (!unlink($file['file_path'])) {
            throw new Exception('Failed to delete file from filesystem');
        }
    }

    // Delete record from database
    $stmt = $conn->prepare("DELETE FROM file_uploads WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $fileId, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete file record');
    }

    echo json_encode([
        'success' => true,
        'message' => 'File deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 