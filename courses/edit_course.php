<?php
// courses/edit_course.php - Edit Course
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$page_title = $lang['edit'] . ' ' . $lang['course'];
$page_subtitle = $lang['app_name'];

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid course ID');
    header("Location: list_course.php");
    exit;
}

// Fetch Course
$stmt = $conn->prepare("SELECT * FROM tb_course WHERE ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$course = $res->fetch_assoc();
$stmt->close();

if (!$course) {
    set_flash('danger', 'Course not found');
    header("Location: list_course.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $CourseID = trim($_POST['CourseID'] ?? '');
    $Course = trim($_POST['Course'] ?? '');
    $Note = trim($_POST['Note'] ?? '');

    if (empty($CourseID) || empty($Course)) {
        $error = $lang['fill_required'];
    } else {
        // Check uniqueness
        $stmtCheck = $conn->prepare("SELECT ID FROM tb_course WHERE CourseID = ? AND ID != ?");
        $stmtCheck->bind_param("si", $CourseID, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $error = "លេខកូដវគ្គសិក្សា ($CourseID) ត្រូវបានប្រើប្រាស់រួចហើយ!";
        } else {
            $stmtUp = $conn->prepare("UPDATE tb_course SET CourseID = ?, Course = ?, Note = ? WHERE ID = ?");
            $stmtUp->bind_param("sssi", $CourseID, $Course, $Note, $id);
            if ($stmtUp->execute()) {
                log_siem_event($conn, get_logged_user(), 'UPDATE_COURSE', "Updated course ID: $id ($Course)");
                set_flash('success', $lang['saved_success']);
                header("Location: list_course.php");
                exit;
            } else {
                $error = "Error: " . $stmtUp->error;
            }
            $stmtUp->close();
        }
        $stmtCheck->close();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-pen-to-square" style="color: var(--secondary);"></i>
            <span><?php echo $page_title; ?></span>
        </div>
        <a href="list_course.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $lang['back']; ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_course.php?id=<?php echo $id; ?>">
        <div class="form-group">
            <label for="CourseID">Course ID <span style="color: var(--danger);">*</span></label>
            <input type="text" id="CourseID" name="CourseID" class="form-control" value="<?php echo htmlspecialchars($_POST['CourseID'] ?? $course['CourseID']); ?>" required>
        </div>

        <div class="form-group">
            <label for="Course"><?php echo $lang['course_name']; ?> <span style="color: var(--danger);">*</span></label>
            <input type="text" id="Course" name="Course" class="form-control" value="<?php echo htmlspecialchars($_POST['Course'] ?? $course['Course']); ?>" required>
        </div>

        <div class="form-group">
            <label for="Note"><?php echo $lang['other_notes']; ?></label>
            <textarea id="Note" name="Note" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['Note'] ?? $course['Note']); ?></textarea>
        </div>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
            <a href="list_course.php" class="btn btn-light">
                <?php echo $lang['cancel']; ?>
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
