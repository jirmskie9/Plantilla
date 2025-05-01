<?php
session_start();
include '../dbconnection.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user data
$userId = $_SESSION['user_id'] ?? 1; // For testing, remove in production
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get applicant counts by status
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM applicants 
    WHERE created_by = ? 
    GROUP BY status
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$statusCounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent files
$stmt = $conn->prepare("
    SELECT * FROM file_uploads 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentFiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent activities
$stmt = $conn->prepare("
    SELECT * FROM activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentActivities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user permissions
$stmt = $conn->prepare("SELECT * FROM user_permissions WHERE user_id = ? AND module = 'organizational_codes'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$permissions = $stmt->get_result()->fetch_assoc();
$stmt->close();

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

// Execute query with prepared statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$organizationalCodes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique departments for filter
$deptQuery = "SELECT DISTINCT department FROM organizational_codes ORDER BY department";
$stmt = $conn->prepare($deptQuery);
$stmt->execute();
$deptResult = $stmt->get_result();
$departments = [];
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row['department'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizational Codes - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
                <h4>User</h4>
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
                    <a class="nav-link" href="my_account.php">
                        <i class="bi bi-person-circle"></i>
                        <span>My Account</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="user-info">
                <img src="<?php echo !empty($user['photo']) ? htmlspecialchars($user['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']); ?>" alt="User">
                <div class="user-details">
                    <h6><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                    <p><?php echo ucfirst($user['role']); ?></p>
                </div>
            </div>
            <div class="logout-btn">
                <a class="nav-link" href="../logout.php" onclick="return confirm('Are you sure you want to logout?')">
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
                    <h2 class="mb-1">Organizational Codes</h2>
                    <p class="text-muted mb-0">Manage and view organizational structure</p>
                </div>
                <?php if ($permissions && $permissions['can_create']): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCodeModal">
                    <i class="bi bi-plus-circle me-2"></i>Add New Code
                </button>
                <?php endif; ?>
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
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Organizational Codes Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="orgCodeTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <?php if ($permissions && ($permissions['can_edit'] || $permissions['can_delete'])): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($organizationalCodes as $code): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($code['code']); ?></td>
                                    <td><?php echo htmlspecialchars($code['description']); ?></td>
                                    <td><?php echo htmlspecialchars($code['department']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $code['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($code['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($code['updated_at'])); ?></td>
                                    <?php if ($permissions && ($permissions['can_edit'] || $permissions['can_delete'])): ?>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($permissions['can_edit']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCodeModal"
                                                    data-id="<?php echo $code['id']; ?>"
                                                    data-code="<?php echo htmlspecialchars($code['code']); ?>"
                                                    data-description="<?php echo htmlspecialchars($code['description']); ?>"
                                                    data-department="<?php echo htmlspecialchars($code['department']); ?>"
                                                    data-status="<?php echo $code['status']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($permissions['can_delete']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCode(<?php echo $code['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Code Modal -->
    <?php if ($permissions && $permissions['can_create']): ?>
    <div class="modal fade" id="addCodeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Organizational Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCodeForm" method="POST" action="process/add_org_code.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Code</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Code Modal -->
    <?php if ($permissions && $permissions['can_edit']): ?>
    <div class="modal fade" id="editCodeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Organizational Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCodeForm" method="POST" action="process/edit_org_code.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control" name="code" id="edit_code" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" id="edit_department" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#orgCodeTable').DataTable({
                order: [[4, 'desc']],
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

            // Handle edit modal
            $('#editCodeModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const id = button.data('id');
                const code = button.data('code');
                const description = button.data('description');
                const department = button.data('department');
                const status = button.data('status');

                const modal = $(this);
                modal.find('#edit_id').val(id);
                modal.find('#edit_code').val(code);
                modal.find('#edit_description').val(description);
                modal.find('#edit_department').val(department);
                modal.find('#edit_status').val(status);
            });
        });

        // Delete function
        function deleteCode(id) {
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
                    window.location.href = `process/delete_org_code.php?id=${id}`;
                }
            });
        }
    </script>
</body>
</html> 