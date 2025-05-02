<?php
session_start();
require '../../dbconnection.php';
header('Content-Type: application/json');

try {
    // Get all uploaded files
    $stmt = $conn->prepare("
        SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m-01') as month_start
        FROM file_uploads
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'title' => 'Files Available',
            'start' => $row['month_start'],
            'allDay' => true,
            'backgroundColor' => '#2962FF',
            'borderColor' => '#2962FF'
        ];
    }
    
    echo json_encode($events);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 