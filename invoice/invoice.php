<?php
// invoice/invoice.php - Invoice & Payment Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['invoices'];
$page_subtitle = $lang['app_name'] . ' - ' . $lang['invoices'];

// Handle Add Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_invoice'])) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $student_name = trim($_POST['student_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $status = $_POST['status'] ?? 'Unpaid';
    $study_time = $_POST['study_time'] ?? '';
    $school_id = $isAdmin ? intval($_POST['school_id'] ?? 1) : ($user_school_id ?: 1);

    if (empty($student_name) && $student_id > 0) {
        $sRes = $conn->query("SELECT student_name, school_id FROM tb_students WHERE id = $student_id");
        if ($sRes && $sRow = $sRes->fetch_assoc()) {
            $student_name = $sRow['student_name'];
            if (!$isAdmin && $school_id <= 0) $school_id = intval($sRow['school_id']);
        }
    }

    if (empty($student_name) || $amount <= 0) {
        set_flash('danger', $lang['fill_required']);
    } else {
        $stmt = $conn->prepare("INSERT INTO tb_invoices (student_id, student_name, description, amount, status, study_time, school_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issdssi", $student_id, $student_name, $description, $amount, $status, $study_time, $school_id);
            if ($stmt->execute()) {
                log_siem_event($conn, get_logged_user(), 'ADD_INVOICE', "Created invoice for $student_name ($amount, $status)");
                set_flash('success', $lang['saved_success']);
            } else {
                set_flash('danger', 'Error: ' . $stmt->error);
            }
            $stmt->close();
        }
    }
    header("Location: invoice.php");
    exit;
}

// Handle Update Status
if (isset($_GET['set_status']) && isset($_GET['id'])) {
    $invId = intval($_GET['id']);
    $newStatus = in_array($_GET['set_status'], ['Paid', 'Unpaid', 'Pending']) ? $_GET['set_status'] : 'Unpaid';

    $stmtUp = $conn->prepare("UPDATE tb_invoices SET status = ? WHERE id = ?");
    if ($stmtUp) {
        $stmtUp->bind_param("si", $newStatus, $invId);
        $stmtUp->execute();
        log_siem_event($conn, get_logged_user(), 'UPDATE_INVOICE_STATUS', "Changed invoice ID $invId status to $newStatus");
        set_flash('success', $lang['saved_success']);
        $stmtUp->close();
    }
    header("Location: invoice.php");
    exit;
}

// Handle Delete Invoice (Admin only)
if (isset($_GET['delete'])) {
    if (!$isAdmin) {
        set_flash('danger', 'Unauthorized action');
    } else {
        $delId = intval($_GET['delete']);
        $stmtDel = $conn->prepare("DELETE FROM tb_invoices WHERE id = ?");
        if ($stmtDel) {
            $stmtDel->bind_param("i", $delId);
            $stmtDel->execute();
            log_siem_event($conn, get_logged_user(), 'DELETE_INVOICE', "Deleted invoice ID: $delId");
            set_flash('success', $lang['deleted_success']);
            $stmtDel->close();
        }
    }
    header("Location: invoice.php");
    exit;
}

// Fetch Students with Active Study for Dropdown
$studentWhere = "";
if (!$isAdmin && $user_school_id > 0) {
    $studentWhere = " WHERE school_id = " . intval($user_school_id);
}
$students = [];
$stuRes = $conn->query("SELECT id, id as ID, student_name, school_id FROM tb_students $studentWhere ORDER BY student_name ASC");
if ($stuRes) {
    while ($r = $stuRes->fetch_assoc()) $students[] = $r;
}

// Fetch Study Times for Dropdown
$study_times = [];
$timeRes = $conn->query("SELECT id, time FROM tb_time ORDER BY id ASC");
if ($timeRes) {
    while ($r = $timeRes->fetch_assoc()) $study_times[] = $r['time'];
}

// Fetch Schools
$schools = [];
if ($isAdmin) {
    $sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
    if ($sRes) {
        while ($r = $sRes->fetch_assoc()) $schools[] = $r;
    }
}

// Check for pre-selected student from URL (?student_id=XX)
$pre_student_id = intval($_GET['student_id'] ?? 0);
$prefill_student = null;
$prefill_amount = '';
$prefill_time = '';
$prefill_school_id = 0;

if ($pre_student_id > 0) {
    $pStmt = $conn->prepare("SELECT s.*, sch.school_name, sch.school_name_kh FROM tb_students s LEFT JOIN tb_schools sch ON s.school_id = sch.id WHERE s.id = ?");
    if ($pStmt) {
        $pStmt->bind_param("i", $pre_student_id);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        if ($pRes && $pRow = $pRes->fetch_assoc()) {
            $prefill_student = $pRow;
            $prefill_school_id = intval($pRow['school_id']);

            // Find latest study
            $stStmt = $conn->prepare("SELECT st.price, t.time FROM tb_study st JOIN tb_time t ON st.id_time = t.id WHERE st.id_stu = ? ORDER BY (st.end_date > CURDATE()) DESC, st.id DESC LIMIT 1");
            if ($stStmt) {
                $stStmt->bind_param("i", $pre_student_id);
                $stStmt->execute();
                $stRes = $stStmt->get_result();
                $coursePrice = 0.0;
                if ($stRes && $stRow = $stRes->fetch_assoc()) {
                    $coursePrice = floatval($stRow['price']);
                    $prefill_time = $stRow['time'];
                }
                $stStmt->close();

                // Compute total paid
                $invStmt = $conn->prepare("SELECT SUM(amount) as paid FROM tb_invoices WHERE (student_id = ? OR student_name = ?) AND status = 'Paid'");
                if ($invStmt) {
                    $invStmt->bind_param("is", $pre_student_id, $pRow['student_name']);
                    $invStmt->execute();
                    $invRes = $invStmt->get_result();
                    $paidSoFar = 0.0;
                    if ($invRes && $invRow = $invRes->fetch_assoc()) {
                        $paidSoFar = floatval($invRow['paid'] ?? 0);
                    }
                    $invStmt->close();

                    $remain = max(0, $coursePrice - $paidSoFar);
                    $prefill_amount = ($remain > 0) ? $remain : ($coursePrice > 0 ? $coursePrice : '');
                }
            }
        }
        $pStmt->close();
    }
}

// Filters
$search = trim($_GET['search'] ?? '');
$filter_time = $_GET['filter_time'] ?? '';
$filter_school = $_GET['filter_school'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$whereClauses = ["1=1"];

if (!empty($search)) {
    $whereClauses[] = "i.student_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

if (!empty($filter_time)) {
    $whereClauses[] = "i.study_time = '" . $conn->real_escape_string($filter_time) . "'";
}

if (!empty($status_filter)) {
    $whereClauses[] = "i.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if (!$isAdmin && $user_school_id > 0) {
    $whereClauses[] = "i.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $whereClauses[] = "i.school_id = " . intval($filter_school);
}

$whereSQL = " WHERE " . implode(" AND ", $whereClauses);

// Fetch Invoices
$sql = "SELECT i.*, sch.school_name_kh, sch.school_name 
        FROM tb_invoices i 
        LEFT JOIN tb_schools sch ON i.school_id = sch.id 
        $whereSQL 
        ORDER BY i.id DESC";
$result = $conn->query($sql);

// Calculate Totals
$totalInvoiced = 0;
$totalPaid = 0;
$totalUnpaid = 0;

$totRes = $conn->query("SELECT 
    SUM(amount) as total_inv,
    SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_p,
    SUM(CASE WHEN status != 'Paid' THEN amount ELSE 0 END) as total_u
    FROM tb_invoices i $whereSQL");
if ($totRes) {
    $totRow = $totRes->fetch_assoc();
    $totalInvoiced = floatval($totRow['total_inv'] ?? 0);
    $totalPaid = floatval($totRow['total_p'] ?? 0);
    $totalUnpaid = floatval($totRow['total_u'] ?? 0);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Statistics Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fa-solid fa-file-invoice"></i>
        </div>
        <div class="stat-content">
            <h4>សរុបវិក្កយបត្រ (Total Invoiced)</h4>
            <div class="stat-value"><?php echo format_money($totalInvoiced); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-content">
            <h4>បានទូទាត់រួច (Paid)</h4>
            <div class="stat-value"><?php echo format_money($totalPaid); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div class="stat-content">
            <h4>នៅខ្វះមិនទាន់បង់ (Unpaid / Pending)</h4>
            <div class="stat-value"><?php echo format_money($totalUnpaid); ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 24px; align-items: start;">

    <!-- Add Invoice Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-file-circle-plus" style="color: var(--secondary);"></i>
                <span><?php echo $lang['add_new']; ?> វិក្កយបត្រ</span>
            </div>
        </div>

        <form method="POST" action="invoice.php">
            <input type="hidden" name="add_invoice" value="1">

            <div class="form-group">
                <label for="student_id"><?php echo $lang['student_name']; ?> <span style="color: var(--danger);">*</span></label>
                <select id="student_id" name="student_id" class="form-control" onchange="updateStudentName(this)" required>
                    <option value=""><?php echo $selected_lang === 'kh' ? '-- ជ្រើសរើសសិស្ស --' : '-- Select Student --'; ?></option>
                    <?php foreach ($students as $s): 
                        $sId = $s['id'] ?? ($s['ID'] ?? 0);
                        $sel = ($sId == $pre_student_id) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $sId; ?>" data-name="<?php echo htmlspecialchars($s['student_name']); ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($s['student_name'] . ' (#' . $sId . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" id="student_name" name="student_name" value="<?php echo htmlspecialchars($prefill_student['student_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="description"><?php echo $lang['description']; ?></label>
                <input type="text" id="description" name="description" class="form-control" placeholder="e.g. Course Fee, Book, Registration..." value="<?php echo $prefill_student ? 'បង់ថ្លៃសិក្សា' : ''; ?>">
            </div>

            <div class="form-group">
                <label for="amount"><?php echo $lang['amount']; ?> ($) <span style="color: var(--danger);">*</span></label>
                <input type="number" step="0.01" id="amount" name="amount" class="form-control" required placeholder="0.00" value="<?php echo !empty($prefill_amount) ? number_format((float)$prefill_amount, 2, '.', '') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="study_time"><?php echo $lang['time_slot']; ?></label>
                <select id="study_time" name="study_time" class="form-control">
                    <option value=""><?php echo $lang['all_times']; ?></option>
                    <?php foreach ($study_times as $st): 
                        $selTime = ($st === $prefill_time) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $selTime; ?>><?php echo htmlspecialchars($st); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status"><?php echo $lang['status']; ?> <span style="color: var(--danger);">*</span></label>
                <select id="status" name="status" class="form-control" required>
                    <option value="Unpaid">Unpaid (មិនទាន់បង់)</option>
                    <option value="Paid" <?php echo $prefill_student ? 'selected' : ''; ?>>Paid (បានបង់រួច)</option>
                    <option value="Pending">Pending (រង់ចាំ)</option>
                </select>
            </div>

            <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label for="school_id"><?php echo $lang['school']; ?></label>
                    <select id="school_id" name="school_id" class="form-control">
                        <?php foreach ($schools as $s): 
                            $selSchool = ($s['id'] == $prefill_school_id) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $selSchool; ?>>
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

    <!-- Invoices List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-receipt" style="color: var(--secondary);"></i>
                <span><?php echo $lang['invoices']; ?></span>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="invoice.php" style="background: #f8fafc; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang['student_name']; ?>..." class="form-control" style="width: 150px; font-size: 13px;">

            <select name="status_filter" class="form-control" style="width: 130px; font-size: 13px;">
                <option value=""><?php echo $selected_lang === 'kh' ? '-- គ្រប់ស្ថានភាព --' : '-- All Status --'; ?></option>
                <option value="Paid" <?php echo ($status_filter === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="Unpaid" <?php echo ($status_filter === 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                <option value="Pending" <?php echo ($status_filter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
            </select>

            <select name="filter_time" class="form-control" style="width: 140px; font-size: 13px;">
                <option value=""><?php echo $lang['all_times']; ?></option>
                <?php foreach ($study_times as $st): ?>
                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($filter_time === $st) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($isAdmin): ?>
                <select name="filter_school" class="form-control" style="width: 140px; font-size: 13px;">
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

            <a href="invoice.php" class="btn btn-light btn-sm">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </form>

        <!-- Invoices Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th><?php echo $lang['student_name']; ?></th>
                        <th><?php echo $lang['description']; ?></th>
                        <th><?php echo $lang['amount']; ?></th>
                        <th><?php echo $lang['status']; ?></th>
                        <th><?php echo $lang['time_slot']; ?></th>
                        <th><?php echo $lang['created_at']; ?></th>
                        <th style="width: 120px; text-align: center;"><?php echo $lang['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong style="color: var(--text-muted);">#<?php echo $row['id']; ?></strong></td>
                                <td>
                                    <a href="javascript:void(0)" onclick="openPaymentModal(<?php echo intval($row['student_id'] ?? 0); ?>, '<?php echo addslashes($row['student_name']); ?>')" class="student-pay-link" title="ចុចដើម្បីបង់ប្រាក់">
                                        <i class="fa-solid fa-circle-dollar-to-slot" style="color: #10b981; margin-right: 4px;"></i>
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                    </a>
                                </td>
                                <td><span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($row['description'] ?? '-'); ?></span></td>
                                <td style="font-weight: 700; color: #b45309;"><?php echo format_money($row['amount']); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Paid'): ?>
                                        <a href="invoice.php?set_status=Unpaid&id=<?php echo $row['id']; ?>" class="badge badge-success" style="text-decoration: none;" title="ចុចដើម្បីប្តូរទៅ Unpaid">
                                            <i class="fa-solid fa-check"></i> Paid
                                        </a>
                                    <?php elseif ($row['status'] === 'Pending'): ?>
                                        <a href="invoice.php?set_status=Paid&id=<?php echo $row['id']; ?>" class="badge badge-warning" style="text-decoration: none;" title="ចុចដើម្បីប្តូរទៅ Paid">
                                            <i class="fa-solid fa-clock"></i> Pending
                                        </a>
                                    <?php else: ?>
                                        <a href="invoice.php?set_status=Paid&id=<?php echo $row['id']; ?>" class="badge badge-danger" style="text-decoration: none;" title="ចុចដើម្បីប្តូរទៅ Paid">
                                            <i class="fa-solid fa-xmark"></i> Unpaid
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($row['study_time'] ?? '-'); ?></span></td>
                                <td><span style="font-size: 12px; color: var(--text-muted);"><?php echo !empty($row['created_at']) ? khmer_date($row['created_at']) : '-'; ?></span></td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="printInvoiceReceipt(<?php echo htmlspecialchars(json_encode($row)); ?>)" title="<?php echo $lang['print']; ?>">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                        <?php if ($isAdmin): ?>
                                            <a href="invoice.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                <?php echo $lang['no_records']; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Printable Receipt Modal / Script -->
<script>
function updateStudentName(select) {
    var selectedOption = select.options[select.selectedIndex];
    var studentName = selectedOption.getAttribute('data-name');
    document.getElementById('student_name').value = studentName || '';
}

function printInvoiceReceipt(inv) {
    var printWindow = window.open('', '_blank', 'width=700,height=600');
    var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>បង្កាន់ដៃទទួលប្រាក់ - #${inv.id}</title>
            <style>
                body { font-family: 'Kantumruy Pro', Arial, sans-serif; padding: 40px; color: #1e293b; }
                .receipt-box { max-width: 550px; margin: 0 auto; border: 2px solid #e2e8f0; padding: 30px; border-radius: 12px; }
                .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; }
                .header h2 { margin: 0 0 6px; color: #1e3a8a; }
                .header p { margin: 0; color: #64748b; font-size: 14px; }
                .info-row { display: flex; justify-content: space-between; margin: 10px 0; font-size: 15px; }
                .total-row { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 14px; border-top: 2px dashed #cbd5e1; font-size: 18px; font-weight: bold; color: #1e3a8a; }
                .signature-area { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 20px; }
                .sig-box { text-align: center; width: 180px; }
                .sig-line { border-top: 1px solid #94a3b8; margin-top: 40px; padding-top: 6px; font-size: 13px; }
            </style>
        </head>
        <body>
            <div class="receipt-box">
                <div class="header">
                    <h2>មាគ៌ាកុំព្យូទ័រ</h2>
                    <p>បង្កាន់ដៃទទួលប្រាក់ / PAYMENT RECEIPT</p>
                    <p style="font-size: 12px; margin-top: 4px;">លេខវិក្កយបត្រ៖ <strong>#INV-${inv.id}</strong> | កាលបរិច្ឆេទ៖ ${inv.created_at}</p>
                </div>
                <div class="info-row"><span>ឈ្មោះសិស្ស (Student Name):</span> <strong>${inv.student_name}</strong></div>
                <div class="info-row"><span>បរិយាយ (Description):</span> <strong>${inv.description || 'វគ្គសិក្សា'}</strong></div>
                <div class="info-row"><span>ម៉ោងសិក្សា (Time Slot):</span> <strong>${inv.study_time || 'N/A'}</strong></div>
                <div class="info-row"><span>ស្ថានភាព (Status):</span> <strong style="color: ${inv.status === 'Paid' ? '#10b981' : '#ef4444'}">${inv.status}</strong></div>
                
                <div class="total-row">
                    <span>ចំនួនទឹកប្រាក់សរុប (Total Amount):</span>
                    <span>$${parseFloat(inv.amount).toFixed(2)}</span>
                </div>

                <div class="signature-area">
                    <div class="sig-box">
                        <div class="sig-line">ហត្ថលេខាអ្នកបង់ប្រាក់</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line">ហត្ថលេខាអ្នកទទួលប្រាក់</div>
                    </div>
                </div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `;
    printWindow.document.write(html);
    printWindow.document.close();
}
</script>

<?php include __DIR__ . '/../includes/payment_modal.php'; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>