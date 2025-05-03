<?php
require '../dbconnection.php';

// Insert test records
$positions = ['Manager', 'Supervisor', 'Staff', 'Assistant'];
$statuses = ['active', 'inactive'];

for ($i = 1; $i <= 50; $i++) {
    $division_id = rand(1, 3); // Matches the divisions we inserted
    $employee_id = 'EMP' . str_pad($i, 4, '0', STR_PAD_LEFT);
    $name = 'Employee ' . $i;
    $position = $positions[array_rand($positions)];
    $salary_grade = 'SG-' . rand(1, 15);
    $status = $statuses[array_rand($statuses)];
    
    $stmt = $conn->prepare("INSERT INTO records 
        (division_id, employee_id, name, position, salary_grade, status) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $division_id, $employee_id, $name, $position, $salary_grade, $status);
    $stmt->execute();
}

echo "Successfully inserted 50 test records";
