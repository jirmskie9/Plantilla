<?php
require '../../dbconnection.php';
header('Content-Type: application/json');

try {
    // Get division filter if provided
    $division_id = isset($_GET['division']) ? (int)$_GET['division'] : 0;
    
    // Build query
    $query = "SELECT 
        r.id,
        r.employee_id,
        r.name,
        r.position,
        r.salary_grade,
        r.status,
        r.division_id,
        r.created_at,
        d.name as division_name,
        d.code as division_code
    FROM records r
    LEFT JOIN divisions d ON r.division_id = d.id
    WHERE r.status = 'active'";
    
    $params = [];
    $types = '';
    
    // Add division filter if specified
    if ($division_id > 0) {
        $query .= " AND r.division_id = ? AND d.status = 'active'";
        $params[] = $division_id;
        $types .= 'i';
    } else {
        // For 'All Divisions', ensure we only get records from active divisions
        $query .= " AND d.status = 'active'";
    }
    
    $query .= " ORDER BY r.name ASC";
    
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
            'division_id' => $division_id,
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
