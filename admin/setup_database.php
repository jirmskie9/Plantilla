<?php
require '../dbconnection.php';

// 1. Divisions table
$conn->query("DROP TABLE IF EXISTS records");
$conn->query("DROP TABLE IF EXISTS divisions");

$conn->query("CREATE TABLE divisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (code)
)");

// 2. Records table
$conn->query("CREATE TABLE records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NOT NULL,
    employee_id VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    salary_grade VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES divisions(id),
    UNIQUE KEY (employee_id)
)");

// 3. Insert divisions
$divisions = [
    ['code' => 'OA', 'name' => 'Office of the Administrator'],
    ['code' => 'AD', 'name' => 'Administrative Division'],
    ['code' => 'HR', 'name' => 'Human Resources Management'],
    // ... all 43 divisions with codes
    ['code' => 'FS', 'name' => 'Field Stations']
];

$stmt = $conn->prepare("INSERT INTO divisions (code, name) VALUES (?, ?)");
foreach ($divisions as $division) {
    $stmt->bind_param("ss", $division['code'], $division['name']);
    $stmt->execute();
}

// 4. Create indexes for better performance
$conn->query("CREATE INDEX idx_division ON records(division_id)");
$conn->query("CREATE INDEX idx_status ON records(status)");

echo "Database schema created successfully with " . count($divisions) . " divisions";
