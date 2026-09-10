<?php
// students/insert_certi.php - Issue Certificates for Finished Studies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
    $studyIds = array_map('intval', $_POST['ids']);
    $studyIds = array_filter($studyIds, function($id) { return $id > 0; });

    if (!empty($studyIds)) {
        $count = 0;
        $stmt = $conn->prepare("INSERT INTO tbl_certi (study_id, created_at) SELECT id, NOW() FROM tb_study WHERE id = ? AND NOT EXISTS (SELECT 1 FROM tbl_certi WHERE study_id = tb_study.id)");
        
        if ($stmt) {
            foreach ($studyIds as $sid) {
                $stmt->bind_param("i", $sid);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $count++;
                }
            }
            $stmt->close();
        }

        log_siem_event($conn, get_logged_user(), 'ISSUE_CERTIFICATE', "Issued certificates for $count studies");
        set_flash('success', "បានចេញវិញ្ញាបនបត្រចំនួន $count ដោយជោគជ័យ!");
    } else {
        set_flash('warning', 'សូមជ្រើសរើសសិស្សយ៉ាងហោចណាស់ម្នាក់!');
    }
} else {
    set_flash('warning', 'មិនមានទិន្នន័យត្រូវបានជ្រើសរើសឡើយ!');
}

header("Location: finished.php");
exit;
?>