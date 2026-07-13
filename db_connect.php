<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "mk";

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// SIEM Logging Function
if (!function_exists('log_siem_event')) {
    function log_siem_event($conn, $username, $action, $details = '') {
        // Ensure table exists
        $conn->query("CREATE TABLE IF NOT EXISTS tb_siem_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            action VARCHAR(50),
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (username), INDEX (action), INDEX (created_at)
        )");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt = $conn->prepare("INSERT INTO tb_siem_logs (username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) { $stmt->bind_param("sssss", $username, $action, $details, $ip, $ua); $stmt->execute(); $stmt->close(); }
    }
}

include_once __DIR__ . '/lang.php';
?>
