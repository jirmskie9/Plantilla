<?php
header('Content-Type: application/json');

// Create logs directory if not exists
if (!file_exists('../../logs')) {
    mkdir('../../logs', 0755, true);
}

$logFile = '../../logs/upload_test.log';
file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Test upload started\n", FILE_APPEND);

// Verify upload directory
$uploadDir = '../../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Test file upload
$testFile = $uploadDir . 'test_' . time() . '.txt';
file_put_contents($testFile, "Test upload file created at " . date('Y-m-d H:i:s'));

if (file_exists($testFile)) {
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Test file created successfully: $testFile\n", FILE_APPEND);
    echo json_encode([
        'success' => true,
        'message' => 'File upload test successful',
        'file_path' => $testFile
    ]);
} else {
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Failed to create test file\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create test file'
    ]);
}
