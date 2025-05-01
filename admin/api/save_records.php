<?php
require '../../dbconnection.php';
header('Content-Type: application/json');

try {
    // Get records from POST data
    $records = json_decode(file_get_contents('php://input'), true)['records'] ?? [];
    
    if (empty($records)) {
        throw new Exception('No records provided');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Prepare statements
    $insertStmt = $conn->prepare("
        INSERT INTO records (
            employee_id, name, position, salary_grade, status, division_id,
            created_by, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $updateStmt = $conn->prepare("
        UPDATE records SET
            employee_id = ?,
            name = ?,
            position = ?,
            salary_grade = ?,
            status = ?,
            division_id = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $success = 0;
    $errors = [];
    
    foreach ($records as $index => $record) {
        try {
            $employee_id = $record['employee_id'] ?? '';
            $name = $record['name'] ?? '';
            $position = $record['position'] ?? '';
            $salary_grade = $record['salary_grade'] ?? '';
            $status = $record['status'] ?? '';
            $division_id = $record['division_id'] ?? null;
            
            // Validate required fields
            if (empty($employee_id) || empty($name) || empty($position) || empty($salary_grade) || empty($status) || empty($division_id)) {
                throw new Exception("Missing required fields in row " . ($index + 1));
            }
            
            // Check if record exists
            if (isset($record['id']) && !empty($record['id'])) {
                // Update existing record
                $updateStmt->bind_param('sssssii',
                    $employee_id,
                    $name,
                    $position,
                    $salary_grade,
                    $status,
                    $division_id,
                    $record['id']
                );
                $updateStmt->execute();
            } else {
                // Insert new record
                $insertStmt->bind_param('sssssi',
                    $employee_id,
                    $name,
                    $position,
                    $salary_grade,
                    $status,
                    $division_id
                );
                $insertStmt->execute();
            }
            
            $success++;
            
        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully processed $success records" . 
                    (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 