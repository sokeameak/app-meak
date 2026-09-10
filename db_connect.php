<?php
// db_connect.php - Database Configuration & Auto-Migration Layer

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "mk";

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("<div style='font-family: Arial; padding: 20px; color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; max-width: 600px; margin: 40px auto;'>
        <h3>Database Connection Failed</h3>
        <p>" . htmlspecialchars($conn->connect_error) . "</p>
        <p>Please make sure MySQL is running in WampServer and database <strong>mk</strong> exists.</p>
    </div>");
}

// Set charset to utf8mb4 for full Khmer unicode support
$conn->set_charset("utf8mb4");

// Helper to ensure a column exists in a table
if (!function_exists('ensure_column_exists')) {
    function ensure_column_exists($conn, $table, $column, $definition) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }
}

// Helper to ensure primary key column is AUTO_INCREMENT
if (!function_exists('ensure_auto_increment')) {
    function ensure_auto_increment($conn, $table, $column = 'id') {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && $check->num_rows > 0) {
            $colInfo = $check->fetch_assoc();
            if (strpos(strtolower($colInfo['Extra'] ?? ''), 'auto_increment') === false) {
                // If there are duplicate 0s, re-sequence them first
                $conn->query("SET @seq_id = 0");
                $conn->query("UPDATE `$table` SET `$column` = (@seq_id := @seq_id + 1) ORDER BY `$column`");
                @$conn->query("ALTER TABLE `$table` MODIFY `$column` INT AUTO_INCREMENT");
            }
        }
    }
}

// 1. Create Core Tables if they do not exist
$initQueries = [
    "CREATE TABLE IF NOT EXISTS tb_schools (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_name VARCHAR(255) NOT NULL,
        school_name_kh VARCHAR(255) NOT NULL,
        logo VARCHAR(255) DEFAULT 'meakea.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        user_type INT DEFAULT 0,
        school_id INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_course (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        CourseID VARCHAR(50) NOT NULL UNIQUE,
        Course VARCHAR(255) NOT NULL,
        Note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_time (
        id INT AUTO_INCREMENT PRIMARY KEY,
        time VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_students (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(255) NOT NULL,
        sex VARCHAR(20) NOT NULL,
        dob DATE NULL,
        other TEXT NULL,
        photo VARCHAR(255) NULL,
        school_id INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_study (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_stu INT NOT NULL,
        id_code INT NOT NULL,
        id_time INT NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NULL,
        student_name VARCHAR(255) NOT NULL,
        description VARCHAR(255) NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'Unpaid',
        study_time VARCHAR(50) NULL,
        school_id INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL DEFAULT 'General',
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        expense_date DATE NOT NULL,
        note TEXT NULL,
        receipt_photo VARCHAR(255) NULL,
        school_id INT DEFAULT 1,
        created_by VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (expense_date), INDEX (category), INDEX (school_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tbl_certi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        study_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tbl_att (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_stu INT NOT NULL,
        date DATETIME NOT NULL,
        status VARCHAR(20) DEFAULT '0',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS tb_siem_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        action VARCHAR(50),
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (username), INDEX (action), INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($initQueries as $q) {
    $conn->query($q);
}

// 2. Automatic Schema Migration for Existing Tables (Ensuring all columns exist)
ensure_column_exists($conn, 'tb_users', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
ensure_column_exists($conn, 'tb_users', 'user_type', 'INT DEFAULT 0');
ensure_column_exists($conn, 'tb_users', 'school_id', 'INT DEFAULT 0');

ensure_column_exists($conn, 'tb_invoices', 'student_id', 'INT NULL');
ensure_column_exists($conn, 'tb_invoices', 'study_time', 'VARCHAR(50) NULL');
ensure_column_exists($conn, 'tb_invoices', 'school_id', 'INT DEFAULT 1');
ensure_column_exists($conn, 'tb_invoices', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

// Ensure tb_invoices.status is VARCHAR(50)
$statusColCheck = $conn->query("SHOW COLUMNS FROM `tb_invoices` LIKE 'status'");
if ($statusColCheck && $statusColCheck->num_rows > 0) {
    $colRow = $statusColCheck->fetch_assoc();
    if (strpos(strtolower($colRow['Type'] ?? ''), 'varchar') === false) {
        $conn->query("ALTER TABLE `tb_invoices` MODIFY `status` VARCHAR(50) DEFAULT 'Unpaid'");
        $conn->query("UPDATE `tb_invoices` SET `status` = 'Unpaid' WHERE `status` = '0' OR `status` = '' OR `status` IS NULL");
        $conn->query("UPDATE `tb_invoices` SET `status` = 'Paid' WHERE `status` = '1'");
    }
}


ensure_column_exists($conn, 'tb_expenses', 'school_id', 'INT DEFAULT 1');
ensure_column_exists($conn, 'tb_expenses', 'receipt_photo', 'VARCHAR(255) NULL');
ensure_column_exists($conn, 'tb_expenses', 'created_by', 'VARCHAR(50) NULL');
ensure_column_exists($conn, 'tb_expenses', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

ensure_column_exists($conn, 'tb_students', 'school_id', 'INT DEFAULT 1');
ensure_column_exists($conn, 'tb_students', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

ensure_column_exists($conn, 'tb_schools', 'school_name_kh', 'VARCHAR(255) NULL');
ensure_column_exists($conn, 'tb_schools', 'logo', "VARCHAR(255) DEFAULT 'meakea.png'");
ensure_column_exists($conn, 'tb_schools', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

ensure_column_exists($conn, 'tb_course', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
ensure_column_exists($conn, 'tb_time', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
ensure_column_exists($conn, 'tb_study', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
ensure_column_exists($conn, 'tbl_certi', 'study_id', 'INT NOT NULL');
ensure_column_exists($conn, 'tbl_certi', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
ensure_column_exists($conn, 'tbl_att', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

// 3. Ensure Auto-Increment on Primary Keys
ensure_auto_increment($conn, 'tbl_att', 'id');
ensure_auto_increment($conn, 'tbl_certi', 'id');
ensure_auto_increment($conn, 'tb_invoices', 'id');
ensure_auto_increment($conn, 'tb_expenses', 'id');
ensure_auto_increment($conn, 'tb_study', 'id');
ensure_auto_increment($conn, 'tb_schools', 'id');
ensure_auto_increment($conn, 'tb_users', 'id');
ensure_auto_increment($conn, 'tb_time', 'id');

// Ensure default school exists
$schoolCount = $conn->query("SELECT COUNT(*) as count FROM tb_schools");
if ($schoolCount && $schoolCount->fetch_assoc()['count'] == 0) {
    $conn->query("INSERT INTO tb_schools (school_name, school_name_kh, logo) VALUES ('Meakea Computer', 'មាគ៌ាកុំព្យូទ័រ', 'meakea.png')");
}

// Ensure default admin user exists ONLY IF table is empty
$userCount = $conn->query("SELECT COUNT(*) as count FROM tb_users WHERE username = 'adminmeakea'");
if ($userCount && $userCount->fetch_assoc()['count'] == 0) {
    $defaultPass = password_hash('Meakkea@0968689680', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO tb_users (username, password, user_type, school_id) VALUES ('adminmeakea', '$defaultPass', 1, 0)");
}

// Include Global Functions & Language
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/lang.php';
?>
