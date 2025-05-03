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
$query = "SELECT r.* FROM records r WHERE 1=1";
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
        $headers[] = $field->name;
    }
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    // If no records found, write error message
    fputcsv($output, array('No records found'));
}

// Close the output stream
fclose($output);
exit(); 