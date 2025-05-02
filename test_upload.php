<?php
session_start();
require 'dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $errors = [];
    $success = false;
    $message = '';
    
    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check if file is CSV
        if ($fileType !== 'csv') {
            $errors[] = "Only CSV files are allowed.";
        } else {
            // Create uploads directory if it doesn't exist
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $fileName = uniqid('test_upload_') . '.' . $fileType;
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Process CSV file
                if (($handle = fopen($filePath, "r")) !== FALSE) {
                    // Get header row and normalize column names
                    $headers = fgetcsv($handle);
                    $normalizedHeaders = array_map(function($header) {
                        return strtolower(str_replace([' ', '/', '-'], '_', trim($header)));
                    }, $headers);
                    
                    // Create a mapping of normalized headers to their positions
                    $headerMap = array_flip($normalizedHeaders);
                    
                    // Log the header mapping for debugging
                    error_log("Header mapping: " . print_r($headerMap, true));
                    
                    // Prepare insert statement
                    $sql = "INSERT INTO records (
                    division_id, plantilla_no, plantilla_division, plantilla_section,
                    equivalent_division, plantilla_division_definition, plantilla_section_definition,
                    fullname, last_name, first_name, middle_name, ext_name, mi, sex,
                    position_title, item_number, tech_code, level, appointment_status,
                        sg, step, monthly_salary, date_of_birth, date_orig_appt, date_govt_srvc,
                        date_last_promotion, date_last_increment, date_longevity, date_vacated,
                        vacated_due_to, vacated_by, id_no, created_by, updated_by, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    
                    $insertStmt = $conn->prepare($sql);
                    
                    if (!$insertStmt) {
                        $errors[] = "Prepare failed: " . $conn->error;
                    } else {
                        $rowCount = 0;
                        while (($data = fgetcsv($handle)) !== FALSE) {
                            // Map CSV columns to database fields using normalized headers
                            $divisionId = intval(1); // Default division ID
                            $plantillaNo = $data[$headerMap['plantilla_no']] ?? null;
                            $plantillaDivision = $data[$headerMap['plantilla_division']] ?? null;
                            $plantillaSection = $data[$headerMap['plantilla_section_station']] ?? null;
                            $equivalentDivision = $data[$headerMap['equivalent_division']] ?? null;
                            $plantillaDivisionDefinition = $data[$headerMap['plantilla_division_definition']] ?? null;
                            $plantillaSectionDefinition = $data[$headerMap['plantilla_section_definition']] ?? null;
                            $fullname = $data[$headerMap['fullname']] ?? null;
                            $lastName = $data[$headerMap['last_name']] ?? null;
                            $firstName = $data[$headerMap['first_name']] ?? null;
                            $middleName = $data[$headerMap['middle_name']] ?? null;
                            $extName = $data[$headerMap['ext_name']] ?? null;
                            $mi = $data[$headerMap['mi']] ?? null;
                            $sex = $data[$headerMap['sex']] ?? null;
                            $positionTitle = $data[$headerMap['position_title']] ?? null;
                            $itemNumber = $data[$headerMap['item_number']] ?? null;
                            $techCode = $data[$headerMap['tech_code']] ?? null;
                            $level = $data[$headerMap['level']] ?? null;
                            $appointmentStatus = $data[$headerMap['appointment_status']] ?? null;
                            $sg = intval($data[$headerMap['sg']] ?? 0);
                            $step = intval($data[$headerMap['step']] ?? 0);
                            $monthlySalary = floatval(str_replace([' ', ',', '₱', 'PHP'], '', $data[$headerMap['monthly_salary']] ?? 0));
                            $dateOfBirth = $data[$headerMap['date_of_birth']] ?? null;
                            $dateOrigAppt = $data[$headerMap['date_orig_appt']] ?? null;
                            $dateGovtSrvc = $data[$headerMap['date_govt_srvc']] ?? null;
                            $dateLastPromotion = $data[$headerMap['date_last_promotion']] ?? null;
                            $dateLastIncrement = $data[$headerMap['date_last_increment']] ?? null;
                            $dateLongevity = $data[$headerMap['date_of_longevity']] ?? null;
                            $dateVacated = $data[$headerMap['date_vacated']] ?? null;
                            $vacatedDueTo = $data[$headerMap['vacated_due_to']] ?? null;
                            $vacatedBy = $data[$headerMap['vacated_by']] ?? null;
                            $idNo = $data[$headerMap['id_no']] ?? null;
                            
                            // Format dates to YYYY-MM-DD
                            $dateFields = [
                                'dateOfBirth' => $dateOfBirth,
                                'dateOrigAppt' => $dateOrigAppt,
                                'dateGovtSrvc' => $dateGovtSrvc,
                                'dateLastPromotion' => $dateLastPromotion,
                                'dateLastIncrement' => $dateLastIncrement,
                                'dateLongevity' => $dateLongevity,
                                'dateVacated' => $dateVacated
                            ];
                            
                            foreach ($dateFields as $field => $value) {
                                if (!empty($value)) {
                                    $timestamp = strtotime($value);
                                    if ($timestamp !== false) {
                                        $$field = date('Y-m-d', $timestamp);
                                    } else {
                                        $$field = null;
                                    }
                                } else {
                                    $$field = null;
                                }
                            }
                            
                            // Bind parameters
                            $bindResult = $insertStmt->bind_param(
                                "issssssssssssssssssssssssssssssssi",
                    $divisionId, $plantillaNo, $plantillaDivision, $plantillaSection,
                    $equivalentDivision, $plantillaDivisionDefinition, $plantillaSectionDefinition,
                    $fullname, $lastName, $firstName, $middleName, $extName, $mi, $sex,
                    $positionTitle, $itemNumber, $techCode, $level, $appointmentStatus,
                                $sg, $step, $monthlySalary, $dateOfBirth, $dateOrigAppt, $dateGovtSrvc,
                                $dateLastPromotion, $dateLastIncrement, $dateLongevity, $dateVacated,
                                $vacatedDueTo, $vacatedBy, $idNo, $_SESSION['user_id'], $_SESSION['user_id']
                            );
                            
                            if (!$bindResult) {
                                $errors[] = "Row {$rowCount} bind error: " . $insertStmt->error;
                    continue;
                }

                            if (!$insertStmt->execute()) {
                                $errors[] = "Row {$rowCount} insert error: " . $insertStmt->error;
                    continue;
                }

                            $rowCount++;
                        }
                        
                        fclose($handle);
                        $insertStmt->close();
                        
                        // Clean up the uploaded file
                        unlink($filePath);
                        
                        if ($rowCount > 0) {
                            $success = true;
                            $message = "Successfully imported $rowCount records.";
                        }
                    }
                } else {
                    $errors[] = "Could not open the uploaded file.";
                }
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        }
    } else {
        $errors[] = "Error uploading file: " . $file['error'];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'errors' => $errors
    ]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test CSV Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Test CSV Upload</h2>
        
        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Upload Test CSV File</h5>
                <p class="card-text">The CSV file should contain the following columns in order:</p>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Column Name</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>plantilla_no</td><td>Plantilla Number</td></tr>
                            <tr><td>plantilla_division</td><td>Plantilla Division</td></tr>
                            <tr><td>plantilla_section</td><td>Plantilla Section</td></tr>
                            <tr><td>equivalent_division</td><td>Equivalent Division</td></tr>
                            <tr><td>plantilla_division_definition</td><td>Plantilla Division Definition</td></tr>
                            <tr><td>plantilla_section_definition</td><td>Plantilla Section Definition</td></tr>
                            <tr><td>fullname</td><td>Full Name</td></tr>
                            <tr><td>last_name</td><td>Last Name</td></tr>
                            <tr><td>first_name</td><td>First Name</td></tr>
                            <tr><td>middle_name</td><td>Middle Name</td></tr>
                            <tr><td>ext_name</td><td>Extension Name</td></tr>
                            <tr><td>mi</td><td>Middle Initial</td></tr>
                            <tr><td>sex</td><td>Gender</td></tr>
                            <tr><td>position_title</td><td>Position Title</td></tr>
                            <tr><td>item_number</td><td>Item Number</td></tr>
                            <tr><td>tech_code</td><td>Technical Code</td></tr>
                            <tr><td>level</td><td>Level</td></tr>
                            <tr><td>appointment_status</td><td>Appointment Status</td></tr>
                            <tr><td>sg</td><td>Salary Grade</td></tr>
                            <tr><td>step</td><td>Step</td></tr>
                            <tr><td>monthly_salary</td><td>Monthly Salary</td></tr>
                            <tr><td>date_of_birth</td><td>Date of Birth (YYYY-MM-DD)</td></tr>
                            <tr><td>date_orig_appt</td><td>Date of Original Appointment (YYYY-MM-DD)</td></tr>
                            <tr><td>date_govt_srvc</td><td>Date of Government Service (YYYY-MM-DD)</td></tr>
                            <tr><td>date_last_promotion</td><td>Date of Last Promotion (YYYY-MM-DD)</td></tr>
                            <tr><td>date_last_increment</td><td>Date of Last Increment (YYYY-MM-DD)</td></tr>
                            <tr><td>date_longevity</td><td>Date of Longevity (YYYY-MM-DD)</td></tr>
                            <tr><td>date_vacated</td><td>Date Vacated (YYYY-MM-DD)</td></tr>
                            <tr><td>vacated_due_to</td><td>Vacated Due To</td></tr>
                            <tr><td>vacated_by</td><td>Vacated By</td></tr>
                            <tr><td>id_no</td><td>ID Number</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Upload
                    </button>
                </form>
            </div>
        </div>
        
        <div class="mt-4">
            <h5>Sample CSV Format</h5>
            <pre class="bg-light p-3 rounded">
plantilla_no,plantilla_division,plantilla_section,equivalent_division,plantilla_division_definition,plantilla_section_definition,fullname,last_name,first_name,middle_name,ext_name,mi,sex,position_title,item_number,tech_code,level,appointment_status,sg,step,monthly_salary,date_of_birth,date_orig_appt,date_govt_srvc,date_last_promotion,date_last_increment,date_longevity,date_vacated,vacated_due_to,vacated_by,id_no
12345,DIV-001,SEC-001,EQUIV-001,Division Definition,Section Definition,John Doe Smith,Doe,John,Smith,,J,M,Administrative Officer,ITEM-001,TECH-001,1,Permanent,11,1,25000,1990-01-01,2010-01-01,2010-01-01,2015-01-01,2018-01-01,2020-01-01,,,EMP-001
            </pre>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
