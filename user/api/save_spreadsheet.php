<?php
session_start();
header('Content-Type: application/json');
require '../../dbconnection.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
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
            plantilla_no = ?,
            plantilla_division = ?,
            equivalent_division = ?,
            plantilla_division_definition = ?,
            fullname = ?,
            last_name = ?,
            first_name = ?,
            middle_name = ?,
            ext_name = ?,
            mi = ?,
            sex = ?,
            position_title = ?,
            item_number = ?,
            tech_code = ?,
            level = ?,
            appointment_status = ?,
            sg = ?,
            step = ?,
            monthly_salary = ?,
            date_of_birth = ?,
            date_orig_appt = ?,
            date_govt_srvc = ?,
            date_last_promotion = ?,
            date_last_increment = ?,
            date_longevity = ?,
            date_vacated = ?,
            vacated_due_to = ?,
            vacated_by = ?,
            id_no = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    // Process each record
    foreach ($data['records'] as $record) {
        if (empty($record['id'])) {
            continue; // Skip records without id
        }
        
        // Convert empty strings to null for date fields
        $dateFields = [
            'date_of_birth', 'date_orig_appt', 'date_govt_srvc',
            'date_last_promotion', 'date_last_increment', 'date_longevity',
            'date_vacated'
        ];
        
        foreach ($dateFields as $field) {
            if (isset($record[$field]) && $record[$field] === '') {
                $record[$field] = null;
            }
        }
        
        // Bind parameters and execute
        $stmt->bind_param(
            "ssssssssssssssssssssssssssssssi",
            $record['plantilla_no'],
            $record['plantilla_division'],
            $record['equivalent_division'],
            $record['plantilla_division_definition'],
            $record['fullname'],
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
            $record['monthly_salary'],
            $record['date_of_birth'],
            $record['date_orig_appt'],
            $record['date_govt_srvc'],
            $record['date_last_promotion'],
            $record['date_last_increment'],
            $record['date_longevity'],
            $record['date_vacated'],
            $record['vacated_due_to'],
            $record['vacated_by'],
            $record['id_no'],
            $record['id']
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
