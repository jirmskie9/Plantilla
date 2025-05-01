<?php

// Load database connection
require '../../dbconnection.php';

// Get filters from URL
$month = $_GET['month'] ?? date('Y-m');
$division = (int)($_GET['division'] ?? 0);

// Build query based on filters
$query = "SELECT r.*, d.name as division_name 
          FROM records r 
          LEFT JOIN divisions d ON r.division_id = d.id 
          WHERE 1=1";

$params = [];
$types = '';

if ($division > 0) {
    $query .= " AND r.division_id = ?";
    $params[] = $division;
    $types .= 'i';
}

// Execute query
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Failed to prepare statement: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Create CSV output
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="plantilla_' . date('Y-m-d') . '.csv"');
header('Cache-Control: max-age=0');

// Open output stream
$output = fopen('php://output', 'w');

// Write headers
$headers = ['Employee ID', 'Name', 'Position', 'Salary Grade', 'Status', 'Division', 'Created At', 'Updated At'];
fputcsv($output, $headers);

// Write data
while ($row = $result->fetch_assoc()) {
    $data = [
        $row['employee_id'],
        $row['name'],
        $row['position'],
        $row['salary_grade'],
        $row['status'],
        $row['division_name'],
        $row['created_at'],
        $row['updated_at']
    ];
    fputcsv($output, $data);
}

// Close output
fclose($output);

exit;
