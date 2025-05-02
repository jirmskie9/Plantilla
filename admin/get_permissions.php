<?php
session_start();
include '../dbconnection.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user ID is provided
if (!isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$userId = (int)$_GET['user_id'];

// Fetch user permissions
$stmt = $conn->prepare("SELECT module, can_view, can_create, can_edit, can_delete FROM user_permissions WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$permissions = [];
while ($row = $result->fetch_assoc()) {
    $permissions[$row['module']] = [
        'can_view' => (bool)$row['can_view'],
        'can_create' => (bool)$row['can_create'],
        'can_edit' => (bool)$row['can_edit'],
        'can_delete' => (bool)$row['can_delete']
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'permissions' => $permissions]); 