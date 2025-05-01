<?php
session_start();
include '../dbconnection.php';
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: ../login.php');
//     exit();
// }

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$position = isset($_GET['position']) ? $_GET['position'] : '';

// Build query with prepared statements
$query = "SELECT a.*, oc.position, oc.department 
          FROM applicants a 
          LEFT JOIN organizational_codes oc ON a.position_id = oc.id 
          WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($position)) {
    $query .= " AND a.position_id = ?";
    $params[] = $position;
    $types .= 'i';
}

// Get unique positions for filter
$posQuery = "SELECT id, position, department FROM organizational_codes WHERE status = 'active' ORDER BY position";
$stmt = $conn->prepare($posQuery);
$stmt->execute();
$posResult = $stmt->get_result();
$positions = [];
while ($row = $posResult->fetch_assoc()) {
    $positions[] = $row;
}
$stmt->close();

// Execute main query with prepared statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$applicants = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Records - Plantilla Management System</title>
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
        .applicant-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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
                    <a class="nav-link" href="data_management.php">
                        <i class="bi bi-database"></i>
                        <span>Data Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="applicant_records.php">
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
                    <h2 class="mb-1">Applicant Records</h2>
                    <p class="text-muted mb-0">Manage and view applicant information</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApplicantModal">
                    <i class="bi bi-plus-lg me-2"></i>Add New Applicant
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
                            <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="position">
                            <option value="">All Positions</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo $pos['id']; ?>" <?php echo $position == $pos['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos['position'] . ' - ' . $pos['department']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="reviewed" <?php echo $status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="shortlisted" <?php echo $status === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                    <table id="applicantTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applicants as $applicant): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo !empty($applicant['photo']) ? htmlspecialchars($applicant['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($applicant['first_name'] . '+' . $applicant['last_name']); ?>" 
                                             class="applicant-photo" 
                                             alt="Applicant Photo">
                                    </td>
                                    <td><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['position']); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['department']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($applicant['status']) {
                                                'pending' => 'warning',
                                                'reviewed' => 'info',
                                                'shortlisted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($applicant['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($applicant['applied_date'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1 view-applicant" 
                                                data-id="<?php echo $applicant['id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewApplicantModal">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success me-1 update-status" 
                                                data-id="<?php echo $applicant['id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateStatusModal">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-applicant" 
                                                data-id="<?php echo $applicant['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Applicant Modal -->
    <div class="modal fade" id="addApplicantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Applicant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addApplicantForm" enctype="multipart/form-data">
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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <select class="form-select" name="position_id" required>
                                <option value="">Select Position</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo $pos['id']; ?>">
                                        <?php echo htmlspecialchars($pos['position'] . ' - ' . $pos['department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resume</label>
                            <input type="file" class="form-control" name="resume" accept=".pdf,.doc,.docx" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveApplicant">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Applicant Modal -->
    <div class="modal fade" id="viewApplicantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Applicant Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img id="viewPhoto" src="" class="img-fluid rounded-circle mb-2" style="max-width: 150px;">
                            <h5 id="viewName" class="mb-1"></h5>
                            <p id="viewPosition" class="text-muted"></p>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <p id="viewEmail" class="form-control-static"></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <p id="viewPhone" class="form-control-static"></p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <p id="viewDepartment" class="form-control-static"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <p id="viewStatus" class="form-control-static"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Applied Date</label>
                                <p id="viewAppliedDate" class="form-control-static"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Resume</label>
                                <a id="viewResume" href="#" target="_blank" class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>View Resume
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Applicant Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm">
                        <input type="hidden" name="applicant_id" id="statusApplicantId">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="shortlisted">Shortlisted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStatus">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = $('#applicantTable').DataTable({
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

            // View Applicant Details
            document.querySelectorAll('.view-applicant').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    fetch(`get_applicant.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const applicant = data.applicant;
                                document.getElementById('viewPhoto').src = applicant.photo || 
                                    `https://ui-avatars.com/api/?name=${encodeURIComponent(applicant.first_name + '+' + applicant.last_name)}`;
                                document.getElementById('viewName').textContent = `${applicant.first_name} ${applicant.last_name}`;
                                document.getElementById('viewPosition').textContent = applicant.position;
                                document.getElementById('viewEmail').textContent = applicant.email;
                                document.getElementById('viewPhone').textContent = applicant.phone;
                                document.getElementById('viewDepartment').textContent = applicant.department;
                                document.getElementById('viewStatus').innerHTML = 
                                    `<span class="badge bg-${getStatusColor(applicant.status)}">${applicant.status}</span>`;
                                document.getElementById('viewAppliedDate').textContent = 
                                    new Date(applicant.applied_date).toLocaleDateString();
                                document.getElementById('viewResume').href = applicant.resume;
                            }
                        });
                });
            });

            // Update Status
            document.querySelectorAll('.update-status').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    document.getElementById('statusApplicantId').value = id;
                });
            });

            // Save Status Update
            document.getElementById('saveStatus').addEventListener('click', function() {
                const form = document.getElementById('updateStatusForm');
                const formData = new FormData(form);

                fetch('update_applicant_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating status');
                    }
                });
            });

            // Delete Applicant
            document.querySelectorAll('.delete-applicant').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this applicant?')) {
                        const id = this.dataset.id;
                        fetch('delete_applicant.php', {
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
                                alert('Error deleting applicant');
                            }
                        });
                    }
                });
            });

            // Save New Applicant
            document.getElementById('saveApplicant').addEventListener('click', function() {
                const form = document.getElementById('addApplicantForm');
                const formData = new FormData(form);

                fetch('add_applicant.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error adding applicant');
                    }
                });
            });

            function getStatusColor(status) {
                switch(status) {
                    case 'pending': return 'warning';
                    case 'reviewed': return 'info';
                    case 'shortlisted': return 'success';
                    case 'rejected': return 'danger';
                    default: return 'secondary';
                }
            }
        });
    </script>
</body>
</html> 