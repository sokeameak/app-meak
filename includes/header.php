<?php
// includes/header.php - Global Layout Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db_connect.php';

$page_title = $page_title ?? $lang['app_name'];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="<?php echo $selected_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo $lang['app_name']; ?></title>
    
    <!-- Google Fonts: Kantumruy Pro (Khmer) & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Kantumruy+Pro:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Moul&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #1e3a8a;
            --primary-hover: #1e40af;
            --primary-light: #eff6ff;
            --secondary: #3b82f6;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --font-khmer: 'Kantumruy Pro', 'Battambang', Arial, sans-serif;
            --font-muol: 'Moul', 'Khmer OS Muol light', serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-khmer);
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            line-height: 1.6;
        }

        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.2);
        }

        .sidebar-brand img {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: white;
            padding: 2px;
        }

        .sidebar-brand-text h2 {
            font-family: var(--font-muol);
            font-size: 16px;
            color: #ffffff;
            line-height: 1.3;
        }

        .sidebar-brand-text span {
            font-size: 11px;
            color: var(--sidebar-text);
            display: block;
        }

        .sidebar-user {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.02);
        }

        .sidebar-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(37,99,235,0.3);
        }

        .sidebar-user-info {
            flex: 1;
            overflow: hidden;
        }

        .sidebar-username {
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-role {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 3px;
        }

        .role-admin {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .role-user {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .lang-picker {
            display: flex;
            gap: 4px;
            padding: 10px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            align-items: center;
            font-size: 12px;
        }

        .lang-btn {
            padding: 3px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--sidebar-text);
            font-weight: 600;
            transition: all 0.2s;
        }

        .lang-btn.active {
            background: var(--primary-hover);
            color: white;
        }

        .sidebar-menu {
            padding: 15px 12px;
            flex: 1;
        }

        .sidebar-heading {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 10px 12px 6px;
            font-weight: 700;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 3px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 15px;
        }

        .sidebar-link:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
            transform: translateX(3px);
        }

        .sidebar-link.active {
            background-color: var(--sidebar-active);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        /* Main Content */
        .main-wrapper {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            background: var(--surface);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--text-muted);
        }

        .content-area {
            padding: 24px 28px;
            flex: 1;
        }

        /* Modern Cards */
        .card {
            background: var(--surface);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Grid for stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 22px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.blue { background: #eff6ff; color: #2563eb; }
        .stat-icon.green { background: #ecfdf5; color: #059669; }
        .stat-icon.amber { background: #fffbeb; color: #d97706; }
        .stat-icon.red { background: #fef2f2; color: #dc2626; }
        .stat-icon.purple { background: #faf5ff; color: #7c3aed; }

        .stat-content h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .stat-content .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.2;
        }

        /* Modern Tables */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: white;
            text-align: left;
        }

        .table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table tr:hover {
            background-color: #f8fafc;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            font-family: var(--font-khmer);
            font-size: 14px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary { background: var(--secondary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); color: white; }

        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; color: white; }

        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; color: white; }

        .btn-warning { background: var(--warning); color: white; }
        .btn-warning:hover { background: #d97706; color: white; }

        .btn-secondary { background: #64748b; color: white; }
        .btn-secondary:hover { background: #475569; color: white; }

        .btn-light { background: #f1f5f9; color: #334155; border: 1px solid var(--border-color); }
        .btn-light:hover { background: #e2e8f0; }

        .btn-sm { padding: 4px 10px; font-size: 12px; }

        /* Form Controls */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-main);
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: var(--font-khmer);
            font-size: 14px;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            line-height: 1;
        }

        .badge-success { background: var(--success-light); color: #065f46; border: 1px solid #a7f3d0; }
        .badge-warning { background: var(--warning-light); color: #92400e; border: 1px solid #fde68a; }
        .badge-danger { background: var(--danger-light); color: #991b1b; border: 1px solid #fecaca; }
        .badge-info { background: var(--primary-light); color: #1e40af; border: 1px solid #bfdbfe; }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-success { background: var(--success-light); color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: var(--danger-light); color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: var(--warning-light); color: #92400e; border: 1px solid #fde68a; }

        /* Student Grid Cards */
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .student-card {
            background: white;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .student-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .student-photo {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .student-photo-placeholder {
            width: 100%;
            height: 180px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 40px;
        }

        .student-card-body {
            padding: 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .student-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        /* Mobile responsive */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-main);
            cursor: pointer;
        }

        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
            }
        }

        /* General Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            overflow-y: auto;
            padding: 20px;
            align-items: center;
            justify-content: center;
        }
        .modal.show, .modal.active {
            display: flex !important;
        }
        .modal-dialog {
            background: white;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 540px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: modalSlideIn 0.25s ease-out;
            position: relative;
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-header {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            line-height: 1;
        }
        .modal-close:hover {
            color: var(--danger);
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 14px 20px;
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .student-pay-link {
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.15s;
        }
        .student-pay-link:hover {
            color: #059669;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="app-layout">
