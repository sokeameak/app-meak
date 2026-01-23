<?php
session_start();
include '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$id = $_GET['id'] ?? '';
if (empty($id)) die("Invalid ID");

// Fetch details
$sql = "SELECT st.ID, st.student_name, st.sex, st.dob, st.photo, c.Course, s.start_date, s.end_date, sch.school_name, sch.school_name_kh, sch.logo 
        FROM tb_study s 
        JOIN tb_students st ON s.id_stu = st.ID 
        JOIN tb_course c ON s.id_code = c.ID 
        LEFT JOIN tb_schools sch ON st.school_id = sch.id
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) die("Record not found");

$khmer_months = [
    '01' => 'មករា', '02' => 'កុម្ភៈ', '03' => 'មីនា', '04' => 'មេសា',
    '05' => 'ឧសភា', '06' => 'មិថុនា', '07' => 'កក្កដា', '08' => 'សីហា',
    '09' => 'កញ្ញា', '10' => 'តុលា', '11' => 'វិច្ឆិកា', '12' => 'ធ្នូ'
];
?>
<!DOCTYPE html>
<html>
<head>
<title>Certificate - <?php echo htmlspecialchars($data['student_name']); ?></title>
<style>
    body { font-family: Arial, 'Khmer OS', sans-serif; background: #f0f0f0; text-align: center; padding: 50px; }
    .certificate { width: 800px; margin: 0 auto; background: white; padding: 50px; border: 10px solid #091077; position: relative; box-shadow: 0 0 20px rgba(6, 3, 215, 0.1); }
    .header { font-size: 40px; font-weight: bold; color: #150581; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
    .sub-header { font-size: 20px; margin-bottom: 40px; font-style: italic; color: #555; }
    .name { font-size: 20px;  border-bottom: 2px solid #d80000; display: inline-block; padding: 0 40px; margin: 20px 0; color: #2c3e50; }
    .body-text { font-size: 20px; margin: 10px 0; color: #010243; font-family: 'Khmer OS';}
    .course { font-size: 28px; font-weight: bold; margin: 20px 0; color: #e67e22; }
    .date-range { margin-top: 20px; font-size: 16px; color: #040e7c; }
    .signature-section { margin-top: 80px; display: flex; justify-content: space-around; padding: 0 20px; }
    .sig-block { text-align: center; }
    .sig-line { border-top: 1px solid #333; width: 200px; margin: 0 auto 10px; }
    .sig-title { font-weight: bold; color: #07036c; }
    
    @media print {
        body { background: white; padding: 0; margin: 0;color: black; }
        .certificate { border: 5px solid #050f75; width: 100%; height: 100vh; box-shadow: none; box-sizing: border-box; margin: 0;text-align: center; }
        .no-print { display: none; }
        .header{ font-size: 32px;font-family: 'Khmer OS Muol light';}
        .name{ font-size: 20px;font-family: 'Khmer OS Muol light';color:red;}
        .lname{font-family: 'Khmer OS';color: #010243;} 
        .lsex{font-family: 'Khmer OS';color: #010243;}
        .ldob{font-family: 'Khmer OS';color: #010243;}
        .give{ font-size: 24px;font-family: 'Khmer OS Muol light';}
        .body-text{ font-size: 25px;font-family: 'Khmer OS';}
    }
   
</style>
</head>
<body>
    <div class="certificate">
       <img src="../logo/<?php echo htmlspecialchars($data['logo'] ?? 'meakea.jpg'); ?>" alt="Logo" style="width: 100px; margin-bottom: 10px;text-align:left;position:absolute;top:20px;left:20px;">
       <div style="position:absolute; top:100px; left:20px; width:100px; text-align:center; font-family: 'Khmer OS Muol light'; font-size: 12px; color: #150581;"><?php echo htmlspecialchars($data['school_name_kh'] ?? 'មាគ៌ាកុំព្យូទ័រ'); ?></div>
      
       <?php if (!empty($data['photo'])): ?>
           <img src="../uploads/<?php echo htmlspecialchars($data['photo']); ?>" alt="Student Photo" style="width: 100px; height: 120px; object-fit: cover; position: absolute; top: 20px; right: 20px; border: 1px solid #ddd;">
       <?php endif; ?>
       <div style="position: absolute; top: 145px; right: 20px; width: 100px; text-align: center; font-weight: bold; font-size: 14px; color: #000;">លេខ: <?php echo htmlspecialchars(str_pad($data['ID'], 6, '0', STR_PAD_LEFT)); ?></div>
       <h1 class="header"​ style="font-family: 'Khmer OS Muol light';font-size: 30px;">លិខិតបញ្ជាក់ការសិក្សា</h1>
       
        <h2 class="give" style="font-family: 'Khmer OS Muol light';">សូមផ្ដល់ជូន</h2>
        <div class="name" style="font-family: 'Khmer OS Muol light';">
            <label class="lname">ឈ្មោះសិស្ស</label>
            <?php echo htmlspecialchars($data['student_name']); ?>
            <label class="lsex">ភេទ</label>
            <?php echo htmlspecialchars($data['sex'] === 'Male' ? 'ប្រុស' : ($data['sex'] === 'Female' ? 'ស្រី' : $data['sex'])); ?>
            <label class="ldob">ថ្ងៃខែឆ្នំាកំណើត</label>
            <?php 
                $dob_ts = strtotime($data['dob']);
                $khmer_nums = ['0'=>'០', '1'=>'១', '2'=>'២', '3'=>'៣', '4'=>'៤', '5'=>'៥', '6'=>'៦', '7'=>'៧', '8'=>'៨', '9'=>'៩'];
                $day = strtr(date('d', $dob_ts), $khmer_nums);
                $year = strtr(date('Y', $dob_ts), $khmer_nums);
                echo $day . ' ' . $khmer_months[date('m', $dob_ts)] . ' ' . $year; 
            ?>
    </div>
        
        <div class="body-text">
        បានបញ្ចប់វគ្គបណ្ដុះបណ្ដាល កុំព្យូទ័រ លើផ្នែក<br> <b style="font-family: 'Khmer Muol light';">រដ្ឋបាលទូទៅ និង អ៊ិនធឺណែត </b>ដោយជោគជ័យ។
        </div>
        
        <div class="study-date" style="font-family: 'Khmer OS'; font-size: 18px; margin-top: 10px;">
            <?php 
                $start_ts = strtotime($data['start_date']);
                $end_ts = strtotime($data['end_date']);
                $khmer_nums = ['0'=>'០', '1'=>'១', '2'=>'២', '3'=>'៣', '4'=>'៤', '5'=>'៥', '6'=>'៦', '7'=>'៧', '8'=>'៨', '9'=>'៩'];
                
                $s_day = strtr(date('d', $start_ts), $khmer_nums);
                $s_year = strtr(date('Y', $start_ts), $khmer_nums);
                $e_day = strtr(date('d', $end_ts), $khmer_nums);
                $e_year = strtr(date('Y', $end_ts), $khmer_nums);

                echo "សិក្សាចាប់ពីថ្ងៃទី " . $s_day . " ខែ " . $khmer_months[date('m', $start_ts)] . " ឆ្នាំ " . $s_year . " ដល់ថ្ងៃទី " . $e_day . " ខែ " . $khmer_months[date('m', $end_ts)] . " ឆ្នាំ " . $e_year; 
            ?>
        </div>
        
        <div class="signature-section" style="margin-top: 40px; display: flex; justify-content: flex-end; padding-right: 60px;">
            <div style="text-align: center;">
                <div class="date-range" style="font-family: 'Khmer OS'; margin-top: 0; margin-bottom: 10px; font-size: 16px; color: #040e7c;">
                    <?php 
                        $end_ts = strtotime($data['end_date']);
                        $khmer_nums = ['0'=>'០', '1'=>'១', '2'=>'២', '3'=>'៣', '4'=>'៤', '5'=>'៥', '6'=>'៦', '7'=>'៧', '8'=>'៨', '9'=>'៩'];
                        $day = strtr(date('d', $end_ts), $khmer_nums);
                        $year = strtr(date('Y', $end_ts), $khmer_nums);
                        echo "ពួក ថ្ងៃទី " . $day . " ខែ " . $khmer_months[date('m', $end_ts)] . " ឆ្នាំ " . $year; 
                    ?>
                </div>
                <div class="sig-title" style="font-family: 'Khmer OS Muol Light';">គណៈគ្រប់គ្រង</div>
                <img src="../logo/meakea.png" alt="Signature" style="width: 120px; margin: 5px auto;">
                <h2 style="font-family: 'Khmer OS Muol Light'; color: #a50000; margin: 0;">មាគ គា</h2>
            </div>
        </div>
        
        <?php
        // Generate QR Code URL pointing to the current page
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $currentLink = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($currentLink);
        ?>
        <div style="position: absolute; bottom: 30px; left: 30px; text-align: center;">
            <img src="<?php echo $qrCodeUrl; ?>" alt="Scan to Verify" style="width: 90px; height: 90px; border: 1px solid #eee; padding: 2px;">
            <div style="font-size: 10px; margin-top: 2px; font-family: Arial; color: #333;">Scan to Verify</div>
        </div>

        <div class="no-print" style="margin-top: 40px;">
            <button onclick="window.print()" style="padding: 12px 24px; background: #3498db; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 16px;">Print Certificate</button>
        </div>
    </div>
</body>
</html>