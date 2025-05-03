<?php
require '../dbconnection.php';

// Create divisions table
$conn->query("CREATE TABLE IF NOT EXISTS divisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert divisions
$divisions = [
    'Office of the Administrator',
    'Administrative Division',
    'Human Resources Management and Development Section',
    // ... all 43 divisions
    'Field Stations'
];

foreach ($divisions as $division) {
    $stmt = $conn->prepare("INSERT IGNORE INTO divisions (name) VALUES (?)");
    $stmt->bind_param("s", $division);
    $stmt->execute();
}

echo "Divisions table created successfully";
