<?php
require '../../dbconnection.php';
header('Content-Type: application/json');

try {
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'organizational_codes'");
    $tableExists = $result->num_rows > 0;
    
    if (!$tableExists) {
        echo json_encode(['success' => false, 'error' => 'organizational_codes table does not exist']);
        exit;
    }
    
    // Get counts
    $total = $conn->query("SELECT COUNT(*) as count FROM organizational_codes")->fetch_assoc()['count'];
    $active = $conn->query("SELECT COUNT(*) as count FROM organizational_codes WHERE status = 'active'")->fetch_assoc()['count'];
    
    // Get sample data
    $sample = $conn->query("SELECT * FROM organizational_codes LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'counts' => [
            'total' => $total,
            'active' => $active
        ],
        'sample_data' => $sample
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
