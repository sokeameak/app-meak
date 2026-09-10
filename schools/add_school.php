<?php
// schools/add_school.php - School & Branch Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';
require_login();

$isAdmin = is_admin();
$page_title = $lang['schools'];
$page_subtitle = $lang['app_name'];

$error = '';
$edit_school = null;

// Handle Edit Mode
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM tb_schools WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $edit_school = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle Form Submission (Add / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        set_flash('danger', 'Unauthorized action');
        header("Location: add_school.php");
        exit;
    }

    $school_name = trim($_POST['school_name'] ?? '');
    $school_name_kh = trim($_POST['school_name_kh'] ?? '');
    $school_id = intval($_POST['school_id'] ?? 0);
    $logoName = $edit_school['logo'] ?? 'meakea.png';

    if (empty($school_name) || empty($school_name_kh)) {
        $error = $lang['fill_required'];
    } else {
        try {
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../logo/';
                $newLogo = safe_image_upload($_FILES['logo'], $targetDir, 5);
                if ($newLogo) {
                    $logoName = $newLogo;
                }
            }

            if ($school_id > 0) {
                // Update
                $stmtUp = $conn->prepare("UPDATE tb_schools SET school_name = ?, school_name_kh = ?, logo = ? WHERE id = ?");
                $stmtUp->bind_param("sssi", $school_name, $school_name_kh, $logoName, $school_id);
                if ($stmtUp->execute()) {
                    log_siem_event($conn, get_logged_user(), 'UPDATE_SCHOOL', "Updated school ID: $school_id ($school_name)");
                    set_flash('success', $lang['saved_success']);
                    header("Location: add_school.php");
                    exit;
                } else {
                    $error = "Error: " . $stmtUp->error;
                }
                $stmtUp->close();
            } else {
                // Insert
                $stmtIns = $conn->prepare("INSERT INTO tb_schools (school_name, school_name_kh, logo) VALUES (?, ?, ?)");
                $stmtIns->bind_param("sss", $school_name, $school_name_kh, $logoName);
                if ($stmtIns->execute()) {
                    log_siem_event($conn, get_logged_user(), 'ADD_SCHOOL', "Added school: $school_name");
                    set_flash('success', $lang['saved_success']);
                    header("Location: add_school.php");
                    exit;
                } else {
                    $error = "Error: " . $stmtIns->error;
                }
                $stmtIns->close();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle Delete School
if (isset($_GET['delete'])) {
    if (!$isAdmin) {
        set_flash('danger', 'Unauthorized action');
    } else {
        $delId = intval($_GET['delete']);
        
        // Check student count
        $stmtCount = $conn->prepare("SELECT COUNT(*) as count FROM tb_students WHERE school_id = ?");
        $stmtCount->bind_param("i", $delId);
        $stmtCount->execute();
        $stuCount = $stmtCount->get_result()->fetch_assoc()['count'] ?? 0;
        $stmtCount->close();

        if ($stuCount > 0) {
            set_flash('danger', "មិនអាចលុបសាលានេះបានទេ ព្រោះមានសិស្សចំនួន $stuCount នាក់!");
        } else {
            $stmtDel = $conn->prepare("DELETE FROM tb_schools WHERE id = ?");
            $stmtDel->bind_param("i", $delId);
            if ($stmtDel->execute()) {
                log_siem_event($conn, get_logged_user(), 'DELETE_SCHOOL', "Deleted school ID: $delId");
                set_flash('success', $lang['deleted_success']);
            }
            $stmtDel->close();
        }
    }
    header("Location: add_school.php");
    exit;
}

// Fetch All Schools
$schools = [];
$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM tb_students WHERE school_id = s.id) as student_count,
        (SELECT COUNT(*) FROM tb_users WHERE school_id = s.id) as user_count
        FROM tb_schools s 
        ORDER BY s.id ASC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">

    <!-- Add/Edit School Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-school" style="color: var(--secondary);"></i>
                <span><?php echo $edit_school ? $lang['edit'] : $lang['add_new']; ?> <?php echo $lang['schools']; ?></span>
            </div>
            <?php if ($edit_school): ?>
                <a href="add_school.php" class="btn btn-light btn-sm"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <form method="POST" action="add_school.php" enctype="multipart/form-data">
                <?php if ($edit_school): ?>
                    <input type="hidden" name="school_id" value="<?php echo $edit_school['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="school_name_kh">ឈ្មោះសាលា (ភាសាខ្មែរ) <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="school_name_kh" name="school_name_kh" class="form-control" value="<?php echo htmlspecialchars($_POST['school_name_kh'] ?? ($edit_school['school_name_kh'] ?? '')); ?>" required placeholder="e.g. មាគ៌ាកុំព្យូទ័រ">
                </div>

                <div class="form-group">
                    <label for="school_name">ឈ្មោះសាលា (English) <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="school_name" name="school_name" class="form-control" value="<?php echo htmlspecialchars($_POST['school_name'] ?? ($edit_school['school_name'] ?? '')); ?>" required placeholder="e.g. Meakea Computer">
                </div>

                <div class="form-group">
                    <label for="logo">Logo រូបសញ្ញា (PNG, JPG)</label>
                    <?php if (!empty($edit_school['logo'])): ?>
                        <div style="margin-bottom: 8px;">
                            <img src="../logo/<?php echo htmlspecialchars($edit_school['logo']); ?>" alt="Current Logo" style="width: 50px; height: 50px; object-fit: contain; background: #f1f5f9; padding: 4px; border-radius: 6px; border: 1px solid var(--border-color);">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="logo" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                    <i class="fa-solid fa-floppy-disk"></i> <?php echo $lang['save']; ?>
                </button>
            </form>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 13px;">មានតែ Administrator ប៉ុណ្ណោះដែលអាចបង្កើត ឬកែប្រែព័ត៌មានសាលាបាន។</p>
        <?php endif; ?>
    </div>

    <!-- Schools List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-list" style="color: var(--secondary);"></i>
                <span><?php echo $selected_lang === 'kh' ? 'បញ្ជីសាលា និងសាខា' : 'Schools & Branches'; ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Logo</th>
                        <th>ឈ្មោះសាលា (ខ្មែរ / English)</th>
                        <th style="width: 100px;"><?php echo $lang['students']; ?></th>
                        <th style="width: 100px;"><?php echo $lang['users']; ?></th>
                        <?php if ($isAdmin): ?>
                            <th style="width: 120px; text-align: center;"><?php echo $lang['actions']; ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../logo/<?php echo htmlspecialchars($row['logo'] ?: 'logo.png'); ?>" alt="Logo" style="width: 40px; height: 40px; object-fit: contain; background: #f8fafc; border-radius: 6px; border: 1px solid var(--border-color); padding: 2px;" onerror="this.src='../logo/logo.png';">
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); font-size: 15px;">
                                        <?php echo htmlspecialchars($row['school_name_kh'] ?: $row['school_name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($row['school_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $row['student_count']; ?> នាក់</span>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?php echo $row['user_count']; ?> នាក់</span>
                                </td>
                                <?php if ($isAdmin): ?>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; gap: 4px;">
                                            <a href="add_school.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo $lang['edit']; ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="add_school.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo addslashes($lang['confirm_delete']); ?>')" title="<?php echo $lang['delete']; ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
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