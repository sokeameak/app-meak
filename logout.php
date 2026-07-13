<?php
session_start();
include 'db_connect.php';
if (isset($_SESSION['user'])) {
    log_siem_event($conn, $_SESSION['user'], 'LOGOUT', 'User logged out');
}
session_destroy();
header("Location: index.php");
exit;
?>