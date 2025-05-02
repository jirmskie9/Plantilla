<?php
header('Content-Type: application/json');
require '../../dbconnection.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['records'])) {
    http_response_code(400);
    die(json_encode(['error' => 'No records provided']));
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Prepare update statement
    $stmt = $conn->prepare("
        UPDATE records SET
            name = ?,
            position = ?,
            salary_grade = ?,
            status = ?,
            division_id = ?,
            remarks = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE employee_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    // Process each record
    foreach ($data['records'] as $record) {
        if (empty($record['employee_id'])) {
            continue; // Skip records without employee_id
        }
        
        // Validate status
        if (!in_array($record['status'], ['active', 'inactive'])) {
            throw new Exception("Invalid status value for employee ID: " . $record['employee_id']);
        }
        
        // Validate division
        if (!empty($record['division_id'])) {
            $divisionStmt = $conn->prepare("SELECT id FROM divisions WHERE id = ? AND status = 'active'");
            $divisionStmt->bind_param("i", $record['division_id']);
            if (!$divisionStmt->execute()) {
                throw new Exception('Database error: ' . $divisionStmt->error);
            }
            $result = $divisionStmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Invalid division ID for employee ID: " . $record['employee_id']);
            }
        }
        
        // Bind parameters and execute
        $stmt->bind_param(
            "sssssss",
            $record['name'],
            $record['position'],
            $record['salary_grade'],
            $record['status'],
            $record['division_id'],
            $record['remarks'] ?? null,
            $record['employee_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Spreadsheet data saved successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]));
}
