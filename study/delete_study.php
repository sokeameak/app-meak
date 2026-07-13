<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prepare delete statement
    $stmt = $conn->prepare("DELETE FROM tb_study WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        log_siem_event($conn, $_SESSION['user'], 'DELETE_STUDY', "Deleted study record ID: $id");
        $message = "Study record deleted successfully!";
    } else {
        $message = "Error deleting record: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect back to list
    header("Location: list_study.php?message=" . urlencode($message));
    exit;
} else {
    header("Location: list_study.php");
    exit;
}
?>