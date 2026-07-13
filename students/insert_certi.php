<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ids'])) {
    $ids = $_POST['ids'];

    // Validate IDs to ensure they are integers
    $ids = array_map('intval', $ids);
    $ids = implode(',', $ids);

    // Insert data into tbl_certi
    $sql = "INSERT INTO tbl_certi (study_id, created_at) SELECT id, NOW() FROM tb_study WHERE id IN ($ids)";

    if ($conn->query($sql) === TRUE) {
        echo "New records created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "No IDs selected.";
}

// Redirect back to finished students page
header("Location: finished_student.php");
exit;

?>