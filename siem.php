<?php
// siem.php - Security Information & Event Management (SIEM) Logs
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';
require_admin();

$page_title = $lang['siem_logs'];
$page_subtitle = $lang['app_name'];

// Handle Export to CSV
if (isset($_GET['export'])) {
    $filename = "siem_logs_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // Add BOM for UTF-8 Excel support
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['ID', 'Date & Time', 'Username', 'Action', 'Details', 'IP Address', 'User Agent']);

    $sqlExp = "SELECT * FROM tb_siem_logs ORDER BY id DESC";
    $expRes = $conn->query($sqlExp);
    if ($expRes) {
        while ($row = $expRes->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['username'],
                $row['action'],
                $row['details'],
                $row['ip_address'],
                $row['user_agent']
            ]);
        }
    }
    fclose($output);
    exit;
}

// Handle Clear Logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $conn->query("TRUNCATE TABLE tb_siem_logs");
    log_siem_event($conn, get_logged_user(), 'CLEAR_LOGS', 'Admin cleared all security audit logs');
    set_flash('success', 'បានសម្អាតកំណត់ហេតុសុវត្ថិភាពដោយជោគជ័យ!');
    header("Location: siem.php");
    exit;
}

// Filters
$search = trim($_GET['search'] ?? '');
$filter_action = $_GET['filter_action'] ?? '';

$whereClauses = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $whereClauses[] = "(username LIKE ? OR details LIKE ? OR ip_address LIKE ?)";
    $st = "%" . $search . "%";
    $params[] = $st;
    $params[] = $st;
    $params[] = $st;
    $types .= "sss";
}

if (!empty($filter_action)) {
    $whereClauses[] = "action = ?";
    $params[] = $filter_action;
    $types .= "s";
}

$whereSQL = " WHERE " . implode(" AND ", $whereClauses);

// Fetch Distinct Actions for Dropdown
$actions = [];
$actRes = $conn->query("SELECT DISTINCT action FROM tb_siem_logs ORDER BY action ASC");
if ($actRes) {
    while ($r = $actRes->fetch_assoc()) $actions[] = $r['action'];
}

// Fetch Logs
$sql = "SELECT * FROM tb_siem_logs $whereSQL ORDER BY id DESC LIMIT 150";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-shield-virus" style="color: var(--secondary);"></i>
            <span><?php echo $lang['siem_logs']; ?> (Security Audit)</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="siem.php?export=1" class="btn btn-success btn-sm">
                <i class="fa-solid fa-file-csv"></i> <?php echo $selected_lang === 'kh' ? 'ទាញយក CSV' : 'Export CSV'; ?>
            </a>
            <form method="POST" action="siem.php" onsubmit="return confirm('តើអ្នកពិតជាចង់សម្អាតកំណត់ហេតុទាំងអស់មែនទេ?')">
                <button type="submit" name="clear_logs" value="1" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash-can"></i> <?php echo $selected_lang === 'kh' ? 'សម្អាត Logs' : 'Clear Logs'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="siem.php" style="background: #f8fafc; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang['search']; ?>..." class="form-control" style="width: 200px; font-size: 13px;">

        <select name="filter_action" class="form-control" style="width: 200px; font-size: 13px;">
            <option value=""><?php echo $selected_lang === 'kh' ? '-- គ្រប់សកម្មភាព --' : '-- All Actions --'; ?></option>
            <?php foreach ($actions as $act): ?>
                <option value="<?php echo htmlspecialchars($act); ?>" <?php echo ($filter_action === $act) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($act); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> <?php echo $lang['filter']; ?>
        </button>

        <a href="siem.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-xmark"></i> <?php echo $selected_lang === 'kh' ? 'សម្អាត' : 'Clear'; ?>
        </a>
    </form>

    <!-- Logs Table -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 170px;">កាលបរិច្ឆេទ & ម៉ោង</th>
                    <th>អ្នកប្រើប្រាស់</th>
                    <th>សកម្មភាព (Action)</th>
                    <th>ព័ត៌មានលម្អិត (Details)</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $act = $row['action'];
                        $badgeClass = 'badge-info';
                        if (strpos($act, 'LOGIN_SUCCESS') !== false || strpos($act, 'ADD_') !== false) {
                            $badgeClass = 'badge-success';
                        } elseif (strpos($act, 'FAILED') !== false || strpos($act, 'DELETE_') !== false || strpos($act, 'CLEAR_') !== false) {
                            $badgeClass = 'badge-danger';
                        } elseif (strpos($act, 'UPDATE_') !== false) {
                            $badgeClass = 'badge-warning';
                        }
                    ?>
                        <tr>
                            <td><strong style="color: var(--text-muted);">#<?php echo $row['id']; ?></strong></td>
                            <td><span style="font-size: 12px; color: var(--text-muted);"><?php echo $row['created_at']; ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['action']); ?></span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: var(--text-main);"><?php echo htmlspecialchars($row['details']); ?></span>
                            </td>
                            <td>
                                <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($row['ip_address']); ?></code>
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

<?php include 'includes/footer.php'; ?>