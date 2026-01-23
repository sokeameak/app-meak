<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
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

$whereSQL = "";
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 1 && $user_school_id > 0) {
    $whereSQL = " WHERE school_id = " . $user_school_id;
}

// Fetch all students from database
$sql = "SELECT ID, student_name, sex, dob, other, photo, (SELECT school_name FROM tb_schools WHERE id = tb_students.school_id) as school_name, (SELECT COUNT(*) FROM tb_study WHERE id_stu = tb_students.ID) as study_count FROM tb_students $whereSQL ORDER BY ID DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, 'Khmer OS', sans-serif; background: #f4f4f4; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; }
        .sidebar h3 { margin-bottom: 20px; }
        .sidebar a { display: block; padding: 10px; margin: 5px 0; text-decoration: none; color: white; border-radius: 5px; }
        .sidebar a:hover { background: #34495e; }
        .sidebar a i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { flex: 1; padding: 20px; }
        .header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { flex: 1; }
        .card { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        .student-id { font-weight: bold; background: #ecf0f1; width: 60px; }
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-edit { background: #27ae60; }
        .btn-edit:hover { background: #229954; }
        .btn-delete { background: #e74c3c; }
        .btn-delete:hover { background: #c0392b; }
        .btn-add { background: #16a085; padding: 10px 20px; }
        .btn-add:hover { background: #138d75; }
        .btn-study { background: #8e44ad; }
        .btn-study:hover { background: #732d91; }
        .btn-has-study { background: #e67e22; }
        .btn-has-study:hover { background: #d35400; }
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
            <a href="../dashboard.php?page=home"><i class="fa-solid fa-home"></i> <?php echo $lang['home']; ?></a>
            <a href="list_student.php" style="background: #34495e;"><i class="fa-solid fa-user-graduate"></i> <?php echo $lang['students']; ?></a>
            <a href="register_student.php"><i class="fa-solid fa-user-plus"></i> <?php echo $lang['add_student']; ?></a>
            <!-- <a href="register_student_study.php"><i class="fa-solid fa-registered"></i> <?php echo $lang['register_study']; ?></a> -->
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> <?php echo $lang['study']; ?></a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> <?php echo $lang['course']; ?></a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> <?php echo $lang['grades']; ?></a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> <?php echo $lang['invoices']; ?></a>
            <a href="finished_student.php"><i class="fa-solid fa-user-check"></i> <?php echo $lang['finished_students']; ?></a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> <?php echo $lang['paid_list']; ?></a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> <?php echo $lang['users']; ?></a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> <?php echo $lang['logout']; ?></a>
        </div>

        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Student List</h1>
                    <p>Manage all registered students</p>
                </div>
                <div>
                    <a href="finished_student.php" class="btn" style="background: #f39c12;">Finished Students</a>
                    <a href="register_student.php" class="btn btn-add">+ Add Student</a>
                </div>
            </div>

            <div class="card">
                <table width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Student Name</th>
                            <th>Sex</th>
                            <th>Date of Birth</th>
                            <th>School</th>
                            <th>Other Info</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='student-id'>" . htmlspecialchars($row['ID']) . "</td>";
                                echo "<td>";
                                if (!empty($row['photo'])) {
                                    echo "<img src='../uploads/" . htmlspecialchars($row['photo']) . "' width='50' height='50' style='object-fit: cover; border-radius: 50%;'>";
                                } else {
                                    echo "<span style='color: #ccc;'>No Photo</span>";
                                }
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['sex'] === 'Male' ? 'ប្រុស' : ($row['sex'] === 'Female' ? 'ស្រី' : $row['sex'])) . "</td>";
                                echo "<td>" . htmlspecialchars($row['dob']) . "</td>";
                                echo "<td><span style='font-size:0.9em; color:#555;'>" . htmlspecialchars($row['school_name'] ?? 'Default') . "</span></td>";
                                echo "<td>" . htmlspecialchars(substr($row['other'] ?? '', 0, 50)) . "</td>";
                                echo "<td>";
                                $btnClass = ($row['study_count'] > 0) ? 'btn-has-study' : 'btn-study';
                                echo "<a href='../study/add_study.php?id_stu=" . $row['ID'] . "' class='btn " . $btnClass . "' title='Study'><i class='fa-solid fa-book-open'></i></a>";
                                echo "<a href='edit_student.php?id=" . $row['ID'] . "' class='btn btn-edit' title='Edit'><i class='fa-solid fa-pen-to-square'></i></a>";
                                if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1) {
                                echo "<a href='delete_student.php?id=" . $row['ID'] . "' class='btn btn-delete' onclick='return confirm(\"Are you sure?\")' title='Delete'><i class='fa-solid fa-trash'></i></a>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: #999;'>No students found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>