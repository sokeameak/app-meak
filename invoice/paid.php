<?php
// invoice/paid.php - Paid Invoices List & Revenue Records
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$user_school_id = get_logged_school_id($conn);
$isAdmin = is_admin();

$page_title = $lang['paid_list'];
$page_subtitle = $lang['app_name'] . ' - ' . $lang['paid_list'];

$search = trim($_GET['search'] ?? '');
$filter_time = $_GET['filter_time'] ?? '';
$filter_school = $_GET['filter_school'] ?? '';

// Build Where
$whereClauses = ["i.status = 'Paid'"];

if (!empty($search)) {
    $whereClauses[] = "i.student_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

if (!empty($filter_time)) {
    $whereClauses[] = "i.study_time = '" . $conn->real_escape_string($filter_time) . "'";
}

if (!$isAdmin && $user_school_id > 0) {
    $whereClauses[] = "i.school_id = " . intval($user_school_id);
} elseif (!empty($filter_school)) {
    $whereClauses[] = "i.school_id = " . intval($filter_school);
}

$whereSQL = " WHERE " . implode(" AND ", $whereClauses);

// Fetch Study Times
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

// Fetch Invoices
$sql = "SELECT i.*, sch.school_name, sch.school_name_kh 
        FROM tb_invoices i 
        LEFT JOIN tb_schools sch ON i.school_id = sch.id 
        $whereSQL 
        ORDER BY i.id DESC";
$result = $conn->query($sql);

// Calculate Paid Sum
$sumRes = $conn->query("SELECT SUM(amount) as total_paid FROM tb_invoices i $whereSQL");
$totalPaid = $sumRes ? floatval($sumRes->fetch_assoc()['total_paid'] ?? 0) : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-circle-check" style="color: var(--success);"></i>
            <span><?php echo $lang['paid_list']; ?></span>
        </div>
        <div>
            <span style="font-size: 16px; font-weight: 700; color: var(--success); background: var(--success-light); padding: 6px 16px; border-radius: 20px; border: 1px solid #a7f3d0;">
                សរុបទឹកប្រាក់បានបង់៖ <?php echo format_money($totalPaid); ?>
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="paid.php" style="background: #f8fafc; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang['student_name']; ?>..." class="form-control" style="width: 180px; font-size: 13px;">

        <select name="filter_time" class="form-control" style="width: 160px; font-size: 13px;">
            <option value=""><?php echo $lang['all_times']; ?></option>
            <?php foreach ($study_times as $st): ?>
                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($filter_time === $st) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
            <?php endforeach; ?>
        </select>

        <?php if ($isAdmin): ?>
            <select name="filter_school" class="form-control" style="width: 160px; font-size: 13px;">
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

        <a href="paid.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-xmark"></i> <?php echo $selected_lang === 'kh' ? 'សម្អាត' : 'Clear'; ?>
        </a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th><?php echo $lang['student_name']; ?></th>
                    <th><?php echo $lang['description']; ?></th>
                    <th><?php echo $lang['amount']; ?></th>
                    <th><?php echo $lang['time_slot']; ?></th>
                    <th><?php echo $lang['school']; ?></th>
                    <th><?php echo $lang['created_at']; ?></th>
                    <th style="width: 100px; text-align: center;"><?php echo $lang['print']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong style="color: var(--text-muted);">#<?php echo $row['id']; ?></strong></td>
                            <td>
                                <a href="javascript:void(0)" onclick="openPaymentModal(<?php echo intval($row['student_id'] ?? 0); ?>, '<?php echo addslashes($row['student_name']); ?>')" class="student-pay-link" title="ចុចដើម្បីមើលព័ត៌មានបង់ប្រាក់">
                                    <i class="fa-solid fa-circle-dollar-to-slot" style="color: #10b981; margin-right: 4px;"></i>
                                    <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                </a>
                            </td>
                            <td><span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($row['description'] ?? '-'); ?></span></td>
                            <td style="font-weight: 700; color: var(--success);"><?php echo format_money($row['amount']); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['study_time'] ?? '-'); ?></span></td>
                            <td><span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($row['school_name_kh'] ?: ($row['school_name'] ?: 'N/A')); ?></span></td>
                            <td><span style="font-size: 12px; color: var(--text-muted);"><?php echo !empty($row['created_at']) ? khmer_date($row['created_at']) : '-'; ?></span></td>
                            <td style="text-align: center;">
                                <button type="button" class="btn btn-sm btn-primary" onclick="printPaidReceipt(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="fa-solid fa-print"></i>
                                </button>
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

<script>
function printPaidReceipt(inv) {
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
                .total-row { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 14px; border-top: 2px dashed #cbd5e1; font-size: 18px; font-weight: bold; color: #10b981; }
                .signature-area { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 20px; }
                .sig-box { text-align: center; width: 180px; }
                .sig-line { border-top: 1px solid #94a3b8; margin-top: 40px; padding-top: 6px; font-size: 13px; }
            </style>
        </head>
        <body>
            <div class="receipt-box">
                <div class="header">
                    <h2>មាគ៌ាកុំព្យូទ័រ</h2>
                    <p>បង្កាន់ដៃទទួលប្រាក់ / OFFICIAL RECEIPT</p>
                    <p style="font-size: 12px; margin-top: 4px;">លេខវិក្កយបត្រ៖ <strong>#REC-${inv.id}</strong> | កាលបរិច្ឆេទ៖ ${inv.created_at}</p>
                </div>
                <div class="info-row"><span>ឈ្មោះសិស្ស (Student Name):</span> <strong>${inv.student_name}</strong></div>
                <div class="info-row"><span>បរិយាយ (Description):</span> <strong>${inv.description || 'វគ្គសិក្សា'}</strong></div>
                <div class="info-row"><span>ម៉ោងសិក្សា (Time Slot):</span> <strong>${inv.study_time || 'N/A'}</strong></div>
                <div class="info-row"><span>ស្ថានភាព (Status):</span> <strong style="color: #10b981;">PAID (បានទូទាត់រួច)</strong></div>
                
                <div class="total-row">
                    <span>ចំនួនទឹកប្រាក់ដែលបានបង់ (Amount Paid):</span>
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