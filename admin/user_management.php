<?php
session_start();
include '../dbconnection.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
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
    <title>User Management - Plantilla Management System</title>
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
                    <a class="nav-link" href="data_management.php">
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
                    <a class="nav-link active" href="user_management.php">
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
                    <h2 class="mb-1">User Management</h2>
                    <p class="text-muted mb-0">Manage system users and their permissions</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus me-2"></i>Add New User
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
                            <input type="text" class="form-control" name="search" placeholder="Search by name, username or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">All Roles</option>
                          
                            <option value="manager" <?php echo $role === 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
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
                    <table id="userTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Division</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo !empty($user['photo']) ? htmlspecialchars($user['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']); ?>" 
                                             class="user-photo" 
                                             alt="User Photo">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge"><?php echo ucfirst($user['role']); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $division_name = 'N/A';
                                        if ($user['division_id']) {
                                            $stmt = $conn->prepare("SELECT name FROM divisions WHERE id = ?");
                                            $stmt->bind_param("i", $user['division_id']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            if ($result->num_rows > 0) {
                                                $division = $result->fetch_assoc();
                                                $division_name = $division['name'];
                                            }
                                        }
                                        ?>
                                        <span class="badge bg-info"><?php echo $division_name; ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap gap-1">
                                            <button class="btn btn-sm btn-outline-primary p-1 view-user" data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewUserModal" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning p-1 edit-user" data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal" data-bs-target="#editUserModal" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger p-1 delete-user" data-id="<?php echo $user['id']; ?>" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" method="POST" action="">
                        <input type="hidden" name="action" value="add_user">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="user">User</option>
                                <option value="manager">Manager</option>
                              
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addUserForm" class="btn btn-primary">Add User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="viewPhoto" src="" class="img-fluid rounded-circle mb-2" style="max-width: 150px;">
                        <h5 id="viewName" class="mb-1"></h5>
                        <p id="viewRole" class="text-muted"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <p id="viewUsername" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <p id="viewEmail" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <p id="viewStatus" class="form-control-static"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Login</label>
                        <p id="viewLastLogin" class="form-control-static"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="id" id="editId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" id="editLastName" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="editUsername" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="editRole" required>
                                <option value="user">User</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
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
                    <button type="button" class="btn btn-primary" id="updateUser">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        // Initialize SweetAlert
        window.Swal = window.Swal || window.Swal2;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = $('#userTable').DataTable({
                order: [[6, 'desc']],
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

            // Show SweetAlert if there's a message in session
            <?php if (isset($_SESSION['alert'])): ?>
                const alert = <?php echo json_encode($_SESSION['alert']); ?>;
                if (alert.message) {
                    Swal.fire({
                        icon: alert.success ? 'success' : 'error',
                        title: alert.success ? 'Success!' : 'Error!',
                        text: alert.message,
                        showConfirmButton: false,
                        timer: alert.success ? 1500 : 3000
                    }).then(() => {
                        if (alert.success) {
                            location.reload();
                        }
                    });
                }
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            // View User Details
            document.querySelectorAll('.view-user').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    fetch(`get_user.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const user = data.user;
                                document.getElementById('viewPhoto').src = user.photo || 
                                    `https://ui-avatars.com/api/?name=${encodeURIComponent(user.first_name + '+' + user.last_name)}`;
                                document.getElementById('viewName').textContent = `${user.first_name} ${user.last_name}`;
                                document.getElementById('viewRole').textContent = user.role;
                                document.getElementById('viewUsername').textContent = user.username;
                                document.getElementById('viewEmail').textContent = user.email;
                                document.getElementById('viewStatus').innerHTML = 
                                    `<span class="status-badge bg-${user.status === 'active' ? 'success' : 'danger'}">${user.status}</span>`;
                                document.getElementById('viewLastLogin').textContent = 
                                    user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';
                            }
                        });
                });
            });

            // Edit User
            document.querySelectorAll('.edit-user').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    fetch(`get_user.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const user = data.user;
                                document.getElementById('editId').value = user.id;
                                document.getElementById('editFirstName').value = user.first_name;
                                document.getElementById('editLastName').value = user.last_name;
                                document.getElementById('editUsername').value = user.username;
                                document.getElementById('editEmail').value = user.email;
                                document.getElementById('editRole').value = user.role;
                                document.getElementById('editStatus').value = user.status;
                            }
                        });
                });
            });

            // Update User
            document.getElementById('updateUser').addEventListener('click', function() {
                const form = document.getElementById('editUserForm');
                const formData = new FormData(form);

                fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating user');
                    }
                });
            });

            // Delete User
            document.querySelectorAll('.delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'This action will permanently delete this user.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('delete_user.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'User has been deleted successfully.',
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: 'Failed to delete user.',
                                        showConfirmButton: true
                                    });
                                }
                            });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>