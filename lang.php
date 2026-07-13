<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$selected_lang = $_SESSION['lang'] ?? 'en';

$en = [
    'dashboard' => 'Dashboard',
    'home' => 'Home',
    'students' => 'Students',
    'add_student' => 'Add Student',
    'register_study' => 'Register & Study',
    'study' => 'Study',
    'course' => 'Course',
    'grades' => 'Grades',
    'invoices' => 'Invoices',
    'finished_students' => 'Finished Students',
    'paid_list' => 'Paid List',
    'schools' => 'Schools',
    'users' => 'Users',
    'logout' => 'Logout',
    'admin' => 'Administrator',
    'normal_user' => 'Normal User',
    'switch_lang' => 'Language',
];

$kh = [
    'dashboard' => 'ផ្ទាំងគ្រប់គ្រង',
    'home' => 'ទំព័រដើម',
    'students' => 'សិស្ស',
    'add_student' => 'ចុះឈ្មោះសិស្ស',
    'register_study' => 'ចុះឈ្មោះ & សិក្សា',
    'study' => 'ការសិក្សា',
    'course' => 'វគ្គសិក្សា',
    'grades' => 'កម្រិត/ម៉ោង',
    'invoices' => 'វិក្កយបត្រ',
    'finished_students' => 'សិស្សបញ្ចប់',
    'paid_list' => 'បញ្ជីបង់ប្រាក់',
    'schools' => 'សាលា',
    'users' => 'អ្នកប្រើប្រាស់',
    'logout' => 'ចាកចេញ',
    'admin' => 'អ្នកគ្រប់គ្រង',
    'normal_user' => 'អ្នកប្រើទូទៅ',
    'switch_lang' => 'ភាសា',
];

$lang = ($selected_lang == 'kh') ? $kh : $en;

function getUrlWithLang($newLang) {
    $params = $_GET;
    $params['lang'] = $newLang;
    return '?' . http_build_query($params);
}
?>