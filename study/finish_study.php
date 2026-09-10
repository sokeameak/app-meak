<?php
// study/finish_study.php - Mark Study Record as Finished
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

        $stmtUp = $conn->prepare("UPDATE tb_study SET end_date = CURDATE() WHERE id = ?");
        if ($stmtUp) {
            $stmtUp->bind_param("i", $id);
            if ($stmtUp->execute()) {
                log_siem_event($conn, get_logged_user(), 'FINISH_STUDY', "Marked study ID: $id as finished for student: {$study['student_name']}");
                set_flash('success', $selected_lang === 'kh' ? 'បានបញ្ចប់ការសិក្សាដោយជោគជ័យ!' : 'Study marked as finished!');
            }
            $stmtUp->close();
        }
    } else {
        set_flash('danger', 'Record not found');
    }
}

header("Location: list_study.php");
exit;
?>