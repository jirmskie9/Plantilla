<?php
session_start();
require '../../dbconnection.php';
header('Content-Type: application/json');

// Check admin permissions


try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data)) {
        throw new Exception('No data provided');
    }

    // Start transaction
    $conn->begin_transaction();

    // Prepare insert statement
    $stmt = $conn->prepare("
        INSERT INTO records (
            plantilla_no, plantilla_division, plantilla_section,
            equivalent_division, plantilla_division_definition, plantilla_section_definition,
            fullname, last_name, first_name, middle_name, ext_name, mi, sex,
            position_title, item_number, tech_code, level, appointment_status,
            sg, step, monthly_salary, date_of_birth, date_orig_appt, date_govt_srvc,
            date_last_promotion, date_last_increment, date_longevity, date_vacated,
            vacated_due_to, vacated_by, id_no, created_by, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssi",
        $data['plantilla_no'],
        $data['plantilla_division'],
        $data['plantilla_section'],
        $data['equivalent_division'],
        $data['plantilla_division_definition'],
        $data['plantilla_section_definition'],
        $data['fullname'],
        $data['last_name'],
        $data['first_name'],
        $data['middle_name'],
        $data['ext_name'],
        $data['mi'],
        $data['sex'],
        $data['position_title'],
        $data['item_number'],
        $data['tech_code'],
        $data['level'],
        $data['appointment_status'],
        $data['sg'],
        $data['step'],
        $data['monthly_salary'],
        $data['date_of_birth'],
        $data['date_orig_appt'],
        $data['date_govt_srvc'],
        $data['date_last_promotion'],
        $data['date_last_increment'],
        $data['date_longevity'],
        $data['date_vacated'],
        $data['vacated_due_to'],
        $data['vacated_by'],
        $data['id_no'],
        $_SESSION['user_id']
    );

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $conn->commit();
        echo json_encode(['success' => true, 'id' => $newId]);
    } else {
        throw new Exception('Failed to create record: ' . $stmt->error);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close(); 