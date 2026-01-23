<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Get current user's school_id
$user_school_id = 0;
$stmt = $conn->prepare("SELECT school_id FROM tb_users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$resUser = $stmt->get_result();
if ($rowUser = $resUser->fetch_assoc()) {
    $user_school_id = $rowUser['school_id'];
}
$stmt->close();

$page = $_GET['page'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management Dashboard</title>
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
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; }
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .student-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .student-card { background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow: hidden; text-align: center; padding-bottom: 15px; transition: transform 0.2s; }
        .student-card:hover { transform: translateY(-5px); }
        .student-photo { width: 100%; height: 200px; object-fit: cover; background-color: #eee; }
        .student-info { padding: 10px; }
        .student-info h3 { margin: 10px 0 5px; font-size: 1.1em; color: #2c3e50; }
        .student-info p { margin: 5px 0; color: #7f8c8d; font-size: 0.9em; }
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
                <div style="color: #bdc3c7; font-size: 0.8em; margin-top: 5px;"><?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1) ? $lang['admin'] : $lang['normal_user']; ?></div>
                <div style="margin-top: 10px;">
                    <a href="<?php echo getUrlWithLang('en'); ?>" style="display:inline; padding:5px; color:white; <?php echo $selected_lang=='en'?'font-weight:bold; text-decoration:underline;':''; ?>">EN</a> | 
                    <a href="<?php echo getUrlWithLang('kh'); ?>" style="display:inline; padding:5px; color:white; <?php echo $selected_lang=='kh'?'font-weight:bold; text-decoration:underline;':''; ?>">KH</a>
                </div>
            </div>
            <h3><?php echo $lang['dashboard']; ?></h3>
            <a href="dashboard.php?page=home"><i class="fa-solid fa-home"></i> <?php echo $lang['home']; ?></a>
            <a href="students/list_student.php"><i class="fa-solid fa-user-graduate"></i> <?php echo $lang['students']; ?></a>
            <a href="students/register_student_study.php"><i class="fa-solid fa-user-plus"></i> <?php echo $lang['add_student']; ?></a>
           <!-- <a href="students/register_student_study.php"><i class="fa-solid fa-registered"></i> <?php echo $lang['register_study']; ?></a> -->
           
             <a href="study/list_study.php"><i class="fa-solid fa-book-open"></i> <?php echo $lang['study']; ?></a>
            <a href="courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> <?php echo $lang['course']; ?></a>
            <a href="time/grades.php"><i class="fa-solid fa-clock"></i> <?php echo $lang['grades']; ?></a>
            <a href="invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> <?php echo $lang['invoices']; ?></a>
             <a href="students/finished_student.php"><i class="fa-solid fa-user-check"></i> <?php echo $lang['finished_students']; ?></a>
            <a href="invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> <?php echo $lang['paid_list']; ?></a>
            <a href="schools/add_school.php"><i class="fa-solid fa-school"></i> <?php echo $lang['schools']; ?></a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="users/add_users.php"><i class="fa-solid fa-users-cog"></i> <?php echo $lang['users']; ?></a>
            <a href="siem.php"><i class="fa-solid fa-shield-halved"></i> SIEM Logs</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> <?php echo $lang['logout']; ?></a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Student Management System</h1>
                <p>Welcome back!</p>
            </div>

            <?php
            switch ($page) {
                case 'home':
                    $chk_studying = true;
                    $chk_finished = false;
                    $filter_time = '';
                    $filter_school = '';

                    // Fetch times for dropdown
                    $times = [];
                    $timeSql = "SELECT id, time FROM tb_time ORDER BY id ASC";
                    $timeResult = $conn->query($timeSql);
                    if ($timeResult) {
                        while ($row = $timeResult->fetch_assoc()) {
                            $times[] = $row;
                        }
                    }

                    // Fetch schools for dropdown
                    $schools = [];
                    $schoolSql = "SELECT id, school_name FROM tb_schools ORDER BY school_name";
                    $schoolResult = $conn->query($schoolSql);
                    if ($schoolResult) {
                        while ($row = $schoolResult->fetch_assoc()) {
                            $schools[] = $row;
                        }
                    }
                    
                    if (isset($_GET['filter'])) {
                        $chk_studying = isset($_GET['chk_studying']);
                        $chk_finished = isset($_GET['chk_finished']);
                        $filter_time = $_GET['filter_time'] ?? '';
                        $filter_school = $_GET['filter_school'] ?? '';
                    }

                    $timeCondition = "";
                    if (!empty($filter_time)) {
                        $timeCondition = " AND id_time = " . intval($filter_time);
                    }

                    $whereConditions = [];
                    if ($chk_studying) {
                        $whereConditions[] = "EXISTS (SELECT 1 FROM tb_study WHERE id_stu = s.ID AND end_date > CURDATE() $timeCondition)";
                    }
                    if ($chk_finished) {
                        $whereConditions[] = "EXISTS (SELECT 1 FROM tb_study WHERE id_stu = s.ID AND end_date <= CURDATE() $timeCondition)";
                    }
                    
                    $whereClause = "";
                    if (!empty($whereConditions)) {
                        $whereClause = " WHERE (" . implode(" OR ", $whereConditions) . ")";
                    } else {
                        $whereClause = " WHERE 1=0";
                    }

                    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 1 && $user_school_id > 0) {
                        if ($whereClause !== " WHERE 1=0") {
                            $whereClause .= " AND s.school_id = " . $user_school_id;
                        }
                    } elseif (!empty($filter_school) && $whereClause !== " WHERE 1=0") {
                        $whereClause .= " AND s.school_id = " . intval($filter_school);
                    }

                    $sql = "SELECT s.*, (SELECT SUM(amount) FROM tb_invoices WHERE student_name = s.student_name AND status = 'Paid') as total_paid, (SELECT SUM(price) FROM tb_study WHERE id_stu = s.ID) as total_study_price, (SELECT GROUP_CONCAT(t.time SEPARATOR ', ') FROM tb_study st JOIN tb_time t ON st.id_time = t.id WHERE st.id_stu = s.ID) as study_times FROM tb_students s $whereClause ORDER BY ID DESC";
                    $result = $conn->query($sql);
                    echo '<div class="card"><h2>Student Overview</h2>';
                    echo '<form method="GET" style="margin-bottom: 20px;">';
                    echo '<input type="hidden" name="page" value="home">';
                    echo '<input type="hidden" name="filter" value="1">';
                    echo '<select name="filter_school" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 15px;">';
                    echo '<option value="">All Schools</option>';
                    foreach ($schools as $s) {
                        $selected = ($filter_school == $s['id']) ? 'selected' : '';
                        echo "<option value='{$s['id']}' $selected>{$s['school_name']}</option>";
                    }
                    echo '</select>';
                    echo '<select name="filter_time" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 15px;">';
                    echo '<option value="">All Times</option>';
                    foreach ($times as $t) {
                        $selected = ($filter_time == $t['id']) ? 'selected' : '';
                        echo "<option value='{$t['id']}' $selected>{$t['time']}</option>";
                    }
                    echo '</select>';
                    echo '<label style="margin-right: 15px; font-weight: bold; color: #2c3e50;"><input type="checkbox" name="chk_studying" ' . ($chk_studying ? 'checked' : '') . '> Studying</label>';
                    echo '<label style="margin-right: 15px; font-weight: bold; color: #2c3e50;"><input type="checkbox" name="chk_finished" ' . ($chk_finished ? 'checked' : '') . '> Finished</label>';
                    echo '<button type="submit" class="btn" style="padding: 5px 10px; font-size: 14px;">Filter</button>';
                    echo '</form>';
                    echo '<div class="student-grid">';
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="student-card">';
                            echo '<a href="invoice/invoice.php?search=' . urlencode($row['student_name']) . '">';
                            if (!empty($row['photo'])) {
                                echo '<img src="uploads/' . htmlspecialchars($row['photo']) . '" alt="Student Photo" class="student-photo">';
                            } else {
                                echo '<div class="student-photo" style="display: flex; align-items: center; justify-content: center; background: #eee; color: #7f8c8d;">';
                                echo 'Add Photo';
                                echo '</div>';
                            }
                            echo '</a>';
                            echo '<div class="student-info">';
                            echo '<h3>' . htmlspecialchars($row['student_name']) . '</h3>';
                            //echo '<p>ID: ' . htmlspecialchars($row['ID']?? '') . '</p>';
                            echo '<p>'  . htmlspecialchars($row['dob']) . '</p>';
                            echo '<p>Time: ' . htmlspecialchars($row['study_times'] ?? 'N/A') . '</p>';
                            echo '<p style="color: #e67e22; font-weight: bold;">Study Price: $' . number_format($row['total_study_price'] ?? 0, 2) . '</p>';
                            echo '<p><a href="invoice/paid.php?search=' . urlencode($row['student_name']) . '" style="color: #27ae60; font-weight: bold; text-decoration: none;">Paid: $' . number_format($row['total_paid'] ?? 0, 2) . '</a></p>';
                            echo '</div></div>';
                        }
                    } else {
                        echo '<p>No students found.</p>';
                    }
                    echo '</div></div>';
                    break;
                case 'students':
                    echo '<div class="card"><h2>Student List</h2>';
                    echo '<table><tr><th>ID</th><th>Name</th><th>Email</th><th style="width: 150px;">Actions</th></tr>';
                    echo '<tr><td>1</td><td>John Doe</td><td>john@example.com</td><td><a href="students/edit_student.php?id=1" class="btn" style="text-decoration: none; display: inline-block;">Edit</a> <button class="btn btn-danger">Delete</button></td></tr>';
                    echo '</table></div>';
                    break;
                case 'add_student':
                    echo '<div class="card"><h2>Add New Student</h2>';
                    echo '<form><input type="text" placeholder="Name" required><input type="email" placeholder="Email" required><button type="submit" class="btn">Add Student</button></form></div>';
                    break;
                case 'grades':
                    echo '<div class="card"><h2>Grades Management</h2><p>View and manage student grades.</p></div>';
                    break;
                default:
                    echo '<div class="card"><h2>Page not found</h2></div>';
            }
            ?>
        </div>
    </div>
</body>
</html>