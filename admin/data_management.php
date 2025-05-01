<?php
session_start();
require '../dbconnection.php';

// Check admin permissions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
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

// Get available months from uploads directory
function getAvailableMonths() {
    $dir = '../uploads';
    if (!file_exists($dir)) {
        return [];
    }
    $months = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($dir . '/' . $item)) {
            $months[] = $item;
        }
    }
    rsort($months); // Sort in descending order (newest first)
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
                <a class="nav-link" href="../logout.php">
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
                                <div>
                                    <select id="monthFilter" class="form-select me-2" style="width: 150px; display: inline-block;">
                                        <option value="">All Months</option>
                                        <?php 
                                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                                  'July', 'August', 'September', 'October', 'November', 'December'];
                                        foreach ($months as $index => $month): ?>
                                        <option value="<?= $index + 1 ?>" <?= date('n') == $index + 1 ? 'selected' : '' ?>>
                                            <?= $month ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-primary" id="exportBtn">
                                        <i class="fas fa-download me-2"></i>Export
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="recordsTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Position</th>
                                                <th>Salary Grade</th>
                                                <th>Status</th>
                                                <th>Division</th>
                                                <th>Created At</th>
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
                            <button class="btn btn-sm btn-outline-primary" id="saveSpreadsheet">
                                <i class="bi bi-save me-1"></i>Save
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="exportExcel">
                                <i class="bi bi-file-earmark-excel me-1"></i>Export
                            </button>
                            <button class="btn btn-sm btn-outline-danger" id="clearSpreadsheet">
                                <i class="bi bi-trash me-1"></i>Clear
                            </button>
                        </div>
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

        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Data File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select File</label>
                                <input type="file" class="form-control" id="file" name="file" accept=".csv, .xls, .xlsx" required>
                                <small class="text-muted">Supported formats: CSV, Excel (XLS, XLSX)</small>
                                <div id="fileHelp" class="form-text">
                                    File should contain columns: Employee ID, Name, Position, Salary Grade, Status, Division ID
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Division</label>
                                <select class="form-select" name="division" id="division" required>
                                    <?php 
                                    $divisions = $conn->query("SELECT * FROM divisions WHERE id != 0 ORDER BY name");
                                    while ($division = $divisions->fetch_assoc()): ?>
                                        <option value="<?= $division['id'] ?>"><?= $division['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="upload-progress d-none">
                                <div class="progress mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="text-center text-muted small upload-status"></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="uploadForm" class="btn btn-primary" id="uploadBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Upload
                        </button>
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
                        d.division = $('#divisionFilter').val();
                        d.month = $('#monthFilter').val();
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { data: 'position' },
                    { data: 'salary_grade' },
                    { 
                        data: 'status',
                        render: function(data, type, row) {
                            return `<span class="badge bg-${data === 'active' ? 'success' : 'danger'}">${data}</span>`;
                        }
                    },
                    { data: 'division_name' },
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
            $('#divisionFilter').on('change', function() {
                const divisionId = $(this).val();
                // Update URL without page reload
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('division', divisionId);
                window.history.pushState({}, '', newUrl);
                recordsTable.ajax.reload();
            });

            // Handle month filter change
            $('#monthFilter').on('change', function() {
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
                    employee_id: '',
                    name: '',
                    position: '',
                    salary_grade: '',
                    status: 'active',
                    division_id: $('#divisionFilter').val()
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

            // Initialize Handsontable for Spreadsheet tab
            const container = document.getElementById('spreadsheet');
            const hot = new Handsontable(container, {
                data: [], // Empty data to start
                colHeaders: ['Employee ID', 'Name', 'Position', 'Salary Grade', 'Status', 'Division'],
                columns: [
                    { 
                        data: 'employee_id', 
                        type: 'text',
                        readOnly: true
                    },
                    { data: 'name', type: 'text', readOnly: false },
                    { data: 'position', type: 'text', readOnly: false },
                    { data: 'salary_grade', type: 'text', readOnly: false },
                    { 
                        data: 'status',
                        type: 'dropdown',
                        source: ['active', 'inactive'],
                        readOnly: false
                    },
                    { 
                        data: 'division_id',
                        type: 'dropdown',
                        source: <?php
                            $divisions = [];
                            $stmt = $conn->prepare("SELECT id, name FROM divisions WHERE status = 'active' ORDER BY name");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                $divisions[] = ['id' => $row['id'], 'name' => $row['name']];
                            }
                            echo json_encode($divisions);
                        ?>,
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
                            const division = cellProperties.source.find(d => d.id === value);
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            if (division) {
                                td.textContent = division.name;
                            }
                        },
                        readOnly: false
                    }
                ],
                rowHeaders: true,
                colWidths: [120, 200, 200, 120, 100, 150],
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
                        
                        // Validate the data before sending
                        if (!rowData || !rowData.employee_id) {
                            console.error('Invalid row data:', rowData);
                            return;
                        }

                        const record = {
                            employee_id: rowData.employee_id,
                            name: rowData.name || '',
                            position: rowData.position || '',
                            salary_grade: rowData.salary_grade || '',
                            status: rowData.status || 'active',
                            division_id: rowData.division_id || 0
                        };
                        console.log('Prepared record for update:', record);

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

                        // Update the database immediately
                        $.ajax({
                            url: 'api/update_record.php',
                            method: 'POST',
                            data: record,
                            success: function(response) {
                                console.log('Update response:', response);
                                try {
                                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                                    if (result.success) {
                                        // Fetch latest data from database
                                        $.ajax({
                                            url: 'api/get_records.php',
                                            method: 'GET',
                                            data: { 
                                                division: $('#divisionFilter').val(),
                                                month: $('#monthFilter').val()
                                            },
                                            success: function(dataResponse) {
                                                if (dataResponse.success) {
                                                    // Transform response data to match Handsontable structure
                                                    const data = dataResponse.data.map(record => ({
                                                        employee_id: record.employee_id,
                                                        name: record.name,
                                                        position: record.position,
                                                        salary_grade: record.salary_grade,
                                                        status: record.status,
                                                        division_id: record.division_id
                                                    }));
                                                    
                                                    // Load data into Handsontable
                                                    hot.loadData(data);
                                                    hot.render();
                                                    
                                                    if (!Swal.isVisible()) {
                                                        Swal.fire({
                                                            icon: 'success',
                                                            title: 'Success',
                                                            text: 'Record updated successfully',
                                                            showConfirmButton: false,
                                                            timer: 1000,
                                                            toast: true,
                                                            position: 'top-end'
                                                        });
                                                    }
                                                } else {
                                                    console.error('Failed to fetch updated data:', dataResponse.error);
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
                                employee_id: record.employee_id,
                                name: record.name,
                                position: record.position,
                                salary_grade: record.salary_grade,
                                status: record.status,
                                division_id: record.division_id
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
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const $progress = $('.upload-progress');
                const $progressBar = $progress.find('.progress-bar');
                const $uploadStatus = $progress.find('.upload-status');
                const $uploadBtn = $('#uploadBtn');
                
                $progress.removeClass('d-none');
                $uploadBtn.prop('disabled', true);
                $uploadBtn.find('.spinner-border').removeClass('d-none');
                
                $.ajax({
                    url: 'api/upload_workbook.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        const xhr = new XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                $progressBar.css('width', percent + '%');
                                $uploadStatus.text(`Uploading: ${percent}%`);
                            }
                        });
                        return xhr;
                    },
                    success: function(response) {
                        console.log('Upload response:', response);
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            console.log('Parsed response:', result);
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Upload Complete',
                                    text: result.message + ' (' + result.records_processed + ' records processed)'
                                });
                                
                                // Refresh the data table
                                recordsTable.ajax.reload();
                                
                                // Close the modal
                                $('#uploadModal').modal('hide');
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Upload Failed',
                                    text: result.error || 'Unknown error occurred'
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Upload Failed',
                                text: 'Error parsing server response: ' + e.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Upload error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        
                        let errorMessage = 'An error occurred during upload';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.error || errorMessage;
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                            errorMessage = 'Server error: ' + xhr.status + ' ' + xhr.statusText;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: errorMessage
                        });
                    },
                    complete: function() {
                        $uploadBtn.prop('disabled', false);
                        $uploadBtn.find('.spinner-border').addClass('d-none');
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
        });
    </script>
</body>
</html>