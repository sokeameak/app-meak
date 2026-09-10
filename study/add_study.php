<?php
// study/add_study.php - Add Study Enrollment for Existing Student
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $selected_lang === 'kh' ? 'ចុះឈ្មោះវគ្គសិក្សាថ្មី' : 'New Study Enrollment';
$page_subtitle = $lang['app_name'];

$preselected_stu = intval($_GET['id_stu'] ?? 0);

// Fetch Students
$studentWhere = "";
if (!$isAdmin && $user_school_id > 0) {
    $studentWhere = " WHERE school_id = " . intval($user_school_id);
}
$students = [];
$stuRes = $conn->query("SELECT id, id as ID, student_name, sex, school_id FROM tb_students $studentWhere ORDER BY student_name ASC");
if ($stuRes) {
    while ($r = $stuRes->fetch_assoc()) $students[] = $r;
}

// Fetch Courses
$courses = [];
$cRes = $conn->query("SELECT ID, CourseID, Course FROM tb_course ORDER BY Course ASC");
if ($cRes) {
    while ($r = $cRes->fetch_assoc()) $courses[] = $r;
}

// Fetch Times
$times = [];
$tRes = $conn->query("SELECT id, time FROM tb_time ORDER BY id ASC");
if ($tRes) {
    while ($r = $tRes->fetch_assoc()) $times[] = $r;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_stu = intval($_POST['id_stu'] ?? 0);
    $id_code = intval($_POST['id_code'] ?? 0);
    $id_time = intval($_POST['id_time'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if (empty($id_stu) || empty($id_code) || empty($id_time) || empty($start_date) || empty($end_date)) {
        $error = $lang['fill_required'];
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO tb_study (id_stu, id_code, id_time, price, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidss", $id_stu, $id_code, $id_time, $price, $start_date, $end_date);
            if (!$stmt->execute()) {
                throw new Exception("Error adding study: " . $stmt->error);
            }
            $stmt->close();

            // Create Invoice
            $studentName = '';
            $schoolId = 1;
            foreach ($students as $s) {
                $currentStuId = $s['id'] ?? ($s['ID'] ?? 0);
                if ($currentStuId == $id_stu) {
                    $studentName = $s['student_name'];
                    $schoolId = $s['school_id'];
                    break;
                }
            }

            $courseName = '';
            foreach ($courses as $c) {
                if ($c['ID'] == $id_code) { $courseName = $c['Course']; break; }
            }

            $timeName = '';
            foreach ($times as $t) {
                if ($t['id'] == $id_time) { $timeName = $t['time']; break; }
            }

            $stmtInv = $conn->prepare("INSERT INTO tb_invoices (student_id, student_name, description, amount, status, study_time, school_id) VALUES (?, ?, ?, ?, 'Unpaid', ?, ?)");
            if ($stmtInv) {
                $invDesc = "Course: " . $courseName;
                $stmtInv->bind_param("issdsi", $id_stu, $studentName, $invDesc, $price, $timeName, $schoolId);
                $stmtInv->execute();
                $stmtInv->close();
            }

            $conn->commit();

            log_siem_event($conn, get_logged_user(), 'ADD_STUDY', "Added study enrollment for student $studentName (ID: $id_stu) in course $courseName");
            set_flash('success', $lang['saved_success']);
            header("Location: list_study.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-book-medical" style="color: var(--secondary);"></i>
            <span><?php echo $page_title; ?></span>
        </div>
        <a href="list_study.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $lang['back']; ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_study.php">
        <div class="form-group">
            <label for="id_stu"><?php echo $lang['student_name']; ?> <span style="color: var(--danger);">*</span></label>
            <select id="id_stu" name="id_stu" class="form-control" required>
                <option value=""><?php echo $selected_lang === 'kh' ? '-- ជ្រើសរើសសិស្ស --' : '-- Select Student --'; ?></option>
                <?php foreach ($students as $s): 
                    $currentStuId = $s['id'] ?? ($s['ID'] ?? 0);
                ?>
                    <option value="<?php echo $currentStuId; ?>" <?php echo (($preselected_stu == $currentStuId) || (($_POST['id_stu'] ?? '') == $currentStuId)) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['student_name'] . ' (' . khmer_gender($s['sex']) . ' - ID: #' . $currentStuId . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_code"><?php echo $lang['course_name']; ?> <span style="color: var(--danger);">*</span></label>
            <select id="id_code" name="id_code" class="form-control" required>
                <option value=""><?php echo $selected_lang === 'kh' ? '-- ជ្រើសរើសវគ្គសិក្សា --' : '-- Select Course --'; ?></option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c['ID']; ?>" <?php echo (($_POST['id_code'] ?? '') == $c['ID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['Course'] . ' (' . $c['CourseID'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_time"><?php echo $lang['time_slot']; ?> <span style="color: var(--danger);">*</span></label>
            <select id="id_time" name="id_time" class="form-control" required>
                <option value=""><?php echo $selected_lang === 'kh' ? '-- ជ្រើសរើសម៉ោង --' : '-- Select Time --'; ?></option>
                <?php foreach ($times as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo (($_POST['id_time'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['time']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="price"><?php echo $lang['price']; ?> ($) <span style="color: var(--danger);">*</span></label>
            <input type="number" step="0.01" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($_POST['price'] ?? '0.00'); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="start_date"><?php echo $lang['start_date']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="form-group">
                <label for="end_date"><?php echo $lang['end_date']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+3 months'))); ?>" required>
            </div>
        </div>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
            <a href="list_study.php" class="btn btn-light">
                <?php echo $lang['cancel']; ?>
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
