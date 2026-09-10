<?php
// lang.php - Language Localization File (English / Khmer)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $allowedLangs = ['en', 'kh'];
    if (in_array($_GET['lang'], $allowedLangs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

$selected_lang = $_SESSION['lang'] ?? 'kh'; // Default to Khmer

$en = [
    // App & Brand
    'app_name' => 'Meakea Computer',
    'app_subtitle' => 'Student Management System',
    
    // Navigation
    'dashboard' => 'Dashboard',
    'home' => 'Home',
    'students' => 'Students',
    'student_list' => 'Student List',
    'add_student' => 'Add Student',
    'register_study' => 'Register & Study',
    'study' => 'Study Records',
    'course' => 'Courses',
    'grades' => 'Time Slots',
    'invoices' => 'Invoices & Billing',
    'expenses' => 'Expenses',
    'add_expense' => 'Add Expense',
    'finished_students' => 'Finished Students',
    'paid_list' => 'Paid List',
    'schools' => 'Schools / Branches',
    'users' => 'User Accounts',
    'siem_logs' => 'Security (SIEM) Logs',
    'logout' => 'Logout',
    'login' => 'Login',
    
    // User Roles
    'admin' => 'Administrator',
    'normal_user' => 'Normal User',
    'switch_lang' => 'Language',
    'welcome_back' => 'Welcome back',
    
    // Dashboard Stats
    'total_students' => 'Total Students',
    'active_students' => 'Active Students',
    'monthly_income' => 'Monthly Revenue',
    'monthly_expenses' => 'Monthly Expenses',
    'net_profit' => 'Net Profit',
    'pending_balance' => 'Unpaid Balance',
    
    // Form & Table Fields
    'photo' => 'Photo',
    'student_name' => 'Student Name',
    'sex' => 'Gender',
    'male' => 'Male',
    'female' => 'Female',
    'dob' => 'Date of Birth',
    'school' => 'School',
    'all_schools' => 'All Schools',
    'course_name' => 'Course Name',
    'time_slot' => 'Time Slot',
    'all_times' => 'All Time Slots',
    'price' => 'Price',
    'paid' => 'Paid',
    'remain' => 'Remaining Balance',
    'status' => 'Status',
    'studying' => 'Studying',
    'finished' => 'Finished',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'actions' => 'Actions',
    'attendance' => 'Attendance',
    'absent' => 'Absent',
    'present' => 'Present',
    'other_notes' => 'Other Notes / Remark',
    'description' => 'Description',
    'amount' => 'Amount',
    'username' => 'Username',
    'password' => 'Password',
    'user_role' => 'Role',
    'created_at' => 'Date Created',
    
    // Expense Fields
    'expense_title' => 'Expense Title',
    'expense_category' => 'Category',
    'expense_date' => 'Expense Date',
    'receipt' => 'Receipt / Voucher',
    'total_expenses' => 'Total Expenses',
    
    // Buttons & Actions
    'filter' => 'Filter',
    'search' => 'Search',
    'save' => 'Save',
    'add_new' => 'Add New',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'view' => 'View',
    'print' => 'Print',
    'back' => 'Back',
    'cancel' => 'Cancel',
    'confirm_delete' => 'Are you sure you want to delete this record?',
    'issue_cert' => 'Issue Certificate',
    'view_cert' => 'View Certificate',
    'no_records' => 'No records found.',
    'table_view' => 'Table View',
    'card_view' => 'Card View',
    'record_attendance' => 'Mark Absent',
    'attendance_recorded' => 'Attendance recorded successfully',
    
    // Messages
    'login_success' => 'Successfully logged in.',
    'login_invalid' => 'Invalid username or password.',
    'fill_required' => 'Please fill in all required fields.',
    'saved_success' => 'Record saved successfully!',
    'deleted_success' => 'Record deleted successfully!',
    'error_occurred' => 'An error occurred. Please try again.'
];

$kh = [
    // App & Brand
    'app_name' => 'មាគ៌ាកុំព្យូទ័រ',
    'app_subtitle' => 'ប្រព័ន្ធគ្រប់គ្រងសិស្ស',
    
    // Navigation
    'dashboard' => 'ផ្ទាំងព័ត៌មានទូទៅ',
    'home' => 'ទំព័រដើម',
    'students' => 'សិស្ស',
    'student_list' => 'បញ្ជីសិស្សទាំងអស់',
    'add_student' => 'ចុះឈ្មោះសិស្សថ្មី',
    'register_study' => 'ចុះឈ្មោះ & បន្ថែមការសិក្សា',
    'study' => 'កាលវិភាគសិក្សា',
    'course' => 'វគ្គសិក្សា',
    'grades' => 'ម៉ោងសិក្សា',
    'invoices' => 'វិក្កយបត្រ & ការទូទាត់',
    'expenses' => 'ការចំណាយ',
    'add_expense' => 'កត់ត្រាការចំណាយ',
    'finished_students' => 'សិស្សបញ្ចប់ការសិក្សា',
    'paid_list' => 'ប្រាក់ចំណូលបានបង់រួច',
    'schools' => 'សាលា / សាខា',
    'users' => 'គណនីអ្នកប្រើប្រាស់',
    'siem_logs' => 'កំណត់ហេតុសុវត្ថិភាព (SIEM)',
    'logout' => 'ចាកចេញ',
    'login' => 'ចូលប្រើប្រាស់',
    
    // User Roles
    'admin' => 'អ្នកគ្រប់គ្រង (Admin)',
    'normal_user' => 'បុគ្គលិកទូទៅ',
    'switch_lang' => 'ភាសា',
    'welcome_back' => 'សូមស្វាគមន៍មកកាន់ប្រព័ន្ធ',
    
    // Dashboard Stats
    'total_students' => 'ចំនួនសិស្សសរុប',
    'active_students' => 'សិស្សកំពុងរៀន',
    'monthly_income' => 'ចំណូលប្រចាំខែ',
    'monthly_expenses' => 'ចំណាយប្រចាំខែ',
    'net_profit' => 'ប្រាក់ចំណេញសុទ្ធ',
    'pending_balance' => 'ប្រាក់មិនទាន់ទូទាត់',
    
    // Form & Table Fields
    'photo' => 'រូបថត',
    'student_name' => 'ឈ្មោះសិស្ស',
    'sex' => 'ភេទ',
    'male' => 'ប្រុស',
    'female' => 'ស្រី',
    'dob' => 'ថ្ងៃខែឆ្នាំកំណើត',
    'school' => 'សាលា / សាខា',
    'all_schools' => 'គ្រប់សាលាទាំងអស់',
    'course_name' => 'ឈ្មោះវគ្គសិក្សា',
    'time_slot' => 'ម៉ោងសិក្សា',
    'all_times' => 'គ្រប់ម៉ោងសិក្សា',
    'price' => 'តម្លៃសិក្សា',
    'paid' => 'បានបង់',
    'remain' => 'នៅខ្វះ',
    'status' => 'ស្ថានភាព',
    'studying' => 'កំពុងរៀន',
    'finished' => 'បានបញ្ចប់',
    'start_date' => 'ថ្ងៃចូលរៀន',
    'end_date' => 'ថ្ងៃបញ្ចប់',
    'actions' => 'សកម្មភាព',
    'attendance' => 'វត្តមាន',
    'absent' => 'អវត្តមាន',
    'present' => 'មានវត្តមាន',
    'other_notes' => 'កំណត់ចំណាំ / ផ្សេងៗ',
    'description' => 'បរិយាយ',
    'amount' => 'ចំនួនទឹកប្រាក់',
    'username' => 'ឈ្មោះអ្នកប្រើ',
    'password' => 'ពាក្យសម្ងាត់',
    'user_role' => 'កម្រិតសិទ្ធិ',
    'created_at' => 'កាលបរិច្ឆេទបង្កើត',
    
    // Expense Fields
    'expense_title' => 'ចំណងជើងការចំណាយ',
    'expense_category' => 'ប្រភេទចំណាយ',
    'expense_date' => 'កាលបរិច្ឆេទចំណាយ',
    'receipt' => 'បង្កាន់ដៃ/វិក្កយបត្រ',
    'total_expenses' => 'សរុបការចំណាយ',
    
    // Buttons & Actions
    'filter' => 'ចម្រាញ់ទិន្នន័យ',
    'search' => 'ស្វែងរក',
    'save' => 'រក្សាទុក',
    'add_new' => 'បន្ថែមថ្មី',
    'edit' => 'កែប្រែ',
    'delete' => 'លុបចេញ',
    'view' => 'មើល',
    'print' => 'បោះពុម្ព',
    'back' => 'ត្រឡប់ក្រោយ',
    'cancel' => 'បោះបង់',
    'confirm_delete' => 'តើអ្នកពិតជាចង់លុបទិន្នន័យនេះមែនទេ?',
    'issue_cert' => 'ចេញវិញ្ញាបនបត្រ',
    'view_cert' => 'មើលវិញ្ញាបនបត្រ',
    'no_records' => 'មិនមានទិន្នន័យត្រូវបានរកឃើញឡើយ។',
    'table_view' => 'ទម្រង់តារាង',
    'card_view' => 'ទម្រង់កាតរូបថត',
    'record_attendance' => 'កត់អវត្តមាន',
    'attendance_recorded' => 'បានកត់ត្រាវត្តមានដោយជោគជ័យ',
    
    // Messages
    'login_success' => 'បានចូលប្រើប្រាស់ប្រព័ន្ធដោយជោគជ័យ',
    'login_invalid' => 'ឈ្មោះអ្នកប្រើប្រាស់ ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវឡើយ',
    'fill_required' => 'សូមបំពេញព័ត៌មានដែលចាំបាច់ទាំងអស់។',
    'saved_success' => 'បានរក្សាទុកទិន្នន័យដោយជោគជ័យ!',
    'deleted_success' => 'បានលុបទិន្នន័យដោយជោគជ័យ!',
    'error_occurred' => 'មានបញ្ហាមិនប្រក្រតីកើតឡើង។ សូមព្យាយាមម្តងទៀត។'
];

$lang = ($selected_lang === 'kh') ? $kh : $en;

if (!function_exists('getUrlWithLang')) {
    function getUrlWithLang($newLang) {
        $params = $_GET;
        $params['lang'] = $newLang;
        return '?' . http_build_query($params);
    }
}