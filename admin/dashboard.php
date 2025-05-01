<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Static data for demonstration
$stats = [
    'on_process' => 25,
    'on_hold' => 12,
    'not_yet_filing' => 8,
    'total_applicants' => 45
];

$recent_activities = [
    [
        'title' => 'New Application Received',
        'description' => 'John Doe submitted a new application',
        'timestamp' => '2 hours ago',
        'icon' => 'bi-person-plus',
        'color' => '#00C853'
    ],
    [
        'title' => 'Status Updated',
        'description' => 'Application #12345 moved to On Process',
        'timestamp' => '4 hours ago',
        'icon' => 'bi-arrow-repeat',
        'color' => '#2962FF'
    ],
    [
        'title' => 'File Uploaded',
        'description' => 'New spreadsheet file uploaded by Admin',
        'timestamp' => '1 day ago',
        'icon' => 'bi-file-earmark-arrow-up',
        'color' => '#FF6D00'
    ]
];
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
                <div class="card dashboard-card card-on-process">
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
                <div class="card dashboard-card card-on-hold">
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
                <div class="card dashboard-card card-not-filing">
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
                <div class="card dashboard-card card-total">
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
                        <div class="status-box status-on-process">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">On Process Applications</h6>
                                    <small>Last updated: <?php echo date('M d, Y'); ?></small>
                                </div>
                                <span class="badge"><?php echo $stats['on_process']; ?> items</span>
                            </div>
                        </div>
                        <div class="status-box status-on-hold">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">On Hold Applications</h6>
                                    <small>Last updated: <?php echo date('M d, Y'); ?></small>
                                </div>
                                <span class="badge"><?php echo $stats['on_hold']; ?> items</span>
                            </div>
                        </div>
                        <div class="status-box status-not-filing">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Not Yet for Filing</h6>
                                    <small>Last updated: <?php echo date('M d, Y'); ?></small>
                                </div>
                                <span class="badge"><?php echo $stats['not_yet_filing']; ?> items</span>
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
                                    <i class="bi <?php echo $activity['icon']; ?>" style="color: <?php echo $activity['color']; ?>"></i>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <small class="text-muted"><?php echo $activity['timestamp']; ?></small>
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
                    <form action="upload.php" method="post" enctype="multipart/form-data">
                        <div class="custom-file-upload mb-3">
                            <i class="bi bi-cloud-upload"></i>
                            <p class="mt-3 mb-2">Drag and drop files here</p>
                            <small class="text-muted">or</small>
                            <input type="file" class="form-control mt-3" name="file" accept=".csv,.xlsx">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload me-2"></i>Upload File
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    <script>
        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    {
                        title: 'Application Deadline',
                        start: '2024-03-15',
                        color: '#D50000'
                    },
                    {
                        title: 'Monthly Review',
                        start: '2024-03-20',
                        color: '#2962FF'
                    }
                ]
            });
            calendar.render();
        });

        // File upload drag and drop
        const fileUpload = document.querySelector('.custom-file-upload');
        const fileInput = document.querySelector('input[type="file"]');

        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.style.borderColor = '#2962FF';
            fileUpload.style.backgroundColor = 'rgba(41, 98, 255, 0.05)';
        });

        fileUpload.addEventListener('dragleave', () => {
            fileUpload.style.borderColor = '#e2e8f0';
            fileUpload.style.backgroundColor = '#f8fafc';
        });

        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.style.borderColor = '#e2e8f0';
            fileUpload.style.backgroundColor = '#f8fafc';
            fileInput.files = e.dataTransfer.files;
        });

        fileUpload.addEventListener('click', () => {
            fileInput.click();
        });

        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html> 