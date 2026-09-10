<?php
header('Content-Type: application/json; charset=utf-8');
$conn = new mysqli('localhost', 'root', '', 'mk');
$res = $conn->query("SELECT * FROM tb_siem_logs WHERE id = 166");
echo json_encode($res->fetch_assoc(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
