<?php
// study/delete_study.php - Safely Delete Study Record
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmtCheck = $conn->prepare("SELECT s.id, st.student_name, st.school_id FROM tb_study s JOIN tb_students st ON s.id_stu = st.ID WHERE s.id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $study = $res->fetch_assoc();
    $stmtCheck->close();

    if ($study) {
        if (!$isAdmin && $user_school_id > 0 && $study['school_id'] != $user_school_id) {
            set_flash('danger', 'Unauthorized action');
            header("Location: list_study.php");
            exit;
        }

        // Delete certificates linked to this study
        $stmtCert = $conn->prepare("DELETE FROM tbl_certi WHERE study_id = ?");
        if ($stmtCert) { $stmtCert->bind_param("i", $id); $stmtCert->execute(); $stmtCert->close(); }

        $stmtDel = $conn->prepare("DELETE FROM tb_study WHERE id = ?");
        if ($stmtDel) {
            $stmtDel->bind_param("i", $id);
            if ($stmtDel->execute()) {
                log_siem_event($conn, get_logged_user(), 'DELETE_STUDY', "Deleted study ID: $id for student: {$study['student_name']}");
                set_flash('success', $lang['deleted_success']);
            }
            $stmtDel->close();
        }
    } else {
        set_flash('danger', 'Record not found');
    }
}

header("Location: list_study.php");
exit;
?>