<?php
session_start();
include '../dbconnection.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
        // Get form data
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $role = $_POST['role'];
        
        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($role)) {
                    $response['message'] = 'All fields are required';
                    break;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response['message'] = 'Invalid email format';
                    break;
        }
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
                    $response['message'] = 'Database error: ' . $conn->error;
                    break;
        }
        
        $stmt->bind_param("ss", $username, $email);
        if (!$stmt->execute()) {
                    $response['message'] = 'Database error: ' . $stmt->error;
                    break;
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
                    $response['message'] = 'Username or email already exists';
                    break;
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
            
                    $response['success'] = true;
                    $response['message'] = 'User added successfully';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }
                break;
                
            case 'edit_user':
                $userId = (int)$_POST['id'];
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $role = $_POST['role'];
                $status = $_POST['status'];
                
                // Validate input
                if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($role) || empty($status)) {
                    $response['message'] = 'All fields are required';
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response['message'] = 'Invalid email format';
                    break;
                }
                
                // Check if username or email already exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->bind_param("ssi", $username, $email, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $response['message'] = 'Username or email already exists';
                    break;
                }
                
                // Update user
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $username, $email, $first_name, $last_name, $role, $status, $userId);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'User updated successfully';
                    
                    // Log activity
                    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'update', ?, ?)");
                    $description = "Updated user: $username";
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip_address);
                    $stmt->execute();
                } else {
                    $response['message'] = 'Failed to update user';
                }
                break;
                
            case 'delete_user':
                $userId = (int)$_POST['id'];
                
                // Prevent deleting own account
                if ($userId === $_SESSION['user_id']) {
                    $response['message'] = 'You cannot delete your own account';
                    break;
                }
                
                // Get user info for logging
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'User deleted successfully';
                    
                    // Log activity
                    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'delete', ?, ?)");
                    $description = "Deleted user: " . $user['username'];
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip_address);
                    $stmt->execute();
                } else {
                    $response['message'] = 'Failed to delete user';
                }
                break;
                
            case 'update_permissions':
                $userId = (int)$_POST['user_id'];
                $permissions = $_POST['permissions'];
                
                // Delete existing permissions
                $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                
                // Insert new permissions
                $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, module, can_view, can_create, can_edit, can_delete) VALUES (?, ?, ?, ?, ?, ?)");
                
                foreach ($permissions as $module => $perms) {
                    $can_view = isset($perms['view']) ? 1 : 0;
                    $can_create = isset($perms['create']) ? 1 : 0;
                    $can_edit = isset($perms['edit']) ? 1 : 0;
                    $can_delete = isset($perms['delete']) ? 1 : 0;
                    
                    $stmt->bind_param("isiiii", $userId, $module, $can_view, $can_create, $can_edit, $can_delete);
                    $stmt->execute();
                }
                
                $response['success'] = true;
                $response['message'] = 'Permissions updated successfully';
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'update', ?, ?)");
                $description = "Updated permissions for user ID: $userId";
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip_address);
                $stmt->execute();
                break;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with prepared statements
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM activity_logs WHERE user_id = u.id) as activity_count,
          (SELECT MAX(created_at) FROM activity_logs WHERE user_id = u.id) as last_activity
          FROM users u WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ssss';
}

if (!empty($role)) {
    $query .= " AND u.role = ?";
    $params[] = $role;
    $types .= 's';
}

if (!empty($status)) {
    $query .= " AND u.status = ?";
    $params[] = $status;
    $types .= 's';
}

$query .= " ORDER BY u.created_at DESC";

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
                <a class="nav-link" onclick="return confirm('Are you sure you want to logout?')" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="mb-0">User Management</h2>
                    <p class="text-muted">Manage system users and their permissions</p>
                </div>
                <div class="col-md-6 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-lg me-2"></i>Add New User
                </button>
            </div>
        </div>

            <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">All Roles</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
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
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

            <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                    <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                    <th>Activity Count</th>
                                    <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo !empty($user['photo']) ? '../' . htmlspecialchars($user['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']); ?>" 
                                             alt="User Photo" 
                                             class="rounded-circle"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td><?php echo $user['activity_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="viewPermissions(<?php echo $user['id']; ?>)">
                                                <i class="bi bi-shield-lock"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
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
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addUserForm">
                <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
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
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="user">User</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                </div>
                </form>
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
            const table = $('#usersTable').DataTable({
                order: [[8, 'desc']],
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

            // Handle form submission
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                
                // Show loading state
                submitBtn.prop('disabled', true);
                submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Adding...');
                
                // Submit form
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while adding the user. Please try again.'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        submitBtn.html('Add User');
                    }
                });
            });

            // Edit User
            function editUser(userId) {
                // Fetch user data
                $.ajax({
                    url: 'get_user.php',
                    type: 'GET',
                    data: { id: userId },
                    success: function(response) {
                        if (response.success) {
                            const user = response.user;
                            // Create modal HTML
                            const modalHtml = `
                                <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form id="editUserForm">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit_user">
                                                    <input type="hidden" name="id" value="${user.id}">
                                                    <div class="mb-3">
                                                        <label class="form-label">Username</label>
                                                        <input type="text" class="form-control" name="username" value="${user.username}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" class="form-control" name="email" value="${user.email}" required>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">First Name</label>
                                                            <input type="text" class="form-control" name="first_name" value="${user.first_name}" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Last Name</label>
                                                            <input type="text" class="form-control" name="last_name" value="${user.last_name}" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Role</label>
                                                        <select class="form-select" name="role" required>
                                                            <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                                                            <option value="manager" ${user.role === 'manager' ? 'selected' : ''}>Manager</option>
                                                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="active" ${user.status === 'active' ? 'selected' : ''}>Active</option>
                                                            <option value="inactive" ${user.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Remove existing modal if any
                            $('#editUserModal').remove();
                            
                            // Add new modal to body
                            $('body').append(modalHtml);
                            
                            // Initialize modal
                            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                            editModal.show();

                            // Handle form submission
                            $('#editUserForm').on('submit', function(e) {
                                e.preventDefault();
                                const form = $(this);
                                const submitBtn = form.find('button[type="submit"]');
                                
                                // Show loading state
                                submitBtn.prop('disabled', true);
                                submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
                                
                                // Submit form
                                $.ajax({
                                    url: window.location.href,
                                    type: 'POST',
                                    data: new FormData(this),
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        if (response.success) {
                                            editModal.hide();
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Success',
                                                text: 'User updated successfully',
                                                showConfirmButton: false,
                                                timer: 1500
                                            }).then(() => {
                                                window.location.reload();
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: response.message || 'Failed to update user'
                                            });
                                        }
                                    },
                                    error: function() {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Failed to update user'
                                        });
                                    },
                                    complete: function() {
                                        submitBtn.prop('disabled', false);
                                        submitBtn.html('Save Changes');
                                    }
                                });
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to fetch user data'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch user data'
                        });
                    }
                });
            }

            // View Permissions
            function viewPermissions(userId) {
                // Fetch user permissions
                $.ajax({
                    url: 'get_permissions.php',
                    type: 'GET',
                    data: { user_id: userId },
                    success: function(response) {
                        if (response.success) {
                            const permissions = response.permissions;
                            const modules = ['dashboard', 'organizational_codes', 'applicants', 'records', 'users'];
                            
                            let html = `
                                <form id="permissionsForm">
                                    <input type="hidden" name="action" value="update_permissions">
                                    <input type="hidden" name="user_id" value="${userId}">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Module</th>
                                                    <th>View</th>
                                                    <th>Create</th>
                                                    <th>Edit</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                            `;
                            
                            modules.forEach(module => {
                                const modulePerms = permissions[module] || { can_view: 0, can_create: 0, can_edit: 0, can_delete: 0 };
                                html += `
                                    <tr>
                                        <td>${module.replace('_', ' ').toUpperCase()}</td>
                                        <td><input type="checkbox" name="permissions[${module}][view]" ${modulePerms.can_view ? 'checked' : ''}></td>
                                        <td><input type="checkbox" name="permissions[${module}][create]" ${modulePerms.can_create ? 'checked' : ''}></td>
                                        <td><input type="checkbox" name="permissions[${module}][edit]" ${modulePerms.can_edit ? 'checked' : ''}></td>
                                        <td><input type="checkbox" name="permissions[${module}][delete]" ${modulePerms.can_delete ? 'checked' : ''}></td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            `;
                            
                            Swal.fire({
                                title: 'User Permissions',
                                html: html,
                                showCancelButton: true,
                                confirmButtonText: 'Save Changes',
                                cancelButtonText: 'Cancel',
                                preConfirm: () => {
                                    const form = document.getElementById('permissionsForm');
                const formData = new FormData(form);

                                    return $.ajax({
                                        url: window.location.href,
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false
                                    }).then(response => {
                                        if (!response.success) {
                                            throw new Error(response.message);
                                        }
                                        return response;
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'Permissions updated successfully',
                                        showConfirmButton: false,
                                        timer: 1500
                                    });
                                }
                            }).catch(error => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: error.message || 'Failed to update permissions'
                                });
                            });
                    } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to fetch permissions'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch permissions'
                        });
                    }
                });
            }

            // Delete User
            function deleteUser(userId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const formData = new FormData();
                        formData.append('action', 'delete_user');
                        formData.append('id', userId);
                        
                        return $.ajax({
                            url: window.location.href,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'User deleted successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                }).catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to delete user'
                    });
                });
            }

            function confirmLogout() {
                // Check if running on localhost
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You will be logged out of the system.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, logout',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '../logout.php';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Logout is only available on localhost'
                    });
                }
            }
        });
    </script>
</body>
</html>