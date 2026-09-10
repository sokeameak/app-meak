<?php
// includes/functions.php - Global Helper Functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Dynamic Base URL Resolver
 * Works seamlessly in root domain (localhost/) or subfolder (localhost/app-meakea/)
 */
function base_url($path = '') {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Determine the root of the project by checking relative position
    $root = preg_replace('#/(att|students|study|courses|time|invoice|schools|users|certificat|includes|logo|uploads|expenses).*$#i', '', $scriptDir);
    $root = rtrim($root, '/');
    $cleanPath = ltrim($path, '/');
    return empty($cleanPath) ? ($root ?: '/') : $root . '/' . $cleanPath;
}

/**
 * Authentication & Role Check Helpers
 */
function is_logged_in() {
    return !empty($_SESSION['user']);
}

function is_admin() {
    return isset($_SESSION['user_type']) && intval($_SESSION['user_type']) === 1;
}

function get_logged_user() {
    return $_SESSION['user'] ?? null;
}

function get_logged_school_id($conn = null) {
    if (isset($_SESSION['school_id'])) {
        return intval($_SESSION['school_id']);
    }
    if ($conn && is_logged_in()) {
        $username = get_logged_user();
        $stmt = $conn->prepare("SELECT school_id FROM tb_users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $_SESSION['school_id'] = intval($row['school_id']);
                return $_SESSION['school_id'];
            }
            $stmt->close();
        }
    }
    return 0;
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Please login to access this page.'
        ];
        header("Location: " . base_url('login.php'));
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Access denied: Administrator privilege required.'
        ];
        header("Location: " . base_url('dashboard.php'));
        exit;
    }
}

/**
 * Flash Notification Helpers
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $icon = $type === 'success' ? 'fa-circle-check' : ($type === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-info');
        $msg = htmlspecialchars($flash['message']);
        echo "<div class='alert alert-{$type}' style='padding: 12px 18px; margin-bottom: 20px; border-radius: 8px; font-size: 15px; display: flex; align-items: center; gap: 10px;'>";
        echo "<i class='fa-solid {$icon}'></i> <span>{$msg}</span>";
        echo "</div>";
    }
}

/**
 * CSRF Protection
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Secure Image Upload Function
 * Checks MIME type, extension, size limit and generates safe random name
 */
function safe_image_upload($fileArray, $targetDir, $maxSizeMb = 5) {
    if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

    // Check size limit (e.g. 5MB)
    if ($fileArray['size'] > ($maxSizeMb * 1024 * 1024)) {
        throw new Exception("File size exceeds maximum allowed limit of {$maxSizeMb}MB.");
    }

    // Validate MIME type via finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileArray['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception("Invalid file type ({$mimeType}). Only JPG, PNG, and WebP images are allowed.");
    }

    // Check extension
    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        throw new Exception("Invalid file extension (.{$ext}).");
    }

    // Generate safe unique filename
    $newFilename = uniqid('img_', true) . '.' . $ext;
    $targetPath = rtrim($targetDir, '/') . '/' . $newFilename;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (!move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload image.");
    }

    return $newFilename;
}

/**
 * Khmer Language & Formatting Helpers
 */
function to_khmer_num($num) {
    $khmerNums = ['0'=>'០', '1'=>'១', '2'=>'២', '3'=>'៣', '4'=>'៤', '5'=>'៥', '6'=>'៦', '7'=>'៧', '8'=>'៨', '9'=>'៩'];
    return strtr((string)$num, $khmerNums);
}

function khmer_date($dateStr) {
    if (empty($dateStr) || $dateStr === '0000-00-00' || $dateStr === '0000-00-00 00:00:00') return '-';
    $ts = is_numeric($dateStr) ? (int)$dateStr : strtotime((string)$dateStr);
    if (!$ts) return (string)$dateStr;
    
    $khmerMonths = [
        '01' => 'មករា', '02' => 'កុម្ភៈ', '03' => 'មីនា', '04' => 'មេសា',
        '05' => 'ឧសភា', '06' => 'មិថុនា', '07' => 'កក្កដា', '08' => 'សីហា',
        '09' => 'កញ្ញា', '10' => 'តុលា', '11' => 'វិច្ឆិកា', '12' => 'ធ្នូ'
    ];
    
    $day = to_khmer_num(date('d', $ts));
    $month = $khmerMonths[date('m', $ts)] ?? date('M', $ts);
    $year = to_khmer_num(date('Y', $ts));
    
    return "{$day} {$month} {$year}";
}

function khmer_gender($sex) {
    $s = strtolower(trim((string)$sex));
    if ($s === 'male' || $s === 'm' || $s === 'ប្រុស') return 'ប្រុស';
    if ($s === 'female' || $s === 'f' || $s === 'ស្រី') return 'ស្រី';
    return (string)$sex;
}

function format_money($amount) {
    return '$' . number_format((float)$amount, 2);
}

/**
 * SIEM Logging Helper
 */
if (!function_exists('log_siem_event')) {
    function log_siem_event($conn, $username, $action, $details = '') {
        if (!$conn) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt = $conn->prepare("INSERT INTO tb_siem_logs (username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $username, $action, $details, $ip, $ua);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>
