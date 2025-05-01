<?php
// Ensure no output before headers
if (ob_get_level()) ob_end_clean();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/upload_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0755, true);
}

// Set JSON header
header('Content-Type: application/json');

// Custom error handler
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error occurred. Please check logs.'
    ]);
    exit;
}
set_error_handler('handleError');

// Custom exception handler
function handleException($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
set_exception_handler('handleException');

try {
    // Debug log start of request
    error_log("Starting file upload process");
    error_log("POST data: " . json_encode($_POST));
    error_log("FILES data: " . json_encode($_FILES));

    require '../../dbconnection.php';

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Debug session
    error_log("Session data: " . json_encode($_SESSION));

    // Get current user ID for tracking (default to 1 if not set - for testing)
    $user_id = $_SESSION['user_id'] ?? 1;
    error_log("Using user_id: $user_id");

    // Create upload directory if needed
    $upload_dir = __DIR__ . '/../../uploads';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }

    // Verify file upload
    if (!isset($_FILES['file'])) {
        throw new Exception('No file was uploaded');
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        throw new Exception($upload_errors[$_FILES['file']['error']] ?? 'Unknown upload error');
    }

    $file = $_FILES['file'];
    error_log("Processing uploaded file: " . json_encode($file));

    // Validate file type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
        throw new Exception("Invalid file type: $ext. Please upload a CSV or Excel file.");
    }

    // Generate unique filename and move file
    $uniqueFilename = uniqid('upload_', true) . '.' . $ext;
    $targetPath = $upload_dir . '/' . $uniqueFilename;

    error_log("Attempting to move file to: $targetPath");
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to move uploaded file to $targetPath");
    }
    error_log("File saved successfully to: $targetPath");

    // Verify file is readable
    if (!is_readable($targetPath)) {
        throw new Exception("Cannot read uploaded file");
    }

    // Record the upload
    $stmt = $conn->prepare("INSERT INTO file_uploads (user_id, file_name, file_path, file_type, file_size, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param('isssi', $user_id, $file['name'], $uniqueFilename, $ext, $file['size']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to record file upload: " . $stmt->error);
    }
    $upload_id = $conn->insert_id;
    error_log("Upload recorded with ID: $upload_id");

    try {
        // Read CSV file
        $handle = fopen($targetPath, 'r');
        if ($handle === false) {
            throw new Exception("Could not open file: $targetPath");
        }

        // Read and validate header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            throw new Exception("Could not read CSV headers");
        }

        // Debug raw headers
        error_log("Raw CSV Headers: " . json_encode($headers));

        // Clean and normalize headers
        $headers = array_map(function($header) {
            // Remove BOM if present
            $header = preg_replace('/^\x{FEFF}/u', '', $header);
            // Remove quotes if present
            $header = trim($header, '"');
            // Convert to lowercase and remove extra spaces
            $header = strtolower(trim($header));
            return $header;
        }, $headers);

        error_log("Cleaned Headers: " . json_encode($headers));

        // Verify required columns
        $required_columns = ['employee_id', 'name', 'position', 'salary_grade', 'status', 'division_id'];
        $header_map = [];
        
        // Create a map of header positions
        foreach ($headers as $index => $header) {
            $header_map[$header] = $index;
            error_log("Mapping header: '$header' to index: $index");
        }
        
        error_log("Final Header Map: " . json_encode($header_map));
        
        // Check for missing columns
        $missing_columns = [];
        foreach ($required_columns as $column) {
            error_log("Checking for column: '$column'");
            if (!isset($header_map[$column])) {
                error_log("Column '$column' not found in headers");
                $missing_columns[] = $column;
            } else {
                error_log("Column '$column' found at index: " . $header_map[$column]);
            }
        }
        
        if (!empty($missing_columns)) {
            error_log("Missing columns: " . implode(', ', $missing_columns));
            error_log("Available headers: " . implode(', ', $headers));
            throw new Exception("Missing required columns: " . implode(', ', $missing_columns) . 
                              ". Available columns: " . implode(', ', $headers));
        }
        
        // Start transaction
        $conn->begin_transaction();
        error_log("Starting database transaction");

        // Prepare insert statement
        $stmt = $conn->prepare("
            INSERT INTO records (
                employee_id, name, position, salary_grade, status, division_id,
                created_by, data, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        if (!$stmt) {
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }

        $imported = 0;
        $errors = [];
        $row_number = 1; // Start after header row
        $total_rows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            $total_rows++;
            
            // Skip empty rows
            if (empty($row[0])) {
                error_log("Skipping empty row $row_number");
                continue;
            }
            
            error_log("Processing row $row_number: " . json_encode($row));
            
            try {
                // If the row is a single string, split it into columns
                if (count($row) === 1) {
                    $row = str_getcsv($row[0]);
                    error_log("Split row into columns: " . json_encode($row));
                }

                // Clean row data
                $row = array_map(function($value) {
                    return trim($value, '"');
                }, $row);

                // Map CSV columns to variables using header positions
                $employee_id = trim($row[$header_map['employee_id']]);
                $name = trim($row[$header_map['name']]);
                $position = trim($row[$header_map['position']]);
                $salary_grade = trim($row[$header_map['salary_grade']]);
                $status = strtolower(trim($row[$header_map['status']]));
                $division_id = (int)trim($row[$header_map['division_id']]);

                // Log mapped data
                error_log("Mapped data for row $row_number: " . json_encode([
                    'employee_id' => $employee_id,
                    'name' => $name,
                    'position' => $position,
                    'salary_grade' => $salary_grade,
                    'status' => $status,
                    'division_id' => $division_id
                ]));

                // Validate required fields
                if (empty($employee_id)) {
                    throw new Exception("Employee ID is required");
                }
                if (empty($name)) {
                    throw new Exception("Name is required");
                }

                // Validate status
                if (empty($status)) {
                    throw new Exception("Status is required");
                }
                if (!in_array($status, ['active', 'inactive'])) {
                    throw new Exception("Invalid status '$status'. Must be 'active' or 'inactive'");
                }

                // Validate division_id
                if ($division_id <= 0) {
                    throw new Exception("Invalid Division ID: $division_id");
                }

                // Check division exists
                $div_check = $conn->prepare("SELECT id FROM divisions WHERE id = ?");
                $div_check->bind_param('i', $division_id);
                $div_check->execute();
                if ($div_check->get_result()->num_rows === 0) {
                    throw new Exception("Division ID $division_id does not exist");
                }

                // Prepare additional data
                $additional_data = json_encode([
                    'upload_id' => $upload_id,
                    'row_number' => $row_number,
                    'imported_at' => date('Y-m-d H:i:s')
                ]);

                // Insert record
                $stmt->bind_param('sssssiss',
                    $employee_id,
                    $name,
                    $position,
                    $salary_grade,
                    $status,
                    $division_id,
                    $user_id,
                    $additional_data
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Database error: " . $stmt->error);
                }

                $imported++;
                error_log("Successfully imported row $row_number");

            } catch (Exception $e) {
                $error_msg = "Row $row_number: " . $e->getMessage();
                error_log("Error - " . $error_msg);
                $errors[] = $error_msg;
            }
        }

        fclose($handle);
        error_log("Finished processing CSV file. Total rows: $total_rows, Imported: $imported, Errors: " . count($errors));

        // Finalize transaction
        if ($imported > 0) {
            $conn->commit();
            error_log("Transaction committed - $imported records imported");
            
            // Update file_uploads status
            $conn->prepare("UPDATE file_uploads SET status = 'processed' WHERE id = ?")->execute([$upload_id]);
            
            // Log activity
            $conn->prepare("
                INSERT INTO activity_logs (user_id, activity_type, description, ip_address) 
                VALUES (?, 'upload', ?, ?)
            ")->execute([
                $user_id,
                "Imported $imported records from {$file['name']}",
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $response = [
                'success' => true,
                'imported' => $imported,
                'total_rows' => $total_rows,
                'errors' => $errors,
                'message' => "Successfully imported $imported records" . 
                            (count($errors) > 0 ? " with " . count($errors) . " errors" : "")
            ];
            error_log("Sending success response: " . json_encode($response));
            echo json_encode($response);
        } else {
            error_log("No records were successfully imported. Total rows processed: $total_rows");
            if (!empty($errors)) {
                error_log("Errors encountered: " . implode("\n", $errors));
            }
            throw new Exception("No records were successfully imported. Please check your data and try again.");
        }

    } catch (Exception $e) {
        throw $e;
    } finally {
        // Clean up the uploaded file
        if (file_exists($targetPath)) {
            unlink($targetPath);
            error_log("Cleaned up temporary file: $targetPath");
        }
    }

} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
        error_log("Transaction rolled back");
        
        // Update file_uploads status if record was created
        if (isset($upload_id)) {
            $conn->prepare("UPDATE file_uploads SET status = 'failed' WHERE id = ?")->execute([$upload_id]);
        }
    }

    $error_response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    error_log("Sending error response: " . json_encode($error_response));
    echo json_encode($error_response);
}
