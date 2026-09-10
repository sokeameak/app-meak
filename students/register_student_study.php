<?php
// students/register_student_study.php - Register Student with Initial Study Record
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['register_study'];
$page_subtitle = $lang['app_name'] . ' - ' . $lang['add_student'];

$error = '';
$success = '';

// Fetch Courses
$courses = [];
$cRes = $conn->query("SELECT ID, CourseID, Course FROM tb_course ORDER BY Course ASC");
if ($cRes) {
    while ($row = $cRes->fetch_assoc()) $courses[] = $row;
}

// Fetch Time Slots
$times = [];
$tRes = $conn->query("SELECT id, time FROM tb_time ORDER BY id ASC");
if ($tRes) {
    while ($row = $tRes->fetch_assoc()) $times[] = $row;
}

// Fetch Schools
$schools = [];
$sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
if ($sRes) {
    while ($row = $sRes->fetch_assoc()) $schools[] = $row;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $other = trim($_POST['other'] ?? '');
    $school_id = $isAdmin ? intval($_POST['school_id'] ?? 1) : ($user_school_id ?: 1);

    $id_code = intval($_POST['id_code'] ?? 0);
    $id_time = intval($_POST['id_time'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Validate Required Fields
    if (empty($student_name) || empty($sex) || empty($dob) || empty($id_code) || empty($id_time) || empty($start_date) || empty($end_date)) {
        $error = $lang['fill_required'];
    } else {
        $conn->begin_transaction();
        try {
            // Handle Photo Upload
            $photoName = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../uploads/';
                $photoName = safe_image_upload($_FILES['photo'], $targetDir, 5);
            }

            // 1. Insert Student
            $stmt = $conn->prepare("INSERT INTO tb_students (student_name, sex, dob, other, photo, school_id) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("sssssi", $student_name, $sex, $dob, $other, $photoName, $school_id);
            if (!$stmt->execute()) {
                throw new Exception("Error saving student: " . $stmt->error);
            }
            $student_id = $conn->insert_id;
            $stmt->close();

            // 2. Insert Study Record
            $stmtStudy = $conn->prepare("INSERT INTO tb_study (id_stu, id_code, id_time, price, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmtStudy) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmtStudy->bind_param("iiidss", $student_id, $id_code, $id_time, $price, $start_date, $end_date);
            if (!$stmtStudy->execute()) {
                throw new Exception("Error saving study record: " . $stmtStudy->error);
            }
            $stmtStudy->close();

            // 3. Create Initial Invoice Record
            $timeName = '';
            foreach ($times as $t) {
                if ($t['id'] == $id_time) { $timeName = $t['time']; break; }
            }
            $courseName = '';
            foreach ($courses as $c) {
                if ($c['ID'] == $id_code) { $courseName = $c['Course']; break; }
            }

            $stmtInv = $conn->prepare("INSERT INTO tb_invoices (student_id, student_name, description, amount, status, study_time, school_id) VALUES (?, ?, ?, ?, 'Unpaid', ?, ?)");
            if ($stmtInv) {
                $invDesc = "Course: " . $courseName;
                $stmtInv->bind_param("issdsi", $student_id, $student_name, $invDesc, $price, $timeName, $school_id);
                $stmtInv->execute();
                $stmtInv->close();
            }

            $conn->commit();

            log_siem_event($conn, get_logged_user(), 'REGISTER_STUDENT', "Registered student $student_name (ID: $student_id) for course ID: $id_code");
            set_flash('success', $lang['saved_success']);
            header("Location: list_student.php");
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

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-user-plus" style="color: var(--secondary);"></i>
            <span><?php echo $lang['register_study']; ?></span>
        </div>
        <a href="list_student.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $lang['back']; ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="register_student_study.php" enctype="multipart/form-data">
        
        <!-- Section 1: Student Information -->
        <h3 style="font-size: 16px; font-weight: 700; color: var(--primary); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-id-card"></i>
            <span>១. ព័ត៌មានផ្ទាល់ខ្លួនសិស្ស (Student Information)</span>
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label for="student_name"><?php echo $lang['student_name']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="text" id="student_name" name="student_name" class="form-control" value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>" required placeholder="ឈ្មោះសិស្ស...">
            </div>

            <div class="form-group">
                <label for="sex"><?php echo $lang['sex']; ?> <span style="color: var(--danger);">*</span></label>
                <select id="sex" name="sex" class="form-control" required>
                    <option value=""><?php echo $selected_lang === 'kh' ? '-- ជ្រើសរើសភេទ --' : '-- Select Gender --'; ?></option>
                    <option value="Male" <?php echo (($_POST['sex'] ?? '') === 'Male') ? 'selected' : ''; ?>><?php echo $lang['male']; ?></option>
                    <option value="Female" <?php echo (($_POST['sex'] ?? '') === 'Female') ? 'selected' : ''; ?>><?php echo $lang['female']; ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="dob"><?php echo $lang['dob']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="dob" name="dob" class="form-control" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>" required>
            </div>

            <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label for="school_id"><?php echo $lang['school']; ?> <span style="color: var(--danger);">*</span></label>
                    <select id="school_id" name="school_id" class="form-control" required>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo (($_POST['school_id'] ?? $user_school_id) == $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group" style="grid-column: 1/-1;">
                <label for="photo"><?php echo $lang['photo']; ?> (JPG, PNG, WebP - Max 5MB)</label>
                <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="form-group" style="grid-column: 1/-1;">
                <label for="other"><?php echo $lang['other_notes']; ?></label>
                <textarea id="other" name="other" class="form-control" rows="2" placeholder="លេខទូរស័ព្ទ ឬព័ត៌មានបន្ថែម..."><?php echo htmlspecialchars($_POST['other'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Section 2: Study Enrollment -->
        <h3 style="font-size: 16px; font-weight: 700; color: var(--primary); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-book-open"></i>
            <span>២. ព័ត៌មានវគ្គសិក្សា (Study Enrollment)</span>
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
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

            <div class="form-group">
                <label for="start_date"><?php echo $lang['start_date']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="form-group">
                <label for="end_date"><?php echo $lang['end_date']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+3 months'))); ?>" required>
            </div>
        </div>

        <!-- Submit Button -->
        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
            <a href="list_student.php" class="btn btn-light">
                <?php echo $lang['cancel']; ?>
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>