<?php
require '../dbconnection.php';

// Create divisions table
$sql = "CREATE TABLE IF NOT EXISTS divisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create records table
$sql = "CREATE TABLE IF NOT EXISTS records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NOT NULL,
    data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES divisions(id)
)";
$conn->query($sql);

// Insert divisions
$divisions = [
    'Office of the Administrator',
    'Administrative Division',
    // ... all 43 divisions
    'Field Stations'
];

foreach ($divisions as $division) {
    $stmt = $conn->prepare("INSERT IGNORE INTO divisions (name) VALUES (?)");
    $stmt->bind_param("s", $division);
    $stmt->execute();
}

echo "Database tables created successfully";
