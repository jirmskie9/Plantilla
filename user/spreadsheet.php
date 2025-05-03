<?php
session_start();
include '../dbconnection.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with prepared statements
$query = "SELECT * FROM organizational_codes WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (code LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($department)) {
    $query .= " AND department = ?";
    $params[] = $department;
    $types .= 's';
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= 's';
}

// Get total count for pagination
$countQuery = str_replace("SELECT *", "SELECT COUNT(*)", $query);
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$totalRecords = $result->fetch_row()[0];
$stmt->close();

// Pagination
$recordsPerPage = 10;
$totalPages = ceil($totalRecords / $recordsPerPage);
$page = isset($_GET['page']) ? max(1, min($_GET['page'], $totalPages)) : 1;
$offset = ($page - 1) * $recordsPerPage;

$query .= " LIMIT ? OFFSET ?";
$params[] = $recordsPerPage;
$params[] = $offset;
$types .= 'ii';

// Execute query with prepared statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$organizationalCodes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique departments for filter using prepared statement
$deptQuery = "SELECT DISTINCT department FROM organizational_codes ORDER BY department";
$stmt = $conn->prepare($deptQuery);
$stmt->execute();
$deptResult = $stmt->get_result();
$departments = [];
while ($row = $deptResult->fetch_row()) {
    $departments[] = $row[0];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spreadsheet View - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .dataTables_wrapper .dataTables_filter {
            float: none;
            text-align: left;
        }
        .dataTables_wrapper .dataTables_length {
            float: none;
            text-align: left;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
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
                    <a class="nav-link" data-bs-toggle="collapse" href="#dataManagement">
                        <i class="bi bi-database"></i>
                        <span>Data Management</span>
                    </a>
                    <div class="collapse" id="dataManagement">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="org_code.php">
                                    <i class="bi bi-diagram-3"></i>
                                    <span>Organizational Code</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="spreadsheet.php">
                                    <i class="bi bi-table"></i>
                                    <span>Spreadsheet View</span>
                                </a>
                            </li>
                        </ul>
                    </div>
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
                    <h2 class="mb-1">Spreadsheet View</h2>
                    <p class="text-muted mb-0">View and export organizational codes</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary me-2" id="exportExcel">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                    </button>
                    <button class="btn btn-outline-secondary" id="exportPDF">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" name="search" placeholder="Search by code or description..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-funnel me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="spreadsheetTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($organizationalCodes as $code): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($code['code']); ?></td>
                                    <td><?php echo htmlspecialchars($code['description']); ?></td>
                                    <td><?php echo htmlspecialchars($code['department']); ?></td>
                                    <td><?php echo htmlspecialchars($code['position']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $code['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($code['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($code['created_at'])); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($code['updated_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = $('#spreadsheetTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                language: {
                    search: "",
                    searchPlaceholder: "Search...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });

            // Export to Excel
            document.getElementById('exportExcel').addEventListener('click', function() {
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.table_to_sheet(document.getElementById('spreadsheetTable'));
                XLSX.utils.book_append_sheet(wb, ws, "Organizational Codes");
                XLSX.writeFile(wb, "organizational_codes.xlsx");
            });

            // Export to PDF
            document.getElementById('exportPDF').addEventListener('click', function() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                doc.autoTable({
                    html: '#spreadsheetTable',
                    theme: 'grid',
                    headStyles: { fillColor: [41, 98, 255] },
                    margin: { top: 20 },
                    didDrawPage: function(data) {
                        doc.setFontSize(20);
                        doc.text('Organizational Codes', 14, 15);
                    }
                });

                doc.save('organizational_codes.pdf');
            });
        });
    </script>
</body>
</html> 