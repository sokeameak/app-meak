<?php
// expenses/edit_expense.php - Edit Expense Record
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['edit'] . ' ' . $lang['expenses'];
$page_subtitle = $lang['app_name'];

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid expense ID');
    header("Location: list_expense.php");
    exit;
}

// Fetch Expense
$stmt = $conn->prepare("SELECT * FROM tb_expenses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$exp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exp) {
    set_flash('danger', 'Expense record not found');
    header("Location: list_expense.php");
    exit;
}

if (!$isAdmin && $user_school_id > 0 && $exp['school_id'] != $user_school_id) {
    set_flash('danger', 'Unauthorized action');
    header("Location: list_expense.php");
    exit;
}

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

// Fetch Schools
$schools = [];
if ($isAdmin) {
    $sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
    if ($sRes) {
        while ($r = $sRes->fetch_assoc()) $schools[] = $r;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $amount = floatval($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $note = trim($_POST['note'] ?? '');
    $school_id = $isAdmin ? intval($_POST['school_id'] ?? 1) : ($user_school_id ?: 1);
    $receipt_photo = $exp['receipt_photo'] ?? '';

    if (empty($title) || $amount <= 0 || empty($expense_date)) {
        $error = $lang['fill_required'];
    } else {
        try {
            if (isset($_FILES['receipt_photo']) && $_FILES['receipt_photo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../uploads/';
                $newReceipt = safe_image_upload($_FILES['receipt_photo'], $targetDir, 5);
                if ($newReceipt) {
                    if (!empty($receipt_photo) && file_exists($targetDir . $receipt_photo)) {
                        @unlink($targetDir . $receipt_photo);
                    }
                    $receipt_photo = $newReceipt;
                }
            }

            $stmtUp = $conn->prepare("UPDATE tb_expenses SET title = ?, category = ?, amount = ?, expense_date = ?, note = ?, receipt_photo = ?, school_id = ? WHERE id = ?");
            if ($stmtUp) {
                $stmtUp->bind_param("ssdsssii", $title, $category, $amount, $expense_date, $note, $receipt_photo, $school_id, $id);
                if ($stmtUp->execute()) {
                    log_siem_event($conn, get_logged_user(), 'UPDATE_EXPENSE', "Updated expense ID: $id ($title, \$$amount)");
                    set_flash('success', $lang['saved_success']);
                    header("Location: list_expense.php");
                    exit;
                } else {
                    $error = "Error: " . $stmtUp->error;
                }
                $stmtUp->close();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-pen-to-square" style="color: var(--secondary);"></i>
            <span><?php echo $page_title; ?>: #<?php echo $id; ?></span>
        </div>
        <a href="list_expense.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $lang['back']; ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_expense.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title"><?php echo $lang['expense_title']; ?> <span style="color: var(--danger);">*</span></label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($_POST['title'] ?? $exp['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="category"><?php echo $lang['expense_category']; ?> <span style="color: var(--danger);">*</span></label>
            <select id="category" name="category" class="form-control" required>
                <?php foreach ($categories as $catKey => $catLabel): ?>
                    <option value="<?php echo $catKey; ?>" <?php echo ((($_POST['category'] ?? $exp['category']) === $catKey) ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($catLabel); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount"><?php echo $lang['amount']; ?> ($) <span style="color: var(--danger);">*</span></label>
            <input type="number" step="0.01" id="amount" name="amount" class="form-control" value="<?php echo htmlspecialchars($_POST['amount'] ?? $exp['amount']); ?>" required>
        </div>

        <div class="form-group">
            <label for="expense_date"><?php echo $lang['expense_date']; ?> <span style="color: var(--danger);">*</span></label>
            <input type="date" id="expense_date" name="expense_date" class="form-control" value="<?php echo htmlspecialchars($_POST['expense_date'] ?? $exp['expense_date']); ?>" required>
        </div>

        <div class="form-group">
            <label for="note"><?php echo $lang['other_notes']; ?></label>
            <textarea id="note" name="note" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['note'] ?? $exp['note']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="receipt_photo"><?php echo $lang['receipt']; ?></label>
            <?php if (!empty($exp['receipt_photo'])): ?>
                <div style="margin-bottom: 8px;">
                    <a href="../uploads/<?php echo htmlspecialchars($exp['receipt_photo']); ?>" target="_blank">
                        <img src="../uploads/<?php echo htmlspecialchars($exp['receipt_photo']); ?>" alt="Receipt" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                    </a>
                </div>
            <?php endif; ?>
            <input type="file" id="receipt_photo" name="receipt_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>

        <?php if ($isAdmin): ?>
            <div class="form-group">
                <label for="school_id"><?php echo $lang['school']; ?></label>
                <select id="school_id" name="school_id" class="form-control">
                    <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ((($_POST['school_id'] ?? $exp['school_id']) == $s['id']) ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
            <a href="list_expense.php" class="btn btn-light">
                <?php echo $lang['cancel']; ?>
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
