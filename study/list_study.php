<?php
// study/list_study.php - Manage Student Study Enrollments
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['study'];
$page_subtitle = $lang['app_name'] . ' - ' . $lang['study'];

$search_date = $_GET['search_date'] ?? '';
$search_name = trim($_GET['search_name'] ?? '');
$filter_course = $_GET['filter_course'] ?? '';
$filter_time = $_GET['filter_time'] ?? '';
$filter_school = $_GET['filter_school'] ?? '';
$show_all = isset($_GET['show_all']);

// Fetch Distinct Start Dates
$dates = [];
$dateRes = $conn->query("SELECT DISTINCT start_date FROM tb_study ORDER BY start_date DESC LIMIT 50");
if ($dateRes) {
    while ($row = $dateRes->fetch_assoc()) $dates[] = $row['start_date'];
}

// Fetch Courses
$courses = [];
$cRes = $conn->query("SELECT ID, Course FROM tb_course ORDER BY Course ASC");
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
if ($isAdmin) {
    $sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
    if ($sRes) {
        while ($row = $sRes->fetch_assoc()) $schools[] = $row;
    }
}

// Build Filter Conditions
$whereClauses = [];

if (!empty($search_date)) {
    $whereClauses[] = "s.start_date = '" . $conn->real_escape_string($search_date) . "'";
} elseif (!$show_all) {
    $whereClauses[] = "s.end_date > CURDATE()"; // Active by default
}

if (!empty($search_name)) {
    $whereClauses[] = "st.student_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

if (!empty($filter_course)) {
    $whereClauses[] = "s.id_code = " . intval($filter_course);
}

if (!empty($filter_time)) {
    $whereClauses[] = "s.id_time = " . intval($filter_time);
}

if (!$isAdmin && $user_school_id > 0) {
    $whereClauses[] = "st.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $whereClauses[] = "st.school_id = " . intval($filter_school);
}

$whereSQL = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

$sql = "SELECT s.*, 
        st.student_name, st.sex, st.dob, st.photo,
        t.time, c.Course, c.CourseID,
        sch.school_name, sch.school_name_kh,
        CASE WHEN s.end_date > CURDATE() THEN 'active' ELSE 'finished' END as study_status
        FROM tb_study s
        JOIN tb_students st ON s.id_stu = st.ID
        JOIN tb_time t ON s.id_time = t.id
        JOIN tb_course c ON s.id_code = c.ID
        LEFT JOIN tb_schools sch ON st.school_id = sch.id
        $whereSQL
        ORDER BY s.id DESC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-book-open-reader" style="color: var(--secondary);"></i>
            <span><?php echo $lang['study']; ?></span>
        </div>
        <a href="add_study.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> <?php echo $selected_lang === 'kh' ? 'បន្ថែមការសិក្សាថ្មី' : 'Add Enrollment'; ?>
        </a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="list_study.php" style="background: #f8fafc; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
        <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="<?php echo $lang['student_name']; ?>..." class="form-control" style="width: 180px; font-size: 13px;">

        <select name="search_date" class="form-control" style="width: 150px; font-size: 13px;">
            <option value=""><?php echo $selected_lang === 'kh' ? '-- ថ្ងៃចូលរៀន --' : '-- Start Date --'; ?></option>
            <?php foreach ($dates as $d): ?>
                <option value="<?php echo $d; ?>" <?php echo ($search_date == $d) ? 'selected' : ''; ?>>
                    <?php echo khmer_date($d); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_course" class="form-control" style="width: 150px; font-size: 13px;">
            <option value=""><?php echo $lang['all_courses']; ?></option>
            <?php foreach ($courses as $c): ?>
                <option value="<?php echo $c['ID']; ?>" <?php echo ($filter_course == $c['ID']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['Course']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_time" class="form-control" style="width: 150px; font-size: 13px;">
            <option value=""><?php echo $lang['all_times']; ?></option>
            <?php foreach ($times as $t): ?>
                <option value="<?php echo $t['id']; ?>" <?php echo ($filter_time == $t['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t['time']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($isAdmin): ?>
            <select name="filter_school" class="form-control" style="width: 150px; font-size: 13px;">
                <option value=""><?php echo $lang['all_schools']; ?></option>
                <?php foreach ($schools as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($filter_school == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="show_all" <?php echo $show_all ? 'checked' : ''; ?>>
            <span><?php echo $selected_lang === 'kh' ? 'បង្ហាញទាំងអស់' : 'Show All'; ?></span>
        </label>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> <?php echo $lang['filter']; ?>
        </button>

        <a href="list_study.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-xmark"></i> <?php echo $selected_lang === 'kh' ? 'សម្អាត' : 'Clear'; ?>
        </a>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 50px;">Photo</th>
                    <th><?php echo $lang['student_name']; ?></th>
                    <th><?php echo $lang['course_name']; ?></th>
                    <th><?php echo $lang['time_slot']; ?></th>
                    <th><?php echo $lang['price']; ?></th>
                    <th><?php echo $lang['start_date']; ?></th>
                    <th><?php echo $lang['end_date']; ?></th>
                    <th><?php echo $lang['status']; ?></th>
                    <th style="width: 160px; text-align: center;"><?php echo $lang['actions']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong style="color: var(--text-muted);">#<?php echo $row['id']; ?></strong></td>
                            <td>
                                <?php if (!empty($row['photo'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%;" onerror="this.outerHTML='<div style=\'width:38px;height:38px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;\'><i class=\'fa-solid fa-user\'></i></div>';">
                                <?php else: ?>
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 12px;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo base_url('invoice/invoice.php?student_id=' . $row['id_stu']); ?>" onclick="openPaymentModal(<?php echo $row['id_stu']; ?>); return false;" class="student-pay-link" title="ចុចដើម្បីបង្កើតវិក្កយបត្រ / បង់ប្រាក់">
                                    <?php echo htmlspecialchars($row['student_name']); ?>
                                    <i class="fa-solid fa-file-invoice-dollar" style="font-size: 12px; color: var(--success);" title="បង្កើតវិក្កយបត្រ / បង់ប្រាក់"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo htmlspecialchars($row['Course']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['time']); ?></td>
                            <td style="font-weight: 700; color: #b45309;"><?php echo format_money($row['price']); ?></td>
                            <td><?php echo khmer_date($row['start_date']); ?></td>
                            <td><?php echo khmer_date($row['end_date']); ?></td>
                            <td>
                                <?php if ($row['study_status'] === 'active'): ?>
                                    <span class="badge badge-success"><?php echo $lang['studying']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?php echo $lang['finished']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <button type="button" class="btn btn-sm btn-success" onclick="openPaymentModal(<?php echo $row['id_stu']; ?>)" title="បង់ប្រាក់រហ័ស">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </button>
                                    <a href="<?php echo base_url('invoice/invoice.php?student_id=' . $row['id_stu']); ?>" class="btn btn-sm btn-primary" title="បង្កើតវិក្កយបត្រ (Create Invoice)">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>
                                    <?php if ($row['study_status'] === 'active'): ?>
                                        <a href="finish_study.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-light" title="<?php echo $selected_lang === 'kh' ? 'បញ្ចប់ការសិក្សា' : 'Mark Finished'; ?>" onclick="return confirm('តើអ្នកពិតជាចង់បញ្ចប់ការសិក្សាសម្រាប់សិស្សនេះមែនទេ?')">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="../view_certificate.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-primary" title="<?php echo $lang['view_cert']; ?>">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="edit_study.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo $lang['edit']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="delete_study.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
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
