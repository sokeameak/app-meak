<?php
session_start();
include 'db_connect.php';

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Create users table if not exists
$tableCheck = "CREATE TABLE IF NOT EXISTS tb_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type INT DEFAULT 0
)";
$conn->query($tableCheck);

// Check if admin user exists, if not create one (default: admin/admin)
$checkAdmin = "SELECT * FROM tb_users WHERE username = 'adminmeakea'";
$result = $conn->query($checkAdmin);
if ($result->num_rows == 0) {
    $defaultPass = password_hash('Meakkea@0968689680', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO tb_users (username, password, user_type) VALUES ('adminmeakea', '$defaultPass', 1)");
}

// Temporary: Reset admin password to 'admin'. Remove this block after use.
$resetPass = password_hash('Meakkea@0968689680', PASSWORD_DEFAULT);
$conn->query("UPDATE tb_users SET password = '$resetPass' WHERE username = 'adminmeakea'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM tb_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                log_siem_event($conn, $user['username'], 'LOGIN', 'User logged in successfully');
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid password!";
                log_siem_event($conn, $username, 'LOGIN_FAILED', 'Invalid password attempt');
            }
        } else {
            $error = "User not found!";
            log_siem_event($conn, $username, 'LOGIN_FAILED', 'User not found');
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>មាគ៌គាកុំព្យូទ័រ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, 'Khmer OS', sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .login-card h2 { margin-bottom: 20px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #3498db; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #2980b9; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; font-size: 14px; }
        .footer { margin-top: 20px; font-size: 12px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2​ style="font-family: 'Khmer OS Muol light';font-size: 30px;color: #1a0285;">មាគ៌ាកុំព្យូទ័រ </h2>
        <hr>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" >
            <div class="form-group">
                <label for="username" style="font-family: 'Khmer OS';font-size: 20px;">ឈ្មោះ អ្នកប្រើ</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>
            </div>
            <div class="form-group">
                <label for="password" style="font-family: 'Khmer OS';font-size: 20px;">លេខកូដសម្ងាត់</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn"​ style="font-family: 'Khmer OS';font-size: 20px;">ចូលប្រើ ប្រព័ន្ទ</button>
        </form>

        <div class="footer">
           
        </div>
    </div>
</body>
</html>