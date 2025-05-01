<?php
header('Content-Type: application/json');
require '../../dbconnection.php';

try {
    // Test connection
    $conn->ping();
    
    // Test records table
    $result = $conn->query("SHOW TABLES LIKE 'records'");
    $tableExists = $result->num_rows > 0;
    
    // Test divisions table
    $result = $conn->query("SHOW TABLES LIKE 'divisions'");
    $divisionsExist = $result->num_rows > 0;
    
    // Test sample query
    $sampleData = [];
    if ($tableExists) {
        $result = $conn->query("SELECT * FROM records LIMIT 5");
        $sampleData = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    echo json_encode([
        'connection' => true,
        'records_table_exists' => $tableExists,
        'divisions_table_exists' => $divisionsExist,
        'sample_data' => $sampleData
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'connection' => false
    ]);
}
