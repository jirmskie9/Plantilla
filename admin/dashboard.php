<?php
session_start();
include '../dbconnection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get real-time status counts
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN remarks = 'On Process' THEN 1 END) as on_process,
        COUNT(CASE WHEN remarks = 'On-Hold' THEN 1 END) as on_hold,
        COUNT(CASE WHEN remarks = 'Not Yet for Filling up' THEN 1 END) as not_yet_filing,
        COUNT(*) as total_applicants
    FROM records
");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent activities
$stmt = $conn->prepare("
    SELECT al.*, u.first_name, u.last_name 
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <style>
        .dashboard-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-on-process {
            border-left: 4px solid #2962FF;
        }
        .card-on-hold {
            border-left: 4px solid #FF6D00;
        }
        .card-not-filing {
            border-left: 4px solid #00C853;
        }
        .card-total {
            border-left: 4px solid #9C27B0;
        }
        .status-box {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .status-on-process {
            border-left: 4px solid #2962FF;
        }
        .status-on-hold {
            border-left: 4px solid #FF6D00;
        }
        .status-not-filing {
            border-left: 4px solid #00C853;
        }
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .file-upload-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            border: 2px dashed #dee2e6;
            transition: all 0.3s;
        }
        .file-upload-section:hover {
            border-color: #2962FF;
            background: #f0f7ff;
        }
        .custom-file-upload {
            cursor: pointer;
        }
        .custom-file-upload i {
            font-size: 3rem;
            color: #2962FF;
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
                    <a class="nav-link active" href="dashboard.php">
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
                    <h2 class="mb-1">Dashboard Overview</h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                </div>
                <button class="btn btn-light d-md-none" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card dashboard-card card-on-process" onclick="window.location.href='applicant_records.php?remarks=On Process'">
                    <div class="card-body">
                        <h5 class="card-title">On Process</h5>
                        <h2 class="card-value"><?php echo $stats['on_process']; ?></h2>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-arrow-up-circle fs-4 me-2"></i>
                            <span>View Details</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card card-on-hold" onclick="window.location.href='applicant_records.php?remarks=On-Hold'">
                    <div class="card-body">
                        <h5 class="card-title">On Hold</h5>
                        <h2 class="card-value"><?php echo $stats['on_hold']; ?></h2>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-pause-circle fs-4 me-2"></i>
                            <span>View Details</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card card-not-filing" onclick="window.location.href='applicant_records.php?remarks=Not Yet for Filling up'">
                    <div class="card-body">
                        <h5 class="card-title">Not Yet for Filing</h5>
                        <h2 class="card-value"><?php echo $stats['not_yet_filing']; ?></h2>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clock fs-4 me-2"></i>
                            <span>View Details</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card card-total" onclick="window.location.href='applicant_records.php'">
                    <div class="card-body">
                        <h5 class="card-title">Total Applicants</h5>
                        <h2 class="card-value"><?php echo $stats['total_applicants']; ?></h2>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-people fs-4 me-2"></i>
                            <span>View All</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Status Boxes and Recent Activities -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Status Overview</h5>
                        <div class="status-box status-on-process" onclick="window.location.href='applicant_records.php?remarks=On Process'">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">On Process Applications</h6>
                                    <small>Last updated: <?php echo date('M d, Y'); ?></small>
                                </div>
                                <span class="badge bg-primary"><?php echo $stats['on_process']; ?> items</span>
                            </div>
                        </div>
                        <div class="status-box status-on-hold" onclick="window.location.href='applicant_records.php?remarks=On-Hold'">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">On Hold Applications</h6>
                                    <small>Last updated: <?php echo date('M d, Y'); ?></small>
                                </div>
                                <span class="badge bg-warning"><?php echo $stats['on_hold']; ?> items</span>
                            </div>
                        </div>
                        <div class="status-box status-not-filing" onclick="window.location.href='applicant_records.php?remarks=Not Yet for Filling up'">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Not Yet for Filing</h6>
                                    <small>Last updated: <?php echo date('M d, Y'); ?></small>
                                </div>
                                <span class="badge bg-success"><?php echo $stats['not_yet_filing']; ?> items</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Recent Activities</h5>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-start">
                                    <i class="bi <?php 
                                        echo match($activity['activity_type']) {
                                            'login' => 'bi-box-arrow-in-right',
                                            'logout' => 'bi-box-arrow-right',
                                            'create' => 'bi-plus-circle',
                                            'update' => 'bi-pencil',
                                            'delete' => 'bi-trash',
                                            'upload' => 'bi-upload',
                                            'download' => 'bi-download',
                                            default => 'bi-circle'
                                        };
                                    ?>" style="color: <?php 
                                        echo match($activity['activity_type']) {
                                            'login' => '#00C853',
                                            'logout' => '#FF6D00',
                                            'create' => '#2962FF',
                                            'update' => '#FFD600',
                                            'delete' => '#D50000',
                                            'upload' => '#9C27B0',
                                            'download' => '#00BCD4',
                                            default => '#9E9E9E'
                                        };
                                    ?>"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></h6>
                                        <p class="text-muted mb-0">By <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></p>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Calendar and File Upload -->
            <div class="col-lg-4">
                <div class="calendar-container mb-4">
                    <h5 class="mb-4">Monthly Calendar</h5>
                    <div id="calendar"></div>
                </div>

                <div class="file-upload-section">
                    <h5 class="mb-4">File Upload</h5>
                    <form id="uploadForm" action="api/upload_workbook.php" method="post" enctype="multipart/form-data">
                        <div class="custom-file-upload mb-3">
                            <i class="bi bi-cloud-upload"></i>
                            <p class="mt-3 mb-2">Drag and drop files here</p>
                            <small class="text-muted">or</small>
                            <input type="file" class="form-control mt-3" id="file" name="file" accept=".csv,.xlsx,.xls" required>
                        </div>
                       
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload me-2"></i>Upload File
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- File Management Section -->
        
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize calendar
            var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                events: 'api/get_calendar_events.php',
                eventClick: function(info) {
                    window.location.href = 'data_management.php?month=' + info.event.start.toISOString().slice(0, 7);
                }
            });
            calendar.render();

            // File upload handling
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                // Show loading state
                Swal.fire({
                    title: 'Uploading...',
                    text: 'Please wait while we process your file',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'api/upload_workbook.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: result.message || 'File uploaded successfully',
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => {
                                    // Redirect to data management page
                                    window.location.href = 'data_management.php?month=' + $('#month').val();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Upload Failed',
                                    text: result.error || 'An error occurred during upload',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to process server response',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: 'An error occurred during upload. Please try again.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 