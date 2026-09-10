<?php
// includes/sidebar.php - Navigation Sidebar & Topbar Component
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = get_logged_user();
$isAdmin = is_admin();
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';

function is_nav_active($paths, $currentUrl) {
    if (!is_array($paths)) $paths = [$paths];
    foreach ($paths as $path) {
        if (strpos($currentUrl, $path) !== false) return 'active';
    }
    return '';
}
?>
<!-- Sidebar Navigation -->
<aside class="sidebar" id="appSidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="<?php echo base_url('logo/meakea.png'); ?>" alt="Logo" onerror="this.src='<?php echo base_url('logo/meakea.jpg'); ?>';">
        <div class="sidebar-brand-text">
            <h2><?php echo $lang['app_name']; ?></h2>
            <span><?php echo $lang['app_subtitle']; ?></span>
        </div>
    </div>

    <!-- Menu Links -->
    <div class="sidebar-menu">
        <div class="sidebar-heading"><?php echo $lang['dashboard']; ?></div>
        
        <a href="<?php echo base_url('dashboard.php'); ?>" class="sidebar-link <?php echo (strpos($currentUrl, 'dashboard.php') !== false || basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>
            <span><?php echo $lang['home']; ?></span>
        </a>

        <div class="sidebar-heading"><?php echo $lang['students']; ?></div>

        <a href="<?php echo base_url('students/list_student.php'); ?>" class="sidebar-link <?php echo is_nav_active('list_student.php', $currentUrl); ?>">
            <i class="fa-solid fa-user-graduate"></i>
            <span><?php echo $lang['student_list']; ?></span>
        </a>

        <a href="<?php echo base_url('students/register_student_study.php'); ?>" class="sidebar-link <?php echo is_nav_active(['register_student_study.php', 'register_student.php'], $currentUrl); ?>">
            <i class="fa-solid fa-user-plus"></i>
            <span><?php echo $lang['register_study']; ?></span>
        </a>

        <a href="<?php echo base_url('study/list_study.php'); ?>" class="sidebar-link <?php echo is_nav_active(['study/list_study.php', 'study/add_study.php', 'study/edit_study.php'], $currentUrl); ?>">
            <i class="fa-solid fa-book-open-reader"></i>
            <span><?php echo $lang['study']; ?></span>
        </a>

        <a href="<?php echo base_url('students/finished.php'); ?>" class="sidebar-link <?php echo is_nav_active(['finished.php', 'finished_student.php'], $currentUrl); ?>">
            <i class="fa-solid fa-award"></i>
            <span><?php echo $lang['finished_students']; ?></span>
        </a>

        <div class="sidebar-heading"><?php echo $lang['course']; ?> & <?php echo $lang['grades']; ?></div>

        <a href="<?php echo base_url('courses/list_course.php'); ?>" class="sidebar-link <?php echo is_nav_active(['courses/list_course.php', 'courses/add_course.php', 'courses/edit_course.php'], $currentUrl); ?>">
            <i class="fa-solid fa-laptop-code"></i>
            <span><?php echo $lang['course']; ?></span>
        </a>

        <a href="<?php echo base_url('time/grades.php'); ?>" class="sidebar-link <?php echo is_nav_active(['time/grades.php', 'time/edit_time.php'], $currentUrl); ?>">
            <i class="fa-solid fa-clock"></i>
            <span><?php echo $lang['grades']; ?></span>
        </a>

        <div class="sidebar-heading"><?php echo $lang['invoices']; ?> & <?php echo $lang['expenses']; ?></div>

        <a href="<?php echo base_url('invoice/invoice.php'); ?>" class="sidebar-link <?php echo is_nav_active('invoice/invoice.php', $currentUrl); ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span><?php echo $lang['invoices']; ?></span>
        </a>

        <a href="<?php echo base_url('invoice/paid.php'); ?>" class="sidebar-link <?php echo is_nav_active('invoice/paid.php', $currentUrl); ?>">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo $lang['paid_list']; ?></span>
        </a>

        <a href="<?php echo base_url('expenses/list_expense.php'); ?>" class="sidebar-link <?php echo is_nav_active(['expenses/list_expense.php', 'expenses/edit_expense.php'], $currentUrl); ?>">
            <i class="fa-solid fa-wallet"></i>
            <span><?php echo $lang['expenses']; ?></span>
        </a>

        <div class="sidebar-heading"><?php echo $lang['schools']; ?> & <?php echo $lang['admin']; ?></div>

        <a href="<?php echo base_url('schools/add_school.php'); ?>" class="sidebar-link <?php echo is_nav_active('schools/add_school.php', $currentUrl); ?>">
            <i class="fa-solid fa-school"></i>
            <span><?php echo $lang['schools']; ?></span>
        </a>

        <?php if ($isAdmin): ?>
        <a href="<?php echo base_url('users/add_users.php'); ?>" class="sidebar-link <?php echo is_nav_active(['users/add_users.php', 'users/edit_user.php'], $currentUrl); ?>">
            <i class="fa-solid fa-users-gear"></i>
            <span><?php echo $lang['users']; ?></span>
        </a>

        <a href="<?php echo base_url('siem.php'); ?>" class="sidebar-link <?php echo is_nav_active('siem.php', $currentUrl); ?>">
            <i class="fa-solid fa-shield-virus"></i>
            <span><?php echo $lang['siem_logs']; ?></span>
        </a>
        <?php endif; ?>

        <a href="<?php echo base_url('logout.php'); ?>" class="sidebar-link" style="color: #f87171; margin-top: 15px;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span><?php echo $lang['logout']; ?></span>
        </a>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="mobile-menu-btn" onclick="document.getElementById('appSidebar').classList.toggle('show')">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                <?php if (!empty($page_subtitle)): ?>
                    <p class="page-subtitle"><?php echo htmlspecialchars($page_subtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="topbar-right" style="display: flex; align-items: center; gap: 14px;">
            <!-- Language Switcher in Topbar -->
            <div style="display: inline-flex; align-items: center; background: #f1f5f9; padding: 3px 6px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 13px;">
                <a href="<?php echo getUrlWithLang('kh'); ?>" style="text-decoration: none; padding: 2px 8px; border-radius: 4px; font-weight: 600; color: <?php echo $selected_lang === 'kh' ? '#ffffff' : '#64748b'; ?>; background: <?php echo $selected_lang === 'kh' ? 'var(--primary)' : 'transparent'; ?>;">ខ្មែរ</a>
                <a href="<?php echo getUrlWithLang('en'); ?>" style="text-decoration: none; padding: 2px 8px; border-radius: 4px; font-weight: 600; color: <?php echo $selected_lang === 'en' ? '#ffffff' : '#64748b'; ?>; background: <?php echo $selected_lang === 'en' ? 'var(--primary)' : 'transparent'; ?>;">EN</a>
            </div>

            <!-- Public Site Link -->
            <a href="<?php echo base_url('index.php'); ?>" target="_blank" class="btn btn-light btn-sm" title="Public Verification Portal">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> <?php echo $selected_lang === 'kh' ? 'ទំព័រមុខ' : 'Public Site'; ?>
            </a>

            <!-- Compact User Badge -->
            <div style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--border-color);">
                <div style="width: 26px; height: 26px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                    <?php echo strtoupper(substr($currentUser ?: 'U', 0, 1)); ?>
                </div>
                <span style="font-size: 13px; font-weight: 600; color: var(--text-main);">
                    <?php echo htmlspecialchars($currentUser ?: 'User'); ?>
                </span>
                <span class="badge <?php echo $isAdmin ? 'badge-danger' : 'badge-info'; ?>" style="font-size: 10px; padding: 2px 6px;">
                    <?php echo $isAdmin ? 'Admin' : 'User'; ?>
                </span>
            </div>
        </div>
    </header>

    <!-- Page Content Container -->
    <main class="content-area">
        <?php render_flash(); ?>
