<?php
// view_certificate.php - Student Certificate View & Print
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("<div style='font-family: Arial; text-align: center; padding: 50px;'><h2>Invalid Certificate ID</h2><p><a href='index.php'>Back to Home</a></p></div>");
}

// Fetch study, student, course, and school details
$sql = "SELECT s.id as study_id, st.id as student_id, st.student_name, st.sex, st.dob, st.photo, 
        c.Course, c.CourseID, c.Note as course_note,
        s.start_date, s.end_date, 
        sch.school_name, sch.school_name_kh, sch.logo 
        FROM tb_study s 
        JOIN tb_students st ON s.id_stu = st.id 
        JOIN tb_course c ON s.id_code = c.id 
        LEFT JOIN tb_schools sch ON st.school_id = sch.id 
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("<div style='font-family: Arial; text-align: center; padding: 50px;'><h2>Certificate Record Not Found</h2><p><a href='index.php'>Back to Home</a></p></div>");
}

// Generate QR Code URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$target_url = $protocol . "://" . $host . base_url('view_certificate.php?id=' . $id);
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=" . urlencode($target_url);
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>លិខិតបញ្ជាក់ការសិក្សា - <?php echo htmlspecialchars($data['student_name']); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@400;600;700&family=Moul&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Kantumruy Pro', 'Battambang', Arial, sans-serif;
            background: #cbd5e1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 15px;
            min-height: 100vh;
        }

        .no-print-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
            z-index: 10;
        }

        .btn-action {
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        .btn-print { background: #1e3a8a; color: white; }
        .btn-print:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-back { background: white; color: #334155; border: 1px solid #cbd5e1; }
        .btn-back:hover { background: #f1f5f9; }

        /* Certificate Container */
        .cert-wrapper {
            width: 960px;
            height: 680px;
            background: white url('logo/border.jpg') no-repeat center center;
            background-size: 100% 100%;
            position: relative;
            box-shadow: 0 12px 35px rgba(0,0,0,0.2);
            padding: 55px 80px 55px 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            overflow: hidden;
        }

        /* Top-Left School Logo */
        .school-logo-box {
            position: absolute;
            top: 80px;
            left: 90px;
            text-align: center;
            width: 100px;
        }

        .school-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .school-title-kh {
            font-family: 'Moul', serif;
            font-size: 12px;
            color: #150581;
            line-height: 1.3;
            margin-top: 4px;
        }

        /* Top-Right Student Photo */
        .student-photo-box {
            position: absolute;
            top: 90px;
            right: 85px;
            text-align: center;
            width: 90px;
        }

        .student-photo-box img {
            width: 80px;
            height: 95px;
            object-fit: cover;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            display: block;
            margin: 0 auto;
        }

        .cert-id-tag {
            font-size: 15px;
            font-weight: bold;
            color: #060606;
            margin-top: 4px;
        }

        /* Titles */
        .cert-main-title {
            font-family: 'Moul', serif;
            font-size: 22px;
            color: #150581;
            margin-top: 45px;
            letter-spacing: 0.5px;
            line-height: 1.5;
        }

        .cert-subtitle {
            font-family: 'Moul', serif;
            font-size: 22px;
            color: #b91c1c;
            margin: 8px 0 6px;
        }

        /* Student Information */
        .student-details {
            font-size: 20px;
            color: #070707;
            margin: 6px 0;
            display: flex;
            align-items: baseline;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
            max-width: 680px;
        }

        .name-highlight {
            font-family: 'Moul', serif;
            color: #b91c1c;
            font-size: 16px;
            padding: 0 3px;
        }

        .course-highlight {
            font-family: 'Moul', serif;
            color: #b91c1c;
            font-size: 16px;
            margin: 6px 0;
            line-height: 1.4;
        }

        .cert-body-text {
            font-size: 20px;
            color: #0a0a0a;
            line-height: 1.6;
            max-width: 650px;
            margin: 4px 0;
        }

        /* Bottom Row: QR & Signature (Safely positioned above golden border) */
        .signature-row {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
            padding: 0 5px 60px 15px;
        }

        .qr-section {
            text-align: center;
            width: 100px;
        }

        .qr-section img {
            width: 68px;
            height: 68px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            padding: 2px;
            background: white;
            display: block;
            margin: 0 auto;
        }

        .qr-text {
            font-size: 9px;
            color: #64748b;
            margin-top: 3px;
            font-weight: 700;
            line-height: 1.1;
        }

        .sig-block {
            text-align: center;
            width: 220px;
        }

        .sig-date {
            font-size: 16px;
            color: #0f172a;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .sig-role {
            font-family: 'Moul', serif;
            font-size: 13px;
            color: #150581;
        }
         .name-sign {
            font-family: 'Moul', serif;
            font-size: 13px;
            color: #e60206;
            margin-left:75px;
        }

        .sig-stamp {
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Print Optimization */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                height: 100vh !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .cert-wrapper {
                box-shadow: none !important;
                width: 297mm !important;
                height: 210mm !important;
                max-width: 100vw !important;
                max-height: 100vh !important;
                background: white url('logo/border.jpg') no-repeat center center !important;
                background-size: 100% 100% !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                page-break-inside: avoid !important;
                padding: 55px 80px 48px 80px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar -->
    <div class="no-print-bar">
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> បោះពុម្ព (Print Certificate)
        </button>
        <?php if (is_logged_in()): ?>
            <a href="students/finished.php" class="btn-action btn-back">
                <i class="fa-solid fa-arrow-left"></i> ត្រឡប់ក្រោយ
            </a>
        <?php else: ?>
            <a href="index.php" class="btn-action btn-back">
                <i class="fa-solid fa-arrow-left"></i> ទំព័រដើម
            </a>
        <?php endif; ?>
    </div>

    <!-- Certificate Document -->
    <div class="cert-wrapper">
        <!-- School Logo & School Name -->
        <div class="school-logo-box">
            <img src="logo/<?php echo htmlspecialchars($data['logo'] ?: 'meakea.png'); ?>" alt="Logo" class="school-logo" onerror="this.src='logo/logo.png';">
            <div class="school-title-kh"><?php echo htmlspecialchars($data['school_name_kh'] ?: 'មាគ៌ាកុំព្យូទ័រ'); ?></div>
        </div>

        <!-- Student Photo & ID -->
        <div class="student-photo-box">
            <?php if (!empty($data['photo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($data['photo']); ?>" alt="Photo" onerror="this.style.display='none';">
            <?php endif; ?>
            <div class="cert-id-tag">លេខ: <?php echo htmlspecialchars(str_pad($data['student_id'] ?? $id, 4, '0', STR_PAD_LEFT)); ?></div>
        </div>

        <!-- Main Titles -->
        <h1 class="cert-main-title">លិខិតបញ្ជាក់ការសិក្សា
            <p>♡────୨💻ৎ────♡</p>
        </h1><br>
        <h2 class="cert-subtitle">សូមផ្ដល់ជូន</h2>

        <!-- Student Information Details -->
        <div class="student-details">
            <span>ឈ្មោះសិស្ស៖</span>
            <span class="name-highlight"><?php echo htmlspecialchars($data['student_name']); ?></span>
            <span style="margin-left: 10px;">ភេទ៖</span>
            <span class="name-highlight"><?php echo khmer_gender($data['sex']); ?></span>
            <span style="margin-left: 10px;">ថ្ងៃខែឆ្នាំកំណើត៖</span>
            <span class="name-highlight"><?php echo khmer_date($data['dob']); ?></span>
        </div>

        <!-- Certificate Body -->
        <div class="cert-body-text">
            បានបញ្ចប់វគ្គបណ្ដុះបណ្ដាលកុំព្យូទ័រលើផ្នែក<br>
            <div class="course-highlight"><?php echo htmlspecialchars($data['Course']); ?></div>
            ដោយជោគជ័យ បានចូលរៀនចាប់ពីថ្ងៃទី <?php echo khmer_date($data['start_date']); ?> ដល់ថ្ងៃទី <?php echo khmer_date($data['end_date']); ?>។
        </div>

        <!-- Signatures & Verification Row -->
        <div class="signature-row">
            <!-- Left: QR Code Verification -->
            <div class="qr-section">
                <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
                <div class="qr-text">Scan to Verify</div>
            </div>

            <!-- Right: Director Signature -->
            <div class="sig-block">
                <div class="sig-date">
                    ធ្វើពួក នៅថ្ងៃទី <?php echo khmer_date($data['end_date']); ?>
                </div>
                <div class="sig-role">គណៈគ្រប់គ្រង</div>
                <div class="sig-stamp">
                    <!-- Space for Official Stamp / Signature -->
                     <img src="logo/meakea.png" alt="Stamp" style="height: 40px; object-fit: contain;">
                     
                </div>
                <h3 class="name-sign">មាគ គា</h3>

            </div>
        </div>
    </div>
 
</body>
</html>