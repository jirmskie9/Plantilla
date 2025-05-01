<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Start session after headers
session_start();

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Function to handle errors
function handleError($message, $statusCode = 500) {
    error_log($message);
    sendJsonResponse(['error' => $message], $statusCode);
}

// Check required PHP extensions
if (!extension_loaded('mbstring')) {
    handleError('Required PHP extension mbstring is not installed', 500);
}
if (!extension_loaded('iconv')) {
    handleError('Required PHP extension iconv is not installed', 500);
}

try {
    // Load Composer autoloader
    require '../../vendor/autoload.php';
    
    // Load database connection
    require '../../dbconnection.php';

    // Debug logging
    error_log("Upload request received");
    error_log("FILES: " . print_r($_FILES, true));
    error_log("POST: " . print_r($_POST, true));

    // Check if file was uploaded
    if (empty($_FILES['file'])) {
        handleError("No file uploaded", 400);
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        handleError("Upload error occurred: " . $_FILES['file']['error'], 400);
    }

    // Get form data
    $month = $_POST['month'] ?? date('Y-m');
    $division = (int)($_POST['division'] ?? 0);

    // Validate file
    $file = $_FILES['file'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    $allowedTypes = [
        'text/csv',
        'text/plain',
        'application/octet-stream'
    ];

    // Check file size
    if ($file['size'] > $maxSize) {
        handleError("File too large. Maximum size is 10MB", 400);
    }

    // Check file type
    if (!in_array($file['type'], $allowedTypes)) {
        error_log("Invalid file type: " . $file['type']);
        sendJsonResponse(['error' => 'Invalid file type. Only CSV files are allowed'], 400);
    }

    // Read CSV file
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        error_log("Failed to open file");
        sendJsonResponse(['error' => 'Failed to read file'], 500);
    }

    $headers = fgetcsv($handle);
    if (!$headers) {
        error_log("Failed to read headers");
        fclose($handle);
        sendJsonResponse(['error' => 'Failed to read CSV headers'], 500);
    }

    $data = [];
    while (($row = fgetcsv($handle)) !== false) {
        $data[] = array_combine($headers, $row);
    }
    fclose($handle);

    // Process the data and insert into database
    $insertedCount = 0;
    $errors = [];
    
    // Get the current user ID (assuming 1 for admin)
    $userId = 1;
    
    // Insert into file_uploads table first
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];
    $fileTempPath = $file['tmp_name'];
    
    // Generate a unique file path
    $uploadDir = '../../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uniqueFileName = uniqid() . '_' . $fileName;
    $fileDestination = $uploadDir . $uniqueFileName;
    
    // Move the uploaded file
    if (!move_uploaded_file($fileTempPath, $fileDestination)) {
        error_log("Failed to move uploaded file");
        sendJsonResponse(['error' => 'Failed to save uploaded file'], 500);
    }
    
    // Insert into file_uploads table
    $fileUploadQuery = "INSERT INTO file_uploads (user_id, file_name, file_path, file_type, file_size, status) 
                       VALUES (?, ?, ?, ?, ?, 'processed')";
    
    $fileStmt = $conn->prepare($fileUploadQuery);
    if (!$fileStmt) {
        error_log("Failed to prepare file upload statement: " . $conn->error);
        sendJsonResponse(['error' => 'Failed to record file upload'], 500);
    }
    
    $fileStmt->bind_param("isssi", $userId, $fileName, $fileDestination, $fileType, $fileSize);
    
    if (!$fileStmt->execute()) {
        error_log("Failed to execute file upload: " . $fileStmt->error);
        sendJsonResponse(['error' => 'Failed to record file upload'], 500);
    }
    
    $fileStmt->close();
    
    // Get the file upload ID
    $fileUploadId = $conn->insert_id;
    
    // Process each record
    foreach ($data as $row) {
        try {
            // Prepare the data
            $divisionId = isset($row['division_id']) ? (int)$row['division_id'] : null;
            $employeeId = isset($row['employee_id']) ? $row['employee_id'] : null;
            $name = isset($row['name']) ? $row['name'] : null;
            $position = isset($row['position']) ? $row['position'] : null;
            $salaryGrade = isset($row['salary_grade']) ? $row['salary_grade'] : null;
            
            // Validate required fields
            if (!$divisionId || !$employeeId || !$name || !$position || !$salaryGrade) {
                throw new Exception('Missing required fields: ' . json_encode($row));
            }
            
            // Check if employee already exists
            $checkQuery = "SELECT id FROM records WHERE employee_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("s", $employeeId);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                throw new Exception("Employee ID {$employeeId} already exists");
            }
            
            $checkStmt->close();
            
            // Prepare the query
            $query = "INSERT INTO records (division_id, employee_id, name, position, salary_grade, status, created_by, updated_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            // Prepare and execute the statement
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            // Bind parameters
            $params = [
                $divisionId,  // division_id
                $employeeId,  // employee_id
                $name,        // name
                $position,    // position
                $salaryGrade, // salary_grade
                'active',     // status
                $userId,      // created_by
                $userId       // updated_by
            ];
            
            // Create type string
            $types = str_repeat('s', count($params));
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
            
            // Execute
            if ($stmt->execute()) {
                $insertedCount++;
            } else {
                $errors[] = "Failed to insert record for employee ID {$employeeId}: " . $stmt->error;
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "Error processing row: " . $e->getMessage();
        }
    }

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'File uploaded and processed successfully',
        'records_processed' => count($data),
        'records_inserted' => $insertedCount,
        'file_upload_id' => $fileUploadId
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    sendJsonResponse($response);

    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['size'] > $maxSize) {
        sendJsonResponse(['error' => 'File size exceeds 10MB limit'], 400);
    }

    if (!in_array($file['type'], $allowedTypes) && !in_array($fileExt, ['csv', 'xlsx', 'xls'])) {
        sendJsonResponse(['error' => 'Invalid file type. Only Excel or CSV files are allowed'], 400);
    }

    // Create upload directory
    $uploadDir = "../../uploads/$month";
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            sendJsonResponse(['error' => 'Failed to create upload directory'], 500);
        }
    }

    // Generate filename and move file
    $filename = uniqid() . '.' . $fileExt;
    $targetPath = "$uploadDir/$filename";

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        sendJsonResponse(['error' => 'Failed to save uploaded file'], 500);
    }

    // Choose reader
    if ($fileExt === 'csv') {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
    } elseif ($fileExt === 'xlsx') {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    } elseif ($fileExt === 'xls') {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
    } else {
        throw new Exception('Unsupported file format');
    }

    // Load spreadsheet
    $spreadsheet = $reader->load($targetPath);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();

    // Begin transaction
    $conn->begin_transaction();
    $stmt = $conn->prepare("INSERT INTO records (employee_id, name, position, salary_grade, status, division_id, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $recordsProcessed = 0;
    $errors = [];

    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            $employee_id = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue());
            $name = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue());
            $position = trim($worksheet->getCellByColumnAndRow(3, $row)->getValue());
            $salary_grade = trim($worksheet->getCellByColumnAndRow(4, $row)->getValue());
            $status = trim(strtolower($worksheet->getCellByColumnAndRow(5, $row)->getValue() ?? 'active'));
            $division_id = (int)($worksheet->getCellByColumnAndRow(6, $row)->getValue() ?? $division);
            $created_by = $_SESSION['user_id'] ?? 1;

            if (empty($employee_id) || empty($name) || empty($position) || empty($salary_grade)) {
                $errors[] = "Row $row: Missing required fields";
                continue;
            }

            $stmt->bind_param("sssssii", 
                $employee_id,
                $name,
                $position,
                $salary_grade,
                $status,
                $division_id,
                $created_by
            );

            if ($stmt->execute()) {
                $recordsProcessed++;
            } else {
                $errors[] = "Row $row: " . $stmt->error;
            }
        } catch (Exception $e) {
            $errors[] = "Row $row: " . $e->getMessage();
        }
    }

    if ($recordsProcessed > 0) {
        $conn->commit();

        $activity_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'import', ?, ?)");
        $description = "Imported $recordsProcessed records from file: $filename";
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_id = $_SESSION['user_id'] ?? 1;
        $activity_stmt->bind_param("iss", $user_id, $description, $ip_address);
        $activity_stmt->execute();

        sendJsonResponse([
            'success' => true,
            'message' => empty($errors) ? 'File processed successfully' : 'File processed with some errors',
            'filename' => $filename,
            'records_processed' => $recordsProcessed,
            'errors' => $errors
        ]);
    } else {
        $conn->rollback();
        unlink($targetPath);
        sendJsonResponse([
            'error' => 'No records could be processed',
            'details' => $errors
        ], 400);
    }

} catch (Exception $e) {
    if (isset($targetPath) && file_exists($targetPath)) {
        unlink($targetPath);
    }
    if (isset($conn)) {
        $conn->rollback();
    }

    error_log("Fatal error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'An internal error occurred',
        'message' => $e->getMessage()
    ], 500);
}
