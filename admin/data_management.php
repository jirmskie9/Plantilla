<?php
session_start();
require '../dbconnection.php';

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
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
    <title>Data Management - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
  <?php include '../assets/css/user_management.php'; ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="bi bi-building"></i>
            </div>
            <div class="title">
                <h4>Admin</h4>
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
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=Admin&background=2962FF&color=fff" alt="Admin">
                <div class="user-details">
                    <h6>Administrator</h6>
                    <p>Super Admin</p>
                </div>
            </div>
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
                    <h2 class="mb-1">Data Management</h2>
                    <p class="text-muted mb-0">Manage organizational codes and spreadsheet data</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-upload me-2"></i>Upload File
                    </button>
                    <button class="btn btn-success" id="newWorkbookBtn">
                        <i class="bi bi-file-earmark-plus me-2"></i>New Workbook
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload CSV File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm" onsubmit="return false;">
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">Select CSV File</label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                <div class="form-text">Only CSV files are allowed. The file should match the sample format.</div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="uploadBtn">
                            <i class="bi bi-upload me-2"></i>Upload
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mt-3" id="dataManagementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="office-tab" data-bs-toggle="tab" data-bs-target="#office" type="button" role="tab">
                    Office and Organizational Code
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="spreadsheet-tab" data-bs-toggle="tab" data-bs-target="#spreadsheet" type="button" role="tab">
                    Spreadsheet
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="dataManagementTabsContent">
            <!-- Office and Organizational Code Tab -->
            <div class="tab-pane fade show active" id="office" role="tabpanel">
                <div class="row">
                    <!-- Division List -->
                    <div class="col-md-3 division-list">
                        <h5 class="mb-3"><i class="fas fa-sitemap me-2"></i>Divisions</h5>
                        <div class="list-group">
                            <?php
                            // Fetch all divisions from database
                            $divisions_result = $conn->query("SELECT * FROM divisions ORDER BY name");
                            $all_divisions = [];
                            while ($row = $divisions_result->fetch_assoc()) {
                                $all_divisions[] = $row;
                            }
                            ?>
                            <a href="?division=0" 
                               class="list-group-item list-group-item-action division-item <?= $selected_division == 0 ? 'active' : '' ?>"
                               data-division-id="0">
                                <i class="fas fa-layer-group me-2"></i>All Divisions
                            </a>
                            <?php foreach ($all_divisions as $division): ?>
                            <a href="?division=<?= $division['id'] ?>" 
                               class="list-group-item list-group-item-action division-item <?= $selected_division == $division['id'] ? 'active' : '' ?>"
                               data-division-id="<?= $division['id'] ?>">
                                <i class="fas fa-building me-2"></i><?= htmlspecialchars($division['name']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Data Display -->
                    <div class="col-md-9">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                                <h5>Records for <?= $selected_division == 0 ? 'All Divisions' : getDivisionName($selected_division) ?></h5>
                                <div class="filters-container">
                                    <form method="GET" class="filter-form d-flex align-items-center gap-3">
                                        <div class="form-group mb-0">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-building"></i>
                                                </span>
                                                <select name="division" id="division" class="form-select border-start-0">
                                                    <option value="0">All Divisions</option>
                                                    <?php
                                                    $stmt = $conn->prepare("SELECT id, name FROM divisions ORDER BY name");
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    while ($row = $result->fetch_assoc()) {
                                                        $selected = $selected_division == $row['id'] ? 'selected' : '';
                                                        echo "<option value='{$row['id']}' {$selected}>{$row['name']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="bi bi-calendar"></i>
                                                </span>
                                                <select name="month" id="month" class="form-select border-start-0">
                                                    <option value="">All Months</option>
                                                    <?php
                                                    $months = getAvailableMonths();
                                                    foreach ($months as $month) {
                                                        $selected = $selected_month === $month ? 'selected' : '';
                                                        echo "<option value='{$month}' {$selected}>{$month}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-funnel me-1"></i>Apply Filters
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="recordsTable">
                                        <thead>
                                            <tr>
                                                <th>PLANTILLA NO.</th>
                                                <th>PLANTILLA DIVISION</th>
                                                <th>EQUIVALENT DIVISION</th>
                                                <th>PLANTILLA DIVISION DEFINITION</th>
                                                <th>FULLNAME</th>
                                                <th>LAST NAME</th>
                                                <th>FIRST NAME</th>
                                                <th>MIDDLE NAME</th>
                                                <th>EXT NAME</th>
                                                <th>MI</th>
                                                <th>SEX</th>
                                                <th>POSITION TITLE</th>
                                                <th>ITEM NUMBER</th>
                                                <th>TECH CODE</th>
                                                <th>LEVEL</th>
                                                <th>APPOINTMENT STATUS</th>
                                                <th>SG</th>
                                                <th>STEP</th>
                                                <th>MONTHLY SALARY</th>
                                                <th>DATE OF BIRTH</th>
                                                <th>DATE ORIG. APPT.</th>
                                                <th>DATE GOVT SRVC</th>
                                                <th>DATE LAST PROMOTION</th>
                                                <th>DATE LAST INCREMENT</th>
                                                <th>DATE OF LONGEVITY</th>
                                                <th>REMARKS</th>
                                                <th>DATE VACATED</th>
                                                <th>VACATED DUE TO</th>
                                                <th>VACATED BY</th>
                                                <th>ID NO.</th>
                                                <th>CREATED AT</th>
                                                <th>UPDATED AT</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spreadsheet Tab -->
            <div class="tab-pane fade" id="spreadsheet" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Spreadsheet Editor</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary" id="newWorkbookBtn">
                                <i class="fas fa-file-excel"></i> New Workbook
                            </button>
                            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" id="importExcel">Import Excel</a></li>
                                <li><a class="dropdown-item" href="#" id="exportExcel">Export Excel</a></li>
                            </ul>
                        </div>
                        
                        <!-- Export Excel Form -->
                        <form id="exportForm" class="d-none">
                            <input type="hidden" name="month" id="exportMonth">
                            <input type="hidden" name="division" id="exportDivision">
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select class="form-select" id="divisionFilter">
                                    <option value="0">All Divisions</option>
                                    <?php 
                                    $stmt = $conn->prepare("SELECT id, name FROM divisions ORDER BY name");
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    while ($row = $result->fetch_assoc()) {
                                        $selected = ($row['id'] == $selected_division) ? 'selected' : '';
                                        echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
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
                                    <button class="btn btn-outline-primary" id="addColumn">
                                        <i class="bi bi-plus-lg"></i> Add Column
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="spreadsheet" class="hot-table"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jexcel/4.9.0/js/jquery.jexcel.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jexcel/4.9.0/css/jquery.jexcel.min.css" />

    <!-- Add Handsontable CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css">
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <script>
        $(document).ready(function() {
            // Get division from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const divisionId = urlParams.get('division');
            
            // Set initial division filter value for Office tab
            if (divisionId !== null) {
                $('#divisionFilter').val(divisionId);
            }

            // Get month from URL parameter
            const monthParam = urlParams.get('month');
            
            // Set initial month filter value
            if (monthParam !== null) {
                $('#monthFilter').val(monthParam);
            }

            // Initialize DataTable for Office tab
            var recordsTable = $('#recordsTable').DataTable({
                ajax: {
                    url: 'api/get_records.php',
                    type: 'GET',
                    data: function(d) {
                        const divisionId = $('#division').val();
                        const month = $('#month').val();
                        d.division = divisionId;
                        d.month = month;
                    }
                },
                columns: [
                    { data: 'id', type: 'numeric', width: 50, readOnly: true },
                    { data: 'plantilla_no', type: 'text', width: 100 },
                    { 
                        data: 'plantilla_division',
                        type: 'text',
                        width: 150
                    },
                    { data: 'equivalent_division' },
                    { data: 'plantilla_division_definition' },
                    { data: 'fullname' },
                    { data: 'last_name' },
                    { data: 'first_name' },
                    { data: 'middle_name' },
                    { data: 'ext_name' },
                    { data: 'mi' },
                    { data: 'sex' },
                    { data: 'position_title' },
                    { data: 'item_number' },
                    { data: 'tech_code' },
                    { data: 'level' },
                    { data: 'appointment_status' },
                    { data: 'sg' },
                    { data: 'step' },
                    { data: 'monthly_salary' },
                    { 
                        data: 'date_of_birth',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'date_orig_appt',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'date_govt_srvc',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'date_last_promotion',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'date_last_increment',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'date_longevity',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'date_vacated',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString();
                            }
                            return data;
                        }
                    },
                    { data: 'vacated_due_to' },
                    { data: 'vacated_by' },
                    { data: 'id_no' },
                    { 
                        data: 'created_at',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'updated_at',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleString();
                            }
                            return data;
                        }
                    },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group">
                                 
                                    <button class="btn btn-sm btn-danger delete-record" data-id="${row.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[6, 'desc']], // Sort by created_at by default
                pageLength: 10,
                responsive: true,
                language: {
                    search: "",
                    searchPlaceholder: "Search...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });

            // Handle division filter change
            $('#division').on('change', function() {
                const divisionId = $(this).val();
                // Update URL without page reload
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('division', divisionId);
                window.history.pushState({}, '', newUrl);
                recordsTable.ajax.reload();
            });

            // Handle month filter change
            $('#month').on('change', function() {
                const month = $(this).val();
                // Update URL without page reload
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('month', month);
                window.history.pushState({}, '', newUrl);
                recordsTable.ajax.reload();
            });

            // Handle search input
            $('#searchSpreadsheet').on('keyup', function() {
                recordsTable.search(this.value).draw();
            });

            let previousData = null;

            // New Workbook button click handler
            $('#newWorkbookBtn').click(function() {
                // Get current division and month
                const divisionId = $('#divisionFilter').val();
                const month = $('#monthFilter').val();
                
                // Create a new workbook with default data
                const defaultData = [{
                    employee_id: '',
                    name: '',
                    position: '',
                    salary_grade: '',
                    status: 'active',
                    division_id: divisionId
                }];
                
                // Set the data in Handsontable
                hot.loadData(defaultData);
                
                // Switch to Spreadsheet tab
                $('#spreadsheet-tab').tab('show');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'New Workbook Created',
                    text: 'You can now start entering data',
                    showConfirmButton: false,
                    timer: 1500
                });
            });

            // Save button
            $('#saveSpreadsheet').click(function() {
                const data = hot.getData();
                if (!data || data.length === 0) {
                    Swal.fire('Error', 'No data to save', 'error');
                    return;
                }

                // Validate all records
                const invalidRecords = data.filter(record => 
                    !record.employee_id || 
                    !record.name || 
                    !record.position || 
                    !record.salary_grade || 
                    !record.status || 
                    !record.division_id
                );

                if (invalidRecords.length > 0) {
                    Swal.fire('Error', 'Please fill in all required fields for all records', 'error');
                    return;
                }

                // Save each record
                let successCount = 0;
                let errorCount = 0;

                data.forEach((record, index) => {
                    $.ajax({
                        url: 'api/create_record.php',
                        method: 'POST',
                        data: record,
                        success: function(response) {
                            if (response.success) {
                                successCount++;
                            } else {
                                errorCount++;
                                console.error('Failed to save record:', response.error);
                            }

                            // If this is the last record, show summary
                            if (index === data.length - 1) {
                                if (errorCount === 0) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: `Successfully saved ${successCount} records`,
                                        showConfirmButton: false,
                                        timer: 1500
                                    });
                                    // Refresh the data
                                    loadSpreadsheetData();
                                } else {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Partial Success',
                                        text: `Saved ${successCount} records, failed to save ${errorCount} records`
                                    });
                                }
                            }
                        },
                        error: function(xhr) {
                            errorCount++;
                            console.error('Error saving record:', xhr.responseText);
                            
                            // If this is the last record, show summary
                            if (index === data.length - 1) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Partial Success',
                                    text: `Saved ${successCount} records, failed to save ${errorCount} records`
                                });
                            }
                        }
                    });
                });
            });

            // Add new row
            $('#addRow').click(function() {
                const currentData = hot.getData();
                const newRow = {
                    plantilla_no: '',
                    division_id: $('#divisionFilter').val(),
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
                hot.alter('insert_row', currentData.length, 1, newRow);
            });

            // Clear spreadsheet
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

            const hot = new Handsontable(document.getElementById('spreadsheet'), {
                colHeaders: [
                    'ID',
                    'PLANTILLA NO.',
                    'PLANTILLA DIVISION',
                    'EQUIVALENT DIVISION',
                    'PLANTILLA DIVISION DEFINITION',
                    'FULLNAME',
                    'LAST NAME',
                    'FIRST NAME',
                    'MIDDLE NAME',
                    'EXT NAME',
                    'MI',
                    'SEX',
                    'POSITION TITLE',
                    'ITEM NUMBER',
                    'TECH CODE',
                    'LEVEL',
                    'APPOINTMENT STATUS',
                    'SG',
                    'STEP',
                    'MONTHLY SALARY',
                    'DATE OF BIRTH',
                    'DATE ORIG. APPT.',
                    'DATE GOVT SRVC',
                    'DATE LAST PROMOTION',
                    'DATE LAST INCREMENT',
                    'DATE OF LONGEVITY',
                    'REMARKS',
                    'DATE VACATED',
                    'VACATED DUE TO',
                    'VACATED BY',
                    'ID NO.',
                    'CREATED AT',
                    'UPDATED AT'
                ],
                columns: [
                    { data: 'id', type: 'numeric', width: 50, readOnly: true },
                    { data: 'plantilla_no', type: 'text', width: 100 },
                    { 
                        data: 'plantilla_division',
                        type: 'text',
                        width: 150
                    },
                    { data: 'equivalent_division', type: 'text', width: 150 },
                    { data: 'plantilla_division_definition', type: 'text', width: 200 },
                    { data: 'fullname', type: 'text', width: 200 },
                    { data: 'last_name', type: 'text', width: 150 },
                    { data: 'first_name', type: 'text', width: 150 },
                    { data: 'middle_name', type: 'text', width: 150 },
                    { data: 'ext_name', type: 'text', width: 100 },
                    { data: 'mi', type: 'text', width: 50 },
                    { data: 'sex', type: 'text', width: 50 },
                    { data: 'position_title', type: 'text', width: 200 },
                    { data: 'item_number', type: 'text', width: 100 },
                    { data: 'tech_code', type: 'text', width: 100 },
                    { data: 'level', type: 'text', width: 100 },
                    { data: 'appointment_status', type: 'text', width: 150 },
                    { data: 'sg', type: 'text', width: 50 },
                    { data: 'step', type: 'text', width: 50 },
                    { data: 'monthly_salary', type: 'numeric', width: 100 },
                    { data: 'date_of_birth', type: 'date', width: 100 },
                    { data: 'date_orig_appt', type: 'date', width: 100 },
                    { data: 'date_govt_srvc', type: 'date', width: 100 },
                    { data: 'date_last_promotion', type: 'date', width: 100 },
                    { data: 'date_last_increment', type: 'date', width: 100 },
                    { data: 'date_longevity', type: 'date', width: 100 },
                    { data: 'date_vacated', type: 'date', width: 100 },
                    { data: 'vacated_due_to', type: 'text', width: 150 },
                    { data: 'vacated_by', type: 'text', width: 150 },
                    { data: 'id_no', type: 'text', width: 100 },
                    { data: 'created_at', type: 'date', width: 150 },
                    { data: 'updated_at', type: 'date', width: 150 }
                ],
                rowHeaders: true,
                colWidths: [100, 150, 150, 200, 200, 150, 150, 150, 50, 50, 200, 100, 100, 100, 150, 50, 50, 100, 100, 100, 100, 100, 100, 100, 100, 150, 150, 100, 150, 150],
                height: 'calc(100vh - 300px)',
                licenseKey: 'non-commercial-and-evaluation',
                contextMenu: true,
                filters: true,
                dropdownMenu: true,
                manualColumnResize: true,
                manualRowResize: true,
                stretchH: 'all',
                allowInsertRow: true,
                allowRemoveRow: true,
                afterChange: function(changes, source) {
                    if (source === 'edit') {
                        console.log('Change detected:', changes);
                        
                        // Get the row data that was changed
                        const rowData = hot.getSourceDataAtRow(changes[0][0]);
                        console.log('Row data:', rowData);
                        
                        // Only validate if the row exists
                        if (!rowData) {
                            console.error('Invalid row data:', rowData);
                            return;
                        }

                        // Store the old value for reverting if needed
                        const oldValue = changes[0][2];
                        const row = changes[0][0];
                        const prop = changes[0][1];
                        console.log('Change details - Row:', row, 'Property:', prop, 'Old value:', oldValue);

                        // Prevent multiple AJAX calls
                        if (hot.isUpdating) {
                            console.log('Update already in progress, skipping...');
                            return;
                        }
                        hot.isUpdating = true;

                        // Get the record ID from the data
                        const recordId = hot.getDataAtRowProp(row, 'id');
                        if (!recordId) {
                            console.error('No record ID found for row:', row);
                            hot.isUpdating = false;
                            return;
                        }

                        // Create update data with only the changed field
                        const updateData = {
                            id: recordId,
                            field: prop,
                            value: changes[0][3] // The new value
                        };

                        console.log('Sending update data:', updateData);

                        // Update the database immediately
                        $.ajax({
                            url: 'api/update_record.php',
                            method: 'POST',
                            data: updateData,
                            success: function(response) {
                                console.log('Update response:', response);
                                try {
                                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                                    if (result.success) {
                                        // Fetch latest data from database for both spreadsheet and table
                                        $.ajax({
                                            url: 'api/get_records.php',
                                            method: 'GET',
                                            data: { 
                                                division: $('#divisionFilter').val(),
                                                month: $('#monthFilter').val()
                                            },
                                            success: function(response) {
                                                if (response.success) {
                                                    // Update spreadsheet
                                                    const data = response.data.map(record => ({
                                                        id: record.id,
                                                        plantilla_no: record.plantilla_no,
                                                        plantilla_division: record.plantilla_division,
                                                        equivalent_division: record.equivalent_division,
                                                        plantilla_division_definition: record.plantilla_division_definition,
                                                        fullname: record.fullname,
                                                        last_name: record.last_name,
                                                        first_name: record.first_name,
                                                        middle_name: record.middle_name,
                                                        ext_name: record.ext_name,
                                                        mi: record.mi,
                                                        sex: record.sex,
                                                        position_title: record.position_title,
                                                        item_number: record.item_number,
                                                        tech_code: record.tech_code,
                                                        level: record.level,
                                                        appointment_status: record.appointment_status,
                                                        sg: record.sg,
                                                        step: record.step,
                                                        monthly_salary: record.monthly_salary,
                                                        date_of_birth: record.date_of_birth,
                                                        date_orig_appt: record.date_orig_appt,
                                                        date_govt_srvc: record.date_govt_srvc,
                                                        date_last_promotion: record.date_last_promotion,
                                                        date_last_increment: record.date_last_increment,
                                                        date_longevity: record.date_longevity,
                                                        date_vacated: record.date_vacated,
                                                        vacated_due_to: record.vacated_due_to,
                                                        vacated_by: record.vacated_by,
                                                        id_no: record.id_no
                                                    }));
                                                    hot.loadData(data);
                                                    hot.render();
                                                    
                                                    // Update records table
                                                    recordsTable.ajax.reload();
                                                    
                                                    // Show success message
                                                    if (!Swal.isVisible()) {
                                                        Swal.fire({
                                                            icon: 'success',
                                                            title: 'Success',
                                                            text: 'Record updated successfully',
                                                            showConfirmButton: false,
                                                            timer: 2000,
                                                            toast: true,
                                                            position: 'top-end'
                                                        });
                                                    }
                                                }
                                            },
                                            error: function(xhr) {
                                                console.error('Failed to fetch updated data:', xhr.responseText);
                                            }
                                        });
                                    } else {
                                        console.error('Update failed:', result.error);
                                        // Revert the change if update failed
                                        hot.setDataAtRowProp(row, prop, oldValue);
                                        if (!Swal.isVisible()) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: result.error || 'Failed to update record',
                                                showConfirmButton: false,
                                                timer: 2000,
                                                toast: true,
                                                position: 'top-end'
                                            });
                                        }
                                    }
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                }
                            },
                            error: function(xhr) {
                                console.error('Failed to update record:', xhr.responseText);
                                if (!Swal.isVisible()) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to update record',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        toast: true,
                                        position: 'top-end'
                                    });
                                }
                            },
                            complete: function() {
                                console.log('Update process completed');
                                hot.isUpdating = false;
                            }
                        });
                    }
                }
            });

            // Load initial data for Spreadsheet
            function loadSpreadsheetData() {
                const divisionId = $('#divisionFilter').val();
                const month = $('#monthFilter').val();
                
                $.ajax({
                    url: 'api/get_records.php',
                    method: 'GET',
                    data: { 
                        division: divisionId,
                        month: month
                    },
                    success: function(response) {
                        if (response.success) {
                            // Transform response data to match Handsontable structure
                            const data = response.data.map(record => ({
                                id: record.id,
                                plantilla_no: record.plantilla_no,
                                plantilla_division: record.plantilla_division,
                                equivalent_division: record.equivalent_division,
                                plantilla_division_definition: record.plantilla_division_definition,
                                fullname: record.fullname,
                                last_name: record.last_name,
                                first_name: record.first_name,
                                middle_name: record.middle_name,
                                ext_name: record.ext_name,
                                mi: record.mi,
                                sex: record.sex,
                                position_title: record.position_title,
                                item_number: record.item_number,
                                tech_code: record.tech_code,
                                level: record.level,
                                appointment_status: record.appointment_status,
                                sg: record.sg,
                                step: record.step,
                                monthly_salary: record.monthly_salary,
                                date_of_birth: record.date_of_birth,
                                date_orig_appt: record.date_orig_appt,
                                date_govt_srvc: record.date_govt_srvc,
                                date_last_promotion: record.date_last_promotion,
                                date_last_increment: record.date_last_increment,
                                date_longevity: record.date_longevity,
                                date_vacated: record.date_vacated,
                                vacated_due_to: record.vacated_due_to,
                                vacated_by: record.vacated_by,
                                id_no: record.id_no
                            }));
                            
                            // Load data into Handsontable
                            hot.loadData(data);
                            hot.render();
                        } else {
                            Swal.fire('Error', response.error || 'Failed to load data', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load data', 'error');
                    }
                });
            }

            // Initialize spreadsheet with current filters
            loadSpreadsheetData();
            
            // Handle division filter change for Spreadsheet
            $('#divisionFilter').on('change', function() {
                loadSpreadsheetData();
            });

            // Handle month filter change for both Office and Spreadsheet
            $('#monthFilter').on('change', function() {
                const month = $(this).val();
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('month', month);
                window.history.pushState({}, '', newUrl);
                recordsTable.ajax.reload();
                loadSpreadsheetData();
            });

            // Export to Excel
            $('#exportExcel').click(function() {
                const data = hot.getData();
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.json_to_sheet(data);
                XLSX.utils.book_append_sheet(wb, ws, "Records");
                XLSX.writeFile(wb, "records.xlsx");
            });

            // Add new column
            $('#addColumn').click(function() {
                hot.alter('insert_col');
            });

            // Search functionality for Spreadsheet
            $('#searchSpreadsheet').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                hot.search.query(searchText);
                hot.render();
            });

            // File upload handling
            $('#uploadBtn').on('click', function() {
                const form = $('#uploadForm')[0];
                const formData = new FormData(form);
                const uploadBtn = $(this);
                
                uploadBtn.prop('disabled', true);
                uploadBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Uploading...');
                
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000
                            }).then(() => {
                                // Reload both table and spreadsheet data
                                recordsTable.ajax.reload();
                                loadSpreadsheetData();
                                // Reset the form
                                form.reset();
                                // Close the modal
                                $('#uploadModal').modal('hide');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Upload Failed',
                                text: response.error || 'An error occurred during upload',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: 'An error occurred during upload. Please try again.',
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function() {
                        uploadBtn.prop('disabled', false);
                        uploadBtn.html('<i class="bi bi-upload me-2"></i>Upload');
                    }
                });
            });

            // Edit record
            $(document).on('click', '.edit-record', function() {
                const id = $(this).data('id');
                // Implement edit functionality
            });

            // Delete record
            $(document).on('click', '.delete-record', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'api/delete_record.php',
                            type: 'POST',
                            data: { id: id },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Deleted!', 'Record has been deleted.', 'success');
                                    recordsTable.ajax.reload();
                                } else {
                                    Swal.fire('Error', response.error || 'Delete failed', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'An error occurred during deletion', 'error');
                            }
                        });
                    }
                });
            });

            // Export button click handler
            $('#exportBtn').on('click', function() {
                const selectedDivision = $('#divisionFilter').val();
                const month = $('#monthFilter').val();
                
                // Show loading state
                Swal.fire({
                    title: 'Exporting...',
                    text: 'Please wait while we prepare your export',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Create a form for the export
                const form = $('<form>', {
                    method: 'POST',
                    action: 'api/export_records.php',
                    target: '_blank'
                });
                
                // Add the division and month parameters
                $('<input>').attr({
                    type: 'hidden',
                    name: 'division',
                    value: selectedDivision
                }).appendTo(form);
                
                $('<input>').attr({
                    type: 'hidden',
                    name: 'month',
                    value: month
                }).appendTo(form);
                
                // Add the form to the document and submit it
                form.appendTo('body').submit();
                
                // Close the loading dialog after a short delay
                setTimeout(() => {
                    Swal.close();
                }, 2000);
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