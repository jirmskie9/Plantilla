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
    <title>Admin Dashboard - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
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
                                <a class="nav-link active" href="org_code.php">
                                    <i class="bi bi-diagram-3"></i>
                                    <span>Organizational Code</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="spreadsheet.php">
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
                    <h2 class="mb-1">Organizational Code Management</h2>
                    <p class="text-muted mb-0">Manage and view organizational structure</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrgCodeModal">
                    <i class="bi bi-plus-lg me-2"></i>Add New Code
                </button>
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

        <!-- Organizational Code Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Actions</th>
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
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1 edit-code" 
                                                data-id="<?php echo $code['id']; ?>"
                                                data-code="<?php echo htmlspecialchars($code['code']); ?>"
                                                data-description="<?php echo htmlspecialchars($code['description']); ?>"
                                                data-department="<?php echo htmlspecialchars($code['department']); ?>"
                                                data-position="<?php echo htmlspecialchars($code['position']); ?>"
                                                data-status="<?php echo htmlspecialchars($code['status']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-code" data-id="<?php echo $code['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department); ?>&status=<?php echo urlencode($status); ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department); ?>&status=<?php echo urlencode($status); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add Organization Code Modal -->
    <div class="modal fade" id="addOrgCodeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Organizational Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addOrgCodeForm">
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control" name="code" placeholder="e.g., HR-001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" placeholder="Enter description" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" placeholder="Enter position" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveOrgCode">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Organization Code Modal -->
    <div class="modal fade" id="editOrgCodeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Organizational Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editOrgCodeForm">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control" name="code" id="editCode" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" id="editDescription" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department" id="editDepartment" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" id="editPosition" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateOrgCode">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit button click handler
        document.querySelectorAll('.edit-code').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const code = this.dataset.code;
                const description = this.dataset.description;
                const department = this.dataset.department;
                const position = this.dataset.position;
                const status = this.dataset.status;

                document.getElementById('editId').value = id;
                document.getElementById('editCode').value = code;
                document.getElementById('editDescription').value = description;
                document.getElementById('editDepartment').value = department;
                document.getElementById('editPosition').value = position;
                document.getElementById('editStatus').value = status;
            });
        });

        // Delete button click handler
        document.querySelectorAll('.delete-code').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this organizational code?')) {
                    const id = this.dataset.id;
                    fetch('delete_org_code.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting organizational code');
                        }
                    });
                }
            });
        });

        // Save new organizational code
        document.getElementById('saveOrgCode').addEventListener('click', function() {
            const form = document.getElementById('addOrgCodeForm');
            const formData = new FormData(form);

            fetch('add_org_code.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error adding organizational code');
                }
            });
        });

        // Update organizational code
        document.getElementById('updateOrgCode').addEventListener('click', function() {
            const form = document.getElementById('editOrgCodeForm');
            const formData = new FormData(form);

            fetch('update_org_code.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating organizational code');
                }
            });
        });
    });
    </script>
</body>
</html> 