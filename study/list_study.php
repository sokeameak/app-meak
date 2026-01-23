<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$message = '';
$error = '';
if (isset($_GET['message'])) $message = $_GET['message'];

$search_date = $_GET['search_date'] ?? '';
$show_all = isset($_GET['show_all']);

// Fetch distinct start dates for filter
$dates = [];
$sql_dates = "SELECT DISTINCT start_date FROM tb_study ORDER BY start_date DESC";
$result_dates = $conn->query($sql_dates);
if ($result_dates) {
    while ($row = $result_dates->fetch_assoc()) $dates[] = $row['start_date'];
}

// Fetch all study records from database with JOIN to get names
$studies = [];
$sql = "SELECT 
    s.id,
    s.id_stu,
    s.id_time,
    s.id_code,
    s.price,
    s.start_date,
    s.end_date,
    st.student_name,
    t.time,
    c.Course,
    sch.school_name
FROM tb_study s
LEFT JOIN tb_students st ON s.id_stu = st.ID
LEFT JOIN tb_time t ON s.id_time = t.id
LEFT JOIN tb_course c ON s.id_code = c.ID
LEFT JOIN tb_schools sch ON st.school_id = sch.id
";

if (!empty($search_date)) {
    $sql .= " WHERE s.start_date = '" . $conn->real_escape_string($search_date) . "'";
} elseif ($show_all) {
    // Show all records
} else {
    $sql .= " WHERE s.end_date > CURDATE()";
}

$sql .= " GROUP BY s.id ORDER BY s.id DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $studies[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Records</title>
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
        .study-id { font-weight: bold; background: #ecf0f1; width: 60px; }
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-edit { background: #27ae60; }
        .btn-edit:hover { background: #229954; }
        .btn-delete { background: #e74c3c; }
        .btn-delete:hover { background: #c0392b; }
        .btn-add { background: #16a085; padding: 10px 20px; }
        .btn-add:hover { background: #138d75; }
        .btn-finish { background: #8e44ad; }
        .btn-finish:hover { background: #732d91; }
        .message { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
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
            <a href="../students/list_student.php"><i class="fa-solid fa-user-graduate"></i> <?php echo $lang['students']; ?></a>
            <a href="../students/register_student.php"><i class="fa-solid fa-user-plus"></i> <?php echo $lang['add_student']; ?></a>
            <!-- <a href="../students/register_student_study.php"><i class="fa-solid fa-registered"></i> <?php echo $lang['register_study']; ?></a> -->
            <a href="list_study.php" style="background: #34495e;"><i class="fa-solid fa-book-open"></i> <?php echo $lang['study']; ?></a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> <?php echo $lang['course']; ?></a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> <?php echo $lang['grades']; ?></a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> <?php echo $lang['invoices']; ?></a>
            <a href="../students/finished_student.php"><i class="fa-solid fa-user-check"></i> <?php echo $lang['finished_students']; ?></a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> <?php echo $lang['paid_list']; ?></a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> <?php echo $lang['users']; ?></a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> <?php echo $lang['logout']; ?></a>
        </div>

        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Study Records</h1>
                    <p>Manage all study sessions and records</p>
                </div>
                <div>
                    <form method="GET" style="display: inline-block; margin-right: 10px;">
                        <label style="margin-right: 5px; font-weight: bold; color: #2c3e50; cursor: pointer;">
                            <input type="checkbox" name="show_all" <?php echo $show_all ? 'checked' : ''; ?>> Show All
                        </label>
                        <select name="search_date" style="padding: 7px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="">-- Select Date --</option>
                            <?php foreach ($dates as $date): ?>
                                <option value="<?php echo $date; ?>" <?php echo ($search_date == $date) ? 'selected' : ''; ?>><?php echo $date; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn" style="background: #2c3e50;">Filter</button>
                        <?php if (!empty($search_date)): ?>
                            <a href="list_study.php" class="btn" style="background: #7f8c8d; text-decoration:none;">Reset</a>
                        <?php endif; ?>
                    </form>
                    <a href="../students/finished_student.php" class="btn" style="background: #f39c12;">Finished Students</a>
                    <a href="add_study.php" class="btn btn-add">+ Add Study</a>
                </div>
            </div>

            <div class="card">
                <?php if (!empty($message)): ?>
                    <div class="message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-msg">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <table width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>School</th>
                            <th>Time Slot</th>
                            <th>Course</th>
                            <th>Price</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studies)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px; color: #999;">No study records added yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($studies as $study): ?>
                                <tr>
                                    <td class="study-id"><?php echo htmlspecialchars($study['id'] ?? ''); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($study['student_name'] ?? ''); ?>
                                        <a href="finish_study.php?id=<?php echo $study['id']; ?>" class="btn btn-finish" onclick="return confirm('Are you sure you want to finish this study?')" title="Finish" style="float: right; padding: 4px 8px; font-size: 12px;"><i class="fa-solid fa-check"></i></a>
                                    </td>
                                    <td><span style='font-size:0.9em; color:#555;'><?php echo htmlspecialchars($study['school_name'] ?? 'Default'); ?></span></td>
                                    <td><?php echo htmlspecialchars($study['time'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($study['Course'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($study['price'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($study['start_date'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($study['end_date'] ?? ''); ?></td>
                                    <td>
                                        <a href="edit_study.php?id=<?php echo $study['id']; ?>" class="btn btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
                                        <a href="delete_study.php?id=<?php echo $study['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                        <?php endif; ?>
                                        
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
