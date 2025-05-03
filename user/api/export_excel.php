<?php

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Function to handle errors
function handleError($message, $statusCode = 500) {
    error_log($message);
    sendJsonResponse(['error' => $message], $statusCode);
}

// Load database connection
require '../../dbconnection.php';

// Get filters from request
$month = $_GET['month'] ?? date('Y-m');
$division = (int)($_GET['division'] ?? 0);

try {
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
        handleError('Failed to prepare statement: ' . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Create Excel file
    require '../../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Autoloader.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    $headers = [
        'Employee ID',
        'Name',
        'Position',
        'Salary Grade',
        'Status',
        'Division',
        'Created At',
        'Updated At'
    ];
    
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $column++;
    }
    
    // Add data
    $row = 2;
    while ($data = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $data['employee_id']);
        $sheet->setCellValue('B' . $row, $data['name']);
        $sheet->setCellValue('C' . $row, $data['position']);
        $sheet->setCellValue('D' . $row, $data['salary_grade']);
        $sheet->setCellValue('E' . $row, $data['status']);
        $sheet->setCellValue('F' . $row, $data['division_name']);
        $sheet->setCellValue('G' . $row, $data['created_at']);
        $sheet->setCellValue('H' . $row, $data['updated_at']);
        $row++;
    }
    
    // Auto-size columns
    foreach(range('A','H') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    // Set file headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="plantilla_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Save Excel file
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    
    exit;
    
} catch (Exception $e) {
    handleError($e->getMessage());
}
