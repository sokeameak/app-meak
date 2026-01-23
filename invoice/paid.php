<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Handle Delete Invoice
if (isset($_GET['delete'])) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
        header("Location: paid.php");
        exit;
    }
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tb_invoices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    log_siem_event($conn, $_SESSION['user'], 'DELETE_INVOICE', "Deleted paid invoice ID: $id");
    $stmt->close();
    header("Location: paid.php");
    exit;
}

// Fetch Study Times for Dropdown (for filter)
$study_times = [];
$studyTimeSql = "SELECT DISTINCT t.time FROM tb_study s JOIN tb_time t ON s.id_time = t.id ORDER BY t.time ASC";
$studyTimeResult = $conn->query($studyTimeSql);
if ($studyTimeResult) {
    while($row = $studyTimeResult->fetch_assoc()) {
        $study_times[] = $row['time'];
    }
}

// Fetch Distinct Periods (Month-Year) for Dropdown
$periods = [];
$periodSql = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as period FROM tb_invoices ORDER BY period DESC";
$periodResult = $conn->query($periodSql);
if ($periodResult) {
    while($row = $periodResult->fetch_assoc()) {
        $periods[] = $row['period'];
    }
}

// Fetch Schools for Dropdown
$schools = [];
$schoolSql = "SELECT school_name FROM tb_schools ORDER BY school_name ASC";
$schoolResult = $conn->query($schoolSql);
if ($schoolResult) {
    while($row = $schoolResult->fetch_assoc()) {
        $schools[] = $row['school_name'];
    }
}

// Fetch Invoices with Filters
$search = $_GET['search'] ?? '';
$filter_time = $_GET['filter_time'] ?? '';
$filter_period = $_GET['filter_period'] ?? '';
$filter_school = $_GET['filter_school'] ?? '';
$chk_paid = isset($_GET['chk_paid']) ? true : (empty($_GET) ? true : false);
$chk_unpaid = isset($_GET['chk_unpaid']) ? true : false;

$status_filters = [];
if ($chk_paid) $status_filters[] = "'Paid'";
if ($chk_unpaid) $status_filters[] = "'Unpaid'";

$sql = "SELECT *, 
        (SELECT sch.school_name 
         FROM tb_students st 
         JOIN tb_schools sch ON st.school_id = sch.id 
         WHERE st.student_name = tb_invoices.student_name LIMIT 1) as school_name 
        FROM tb_invoices WHERE 1=1";

if (!empty($status_filters)) {
    $sql .= " AND status IN (" . implode(",", $status_filters) . ")";
} else {
    $sql .= " AND 1=0";
}

$params = [];
$types = "";

if ($search) {
    $sql .= " AND student_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if ($filter_time) {
    $sql .= " AND study_time = ?";
    $params[] = $filter_time;
    $types .= "s";
}

if ($filter_period) {
    $sql .= " AND DATE_FORMAT(created_at, '%Y-%m') = ?";
    $params[] = $filter_period;
    $types .= "s";
}

if ($filter_school) {
    $sql .= " AND (SELECT sch.school_name FROM tb_students st JOIN tb_schools sch ON st.school_id = sch.id WHERE st.student_name = tb_invoices.student_name LIMIT 1) = ?";
    $params[] = $filter_school;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paid Invoices</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, 'Khmer OS', sans-serif; background: #f4f4f4; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; }
        .sidebar a { display: block; padding: 10px; margin: 5px 0; text-decoration: none; color: white; border-radius: 5px; }
        .sidebar a:hover { background: #34495e; }
        .sidebar a i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { flex: 1; padding: 20px; }
        .header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; }
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block;}
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; color: white; }
        .status-paid { background: #2ecc71; }
        .status-unpaid { background: #e74c3c; }
        .status-pending { background: #f1c40f; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div style="text-align: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #34495e;">
                <div style="width: 80px; height: 80px; background: #ecf0f1; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #2c3e50; font-weight: bold;">
                    <?php echo strtoupper(substr($_SESSION['user'], 0, 1)); ?>
                </div>
                <div style="color: white; font-weight: bold; font-size: 1.1em;"><?php echo htmlspecialchars($_SESSION['user']); ?></div>
                <div style="color: #bdc3c7; font-size: 0.8em; margin-top: 5px;"><?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1) ? $lang['admin'] : $lang['normal_user']; ?></div>
                <div style="margin-top: 10px;">
                    <a href="<?php echo getUrlWithLang('en'); ?>" style="display:inline; padding:5px; color:white; <?php echo $selected_lang=='en'?'font-weight:bold; text-decoration:underline;':''; ?>">EN</a> | 
                    <a href="<?php echo getUrlWithLang('kh'); ?>" style="display:inline; padding:5px; color:white; <?php echo $selected_lang=='kh'?'font-weight:bold; text-decoration:underline;':''; ?>">KH</a>
                </div>
            </div>
            <h3><?php echo $lang['dashboard']; ?></h3>
            <a href="../dashboard.php?page=home"><i class="fa-solid fa-home"></i> <?php echo $lang['home']; ?></a>
            <a href="../students/list_student.php"><i class="fa-solid fa-user-graduate"></i> <?php echo $lang['students']; ?></a>
            <a href="../students/register_student.php"><i class="fa-solid fa-user-plus"></i> <?php echo $lang['add_student']; ?></a>
            <a href="../students/finished_student.php"><i class="fa-solid fa-user-check"></i> <?php echo $lang['finished_students']; ?></a>
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> <?php echo $lang['study']; ?></a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> <?php echo $lang['course']; ?></a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> <?php echo $lang['grades']; ?></a>
            <a href="invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> <?php echo $lang['invoices']; ?></a>
            <a href="paid.php" style="background: #34495e;"><i class="fa-solid fa-file-invoice"></i> <?php echo $lang['paid_list']; ?></a>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> <?php echo $lang['logout']; ?></a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Paid Invoices</h1>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2>Paid List</h2>
                    <form method="GET" style="display: flex; gap: 10px;">
                        <label style="display: flex; align-items: center;"><input type="checkbox" name="chk_paid" <?php echo $chk_paid ? 'checked' : ''; ?> style="width: auto; margin-right: 5px;"> Paid</label>
                        <label style="display: flex; align-items: center;"><input type="checkbox" name="chk_unpaid" <?php echo $chk_unpaid ? 'checked' : ''; ?> style="width: auto; margin-right: 5px;"> Unpaid</label>
                        <input type="text" name="search" placeholder="Search student..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <select name="filter_time" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Times</option>
                            <?php foreach ($study_times as $time): ?>
                                <option value="<?php echo htmlspecialchars($time); ?>" <?php echo ($filter_time === $time) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($time); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="filter_school" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Schools</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo htmlspecialchars($school); ?>" <?php echo ($filter_school === $school) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($school); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="filter_period" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Periods</option>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?php echo htmlspecialchars($p); ?>" <?php echo ($filter_period === $p) ? 'selected' : ''; ?>>
                                    <?php echo date('F Y', strtotime($p . '-01')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn">Search</button>
                        <?php if ($search || $filter_time || $filter_period || $filter_school): ?><a href="paid.php" class="btn" style="background: #95a5a6;">Clear</a><?php endif; ?>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>School</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $total_amount = 0; ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php $total_amount += $row['amount']; ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><span style='font-size:0.9em; color:#555;'><?php echo htmlspecialchars($row['school_name'] ?? 'Default'); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td>$<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['study_time'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'status-pending';
                                            if ($row['status'] == 'Paid') $statusClass = 'status-paid';
                                            elseif ($row['status'] == 'Unpaid') $statusClass = 'status-unpaid';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
                                        <a href="paid.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')" style="font-size: 0.8em;" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <tr style="background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #bdc3c7;">
                                <td colspan="3" style="text-align: right;">Total Amount:</td>
                                <td colspan="5" style="color: #27ae60; font-size: 1.1em;">$<?php echo number_format($total_amount, 2); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="8">No paid invoices found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>