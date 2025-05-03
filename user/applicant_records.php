<?php
session_start();
include '../dbconnection.php';
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: ../login.php');
//     exit();
// }

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$remarks = isset($_GET['remarks']) ? $_GET['remarks'] : '';
$position = isset($_GET['position']) ? $_GET['position'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : '';
$division = isset($_GET['division']) ? $_GET['division'] : '';

// Build query with prepared statements
$query = "SELECT r.*, 
          DATE_FORMAT(r.date_of_birth, '%M %d, %Y') as formatted_dob,
          DATE_FORMAT(r.date_orig_appt, '%M %d, %Y') as formatted_orig_appt,
          DATE_FORMAT(r.date_govt_srvc, '%M %d, %Y') as formatted_govt_srvc,
          DATE_FORMAT(r.date_last_promotion, '%M %d, %Y') as formatted_last_promotion,
          DATE_FORMAT(r.date_last_increment, '%M %d, %Y') as formatted_last_increment,
          DATE_FORMAT(r.date_longevity, '%M %d, %Y') as formatted_longevity
          FROM records r 
          WHERE 1=1";

$params = [];
$types = '';

// Add search filter
if (!empty($search)) {
    $query .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ? OR r.ext_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ssss';
}

// Add month filter
if (!empty($month)) {
    $query .= " AND MONTH(r.created_at) = ?";
    $params[] = $month;
    $types .= 'i';
}

// Add division filter
if (!empty($division)) {
    $query .= " AND r.division = ?";
    $params[] = $division;
    $types .= 's';
}

// Add remarks filter
if (!empty($remarks)) {
    $query .= " AND r.remarks = ?";
    $params[] = $remarks;
    $types .= 's';
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
$records = $result->fetch_all(MYSQLI_ASSOC);
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
    <link rel="shortcut icon" href="../assets/img/logo.jpg" type="image/x-icon">
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
        .sidebar {
            width: 250px;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            border-right: 1px solid #e9ecef;
        }

        .sidebar.minimized {
            width: 70px;
        }

        .sidebar.minimized .sidebar-header .title,
        .sidebar.minimized .nav-link span,
        .sidebar.minimized .user-details,
        .sidebar.minimized .logout-btn span {
            display: none;
        }

        .sidebar.minimized .nav-link {
            padding: 0.5rem;
            text-align: center;
        }

        .sidebar.minimized .nav-link i {
            margin-right: 0;
            font-size: 1.2rem;
        }

        .sidebar.minimized .user-info {
            padding: 0.5rem;
            justify-content: center;
        }

        .sidebar.minimized .user-info img {
            margin-right: 0;
        }

        .sidebar.minimized .logout-btn a {
            padding: 0.5rem;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        .toggle-sidebar {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            color: #495057;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .toggle-sidebar:hover {
            background: #e9ecef;
            color: #212529;
            transform: scale(1.05);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .toggle-sidebar i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .sidebar.minimized .toggle-sidebar i {
            transform: rotate(180deg);
        }

        .sidebar.minimized .toggle-sidebar {
            right: 10px;
        }

        .toggle-sidebar::after {
            content: 'Minimize Sidebar';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #212529;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .toggle-sidebar:hover::after {
            opacity: 1;
            visibility: visible;
        }

        .sidebar.minimized .toggle-sidebar::after {
            content: 'Expand Sidebar';
        }

        .sidebar-header {
            position: relative;
            padding-right: 50px;
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
                <h4><?php echo $_SESSION['username']; ?></h4>
                <p>Plantilla Management</p>
            </div>
            <button class="toggle-sidebar" id="toggleSidebar" title="Minimize Sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>
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
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Applicant Records</h2>
                    <p class="text-muted mb-0">Manage and view applicant information</p>
                </div>
               
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="month">
                            <option value="">All Months</option>
                            <?php
                            $months = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                            ];
                            foreach ($months as $num => $name) {
                                $selected = $month == $num ? 'selected' : '';
                                echo "<option value='$num' $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="division">
                            <option value="">All Divisions</option>
                            <?php
                            $divisions = array_unique(array_column($records, 'division'));
                            foreach ($divisions as $div) {
                                $selected = $division == $div ? 'selected' : '';
                                echo "<option value='$div' $selected>$div</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="remarks">
                            <option value="">All Remarks</option>
                            <option value="Not Yet for Filling up" <?php echo $remarks === 'Not Yet for Filling up' ? 'selected' : ''; ?>>Not Yet for Filling up</option>
                            <option value="On-Hold" <?php echo $remarks === 'On-Hold' ? 'selected' : ''; ?>>On-Hold</option>
                            <option value="On Process" <?php echo $remarks === 'On Process' ? 'selected' : ''; ?>>On Process</option>
                        </select>
                    </div>
                    <div class="col-md-3">
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
                    <table id="recordsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID No.</th>
                                <th>Full Name</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Ext. Name</th>
                                <th>MI</th>
                                <th>Sex</th>
                                <th>Position Title</th>
                                <th>Item No.</th>
                                <th>Tech Code</th>
                                <th>Level</th>
                                <th>Appointment Status</th>
                                <th>SG</th>
                                <th>Step</th>
                                <th>Date of Birth</th>
                                <th>Date of Original Appointment</th>
                                <th>Date of Govt. Service</th>
                                <th>Date of Last Promotion</th>
                                <th>Date of Last Increment</th>
                                <th>Date of Longevity</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['id_no']); ?></td>
                                    <td><?php echo htmlspecialchars($record['last_name'] . ', ' . $record['first_name'] . ' ' . $record['middle_name'] . ' ' . $record['ext_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['middle_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['ext_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['mi']); ?></td>
                                    <td><?php echo htmlspecialchars($record['sex']); ?></td>
                                    <td><?php echo htmlspecialchars($record['position_title']); ?></td>
                                    <td><?php echo htmlspecialchars($record['item_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['tech_code']); ?></td>
                                    <td><?php echo htmlspecialchars($record['level']); ?></td>
                                    <td><?php echo htmlspecialchars($record['appointment_status']); ?></td>
                                    <td><?php echo htmlspecialchars($record['sg']); ?></td>
                                    <td><?php echo htmlspecialchars($record['step']); ?></td>
                                    <td><?php echo $record['formatted_dob']; ?></td>
                                    <td><?php echo $record['formatted_orig_appt']; ?></td>
                                    <td><?php echo $record['formatted_govt_srvc']; ?></td>
                                    <td><?php echo $record['formatted_last_promotion']; ?></td>
                                    <td><?php echo $record['formatted_last_increment']; ?></td>
                                    <td><?php echo $record['formatted_longevity']; ?></td>
                                    <td data-remarks><?php echo htmlspecialchars($record['remarks']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success update-remarks" 
                                                data-id="<?php echo $record['id']; ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateRemarksModal">
                                            <i class="bi bi-pencil"></i> 
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
    <div class="modal fade" id="viewRecordModal" tabindex="-1">
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

    <!-- Update Remarks Modal -->
    <div class="modal fade" id="updateRemarksModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Remarks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateRemarksForm">
                        <input type="hidden" name="record_id" id="remarksRecordId">
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <select class="form-select" name="remarks" required>
                                <option value="Not Yet for Filling up">Not Yet for Filling up</option>
                                <option value="On-Hold">On-Hold</option>
                                <option value="On Process">On Process</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveRemarks">Update Remarks</button>
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
            const table = $('#recordsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                scrollX: true,
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
            document.querySelectorAll('.view-record').forEach(button => {
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

            // Update Remarks
            document.querySelectorAll('.update-remarks').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const currentRemarks = this.closest('tr').querySelector('td[data-remarks]').textContent.trim();
                    document.getElementById('remarksRecordId').value = id;
                    document.querySelector('#updateRemarksForm select[name="remarks"]').value = currentRemarks;
                });
            });

            // Save Remarks Update
            document.getElementById('saveRemarks').addEventListener('click', function() {
                const form = document.getElementById('updateRemarksForm');
                const formData = new FormData(form);
                const recordId = formData.get('record_id');
                const newRemarks = formData.get('remarks');

                // Show loading state
                const saveButton = this;
                const originalText = saveButton.innerHTML;
                saveButton.disabled = true;
                saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

                fetch('update_remarks.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the remarks in the table without reloading
                        const row = document.querySelector(`.update-remarks[data-id="${recordId}"]`).closest('tr');
                        row.querySelector('td[data-remarks]').textContent = newRemarks;
                        
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('updateRemarksModal'));
                        modal.hide();
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message || 'Remarks updated successfully',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to update remarks'
                        });
                    }
                })
                .catch(error => {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while updating remarks'
                    });
                })
                .finally(() => {
                    // Reset button state
                    saveButton.disabled = false;
                    saveButton.innerHTML = originalText;
                });
            });

            // Delete Applicant
            document.querySelectorAll('.delete-record').forEach(button => {
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

        $(document).ready(function() {
            const sidebar = $('#sidebar');
            const mainContent = $('.main-content');
            const toggleBtn = $('#toggleSidebar');
            
            // Check if sidebar was minimized in previous session
            if (localStorage.getItem('sidebarMinimized') === 'true') {
                sidebar.addClass('minimized');
                mainContent.addClass('expanded');
                toggleBtn.attr('title', 'Expand Sidebar');
            }
            
            // Toggle sidebar
            toggleBtn.on('click', function() {
                sidebar.toggleClass('minimized');
                mainContent.toggleClass('expanded');
                
                // Update button title
                if (sidebar.hasClass('minimized')) {
                    toggleBtn.attr('title', 'Expand Sidebar');
                    localStorage.setItem('sidebarMinimized', 'true');
                } else {
                    toggleBtn.attr('title', 'Minimize Sidebar');
                    localStorage.setItem('sidebarMinimized', 'false');
                }
            });
        });
    </script>
</body>
</html> 