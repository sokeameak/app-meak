<?php
header('Content-Type: application/json; charset=utf-8');
$conn = new mysqli('localhost', 'root', '', 'mk');
$tables = ['tbl_certi', 'tb_course', 'tb_invoices', 'tb_schools', 'tb_siem_logs'];
$schemas = [];

foreach ($tables as $t) {
    $res = $conn->query("DESCRIBE `$t`");
    $cols = [];
    while ($row = $res->fetch_assoc()) {
        $cols[] = $row;
    }
    $schemas[$t] = $cols;
}

echo json_encode($schemas, JSON_PRETTY_PRINT);
