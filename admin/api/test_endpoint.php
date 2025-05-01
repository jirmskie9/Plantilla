<?php
header('Content-Type: application/json');
require '../../dbconnection.php';

echo json_encode(['status' => 'success', 'message' => 'API endpoint is working']);
