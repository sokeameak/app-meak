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

$id = $_GET['id'] ?? '';
$error = '';
$success = '';

if (empty($id)) {
    header("Location: add_users.php");
    exit;
}

// Fetch user data
$user = [];
$stmt = $conn->prepare("SELECT * FROM tb_users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}
$stmt->close();

// Fetch Schools for dropdown
$schools = [];
$schoolRes = $conn->query("SELECT * FROM tb_schools");
if($schoolRes) {
    while($r = $schoolRes->fetch_assoc()) $schools[] = $r;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 0;
    $school_id = $_POST['school_id'] ?? 0;

    if (empty($username)) {
        $error = "Username is required!";
    } else {
        // Check if username exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM tb_users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $stmt->close();
            
            if (!empty($password)) {
                // Update with password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE tb_users SET username = ?, password = ?, user_type = ?, school_id = ? WHERE id = ?");
                $stmt->bind_param("ssiii", $username, $hashed_password, $user_type, $school_id, $id);
            } else {
                // Update without password
                $stmt = $conn->prepare("UPDATE tb_users SET username = ?, user_type = ?, school_id = ? WHERE id = ?");
                $stmt->bind_param("siii", $username, $user_type, $school_id, $id);
            }

            if ($stmt->execute()) {
                $success = "User updated successfully!";
                // Refresh data
                $user['username'] = $username;
                $user['user_type'] = $user_type;
                $user['school_id'] = $school_id;
            } else {
                $error = "Error updating user: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-back { background: #95a5a6; }
        .btn-back:hover { background: #7f8c8d; }
        .error { color: #c0392b; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h3>Dashboard</h3>
            <a href="../dashboard.php?page=home"><i class="fa-solid fa-home"></i> Home</a>
            <a href="add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Edit User</h1>
            </div>

            <div class="card">
                <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                <form method="POST">
                    <div class="form-group"><label>Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                    <div class="form-group"><label>Password (Leave blank to keep current)</label><input type="password" name="password"></div>
                    <div class="form-group">
                        <label>User Type</label>
                        <select name="user_type">
                            <option value="0" <?php echo ($user['user_type'] == 0) ? 'selected' : ''; ?>>Normal User</option>
                            <option value="1" <?php echo ($user['user_type'] == 1) ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>School</label>
                        <select name="school_id">
                            <option value="0">All Schools / None</option>
                            <?php foreach($schools as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo (($user['school_id'] ?? 0) == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['school_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Update User</button>
                    <a href="add_users.php" class="btn btn-back">Back</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>