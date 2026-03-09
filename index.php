<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - មាគ៌ាកុំព្យូទ័រ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, 'Khmer OS', sans-serif; background: #f4f4f4; }
        .welcome-card { 
            background: white; 
            text-align: center; 
            width: 100%; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            position: relative;
        }
        .head {
            /* Semantic container for the navbar */
        }
        .navbar { position: absolute; top: 0; left: 0; width: 100%; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        .nav-links a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
            font-size: 16px;
            transition: color 0.3s;
        }
        .nav-links a:hover { color: #3498db; }
        
        .main {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .main h1 { color: #1a0285; font-family: 'Khmer OS Muol light'; font-size: 36px; margin-bottom: 20px; }
        .main p { color: #7f8c8d; font-size: 18px; margin-bottom: 40px; }

        .footer {
            padding: 20px;
            font-size: 14px;
            color: #7f8c8d;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .btn { display: inline-block; padding: 10px 25px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; font-family: 'Khmer OS'; transition: background 0.3s; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="welcome-card">
        <header class="head">
            <nav class="navbar">
                <div class="nav-links">
                    <a href="index.php">Home</a>
                    <a href="index.php">Student</a>
                    <a href="#">about me</a>
                </div>
                <a href="login.php" class="btn">ចូលប្រើ ប្រព័ន្ទ (Login)</a>
            </nav>
        </header>
        <main class="main">
            <h1>មាគ៌ាកុំព្យូទ័រ</h1>
            <p>Welcome to Student Management System</p>
        </main>
        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Meakea Computer. All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>