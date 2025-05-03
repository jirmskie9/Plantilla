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
                    <button class="btn btn-primary add-user-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
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
    <div id="addUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; width: 90%; max-width: 500px; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                <h3 style="margin: 0; font-size: 1.5rem; color: #333;">Add New User</h3>
             
            </div>
            <form id="addUserForm">
                <input type="hidden" name="action" value="add_user">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">Username</label>
                    <input type="text" name="username" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;" required>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">Email</label>
                    <input type="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;" required>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">Password</label>
                    <input type="password" name="password" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;" required>
                </div>
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">First Name</label>
                        <input type="text" name="first_name" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">Last Name</label>
                        <input type="text" name="last_name" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;" required>
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #333;">Role</label>
                    <select name="role" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;" required>
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                    <a href="user_management.php" class="close-modal" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</a>
                    <button type="submit" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close">&times;</span>
            </div>
            <form id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" name="username" id="editUsername" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="editEmail" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control" name="last_name" id="editLastName" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="role" id="editRole" required>
                            <option value="user">User</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" id="editStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal" id="deleteUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete User</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <input type="hidden" id="deleteUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 50px auto;
            padding: 20px;
            border: none;
            width: 90%;
            max-width: 600px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1001;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }

        .close:hover {
            color: #000;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Table styling for permissions */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
        }

        .table th {
            background-color: #f8f9fa;
            padding: 8px;
            text-align: left;
        }

        .table td {
            padding: 8px;
            vertical-align: middle;
        }

        /* Checkbox styling */
        .permission-checkbox {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 20px auto;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#usersTable').DataTable({
                responsive: true,
                language: {
                    search: "Search users:"
                }
            });

            // Show Add User Modal
            $('.add-user-btn').click(function() {
                $('#addUserModal').fadeIn(200);
                $('body').css('overflow', 'hidden');
            });

            // Show Edit User Modal
            window.editUser = function(userId) {
                $.ajax({
                    url: 'get_user.php',
                    type: 'GET',
                    data: { id: userId },
                    success: function(response) {
                        if (response.success) {
                            const user = response.user;
                            $('#editUserId').val(user.id);
                            $('#editUsername').val(user.username);
                            $('#editEmail').val(user.email);
                            $('#editFirstName').val(user.first_name);
                            $('#editLastName').val(user.last_name);
                            $('#editRole').val(user.role);
                            $('#editStatus').val(user.status);
                            $('#editUserModal').show();
                        } else {
                            alert(response.message || 'Failed to fetch user data');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Failed to fetch user data');
                    }
                });
            };

            // Show Delete User Modal
            window.deleteUser = function(userId) {
                $('#deleteUserId').val(userId);
                $('#deleteUserModal').show();
            };

            // Close modal when clicking the close button
            $('.close').click(function() {
                $(this).closest('.modal').hide();
            });

            // Close modal when clicking outside
            $(window).click(function(e) {
                if ($(e.target).attr('id') === 'addUserModal') {
                    $('#addUserModal').fadeOut(200, function() {
                        $('body').css('overflow', 'auto');
                    });
                }
            });

            // Handle Add User Form Submission
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add_user');

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#addUserModal').fadeOut(200, function() {
                                $('body').css('overflow', 'auto');
                                location.reload();
                            });
                        } else {
                            alert(response.message || 'Failed to add user');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('An error occurred while processing your request');
                    }
                });
            });

            // Handle Edit User Form Submission
            $('#editUserForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'edit_user');

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editUserModal').hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to update user');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('An error occurred while processing your request');
                    }
                });
            });

            // Handle Delete Confirmation
            $('#confirmDelete').on('click', function() {
                const userId = $('#deleteUserId').val();
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('id', userId);

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#deleteUserModal').hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to delete user');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('An error occurred while processing your request');
                    }
                });
            });

            // Close modal when clicking close button or outside
            $('.close-modal').click(function() {
                $('#addUserModal').fadeOut(200, function() {
                    $('body').css('overflow', 'auto');
                });
            });
        });

        // Function to close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
</body>
</html>