<?php
header('Content-Type: application/json');
require '../../dbconnection.php';

$query = "SELECT d.id, d.code, d.name, 
          oc.code AS office_code, 
          org.code AS org_code
          FROM divisions d
          LEFT JOIN organizational_codes oc ON d.id = oc.division_id
          LEFT JOIN organizational_codes org ON d.id = org.division_id AND org.type = 'organization'
          WHERE d.status = 'active'
          ORDER BY d.name";

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}

$divisions = [];
while ($row = $result->fetch_assoc()) {
    $divisions[] = [
        'id' => $row['id'],
        'code' => $row['code'],
        'name' => $row['name'],
        'office_code' => $row['office_code'],
        'org_code' => $row['org_code']
    ];
}

echo json_encode($divisions);
?>
