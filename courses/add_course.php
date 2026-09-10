<?php
// courses/add_course.php - Add New Course
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$page_title = $lang['add_new'] . ' ' . $lang['course'];
$page_subtitle = $lang['app_name'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $CourseID = trim($_POST['CourseID'] ?? '');
    $Course = trim($_POST['Course'] ?? '');
    $Note = trim($_POST['Note'] ?? '');

    if (empty($CourseID) || empty($Course)) {
        $error = $lang['fill_required'];
    } else {
        // Check uniqueness of CourseID
        $stmtCheck = $conn->prepare("SELECT ID FROM tb_course WHERE CourseID = ?");
        $stmtCheck->bind_param("s", $CourseID);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $error = "លេខកូដវគ្គសិក្សា ($CourseID) មានរួចហើយ!";
        } else {
            $stmt = $conn->prepare("INSERT INTO tb_course (CourseID, Course, Note) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $CourseID, $Course, $Note);
            if ($stmt->execute()) {
                log_siem_event($conn, get_logged_user(), 'ADD_COURSE', "Added course: $Course ($CourseID)");
                set_flash('success', $lang['saved_success']);
                header("Location: list_course.php");
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
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
            <i class="fa-solid fa-plus" style="color: var(--secondary);"></i>
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

    <form method="POST" action="add_course.php">
        <div class="form-group">
            <label for="CourseID">Course ID / លេខកូដ <span style="color: var(--danger);">*</span></label>
            <input type="text" id="CourseID" name="CourseID" class="form-control" value="<?php echo htmlspecialchars($_POST['CourseID'] ?? ''); ?>" required placeholder="e.g. CS01, WD01...">
        </div>

        <div class="form-group">
            <label for="Course"><?php echo $lang['course_name']; ?> <span style="color: var(--danger);">*</span></label>
            <input type="text" id="Course" name="Course" class="form-control" value="<?php echo htmlspecialchars($_POST['Course'] ?? ''); ?>" required placeholder="ឈ្មោះវគ្គសិក្សា...">
        </div>

        <div class="form-group">
            <label for="Note"><?php echo $lang['other_notes']; ?></label>
            <textarea id="Note" name="Note" class="form-control" rows="3" placeholder="ពិពណ៌នាបន្ថែម..."><?php echo htmlspecialchars($_POST['Note'] ?? ''); ?></textarea>
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
