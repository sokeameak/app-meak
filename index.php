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

// Create tbl_certi if not exists (for displaying on index)
$conn->query("CREATE TABLE IF NOT EXISTS tbl_certi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    study_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure study_id column exists (fix for table schema mismatch)
$colCheck = $conn->query("SHOW COLUMNS FROM tbl_certi LIKE 'study_id'");
if ($colCheck && $colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE tbl_certi ADD COLUMN study_id INT NOT NULL");
}

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

// Fetch Certified Students for Display
$search_query = $_GET['search_query'] ?? '';
$search_sql = "";
if (!empty($search_query)) {
    $search_sql = " AND st.student_name LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}

$cert_students = [];
$certSql = "SELECT s.id as study_id, st.student_name, st.photo, st.sex, st.dob, c.Course, sch.school_name, s.end_date 
            FROM tbl_certi cert 
            JOIN tb_study s ON cert.study_id = s.id 
            JOIN tb_students st ON s.id_stu = st.ID 
            JOIN tb_course c ON s.id_code = c.ID 
            LEFT JOIN tb_schools sch ON st.school_id = sch.id 
            WHERE 1=1 $search_sql
            ORDER BY cert.created_at DESC LIMIT 20";
$certResult = $conn->query($certSql);
if ($certResult) {
    while($row = $certResult->fetch_assoc()) {
        $cert_students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>មាគ៌គាកុំព្យូទ័រ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, 'Khmer OS', sans-serif; background: #f4f4f4; display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding: 40px 20px; }
        
        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
        }
        .login-card { 
            background: white; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
            margin: 10vh auto; 
            position: relative;
            animation: animatezoom 0.4s;
        }
        @keyframes animatezoom {
            from {transform: scale(0)} 
            to {transform: scale(1)}
        }

        .login-card h2 { margin-bottom: 20px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #3498db; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #2980b9; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; font-size: 14px; }
        .footer { margin-top: 20px; font-size: 12px; color: #7f8c8d; }
        
        .cert-section { width: 100%; max-width: 1200px; }
        .cert-header { text-align: center; margin-bottom: 30px; color: #2c3e50; font-family: 'Khmer OS Muol light'; }
        .search-container { text-align: center; margin-bottom: 30px; }
        .search-container input { padding: 12px; width: 100%; max-width: 400px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .search-container button { padding: 12px 25px; background: #2c3e50; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; margin-left: 10px; }
        .search-container button:hover { background: #34495e; }

        .cert-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .cert-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; flex-direction: column; align-items: center; text-align: center; gap: 15px; transition: transform 0.2s; border-top: 4px solid #3498db; }
        .cert-card:hover { transform: translateY(-5px); }
        .cert-photo { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #f0f0f0; margin-bottom: 10px; }
        .cert-info { width: 100%; }
        .cert-info h4 { margin: 0 0 10px; color: #2c3e50; font-size: 18px; font-weight: bold; }
        .cert-info p { margin: 5px 0; color: #7f8c8d; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-view { display: inline-block; margin-top: 15px; padding: 8px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 20px; font-size: 14px; transition: background 0.3s; }
        .btn-view:hover { background: #2980b9; }
        
        .top-nav { width: 100%; display: flex; justify-content: flex-end; position: absolute; top: 0; right: 0; padding: 20px; }
        .btn-login-popup { padding: 10px 25px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-login-popup:hover { background: #34495e; }
        .close-modal { position: absolute; right: 15px; top: 10px; font-size: 30px; font-weight: bold; color: #aaa; cursor: pointer; }
        .close-modal:hover { color: #000; }
    </style>
</head>
<body>
    <div class="top-nav">
        <button class="btn-login-popup" onclick="document.getElementById('loginModal').style.display='block'"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
    </div>

    <div id="loginModal" class="modal">
    <div class="login-card">
        <span onclick="document.getElementById('loginModal').style.display='none'" class="close-modal">&times;</span>
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
    </div>

    <?php if (!empty($cert_students) || !empty($search_query)): ?>
    <div class="cert-section" id="cert-section">
        <h2 class="cert-header">បញ្ជីសិស្សបញ្ចប់ការសិក្សា (Certified Students)</h2>
        
        <div class="search-container">
            <form method="GET" action="#cert-section">
                <input type="text" name="search_query" placeholder="ស្វែងរកឈ្មោះសិស្ស (Search Student Name)..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit"><i class="fa-solid fa-search"></i> Search</button>
            </form>
        </div>

        <div class="cert-grid">
            <?php foreach ($cert_students as $stu): ?>
                <div class="cert-card">
                    <?php if (!empty($stu['photo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($stu['photo']); ?>" alt="Student" class="cert-photo">
                    <?php else: ?>
                        <div class="cert-photo" style="background: #ecf0f1; display: flex; align-items: center; justify-content: center; color: #7f8c8d; font-size: 40px;"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div class="cert-info">
                        <h4><?php echo htmlspecialchars($stu['student_name']); ?></h4>
                        <p><i class="fa-solid fa-venus-mars"></i> <?php echo htmlspecialchars($stu['sex'] ?? 'N/A'); ?></p>
                        <p><i class="fa-solid fa-birthday-cake"></i> <?php echo htmlspecialchars($stu['dob'] ?? 'N/A'); ?></p>
                        <p><i class="fa-solid fa-book"></i> <?php echo htmlspecialchars($stu['Course']); ?></p>
                        <p><i class="fa-solid fa-school"></i> <?php echo htmlspecialchars($stu['school_name'] ?? 'N/A'); ?></p>
                        <p><i class="fa-solid fa-graduation-cap"></i> Finished: <?php echo htmlspecialchars($stu['end_date']); ?></p>
                        <a href="view_certificate.php?id=<?php echo $stu['study_id']; ?>" target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i> View Certificate</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($cert_students)): ?>
                <p style="text-align: center; grid-column: 1/-1; color: #7f8c8d;">No student found with that name.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Get the modal
        var modal = document.getElementById('loginModal');
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        // Keep modal open if there is a login error
        <?php if ($error): ?>
            modal.style.display = "block";
        <?php endif; ?>
    </script>
</body>
</html>