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

// Fetch students
$students = [];
$sql = "SELECT ID, student_name FROM tb_students ORDER BY student_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch time slots
$times = [];
$sql = "SELECT id, time FROM tb_time ORDER BY time";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $times[] = $row;
    }
}

// Fetch courses
$courses = [];
$sql = "SELECT ID, Course FROM tb_course ORDER BY Course";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_stu = $_POST['id_stu'] ?? '';
    $id_time = $_POST['id_time'] ?? '';
    $id_code = $_POST['id_course'] ?? '';
    $price = $_POST['price'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    // Validate inputs
    if (empty($id_stu) || empty($id_time) || empty($id_code) || empty($price) || empty($start_date) || empty($end_date)) {
        $error = "All fields are required! Please ensure you selected valid course.";
    } else {
        // Insert into database
        $sql = "INSERT INTO tb_study (id_stu, id_time, id_code, price, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("iisdss", $id_stu, $id_time, $id_code, $price, $start_date, $end_date);
            if ($stmt->execute()) {
                $success = "Study record added successfully!";
                // Clear form
                $id_stu = '';
                $id_time = '';
                $id_code = '';
                $price = '';
                $start_date = '';
                $end_date = '';
            } else {
                $error = "Error adding study record: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Study Record</title>
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
        .card { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; font-family: Arial, sans-serif; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #3498db; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
        textarea { resize: vertical; min-height: 80px; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-right: 10px; }
        .btn:hover { background: #2980b9; }
        .btn-back { background: #95a5a6; }
        .btn-back:hover { background: #7f8c8d; }
        .error { color: #c0392b; background: #f8d7da; padding: 12px; border-radius: 4px; margin: 15px 0; border: 1px solid #f5c6cb; }
        .success { color: #155724; background: #d4edda; padding: 12px; border-radius: 4px; margin: 15px 0; border: 1px solid #c3e6cb; }
        .button-group { margin-top: 20px; }
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
            <a href="../students/register_student.php"><i class="fa-solid fa-user-plus"></i> Add Student</a>
            <!-- <a href="../students/register_student_study.php"><i class="fa-solid fa-registered"></i> Register & Study</a> -->
            <a href="list_study.php" style="background: #34495e;"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="../students/finished_student.php"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Add Study Record</h1>
            </div>

            <div class="card">
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="id_stu">Student:</label>
                        <select 
                            id="id_stu" 
                            name="id_stu" 
                            required
                        >
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['ID']; ?>" <?php echo (($_POST['id_stu'] ?? $_GET['id_stu'] ?? '') == $student['ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['student_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_time">Time Slot:</label>
                        <select 
                            id="id_time" 
                            name="id_time" 
                            required
                        >
                            <option value="">-- Select Time Slot --</option>
                            <?php foreach ($times as $time): ?>
                                <option value="<?php echo $time['id']; ?>" <?php echo (($_POST['id_time'] ?? '') === (string)$time['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($time['time']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_course">Course:</label>
                        <select 
                            id="id_course" 
                            name="id_course" 
                            required
                        >
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['ID']; ?>" <?php echo (($_POST['id_code'] ?? '') === $course['ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['Course']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Price:</label>
                        <select id="price" name="price" required>
                            <option value="">-- Select Price --</option>
                            <?php 
                            $prices = [10, 15, 20, 25, 30, 35, 40];
                            foreach ($prices as $p) {
                                $selected = (($_POST['price'] ?? '') == $p) ? 'selected' : '';
                                echo "<option value='$p' $selected>$p</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date:</label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn">Add Study Record</button>
                        <a href="list_study.php" class="btn btn-back">Back to Study Records</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            if (!isNaN(startDate.getTime())) {
                const endDate = new Date(startDate);
                endDate.setMonth(startDate.getMonth() + 3);
                
                const year = endDate.getFullYear();
                const month = String(endDate.getMonth() + 1).padStart(2, '0');
                const day = String(endDate.getDate()).padStart(2, '0');
                
                document.getElementById('end_date').value = `${year}-${month}-${day}`;
            }
        });
    </script>
</body>
</html>
