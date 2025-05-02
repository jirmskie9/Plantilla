<?php
require '../../dbconnection.php';
header('Content-Type: application/json');

try {
    // Get division filter if provided
    $division = isset($_GET['division']) ? $_GET['division'] : '';
    
    // Build query
    $query = "SELECT 
        r.*, 
        d.name as division_name,
        d.code as division_code
    FROM records r
    LEFT JOIN divisions d ON r.plantilla_division = d.code";
    
    $params = [];
    $types = '';
    
    // Add division filter if specified
    if (!empty($division)) {
        $query .= " WHERE r.plantilla_division_definition = ?";
        $params[] = $division;
        $types .= 's';
    }
    
    $query .= " ORDER BY r.plantilla_no ASC";
    
    // Debug output
    error_log("Executing query: $query");
    if (!empty($params)) {
        error_log("With params: " . print_r($params, true));
    }
    
    // Execute query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $records,
        'debug' => [
            'query' => $query,
            'params' => $params,
            'division' => $division,
            'num_rows' => $result->num_rows
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}
