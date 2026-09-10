<?php
// time/grades.php - Time Slots / Study Schedule Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$page_title = $lang['grades'];
$page_subtitle = $lang['app_name'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time = trim($_POST['time'] ?? '');

    if (empty($time)) {
        $error = $lang['fill_required'];
    } else {
        $stmt = $conn->prepare("INSERT INTO tb_time (time) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $time);
            if ($stmt->execute()) {
                log_siem_event($conn, get_logged_user(), 'ADD_TIME_SLOT', "Added time slot: $time");
                set_flash('success', $lang['saved_success']);
                header("Location: grades.php");
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all time slots with student count
$times = [];
$sql = "SELECT t.*, 
        (SELECT COUNT(DISTINCT id_stu) FROM tb_study WHERE id_time = t.id AND end_date > CURDATE()) as active_students,
        (SELECT COUNT(DISTINCT id_stu) FROM tb_study WHERE id_time = t.id) as total_students
        FROM tb_time t 
        ORDER BY t.id ASC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
    
    <!-- Add Time Slot Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-clock-medical" style="color: var(--secondary);"></i>
                <span><?php echo $lang['add_new']; ?> <?php echo $lang['time_slot']; ?></span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="grades.php">
            <div class="form-group">
                <label for="time"><?php echo $lang['time_slot']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="text" id="time" name="time" class="form-control" placeholder="e.g. 08:00 - 09:00 AM" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-plus"></i> <?php echo $lang['save']; ?>
            </button>
        </form>
    </div>

    <!-- Time Slots List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-clock" style="color: var(--secondary);"></i>
                <span><?php echo $lang['grades']; ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th><?php echo $lang['time_slot']; ?></th>
                        <th style="width: 120px;"><?php echo $lang['studying']; ?></th>
                        <th style="width: 120px;"><?php echo $lang['students']; ?></th>
                        <th style="width: 120px; text-align: center;"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong style="color: var(--text-muted);">#<?php echo $row['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['time']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?php echo $row['active_students']; ?> នាក់</span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $row['total_students']; ?> នាក់</span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <a href="edit_time.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo $lang['edit']; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="delete_time.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                <?php echo $lang['no_records']; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
