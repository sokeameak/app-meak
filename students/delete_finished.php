<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM tb_study WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    log_siem_event($conn, $_SESSION['user'], 'DELETE_FINISHED_STUDENT', "Deleted finished study ID: $id");
    $stmt->close();
}

header("Location: finished_student.php");
exit;
?>