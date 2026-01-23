<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Access Control: Only Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
    header("Location: dashboard.php");
    exit;
}

// 1. Setup Table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS tb_siem_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    action VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (username),
    INDEX (action),
    INDEX (created_at)
)");

// Handle Export
if (isset($_GET['export'])) {
    $filename = "siem_logs_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Time', 'Username', 'Action', 'Details', 'IP Address', 'User Agent'));
    
    // Build Query for Export (Reusing filter logic)
    $sql = "SELECT * FROM tb_siem_logs WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($_GET['search'])) {
        $sql .= " AND (username LIKE ? OR details LIKE ?)";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
        $types .= "ss";
    }
    if (!empty($_GET['action'])) {
        $sql .= " AND action = ?";
        $params[] = $_GET['action'];
        $types .= "s";
    }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle Clear Logs
$message = '';
if (isset($_POST['clear_logs'])) {
    $conn->query("TRUNCATE TABLE tb_siem_logs");
    log_siem_event($conn, $_SESSION['user'], 'CLEAR_LOGS', 'Cleared all SIEM logs');
    $message = "Logs cleared successfully.";
}

// Filters & View
$search = $_GET['search'] ?? '';
$filter_action = $_GET['action'] ?? '';

$sql = "SELECT * FROM tb_siem_logs WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND (username LIKE ? OR details LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
if ($filter_action) {
    $sql .= " AND action = ?";
    $params[] = $filter_action;
    $types .= "s";
}

$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get Actions for Dropdown
$actions = [];
$actRes = $conn->query("SELECT DISTINCT action FROM tb_siem_logs ORDER BY action");
if($actRes) while($r = $actRes->fetch_assoc()) $actions[] = $r['action'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIEM - Security Logs</title>
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
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .success { color: #155724; background: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
        .filter-bar { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; }
        input, select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
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
                <div style="color: #bdc3c7; font-size: 0.8em; margin-top: 5px;">Administrator</div>
            </div>
            <h3>Dashboard</h3>
            <a href="dashboard.php?page=home"><i class="fa-solid fa-home"></i> Home</a>
            <a href="students/list_student.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="students/register_student_study.php"><i class="fa-solid fa-user-plus"></i> Add Student</a>
            <a href="study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="students/finished_student.php"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <a href="users/add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <a href="siem.php" style="background: #34495e;"><i class="fa-solid fa-shield-halved"></i> SIEM Logs</a>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>SIEM - Security Logs</h1>
                <p>Monitor system activity and security events.</p>
            </div>

            <div class="card">
                <?php if ($message): ?><div class="success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <form method="GET" class="filter-bar" style="margin-bottom: 0;">
                        <input type="text" name="search" placeholder="Search user or details..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $act): ?>
                                <option value="<?php echo htmlspecialchars($act); ?>" <?php echo ($filter_action === $act) ? 'selected' : ''; ?>><?php echo htmlspecialchars($act); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn">Filter</button>
                        <?php if($search || $filter_action): ?><a href="siem.php" class="btn" style="background: #95a5a6;">Reset</a><?php endif; ?>
                    </form>
                    <div>
                        <a href="siem.php?export=1&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($filter_action); ?>" class="btn" style="background: #27ae60;"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear ALL logs? This cannot be undone.');">
                            <button type="submit" name="clear_logs" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Clear Logs</button>
                        </form>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td style="font-size: 0.9em; color: #555;"><?php echo $row['created_at']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                    <td><span style="background: #ecf0f1; padding: 2px 6px; border-radius: 4px; font-size: 0.9em;"><?php echo htmlspecialchars($row['action']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['details']); ?></td>
                                    <td style="font-size: 0.9em; color: #7f8c8d;"><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px; color: #999;">No logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>