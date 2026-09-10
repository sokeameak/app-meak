<?php
// courses/delete_course.php - Safely Delete Course
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Check if course is used in studies
    $stmtCount = $conn->prepare("SELECT COUNT(*) as count FROM tb_study WHERE id_code = ?");
    $stmtCount->bind_param("i", $id);
    $stmtCount->execute();
    $studyCount = $stmtCount->get_result()->fetch_assoc()['count'] ?? 0;
    $stmtCount->close();

    if ($studyCount > 0) {
        set_flash('danger', "មិនអាចលុបវគ្គសិក្សានេះបានទេ ព្រោះមានសិស្សកំពុងសិក្សាចំនួន $studyCount នាក់!");
    } else {
        $stmtDel = $conn->prepare("DELETE FROM tb_course WHERE ID = ?");
        if ($stmtDel) {
            $stmtDel->bind_param("i", $id);
            if ($stmtDel->execute()) {
                log_siem_event($conn, get_logged_user(), 'DELETE_COURSE', "Deleted course ID: $id");
                set_flash('success', $lang['deleted_success']);
            }
            $stmtDel->close();
        }
    }
}

header("Location: list_course.php");
exit;
?>
