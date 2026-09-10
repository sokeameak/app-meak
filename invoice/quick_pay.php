<?php
// invoice/quick_pay.php - Quick Student Payment & Invoice API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']) || isset($_GET['ajax']);

// -------------------------------------------------------------
// GET: Fetch student details, study enrollments & invoices
// -------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && (isset($_GET['student_id']) || isset($_GET['name']))) {
    header('Content-Type: application/json; charset=utf-8');
    $student_id = intval($_GET['student_id'] ?? 0);
    $student_name = trim($_GET['name'] ?? '');

    if ($student_id <= 0 && empty($student_name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Student ID or Name']);
        exit;
    }

    // 1. Fetch Student
    if ($student_id > 0) {
        $stmt = $conn->prepare("SELECT s.*, sch.school_name, sch.school_name_kh 
                                FROM tb_students s 
                                LEFT JOIN tb_schools sch ON s.school_id = sch.id 
                                WHERE s.id = ?");
        $stmt->bind_param("i", $student_id);
    } else {
        $stmt = $conn->prepare("SELECT s.*, sch.school_name, sch.school_name_kh 
                                FROM tb_students s 
                                LEFT JOIN tb_schools sch ON s.school_id = sch.id 
                                WHERE s.student_name = ? LIMIT 1");
        $stmt->bind_param("s", $student_name);
    }
    $stmt->execute();
    $sRes = $stmt->get_result();
    $student = $sRes ? $sRes->fetch_assoc() : null;
    $stmt->close();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    $student_id = intval($student['id']);

    // Check School Access
    if (!$isAdmin && $user_school_id > 0 && intval($student['school_id']) !== $user_school_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to student from another branch']);
        exit;
    }

    // 2. Fetch Latest / Active Study Info
    $studyStmt = $conn->prepare("SELECT st.*, c.Course, t.time,
                                 (st.end_date > CURDATE()) as is_active
                                 FROM tb_study st
                                 JOIN tb_course c ON st.id_code = c.ID
                                 JOIN tb_time t ON st.id_time = t.id
                                 WHERE st.id_stu = ?
                                 ORDER BY (st.end_date > CURDATE()) DESC, st.id DESC");
    $studyStmt->bind_param("i", $student_id);
    $studyStmt->execute();
    $studyRes = $studyStmt->get_result();
    $studies = [];
    $total_study_price = 0.0;
    while ($stRow = $studyRes->fetch_assoc()) {
        $total_study_price += floatval($stRow['price']);
        $studies[] = $stRow;
    }
    $studyStmt->close();

    $primaryStudy = !empty($studies) ? $studies[0] : null;

    // 3. Fetch Invoices for this student
    $invStmt = $conn->prepare("SELECT * FROM tb_invoices 
                               WHERE (student_id = ? OR student_name = ?) 
                               ORDER BY id DESC");
    $invStmt->bind_param("is", $student_id, $student['student_name']);
    $invStmt->execute();
    $invRes = $invStmt->get_result();
    $invoices = [];
    $total_paid = 0.0;
    $unpaid_invoices = [];

    while ($inv = $invRes->fetch_assoc()) {
        if (strcasecmp($inv['status'], 'Paid') === 0) {
            $total_paid += floatval($inv['amount']);
        } else {
            $unpaid_invoices[] = $inv;
        }
        $invoices[] = $inv;
    }
    $invStmt->close();

    $remaining_balance = max(0, $total_study_price - $total_paid);

    echo json_encode([
        'success' => true,
        'student' => [
            'id' => intval($student['id']),
            'student_name' => $student['student_name'],
            'photo' => $student['photo'] ?: '',
            'sex' => $student['sex'],
            'dob' => $student['dob'],
            'school_id' => intval($student['school_id']),
            'school_name' => $student['school_name_kh'] ?: $student['school_name']
        ],
        'study' => $primaryStudy ? [
            'id' => intval($primaryStudy['id']),
            'course' => $primaryStudy['Course'],
            'time' => $primaryStudy['time'],
            'price' => floatval($primaryStudy['price']),
            'start_date' => $primaryStudy['start_date'],
            'end_date' => $primaryStudy['end_date'],
            'is_active' => (bool)$primaryStudy['is_active']
        ] : null,
        'financials' => [
            'total_price' => $total_study_price,
            'total_paid' => $total_paid,
            'remain' => $remaining_balance
        ],
        'invoices' => $invoices,
        'unpaid_invoices' => $unpaid_invoices
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// POST: Process Payment (Pay Invoice OR Create New Payment)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create_payment';

    // A. Pay Existing Unpaid Invoice
    if ($action === 'pay_invoice') {
        $invoice_id = intval($_POST['invoice_id'] ?? 0);
        if ($invoice_id <= 0) {
            $msg = 'Invalid Invoice ID';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            set_flash('danger', $msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? base_url('dashboard.php')));
            exit;
        }

        $stmt = $conn->prepare("UPDATE tb_invoices SET status = 'Paid' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $invoice_id);
            $stmt->execute();
            $stmt->close();

            log_siem_event($conn, get_logged_user(), 'PAY_INVOICE', "Marked invoice #$invoice_id as Paid via Quick Pay");

            $successMsg = "វិក្កយបត្រ #$invoice_id ត្រូវបានទូទាត់រួចរាល់ (Paid)!";
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => $successMsg]);
                exit;
            }
            set_flash('success', $successMsg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? base_url('dashboard.php')));
            exit;
        }
    }

    // B. Create New Payment / Invoice
    if ($action === 'create_payment') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $student_name = trim($_POST['student_name'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? 'បង់ថ្លៃសិក្សា');
        $study_time = trim($_POST['study_time'] ?? '');
        $status = in_array($_POST['status'] ?? 'Paid', ['Paid', 'Unpaid', 'Pending']) ? $_POST['status'] : 'Paid';
        $school_id = intval($_POST['school_id'] ?? ($user_school_id ?: 1));

        if (empty($student_name) && $student_id > 0) {
            $sRes = $conn->query("SELECT student_name, school_id FROM tb_students WHERE id = $student_id");
            if ($sRow = $sRes->fetch_assoc()) {
                $student_name = $sRow['student_name'];
                if ($school_id <= 0) $school_id = intval($sRow['school_id']);
            }
        }

        if (empty($student_name) || $amount <= 0) {
            $msg = 'សូមបញ្ចូលចំនួនទឹកប្រាក់ដែលត្រូវបង់ឱ្យបានត្រឹមត្រូវ!';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            set_flash('danger', $msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? base_url('dashboard.php')));
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO tb_invoices (student_id, student_name, description, amount, status, study_time, school_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issdssi", $student_id, $student_name, $description, $amount, $status, $study_time, $school_id);
            if ($stmt->execute()) {
                $newInvId = $conn->insert_id;
                log_siem_event($conn, get_logged_user(), 'QUICK_PAYMENT', "Recorded payment for $student_name: \$$amount ($status) - Invoice #$newInvId");

                $successMsg = "បានកត់ត្រាការបង់ប្រាក់សម្រាប់សិស្ស $student_name ចំនួន $" . number_format($amount, 2) . " រួចរាល់!";
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'message' => $successMsg,
                        'invoice_id' => $newInvId,
                        'amount' => $amount,
                        'student_name' => $student_name
                    ]);
                    exit;
                }
                set_flash('success', $successMsg);
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? base_url('dashboard.php')));
                exit;
            } else {
                $errMsg = "កំហុសក្នុងការរក្សាទុក៖ " . $stmt->error;
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $errMsg]);
                    exit;
                }
                set_flash('danger', $errMsg);
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? base_url('dashboard.php')));
                exit;
            }
            $stmt->close();
        }
    }
}

// If no matching request
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}
header("Location: " . base_url('dashboard.php'));
exit;
