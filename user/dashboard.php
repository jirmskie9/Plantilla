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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
   <?php include '../assets/css/dashboard.php'; ?>
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
                    <a class="nav-link active" href="dashboard.php">
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
                    <h2 class="mb-1">Dashboard</h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-upload me-2"></i>Upload File
                    </button>
                </div>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="row g-4 mb-4">
            <?php
            $statusColors = [
                'pending' => 'warning',
                'reviewed' => 'info',
                'shortlisted' => 'success',
                'rejected' => 'danger'
            ];
            
            foreach ($statusCounts as $status): ?>
                <div class="col-md-3">
                    <div class="card status-card h-100" onclick="filterApplicants('<?php echo $status['status']; ?>')">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-0"><?php echo ucfirst($status['status']); ?></h6>
                                    <h3 class="mb-0"><?php echo $status['count']; ?></h3>
                                </div>
                                <div class="icon-circle bg-<?php echo $statusColors[$status['status']]; ?>-subtle">
                                    <i class="bi bi-<?php 
                                        echo match($status['status']) {
                                            'pending' => 'clock',
                                            'reviewed' => 'check2-circle',
                                            'shortlisted' => 'star',
                                            'rejected' => 'x-circle',
                                            default => 'circle'
                                        };
                                    ?> text-<?php echo $statusColors[$status['status']]; ?>"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <!-- Recent Files -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Files</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentFiles)): ?>
                            <p class="text-muted text-center">No recent files</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recentFiles as $file): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-file-earmark me-2"></i>
                                                <?php echo htmlspecialchars($file['file_name']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($file['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivities)): ?>
                            <p class="text-muted text-center">No recent activities</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-<?php 
                                                    echo match($activity['activity_type']) {
                                                        'login' => 'box-arrow-in-right',
                                                        'logout' => 'box-arrow-right',
                                                        'create' => 'plus-circle',
                                                        'update' => 'pencil',
                                                        'delete' => 'trash',
                                                        'upload' => 'upload',
                                                        'download' => 'download',
                                                        default => 'circle'
                                                    };
                                                ?> me-2"></i>
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm">
                        <div class="file-upload mb-3" id="dropZone">
                            <i class="bi bi-cloud-upload display-4 text-muted"></i>
                            <p class="mt-2 mb-0">Drag and drop files here or click to browse</p>
                            <input type="file" class="d-none" name="file" accept=".csv,.xlsx,.pdf">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File Type</label>
                            <select class="form-select" name="file_type" required>
                                <option value="csv">CSV</option>
                                <option value="xlsx">Excel (XLSX)</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="uploadBtn">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // File upload handling
            const dropZone = document.getElementById('dropZone');
            const fileInput = dropZone.querySelector('input[type="file"]');

            dropZone.addEventListener('click', () => fileInput.click());

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#2962FF';
                dropZone.style.backgroundColor = '#f8f9fa';
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.style.borderColor = '#dee2e6';
                dropZone.style.backgroundColor = 'transparent';
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#dee2e6';
                dropZone.style.backgroundColor = 'transparent';
                fileInput.files = e.dataTransfer.files;
            });

            // Upload form submission
            document.getElementById('uploadBtn').addEventListener('click', function() {
                const form = document.getElementById('uploadForm');
                const formData = new FormData(form);

                fetch('upload_file.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('File uploaded successfully');
                        location.reload();
                    } else {
                        alert('Error uploading file');
                    }
                });
            });

            // Filter applicants by status
            window.filterApplicants = function(status) {
                window.location.href = `applicant_records.php?status=${status}`;
            };
        });
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