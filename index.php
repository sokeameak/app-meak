<?php
// index.php - Public Certificate Verification Portal & Staff Login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';

// Handle Quick Staff Login Modal POST
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $login_error = $lang['fill_required'];
    } else {
        $stmt = $conn->prepare("SELECT * FROM tb_users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user['username'];
                    $_SESSION['user_type'] = intval($user['user_type']);
                    $_SESSION['school_id'] = intval($user['school_id']);
                    log_siem_event($conn, $user['username'], 'LOGIN_SUCCESS', 'User logged in from index portal');
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $login_error = $lang['login_invalid'];
                    log_siem_event($conn, $username, 'LOGIN_FAILED', 'Invalid password from index');
                }
            } else {
                $login_error = $lang['login_invalid'];
                log_siem_event($conn, $username, 'LOGIN_FAILED', 'User not found from index');
            }
            $stmt->close();
        }
    }
}

// Fetch Certified Students
$search_query = trim($_GET['search_query'] ?? '');
$cert_students = [];

$sql = "SELECT s.id as study_id, st.ID as student_id, st.student_name, st.photo, st.sex, st.dob, c.Course, sch.school_name, sch.school_name_kh, s.end_date 
        FROM tbl_certi cert 
        JOIN tb_study s ON cert.study_id = s.id 
        JOIN tb_students st ON s.id_stu = st.ID 
        JOIN tb_course c ON s.id_code = c.ID 
        LEFT JOIN tb_schools sch ON st.school_id = sch.id 
        WHERE 1=1 ";

$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " AND (st.student_name LIKE ? OR st.ID = ? OR s.id = ?) ";
    $searchTerm = "%" . $search_query . "%";
    $searchId = intval($search_query);
    $params[] = $searchTerm;
    $params[] = $searchId;
    $params[] = $searchId;
    $types .= "sii";
}

$sql .= " ORDER BY cert.id DESC LIMIT 30";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cert_students[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $selected_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['app_name']; ?> - <?php echo $selected_lang === 'kh' ? 'ផ្ទៀងផ្ទាត់សញ្ញាបត្រ' : 'Certificate Verification'; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@300;400;600;700&family=Moul&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #1e3a8a;
            --primary-dark: #0f172a;
            --accent: #2563eb;
            --gold: #d97706;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --font-khmer: 'Kantumruy Pro', 'Battambang', Arial, sans-serif;
            --font-muol: 'Moul', 'Khmer OS Muol light', serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-khmer);
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation */
        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-brand img {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            object-fit: cover;
        }

        .nav-brand-text h1 {
            font-family: var(--font-muol);
            font-size: 18px;
            color: var(--primary);
            line-height: 1.2;
        }

        .nav-brand-text span {
            font-size: 12px;
            color: var(--text-muted);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lang-switch a {
            text-decoration: none;
            color: var(--text-muted);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        .lang-switch a.active {
            background: #e2e8f0;
            color: var(--primary);
        }

        .btn-login {
            padding: 8px 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: var(--accent);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, var(--accent) 100%);
            color: white;
            text-align: center;
            padding: 60px 20px 70px;
            position: relative;
        }

        .hero h2 {
            font-family: var(--font-muol);
            font-size: 28px;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 16px;
            color: #cbd5e1;
            max-width: 600px;
            margin: 0 auto 30px;
        }

        .search-box {
            max-width: 620px;
            margin: 0 auto;
            display: flex;
            background: white;
            padding: 6px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }

        .search-box input {
            flex: 1;
            border: none;
            padding: 12px 18px;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            border-radius: 8px;
        }

        .search-box button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .search-box button:hover {
            background: #1d4ed8;
        }

        /* Certificate Grid */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            flex: 1;
            width: 100%;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cert-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .cert-card {
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 24px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--accent);
        }

        .cert-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px -5px rgba(0,0,0,0.1);
        }

        .cert-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e2e8f0;
            margin-bottom: 14px;
            background: #f1f5f9;
        }

        .cert-photo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 3px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 36px;
            margin-bottom: 14px;
        }

        .cert-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .cert-info-item {
            font-size: 13px;
            color: var(--text-muted);
            margin: 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cert-course {
            font-weight: 600;
            color: #b45309;
            background: #fef3c7;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin: 10px 0;
        }

        .btn-view-cert {
            margin-top: 14px;
            padding: 8px 18px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .btn-view-cert:hover {
            background: var(--accent);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-card {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 34px 28px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);
            text-align: center;
            position: relative;
            animation: zoomIn 0.25s ease-out;
        }

        @keyframes zoomIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            background: none;
            border: none;
            font-size: 22px;
            color: #94a3b8;
            cursor: pointer;
        }

        .modal-close:hover { color: #1e293b; }

        .form-group {
            text-align: left;
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn-submit:hover { background: var(--accent); }

        footer {
            background: white;
            border-top: 1px solid var(--border);
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <img src="logo/meakea.png" alt="Logo" onerror="this.src='logo/meakea.jpg';">
            <div class="nav-brand-text">
                <h1><?php echo $lang['app_name']; ?></h1>
                <span><?php echo $lang['app_subtitle']; ?></span>
            </div>
        </a>

        <div class="nav-actions">
            <div class="lang-switch">
                <a href="?lang=kh" class="<?php echo $selected_lang === 'kh' ? 'active' : ''; ?>">KH</a>
                <a href="?lang=en" class="<?php echo $selected_lang === 'en' ? 'active' : ''; ?>">EN</a>
            </div>

            <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="btn-login">
                    <i class="fa-solid fa-gauge-high"></i> <?php echo $lang['dashboard']; ?>
                </a>
            <?php else: ?>
                <button type="button" class="btn-login" onclick="openLoginModal()">
                    <i class="fa-solid fa-right-to-bracket"></i> <?php echo $lang['login']; ?>
                </button>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Search -->
    <section class="hero">
        <h2><?php echo $selected_lang === 'kh' ? 'ប្រព័ន្ធផ្ទៀងផ្ទាត់លិខិតបញ្ជាក់ការសិក្សា' : 'Student Certificate Verification'; ?></h2>
        <p><?php echo $selected_lang === 'kh' ? 'សូមបញ្ចូលឈ្មោះសិស្ស ឬលេខសម្គាល់ ដើម្បីស្វែងរក និងផ្ទៀងផ្ទាត់សញ្ញាបត្រ' : 'Enter student name or certificate ID to verify graduation records'; ?></p>

        <form method="GET" action="index.php" class="search-box">
            <input type="text" name="search_query" placeholder="<?php echo $selected_lang === 'kh' ? 'ស្វែងរកតាមឈ្មោះសិស្ស ឬលេខកូដ...' : 'Search student name or ID...'; ?>" value="<?php echo htmlspecialchars($search_query); ?>" required>
            <button type="submit">
                <i class="fa-solid fa-magnifying-glass"></i> <?php echo $lang['search']; ?>
            </button>
        </form>
    </section>

    <!-- Certificate List -->
    <main class="container">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fa-solid fa-award" style="color: var(--gold);"></i>
                <?php echo $selected_lang === 'kh' ? 'បញ្ជីសិស្សទទួលបានលិខិតបញ្ជាក់ការសិក្សា' : 'Certified Students Directory'; ?>
            </h3>
            <?php if (!empty($search_query)): ?>
                <a href="index.php" class="btn-login" style="background: #64748b; font-size: 12px; padding: 6px 12px;">
                    <i class="fa-solid fa-xmark"></i> <?php echo $selected_lang === 'kh' ? 'សម្អាតការស្វែងរក' : 'Clear Search'; ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($cert_students)): ?>
            <div class="cert-grid">
                <?php foreach ($cert_students as $stu): ?>
                    <div class="cert-card">
                        <?php if (!empty($stu['photo'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($stu['photo']); ?>" alt="Student" class="cert-photo" onerror="this.outerHTML='<div class=\'cert-photo-placeholder\'><i class=\'fa-solid fa-user\'></i></div>';">
                        <?php else: ?>
                            <div class="cert-photo-placeholder"><i class="fa-solid fa-user"></i></div>
                        <?php endif; ?>

                        <h4 class="cert-name"><?php echo htmlspecialchars($stu['student_name']); ?></h4>
                        
                        <div class="cert-course">
                            <i class="fa-solid fa-book-open"></i> <?php echo htmlspecialchars($stu['Course']); ?>
                        </div>

                        <div class="cert-info-item">
                            <i class="fa-solid fa-venus-mars"></i>
                            <span><?php echo khmer_gender($stu['sex']); ?></span>
                        </div>

                        <div class="cert-info-item">
                            <i class="fa-solid fa-cake-candles"></i>
                            <span><?php echo khmer_date($stu['dob']); ?></span>
                        </div>

                        <div class="cert-info-item">
                            <i class="fa-solid fa-school"></i>
                            <span><?php echo htmlspecialchars($stu['school_name_kh'] ?: ($stu['school_name'] ?: 'Meakea Computer')); ?></span>
                        </div>

                        <div class="cert-info-item">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span><?php echo $selected_lang === 'kh' ? 'បញ្ចប់៖ ' : 'Finished: '; ?><?php echo khmer_date($stu['end_date']); ?></span>
                        </div>

                        <a href="view_certificate.php?id=<?php echo $stu['study_id']; ?>" target="_blank" class="btn-view-cert">
                            <i class="fa-solid fa-print"></i> <?php echo $lang['view_cert']; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: white; border-radius: 12px; border: 1px dashed var(--border); padding: 50px 20px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size: 48px; color: #cbd5e1; margin-bottom: 12px;"></i>
                <p><?php echo $lang['no_records']; ?></p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="modal <?php echo !empty($login_error) ? 'active' : ''; ?>">
        <div class="modal-card">
            <button type="button" class="modal-close" onclick="closeLoginModal()">&times;</button>
            
            <img src="logo/meakea.png" alt="Logo" style="width: 60px; height: 60px; border-radius: 12px; margin-bottom: 12px;" onerror="this.src='logo/meakea.jpg';">
            <h3 style="font-family: var(--font-muol); color: var(--primary); font-size: 18px; margin-bottom: 6px;"><?php echo $lang['app_name']; ?></h3>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;"><?php echo $lang['login']; ?> គ្រប់គ្រងទិន្នន័យ</p>

            <?php if (!empty($login_error)): ?>
                <div style="background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; border: 1px solid #fecaca; text-align: left;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="form-group">
                    <label for="modalUsername"><?php echo $lang['username']; ?></label>
                    <input type="text" id="modalUsername" name="username" class="form-control" required placeholder="<?php echo $lang['username']; ?>...">
                </div>

                <div class="form-group">
                    <label for="modalPassword"><?php echo $lang['password']; ?></label>
                    <input type="password" id="modalPassword" name="password" class="form-control" required placeholder="<?php echo $lang['password']; ?>...">
                </div>

                <button type="submit" name="login_submit" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i> <?php echo $lang['login']; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <strong><?php echo $lang['app_name']; ?></strong> - <?php echo $lang['app_subtitle']; ?>. All rights reserved.</p>
    </footer>

    <script>
        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
        }
        function closeLoginModal() {
            document.getElementById('loginModal').classList.remove('active');
        }
        window.onclick = function(event) {
            var modal = document.getElementById('loginModal');
            if (event.target === modal) {
                closeLoginModal();
            }
        };
    </script>
</body>
</html>