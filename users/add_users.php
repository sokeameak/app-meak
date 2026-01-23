<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

// Check if admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
    header("Location: ../dashboard.php");
    exit;
}

// Create users table if not exists
$tableCheck = "CREATE TABLE IF NOT EXISTS tb_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type INT DEFAULT 0
)";
$conn->query($tableCheck);

// Add user_type column if not exists
$colCheck = $conn->query("SHOW COLUMNS FROM tb_users LIKE 'user_type'");
if ($colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE tb_users ADD COLUMN user_type INT DEFAULT 0");
    // Update existing admin to be type 1
    $conn->query("UPDATE tb_users SET user_type = 1 WHERE username = 'admin'");
}

// Add school_id column if not exists
$colCheck = $conn->query("SHOW COLUMNS FROM tb_users LIKE 'school_id'");
if ($colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE tb_users ADD COLUMN school_id INT DEFAULT 0");
}

$error = '';
$success = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 0;
    $school_id = $_POST['school_id'] ?? 0;

    if (empty($username) || empty($password)) {
        $error = "Username and Password are required!";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM tb_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $stmt->close();
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO tb_users (username, password, user_type, school_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $username, $hashed_password, $user_type, $school_id);
            
            if ($stmt->execute()) {
                log_siem_event($conn, $_SESSION['user'], 'ADD_USER', "Added user: $username");
                $success = "User added successfully!";
            } else {
                $error = "Error adding user: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Prevent deleting the currently logged in user (optional safety) or specific admin
    // For now, just delete
    $stmt = $conn->prepare("DELETE FROM tb_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    log_siem_event($conn, $_SESSION['user'], 'DELETE_USER', "Deleted user ID: $id");
    $stmt->close();
    header("Location: add_users.php");
    exit;
}

// Fetch Users
$users = [];
$result = $conn->query("SELECT u.*, s.school_name FROM tb_users u LEFT JOIN tb_schools s ON u.school_id = s.id ORDER BY u.id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch Schools for dropdown
$schools = [];
$schoolRes = $conn->query("SELECT * FROM tb_schools");
if($schoolRes) {
    while($r = $schoolRes->fetch_assoc()) $schools[] = $r;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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
            <h3>Dashboard</h3>
            <a href="../dashboard.php?page=home"><i class="fa-solid fa-home"></i> Home</a>
            <a href="../students/list_student.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="add_users.php" style="background: #34495e;"><i class="fa-solid fa-users-cog"></i> Users</a>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Manage Users</h1>
            </div>

            <div class="card">
                <h2>Add New User</h2>
                <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                <form method="POST">
                    <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                    <div class="form-group">
                        <label>User Type</label>
                        <select name="user_type">
                            <option value="0">Normal User</option>
                            <option value="1">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>School</label>
                        <select name="school_id">
                            <option value="0">All Schools / None</option>
                            <?php foreach($schools as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['school_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Add User</button>
                </form>
            </div>

            <div class="card">
                <h2>User List</h2>
                <table>
                    <thead><tr><th>ID</th><th>Username</th><th>Type</th><th>School</th><th style="width: 150px;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo ($u['user_type'] == 1) ? '<span style="color:green;font-weight:bold;">Admin</span>' : 'Normal User'; ?></td>
                                <td><?php echo htmlspecialchars($u['school_name'] ?? 'All/None'); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="btn" style="background: #27ae60; padding: 5px 10px; font-size: 12px;" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <?php if ($u['username'] !== 'admin' && $u['username'] !== $_SESSION['user']): ?>
                                        <a href="add_users.php?delete=<?php echo $u['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this user?')" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.9em;">(No Delete)</span>
                                    <?php endif; ?>
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