<?php
// att/create_att.php - Record Student Attendance Safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';

// Require login
if (!is_logged_in()) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = intval($_POST['student_id'] ?? 0);
    $status = $_POST['status'] ?? '0'; // 0: Absent, 1: Present, 2: Permission

    if ($studentId > 0) {
        $success = false;
        $errorMsg = '';

        try {
            $stmt = $conn->prepare("INSERT INTO tbl_att (id_stu, date, status) VALUES (?, NOW(), ?)");
            if ($stmt) {
                $stmt->bind_param("is", $studentId, $status);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errorMsg = $stmt->error;
                }
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            // Auto-repair missing AUTO_INCREMENT and retry once
            ensure_auto_increment($conn, 'tbl_att', 'id');
            $stmt = $conn->prepare("INSERT INTO tbl_att (id_stu, date, status) VALUES (?, NOW(), ?)");
            if ($stmt) {
                $stmt->bind_param("is", $studentId, $status);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errorMsg = $stmt->error;
                }
                $stmt->close();
            }
        }

        if ($success) {
            $statusText = ($status === '0') ? 'Absent' : (($status === '1') ? 'Present' : 'Permission');
            log_siem_event($conn, get_logged_user(), 'RECORD_ATTENDANCE', "Recorded {$statusText} for student ID: {$studentId}");
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $lang['attendance_recorded']]);
                exit;
            }
            
            set_flash('success', $lang['attendance_recorded']);
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            set_flash('danger', 'Error: ' . $errorMsg);
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
            exit;
        }
        set_flash('danger', 'Invalid student ID');
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';
header("Location: " . $referer);
exit;
?>
