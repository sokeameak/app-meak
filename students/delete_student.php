<?php
// students/delete_student.php - Safely Delete Student Record
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$student_id = intval($_GET['id'] ?? 0);

if ($student_id > 0) {
    // Check school permission
    $stmtCheck = $conn->prepare("SELECT student_name, school_id, photo FROM tb_students WHERE ID = ?");
    $stmtCheck->bind_param("i", $student_id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $student = $res->fetch_assoc();
    $stmtCheck->close();

    if ($student) {
        if (!$isAdmin && $user_school_id > 0 && $student['school_id'] != $user_school_id) {
            set_flash('danger', 'Unauthorized action');
            header("Location: list_student.php");
            exit;
        }

        $conn->begin_transaction();
        try {
            // Delete from tbl_att
            $stmtAtt = $conn->prepare("DELETE FROM tbl_att WHERE id_stu = ?");
            if ($stmtAtt) { $stmtAtt->bind_param("i", $student_id); $stmtAtt->execute(); $stmtAtt->close(); }

            // Delete certificates associated with this student's studies
            $conn->query("DELETE cert FROM tbl_certi cert JOIN tb_study s ON cert.study_id = s.id WHERE s.id_stu = " . $student_id);

            // Delete from tb_study
            $stmtStudy = $conn->prepare("DELETE FROM tb_study WHERE id_stu = ?");
            if ($stmtStudy) { $stmtStudy->bind_param("i", $student_id); $stmtStudy->execute(); $stmtStudy->close(); }

            // Delete from tb_students
            $stmtDel = $conn->prepare("DELETE FROM tb_students WHERE ID = ?");
            if ($stmtDel) { $stmtDel->bind_param("i", $student_id); $stmtDel->execute(); $stmtDel->close(); }

            $conn->commit();

            // Clean up photo if exists
            if (!empty($student['photo'])) {
                $photoPath = __DIR__ . '/../uploads/' . $student['photo'];
                if (file_exists($photoPath) && is_file($photoPath)) {
                    @unlink($photoPath);
                }
            }

            log_siem_event($conn, get_logged_user(), 'DELETE_STUDENT', "Deleted student: {$student['student_name']} (ID: $student_id)");
            set_flash('success', $lang['deleted_success']);
        } catch (Exception $e) {
            $conn->rollback();
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
    } else {
        set_flash('danger', 'Student not found');
    }
}

header("Location: list_student.php");
exit;
?>
