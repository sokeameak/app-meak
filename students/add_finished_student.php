<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Fetch Courses
$courses = [];
$courseSql = "SELECT ID, Course FROM tb_course ORDER BY Course ASC";
$courseResult = $conn->query($courseSql);
if ($courseResult) {
    while ($row = $courseResult->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Fetch Time Slots
$times = [];
$timeSql = "SELECT id, time FROM tb_time ORDER BY id ASC";
$timeResult = $conn->query($timeSql);
if ($timeResult) {
    while ($row = $timeResult->fetch_assoc()) {
        $times[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Student Data
    $student_name = $_POST['student_name'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $dob_day = $_POST['dob_day'] ?? '';
    $dob_month = $_POST['dob_month'] ?? '';
    $dob_year = $_POST['dob_year'] ?? '';
    $dob = ($dob_day && $dob_month && $dob_year) ? "$dob_year-$dob_month-$dob_day" : '';
    $other = $_POST['other'] ?? '';
    $photo = '';

    // Study Data
    $id_code = $_POST['id_code'] ?? '';
    $id_time = $_POST['id_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Validate inputs
    if (empty($student_name) || empty($sex) || empty($dob) || empty($id_code) || empty($id_time) || empty($price) || empty($end_date)) {
        $error = "Please fill in all required fields!";
    } else {
        // Start Transaction
        $conn->begin_transaction();

        try {
            // 1. Handle Photo Upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $target_dir = "../uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $new_filename)) {
                    $photo = $new_filename;
                }
            }

            // 2. Insert Student
            $stmt = $conn->prepare("INSERT INTO tb_students (student_name, sex, dob, other, photo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $student_name, $sex, $dob, $other, $photo);
            if (!$stmt->execute()) {
                throw new Exception("Error registering student: " . $stmt->error);
            }
            $student_id = $conn->insert_id;
            $stmt->close();

            // 3. Insert Study Record
            $stmt = $conn->prepare("INSERT INTO tb_study (id_stu, id_code, id_time, price, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidss", $student_id, $id_code, $id_time, $price, $start_date, $end_date);
            if (!$stmt->execute()) {
                throw new Exception("Error adding study record: " . $stmt->error);
            }
            $stmt->close();

            // Commit
            $conn->commit();
            log_siem_event($conn, $_SESSION['user'], 'ADD_FINISHED_STUDENT', "Added finished student: $student_name");
            $success = "Finished student added successfully!";
            
            // Clear form data
            $_POST = []; 

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Finished Student</title>
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
        .header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-right: 10px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-back { background: #95a5a6; }
        .btn-back:hover { background: #7f8c8d; }
        .error { color: #c0392b; background: #f8d7da; padding: 12px; border-radius: 4px; margin: 15px 0; border: 1px solid #f5c6cb; }
        .success { color: #155724; background: #d4edda; padding: 12px; border-radius: 4px; margin: 15px 0; border: 1px solid #c3e6cb; }
        .section-title { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px; color: #2c3e50; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
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
                <div style="color: #bdc3c7; font-size: 0.8em; margin-top: 5px;"><?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1) ? 'Administrator' : 'Normal User'; ?></div>
            </div>
            <h3>Dashboard</h3>
            <a href="../dashboard.php?page=home"><i class="fa-solid fa-home"></i> Home</a>
            <a href="../students/list_student.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="register_student.php"><i class="fa-solid fa-user-plus"></i> Add Student</a>
            <!-- <a href="register_student_study.php"><i class="fa-solid fa-registered"></i> Register & Study</a> -->
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="finished_student.php" style="background: #34495e;"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Add Finished Student</h1>
            </div>
    
            <?php if (!empty($error)): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <h3 class="section-title" style="margin-top: 0;">Student Information</h3>
                    <div class="grid-2">
                        <div class="form-group"><label>Student Name:</label><input type="text" name="student_name" required></div>
                        <div class="form-group"><label>Sex:</label>
                            <div style="margin-top: 10px;"><label style="display: inline-block; margin-right: 15px; font-weight: normal; cursor: pointer;"><input type="radio" name="sex" value="Male" required> Male</label>
                            <label style="display: inline-block; font-weight: normal; cursor: pointer;"><input type="radio" name="sex" value="Female" required> Female</label></div>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group"><label>Date of Birth:</label>
                            <div style="display: flex; gap: 10px;">
                                <select name="dob_day" required style="width: 30%;">
                                    <option value="">Day</option>
                                    <?php for($i=1; $i<=31; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                                <select name="dob_month" required style="width: 30%;">
                                    <option value="">Month</option>
                                    <?php 
                                    $months = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
                                    foreach($months as $k => $m) {
                                        $val = str_pad($k+1, 2, '0', STR_PAD_LEFT);
                                        echo "<option value='$val'>$m</option>";
                                    }
                                    ?>
                                </select>
                                <select name="dob_year" required style="width: 40%;">
                                    <option value="">Year</option>
                                    <?php for($i=date('Y'); $i>=1900; $i--) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label>Photo:</label><input type="file" name="photo" accept="image/*"></div>
                    </div>
                    <div class="form-group"><label>Other Info:</label><input type="text" name="other"></div>

                    <h3 class="section-title">Study Information (Finished)</h3>
                    <div class="grid-2">
                        <div class="form-group"><label>Course:</label><select name="id_code" required><option value="">Select Course</option><?php foreach ($courses as $c) echo "<option value='{$c['ID']}'>{$c['Course']}</option>"; ?></select></div>
                        <div class="form-group"><label>Time Slot:</label><select name="id_time" required><option value="">Select Time</option><?php foreach ($times as $t) echo "<option value='{$t['id']}'>{$t['time']}</option>"; ?></select></div>
                    </div>
                    <div class="form-group">
                        <label>Price ($):</label>
                        <select name="price" required>
                            <option value="">-- Select Price --</option>
                            <?php 
                            $prices = [10, 15, 20, 25, 30, 35, 40];
                            foreach ($prices as $p) echo "<option value='$p' " . (($_POST['price'] ?? '') == $p ? 'selected' : '') . ">$p</option>";
                            ?>
                        </select>
                    </div>
                    <div class="grid-2">
                        <div class="form-group"><label>Start Date:</label><input type="date" name="start_date"></div>
                        <div class="form-group"><label>End Date (Finished):</label><input type="date" name="end_date" required></div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn">Add Finished Student</button>
                        <a href="finished_student.php" class="btn btn-back">Back to List</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>