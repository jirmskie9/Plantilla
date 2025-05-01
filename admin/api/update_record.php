<?php
session_start();
require '../../dbconnection.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get POST data
$employee_id = $_POST['employee_id'] ?? '';
$name = $_POST['name'] ?? '';
$position = $_POST['position'] ?? '';
$salary_grade = $_POST['salary_grade'] ?? '';
$status = $_POST['status'] ?? '';
$division_id = $_POST['division_id'] ?? '';

// Debug log
error_log("Update attempt - Employee ID: $employee_id, Name: $name, Position: $position, Salary Grade: $salary_grade, Status: $status, Division ID: $division_id");

// Validate required fields
if (empty($employee_id) || empty($name) || empty($position) || empty($salary_grade) || empty($status) || empty($division_id)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

try {
    // First check if the record exists
    $check_stmt = $conn->prepare("SELECT id FROM records WHERE employee_id = ?");
    $check_stmt->bind_param("s", $employee_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Record not found');
    }
    
    $record = $result->fetch_assoc();
    $record_id = $record['id'];

    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE records SET 
        name = ?, 
        position = ?, 
        salary_grade = ?, 
        status = ?, 
        division_id = ?,
        updated_by = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("sssssii", 
        $name,
        $position,
        $salary_grade,
        $status,
        $division_id,
        $_SESSION['user_id'],
        $record_id
    );

    // Execute the update
    if (!$stmt->execute()) {
        throw new Exception('Failed to update record: ' . $stmt->error);
    }

    // Log the activity
    $activity_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'update', ?, ?)");
    $description = "Updated record for employee: $employee_id";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $activity_stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip_address);
    $activity_stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 