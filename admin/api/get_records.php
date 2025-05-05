<?php
require '../../dbconnection.php';
header('Content-Type: application/json');

try {
    // Get division filter if provided
    $division = isset($_GET['division']) ? $_GET['division'] : '';
    $month = isset($_GET['month']) ? $_GET['month'] : '';
    
    // Build query
    $query = "SELECT 
        r.*, 
        d.name as division_name,
        d.code as division_code,
        d.order_count
    FROM records r
    LEFT JOIN divisions d ON r.plantilla_division_definition = d.name 
        OR r.plantilla_division = d.code";
    
    $params = [];
    $types = '';
    $whereClauses = [];
    
    // Add division filter if specified
    if (!empty($division) && $division != '0') {
        $whereClauses[] = "d.id = ?";
        $params[] = $division;
        $types .= 'i';
    }
    
    // Add month filter if specified
    if (!empty($month)) {
        $whereClauses[] = "DATE_FORMAT(r.created_at, '%Y-%m') = ?";
        $params[] = $month;
        $types .= 's';
    }
    
    // Add WHERE clause if we have any filters
    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    // Order by division code using CASE statement for exact ordering
    $query .= " ORDER BY 
        CASE d.code
            WHEN 'OA' THEN 1
            WHEN 'AD' THEN 2
            WHEN 'HRMDS' THEN 3
            WHEN 'RMS' THEN 4
            WHEN 'PPGSS' THEN 5
            WHEN 'FPMD' THEN 6
            WHEN 'AS' THEN 7
            WHEN 'BPS' THEN 8
            WHEN 'MSS' THEN 9
            WHEN 'ETSD' THEN 10
            WHEN 'METTSS' THEN 11
            WHEN 'MGSS' THEN 12
            WHEN 'MEIES' THEN 13
            WHEN 'WD' THEN 14
            WHEN 'WFS' THEN 15
            WHEN 'MDIES' THEN 16
            WHEN 'TAMSS' THEN 17
            WHEN 'AMSS' THEN 18
            WHEN 'MMSS' THEN 19
            WHEN 'HMD' THEN 20
            WHEN 'HDAS' THEN 21
            WHEN 'FFWS' THEN 22
            WHEN 'HTS' THEN 23
            WHEN 'CAD' THEN 24
            WHEN 'CMPS' THEN 25
            WHEN 'FWSS' THEN 26
            WHEN 'IAAS' THEN 27
            WHEN 'CADS' THEN 28
            WHEN 'RDTD' THEN 29
            WHEN 'ASSS' THEN 30
            WHEN 'CARDS' THEN 31
            WHEN 'HTMIRD' THEN 32
            WHEN 'NMS' THEN 33
            WHEN 'TPIS' THEN 34
            WHEN 'NLPRSD' THEN 35
            WHEN 'AFFWS' THEN 36
            WHEN 'PFFWS' THEN 37
            WHEN 'SLPRSD' THEN 38
            WHEN 'BFFWS' THEN 39
            WHEN 'VPRSD' THEN 40
            WHEN 'NMPRSD' THEN 41
            WHEN 'SMPRSD' THEN 42
            WHEN 'FS' THEN 43
            ELSE 999
        END,
        d.code,
        r.created_at DESC";
    
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
            'month' => $month,
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