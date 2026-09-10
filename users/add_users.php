<?php
// users/add_users.php - User Account Management (Admin Only)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_admin();

$page_title = $lang['users'];
$page_subtitle = $lang['app_name'];

$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $user_type = intval($_POST['user_type'] ?? 0);
    $school_id = intval($_POST['school_id'] ?? 0);

    if (empty($username) || empty($password)) {
        $error = $lang['fill_required'];
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM tb_users WHERE username = ?");
        $stmtCheck->bind_param("s", $username);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $error = "ឈ្មោះអ្នកប្រើ ($username) មានរួចហើយ!";
        } else {
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            $stmtIns = $conn->prepare("INSERT INTO tb_users (username, password, user_type, school_id) VALUES (?, ?, ?, ?)");
            $stmtIns->bind_param("ssii", $username, $hashedPass, $user_type, $school_id);
            if ($stmtIns->execute()) {
                log_siem_event($conn, get_logged_user(), 'ADD_USER', "Created user account: $username (Role: $user_type, School: $school_id)");
                set_flash('success', $lang['saved_success']);
                header("Location: add_users.php");
                exit;
            } else {
                $error = "Error: " . $stmtIns->error;
            }
            $stmtIns->close();
        }
        $stmtCheck->close();
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    
    // Check if target user is currently logged in user
    $stmtCheck = $conn->prepare("SELECT username FROM tb_users WHERE id = ?");
    $stmtCheck->bind_param("i", $delId);
    $stmtCheck->execute();
    $targetUser = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($targetUser) {
        if ($targetUser['username'] === get_logged_user()) {
            set_flash('danger', 'មិនអាចលុបគណនីដែលកំពុងដំណើរការបានទេ!');
        } else {
            $stmtDel = $conn->prepare("DELETE FROM tb_users WHERE id = ?");
            $stmtDel->bind_param("i", $delId);
            if ($stmtDel->execute()) {
                log_siem_event($conn, get_logged_user(), 'DELETE_USER', "Deleted user account: {$targetUser['username']} (ID: $delId)");
                set_flash('success', $lang['deleted_success']);
            }
            $stmtDel->close();
        }
    }
    header("Location: add_users.php");
    exit;
}

// Fetch Schools
$schools = [];
$sRes = $conn->query("SELECT id, school_name, school_name_kh FROM tb_schools ORDER BY school_name");
if ($sRes) {
    while ($r = $sRes->fetch_assoc()) $schools[] = $r;
}

// Fetch Users
$users = [];
$sql = "SELECT u.*, sch.school_name, sch.school_name_kh 
        FROM tb_users u 
        LEFT JOIN tb_schools sch ON u.school_id = sch.id 
        ORDER BY u.id ASC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">

    <!-- Add User Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-user-plus" style="color: var(--secondary);"></i>
                <span><?php echo $lang['add_new']; ?> <?php echo $lang['users']; ?></span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_users.php">
            <div class="form-group">
                <label for="username"><?php echo $lang['username']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required placeholder="Username...">
            </div>

            <div class="form-group">
                <label for="password"><?php echo $lang['password']; ?> <span style="color: var(--danger);">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Password...">
            </div>

            <div class="form-group">
                <label for="user_type"><?php echo $lang['user_role']; ?> <span style="color: var(--danger);">*</span></label>
                <select id="user_type" name="user_type" class="form-control" required>
                    <option value="0"><?php echo $lang['normal_user']; ?></option>
                    <option value="1"><?php echo $lang['admin']; ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="school_id"><?php echo $lang['school']; ?></label>
                <select id="school_id" name="school_id" class="form-control">
                    <option value="0"><?php echo $selected_lang === 'kh' ? '-- គ្រប់សាលាទាំងអស់ (Admin) --' : '-- All Schools (Admin) --'; ?></option>
                    <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s['id']; ?>">
                            <?php echo htmlspecialchars($s['school_name_kh'] ?: $s['school_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
            </button>
        </form>
    </div>

    <!-- Users List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-users-gear" style="color: var(--secondary);"></i>
                <span><?php echo $selected_lang === 'kh' ? 'បញ្ជីគណនីអ្នកប្រើប្រាស់' : 'User Accounts Directory'; ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th><?php echo $lang['username']; ?></th>
                        <th><?php echo $lang['user_role']; ?></th>
                        <th><?php echo $lang['school']; ?></th>
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
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <?php if ($row['username'] === get_logged_user()): ?>
                                        <span style="font-size: 11px; color: var(--success);">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['user_type'] == 1): ?>
                                        <span class="badge badge-danger"><i class="fa-solid fa-shield-halved"></i> <?php echo $lang['admin']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-info"><i class="fa-solid fa-user"></i> <?php echo $lang['normal_user']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 13px; color: var(--text-muted);">
                                        <?php echo $row['school_id'] > 0 ? htmlspecialchars($row['school_name_kh'] ?: $row['school_name']) : 'គ្រប់សាលាទាំងអស់'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 12px; color: var(--text-muted);">
                                        <?php echo !empty($row['created_at']) ? khmer_date($row['created_at']) : '-'; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo $lang['edit']; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <?php if ($row['username'] !== get_logged_user()): ?>
                                            <a href="add_users.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
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

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>