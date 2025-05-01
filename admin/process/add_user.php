<?php
// Disable error display and enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../../error.log');

// Start output buffering
ob_start();

try {
    session_start();
    include '../../dbconnection.php';

    // Log the received POST data
    error_log("Received POST data: " . print_r($_POST, true));

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = ['success' => false, 'message' => ''];
        
        try {
            // Get form data
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $role = $_POST['role'] ?? '';
            
            // Log the processed data
            error_log("Processed data - Username: $username, Email: $email, First Name: $first_name, Last Name: $last_name, Role: $role");
            
            // Validate input
            if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($role)) {
                throw new Exception('All fields are required');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $stmt->bind_param("ss", $username, $email);
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                throw new Exception('Username or email already exists');
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password, email, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                
                $stmt->bind_param('sssssss', 
                    $first_name, 
                    $last_name, 
                    $username, 
                    $hashed_password, 
                    $email, 
                    $role, 
                    'active'
                );
                if (!$stmt->execute()) {
                    throw new Exception('Database error: ' . $stmt->error);
                }
                
                $new_user_id = $stmt->insert_id;
                error_log("New user created with ID: $new_user_id");
                
                // Log the query for debugging
                file_put_contents('../../logs/user_creation.log', "[".date('Y-m-d H:i:s')."] Creating user: $username\n", FILE_APPEND);
                
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
                $stmt->bind_param("iss", $_SESSION['user_id'] ?? 1, $description, $ip_address);
                if (!$stmt->execute()) {
                    throw new Exception('Database error: ' . $stmt->error);
                }
                
                // Commit transaction
                $conn->commit();
                
                $response['success'] = true;
                $response['message'] = 'User added successfully';
                error_log("User added successfully");
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Error in add_user.php: " . $e->getMessage());
            $response['message'] = $e->getMessage();
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }
        
        // Clear any previous output
        ob_clean();
        
        // Set proper headers and send JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    // Send error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
    exit();
} 