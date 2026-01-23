<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Create schools table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS tb_schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255),
    school_name_kh VARCHAR(255),
    logo VARCHAR(255)
)");

$edit_school = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM tb_schools WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_school = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle Add/Update School
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $school_name = $_POST['school_name'] ?? '';
    $school_name_kh = $_POST['school_name_kh'] ?? '';
    $school_id = $_POST['school_id'] ?? '';
    $logo = '';

    if (empty($school_name) || empty($school_name_kh)) {
        $error = "School Name (English & Khmer) are required!";
    } else {
        // Handle Logo Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $target_dir = "../logo/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_dir . $new_filename)) {
                $logo = $new_filename;
            }
        }

        if (!empty($school_id)) {
            // Update
            if ($logo) {
                $stmt = $conn->prepare("UPDATE tb_schools SET school_name=?, school_name_kh=?, logo=? WHERE id=?");
                $stmt->bind_param("sssi", $school_name, $school_name_kh, $logo, $school_id);
            } else {
                $stmt = $conn->prepare("UPDATE tb_schools SET school_name=?, school_name_kh=? WHERE id=?");
                $stmt->bind_param("ssi", $school_name, $school_name_kh, $school_id);
            }
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO tb_schools (school_name, school_name_kh, logo) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $school_name, $school_name_kh, $logo);
        }
        
        if ($stmt->execute()) {
            if (!empty($school_id)) {
                log_siem_event($conn, $_SESSION['user'], 'UPDATE_SCHOOL', "Updated school ID: $school_id");
                header("Location: add_school.php");
                exit;
            }
            log_siem_event($conn, $_SESSION['user'], 'ADD_SCHOOL', "Added school: $school_name");
            $success = "School added successfully!";
        } else {
            $error = "Error saving school: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete School
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tb_schools WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    log_siem_event($conn, $_SESSION['user'], 'DELETE_SCHOOL', "Deleted school ID: $id");
    $stmt->close();
    header("Location: add_school.php");
    exit;
}

// Fetch Schools
$schools = [];
$result = $conn->query("SELECT * FROM tb_schools ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $schools[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schools</title>
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
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; padding: 5px 10px; font-size: 12px; }
        .btn-danger:hover { background: #c0392b; }
        .btn-edit { background: #27ae60; padding: 5px 10px; font-size: 12px; margin-right: 5px; }
        .btn-edit:hover { background: #229954; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; }
        .error { color: #c0392b; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
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
                <div style="color: #bdc3c7; font-size: 0.8em; margin-top: 5px;"><?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1) ? 'Administrator' : 'Normal User'; ?></div>
            </div>
            <h3>Dashboard</h3>
            <a href="../dashboard.php?page=home"><i class="fa-solid fa-home"></i> Home</a>
            <a href="../students/list_student.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="../students/register_student.php"><i class="fa-solid fa-user-plus"></i> Add Student</a>
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="../students/finished_student.php"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <a href="add_school.php" style="background: #34495e;"><i class="fa-solid fa-school"></i> Schools</a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Manage Schools</h1>
            </div>

            <div class="card">
                <h2><?php echo $edit_school ? 'Edit School' : 'Add New School'; ?></h2>
                <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_school): ?>
                        <input type="hidden" name="school_id" value="<?php echo $edit_school['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group"><label>School Name (English)</label><input type="text" name="school_name" required placeholder="e.g., Meakea Computer" value="<?php echo htmlspecialchars($edit_school['school_name'] ?? ''); ?>"></div>
                    <div class="form-group"><label>School Name (Khmer)</label><input type="text" name="school_name_kh" required placeholder="e.g., មាគ៌ាកុំព្យូទ័រ" value="<?php echo htmlspecialchars($edit_school['school_name_kh'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Logo</label>
                    <?php if (!empty($edit_school['logo'])): ?><img src="../logo/<?php echo htmlspecialchars($edit_school['logo']); ?>" width="50" style="vertical-align: middle; margin-right: 10px;"><?php endif; ?>
                    <input type="file" name="logo" accept="image/*"></div>
                    <button type="submit" class="btn"><?php echo $edit_school ? 'Update School' : 'Add School'; ?></button>
                    <?php if ($edit_school): ?><a href="add_school.php" class="btn" style="background: #95a5a6; text-decoration: none;">Cancel</a><?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h2>School List</h2>
                <table>
                    <thead><tr><th>ID</th><th>Logo</th><th>Name (EN)</th><th>Name (KH)</th><th style="width: 150px;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($schools as $s): ?>
                            <tr>
                                <td><?php echo $s['id']; ?></td>
                                <td>
                                    <?php if (!empty($s['logo'])): ?>
                                        <img src="../logo/<?php echo htmlspecialchars($s['logo']); ?>" width="50" style="object-fit: contain;">
                                    <?php else: ?>
                                        No Logo
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($s['school_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['school_name_kh']); ?></td>
                                <td>
                                    <a href="add_school.php?edit=<?php echo $s['id']; ?>" class="btn btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="add_school.php?delete=<?php echo $s['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this school?')" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>