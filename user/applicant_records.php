<?php
session_start();
include '../dbconnection.php';

// Check if user is logged in
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

// Get user permissions
$stmt = $conn->prepare("SELECT * FROM user_permissions WHERE user_id = ? AND module = 'applicants'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$permissions = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$position = isset($_GET['position']) ? $_GET['position'] : '';

// Build query with prepared statements
$query = "SELECT 
    a.*,
    oc.position,
    oc.department,
    u.first_name as created_by_first,
    u.last_name as created_by_last
FROM applicants a
LEFT JOIN organizational_codes oc ON a.position_id = oc.id
LEFT JOIN users u ON a.created_by = u.id
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

// Execute query with prepared statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$applicants = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get active positions for filter
$posQuery = "SELECT id, position, department FROM organizational_codes WHERE status = 'active' ORDER BY position";
$stmt = $conn->prepare($posQuery);
$stmt->execute();
$posResult = $stmt->get_result();
$positions = [];
while ($row = $posResult->fetch_assoc()) {
    $positions[] = $row;
}
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
                    <a class="nav-link active" href="applicant_records.php">
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
                <a class="nav-link" href="../logout.php" >
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
                <?php if ($permissions && $permissions['can_create']): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApplicantModal">
                    <i class="bi bi-plus-lg me-2"></i>Add New Applicant
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
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Applicants Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="applicantTable">
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
                                        <img src="<?php echo !empty($applicant['photo_path']) ? htmlspecialchars($applicant['photo_path']) : 'https://ui-avatars.com/api/?name=' . urlencode($applicant['first_name'] . '+' . $applicant['last_name']); ?>" 
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
                                    <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-applicant" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewApplicantModal"
                                                    data-id="<?php echo $applicant['id']; ?>"
                                                    data-first-name="<?php echo htmlspecialchars($applicant['first_name']); ?>"
                                                    data-last-name="<?php echo htmlspecialchars($applicant['last_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($applicant['email']); ?>"
                                                    data-phone="<?php echo htmlspecialchars($applicant['phone']); ?>"
                                                    data-position="<?php echo htmlspecialchars($applicant['position']); ?>"
                                                    data-department="<?php echo htmlspecialchars($applicant['department']); ?>"
                                                    data-status="<?php echo $applicant['status']; ?>"
                                                    data-remarks="<?php echo htmlspecialchars($applicant['remarks']); ?>"
                                                    data-photo="<?php echo htmlspecialchars($applicant['photo_path']); ?>"
                                                    data-resume="<?php echo htmlspecialchars($applicant['resume_path']); ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($permissions && $permissions['can_edit']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-applicant"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editApplicantModal"
                                                    data-id="<?php echo $applicant['id']; ?>"
                                                    data-first-name="<?php echo htmlspecialchars($applicant['first_name']); ?>"
                                                    data-last-name="<?php echo htmlspecialchars($applicant['last_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($applicant['email']); ?>"
                                                    data-phone="<?php echo htmlspecialchars($applicant['phone']); ?>"
                                                    data-position-id="<?php echo $applicant['position_id']; ?>"
                                                    data-status="<?php echo $applicant['status']; ?>"
                                                    data-remarks="<?php echo htmlspecialchars($applicant['remarks']); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($permissions && $permissions['can_delete']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-applicant" 
                                                    data-id="<?php echo $applicant['id']; ?>">
                                                <i class="bi bi-trash"></i>
                    </button>
                                            <?php endif; ?>
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

    <!-- Add Applicant Modal -->
    <?php if ($permissions && $permissions['can_create']): ?>
    <div class="modal fade" id="addApplicantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Applicant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addApplicantForm" method="POST" action="process/add_applicant.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Applicant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Email:</strong></p>
                                    <p id="viewEmail"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Phone:</strong></p>
                                    <p id="viewPhone"></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Department:</strong></p>
                                    <p id="viewDepartment"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Status:</strong></p>
                                    <p id="viewStatus"></p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Remarks:</strong></p>
                                <p id="viewRemarks"></p>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Resume:</strong></p>
                                <a id="viewResume" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>View Resume
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Applicant Modal -->
    <?php if ($permissions && $permissions['can_edit']): ?>
    <div class="modal fade" id="editApplicantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Applicant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editApplicantForm" method="POST" action="process/edit_applicant.php">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="editLastName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="editPhone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <select class="form-select" name="position_id" id="editPositionId" required>
                                <option value="">Select Position</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo $pos['id']; ?>">
                                        <?php echo htmlspecialchars($pos['position'] . ' - ' . $pos['department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="shortlisted">Shortlisted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" id="editRemarks" rows="3"></textarea>
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
        $(document).ready(function() {
            // Initialize DataTable
            $('#applicantTable').DataTable({
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

            // Handle view applicant modal
            $('.view-applicant').on('click', function() {
                const data = $(this).data();
                $('#viewPhoto').attr('src', data.photo || `https://ui-avatars.com/api/?name=${encodeURIComponent(data.firstName + '+' + data.lastName)}`);
                $('#viewName').text(data.firstName + ' ' + data.lastName);
                $('#viewEmail').text(data.email);
                $('#viewPhone').text(data.phone || 'N/A');
                $('#viewPosition').text(data.position);
                $('#viewDepartment').text(data.department);
                $('#viewStatus').html(`<span class="badge bg-${getStatusColor(data.status)}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>`);
                $('#viewRemarks').text(data.remarks || 'No remarks');
                $('#viewResume').attr('href', data.resume);
            });

            // Handle edit applicant modal
            $('.edit-applicant').on('click', function() {
                const data = $(this).data();
                $('#editId').val(data.id);
                $('#editFirstName').val(data.firstName);
                $('#editLastName').val(data.lastName);
                $('#editEmail').val(data.email);
                $('#editPhone').val(data.phone);
                $('#editPositionId').val(data.positionId);
                $('#editStatus').val(data.status);
                $('#editRemarks').val(data.remarks);
            });

            // Handle delete applicant
            $('.delete-applicant').on('click', function() {
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
                        window.location.href = `process/delete_applicant.php?id=${id}`;
                    }
                });
            });

            // Helper function for status colors
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