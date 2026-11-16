-- =============================================================================
-- Web-Based Academic Transcript Generating System with QR Verification
-- Kano State Polytechnic - Department of Computer Science
-- Normalized Relational Database Schema (MySQL 8.0+ / MariaDB 10.5+)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `onlinecourse` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `onlinecourse`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. ROLES & PERMISSIONS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `permission_key` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. USERS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(255) NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. DEPARTMENTS & INSTITUTION
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `departments`;

CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_name` VARCHAR(255) NOT NULL,
    `department_code` VARCHAR(50) NOT NULL UNIQUE,
    `faculty_name` VARCHAR(255) NOT NULL DEFAULT 'School of Technology',
    `hod_name` VARCHAR(255) NOT NULL DEFAULT 'Dr. Aminu Abdullahi',
    `hod_title` VARCHAR(255) NOT NULL DEFAULT 'Head of Department',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. ACADEMIC SESSIONS & SEMESTERS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `semesters`;
DROP TABLE IF EXISTS `academic_sessions`;

CREATE TABLE `academic_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_name` VARCHAR(50) NOT NULL UNIQUE,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `status` ENUM('Upcoming', 'Active', 'Closed') DEFAULT 'Upcoming',
    `is_active` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `semesters` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `academic_session_id` INT NOT NULL,
    `semester_name` VARCHAR(100) NOT NULL,
    `semester_code` VARCHAR(20) NOT NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `status` ENUM('Upcoming', 'Active', 'Closed') DEFAULT 'Upcoming',
    `is_active` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sem_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. STUDENTS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `students`;

CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `matric_number` VARCHAR(100) NOT NULL UNIQUE,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `gender` ENUM('Male', 'Female') NOT NULL,
    `date_of_birth` DATE NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NULL,
    `department_id` INT NOT NULL,
    `programme` VARCHAR(255) NOT NULL,
    `admission_session_id` INT NOT NULL,
    `current_level` ENUM('ND I', 'ND II', 'HND I', 'HND II') NOT NULL DEFAULT 'ND I',
    `photograph` VARCHAR(255) NULL,
    `status` ENUM('Active', 'Graduated', 'Suspended', 'Withdrawn') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_student_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
    CONSTRAINT `fk_student_session` FOREIGN KEY (`admission_session_id`) REFERENCES `academic_sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. COURSES
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `courses`;

CREATE TABLE `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_code` VARCHAR(50) NOT NULL UNIQUE,
    `course_title` VARCHAR(255) NOT NULL,
    `course_description` TEXT NULL,
    `credit_unit` INT NOT NULL DEFAULT 3,
    `department_id` INT NOT NULL,
    `academic_level` ENUM('ND I', 'ND II', 'HND I', 'HND II') NOT NULL,
    `semester_id` INT NULL,
    `semester_name` VARCHAR(50) NOT NULL DEFAULT 'First Semester',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_course_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. STUDENT COURSE REGISTRATION
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `student_courses`;

CREATE TABLE `student_courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `academic_session_id` INT NOT NULL,
    `semester_id` INT NULL,
    `semester_name` VARCHAR(50) NOT NULL,
    `registration_status` ENUM('registered', 'approved', 'dropped') DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_student_reg` (`student_id`, `course_id`, `academic_session_id`, `semester_name`),
    CONSTRAINT `fk_sc_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sc_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
    CONSTRAINT `fk_sc_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. CONFIGURABLE GRADING SYSTEM
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `grading_system`;

CREATE TABLE `grading_system` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `letter_grade` VARCHAR(10) NOT NULL,
    `min_score` DECIMAL(5,2) NOT NULL,
    `max_score` DECIMAL(5,2) NOT NULL,
    `grade_point` DECIMAL(4,2) NOT NULL,
    `grade_description` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. RESULTS MANAGEMENT
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `results`;

CREATE TABLE `results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `academic_session_id` INT NOT NULL,
    `semester_id` INT NULL,
    `semester_name` VARCHAR(50) NOT NULL,
    `academic_level` VARCHAR(20) NOT NULL,
    `ca_score` DECIMAL(5,2) DEFAULT 0.00,
    `examination_score` DECIMAL(5,2) NOT NULL,
    `total_score` DECIMAL(5,2) NOT NULL,
    `letter_grade` VARCHAR(10) NOT NULL,
    `grade_point` DECIMAL(4,2) NOT NULL,
    `credit_unit` INT NOT NULL,
    `quality_points` DECIMAL(6,2) NOT NULL,
    `approval_status` ENUM('Draft', 'Approved') DEFAULT 'Draft',
    `entered_by` INT NULL,
    `approved_by` INT NULL,
    `approved_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_student_course_result` (`student_id`, `course_id`, `academic_session_id`, `semester_name`),
    CONSTRAINT `fk_res_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_res_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
    CONSTRAINT `fk_res_session` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`),
    CONSTRAINT `fk_res_entered_by` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_res_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. TRANSCRIPTS & IMMUTABLE DETAILS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `transcript_details`;
DROP TABLE IF EXISTS `transcript_verifications`;
DROP TABLE IF EXISTS `transcripts`;

CREATE TABLE `transcripts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `transcript_reference` VARCHAR(100) NOT NULL UNIQUE,
    `academic_start_session` VARCHAR(50) NOT NULL,
    `academic_end_session` VARCHAR(50) NOT NULL,
    `issue_date` DATE NOT NULL,
    `total_credit_units` INT NOT NULL,
    `total_quality_points` DECIMAL(8,2) NOT NULL,
    `cgpa` DECIMAL(4,2) NOT NULL,
    `classification` VARCHAR(100) NOT NULL,
    `remarks` TEXT NULL,
    `generated_by` INT NULL,
    `transcript_status` ENUM('Valid', 'Revoked') DEFAULT 'Valid',
    `revocation_reason` TEXT NULL,
    `revoked_at` DATETIME NULL,
    `pdf_file_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tr_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transcript_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transcript_id` INT NOT NULL,
    `course_code` VARCHAR(50) NOT NULL,
    `course_title` VARCHAR(255) NOT NULL,
    `credit_unit` INT NOT NULL,
    `examination_score` DECIMAL(5,2) NOT NULL,
    `letter_grade` VARCHAR(10) NOT NULL,
    `grade_point` DECIMAL(4,2) NOT NULL,
    `quality_points` DECIMAL(6,2) NOT NULL,
    `academic_session` VARCHAR(50) NOT NULL,
    `semester` VARCHAR(50) NOT NULL,
    `academic_level` VARCHAR(50) NOT NULL,
    `semester_gpa` DECIMAL(4,2) NOT NULL,
    `cumulative_cgpa` DECIMAL(4,2) NOT NULL,
    CONSTRAINT `fk_td_transcript` FOREIGN KEY (`transcript_id`) REFERENCES `transcripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. QR VERIFICATION & VERIFICATION TOKENS
-- -----------------------------------------------------------------------------
CREATE TABLE `transcript_verifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transcript_id` INT NOT NULL,
    `verification_token_hash` VARCHAR(255) NOT NULL UNIQUE,
    `verification_token_preview` VARCHAR(32) NOT NULL,
    `verification_status` ENUM('Valid', 'Revoked') DEFAULT 'Valid',
    `issued_at` DATETIME NOT NULL,
    `revoked_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tv_transcript` FOREIGN KEY (`transcript_id`) REFERENCES `transcripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `verification_logs`;

CREATE TABLE `verification_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reference_number` VARCHAR(100) NOT NULL,
    `token_hash` VARCHAR(255) NULL,
    `status` ENUM('VERIFIED', 'REVOKED', 'NOT_FOUND') NOT NULL,
    `ip_address` VARCHAR(100) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. AUDIT LOGS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `affected_record` VARCHAR(255) NULL,
    `ip_address` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. SYSTEM & INSTITUTION SETTINGS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `system_settings`;

CREATE TABLE `system_settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. LOGIN RATE LIMITING
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;

CREATE TABLE `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(100) NOT NULL,
    `identifier` VARCHAR(255) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
