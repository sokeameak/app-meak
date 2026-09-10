<?php
// students/get_certificate.php - Forward to main view_certificate.php
$id = $_GET['id'] ?? '';
header("Location: ../view_certificate.php?id=" . urlencode($id));
exit;
?>