<?php
// students/edit_student.php - Edit Student Profile
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['edit'] . ' ' . $lang['students'];
$page_subtitle = $lang['app_name'];

$student_id = intval($_GET['id'] ?? 0);
if ($student_id <= 0) {
    set_flash('danger', 'Invalid student ID');
    header("Location: list_student.php");
    exit;
}

// Fetch Student
$stmt = $conn->prepare("SELECT * FROM tb_students WHERE ID = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) {
    set_flash('danger', 'Student not found');
    header("Location: list_student.php");
    exit;
}

// Check School Authorization
if (!$isAdmin && $user_school_id > 0 && $student['school_id'] != $user_school_id) {
    set_flash('danger', 'Unauthorized access to this student');
    header("Location: list_student.php");
    exit;
}

// Fetch Schools
$schools = [];
if ($isAdmin) {
    $sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
    if ($sRes) {
        while ($r = $sRes->fetch_assoc()) $schools[] = $r;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $other = trim($_POST['other'] ?? '');
    $school_id = $isAdmin ? intval($_POST['school_id'] ?? $student['school_id']) : $student['school_id'];
    $photoName = $student['photo'];

    if (empty($student_name) || empty($sex) || empty($dob)) {
        $error = $lang['fill_required'];
    } else {
        try {
            // Check if new photo uploaded
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../uploads/';
                $newPhoto = safe_image_upload($_FILES['photo'], $targetDir, 5);
                if ($newPhoto) {
                    $photoName = $newPhoto;
                }
            }

            $stmtUpdate = $conn->prepare("UPDATE tb_students SET student_name = ?, sex = ?, dob = ?, other = ?, photo = ?, school_id = ? WHERE ID = ?");
            $stmtUpdate->bind_param("sssssii", $student_name, $sex, $dob, $other, $photoName, $school_id, $student_id);
            
            if ($stmtUpdate->execute()) {
                log_siem_event($conn, get_logged_user(), 'UPDATE_STUDENT', "Updated student $student_name (ID: $student_id)");
                set_flash('success', $lang['saved_success']);
                header("Location: list_student.php");
                exit;
            } else {
                $error = "Error updating: " . $stmtUpdate->error;
            }
            $stmtUpdate->close();
        } catch (Exception $e) {
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
            <i class="fa-solid fa-user-pen" style="color: var(--secondary);"></i>
            <span><?php echo $lang['edit']; ?>: <?php echo htmlspecialchars($student['student_name']); ?></span>
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

    <form method="POST" action="edit_student.php?id=<?php echo $student_id; ?>" enctype="multipart/form-data">
        <div class="form-group">
            <label for="student_name"><?php echo $lang['student_name']; ?> <span style="color: var(--danger);">*</span></label>
            <input type="text" id="student_name" name="student_name" class="form-control" value="<?php echo htmlspecialchars($_POST['student_name'] ?? $student['student_name']); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="sex"><?php echo $lang['sex']; ?> <span style="color: var(--danger);">*</span></label>
                <select id="sex" name="sex" class="form-control" required>
                    <option value="Male" <?php echo (($student['sex'] === 'Male') ? 'selected' : ''); ?>><?php echo $lang['male']; ?></option>
                    <option value="Female" <?php echo (($student['sex'] === 'Female') ? 'selected' : ''); ?>><?php echo $lang['female']; ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="dob"><?php echo $lang['dob']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="dob" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob']); ?>" required>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <div class="form-group">
                <label for="school_id"><?php echo $lang['school']; ?></label>
                <select id="school_id" name="school_id" class="form-control">
                    <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($student['school_id'] == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label><?php echo $lang['photo']; ?></label>
            <div style="display: flex; gap: 16px; align-items: center;">
                <?php if (!empty($student['photo'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" alt="Current Photo" style="width: 70px; height: 70px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <?php endif; ?>
                <div style="flex: 1;">
                    <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small style="color: var(--text-muted); font-size: 12px;">ជ្រើសរើសរូបភាពថ្មីដើម្បីជំនួសរូបភាពចាស់ (JPG, PNG, WebP)</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="other"><?php echo $lang['other_notes']; ?></label>
            <textarea id="other" name="other" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['other'] ?? $student['other']); ?></textarea>
        </div>

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
