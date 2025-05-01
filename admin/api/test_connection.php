<?php
header('Content-Type: application/json');
require '../../dbconnection.php';

try {
    // Test divisions table
    $result = $conn->query("SELECT COUNT(*) as count FROM divisions");
    $divisions = $result->fetch_assoc()['count'];
    
    // Test records table
    $result = $conn->query("SHOW COLUMNS FROM records");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo json_encode([
        'success' => true,
        'divisions_count' => $divisions,
        'records_columns' => $columns,
        'connection' => 'OK'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
