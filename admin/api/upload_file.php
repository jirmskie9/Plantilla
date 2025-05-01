<?php
header('Content-Type: application/json');
require '../../dbconnection.php';

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create log directory if not exists
if (!file_exists('../../logs')) {
    mkdir('../../logs', 0755, true);
}

$logFile = '../../logs/upload_debug.log';
file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Upload process started\n", FILE_APPEND);

// Verify file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error = $_FILES['file']['error'] ?? 'No file uploaded';
    $errorMessages = [
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    ];
    $errorMsg = $errorMessages[$error] ?? "Unknown upload error ($error)";
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Upload failed: $errorMsg\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

$file = $_FILES['file'];
$division_id = $_POST['division'] ?? 0;
file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] File received: ".print_r($file, true)."\n", FILE_APPEND);
file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Division ID: $division_id\n", FILE_APPEND);

// Validate division
if (empty($division_id)) {
    $error = "Division is required";
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

// Validate division exists
$stmt = $conn->prepare("SELECT id FROM divisions WHERE id = ?");
$stmt->bind_param('i', $division_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $error = "Invalid division ID: $division_id";
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

// Validate file type and extension
$allowedTypes = [
    'text/csv',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/octet-stream',
    'application/x-csv',
    'text/x-csv',
    'text/comma-separated-values',
    'text/plain'
];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, ['csv', 'xls', 'xlsx'])) {
    $error = "Invalid file extension. Only CSV, XLS, and XLSX files are allowed. Detected extension: $fileExt";
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

// Process file contents
try {
    $conn->autocommit(FALSE);
    
    // Check if file is CSV or Excel
    if ($fileExt === 'csv') {
        processCSV($file['tmp_name'], $conn, $logFile, $division_id);
    } else {
        // For Excel files, use a simple approach
        $data = readExcelFile($file['tmp_name'], $logFile);
        if ($data) {
            processExcelData($data, $conn, $logFile, $division_id);
        } else {
            throw new Exception('Failed to read Excel file');
        }
    }
    
    $conn->commit();
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] File processed successfully\n", FILE_APPEND);
    echo json_encode(['success' => true, 'message' => 'File uploaded successfully']);
} catch (Exception $e) {
    $conn->rollback();
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Error: ".$e->getMessage()."\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function processCSV($filePath, $conn, $logFile, $division_id) {
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Processing CSV file\n", FILE_APPEND);
    
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        throw new Exception('Failed to open CSV file');
    }

    // Get and validate headers
    $headers = fgetcsv($handle);
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] CSV headers: ".print_r($headers, true)."\n", FILE_APPEND);
    
    $requiredHeaders = ['employee_id', 'name', 'position', 'salary_grade', 'status'];
    foreach ($requiredHeaders as $header) {
        if (!in_array($header, $headers)) {
            throw new Exception("Missing required column: $header");
        }
    }

    // Prepare statements
    $insertStmt = $conn->prepare("INSERT INTO records (employee_id, name, position, salary_grade, status, division_id) VALUES (?, ?, ?, ?, ?, ?)");
    $checkStmt = $conn->prepare("SELECT id FROM records WHERE employee_id = ?");
    
    // Process rows
    $rowCount = 0;
    $imported = 0;
    $duplicates = 0;
    $invalidStatus = 0;
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        $rowCount++;
        $row = array_combine($headers, $data);
        
        // Validate status
        if (!in_array(strtolower($row['status']), ['active', 'inactive'])) {
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Invalid status in row $rowCount: ".$row['status']."\n", FILE_APPEND);
            $invalidStatus++;
            continue;
        }
        
        // Check for duplicate employee_id
        $checkStmt->bind_param('s', $row['employee_id']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Duplicate employee_id in row $rowCount: ".$row['employee_id']."\n", FILE_APPEND);
            $duplicates++;
            continue;
        }
        
        // Insert record
        $insertStmt->bind_param('sssssi', 
            $row['employee_id'],
            $row['name'],
            $row['position'],
            $row['salary_grade'],
            strtolower($row['status']),
            $division_id
        );
        
        if ($insertStmt->execute()) {
            $imported++;
        } else {
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Failed to insert row $rowCount: ".$insertStmt->error."\n", FILE_APPEND);
        }
    }
    
    fclose($handle);
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Processed $rowCount rows, imported $imported records, $duplicates duplicates skipped, $invalidStatus invalid status skipped\n", FILE_APPEND);
    return $imported;
}

function readExcelFile($filePath, $logFile) {
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Reading Excel file: $filePath\n", FILE_APPEND);
    
    // Create a temporary CSV file
    $csvFile = tempnam(sys_get_temp_dir(), 'excel_');
    $csvFile .= '.csv';
    
    // Use PHP's built-in file functions to read the file
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Failed to open Excel file\n", FILE_APPEND);
        return false;
    }
    
    $data = [];
    $headers = [];
    $firstRow = true;
    $rowCount = 0;
    
    // Read the file line by line
    while (($line = fgets($handle)) !== false) {
        $rowCount++;
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Reading row $rowCount\n", FILE_APPEND);
        
        // Try to parse the line as CSV
        $row = str_getcsv($line);
        
        if ($firstRow) {
            $headers = array_map('strtolower', $row);
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Headers found: ".implode(', ', $headers)."\n", FILE_APPEND);
            $firstRow = false;
            continue;
        }
        
        if (count($row) >= count($headers)) {
            $data[] = array_combine($headers, $row);
        } else {
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Row $rowCount has incorrect number of columns: ".count($row)." vs ".count($headers)."\n", FILE_APPEND);
        }
    }
    
    fclose($handle);
    
    if (empty($data)) {
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] No data found in Excel file\n", FILE_APPEND);
        return false;
    }
    
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Successfully read " . count($data) . " rows from Excel file\n", FILE_APPEND);
    return $data;
}

function processExcelData($data, $conn, $logFile, $division_id) {
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Processing Excel data\n", FILE_APPEND);
    
    // Validate required headers
    $requiredHeaders = ['employee_id', 'name', 'position', 'salary_grade', 'status'];
    $headers = array_keys($data[0] ?? []);
    
    foreach ($requiredHeaders as $header) {
        if (!in_array($header, $headers)) {
            throw new Exception("Missing required column: $header");
        }
    }
    
    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO records (employee_id, name, position, salary_grade, status, division_id) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Process rows
    $imported = 0;
    $duplicates = 0;
    $invalidStatus = 0;
    $rowCount = 0;
    
    foreach ($data as $row) {
        $rowCount++;
        
        // Validate status
        if (!in_array(strtolower($row['status']), ['active', 'inactive'])) {
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Invalid status in row $rowCount: ".$row['status']."\n", FILE_APPEND);
            $invalidStatus++;
            continue;
        }
        
        // Insert record
        $stmt->bind_param('sssssi', 
            $row['employee_id'],
            $row['name'],
            $row['position'],
            $row['salary_grade'],
            strtolower($row['status']),
            $division_id
        );
        
        if ($stmt->execute()) {
            $imported++;
        } else {
            if (strpos($stmt->error, 'Duplicate entry') !== false) {
                $duplicates++;
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Duplicate employee_id in row $rowCount: ".$row['employee_id']."\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Failed to insert row $rowCount: ".$stmt->error."\n", FILE_APPEND);
            }
        }
    }
    
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Processed $rowCount rows, imported $imported records, $duplicates duplicates skipped, $invalidStatus invalid status skipped\n", FILE_APPEND);
    return $imported;
}
