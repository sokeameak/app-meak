-- ==========================================================
-- Database Schema for App-Meakea (Student Management System)
-- Database Name: `mk`
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `mk` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mk`;

-- 1. Schools Table (តារាងសាលា/សាខា)
CREATE TABLE IF NOT EXISTS `tb_schools` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `school_name` VARCHAR(255) NOT NULL,
    `school_name_kh` VARCHAR(255) NOT NULL,
    `logo` VARCHAR(255) DEFAULT 'meakea.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table (តារាងគណនីអ្នកប្រើប្រាស់)
CREATE TABLE IF NOT EXISTS `tb_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `user_type` INT DEFAULT 0 COMMENT '1: Admin, 0: Normal User',
    `school_id` INT DEFAULT 0 COMMENT '0: All Schools (Admin), >0: Branch ID',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`user_type`),
    INDEX (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Courses Table (តារាងវគ្គសិក្សា)
CREATE TABLE IF NOT EXISTS `tb_course` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `CourseID` VARCHAR(50) NOT NULL UNIQUE,
    `Course` VARCHAR(255) NOT NULL,
    `Note` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Time Slots Table (តារាងម៉ោងសិក្សា)
CREATE TABLE IF NOT EXISTS `tb_time` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `time` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Students Table (តារាងព័ត៌មានផ្ទាល់ខ្លួនរបស់សិស្ស)
CREATE TABLE IF NOT EXISTS `tb_students` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `student_name` VARCHAR(255) NOT NULL,
    `sex` VARCHAR(20) NOT NULL,
    `dob` DATE NULL,
    `other` TEXT NULL,
    `photo` VARCHAR(255) NULL,
    `school_id` INT DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`school_id`),
    INDEX (`student_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Study Enrollments Table (តារាងការចុះឈ្មោះរៀន)
CREATE TABLE IF NOT EXISTS `tb_study` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_stu` INT NOT NULL,
    `id_code` INT NOT NULL,
    `id_time` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`id_stu`),
    INDEX (`id_code`),
    INDEX (`id_time`),
    INDEX (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Invoices & Payments Table (តារាងវិក្កយបត្រ និងការបង់ប្រាក់)
CREATE TABLE IF NOT EXISTS `tb_invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NULL,
    `student_name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` VARCHAR(50) DEFAULT 'Unpaid' COMMENT 'Paid, Unpaid, Pending',
    `study_time` VARCHAR(50) NULL,
    `school_id` INT DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`student_id`),
    INDEX (`student_name`),
    INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Expenses Table (តារាងការចំណាយ)
CREATE TABLE IF NOT EXISTS `tb_expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'General',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `expense_date` DATE NOT NULL,
    `note` TEXT NULL,
    `receipt_photo` VARCHAR(255) NULL,
    `school_id` INT DEFAULT 1,
    `created_by` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`expense_date`),
    INDEX (`category`),
    INDEX (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Certified Students Table (តារាងសញ្ញាបត្រ/វិញ្ញាបនបត្រ)
CREATE TABLE IF NOT EXISTS `tbl_certi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `study_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`study_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Attendance Table (តារាងវត្តមាន)
CREATE TABLE IF NOT EXISTS `tbl_att` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_stu` INT NOT NULL,
    `date` DATETIME NOT NULL,
    `status` VARCHAR(20) DEFAULT '0' COMMENT '0: Absent, 1: Present, 2: Permission',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`id_stu`),
    INDEX (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. SIEM Security Logs Table (តារាងកំណត់ហេតុសុវត្ថិភាព)
CREATE TABLE IF NOT EXISTS `tb_siem_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50),
    `action` VARCHAR(50),
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`username`),
    INDEX (`action`),
    INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Seed Data (ទិន្នន័យលំនាំដើម)
INSERT INTO `tb_schools` (`id`, `school_name`, `school_name_kh`, `logo`) 
VALUES 
(1, 'Meakea Computer', 'មាគ៌ាកុំព្យូទ័រ', 'meakea.jpg'),
(2, 'Branch 2', 'សាខាទី២', 'meakea.png')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Admin User (Password: Meakkea@0968689680)
INSERT INTO `tb_users` (`id`, `username`, `password`, `user_type`, `school_id`)
VALUES (1, 'adminmeakea', '$2y$10$tZ9210w4vX3oT1i9aJjQ3u.cW1vW39xHjZ3v4mR1rP7vT8u8e2qea', 1, 0)
ON DUPLICATE KEY UPDATE `user_type`=1;

-- Default Time Slots
INSERT INTO `tb_time` (`id`, `time`) VALUES
(1, '08:00 - 09:00 AM'),
(2, '09:00 - 10:00 AM'),
(3, '10:00 - 11:00 AM'),
(4, '02:00 - 03:00 PM'),
(5, '03:00 - 04:00 PM'),
(6, '05:00 - 06:00 PM')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Default Courses
INSERT INTO `tb_course` (`id`, `CourseID`, `Course`, `Note`) VALUES
(1, 'CS01', 'Computer Office (Word, Excel, PPT)', 'មូលដ្ឋានគ្រឹះកុំព្យូទ័ររដ្ឋបាល'),
(2, 'WD01', 'Web Design (HTML, CSS, JS)', 'ការរចនាគេហទំព័រ'),
(3, 'GD01', 'Graphic Design (Photoshop, Illustrator)', 'ការរចនាក្រាហ្វិក')
ON DUPLICATE KEY UPDATE `id`=`id`;
