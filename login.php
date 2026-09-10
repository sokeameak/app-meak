<?php
// login.php - Secure User Login Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';

// Check if already logged in
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = $lang['fill_required'];
    } else {
        $stmt = $conn->prepare("SELECT * FROM tb_users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Login Success
                    $_SESSION['user'] = $user['username'];
                    $_SESSION['user_type'] = intval($user['user_type']);
                    $_SESSION['school_id'] = intval($user['school_id']);
                    
                    log_siem_event($conn, $user['username'], 'LOGIN_SUCCESS', 'User logged in successfully');
                    
                    set_flash('success', $lang['login_success'] . ', ' . $user['username']);
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = $lang['login_invalid'];
                    log_siem_event($conn, $username, 'LOGIN_FAILED', 'Invalid password attempt');
                }
            } else {
                $error = $lang['login_invalid'];
                log_siem_event($conn, $username, 'LOGIN_FAILED', 'Username not found');
            }
            $stmt->close();
        } else {
            $error = $lang['error_occurred'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $selected_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['login']; ?> - <?php echo $lang['app_name']; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@400;600;700&family=Moul&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Kantumruy Pro', 'Battambang', Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0369a1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.2), 0 8px 10px -6px rgb(0 0 0 / 0.2);
            text-align: center;
            position: relative;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .brand-title {
            font-family: 'Moul', 'Khmer OS Muol light', serif;
            font-size: 22px;
            color: #1e3a8a;
            margin-bottom: 6px;
        }

        .brand-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 24px;
        }

        .lang-switch {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 12px;
        }

        .lang-switch a {
            text-decoration: none;
            color: #64748b;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .lang-switch a.active {
            background: #e2e8f0;
            color: #1e293b;
        }

        .form-group {
            text-align: left;
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 15px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #fecaca;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .public-link {
            margin-top: 24px;
            font-size: 13px;
            color: #64748b;
        }

        .public-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="lang-switch">
            <a href="?lang=kh" class="<?php echo $selected_lang === 'kh' ? 'active' : ''; ?>">KH</a>
            <a href="?lang=en" class="<?php echo $selected_lang === 'en' ? 'active' : ''; ?>">EN</a>
        </div>

        <img src="logo/logo.png" alt="Logo" class="brand-logo" onerror="this.src='logo/logo.png';">
        <h1 class="brand-title"><?php echo $lang['app_name']; ?></h1>
        <p class="brand-subtitle"><?php echo $lang['app_subtitle']; ?></p>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username"><?php echo $lang['username']; ?></label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="<?php echo $lang['username']; ?>..." required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password"><?php echo $lang['password']; ?></label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="<?php echo $lang['password']; ?>..." required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> <?php echo $lang['login']; ?>
            </button>
        </form>

        <div class="public-link">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> <?php echo $selected_lang === 'kh' ? 'ទៅកាន់ទំព័រផ្ទៀងផ្ទាត់សញ្ញាបត្រ' : 'Back to Certificate Verification'; ?></a>
        </div>
    </div>

</body>
</html>