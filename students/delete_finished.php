<?php
// students/delete_finished.php - Delete Finished Study Record
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$study_id = intval($_GET['id'] ?? 0);

if ($study_id > 0) {
    // Check permission
    $stmtCheck = $conn->prepare("SELECT s.id, st.student_name, st.school_id FROM tb_study s JOIN tb_students st ON s.id_stu = st.ID WHERE s.id = ?");
    $stmtCheck->bind_param("i", $study_id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $study = $res->fetch_assoc();
    $stmtCheck->close();

    if ($study) {
        if (!$isAdmin && $user_school_id > 0 && $study['school_id'] != $user_school_id) {
            set_flash('danger', 'Unauthorized action');
            header("Location: finished.php");
            exit;
        }

        // Delete from tbl_certi first
        $stmtCert = $conn->prepare("DELETE FROM tbl_certi WHERE study_id = ?");
        if ($stmtCert) { $stmtCert->bind_param("i", $study_id); $stmtCert->execute(); $stmtCert->close(); }

        // Delete from tb_study
        $stmtDel = $conn->prepare("DELETE FROM tb_study WHERE id = ?");
        if ($stmtDel) {
            $stmtDel->bind_param("i", $study_id);
            if ($stmtDel->execute()) {
                log_siem_event($conn, get_logged_user(), 'DELETE_FINISHED_STUDY', "Deleted study ID: $study_id for student: {$study['student_name']}");
                set_flash('success', $lang['deleted_success']);
            }
            $stmtDel->close();
        }
    } else {
        set_flash('danger', 'Record not found');
    }
}

header("Location: finished.php");
exit;
?>