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
    // Get files with user information
    $stmt = $conn->prepare("
        SELECT 
            f.id,
            f.file_name,
            f.file_type,
            f.file_size,
            f.month,
            f.created_at,
            u.username as uploaded_by
        FROM file_uploads f
        JOIN users u ON f.user_id = u.id
        ORDER BY f.created_at DESC
    ");
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to fetch files');
    }

    $result = $stmt->get_result();
    $files = [];

    while ($row = $result->fetch_assoc()) {
        // Format file size
        $size = $row['file_size'];
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        $row['formatted_size'] = round($size, 2) . ' ' . $units[$i];

        // Format date
        $row['formatted_date'] = date('M d, Y H:i', strtotime($row['created_at']));

        $files[] = $row;
    }

    echo json_encode([
        'success' => true,
        'files' => $files
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 