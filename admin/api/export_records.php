<?php
session_start();
require '../../dbconnection.php';

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

// Get filter parameters
$division = isset($_POST['division']) ? trim($_POST['division']) : '';
$month = isset($_POST['month']) ? $_POST['month'] : '';

// Debug output
error_log("Raw Division: " . $division);
error_log("Raw Month: " . $month);

// If division is a number, get the division name from the database
if (is_numeric($division)) {
    $stmt = $conn->prepare("SELECT name FROM divisions WHERE id = ?");
    $stmt->bind_param("i", $division);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $division = $row['name'];
        error_log("Converted division ID to name: " . $division);
    }
}

// Build the query
$query = "SELECT 
    r.plantilla_no,
    r.plantilla_division,
    r.equivalent_division,
    r.plantilla_division_definition,
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
    r.id_no,
    r.created_at,
    r.updated_at
FROM records r
WHERE 1=1";

$params = [];
$types = '';

if (!empty($division)) {
    // Use LIKE for more flexible matching
    $query .= " AND r.plantilla_division_definition LIKE ?";
    $params[] = '%' . $division . '%';
    $types .= 's';
    error_log("Added division filter with LIKE: " . $params[count($params)-1]);
}

if (!empty($month)) {
    $query .= " AND DATE_FORMAT(r.created_at, '%Y-%m') = ?";
    $params[] = $month;
    $types .= 's';
    error_log("Added month filter: " . $month);
}

// Debug the final query
error_log("Final query: " . $query);
error_log("Parameters: " . print_r($params, true));

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Error preparing statement: " . $conn->error);
    die("Error preparing statement: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    error_log("Error executing statement: " . $stmt->error);
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
$rowCount = $result->num_rows;
error_log("Number of rows found: " . $rowCount);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="records_export.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, [
    'PLANTILLA NO.',
    'PLANTILLA DIVISION',
    'EQUIVALENT DIVISION',
    'PLANTILLA DIVISION DEFINITION',
    'FULLNAME',
    'LAST NAME',
    'FIRST NAME',
    'MIDDLE NAME',
    'EXT NAME',
    'MI',
    'SEX',
    'POSITION TITLE',
    'ITEM NUMBER',
    'TECH CODE',
    'LEVEL',
    'APPOINTMENT STATUS',
    'SG',
    'STEP',
    'MONTHLY SALARY',
    'DATE OF BIRTH',
    'DATE ORIG. APPT.',
    'DATE GOVT SRVC',
    'DATE LAST PROMOTION',
    'DATE LAST INCREMENT',
    'DATE OF LONGEVITY',
    'DATE VACATED',
    'VACATED DUE TO',
    'VACATED BY',
    'ID NO.',
    'CREATED AT',
    'UPDATED AT'
]);

// Write data
$exportedRows = 0;
while ($row = $result->fetch_assoc()) {
    $exportedRows++;
    fputcsv($output, [
        $row['plantilla_no'],
        $row['plantilla_division'],
        $row['equivalent_division'],
        $row['plantilla_division_definition'],
        $row['fullname'],
        $row['last_name'],
        $row['first_name'],
        $row['middle_name'],
        $row['ext_name'],
        $row['mi'],
        $row['sex'],
        $row['position_title'],
        $row['item_number'],
        $row['tech_code'],
        $row['level'],
        $row['appointment_status'],
        $row['sg'],
        $row['step'],
        $row['monthly_salary'],
        $row['date_of_birth'],
        $row['date_orig_appt'],
        $row['date_govt_srvc'],
        $row['date_last_promotion'],
        $row['date_last_increment'],
        $row['date_longevity'],
        $row['date_vacated'],
        $row['vacated_due_to'],
        $row['vacated_by'],
        $row['id_no'],
        $row['created_at'],
        $row['updated_at']
    ]);
}

error_log("Number of rows exported: " . $exportedRows);

// Close the output stream
fclose($output);
exit();
