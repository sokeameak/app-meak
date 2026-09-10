<?php
header('Content-Type: application/json; charset=utf-8');
$conn = new mysqli('localhost', 'root', '', 'mk');
$res = $conn->query("SELECT * FROM tb_study WHERE id = 43");
echo json_encode($res->fetch_assoc(), JSON_PRETTY_PRINT);
