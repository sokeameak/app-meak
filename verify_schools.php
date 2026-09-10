<?php
header('Content-Type: application/json; charset=utf-8');
$conn = new mysqli('localhost', 'root', '', 'mk');
$res = $conn->query("SELECT * FROM tb_schools");
echo json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
