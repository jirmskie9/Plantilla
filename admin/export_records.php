<?php
session_start();
require '../dbconnection.php';

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters from URL
$division_id = isset($_GET['division']) ? (int)$_GET['division'] : 0;
$month = isset($_GET['month']) ? $_GET['month'] : '';

// Get division definition if division ID is provided
$division_definition = '';
if ($division_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM divisions WHERE id = ?");
    $stmt->bind_param("i", $division_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $division_definition = $row['name'];
    }
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=records_export_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Build query with filters
$query = "SELECT 
    r.plantilla_no,
    r.plantilla_division,
    r.plantilla_section,
    r.equivalent_division,
    r.plantilla_division_definition,
    r.plantilla_section_definition,
    r.fullname,
    r.last_name,
    r.first_name,
    r.middle_name,
    r.ext_name,
    r.mi,
    r.sex,
    r.position_title,
    r.item_number,
    r.tech_code,
    r.level,
    r.appointment_status,
    r.sg,
    r.step,
    r.monthly_salary,
    r.date_of_birth,
    r.date_orig_appt,
    r.date_govt_srvc,
    r.date_last_promotion,
    r.date_last_increment,
    r.date_longevity,
    r.date_vacated,
    r.vacated_due_to,
    r.vacated_by,
    r.id_no
FROM records r WHERE 1=1";
$params = array();
$types = '';

if (!empty($division_definition)) {
    $query .= " AND r.plantilla_division_definition = ?";
    $params[] = $division_definition;
    $types .= 's';
}

if (!empty($month)) {
    $query .= " AND DATE_FORMAT(r.created_at, '%Y-%m') = ?";
    $params[] = $month;
    $types .= 's';
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    // Get column names
    $fields = $result->fetch_fields();
    $headers = array();
    foreach ($fields as $field) {
        // Convert column name to uppercase and replace underscores with spaces
        $header = str_replace('_', ' ', strtoupper($field->name));
        $headers[] = $header;
    }
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    // If no records found, write error message
    fputcsv($output, array('NO RECORDS FOUND'));
}

// Close the output stream
fclose($output);
exit(); 