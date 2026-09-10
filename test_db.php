<?php
header('Content-Type: application/json; charset=utf-8');
$conn = new mysqli('localhost', 'root', '', '');
if ($conn->connect_error) {
    echo json_encode(['error' => $conn->connect_error]);
    exit;
}

$res = $conn->query("SHOW DATABASES");
$databases = [];
while ($row = $res->fetch_row()) {
    $databases[] = $row[0];
}

$details = [];
foreach (['mk', 'meakncva_mk'] as $db) {
    if (in_array($db, $databases)) {
        $conn->select_db($db);
        $tRes = $conn->query("SHOW TABLES");
        $tables = [];
        while ($tRow = $tRes->fetch_row()) {
            $table = $tRow[0];
            $cRes = $conn->query("SELECT COUNT(*) FROM `$table`");
            $count = $cRes ? $cRes->fetch_row()[0] : 0;
            $tables[$table] = $count;
        }
        $details[$db] = $tables;
    }
}

echo json_encode(['databases' => $databases, 'details' => $details], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
