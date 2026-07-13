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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $CourseID = $_POST['CourseID'] ?? '';
    $Course = $_POST['Course'] ?? '';
    $Note = $_POST['Note'] ?? '';
   
    
    // Validate inputs
    if (empty($CourseID) || empty($Course)) {
        $error = "CourseID and Course are required!";
    } else {
        // Insert into database
        $sql = "INSERT INTO tb_course (CourseID, Course, Note) 
                VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sss", $CourseID, $Course, $Note);
            if ($stmt->execute()) {
                log_siem_event($conn, $_SESSION['user'], 'ADD_COURSE', "Added course: $Course ($CourseID)");
                $success = "Course added successfully!";
                // Clear form
                $CourseID  = '';
                $Course= '';
                $Note = '';
                
            } else {
                $error = "Error adding course: " . $stmt->error;
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
    <title>Add New Course</title>
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
            <!-- <a href="../students/register_student.php"><i class="fa-solid fa-user-plus"></i> Add Student</a> -->
            <a href="../students/register_student_study.php"><i class="fa-solid fa-registered"></i> Register & Study</a>
            <a href="../students/finished_student.php"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="add_course.php" style="background: #34495e;"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Add New Course</h1>
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
                        <label for="CourseID">Course ID:</label>
                        <input 
                            type="text" 
                            id="CourseID" 
                            name="CourseID" 
                            placeholder="e.g., CS101" 
                            value="<?php echo htmlspecialchars($_POST['CourseID'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="Course">Course:</label>
                        <input 
                            type="text" 
                            id="Course" 
                            name="Course" 
                            placeholder="e.g., Introduction to Programming" 
                            value="<?php echo htmlspecialchars($_POST['Course'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="Note">Note:</label>
                        <textarea 
                            id="Note" 
                            name="Note" 
                            placeholder="Enter course note..."
                        ><?php echo htmlspecialchars($_POST['Note'] ?? ''); ?></textarea>
                    </div>

                   

                   

                    <div class="button-group">
                        <button type="submit" class="btn">Add Course</button>
                        <a href="list_course.php" class="btn btn-back">Back to Courses</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
