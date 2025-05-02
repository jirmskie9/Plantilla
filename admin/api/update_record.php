<?php
session_start();
require '../../dbconnection.php';

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get the update data
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$field = isset($_POST['field']) ? $_POST['field'] : '';
$value = isset($_POST['value']) ? $_POST['value'] : null;

// Validate required fields
if (!$id || !$field) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

// Map field names to database columns
$fieldMap = [
    'plantilla_no' => 'plantilla_no',
    'plantilla_division' => 'plantilla_division',
    'equivalent_division' => 'equivalent_division',
    'plantilla_division_definition' => 'plantilla_division_definition',
    'fullname' => 'fullname',
    'last_name' => 'last_name',
    'first_name' => 'first_name',
    'middle_name' => 'middle_name',
    'ext_name' => 'ext_name',
    'mi' => 'mi',
    'sex' => 'sex',
    'position_title' => 'position_title',
    'item_number' => 'item_number',
    'tech_code' => 'tech_code',
    'level' => 'level',
    'appointment_status' => 'appointment_status',
    'sg' => 'sg',
    'step' => 'step',
    'monthly_salary' => 'monthly_salary',
    'date_of_birth' => 'date_of_birth',
    'date_orig_appt' => 'date_orig_appt',
    'date_govt_srvc' => 'date_govt_srvc',
    'date_last_promotion' => 'date_last_promotion',
    'date_last_increment' => 'date_last_increment',
    'date_longevity' => 'date_longevity',
    'date_vacated' => 'date_vacated',
    'vacated_due_to' => 'vacated_due_to',
    'vacated_by' => 'vacated_by',
    'id_no' => 'id_no'
];

// Check if the field is valid
if (!isset($fieldMap[$field])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid field']);
    exit();
}

// Get the database column name
$column = $fieldMap[$field];

// Special handling for plantilla_no
if ($field === 'plantilla_no') {
    // Check if plantilla_no is empty
    if (empty($value)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Plantilla number cannot be empty']);
        exit();
    }

    // Check if plantilla_no already exists for another record
    $check_stmt = $conn->prepare("SELECT id FROM records WHERE plantilla_no = ? AND id != ?");
    $check_stmt->bind_param("si", $value, $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Plantilla number already exists']);
        exit();
    }
}

// Prepare the update query
$query = "UPDATE records SET $column = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

// Bind parameters
$stmt->bind_param('si', $value, $id);

// Execute the update
if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 