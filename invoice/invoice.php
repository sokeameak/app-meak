<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Create table if not exists (Auto-setup for convenience)
$tableCheck = "CREATE TABLE IF NOT EXISTS tb_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Unpaid',
    study_time VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($tableCheck);

// Add column if it doesn't exist (for existing tables)
$colCheck = $conn->query("SHOW COLUMNS FROM tb_invoices LIKE 'study_time'");
if ($colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE tb_invoices ADD COLUMN study_time VARCHAR(50)");
}

// Handle Add Invoice
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_invoice'])) {
    $student_name = $_POST['student_name'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];
    $study_time = $_POST['study_time'];
    
    $stmt = $conn->prepare("INSERT INTO tb_invoices (student_name, description, amount, status, study_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $student_name, $description, $amount, $status, $study_time);
    $stmt->execute();
    log_siem_event($conn, $_SESSION['user'], 'ADD_INVOICE', "Created invoice for $student_name ($amount)");
    $stmt->close();
    header("Location: invoice.php");
    exit;
}

// Handle Delete Invoice
if (isset($_GET['delete'])) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
        header("Location: invoice.php");
        exit;
    }
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tb_invoices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    log_siem_event($conn, $_SESSION['user'], 'DELETE_INVOICE', "Deleted invoice ID: $id");
    $stmt->close();
    header("Location: invoice.php");
    exit;
}

// Fetch Study Times for Dropdown (Moved up for filter)
$study_times = [];
$studyTimeSql = "SELECT DISTINCT t.time FROM tb_study s JOIN tb_time t ON s.id_time = t.id ORDER BY t.time ASC";
$studyTimeResult = $conn->query($studyTimeSql);
if ($studyTimeResult) {
    while($row = $studyTimeResult->fetch_assoc()) {
        $study_times[] = $row['time'];
    }
}

// Fetch Time-Student Mapping for Dynamic Dropdown
$timeStudentMap = [];
$mapSql = "SELECT DISTINCT t.time, st.student_name, s.price 
           FROM tb_study s 
           JOIN tb_students st ON s.id_stu = st.ID 
           JOIN tb_time t ON s.id_time = t.id 
           ORDER BY t.time, st.student_name";
$mapResult = $conn->query($mapSql);
if ($mapResult) {
    while($row = $mapResult->fetch_assoc()) {
        $timeStudentMap[$row['time']][] = [
            'name' => $row['student_name'],
            'price' => $row['price']
        ];
    }
}

// Fetch Invoices
$search = $_GET['search'] ?? '';
$filter_time = $_GET['filter_time'] ?? '';
$chk_paid = isset($_GET['chk_paid']) ? true : (empty($_GET) ? true : false);
$chk_unpaid = isset($_GET['chk_unpaid']) ? true : (empty($_GET) ? true : false);
$chk_pending = isset($_GET['chk_pending']) ? true : (empty($_GET) ? true : false);

$status_filters = [];
if ($chk_paid) $status_filters[] = "'Paid'";
if ($chk_unpaid) $status_filters[] = "'Unpaid'";
if ($chk_pending) $status_filters[] = "'Pending'";

$sql = "SELECT * FROM tb_invoices WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND student_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if ($filter_time) {
    $sql .= " AND study_time = ?";
    $params[] = $filter_time;
    $types .= "s";
}

if (!empty($status_filters)) {
    $sql .= " AND status IN (" . implode(",", $status_filters) . ")";
} else {
    $sql .= " AND 1=0";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch Students for Dropdown
$studentSql = "SELECT student_name FROM tb_students ORDER BY student_name ASC";
$studentResult = $conn->query($studentSql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management</title>
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
        .btn { padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block;}
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; color: white; }
        .status-paid { background: #2ecc71; }
        .status-unpaid { background: #e74c3c; }
        .status-pending { background: #f1c40f; }
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
                <div style="color: #bdc3c7; font-size: 0.8em; margin-top: 5px;">Administrator</div>
            </div>
            <h3>Dashboard</h3>
            <a href="../dashboard.php?page=home"><i class="fa-solid fa-home"></i> Home</a>
            <a href="../students/list_student.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="../students/register_student.php"><i class="fa-solid fa-user-plus"></i> Add Student</a>
            <a href="../students/finished_student.php"><i class="fa-solid fa-user-check"></i> Finished Students</a>
            <a href="../study/list_study.php"><i class="fa-solid fa-book-open"></i> Study</a>
            <a href="../courses/add_course.php"><i class="fa-solid fa-chalkboard"></i> Course</a>
            <a href="../time/grades.php"><i class="fa-solid fa-clock"></i> Grades</a>
            <a href="invoice.php" style="background: #34495e;"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a>
            <a href="paid.php"><i class="fa-solid fa-file-invoice"></i> Paid List</a>
            <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Invoice Management</h1>
            </div>

            <div class="card">
                <h2>Create New Invoice</h2>
                <form method="POST" style="margin-top: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Study Time</label>
                            <select name="study_time" id="study_time_select" onchange="updateStudents()" required>
                                <option value="">Select Time</option>
                                <?php 
                                foreach ($study_times as $time) {
                                    echo '<option value="' . htmlspecialchars($time) . '">' . htmlspecialchars($time) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Student Name</label>
                            <select name="student_name" id="student_name_select" required>
                                <option value="">Select Time First</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Amount ($)</label>
                        <input type="number" step="0.01" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Unpaid">Unpaid</option>
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <button type="submit" name="add_invoice" class="btn">Create Invoice</button>
                </form>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2>Invoice List</h2>
                    <form method="GET" style="display: flex; gap: 10px;">
                        <label style="display: flex; align-items: center;"><input type="checkbox" name="chk_paid" <?php echo $chk_paid ? 'checked' : ''; ?> style="margin-right: 5px;"> Paid</label>
                        <label style="display: flex; align-items: center;"><input type="checkbox" name="chk_unpaid" <?php echo $chk_unpaid ? 'checked' : ''; ?> style="margin-right: 5px;"> Unpaid</label>
                        <label style="display: flex; align-items: center;"><input type="checkbox" name="chk_pending" <?php echo $chk_pending ? 'checked' : ''; ?> style="margin-right: 5px;"> Pending</label>
                        <input type="text" name="search" placeholder="Search student..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <select name="filter_time" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Times</option>
                            <?php foreach ($study_times as $time): ?>
                                <option value="<?php echo htmlspecialchars($time); ?>" <?php echo ($filter_time === $time) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($time); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn">Search</button>
                        <?php if ($search || $filter_time): ?><a href="invoice.php" class="btn" style="background: #95a5a6;">Clear</a><?php endif; ?>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td>$<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['study_time'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'status-pending';
                                            if ($row['status'] == 'Paid') $statusClass = 'status-paid';
                                            elseif ($row['status'] == 'Unpaid') $statusClass = 'status-unpaid';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
                                        <a href="invoice.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')" style="font-size: 0.8em;" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8">No invoices found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const timeStudentMap = <?php echo json_encode($timeStudentMap); ?>;
        const searchParam = "<?php echo htmlspecialchars($search); ?>";

        function updateStudents() {
            const timeSelect = document.getElementById('study_time_select');
            const studentSelect = document.getElementById('student_name_select');
            const selectedTime = timeSelect.value;

            studentSelect.innerHTML = '<option value="">Select Student</option>';

            if (selectedTime && timeStudentMap[selectedTime]) {
                timeStudentMap[selectedTime].forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.name;
                    option.textContent = student.name;
                    option.dataset.price = student.price;
                    studentSelect.appendChild(option);
                });
            }
        }

        document.getElementById('student_name_select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.price) {
                document.querySelector('input[name="amount"]').value = selectedOption.dataset.price;
            }
        });

        // Auto-select based on search parameter
        if (searchParam) {
            for (const [time, students] of Object.entries(timeStudentMap)) {
                const student = students.find(s => s.name === searchParam);
                if (student) {
                    document.getElementById('study_time_select').value = time;
                    updateStudents();
                    document.getElementById('student_name_select').value = searchParam;
                    if (student.price) {
                        document.querySelector('input[name="amount"]').value = student.price;
                    }
                    break;
                }
            }
        }
    </script>
</body>
</html>