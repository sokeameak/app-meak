<?php
// students/finished.php - Finished Students & Certificate Issuance
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['finished_students'];
$page_subtitle = $lang['app_name'];

$search = trim($_GET['search'] ?? '');
$filter_school = $_GET['filter_school'] ?? '';

// Build Where
$whereConditions = ["s.end_date <= CURDATE()"];
if (!$isAdmin && $user_school_id > 0) {
    $whereConditions[] = "st.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $whereConditions[] = "st.school_id = " . intval($filter_school);
}

if (!empty($search)) {
    $whereConditions[] = "st.student_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$whereSQL = " WHERE " . implode(" AND ", $whereConditions);

// Fetch schools (admin)
$schools = [];
if ($isAdmin) {
    $sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
    if ($sRes) {
        while ($row = $sRes->fetch_assoc()) $schools[] = $row;
    }
}

// Fetch Finished Students
$sql = "SELECT s.id as study_id, st.id as student_id, st.student_name, st.sex, st.dob, st.photo, 
        s.start_date, s.end_date, c.Course, c.CourseID,
        sch.school_name, sch.school_name_kh,
        (SELECT COUNT(*) FROM tbl_certi WHERE study_id = s.id) as has_cert
        FROM tb_study s 
        JOIN tb_students st ON s.id_stu = st.id 
        JOIN tb_course c ON s.id_code = c.id 
        LEFT JOIN tb_schools sch ON st.school_id = sch.id
        $whereSQL
        ORDER BY s.end_date DESC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-award" style="color: var(--warning);"></i>
            <span><?php echo $lang['finished_students']; ?></span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="finished.php" style="background: #f8fafc; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
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
            <a href="finished.php" class="btn btn-light btn-sm">
                <i class="fa-solid fa-xmark"></i> <?php echo $selected_lang === 'kh' ? 'សម្អាត' : 'Clear'; ?>
            </a>
        <?php endif; ?>
    </form>

    <!-- Batch Form for Issue Certificates -->
    <form method="POST" action="insert_certi.php">
        <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-certificate"></i> <?php echo $lang['issue_cert']; ?> (ជ្រើសរើស)
            </button>
            <span style="font-size: 13px; color: var(--text-muted);">
                សរុបសិស្សបញ្ចប់៖ <strong><?php echo $result ? $result->num_rows : 0; ?> នាក់</strong>
            </span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes(this)">
                        </th>
                        <th style="width: 50px;">Photo</th>
                        <th><?php echo $lang['student_name']; ?></th>
                        <th><?php echo $lang['sex']; ?></th>
                        <th><?php echo $lang['dob']; ?></th>
                        <th><?php echo $lang['course_name']; ?></th>
                        <th><?php echo $lang['school']; ?></th>
                        <th><?php echo $lang['end_date']; ?></th>
                        <th><?php echo $lang['status']; ?></th>
                        <th style="width: 160px; text-align: center;"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="ids[]" value="<?php echo $row['study_id']; ?>" class="row-checkbox">
                                </td>
                                <td>
                                    <?php if (!empty($row['photo'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;" onerror="this.outerHTML='<div style=\'width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;\'><i class=\'fa-solid fa-user\'></i></div>';">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 14px;">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                </td>
                                <td><?php echo khmer_gender($row['sex']); ?></td>
                                <td><?php echo khmer_date($row['dob']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($row['Course']); ?></span>
                                </td>
                                <td>
                                    <span style="font-size: 13px; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($row['school_name_kh'] ?: ($row['school_name'] ?: 'N/A')); ?>
                                    </span>
                                </td>
                                <td><?php echo khmer_date($row['end_date']); ?></td>
                                <td>
                                    <?php if ($row['has_cert'] > 0): ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check"></i> <?php echo $selected_lang === 'kh' ? 'មានវិញ្ញាបនបត្រ' : 'Certified'; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?php echo $selected_lang === 'kh' ? 'មិនទាន់ចេញ' : 'Pending Cert'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <a href="../view_certificate.php?id=<?php echo $row['study_id']; ?>" target="_blank" class="btn btn-sm btn-primary" title="<?php echo $lang['view_cert']; ?>">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <a href="delete_finished.php?id=<?php echo $row['study_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-graduation-cap" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                <?php echo $lang['no_records']; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
function toggleAllCheckboxes(source) {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>