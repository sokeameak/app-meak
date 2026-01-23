<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$student_id = $_GET['id'] ?? '';
$error = '';
$success = '';

// Fetch student record from database
$student = [
    'ID' => $student_id,
    'student_name' => '',
    'sex' => '',
    'dob' => '',
    'other' => '',
    'photo' => '',
    'school_id' => 1
];

if (empty($student_id)) {
    $error = "Invalid student ID!";
} else {
    $sql = "SELECT * FROM tb_students WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $student = $result->fetch_assoc();
        } else {
            $error = "Student not found!";
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Fetch schools
$schools = [];
$schoolRes = $conn->query("SELECT * FROM tb_schools");
if($schoolRes) {
    while($r = $schoolRes->fetch_assoc()) $schools[] = $r;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_name = $_POST['student_name'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $dob_day = $_POST['dob_day'] ?? '';
    $dob_month = $_POST['dob_month'] ?? '';
    $dob_year = $_POST['dob_year'] ?? '';
    $dob = ($dob_day && $dob_month && $dob_year) ? "$dob_year-$dob_month-$dob_day" : '';
    $other = $_POST['other'] ?? '';
    $photo = $student['photo']; // Default to existing photo
    $school_id = $_POST['school_id'] ?? 1;
    
    // Validate inputs
    if (empty($student_name) || empty($sex) || empty($dob)) {
        $error = "Student name, sex, and date of birth are required!";
    } else {
        // Handle Photo Upload
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

        // Update database
        $sql = "UPDATE tb_students SET student_name = ?, sex = ?, dob = ?, other = ?, photo = ?, school_id = ? WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sssssii", $student_name, $sex, $dob, $other, $photo, $school_id, $student_id);
            if ($stmt->execute()) {
                log_siem_event($conn, $_SESSION['user'], 'UPDATE_STUDENT', "Updated student ID: $student_id ($student_name)");
                $success = "Student updated successfully!";
                $student['student_name'] = $student_name;
                $student['sex'] = $sex;
                $student['dob'] = $dob;
                $student['other'] = $other;
                $student['photo'] = $photo;
                $student['school_id'] = $school_id;
            } else {
                $error = "Error updating student: " . $stmt->error;
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
    <title>Edit Student</title>
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
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #3498db; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-right: 10px; }
        .btn:hover { background: #2980b9; }
        .btn-back { background: #95a5a6; }
        .btn-back:hover { background: #7f8c8d; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
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
            <a href="list_student.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="register_student.php"><i class="fa-solid fa-user-plus"></i> Add Student</a>
            <!-- <a href="register_student_study.php"><i class="fa-solid fa-registered"></i> Register & Study</a> -->
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="../invoice/invoice.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="finished_student.php"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Edit Student</h1>
                <p>Update student information below.</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>School / Brand:</label>
                        <select name="school_id">
                            <?php foreach($schools as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo (($student['school_id'] ?? 1) == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['school_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Student Name:</label>
                        <input type="text" name="student_name" value="<?php echo htmlspecialchars($student['student_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Sex:</label>
                        <div style="margin-top: 10px;">
                            <label style="display: inline-block; margin-right: 15px; font-weight: normal; cursor: pointer;"><input type="radio" name="sex" value="Male" required <?php echo (($student['sex'] ?? '') === 'Male' || ($student['sex'] ?? '') === 'ប្រុស') ? 'checked' : ''; ?>> Male</label>
                            <label style="display: inline-block; font-weight: normal; cursor: pointer;"><input type="radio" name="sex" value="Female" required <?php echo (($student['sex'] ?? '') === 'Female' || ($student['sex'] ?? '') === 'ស្រី') ? 'checked' : ''; ?>> Female</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Birth:</label>
                        <?php
                            $ex_dob = $student['dob'] ?? '';
                            $ex_day = ''; $ex_month = ''; $ex_year = '';
                            if ($ex_dob) {
                                $parts = explode('-', $ex_dob);
                                if (count($parts) == 3) {
                                    $ex_year = $parts[0];
                                    $ex_month = $parts[1];
                                    $ex_day = $parts[2];
                                }
                            }
                            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                $ex_day = $_POST['dob_day'] ?? '';
                                $ex_month = $_POST['dob_month'] ?? '';
                                $ex_year = $_POST['dob_year'] ?? '';
                            }
                        ?>
                        <div style="display: flex; gap: 10px;">
                            <select name="dob_day" required style="width: 30%;">
                                <option value="">Day</option>
                                <?php for($i=1; $i<=31; $i++) { 
                                    $selected = ($ex_day == $i) ? 'selected' : '';
                                    echo "<option value='$i' $selected>$i</option>"; 
                                } ?>
                            </select>
                            <select name="dob_month" required style="width: 30%;">
                                <option value="">Month</option>
                                <?php 
                                $months = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
                                foreach($months as $k => $m) {
                                    $val = str_pad($k+1, 2, '0', STR_PAD_LEFT);
                                    $selected = ($ex_month == $val) ? 'selected' : '';
                                    echo "<option value='$val' $selected>$m</option>";
                                }
                                ?>
                            </select>
                            <select name="dob_year" required style="width: 40%;">
                                <option value="">Year</option>
                                <?php 
                                $currentYear = date('Y');
                                for($i=$currentYear; $i>=1900; $i--) {
                                    $selected = ($ex_year == $i) ? 'selected' : '';
                                    echo "<option value='$i' $selected>$i</option>";
                                } 
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Other Information:</label>
                        <input type="text" name="other" value="<?php echo htmlspecialchars($student['other'] ?? ''); ?>" placeholder="e.g., Address, Phone, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label>Photo:</label>
                        <?php if (!empty($student['photo'])): ?>
                            <div style="margin-bottom: 10px;"><img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" width="100" style="border-radius: 5px;"></div>
                        <?php endif; ?>
                        <input type="file" name="photo" accept="image/*">
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn">Update Student</button>
                        <a href="list_student.php" class="btn btn-back">Back to Students</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
