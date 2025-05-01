<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'debug' => []];

try {
    // 1. Test DB connection file exists
    $dbPath = realpath(dirname(__FILE__) . '/../../dbconnection.php');
    if (!file_exists($dbPath)) {
        throw new Exception('dbconnection.php not found at: ' . $dbPath);
    }
    $response['debug']['db_path'] = $dbPath;

    // 2. Test DB connection
    require $dbPath;
    if (!$conn || $conn->connect_error) {
        throw new Exception('Connection failed: ' . ($conn->connect_error ?? 'No connection object'));
    }
    $response['debug']['connection'] = 'Success';

    // 3. Test required tables
    $tables = ['divisions', 'organizational_codes'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$result || $result->num_rows === 0) {
            throw new Exception("Table '$table' not found");
        }
        $response['debug']['tables'][$table] = 'Exists';
    }

    // 4. Test basic query
    $testQuery = "SELECT id, code, name FROM divisions LIMIT 1";
    $result = $conn->query($testQuery);
    if (!$result) {
        throw new Exception('Test query failed: ' . $conn->error);
    }
    $response['debug']['test_query'] = $testQuery;
    $response['debug']['test_result'] = $result->fetch_assoc() ?? 'No rows';

    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
