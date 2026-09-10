<?php
// students/list_student.php - Student Directory Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['student_list'];
$page_subtitle = $lang['app_name'] . ' - ' . $lang['students'];

$search = trim($_GET['search'] ?? '');
$filter_school = $_GET['filter_school'] ?? '';

// Build Where Clause
$whereConditions = [];
if (!$isAdmin && $user_school_id > 0) {
    $whereConditions[] = "s.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $whereConditions[] = "s.school_id = " . intval($filter_school);
}

if (!empty($search)) {
    $whereConditions[] = "s.student_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$whereSQL = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

// Fetch schools for filter (admin)
$schools = [];
if ($isAdmin) {
    $schoolRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
    if ($schoolRes) {
        while ($row = $schoolRes->fetch_assoc()) $schools[] = $row;
    }
}

// Fetch Students (including attendance count)
$sql = "SELECT s.*, 
        s.id as ID,
        s.id as id,
        sch.school_name, sch.school_name_kh,
        (SELECT COUNT(*) FROM tb_study WHERE id_stu = s.id) as study_count,
        (SELECT COUNT(*) FROM tb_study WHERE id_stu = s.id AND end_date > CURDATE()) as active_study_count,
        (SELECT COUNT(*) FROM tbl_att WHERE id_stu = s.id AND status = '0') as absent_count,
        (SELECT COUNT(*) FROM tbl_att WHERE id_stu = s.id) as total_att_count
        FROM tb_students s 
        LEFT JOIN tb_schools sch ON s.school_id = sch.id 
        $whereSQL 
        ORDER BY s.id DESC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-users" style="color: var(--secondary);"></i>
            <span><?php echo $lang['student_list']; ?></span>
        </div>
        <div>
            <a href="register_student_study.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus"></i> <?php echo $lang['register_study']; ?>
            </a>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <form method="GET" action="list_student.php" style="background: #f8fafc; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang['search']; ?>..." class="form-control" style="width: 220px; font-size: 13px;">

        <?php if ($isAdmin): ?>
            <select name="filter_school" class="form-control" style="width: 180px; font-size: 13px;">
                <option value=""><?php echo $lang['all_schools']; ?></option>
                <?php foreach ($schools as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($filter_school == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> <?php echo $lang['filter']; ?>
        </button>

        <?php if (!empty($search) || !empty($filter_school)): ?>
            <a href="list_student.php" class="btn btn-light btn-sm">
                <i class="fa-solid fa-xmark"></i> <?php echo $selected_lang === 'kh' ? 'សម្អាត' : 'Clear'; ?>
            </a>
        <?php endif; ?>
    </form>

    <!-- Student Table -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 60px;"><?php echo $lang['photo']; ?></th>
                    <th><?php echo $lang['student_name']; ?></th>
                    <th><?php echo $lang['sex']; ?></th>
                    <th><?php echo $lang['dob']; ?></th>
                    <th><?php echo $lang['school']; ?></th>
                    <th><?php echo $lang['study']; ?></th>
                    <th style="text-align: center;"><?php echo $lang['attendance']; ?> (អវត្តមាន)</th>
                    <th><?php echo $lang['other_notes']; ?></th>
                    <th style="width: 200px; text-align: center;"><?php echo $lang['actions']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $stuId = $row['id'] ?? ($row['ID'] ?? 0);
                        $absentCount = intval($row['absent_count'] ?? 0);
                    ?>
                        <tr>
                            <td><strong style="color: var(--text-muted);">#<?php echo $stuId; ?></strong></td>
                            <td>
                                <?php if (!empty($row['photo'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" style="width: 42px; height: 42px; object-fit: cover; border-radius: 50%;" onerror="this.outerHTML='<div style=\'width:42px;height:42px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;\'><i class=\'fa-solid fa-user\'></i></div>';">
                                <?php else: ?>
                                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 14px;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo base_url('invoice/invoice.php?student_id=' . $stuId); ?>" onclick="openPaymentModal(<?php echo $stuId; ?>); return false;" class="student-pay-link" title="ចុចដើម្បីបង្កើតវិក្កយបត្រ / បង់ប្រាក់">
                                    <?php echo htmlspecialchars($row['student_name']); ?>
                                    <i class="fa-solid fa-file-invoice-dollar" style="font-size: 12px; color: var(--success);" title="បង្កើតវិក្កយបត្រ / បង់ប្រាក់"></i>
                                </a>
                            </td>
                            <td><?php echo khmer_gender($row['sex']); ?></td>
                            <td><?php echo khmer_date($row['dob']); ?></td>
                            <td>
                                <span style="font-size: 13px; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($row['school_name_kh'] ?: ($row['school_name'] ?: 'N/A')); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['active_study_count'] > 0): ?>
                                    <span class="badge badge-success"><?php echo $row['active_study_count']; ?> <?php echo $lang['studying']; ?></span>
                                <?php elseif ($row['study_count'] > 0): ?>
                                    <span class="badge badge-warning"><?php echo $row['study_count']; ?> <?php echo $lang['finished']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo $selected_lang === 'kh' ? 'គ្មានការសិក្សា' : 'No Study'; ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($absentCount > 0): ?>
                                    <span class="badge badge-danger" style="font-size: 13px; padding: 5px 10px;" title="ចំនួនអវត្តមានសរុប">
                                        <i class="fa-solid fa-user-xmark"></i> <?php echo $absentCount; ?> ដង
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success" style="font-size: 13px; padding: 5px 10px;" title="មិនដែលអវត្តមាន">
                                        <i class="fa-solid fa-check"></i> 0 ដង
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo htmlspecialchars(mb_strimwidth($row['other'] ?? '', 0, 30, '...')); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <button type="button" class="btn btn-sm btn-success" onclick="openPaymentModal(<?php echo $stuId; ?>)" title="បង់ប្រាក់រហ័ស">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </button>
                                    <a href="<?php echo base_url('invoice/invoice.php?student_id=' . $stuId); ?>" class="btn btn-sm btn-primary" title="បង្កើតវិក្កយបត្រ (Create Invoice)">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>
                                    <a href="../study/add_study.php?id_stu=<?php echo $stuId; ?>" class="btn btn-sm btn-primary" title="<?php echo $lang['register_study']; ?>">
                                        <i class="fa-solid fa-book-medical"></i>
                                    </a>
                                    <a href="edit_student.php?id=<?php echo $stuId; ?>" class="btn btn-sm btn-warning" title="<?php echo $lang['edit']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="delete_student.php?id=<?php echo $stuId; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                            <?php echo $lang['no_records']; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/payment_modal.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>