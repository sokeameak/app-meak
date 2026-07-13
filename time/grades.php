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

// Handle form submission to add time
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time = $_POST['time'] ?? '';
    
    if (empty($time)) {
        $error = "Please enter a time slot.";
    } else {
        // Insert into database
        $sql = "INSERT INTO tb_time (time) VALUES (?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $time);
            if ($stmt->execute()) {
                log_siem_event($conn, $_SESSION['user'], 'ADD_TIME', "Added time slot: $time");
                $message = "Time slot added successfully!";
            } else {
                $error = "Error adding time slot: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Fetch all time slots from database
$times = [];
$sql = "SELECT * FROM tb_time ORDER BY id DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $times[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Study Schedule</title>
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
        .card { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        .time-slot { font-weight: bold; background: #ecf0f1; width: 150px; }
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 5px; }
        .btn:hover { background: #2980b9; }
        .btn-edit { background: #27ae60; }
        .btn-edit:hover { background: #229954; }
        .btn-delete { background: #e74c3c; }
        .btn-delete:hover { background: #c0392b; }
        .btn-add { background: #16a085; }
        .btn-add:hover { background: #138d75; padding: 10px 20px; }
        .controls { margin-bottom: 20px; }
        .controls label { margin-right: 10px; font-weight: bold; color: #2c3e50; }
        .controls input[type="text"] { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; width: 200px; margin-right: 10px; }
        .controls input[type="text"]:focus { outline: none; border-color: #3498db; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
       
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
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="grades.php" style="background: #34495e;"><i class="fa-solid fa-clock"></i> Grades</a>
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
                <h1>Time Study Schedule</h1>
                <p>Manage your daily study schedule and time slots.</p>
            </div>

            <div class="card">
                <?php if (!empty($message)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="controls">
                        <label for="time">Time Slot</label>  
                        <input type="text" id="time" name="time" placeholder="e.g., 7:00 - 8:00 AM" required>
                        <button type="submit" class="btn btn-add">+ Add Time</button>
                    </div>
                </form>

                <table width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th style="width: 50%;">Time Slot</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($times)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 20px; color: #999;">No time slots added yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($times as $time): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($time['id']); ?></td>
                                    <td class="time-slot"><?php echo htmlspecialchars($time['time']); ?></td>
                                    <td>
                                        <a href="edit_time.php?id=<?php echo $time['id']; ?>" class="btn btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
                                        <a href="delete_time.php?id=<?php echo $time['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')" title="Delete"><i class="fa-solid fa-trash"></i></a>
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
