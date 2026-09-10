<?php
// study/edit_study.php - Edit Study Enrollment Record
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['edit'] . ' ' . $lang['study'];
$page_subtitle = $lang['app_name'];

$study_id = intval($_GET['id'] ?? 0);
if ($study_id <= 0) {
    set_flash('danger', 'Invalid study ID');
    header("Location: list_study.php");
    exit;
}

// Fetch Study Record
$stmt = $conn->prepare("SELECT s.*, st.student_name, st.school_id FROM tb_study s JOIN tb_students st ON s.id_stu = st.ID WHERE s.id = ?");
$stmt->bind_param("i", $study_id);
$stmt->execute();
$res = $stmt->get_result();
$study = $res->fetch_assoc();
$stmt->close();

if (!$study) {
    set_flash('danger', 'Study record not found');
    header("Location: list_study.php");
    exit;
}

// School Authorization
if (!$isAdmin && $user_school_id > 0 && $study['school_id'] != $user_school_id) {
    set_flash('danger', 'Unauthorized access');
    header("Location: list_study.php");
    exit;
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
    $id_code = intval($_POST['id_code'] ?? 0);
    $id_time = intval($_POST['id_time'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if (empty($id_code) || empty($id_time) || empty($start_date) || empty($end_date)) {
        $error = $lang['fill_required'];
    } else {
        $stmtUp = $conn->prepare("UPDATE tb_study SET id_code = ?, id_time = ?, price = ?, start_date = ?, end_date = ? WHERE id = ?");
        $stmtUp->bind_param("iidssi", $id_code, $id_time, $price, $start_date, $end_date, $study_id);
        if ($stmtUp->execute()) {
            log_siem_event($conn, get_logged_user(), 'UPDATE_STUDY', "Updated study record ID: $study_id for student: {$study['student_name']}");
            set_flash('success', $lang['saved_success']);
            header("Location: list_study.php");
            exit;
        } else {
            $error = "Error updating: " . $stmtUp->error;
        }
        $stmtUp->close();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-pen-to-square" style="color: var(--secondary);"></i>
            <span><?php echo $page_title; ?>: <?php echo htmlspecialchars($study['student_name']); ?></span>
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

    <form method="POST" action="edit_study.php?id=<?php echo $study_id; ?>">
        <div class="form-group">
            <label><?php echo $lang['student_name']; ?></label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($study['student_name']); ?>" disabled style="background: #f1f5f9;">
        </div>

        <div class="form-group">
            <label for="id_code"><?php echo $lang['course_name']; ?> <span style="color: var(--danger);">*</span></label>
            <select id="id_code" name="id_code" class="form-control" required>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c['ID']; ?>" <?php echo ($study['id_code'] == $c['ID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['Course'] . ' (' . $c['CourseID'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_time"><?php echo $lang['time_slot']; ?> <span style="color: var(--danger);">*</span></label>
            <select id="id_time" name="id_time" class="form-control" required>
                <?php foreach ($times as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo ($study['id_time'] == $t['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['time']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="price"><?php echo $lang['price']; ?> ($) <span style="color: var(--danger);">*</span></label>
            <input type="number" step="0.01" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($study['price']); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="start_date"><?php echo $lang['start_date']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($study['start_date']); ?>" required>
            </div>

            <div class="form-group">
                <label for="end_date"><?php echo $lang['end_date']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($study['end_date']); ?>" required>
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