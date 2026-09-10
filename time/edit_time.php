<?php
// time/edit_time.php - Edit Time Slot
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$page_title = $lang['edit'] . ' ' . $lang['time_slot'];
$page_subtitle = $lang['app_name'];

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid time slot ID');
    header("Location: grades.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tb_time WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$timeRow = $res->fetch_assoc();
$stmt->close();

if (!$timeRow) {
    set_flash('danger', 'Time slot not found');
    header("Location: grades.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time = trim($_POST['time'] ?? '');

    if (empty($time)) {
        $error = $lang['fill_required'];
    } else {
        $stmtUp = $conn->prepare("UPDATE tb_time SET time = ? WHERE id = ?");
        $stmtUp->bind_param("si", $time, $id);
        if ($stmtUp->execute()) {
            log_siem_event($conn, get_logged_user(), 'UPDATE_TIME_SLOT', "Updated time slot ID: $id to $time");
            set_flash('success', $lang['saved_success']);
            header("Location: grades.php");
            exit;
        } else {
            $error = "Error: " . $stmtUp->error;
        }
        $stmtUp->close();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-pen-to-square" style="color: var(--secondary);"></i>
            <span><?php echo $page_title; ?></span>
        </div>
        <a href="grades.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $lang['back']; ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_time.php?id=<?php echo $id; ?>">
        <div class="form-group">
            <label for="time"><?php echo $lang['time_slot']; ?> <span style="color: var(--danger);">*</span></label>
            <input type="text" id="time" name="time" class="form-control" value="<?php echo htmlspecialchars($_POST['time'] ?? $timeRow['time']); ?>" required>
        </div>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
            <a href="grades.php" class="btn btn-light">
                <?php echo $lang['cancel']; ?>
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
