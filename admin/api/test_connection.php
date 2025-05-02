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
    
    // Check if remarks column exists, if not add it
    if (!in_array('remarks', $columns)) {
        $conn->query("ALTER TABLE records ADD COLUMN remarks TEXT DEFAULT NULL AFTER date_longevity");
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
