<?php
// Disable error display and enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

try {
    include 'dbconnection.php';

    // Admin user details
    $username = 'admin';
    $password = 'admin123'; // This will be hashed
    $email = 'admin@example.com';
    $first_name = 'System';
    $last_name = 'Administrator';
    $role = 'admin';
    $status = 'active';

    // Check if admin user already exists
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
        echo "Admin user already exists!\n";
        exit();
    }
    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param("sssssss", 
            $username,
            $hashed_password,
            $email,
            $first_name,
            $last_name,
            $role,
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }

        $admin_id = $stmt->insert_id;
        $stmt->close();

        // Insert default permissions for admin
        $modules = ['dashboard', 'organizational_codes', 'applicants', 'users'];
        $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, module, can_view, can_create, can_edit, can_delete) VALUES (?, ?, 1, 1, 1, 1)");
        
        foreach ($modules as $module) {
            $stmt->bind_param("is", $admin_id, $module);
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert permissions: ' . $stmt->error);
            }
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();

        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Please change this password after first login.\n";

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 