<?php
require '../../dbconnection.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Verify database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get filter parameters
    $division = isset($_GET['division']) ? $_GET['division'] : '';
    $month = isset($_GET['month']) ? $_GET['month'] : '';
    $include_archived = isset($_GET['include_archived']) ? (bool)$_GET['include_archived'] : false;
    
    // Get DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 25;
    $search = isset($_GET['search_value']) ? $_GET['search_value'] : '';
    
    // Log incoming parameters
    error_log("API Parameters: " . json_encode([
        'division' => $division,
        'month' => $month,
        'draw' => $draw,
        'start' => $start,
        'length' => $length,
        'search' => $search
    ]));

    // Build base query
    $baseQuery = "FROM records r
        LEFT JOIN divisions d ON r.plantilla_division_definition = d.name 
        OR r.plantilla_division = d.code";
    
    // Build WHERE clauses
    $whereClauses = [];
    $params = [];
    $types = '';
    
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

    // Add archive filter
    if (!$include_archived) {
        $whereClauses[] = "(r.archive = 0 OR r.archive IS NULL)";
    }

    // Add search condition
    if (!empty($search)) {
        $searchFields = [
            'r.plantilla_no', 'r.plantilla_division', 'r.equivalent_division',
            'r.plantilla_division_definition', 'r.fullname', 'r.last_name',
            'r.first_name', 'r.position_title', 'r.id_no'
        ];
        $searchConditions = [];
        foreach ($searchFields as $field) {
            $searchConditions[] = "$field LIKE ?";
            $params[] = "%$search%";
            $types .= 's';
        }
        $whereClauses[] = "(" . implode(" OR ", $searchConditions) . ")";
    }

    // Combine WHERE clauses
    $whereClause = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";
    
    // Get total records count
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalRecords = $stmt->get_result()->fetch_assoc()['total'];
    
    // Build main query
    $query = "SELECT r.*, d.name as division_name, d.code as division_code, d.order_count " . 
             $baseQuery . $whereClause . 
             " ORDER BY 
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
                r.created_at DESC
             LIMIT ?, ?";
    
    // Add pagination parameters
    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';
    
    // Execute main query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    if (!empty($params)) {
        if (!$stmt->bind_param($types, ...$params)) {
            throw new Exception("Parameter binding failed: " . $stmt->error);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    // Log successful response
    error_log("API Response: " . json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data_count' => count($records)
    ]));
    
    // Return DataTables format
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $records
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}