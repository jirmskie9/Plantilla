<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Test DB connection file exists
echo "1. Checking dbconnection.php...\n";
$dbPath = realpath(dirname(__FILE__) . '/../../dbconnection.php');
if (!file_exists($dbPath)) {
    die("FAIL: dbconnection.php not found at: " . $dbPath);
}
echo "SUCCESS: Found at " . $dbPath . "\n\n";

// 2. Test DB connection
echo "2. Testing database connection...\n";
require $dbPath;
if (!$conn) {
    die("FAIL: No connection object");
}
if ($conn->connect_error) {
    die("FAIL: " . $conn->connect_error);
}
echo "SUCCESS: Connected to database\n\n";

// 3. Test simple query
echo "3. Testing simple query...\n";
$result = $conn->query("SELECT 1");
if (!$result) {
    die("FAIL: " . $conn->error);
}
echo "SUCCESS: Basic query works\n\n";

// 4. Test divisions table exists
echo "4. Checking divisions table...\n";
$result = $conn->query("SHOW TABLES LIKE 'divisions'");
if (!$result || $result->num_rows === 0) {
    die("FAIL: Divisions table not found");
}
echo "SUCCESS: Divisions table exists\n\n";

// 5. Test basic divisions query
echo "5. Testing divisions query...\n";
$query = "SELECT id, code, name FROM divisions WHERE status = 'active' LIMIT 1";
$result = $conn->query($query);
if (!$result) {
    die("FAIL: " . $conn->error . "\nQuery: " . $query);
}

echo "SUCCESS: Found " . $result->num_rows . " divisions\n";
?>
