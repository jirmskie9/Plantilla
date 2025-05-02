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
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$userId = (int)$_GET['id'];

// Fetch user data
$stmt = $conn->prepare("SELECT id, username, email, first_name, last_name, role, status FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not found']);
} 