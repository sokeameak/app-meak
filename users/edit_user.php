<?php
// users/edit_user.php - Edit User Account
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_admin();

$page_title = $lang['edit'] . ' ' . $lang['users'];
$page_subtitle = $lang['app_name'];

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid user ID');
    header("Location: add_users.php");
    exit;
}

// Fetch User
$stmt = $conn->prepare("SELECT * FROM tb_users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$targetUser = $res->fetch_assoc();
$stmt->close();

if (!$targetUser) {
    set_flash('danger', 'User not found');
    header("Location: add_users.php");
    exit;
}

// Fetch Schools
$schools = [];
$sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
if ($sRes) {
    while ($r = $sRes->fetch_assoc()) $schools[] = $r;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $user_type = intval($_POST['user_type'] ?? 0);
    $school_id = intval($_POST['school_id'] ?? 0);

    if (empty($username)) {
        $error = $lang['fill_required'];
    } else {
        // Check uniqueness
        $stmtCheck = $conn->prepare("SELECT id FROM tb_users WHERE username = ? AND id != ?");
        $stmtCheck->bind_param("si", $username, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $error = "ឈ្មោះអ្នកប្រើ ($username) ត្រូវបានប្រើប្រាស់រួចហើយ!";
        } else {
            if (!empty($password)) {
                // Update with new password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmtUp = $conn->prepare("UPDATE tb_users SET username = ?, password = ?, user_type = ?, school_id = ? WHERE id = ?");
                $stmtUp->bind_param("ssiii", $username, $hashed, $user_type, $school_id, $id);
            } else {
                // Update without changing password
                $stmtUp = $conn->prepare("UPDATE tb_users SET username = ?, user_type = ?, school_id = ? WHERE id = ?");
                $stmtUp->bind_param("siii", $username, $user_type, $school_id, $id);
            }

            if ($stmtUp->execute()) {
                log_siem_event($conn, get_logged_user(), 'UPDATE_USER', "Updated user ID: $id ($username)");
                set_flash('success', $lang['saved_success']);
                header("Location: add_users.php");
                exit;
            } else {
                $error = "Error: " . $stmtUp->error;
            }
            $stmtUp->close();
        }
        $stmtCheck->close();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-user-pen" style="color: var(--secondary);"></i>
            <span><?php echo $page_title; ?>: <?php echo htmlspecialchars($targetUser['username']); ?></span>
        </div>
        <a href="add_users.php" class="btn btn-light btn-sm">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $lang['back']; ?>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_user.php?id=<?php echo $id; ?>">
        <div class="form-group">
            <label for="username"><?php echo $lang['username']; ?> <span style="color: var(--danger);">*</span></label>
            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? $targetUser['username']); ?>" required>
        </div>

        <div class="form-group">
            <label for="password"><?php echo $lang['password']; ?></label>
            <input type="password" id="password" name="password" class="form-control" placeholder="ទុកទំនេរ ប្រសិនបើមិនចង់ប្តូរពាក្យសម្ងាត់...">
        </div>

        <div class="form-group">
            <label for="user_type"><?php echo $lang['user_role']; ?> <span style="color: var(--danger);">*</span></label>
            <select id="user_type" name="user_type" class="form-control" required>
                <option value="0" <?php echo ($targetUser['user_type'] == 0) ? 'selected' : ''; ?>><?php echo $lang['normal_user']; ?></option>
                <option value="1" <?php echo ($targetUser['user_type'] == 1) ? 'selected' : ''; ?>><?php echo $lang['admin']; ?></option>
            </select>
        </div>

        <div class="form-group">
            <label for="school_id"><?php echo $lang['school']; ?></label>
            <select id="school_id" name="school_id" class="form-control">
                <option value="0"><?php echo $selected_lang === 'kh' ? '-- គ្រប់សាលាទាំងអស់ (Admin) --' : '-- All Schools (Admin) --'; ?></option>
                <?php foreach ($schools as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($targetUser['school_id'] == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
            <a href="add_users.php" class="btn btn-light">
                <?php echo $lang['cancel']; ?>
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>