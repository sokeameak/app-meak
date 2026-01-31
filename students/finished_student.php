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

$whereSQL = " WHERE s.end_date <= CURDATE()";
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 1 && $user_school_id > 0) {
    $whereSQL .= " AND st.school_id = " . $user_school_id;
}

// Fetch finished students (where end_date is in the past)
$sql = "SELECT s.id as study_id, st.ID, st.student_name, st.sex, st.dob, st.photo, s.end_date, c.Course, sch.school_name 
        FROM tb_study s 
        JOIN tb_students st ON s.id_stu = st.ID 
        JOIN tb_course c ON s.id_code = c.ID 
        LEFT JOIN tb_schools sch ON st.school_id = sch.id
        $whereSQL
        ORDER BY s.end_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finished Students</title>
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
        .card { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        .student-id { font-weight: bold; background: #ecf0f1; width: 60px; }
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-add { background: #16a085; padding: 10px 20px; }
        .btn-add:hover { background: #138d75; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
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
            <a href="finished_student.php" style="background: #34495e;"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="../invoice/paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <a href="../users/add_users.php"><i class="fa-solid fa-users-cog"></i> Users</a>
            <?php endif; ?>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Finished Students</h1>
                    <p>List of students who have completed their courses.</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="search_name" onkeyup="filterFinishedStudents()" placeholder="Search Name..." style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    <a href="add_finished_student.php" class="btn btn-add">+ Add Finished Student</a>
                </div>
            </div>

            <div class="card">
                <table width="100%">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Student Name</th>
                            <th>Sex</th>
                            <th>Course</th>
                            <th>School</th>
                            <th>Finished Date</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><input type='checkbox' name='ids[]' value='" . $row['study_id'] . "'></td>";
                                echo "<td class='student-id'>" . htmlspecialchars($row['ID']) . "</td>";
                                echo "<td>";
                                if (!empty($row['photo'])) {
                                    echo "<img src='../uploads/" . htmlspecialchars($row['photo']) . "' width='50' height='50' style='object-fit: cover; border-radius: 50%;'>";
                                } else {
                                    echo "<span style='color: #ccc;'>No Photo</span>";
                                }
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
                                echo "<td>";
                                echo "<a href='get_certificate.php?id=" . $row['study_id'] . "' target='_blank' class='btn' style='background: #8e44ad; margin-right: 5px;' title='Certificate'><i class='fa-solid fa-certificate'></i></a>";
                                if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1) {
                                echo "<a href='delete_finished.php?id=" . $row['study_id'] . "' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to remove this record?\")' title='Remove'><i class='fa-solid fa-trash'></i></a>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center; padding: 20px; color: #999;'>No finished students found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function toggleAll(source) {
            checkboxes = document.getElementsByName('ids[]');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        function filterFinishedStudents() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("search_name");
            filter = input.value.toUpperCase();
            table = document.querySelector("table");
            tr = table.getElementsByTagName("tr");
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[3]; // Column 3 is Student Name
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>