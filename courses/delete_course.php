<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

// Get the CourseID from URL
$CourseID = $_GET['id'] ?? '';

if (empty($CourseID)) {
    header("Location: list_course.php");
    exit;
}

// Delete from database
$sql = "DELETE FROM tb_course WHERE CourseID = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $CourseID);
    if ($stmt->execute()) {
        log_siem_event($conn, $_SESSION['user'], 'DELETE_COURSE', "Deleted course ID: $CourseID");
    }
    $stmt->close();
}

// Redirect back to courses list
header("Location: list_course.php");
exit;
?>
