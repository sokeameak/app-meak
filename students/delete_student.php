<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

// Get the student ID from URL
$student_id = $_GET['id'] ?? '';

if (empty($student_id)) {
    header("Location: list_student.php");
    exit;
}

// Delete from study table
$sql_study = "DELETE FROM tb_study WHERE id_stu = ?";
$stmt_study = $conn->prepare($sql_study);
if ($stmt_study) {
    $stmt_study->bind_param("i", $student_id);
    $stmt_study->execute();
    $stmt_study->close();
}

// Delete from database
$sql = "DELETE FROM tb_students WHERE ID = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $student_id);
    if ($stmt->execute()) {
        log_siem_event($conn, $_SESSION['user'], 'DELETE_STUDENT', "Deleted student ID: $student_id");
    }
    $stmt->close();
}

// Redirect back to students list
header("Location: list_student.php");
exit;
?>
