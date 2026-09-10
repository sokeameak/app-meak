<?php
// courses/list_course.php - Course List & Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$page_title = $lang['course'];
$page_subtitle = $lang['app_name'];

$courses = [];
$sql = "SELECT c.*, 
        c.ID as id,
        c.ID as ID,
        (SELECT COUNT(*) FROM tb_study WHERE id_code = c.ID) as student_count 
        FROM tb_course c 
        ORDER BY c.ID DESC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-chalkboard" style="color: var(--secondary);"></i>
            <span><?php echo $lang['course']; ?></span>
        </div>
        <a href="add_course.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> <?php echo $lang['add_new']; ?>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 140px;">Course ID</th>
                    <th><?php echo $lang['course_name']; ?></th>
                    <th><?php echo $lang['other_notes']; ?></th>
                    <th style="width: 120px;"><?php echo $lang['students']; ?></th>
                    <th style="width: 140px; text-align: center;"><?php echo $lang['actions']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $cId = $row['id'] ?? ($row['ID'] ?? 0);
                    ?>
                        <tr>
                            <td><strong style="color: var(--text-muted);">#<?php echo $cId; ?></strong></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['CourseID']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['Course']); ?></strong></td>
                            <td><span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($row['Note'] ?? '-'); ?></span></td>
                            <td><span class="badge badge-success"><?php echo $row['student_count']; ?> នាក់</span></td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <a href="edit_course.php?id=<?php echo $cId; ?>" class="btn btn-sm btn-warning" title="<?php echo $lang['edit']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="delete_course.php?id=<?php echo $cId; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                            <?php echo $lang['no_records']; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
