<?php
header('Content-Type: application/json; charset=utf-8');

$jsonFile = __DIR__ . '/data_update.json';
if (!file_exists($jsonFile)) {
    echo json_encode(['error' => 'data_update.json not found']);
    exit;
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data || !isset($data['tables'])) {
    echo json_encode(['error' => 'Invalid JSON structure']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', '');
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

// We will update 'mk' (local app database) and also 'meakncva_mk' (specified in JSON)
$targetDatabases = ['mk', 'meakncva_mk'];
$results = [];

foreach ($targetDatabases as $dbName) {
    // Ensure database exists
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($dbName);

    // Ensure required tables exist if in meakncva_mk (clone schema from mk if needed)
    if ($dbName === 'meakncva_mk') {
        foreach (['tb_schools', 'tb_course', 'tb_invoices', 'tb_siem_logs', 'tbl_certi', 'tb_students', 'tb_study'] as $t) {
            $check = $conn->query("SHOW TABLES LIKE '$t'");
            if ($check && $check->num_rows === 0) {
                $conn->query("CREATE TABLE `$dbName`.`$t` LIKE `mk`.`$t`");
            }
        }
    }

    $dbResult = [];

    // 1. Update tbl_certi
    if (isset($data['tables']['tbl_certi'])) {
        $stmt = $conn->prepare("INSERT INTO tbl_certi (id, id_student, id_study, status, study_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                study_id = VALUES(study_id),
                created_at = VALUES(created_at),
                id_student = IF(VALUES(id_student) != 0, VALUES(id_student), id_student),
                id_study = IF(VALUES(id_study) != 0, VALUES(id_study), id_study)");
        
        $inserted = 0;
        $updated = 0;
        foreach ($data['tables']['tbl_certi'] as $row) {
            $id = intval($row['id']);
            $id_student = intval($row['id_student'] ?? 0);
            $id_study = intval($row['id_study'] ?? 0);
            $status = intval($row['status'] ?? 0);
            $study_id = intval($row['study_id']);
            $created_at = $row['created_at'] ?? date('Y-m-d H:i:s');
            
            $stmt->bind_param("iiiiis", $id, $id_student, $id_study, $status, $study_id, $created_at);
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) $inserted++;
                elseif ($stmt->affected_rows === 2) $updated++;
            }
        }
        $stmt->close();

        // Safety backfill id_student and id_study if they are 0 and exist in tb_study
        $conn->query("UPDATE tbl_certi c 
                      JOIN tb_study s ON c.study_id = s.id 
                      SET c.id_student = s.id_stu, c.id_study = s.id 
                      WHERE (c.id_student = 0 OR c.id_student IS NULL)");

        $dbResult['tbl_certi'] = ['inserted' => $inserted, 'updated' => $updated, 'total_processed' => count($data['tables']['tbl_certi'])];
    }

    // 2. Update tb_course
    if (isset($data['tables']['tb_course'])) {
        $stmt = $conn->prepare("INSERT INTO tb_course (ID, CourseID, Course, Note)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                CourseID = VALUES(CourseID),
                Course = VALUES(Course),
                Note = VALUES(Note)");
        
        $inserted = 0;
        $updated = 0;
        foreach ($data['tables']['tb_course'] as $row) {
            $id = intval($row['ID']);
            $course_id = $row['CourseID'];
            $course = $row['Course'];
            $note = $row['Note'] ?? '';

            $stmt->bind_param("isss", $id, $course_id, $course, $note);
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) $inserted++;
                elseif ($stmt->affected_rows === 2) $updated++;
            }
        }
        $stmt->close();
        $dbResult['tb_course'] = ['inserted' => $inserted, 'updated' => $updated, 'total_processed' => count($data['tables']['tb_course'])];
    }

    // 3. Update tb_invoices
    if (isset($data['tables']['tb_invoices'])) {
        $stmt = $conn->prepare("INSERT INTO tb_invoices (id, student_name, description, amount, status, created_at, study_time, school_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                student_name = VALUES(student_name),
                description = VALUES(description),
                amount = VALUES(amount),
                status = VALUES(status),
                created_at = VALUES(created_at),
                study_time = VALUES(study_time),
                school_id = VALUES(school_id)");
        
        $inserted = 0;
        $updated = 0;
        foreach ($data['tables']['tb_invoices'] as $row) {
            $id = intval($row['id']);
            $student_name = $row['student_name'];
            $description = $row['description'] ?? '';
            $amount = floatval($row['amount']);
            $status = intval($row['status'] ?? 0);
            $created_at = $row['created_at'] ?? date('Y-m-d H:i:s');
            $study_time = $row['study_time'] ?? '';
            $school_id = intval($row['school_id'] ?? 0);

            $stmt->bind_param("issdisis", $id, $student_name, $description, $amount, $status, $created_at, $study_time, $school_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) $inserted++;
                elseif ($stmt->affected_rows === 2) $updated++;
            }
        }
        $stmt->close();
        $dbResult['tb_invoices'] = ['inserted' => $inserted, 'updated' => $updated, 'total_processed' => count($data['tables']['tb_invoices'])];
    }

    // 4. Update tb_schools
    if (isset($data['tables']['tb_schools'])) {
        $stmt = $conn->prepare("INSERT INTO tb_schools (id, school_name, school_name_kh, logo)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                school_name = VALUES(school_name),
                school_name_kh = VALUES(school_name_kh),
                logo = VALUES(logo)");
        
        $inserted = 0;
        $updated = 0;
        foreach ($data['tables']['tb_schools'] as $row) {
            $id = intval($row['id']);
            $school_name = $row['school_name'];
            $school_name_kh = $row['school_name_kh'];
            $logo = $row['logo'];

            $stmt->bind_param("isss", $id, $school_name, $school_name_kh, $logo);
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) $inserted++;
                elseif ($stmt->affected_rows === 2) $updated++;
            }
        }
        $stmt->close();
        $dbResult['tb_schools'] = ['inserted' => $inserted, 'updated' => $updated, 'total_processed' => count($data['tables']['tb_schools'])];
    }

    // 5. Update tb_siem_logs
    if (isset($data['tables']['tb_siem_logs'])) {
        $stmt = $conn->prepare("INSERT INTO tb_siem_logs (id, username, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                action = VALUES(action),
                details = VALUES(details),
                ip_address = COALESCE(VALUES(ip_address), ip_address),
                user_agent = COALESCE(VALUES(user_agent), user_agent),
                created_at = COALESCE(VALUES(created_at), created_at)");
        
        $inserted = 0;
        $updated = 0;
        foreach ($data['tables']['tb_siem_logs'] as $row) {
            $id = intval($row['id']);
            $username = $row['username'] ?? 'admin';
            $action = $row['action'] ?? 'SYSTEM';
            $details = preg_replace('/\[cite:\s*\d+\]/', '', $row['details'] ?? '');
            $ip_address = $row['ip_address'] ?? '::1';
            $user_agent = $row['user_agent'] ?? 'Mozilla/5.0';
            $created_at = $row['created_at'] ?? date('Y-m-d H:i:s');

            $stmt->bind_param("issssss", $id, $username, $action, $details, $ip_address, $user_agent, $created_at);
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) $inserted++;
                elseif ($stmt->affected_rows === 2) $updated++;
            }
        }
        $stmt->close();
        $dbResult['tb_siem_logs'] = ['inserted' => $inserted, 'updated' => $updated, 'total_processed' => count($data['tables']['tb_siem_logs'])];
    }

    $results[$dbName] = $dbResult;
}

echo json_encode(['status' => 'success', 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
