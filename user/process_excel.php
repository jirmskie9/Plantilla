<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
include '../dbconnection.php';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Load Excel file
$inputFileName = '../samplefile.xlsx';
$spreadsheet = IOFactory::load($inputFileName);
$worksheet = $spreadsheet->getActiveSheet();

// Get the highest row and column
$highestRow = $worksheet->getHighestRow();
$highestColumn = $worksheet->getHighestColumn();

// Prepare SQL statement
$stmt = $pdo->prepare("INSERT INTO organizational_codes (code, description, department, position, status) VALUES (?, ?, ?, ?, ?)");

// Start from row 2 to skip headers
for ($row = 2; $row <= $highestRow; $row++) {
    $code = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
    $description = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
    $department = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
    $position = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
    $status = $worksheet->getCellByColumnAndRow(5, $row)->getValue();

    // Execute the prepared statement
    $stmt->execute([$code, $description, $department, $position, $status]);
}

echo "Data imported successfully!";
?> 