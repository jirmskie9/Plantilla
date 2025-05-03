<?php
session_start();
require '../dbconnection.php';

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Handle file upload first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // Clear any previous output
    ob_clean();
    
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
            $uploadDir = '../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $fileName = uniqid('upload_') . '.' . $fileType;
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Record file upload
                $stmt = $conn->prepare("INSERT INTO file_uploads (user_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, 'csv', ?)");
                $stmt->bind_param("issi", $_SESSION['user_id'], $file['name'], $filePath, $file['size']);
                $stmt->execute();
                $uploadId = $stmt->insert_id;
                
                // Process CSV file
                if (($handle = fopen($filePath, "r")) !== FALSE) {
                    // Skip header row
                    fgetcsv($handle);
                    
                    // Prepare insert statement
                    $sql = "INSERT INTO records (
                        plantilla_no, plantilla_division, plantilla_section,
                        equivalent_division, plantilla_division_definition, plantilla_section_definition,
                        fullname, last_name, first_name, middle_name, ext_name, mi, sex,
                        position_title, item_number, tech_code, level, appointment_status,
                        sg, step, monthly_salary, date_of_birth, date_orig_appt, date_govt_srvc,
                        date_last_promotion, date_last_increment, date_longevity, date_vacated,
                        vacated_due_to, vacated_by, id_no, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $insertStmt = $conn->prepare($sql);
                    
                    if (!$insertStmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }

                    $rowCount = 0;
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        // Map CSV columns to database fields
                        $plantillaNo = $data[0] ?? null;
                        $plantillaDivision = $data[1] ?? null;
                        $plantillaSection = $data[2] ?? null;
                        $equivalentDivision = $data[3] ?? null;
                        $plantillaDivisionDefinition = $data[4] ?? null;
                        $plantillaSectionDefinition = $data[5] ?? null;
                        $fullname = $data[6] ?? null;
                        $lastName = $data[7] ?? null;
                        $firstName = $data[8] ?? null;
                        $middleName = $data[9] ?? null;
                        $extName = $data[10] ?? null;
                        $mi = $data[11] ?? null;
                        $sex = $data[12] ?? null;
                        $positionTitle = $data[13] ?? null;
                        $itemNumber = $data[14] ?? null;
                        $techCode = $data[15] ?? null;
                        $level = $data[16] ?? null;
                        $appointmentStatus = $data[17] ?? null;
                        $sg = $data[18] ?? null;
                        $step = $data[19] ?? null;
                        $monthlySalary = $data[20] ?? null;
                        $dateOfBirth = $data[21] ?? null;
                        $dateOrigAppt = $data[22] ?? null;
                        $dateGovtSrvc = $data[23] ?? null;
                        $dateLastPromotion = $data[24] ?? null;
                        $dateLastIncrement = $data[25] ?? null;
                        $dateLongevity = $data[26] ?? null;
                        $dateVacated = $data[27] ?? null;
                        $vacatedDueTo = $data[28] ?? null;
                        $vacatedBy = $data[29] ?? null;
                        $idNo = $data[30] ?? null;
                        
                        // Bind parameters
                        $insertStmt->bind_param(
                            "sssssssssssssssssssssssssssssssi",
                            $plantillaNo, $plantillaDivision, $plantillaSection,
                            $equivalentDivision, $plantillaDivisionDefinition, $plantillaSectionDefinition,
                            $fullname, $lastName, $firstName, $middleName, $extName, $mi, $sex,
                            $positionTitle, $itemNumber, $techCode, $level, $appointmentStatus,
                            $sg, $step, $monthlySalary, $dateOfBirth, $dateOrigAppt, $dateGovtSrvc,
                            $dateLastPromotion, $dateLastIncrement, $dateLongevity, $dateVacated,
                            $vacatedDueTo, $vacatedBy, $idNo, $_SESSION['user_id']
                        );
                        
                        if (!$insertStmt->execute()) {
                            throw new Exception("Execute failed: " . $insertStmt->error);
                        }
                        
                        $rowCount++;
                    }
                    
                    fclose($handle);
                    $insertStmt->close();
                    
                    // Update file upload status
                    $updateStmt = $conn->prepare("UPDATE file_uploads SET status = ? WHERE id = ?");
                    $status = empty($errors) ? 'processed' : 'failed';
                    $updateStmt->bind_param("si", $status, $uploadId);
                    $updateStmt->execute();
                    
                    $success = true;
                    $message = "Successfully imported $rowCount records.";
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
    
    // Clear any output buffer
    ob_clean();
    
    // Set proper headers for JSON response
    header('Content-Type: application/json');
    
    // Return JSON response
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'error' => !empty($errors) ? implode("\n", $errors) : null
    ]);
    exit();
}

// Initialize variables
$selected_division = isset($_GET['division']) ? (int)$_GET['division'] : 0;
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Helper function to get division name by ID
function getDivisionName($id) {
    global $conn;
    if ($id == 0) return 'All Divisions';
    
    $stmt = $conn->prepare("SELECT name FROM divisions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $division = $result->fetch_assoc();
        return $division['name'] ?? 'Unknown Division';
    }
    
    return 'Unknown Division';
}

// Helper function to get available files for selected month
function getMonthlyFiles($month) {
    $dir = '../uploads/' . $month;
    if (!file_exists($dir)) {
        return [];
    }
    $files = scandir($dir);
    return array_filter($files, function($file) {
        return $file !== '.' && $file !== '..' && !is_dir($file);
    });
}

// Get available months from records table
function getAvailableMonths() {
    global $conn;
    $months = [];
    $query = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month 
              FROM records 
              ORDER BY month DESC";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $months[] = $row['month'];
        }
    }
    return $months;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $success = false;
    $message = '';
    
    try {
        // Get form data
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $role = $_POST['role'];
        
        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($role)) {
            throw new Exception('All fields are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ss", $username, $email);
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception('Username or email already exists');
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $stmt->bind_param("ssssss", $username, $hashed_password, $email, $first_name, $last_name, $role);
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            $new_user_id = $stmt->insert_id;
            
            // Insert default permissions
            $modules = ['dashboard', 'organizational_codes', 'applicants'];
            foreach ($modules as $module) {
                $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, module, can_view, can_create, can_edit, can_delete) VALUES (?, ?, TRUE, FALSE, FALSE, FALSE)");
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                
                $stmt->bind_param("is", $new_user_id, $module);
                if (!$stmt->execute()) {
                    throw new Exception('Database error: ' . $stmt->error);
                }
            }
            
            // Log the activity
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'create', ?, ?)");
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $description = "Created new user: $username";
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_id = $_SESSION['user_id'] ?? 1;
            $stmt->bind_param("iss", $user_id, $description, $ip_address);
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = true;
            $message = 'User added successfully';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
    
    // Store the result in session for SweetAlert
    $_SESSION['alert'] = [
        'success' => $success,
        'message' => $message
    ];
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with prepared statements
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ssss';
}

if (!empty($role)) {
    $query .= " AND role = ?";
    $params[] = $role;
    $types .= 's';
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= 's';
}

// Execute query with prepared statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get available months
$available_months = getAvailableMonths();
$monthly_files = getMonthlyFiles($selected_month);

// Check if user is logged in and has admin role
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../login.php');
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Workbook - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="bi bi-building"></i>
            </div>
            <div class="title">
                <h4><?php echo $_SESSION['username']; ?></h4>
                <p>Plantilla Management</p>
            </div>
        </div>
        <div class="sidebar-content">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="data_management.php">
                        <i class="bi bi-database"></i>
                        <span>Data Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="applicant_records.php">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Applicant Records</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_management.php">
                        <i class="bi bi-people"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_account.php">
                        <i class="bi bi-person-circle"></i>
                        <span>My Account</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
           
           <div class="logout-btn">
           <a class="nav-link" onclick="return confirm('Are you sure you want to logout?')" href="logout.php">
                   <i class="bi bi-box-arrow-right"></i>
                   <span>Logout</span>
               </a>
           </div>
       </div>
    </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">New Workbook</h2>
                        <p class="text-muted mb-0">Create a new spreadsheet</p>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-primary" id="saveSpreadsheet">
                            <i class="bi bi-save me-2"></i>Save
                        </button>
                        <button class="btn btn-secondary" id="clearSpreadsheet">
                            <i class="bi bi-trash me-2"></i>Clear
                        </button>
                        <a href="data_management.php" class="btn btn-info">
                            <i class="bi bi-arrow-left me-2"></i>Return
                        </a>
                    </div>
                </div>
            </div>

            <!-- Spreadsheet Editor -->
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <!-- <div class="col-md-4">
                            <select class="form-select" id="divisionFilter">
                                <option value="0">All Divisions</option>
                                <?php 
                                $stmt = $conn->prepare("SELECT id, name FROM divisions ORDER BY name");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div> -->
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchSpreadsheet" placeholder="Search...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="btn-group w-100">
                                <button class="btn btn-outline-primary" id="addRow">
                                    <i class="bi bi-plus-lg"></i> Add Row
                                </button>
                               
                            </div>
                        </div>
                    </div>
                    <div id="spreadsheet" class="hot-table"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Handsontable
            const hot = new Handsontable(document.getElementById('spreadsheet'), {
                data: [],
                colHeaders: [
                    'ID', 'Plantilla No.', 'Plantilla Division', 'Equivalent Division', 
                    'Plantilla Division Definition', 'Full Name', 'Last Name', 'First Name', 
                    'Middle Name', 'Ext Name', 'MI', 'Sex', 'Position Title', 'Item Number', 
                    'Tech Code', 'Level', 'Appointment Status', 'SG', 'Step', 'Monthly Salary',
                    'Date of Birth', 'Date of Original Appointment', 'Date of Government Service',
                    'Date of Last Promotion', 'Date of Last Increment', 'Date of Longevity',
                    'Date Vacated', 'Vacated Due To', 'Vacated By', 'ID No.'
                ],
                columns: [
                    { data: 'id', type: 'numeric', width: 50, readOnly: true },
                    { data: 'plantilla_no', type: 'text', width: 100 },
                    { data: 'plantilla_division', type: 'text', width: 150 },
                    { data: 'equivalent_division', type: 'text', width: 150 },
                    { data: 'plantilla_division_definition', type: 'text', width: 200 },
                    { data: 'fullname', type: 'text', width: 150 },
                    { data: 'last_name', type: 'text', width: 120 },
                    { data: 'first_name', type: 'text', width: 120 },
                    { data: 'middle_name', type: 'text', width: 120 },
                    { data: 'ext_name', type: 'text', width: 80 },
                    { data: 'mi', type: 'text', width: 50 },
                    { data: 'sex', type: 'text', width: 50 },
                    { data: 'position_title', type: 'text', width: 200 },
                    { data: 'item_number', type: 'text', width: 100 },
                    { data: 'tech_code', type: 'text', width: 100 },
                    { data: 'level', type: 'text', width: 80 },
                    { data: 'appointment_status', type: 'text', width: 150 },
                    { data: 'sg', type: 'text', width: 50 },
                    { data: 'step', type: 'text', width: 50 },
                    { data: 'monthly_salary', type: 'numeric', width: 120 },
                    { data: 'date_of_birth', type: 'date', width: 120 },
                    { data: 'date_orig_appt', type: 'date', width: 150 },
                    { data: 'date_govt_srvc', type: 'date', width: 150 },
                    { data: 'date_last_promotion', type: 'date', width: 150 },
                    { data: 'date_last_increment', type: 'date', width: 150 },
                    { data: 'date_longevity', type: 'date', width: 120 },
                    { data: 'date_vacated', type: 'date', width: 120 },
                    { data: 'vacated_due_to', type: 'text', width: 150 },
                    { data: 'vacated_by', type: 'text', width: 150 },
                    { data: 'id_no', type: 'text', width: 100 }
                ],
                rowHeaders: true,
                height: 'calc(100vh - 300px)',
                licenseKey: 'non-commercial-and-evaluation',
                contextMenu: true,
                manualColumnResize: true,
                manualRowResize: true,
                filters: true,
                dropdownMenu: true,
                hiddenColumns: {
                    indicators: true
                },
                stretchH: 'all',
                allowInsertRow: true,
                allowRemoveRow: true,
                minSpareRows: 1,
                afterChange: function(changes, source) {
                    if (source === 'edit') {
                        const rowData = hot.getSourceDataAtRow(changes[0][0]);
                        
                        // If this is a new row (no ID), save it
                        if (!rowData.id) {
                            // Convert empty strings to null for date fields
                            const dateFields = [
                                'date_of_birth', 'date_orig_appt', 'date_govt_srvc',
                                'date_last_promotion', 'date_last_increment', 'date_longevity',
                                'date_vacated'
                            ];
                            
                            dateFields.forEach(field => {
                                if (rowData[field] === '') {
                                    rowData[field] = null;
                                }
                            });

                            $.ajax({
                                url: 'api/create_record.php',
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify(rowData),
                                success: function(response) {
                                    if (response.success) {
                                        // Update the row with the new ID
                                        hot.setDataAtRowProp(changes[0][0], 'id', response.id);
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: 'Record saved successfully',
                                            showConfirmButton: false,
                                            timer: 1500
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: response.error || 'Failed to save record'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to save record: ' + error
                                    });
                                }
                            });
                        } else {
                            // Update existing record
                            $.ajax({
                                url: 'api/update_record.php',
                                method: 'POST',
                                data: {
                                    id: rowData.id,
                                    field: changes[0][1],
                                    value: changes[0][3]
                                },
                                success: function(response) {
                                    if (!response.success) {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: response.error || 'Failed to update record'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to update record: ' + error
                                    });
                                }
                            });
                        }
                    }
                }
            });

            // Add row button click handler
            $('#addRow').click(function() {
                try {
                    const currentData = hot.getData();
                    const newRow = {
                        id: null,
                        plantilla_no: '',
                        plantilla_division: $('#divisionFilter').val() ? $('#divisionFilter option:selected').text() : '',
                        equivalent_division: '',
                        plantilla_division_definition: '',
                        fullname: '',
                        last_name: '',
                        first_name: '',
                        middle_name: '',
                        ext_name: '',
                        mi: '',
                        sex: '',
                        position_title: '',
                        item_number: '',
                        tech_code: '',
                        level: '',
                        appointment_status: '',
                        sg: '',
                        step: '',
                        monthly_salary: '',
                        date_of_birth: '',
                        date_orig_appt: '',
                        date_govt_srvc: '',
                        date_last_promotion: '',
                        date_last_increment: '',
                        date_longevity: '',
                        date_vacated: '',
                        vacated_due_to: '',
                        vacated_by: '',
                        id_no: ''
                    };
                    
                    // Add the new row at the end using the correct API method
                    hot.alter('insert_row_below', currentData.length - 1, 1, newRow);
                    
                    // Select the new row
                    hot.selectCell(currentData.length, 1);
                    
                    // Scroll to the new row
                    hot.scrollViewportTo(currentData.length, 0);
                } catch (error) {
                    console.error('Error adding row:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to add new row: ' + error.message
                    });
                }
            });

            // Add column button click handler
            $('#addColumn').click(function() {
                hot.alter('insert_col');
            });

            // Clear spreadsheet button click handler
            $('#clearSpreadsheet').click(function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will clear all data in the spreadsheet",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, clear it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        hot.loadData([]);
                        Swal.fire('Cleared!', 'Spreadsheet has been cleared.', 'success');
                    }
                });
            });

            // Search functionality
            $('#searchSpreadsheet').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                hot.search.query(searchText);
                hot.render();
            });
        });
    </script>
</body>
</html>

<style>
.filters-container {
    background: #fff;
    padding: 0.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.filter-form .form-group {
    min-width: 200px;
}

.filter-form .input-group {
    border-radius: 0.375rem;
    overflow: hidden;
}

.filter-form .input-group-text {
    padding: 0.375rem 0.75rem;
    color: #6c757d;
}

.filter-form .form-select {
    padding: 0.375rem 0.75rem;
    border-left: none;
}

.filter-form .form-select:focus {
    border-color: #ced4da;
    box-shadow: none;
}

.filter-form .btn-primary {
    padding: 0.375rem 1rem;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .filters-container {
        width: 100%;
    }
    
    .filter-form {
        flex-direction: column;
        gap: 1rem !important;
    }
    
    .filter-form .form-group {
        width: 100%;
    }
    
    .filter-form .btn-primary {
        width: 100%;
    }
}
</style>