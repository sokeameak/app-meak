<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

// Get the ID from URL
$id = $_GET['id'] ?? '';

if (empty($id)) {
    header("Location: time/grades.php");
    exit;
}

// Delete from database
$sql = "DELETE FROM tb_time WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        log_siem_event($conn, $_SESSION['user'], 'DELETE_TIME', "Deleted time slot ID: $id");
    }
    $stmt->close();
}

// Redirect back to grades page
header("Location: grades.php");
exit;
?>
