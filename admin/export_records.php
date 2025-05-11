<?php
session_start();
require '../dbconnection.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized access');
}

// Get all filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : '';
$division = isset($_GET['division']) ? $_GET['division'] : '';

try {
    // Build the query with all filters
    $query = "SELECT 
        id_no,
        last_name,
        first_name,
        middle_name,
        ext_name,
        mi,
        sex,
        position_title,
        item_number,
        tech_code,
        level,
        appointment_status,
        sg,
        step,
        DATE_FORMAT(date_of_birth, '%M %d, %Y') as date_of_birth,
        DATE_FORMAT(date_orig_appt, '%M %d, %Y') as date_orig_appt,
        DATE_FORMAT(date_govt_srvc, '%M %d, %Y') as date_govt_srvc,
        DATE_FORMAT(date_last_promotion, '%M %d, %Y') as date_last_promotion,
        DATE_FORMAT(date_last_increment, '%M %d, %Y') as date_last_increment,
        DATE_FORMAT(date_longevity, '%M %d, %Y') as date_longevity,
        plantilla_division,
        plantilla_section,
        status,
        created_at
    FROM records 
    WHERE 1=1";

    $params = [];
    $types = "";

    // Add status filter if present
    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    // Add search filter
    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR ext_name LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= "ssss";
    }

    // Add month filter
    if (!empty($month)) {
        $query .= " AND MONTH(created_at) = ?";
        $params[] = $month;
        $types .= "i";
    }

    // Add division filter
    if (!empty($division)) {
        $query .= " AND plantilla_division = ?";
        $params[] = $division;
        $types .= "s";
    }

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);

    // Generate filename based on filters
    $filename = 'Records_Export';
    if (!empty($status)) $filename .= '_' . $status;
    if (!empty($search)) $filename .= '_Search_' . $search;
    if (!empty($month)) $filename .= '_Month_' . $month;
    if (!empty($division)) $filename .= '_Division_' . $division;
    $filename .= '_' . date('Y-m-d') . '.csv';

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Headers
    $headers = [
        'ID No.',
        'Last Name',
        'First Name',
        'Middle Name',
        'Ext. Name',
        'MI',
        'Sex',
        'Position Title',
        'Item Number',
        'Tech Code',
        'Level',
        'Appointment Status',
        'SG',
        'Step',
        'Date of Birth',
        'Date of Original Appointment',
        'Date of Govt. Service',
        'Date of Last Promotion',
        'Date of Last Increment',
        'Date of Longevity',
        'Division',
        'Section',
        'Status',
        'Created At'
    ];

    // Write headers
    fputcsv($output, $headers);

    // Write data rows
    foreach ($records as $record) {
        $row = [
            $record['id_no'],
            $record['last_name'],
            $record['first_name'],
            $record['middle_name'],
            $record['ext_name'],
            $record['mi'],
            $record['sex'],
            $record['position_title'],
            $record['item_number'],
            $record['tech_code'],
            $record['level'],
            $record['appointment_status'],
            $record['sg'],
            $record['step'],
            $record['date_of_birth'],
            $record['date_orig_appt'],
            $record['date_govt_srvc'],
            $record['date_last_promotion'],
            $record['date_last_increment'],
            $record['date_longevity'],
            $record['plantilla_division'],
            $record['plantilla_section'],
            $record['status'],
            $record['created_at']
        ];
        fputcsv($output, $row);
    }

    // Close the output stream
    fclose($output);
    exit;

} catch (Exception $e) {
    die('Error exporting records: ' . $e->getMessage());
}
?> 