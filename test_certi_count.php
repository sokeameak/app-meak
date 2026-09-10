<?php
header('Content-Type: application/json; charset=utf-8');
$conn = new mysqli('localhost', 'root', '', 'mk');
$res = $conn->query("SELECT COUNT(*) FROM tbl_certi WHERE id_student > 0");
$count = $res->fetch_row()[0];
echo json_encode(['count_with_id_student' => $count]);
