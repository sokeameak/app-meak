<?php
// expenses/list_expense.php - School Expenses Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['expenses'];
$page_subtitle = $lang['app_name'] . ' - ' . $lang['expenses'];

// Expense Categories
$categories = [
    'Salary' => 'ប្រាក់បៀវត្សគ្រូ & បុគ្គលិក (Salary)',
    'Utilities' => 'ថ្លៃទឹក ភ្លើង & អ៊ីនធឺណិត (Utilities & Internet)',
    'Rent' => 'ថ្លៃជួលទីតាំង (Building Rent)',
    'Equipment' => 'សម្ភារៈ & ឧបករណ៍កុំព្យូទ័រ (Equipment & Hardware)',
    'Supplies' => 'សម្ភារៈការិយាល័យ & សៀវភៅ (Office & Books)',
    'Marketing' => 'ការផ្សព្វផ្សាយ & ទីផ្សារ (Marketing & Ads)',
    'Maintenance' => 'ជួសជុល & ថែទាំ (Maintenance & Repair)',
    'Other' => 'ចំណាយផ្សេងៗ (Other Miscellaneous)'
];

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $amount = floatval($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $note = trim($_POST['note'] ?? '');
    $school_id = $isAdmin ? intval($_POST['school_id'] ?? 1) : ($user_school_id ?: 1);
    $created_by = get_logged_user();
    $receipt_photo = '';

    if (empty($title) || $amount <= 0 || empty($expense_date)) {
        set_flash('danger', $lang['fill_required']);
    } else {
        try {
            if (isset($_FILES['receipt_photo']) && $_FILES['receipt_photo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../uploads/';
                $receipt_photo = safe_image_upload($_FILES['receipt_photo'], $targetDir, 5);
            }

            $stmt = $conn->prepare("INSERT INTO tb_expenses (title, category, amount, expense_date, note, receipt_photo, school_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssdsssis", $title, $category, $amount, $expense_date, $note, $receipt_photo, $school_id, $created_by);
                if ($stmt->execute()) {
                    log_siem_event($conn, $created_by, 'ADD_EXPENSE', "Added expense: $title ($$amount, Category: $category)");
                    set_flash('success', $lang['saved_success']);
                } else {
                    set_flash('danger', 'Error: ' . $stmt->error);
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            set_flash('danger', $e->getMessage());
        }
    }
    header("Location: list_expense.php");
    exit;
}

// Handle Delete Expense
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    
    // Check permission
    $stmtCheck = $conn->prepare("SELECT id, title, amount, receipt_photo, school_id FROM tb_expenses WHERE id = ?");
    $stmtCheck->bind_param("i", $delId);
    $stmtCheck->execute();
    $exp = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($exp) {
        if (!$isAdmin && $user_school_id > 0 && $exp['school_id'] != $user_school_id) {
            set_flash('danger', 'Unauthorized action');
        } else {
            // Delete photo if exists
            if (!empty($exp['receipt_photo'])) {
                $photoPath = __DIR__ . '/../uploads/' . $exp['receipt_photo'];
                if (file_exists($photoPath)) @unlink($photoPath);
            }

            $stmtDel = $conn->prepare("DELETE FROM tb_expenses WHERE id = ?");
            $stmtDel->bind_param("i", $delId);
            if ($stmtDel->execute()) {
                log_siem_event($conn, get_logged_user(), 'DELETE_EXPENSE', "Deleted expense ID: $delId ({$exp['title']}, \${$exp['amount']})");
                set_flash('success', $lang['deleted_success']);
            }
            $stmtDel->close();
        }
    }
    header("Location: list_expense.php");
    exit;
}

// Fetch Schools
$schools = [];
if ($isAdmin) {
    $sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
    if ($sRes) {
        while ($r = $sRes->fetch_assoc()) $schools[] = $r;
    }
}

// Filters
$search = trim($_GET['search'] ?? '');
$filter_cat = $_GET['filter_cat'] ?? '';
$filter_school = $_GET['filter_school'] ?? '';
$filter_month = $_GET['filter_month'] ?? date('Y-m');

$whereClauses = ["1=1"];

if (!empty($search)) {
    $whereClauses[] = "(e.title LIKE '%" . $conn->real_escape_string($search) . "%' OR e.note LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if (!empty($filter_cat)) {
    $whereClauses[] = "e.category = '" . $conn->real_escape_string($filter_cat) . "'";
}

if (!empty($filter_month)) {
    $parts = explode('-', $filter_month);
    if (count($parts) === 2) {
        $whereClauses[] = "YEAR(e.expense_date) = " . intval($parts[0]) . " AND MONTH(e.expense_date) = " . intval($parts[1]);
    }
}

if (!$isAdmin && $user_school_id > 0) {
    $whereClauses[] = "e.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $whereClauses[] = "e.school_id = " . intval($filter_school);
}

$whereSQL = " WHERE " . implode(" AND ", $whereClauses);

// Fetch Expenses
$sql = "SELECT e.*, sch.school_name, sch.school_name_kh 
        FROM tb_expenses e 
        LEFT JOIN tb_schools sch ON e.school_id = sch.id 
        $whereSQL 
        ORDER BY e.expense_date DESC, e.id DESC";
$result = $conn->query($sql);

// Calculate Totals for Stats Overview
$currentY = date('Y');
$currentM = date('m');

// 1. This Month's Expenses
$statWhereMonth = "WHERE YEAR(expense_date) = $currentY AND MONTH(expense_date) = $currentM";
if (!$isAdmin && $user_school_id > 0) $statWhereMonth .= " AND school_id = " . intval($user_school_id);
$mExpRes = $conn->query("SELECT SUM(amount) as total FROM tb_expenses $statWhereMonth");
$monthlyExpenses = $mExpRes ? floatval($mExpRes->fetch_assoc()['total'] ?? 0) : 0;

// 2. This Month's Paid Revenue
$statWhereRev = "WHERE status = 'Paid' AND YEAR(created_at) = $currentY AND MONTH(created_at) = $currentM";
if (!$isAdmin && $user_school_id > 0) $statWhereRev .= " AND school_id = " . intval($user_school_id);
$mRevRes = $conn->query("SELECT SUM(amount) as total FROM tb_invoices $statWhereRev");
$monthlyRevenue = $mRevRes ? floatval($mRevRes->fetch_assoc()['total'] ?? 0) : 0;

// 3. Net Profit this month
$netProfit = $monthlyRevenue - $monthlyExpenses;

// 4. Total Filtered Expenses
$filteredTotalRes = $conn->query("SELECT SUM(amount) as total FROM tb_expenses e $whereSQL");
$filteredTotal = $filteredTotalRes ? floatval($filteredTotalRes->fetch_assoc()['total'] ?? 0) : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Statistics Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fa-solid fa-money-bill-trend-up"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $lang['monthly_expenses']; ?> (<?php echo date('M Y'); ?>)</h4>
            <div class="stat-value" style="color: var(--danger);"><?php echo format_money($monthlyExpenses); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $lang['monthly_income']; ?> (<?php echo date('M Y'); ?>)</h4>
            <div class="stat-value" style="color: var(--success);"><?php echo format_money($monthlyRevenue); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon <?php echo $netProfit >= 0 ? 'blue' : 'amber'; ?>">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $lang['net_profit']; ?> (<?php echo date('M Y'); ?>)</h4>
            <div class="stat-value" style="color: <?php echo $netProfit >= 0 ? '#1e3a8a' : 'var(--danger)'; ?>;">
                <?php echo format_money($netProfit); ?>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2.3fr; gap: 24px; align-items: start;">

    <!-- Add Expense Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-circle-plus" style="color: var(--secondary);"></i>
                <span><?php echo $lang['add_expense']; ?></span>
            </div>
        </div>

        <form method="POST" action="list_expense.php" enctype="multipart/form-data">
            <input type="hidden" name="add_expense" value="1">

            <div class="form-group">
                <label for="title"><?php echo $lang['expense_title']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. ទិញទឹកថ្នាំព្រីន, ថ្លៃភ្លើងខែ...">
            </div>

            <div class="form-group">
                <label for="category"><?php echo $lang['expense_category']; ?> <span style="color: var(--danger);">*</span></label>
                <select id="category" name="category" class="form-control" required>
                    <?php foreach ($categories as $catKey => $catLabel): ?>
                        <option value="<?php echo $catKey; ?>"><?php echo htmlspecialchars($catLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount"><?php echo $lang['amount']; ?> ($) <span style="color: var(--danger);">*</span></label>
                <input type="number" step="0.01" id="amount" name="amount" class="form-control" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="expense_date"><?php echo $lang['expense_date']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="date" id="expense_date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="note"><?php echo $lang['other_notes']; ?></label>
                <textarea id="note" name="note" class="form-control" rows="2" placeholder="ព័ត៌មានលម្អិតបន្ថែម..."></textarea>
            </div>

            <div class="form-group">
                <label for="receipt_photo"><?php echo $lang['receipt']; ?> (រូបថត/PDF)</label>
                <input type="file" id="receipt_photo" name="receipt_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>

            <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label for="school_id"><?php echo $lang['school']; ?></label>
                    <select id="school_id" name="school_id" class="form-control">
                        <?php foreach ($schools as $s): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
        </form>
    </div>

    <!-- Expenses List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-list-check" style="color: var(--secondary);"></i>
                <span><?php echo $selected_lang === 'kh' ? 'តារាងកំណត់ត្រាចំណាយ' : 'Expenses List'; ?></span>
            </div>
            <div>
                <span style="font-size: 14px; font-weight: 700; color: var(--danger); background: var(--danger-light); padding: 4px 12px; border-radius: 20px;">
                    សរុប៖ <?php echo format_money($filteredTotal); ?>
                </span>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="list_expense.php" style="background: #f8fafc; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang['search']; ?>..." class="form-control" style="width: 140px; font-size: 13px;">

            <select name="filter_cat" class="form-control" style="width: 140px; font-size: 13px;">
                <option value=""><?php echo $selected_lang === 'kh' ? '-- គ្រប់ប្រភេទ --' : '-- All Categories --'; ?></option>
                <?php foreach ($categories as $catKey => $catLabel): ?>
                    <option value="<?php echo $catKey; ?>" <?php echo ($filter_cat === $catKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($catKey); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="month" name="filter_month" value="<?php echo htmlspecialchars($filter_month); ?>" class="form-control" style="width: 140px; font-size: 13px;">

            <?php if ($isAdmin): ?>
                <select name="filter_school" class="form-control" style="width: 130px; font-size: 13px;">
                    <option value=""><?php echo $lang['all_schools']; ?></option>
                    <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($filter_school == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-filter"></i>
            </button>

            <a href="list_expense.php?filter_month=" class="btn btn-light btn-sm">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </form>

        <!-- Expenses Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th><?php echo $lang['expense_date']; ?></th>
                        <th><?php echo $lang['expense_title']; ?></th>
                        <th><?php echo $lang['expense_category']; ?></th>
                        <th><?php echo $lang['amount']; ?></th>
                        <th><?php echo $lang['receipt']; ?></th>
                        <th>អ្នកកត់ត្រា</th>
                        <th style="width: 100px; text-align: center;"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong style="color: var(--text-muted);">#<?php echo $row['id']; ?></strong></td>
                                <td><span style="font-size: 13px; font-weight: 600;"><?php echo khmer_date($row['expense_date']); ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                    <?php if (!empty($row['note'])): ?>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['note']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($row['category']); ?></span>
                                </td>
                                <td style="font-weight: 700; color: var(--danger); font-size: 14px;">
                                    <?php echo format_money($row['amount']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['receipt_photo'])): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($row['receipt_photo']); ?>" target="_blank" class="badge badge-success" style="text-decoration: none;">
                                            <i class="fa-solid fa-image"></i> មើល
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 12px; color: var(--text-muted);">
                                        <i class="fa-solid fa-user" style="font-size: 10px;"></i> <?php echo htmlspecialchars($row['created_by'] ?: 'Admin'); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <a href="edit_expense.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo $lang['edit']; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="list_expense.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-receipt" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
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
