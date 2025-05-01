<?php
session_start();
include '../dbconnection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
   <?php include '../assets/css/my_account_admin.php'; ?>
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
                    <a class="nav-link active" href="my_account.php">
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
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="profile-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <img src="<?php echo !empty($user['photo']) ? htmlspecialchars($user['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . '+' . $user['last_name']); ?>" 
                             class="profile-photo" 
                             alt="Profile Photo">
                    </div>
                    <div class="col-md-10">
                        <h2 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                        <p class="mb-0"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <ul class="nav nav-pills flex-column" id="profileTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="profile-tab" data-bs-toggle="pill" href="#profile" role="tab">
                                        <i class="bi bi-person me-2"></i>Profile
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="password-tab" data-bs-toggle="pill" href="#password" role="tab">
                                        <i class="bi bi-key me-2"></i>Password
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="activity-tab" data-bs-toggle="pill" href="#activity" role="tab">
                                        <i class="bi bi-clock-history me-2"></i>Activity Log
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Profile Information</h5>
                                </div>
                                <div class="card-body">
                                    <form id="profileForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <input type="text" class="form-control" value="<?php echo ucfirst($user['status']); ?>" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Profile Photo</label>
                                            <input type="file" class="form-control" name="photo" accept="image/*">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form id="passwordForm">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Log Tab -->
                        <div class="tab-pane fade" id="activity" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Activity Log</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Activity</th>
                                                    <th>IP Address</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><?php echo date('M d, Y H:i'); ?></td>
                                                    <td>Logged in</td>
                                                    <td>192.168.1.1</td>
                                                </tr>
                                                <!-- Add more activity logs here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update Profile
            document.getElementById('profileForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Profile updated successfully');
                        location.reload();
                    } else {
                        alert('Error updating profile');
                    }
                });
            });

            // Change Password
            document.getElementById('passwordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                if (formData.get('new_password') !== formData.get('confirm_password')) {
                    alert('New passwords do not match');
                    return;
                }

                fetch('change_password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Password changed successfully');
                        this.reset();
                    } else {
                        alert(data.message || 'Error changing password');
                    }
                });
            });
        });
    </script>
</body>
</html> 