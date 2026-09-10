<?php
header('Content-Type: application/json; charset=utf-8');
$conn = new mysqli('localhost', 'root', '', 'mk');

$out = [];
$res = $conn->query("SELECT * FROM tbl_certi LIMIT 3");
$out['tbl_certi_sample'] = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$res = $conn->query("SELECT * FROM tb_schools");
$out['tb_schools'] = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$res = $conn->query("SELECT * FROM tb_course");
$out['tb_course'] = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$res = $conn->query("SELECT * FROM tb_invoices WHERE id IN (19, 20, 21)");
$out['tb_invoices_sample'] = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
