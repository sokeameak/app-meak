<?php
// dashboard.php - Main Management Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['dashboard'];
$page_subtitle = $lang['welcome_back'] . ', ' . htmlspecialchars(get_logged_user());

$view = $_GET['view'] ?? 'table';
$chk_studying = isset($_GET['filter']) ? isset($_GET['chk_studying']) : true;
$chk_finished = isset($_GET['filter']) ? isset($_GET['chk_finished']) : false;
$filter_time = $_GET['filter_time'] ?? '';
$filter_school = $_GET['filter_school'] ?? '';
$search_name = trim($_GET['search_name'] ?? '');

// Fetch times for dropdown
$times = [];
$timeRes = $conn->query("SELECT id, time FROM tb_time ORDER BY id ASC");
if ($timeRes) {
    while ($row = $timeRes->fetch_assoc()) $times[] = $row;
}

// Fetch schools for dropdown
$schools = [];
$schoolRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
if ($schoolRes) {
    while ($row = $schoolRes->fetch_assoc()) $schools[] = $row;
}

// Build School Condition for Current User / Filter
$schoolWhere = "";
if (!$isAdmin && $user_school_id > 0) {
    $schoolWhere = " AND s.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $schoolWhere = " AND s.school_id = " . intval($filter_school);
}

// Build Time & Study Status Conditions
$timeCondition = !empty($filter_time) ? " AND id_time = " . intval($filter_time) : "";

$statusConditions = [];
if ($chk_studying) {
    $statusConditions[] = "EXISTS (SELECT 1 FROM tb_study WHERE id_stu = s.id AND end_date > CURDATE() $timeCondition)";
}
if ($chk_finished) {
    $statusConditions[] = "EXISTS (SELECT 1 FROM tb_study WHERE id_stu = s.id AND end_date <= CURDATE() $timeCondition)";
}

$whereClause = "WHERE 1=1";
if (!empty($statusConditions)) {
    $whereClause .= " AND (" . implode(" OR ", $statusConditions) . ")";
} else {
    $whereClause .= " AND 1=0";
}

$whereClause .= $schoolWhere;

if (!empty($search_name)) {
    $whereClause .= " AND s.student_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

// 1. Dashboard Statistics
$statsSchoolWhere = "";
if (!$isAdmin && $user_school_id > 0) {
    $statsSchoolWhere = " WHERE school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $statsSchoolWhere = " WHERE school_id = " . intval($filter_school);
}

// Total Students
$resCount = $conn->query("SELECT COUNT(*) as count FROM tb_students" . $statsSchoolWhere);
$totalStudents = $resCount ? ($resCount->fetch_assoc()['count'] ?? 0) : 0;

// Active Students
$activeStatsWhere = "WHERE st.end_date > CURDATE()";
if (!$isAdmin && $user_school_id > 0) {
    $activeStatsWhere .= " AND s.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $activeStatsWhere .= " AND s.school_id = " . intval($filter_school);
}
$resActive = $conn->query("SELECT COUNT(DISTINCT s.id) as count FROM tb_students s JOIN tb_study st ON s.id = st.id_stu " . $activeStatsWhere);
$activeStudents = $resActive ? ($resActive->fetch_assoc()['count'] ?? 0) : 0;

// Monthly Income & Expenses
$currentMonth = date('m');
$currentYear = date('Y');

$resIncome = $conn->query("SELECT SUM(amount) as total FROM tb_invoices WHERE status = 'Paid' AND MONTH(created_at) = $currentMonth AND YEAR(created_at) = $currentYear" . ($user_school_id && !$isAdmin ? " AND school_id = $user_school_id" : ""));
$monthlyIncome = $resIncome ? floatval($resIncome->fetch_assoc()['total'] ?? 0) : 0;

$resExp = $conn->query("SELECT SUM(amount) as total FROM tb_expenses WHERE MONTH(expense_date) = $currentMonth AND YEAR(expense_date) = $currentYear" . ($user_school_id && !$isAdmin ? " AND school_id = $user_school_id" : ""));
$monthlyExpenses = $resExp ? floatval($resExp->fetch_assoc()['total'] ?? 0) : 0;

// Main Query for Student List with Attendance Count
$sql = "SELECT s.*, 
        s.id as ID,
        s.id as student_id,
        sch.school_name, sch.school_name_kh,
        (SELECT COALESCE(SUM(amount), 0) FROM tb_invoices WHERE (student_id = s.id OR student_name = s.student_name) AND status = 'Paid') as total_paid,
        (SELECT COALESCE(SUM(price), 0) FROM tb_study WHERE id_stu = s.id) as total_study_price,
        (SELECT COUNT(*) FROM tbl_att WHERE id_stu = s.id AND status = '0') as absent_count,
        (SELECT GROUP_CONCAT(DISTINCT t.time SEPARATOR ', ') FROM tb_study st JOIN tb_time t ON st.id_time = t.id WHERE st.id_stu = s.id) as study_times,
        (SELECT GROUP_CONCAT(DISTINCT c.Course SEPARATOR ', ') FROM tb_study st JOIN tb_course c ON st.id_code = c.ID WHERE st.id_stu = s.id) as study_courses
        FROM tb_students s 
        LEFT JOIN tb_schools sch ON s.school_id = sch.id
        $whereClause 
        ORDER BY s.id DESC";
$result = $conn->query($sql);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Statistics Overview Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $lang['total_students']; ?></h4>
            <div class="stat-value"><?php echo to_khmer_num($totalStudents); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $lang['active_students']; ?></h4>
            <div class="stat-value"><?php echo to_khmer_num($activeStudents); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon amber">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $lang['monthly_income']; ?> (<?php echo date('M Y'); ?>)</h4>
            <div class="stat-value" style="color: var(--success);"><?php echo format_money($monthlyIncome); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $lang['monthly_expenses']; ?> (<?php echo date('M Y'); ?>)</h4>
            <div class="stat-value" style="color: var(--danger);"><?php echo format_money($monthlyExpenses); ?></div>
        </div>
    </div>
</div>

<!-- Main Content Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-graduation-cap" style="color: var(--secondary);"></i>
            <span><?php echo $lang['student_list']; ?></span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="<?php echo base_url('students/register_student_study.php'); ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> <?php echo $lang['add_student']; ?>
            </a>
        </div>
    </div>

    <!-- Filter & View Controls -->
    <form method="GET" action="dashboard.php" style="background: #f8fafc; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
        <input type="hidden" name="filter" value="1">
        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">

        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <!-- Search Name -->
            <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="<?php echo $lang['search']; ?>..." class="form-control" style="width: 180px; padding: 6px 12px; font-size: 13px;">

            <!-- School Filter (Admin) -->
            <?php if ($isAdmin): ?>
                <select name="filter_school" class="form-control" style="width: 160px; padding: 6px 12px; font-size: 13px;">
                    <option value=""><?php echo $lang['all_schools']; ?></option>
                    <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($filter_school == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <!-- Time Filter -->
            <select name="filter_time" class="form-control" style="width: 160px; padding: 6px 12px; font-size: 13px;">
                <option value=""><?php echo $lang['all_times']; ?></option>
                <?php foreach ($times as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo ($filter_time == $t['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['time']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Status Checkboxes -->
            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                <input type="checkbox" name="chk_studying" <?php echo $chk_studying ? 'checked' : ''; ?>>
                <span class="badge badge-success"><?php echo $lang['studying']; ?></span>
            </label>

            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                <input type="checkbox" name="chk_finished" <?php echo $chk_finished ? 'checked' : ''; ?>>
                <span class="badge badge-warning"><?php echo $lang['finished']; ?></span>
            </label>

            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-filter"></i> <?php echo $lang['filter']; ?>
            </button>
        </div>

        <!-- View Switcher -->
        <div style="display: flex; gap: 4px;">
            <?php
            $queryTable = $_GET; $queryTable['view'] = 'table';
            $queryCard = $_GET; $queryCard['view'] = 'card';
            ?>
            <a href="?<?php echo http_build_query($queryTable); ?>" class="btn btn-sm <?php echo $view === 'table' ? 'btn-primary' : 'btn-light'; ?>" title="<?php echo $lang['table_view']; ?>">
                <i class="fa-solid fa-table-list"></i>
            </a>
            <a href="?<?php echo http_build_query($queryCard); ?>" class="btn btn-sm <?php echo $view === 'card' ? 'btn-primary' : 'btn-light'; ?>" title="<?php echo $lang['card_view']; ?>">
                <i class="fa-solid fa-grip"></i>
            </a>
        </div>
    </form>

    <!-- Students Display -->
    <?php if ($view === 'card'): ?>
        <!-- Card View -->
        <div class="student-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $stuId = $row['id'] ?? ($row['ID'] ?? 0);
                    $price = floatval($row['total_study_price'] ?? 0);
                    $paid = floatval($row['total_paid'] ?? 0);
                    $remain = $price - $paid;
                    $absentCount = intval($row['absent_count'] ?? 0);
                ?>
                    <div class="student-card">
                        <?php if (!empty($row['photo'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" class="student-photo" onerror="this.outerHTML='<div class=\'student-photo-placeholder\'><i class=\'fa-solid fa-user\'></i></div>';">
                        <?php else: ?>
                            <div class="student-photo-placeholder"><i class="fa-solid fa-user"></i></div>
                        <?php endif; ?>

                        <div class="student-card-body">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 4px;">
                                    <h3 class="student-card-title">
                                        <a href="<?php echo base_url('invoice/invoice.php?student_id=' . $stuId); ?>" onclick="openPaymentModal(<?php echo $stuId; ?>); return false;" style="color: inherit; text-decoration: none; cursor: pointer;" title="ចុចដើម្បីបង្កើតវិក្កយបត្រ / បង់ប្រាក់">
                                            <?php echo htmlspecialchars($row['student_name']); ?>
                                            <i class="fa-solid fa-file-invoice-dollar" style="font-size: 13px; margin-left: 4px; color: var(--success);" title="បង្កើតវិក្កយបត្រ / បង់ប្រាក់"></i>
                                        </a>
                                    </h3>
                                    <?php if ($absentCount > 0): ?>
                                        <span class="badge badge-danger" style="font-size: 11px;" title="ចំនួនអវត្តមាន">
                                            <i class="fa-solid fa-user-xmark"></i> <?php echo $absentCount; ?> ដង
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success" style="font-size: 11px;" title="មិនដែលអវត្តមាន">
                                            <i class="fa-solid fa-check"></i> 0 ដង
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                                    <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($row['study_times'] ?? 'N/A'); ?>
                                </p>
                                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                                    <i class="fa-solid fa-book"></i> <?php echo htmlspecialchars($row['study_courses'] ?? 'N/A'); ?>
                                </p>
                            </div>

                            <div style="background: #f8fafc; padding: 10px; border-radius: var(--radius-sm); margin: 8px 0; font-size: 13px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                                    <span style="color: var(--text-muted);"><?php echo $lang['price']; ?>:</span>
                                    <strong style="color: #b45309;"><?php echo format_money($price); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                                    <span style="color: var(--text-muted);"><?php echo $lang['paid']; ?>:</span>
                                    <strong style="color: var(--success);"><?php echo format_money($paid); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);"><?php echo $lang['remain']; ?>:</span>
                                    <strong style="color: <?php echo $remain > 0 ? 'var(--danger)' : 'var(--success)'; ?>;"><?php echo format_money($remain); ?></strong>
                                </div>
                            </div>

                            <div style="display: flex; gap: 6px; justify-content: center; margin-top: 8px;">
                                <button type="button" class="btn btn-success btn-sm" onclick="openPaymentModal(<?php echo $stuId; ?>)" title="បង់ប្រាក់រហ័ស">
                                    <i class="fa-solid fa-hand-holding-dollar"></i> <?php echo $selected_lang === 'kh' ? 'បង់ប្រាក់' : 'Pay'; ?>
                                </button>
                                <a href="<?php echo base_url('invoice/invoice.php?student_id=' . $stuId); ?>" class="btn btn-light btn-sm" title="បង្កើតវិក្កយបត្រ (Create Invoice)">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>
                                <button type="button" class="btn btn-warning btn-sm" onclick="markAbsent(<?php echo $stuId; ?>, '<?php echo htmlspecialchars(addslashes($row['student_name'])); ?>')">
                                    <i class="fa-solid fa-user-xmark"></i> <?php echo $lang['absent']; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-user-slash" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px;"></i>
                    <p><?php echo $lang['no_records']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Table View -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo $lang['photo']; ?></th>
                        <th><?php echo $lang['student_name']; ?></th>
                        <th><?php echo $lang['sex']; ?></th>
                        <th><?php echo $lang['dob']; ?></th>
                        <th><?php echo $lang['time_slot']; ?></th>
                        <th><?php echo $lang['price']; ?></th>
                        <th><?php echo $lang['paid']; ?></th>
                        <th><?php echo $lang['remain']; ?></th>
                        <th style="width: 150px; text-align: center;"><?php echo $lang['attendance']; ?> (អវត្តមាន)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $stuId = $row['id'] ?? ($row['ID'] ?? 0);
                            $price = floatval($row['total_study_price'] ?? 0);
                            $paid = floatval($row['total_paid'] ?? 0);
                            $remain = $price - $paid;
                            $absentCount = intval($row['absent_count'] ?? 0);
                        ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['photo'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="Img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;" onerror="this.outerHTML='<div style=\'width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;\'><i class=\'fa-solid fa-user\'></i></div>';">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 14px;">
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
                                    <span class="badge badge-info"><?php echo htmlspecialchars($row['study_times'] ?? 'N/A'); ?></span>
                                </td>
                                <td style="font-weight: 700; color: #b45309;"><?php echo format_money($price); ?></td>
                                <td>
                                    <a href="<?php echo base_url('invoice/paid.php?search=' . urlencode($row['student_name'])); ?>" style="color: var(--success); font-weight: 700; text-decoration: none;">
                                        <?php echo format_money($paid); ?>
                                    </a>
                                </td>
                                <td style="font-weight: 700; color: <?php echo $remain > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">
                                    <a href="javascript:void(0)" onclick="openPaymentModal(<?php echo $stuId; ?>)" style="color: inherit; text-decoration: none; cursor: pointer;" title="ចុចដើម្បីបង់ប្រាក់">
                                        <?php echo format_money($remain); ?>
                                        <?php if ($remain > 0): ?>
                                            <span class="badge badge-danger" style="font-size: 10px; margin-left: 2px;">បង់</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; align-items: center; gap: 4px;">
                                        <button type="button" class="btn btn-success btn-sm" onclick="openPaymentModal(<?php echo $stuId; ?>)" title="បង់ប្រាក់រហ័ស (Quick Pay)">
                                            <i class="fa-solid fa-hand-holding-dollar"></i>
                                        </button>
                                        <a href="<?php echo base_url('invoice/invoice.php?student_id=' . $stuId); ?>" class="btn btn-primary btn-sm" title="បង្កើតវិក្កយបត្រ (Create Invoice)">
                                            <i class="fa-solid fa-file-invoice"></i>
                                        </a>
                                        <?php if ($absentCount > 0): ?>
                                            <span class="badge badge-danger" style="font-size: 11px; padding: 4px 6px;" title="ចំនួនអវត្តមានសរុប">
                                                <i class="fa-solid fa-user-xmark"></i> <?php echo $absentCount; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success" style="font-size: 11px; padding: 4px 6px;" title="មិនដែលអវត្តមាន">
                                                <i class="fa-solid fa-check"></i> 0
                                            </span>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-warning btn-sm" onclick="markAbsent(<?php echo $stuId; ?>, '<?php echo htmlspecialchars(addslashes($row['student_name'])); ?>')" title="កត់អវត្តមាន">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-user-slash" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                <?php echo $lang['no_records']; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Attendance Form Modal / AJAX Handler -->
<script>
function markAbsent(studentId, studentName) {
    if (!confirm('កត់អវត្តមានសម្រាប់សិស្ស ' + studentName + ' ?')) {
        return;
    }

    var formData = new FormData();
    formData.append('student_id', studentId);
    formData.append('status', '0');
    formData.append('ajax', '1');

    fetch('<?php echo base_url('att/create_att.php'); ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        // Fallback form submit
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo base_url('att/create_att.php'); ?>';
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'student_id';
        input.value = studentId;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    });
}
</script>
<?php include __DIR__ . '/includes/payment_modal.php'; ?>
<?php include 'includes/footer.php'; ?>