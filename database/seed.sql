-- =============================================================================
-- Web-Based Academic Transcript Generating System with QR Verification
-- Kano State Polytechnic - Department of Computer Science
-- Comprehensive Database Seed File
-- =============================================================================

USE `onlinecourse`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. ROLES
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `roles`;
INSERT INTO `roles` (`id`, `role_name`, `description`) VALUES
(1, 'Super Admin', 'Full system access, user management, and security controls'),
(2, 'Department Admin', 'Academic session, course, student, and departmental record administration'),
(3, 'Result Officer', 'Entering, uploading, modifying, and submitting semester examination results'),
(4, 'Transcript Officer', 'Reviewing student transcripts, generating official PDFs, and managing QR records');

-- -----------------------------------------------------------------------------
-- 2. PERMISSIONS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `permissions`;
INSERT INTO `permissions` (`id`, `permission_key`, `description`) VALUES
(1, 'students.view', 'View student listings and academic profiles'),
(2, 'students.create', 'Register new students'),
(3, 'students.edit', 'Edit student biodata and matriculation details'),
(4, 'students.delete', 'Deactivate or delete student records'),
(5, 'courses.view', 'View course listings and syllabi'),
(6, 'courses.create', 'Add new courses'),
(7, 'courses.edit', 'Update course details and credit units'),
(8, 'courses.delete', 'Remove courses from catalog'),
(9, 'sessions.view', 'View academic session schedules'),
(10, 'sessions.manage', 'Create, update, and activate academic sessions'),
(11, 'semesters.view', 'View semester listings'),
(12, 'semesters.manage', 'Create, update, and activate academic semesters'),
(13, 'results.view', 'View student grades and result sheets'),
(14, 'results.enter', 'Enter and upload exam and CA scores'),
(15, 'results.approve', 'Approve and publish departmental examination results'),
(16, 'gpa.calculate', 'Trigger batch GPA and CGPA mathematical recalculations'),
(17, 'transcripts.view', 'View generated and historical academic transcripts'),
(18, 'transcripts.generate', 'Generate official academic transcripts with QR tokens'),
(19, 'transcripts.revoke', 'Revoke invalid or tampered academic transcripts'),
(20, 'verification.view', 'View verification logs and transcript audit records'),
(21, 'verification.verify', 'Verify QR tokens against database registry'),
(22, 'settings.manage', 'Configure institution settings and grading system scales'),
(23, 'users.manage', 'Manage administrative user accounts and permissions');

-- -----------------------------------------------------------------------------
-- 3. ROLE PERMISSIONS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `role_permissions`;
-- Super Admin: All permissions (1 to 23)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Department Admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE id IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,20,21,22);

-- Result Officer
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE id IN (1, 5, 13, 14, 15, 16);

-- Transcript Officer
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE id IN (1, 5, 13, 17, 18, 19, 20, 21);

-- -----------------------------------------------------------------------------
-- 4. USERS (Password: admin123)
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `users`;
INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `role_id`, `status`) VALUES
(1, 'System Administrator', 'admin', 'admin@kanopoly.edu.ng', '$2y$12$Iyzna6OthdqsPj51YD7.detPvP5grV4/14HxJNHN2YJrEP5lPMOs.', 1, 'active'),
(2, 'Dr. Aminu Abdullahi', 'dept_admin', 'cs.hod@kanopoly.edu.ng', '$2y$12$Iyzna6OthdqsPj51YD7.detPvP5grV4/14HxJNHN2YJrEP5lPMOs.', 2, 'active'),
(3, 'Malam Kabir Sanusi', 'result_officer', 'results.cs@kanopoly.edu.ng', '$2y$12$Iyzna6OthdqsPj51YD7.detPvP5grV4/14HxJNHN2YJrEP5lPMOs.', 3, 'active'),
(4, 'Hajiya Zainab Umar', 'transcript_officer', 'transcripts.cs@kanopoly.edu.ng', '$2y$12$Iyzna6OthdqsPj51YD7.detPvP5grV4/14HxJNHN2YJrEP5lPMOs.', 4, 'active');

-- -----------------------------------------------------------------------------
-- 5. DEPARTMENTS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `departments`;
INSERT INTO `departments` (`id`, `department_name`, `department_code`, `faculty_name`, `hod_name`, `hod_title`, `status`) VALUES
(1, 'Computer Science', 'CS', 'School of Technology', 'Dr. Aminu Abdullahi', 'Head of Department', 'active'),
(2, 'Electrical & Electronics Engineering', 'EEE', 'School of Technology', 'Engr. Bello Mohammed', 'Head of Department', 'active'),
(3, 'Mathematics and Statistics', 'MAS', 'School of Technology', 'Mal. Sani Garba', 'Head of Department', 'active');

-- -----------------------------------------------------------------------------
-- 6. ACADEMIC SESSIONS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `academic_sessions`;
INSERT INTO `academic_sessions` (`id`, `session_name`, `start_date`, `end_date`, `status`, `is_active`) VALUES
(1, '2022/2023', '2022-10-10', '2023-08-15', 'Closed', 0),
(2, '2023/2024', '2023-10-09', '2024-08-20', 'Closed', 0),
(3, '2024/2025', '2024-10-14', '2025-08-30', 'Active', 1),
(4, '2025/2026', '2025-10-13', '2026-08-28', 'Upcoming', 0);

-- -----------------------------------------------------------------------------
-- 7. SEMESTERS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `semesters`;
INSERT INTO `semesters` (`id`, `academic_session_id`, `semester_name`, `semester_code`, `start_date`, `end_date`, `status`, `is_active`) VALUES
(1, 1, 'First Semester', '2022/2023-1', '2022-10-10', '2023-03-01', 'Closed', 0),
(2, 1, 'Second Semester', '2022/2023-2', '2023-03-15', '2023-08-15', 'Closed', 0),
(3, 2, 'First Semester', '2023/2024-1', '2023-10-09', '2024-03-05', 'Closed', 0),
(4, 2, 'Second Semester', '2023/2024-2', '2024-03-20', '2024-08-20', 'Closed', 0),
(5, 3, 'First Semester', '2024/2025-1', '2024-10-14', '2025-03-10', 'Active', 1),
(6, 3, 'Second Semester', '2024/2025-2', '2025-03-24', '2025-08-30', 'Upcoming', 0);

-- -----------------------------------------------------------------------------
-- 8. GRADING SYSTEM (NBTE Standard 4.00 Scale)
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `grading_system`;
INSERT INTO `grading_system` (`id`, `letter_grade`, `min_score`, `max_score`, `grade_point`, `grade_description`, `is_active`, `display_order`) VALUES
(1, 'A',  75.00, 100.00, 4.00, 'Distinction', 1, 1),
(2, 'AB', 70.00,  74.99, 3.50, 'Very Good', 1, 2),
(3, 'B',  65.00,  69.99, 3.25, 'Good', 1, 3),
(4, 'BC', 60.00,  64.99, 3.00, 'Upper Credit', 1, 4),
(5, 'C',  55.00,  59.99, 2.75, 'Lower Credit', 1, 5),
(6, 'CD', 50.00,  54.99, 2.50, 'Fair', 1, 6),
(7, 'D',  45.00,  49.99, 2.25, 'Pass', 1, 7),
(8, 'E',  40.00,  44.99, 2.00, 'Bare Pass', 1, 8),
(9, 'F',   0.00,  39.99, 0.00, 'Fail', 1, 9);

-- -----------------------------------------------------------------------------
-- 9. COURSES
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `courses`;
INSERT INTO `courses` (`id`, `department_id`, `course_code`, `course_title`, `credit_unit`, `academic_level`, `semester_id`, `semester_name`, `course_description`, `status`) VALUES
-- ND I First Semester
(1, 1, 'COM 111', 'Introduction to Computing', 3, 'ND I', 5, 'First Semester', 'Fundamental computer concepts, hardware, software, and binary systems', 'active'),
(2, 1, 'COM 112', 'Digital Electronics', 3, 'ND I', 5, 'First Semester', 'Logic gates, Boolean algebra, flip-flops, and counters', 'active'),
(3, 1, 'COM 113', 'Introduction to Programming', 3, 'ND I', 5, 'First Semester', 'Problem solving algorithms, flowcharts, and C syntax basics', 'active'),
(4, 1, 'GNS 101', 'Use of English I', 2, 'ND I', 5, 'First Semester', 'Grammar, reading comprehension, and study skills', 'active'),
(5, 1, 'MTH 111', 'Logic and Linear Algebra', 2, 'ND I', 5, 'First Semester', 'Mathematical logic, set theory, vectors, and matrices', 'active'),

-- ND I Second Semester
(6, 1, 'COM 121', 'Programming with C/C++', 3, 'ND I', 6, 'Second Semester', 'Structured programming, pointers, structs, and file handling in C/C++', 'active'),
(7, 1, 'COM 122', 'Internet & Web Technology', 3, 'ND I', 6, 'Second Semester', 'HTML5, CSS, client-server web models, and internet protocols', 'active'),
(8, 1, 'COM 123', 'Computer Architecture', 3, 'ND I', 6, 'Second Semester', 'Von Neumann architecture, instruction cycle, memory hierarchy', 'active'),
(9, 1, 'COM 124', 'Data Structures & Algorithms', 3, 'ND I', 6, 'Second Semester', 'Arrays, linked lists, stacks, queues, trees, and searching algorithms', 'active'),
(10, 1, 'GNS 102', 'Communication Skills', 2, 'ND I', 6, 'Second Semester', 'Report writing, presentation skills, and professional correspondence', 'active'),

-- ND II First Semester
(11, 1, 'COM 211', 'Object Oriented Programming (Java)', 3, 'ND II', 5, 'First Semester', 'Classes, inheritance, polymorphism, and Java GUI design', 'active'),
(12, 1, 'COM 212', 'Database Design I', 3, 'ND II', 5, 'First Semester', 'Relational models, ER diagrams, normalization, and SQL DDL/DML', 'active'),
(13, 1, 'COM 213', 'Operating Systems', 3, 'ND II', 5, 'First Semester', 'Process management, concurrency, memory paging, and file systems', 'active'),
(14, 1, 'COM 214', 'Systems Analysis and Design', 3, 'ND II', 5, 'First Semester', 'SDLC, requirement gathering, UML modeling, and feasibility analysis', 'active'),

-- ND II Second Semester
(15, 1, 'COM 221', 'Mobile Application Development', 3, 'ND II', 6, 'Second Semester', 'Mobile architecture, responsive UI, state, and mobile data storage', 'active'),
(16, 1, 'COM 222', 'Database Design II', 3, 'ND II', 6, 'Second Semester', 'Transactions, indexing, stored procedures, triggers, and NoSQL', 'active'),
(17, 1, 'COM 223', 'Computer Networking', 3, 'ND II', 6, 'Second Semester', 'OSI model, TCP/IP, subnetting, routing, and network security', 'active'),
(18, 1, 'COM 224', 'Project & Seminar', 4, 'ND II', 6, 'Second Semester', 'Supervised capstone software design, development, and defense', 'active');

-- -----------------------------------------------------------------------------
-- 10. STUDENTS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `students`;
INSERT INTO `students` (`id`, `matric_number`, `first_name`, `middle_name`, `last_name`, `gender`, `date_of_birth`, `email`, `phone`, `department_id`, `programme`, `admission_session_id`, `current_level`, `status`, `created_at`) VALUES
(1, 'KSP/CS/2023/001', 'Ibrahim', 'Musa', 'Garba', 'Male', '2002-05-14', 'ibrahim.garba@student.kanopoly.edu.ng', '+2348031112233', 1, 'National Diploma in Computer Science', 2, 'ND II', 'Active', '2023-10-15 10:00:00'),
(2, 'KSP/CS/2023/002', 'Fatima', 'Bello', 'Umar', 'Female', '2003-08-22', 'fatima.umar@student.kanopoly.edu.ng', '+2348022223344', 1, 'National Diploma in Computer Science', 2, 'ND II', 'Active', '2023-10-15 10:05:00'),
(3, 'KSP/CS/2023/003', 'Abubakar', 'Sadiq', 'Sani', 'Male', '2001-11-03', 'abubakar.sani@student.kanopoly.edu.ng', '+2348033334455', 1, 'National Diploma in Computer Science', 2, 'ND II', 'Active', '2023-10-16 09:20:00'),
(4, 'KSP/CS/2023/004', 'Aisha', 'Kabir', 'Mohammed', 'Female', '2003-01-19', 'aisha.mohammed@student.kanopoly.edu.ng', '+2348044445566', 1, 'National Diploma in Computer Science', 2, 'ND II', 'Active', '2023-10-16 09:45:00'),
(5, 'KSP/CS/2024/005', 'Zainab', 'Haruna', 'Aliyu', 'Female', '2004-04-12', 'zainab.aliyu@student.kanopoly.edu.ng', '+2348055556677', 1, 'National Diploma in Computer Science', 3, 'ND I', 'Active', '2024-10-20 11:15:00'),
(6, 'KSP/CS/2024/006', 'Usman', 'Danladi', 'Yahaya', 'Male', '2003-09-30', 'usman.yahaya@student.kanopoly.edu.ng', '+2348066667788', 1, 'National Diploma in Computer Science', 3, 'ND I', 'Active', '2024-10-21 14:00:00'),
(7, 'KSP/CS/2024/007', 'Maryam', 'Abdullahi', 'Tukur', 'Female', '2004-12-05', 'maryam.tukur@student.kanopoly.edu.ng', '+2348077778899', 1, 'National Diploma in Computer Science', 3, 'ND I', 'Active', '2024-10-22 08:30:00'),
(8, 'KSP/CS/2022/008', 'Mustapha', 'Bashir', 'Jibril', 'Male', '2001-03-17', 'mustapha.jibril@student.kanopoly.edu.ng', '+2348088889900', 1, 'National Diploma in Computer Science', 1, 'ND II', 'Graduated', '2022-10-18 12:00:00');

-- -----------------------------------------------------------------------------
-- 11. STUDENT COURSE REGISTRATION
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `student_courses`;
INSERT INTO `student_courses` (`student_id`, `course_id`, `academic_session_id`, `semester_id`, `semester_name`, `registration_status`) VALUES
-- Ibrahim Musa Garba (Student 1)
(1, 1, 2, 3, 'First Semester', 'approved'),
(1, 2, 2, 3, 'First Semester', 'approved'),
(1, 3, 2, 3, 'First Semester', 'approved'),
(1, 4, 2, 3, 'First Semester', 'approved'),
(1, 5, 2, 3, 'First Semester', 'approved'),
(1, 6, 2, 4, 'Second Semester', 'approved'),
(1, 7, 2, 4, 'Second Semester', 'approved'),
(1, 8, 2, 4, 'Second Semester', 'approved'),
(1, 9, 2, 4, 'Second Semester', 'approved'),
(1, 10, 2, 4, 'Second Semester', 'approved'),
(1, 11, 3, 5, 'First Semester', 'approved'),
(1, 12, 3, 5, 'First Semester', 'approved'),
(1, 13, 3, 5, 'First Semester', 'approved'),
(1, 14, 3, 5, 'First Semester', 'approved'),

-- Fatima Bello Umar (Student 2)
(2, 1, 2, 3, 'First Semester', 'approved'),
(2, 2, 2, 3, 'First Semester', 'approved'),
(2, 3, 2, 3, 'First Semester', 'approved'),
(2, 4, 2, 3, 'First Semester', 'approved'),
(2, 5, 2, 3, 'First Semester', 'approved'),
(2, 6, 2, 4, 'Second Semester', 'approved'),
(2, 7, 2, 4, 'Second Semester', 'approved'),
(2, 8, 2, 4, 'Second Semester', 'approved'),
(2, 9, 2, 4, 'Second Semester', 'approved'),
(2, 10, 2, 4, 'Second Semester', 'approved'),
(2, 11, 3, 5, 'First Semester', 'approved'),
(2, 12, 3, 5, 'First Semester', 'approved'),
(2, 13, 3, 5, 'First Semester', 'approved'),
(2, 14, 3, 5, 'First Semester', 'approved'),

-- Zainab Haruna Aliyu (Student 5 - ND I)
(5, 1, 3, 5, 'First Semester', 'approved'),
(5, 2, 3, 5, 'First Semester', 'approved'),
(5, 3, 3, 5, 'First Semester', 'approved'),
(5, 4, 3, 5, 'First Semester', 'approved'),
(5, 5, 3, 5, 'First Semester', 'approved');

-- -----------------------------------------------------------------------------
-- 12. RESULTS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `results`;
INSERT INTO `results` (`student_id`, `course_id`, `academic_session_id`, `semester_id`, `semester_name`, `academic_level`, `ca_score`, `examination_score`, `total_score`, `letter_grade`, `grade_point`, `credit_unit`, `quality_points`, `approval_status`, `entered_by`, `approved_by`, `approved_at`) VALUES
-- Student 1 (Ibrahim Musa Garba) ND I Sem 1
(1, 1, 2, 3, 'First Semester', 'ND I', 32.00, 48.00, 80.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(1, 2, 2, 3, 'First Semester', 'ND I', 28.00, 44.00, 72.00, 'AB', 3.50, 3, 10.50, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(1, 3, 2, 3, 'First Semester', 'ND I', 34.00, 51.00, 85.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(1, 4, 2, 3, 'First Semester', 'ND I', 25.00, 42.00, 67.00, 'B',  3.25, 2,  6.50, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(1, 5, 2, 3, 'First Semester', 'ND I', 26.00, 45.00, 71.00, 'AB', 3.50, 2,  7.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),

-- Student 1 ND I Sem 2
(1, 6, 2, 4, 'Second Semester', 'ND I', 35.00, 48.00, 83.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(1, 7, 2, 4, 'Second Semester', 'ND I', 30.00, 47.00, 77.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(1, 8, 2, 4, 'Second Semester', 'ND I', 27.00, 40.00, 67.00, 'B',  3.25, 3,  9.75, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(1, 9, 2, 4, 'Second Semester', 'ND I', 33.00, 46.00, 79.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(1, 10, 2, 4, 'Second Semester', 'ND I', 28.00, 44.00, 72.00, 'AB', 3.50, 2,  7.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),

-- Student 1 ND II Sem 1
(1, 11, 3, 5, 'First Semester', 'ND II', 36.00, 50.00, 86.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2025-03-15 16:00:00'),
(1, 12, 3, 5, 'First Semester', 'ND II', 34.00, 49.00, 83.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2025-03-15 16:00:00'),
(1, 13, 3, 5, 'First Semester', 'ND II', 31.00, 45.00, 76.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2025-03-15 16:00:00'),
(1, 14, 3, 5, 'First Semester', 'ND II', 29.00, 43.00, 72.00, 'AB', 3.50, 3, 10.50, 'Approved', 3, 2, '2025-03-15 16:00:00'),

-- Student 2 (Fatima Bello Umar) ND I Sem 1
(2, 1, 2, 3, 'First Semester', 'ND I', 35.00, 52.00, 87.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(2, 2, 2, 3, 'First Semester', 'ND I', 32.00, 48.00, 80.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(2, 3, 2, 3, 'First Semester', 'ND I', 33.00, 50.00, 83.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(2, 4, 2, 3, 'First Semester', 'ND I', 27.00, 45.00, 72.00, 'AB', 3.50, 2,  7.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),
(2, 5, 2, 3, 'First Semester', 'ND I', 28.00, 47.00, 75.00, 'A',  4.00, 2,  8.00, 'Approved', 3, 2, '2024-03-12 11:00:00'),

-- Student 2 ND I Sem 2
(2, 6, 2, 4, 'Second Semester', 'ND I', 34.00, 49.00, 83.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(2, 7, 2, 4, 'Second Semester', 'ND I', 36.00, 50.00, 86.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(2, 8, 2, 4, 'Second Semester', 'ND I', 30.00, 45.00, 75.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(2, 9, 2, 4, 'Second Semester', 'ND I', 35.00, 51.00, 86.00, 'A',  4.00, 3, 12.00, 'Approved', 3, 2, '2024-08-25 14:30:00'),
(2, 10, 2, 4, 'Second Semester', 'ND I', 29.00, 45.00, 74.00, 'AB', 3.50, 2,  7.00, 'Approved', 3, 2, '2024-08-25 14:30:00');

-- -----------------------------------------------------------------------------
-- 13. TRANSCRIPTS & VERIFICATIONS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `transcripts`;
TRUNCATE TABLE `transcript_verifications`;
TRUNCATE TABLE `transcript_details`;

INSERT INTO `transcripts` (`id`, `student_id`, `transcript_reference`, `academic_start_session`, `academic_end_session`, `issue_date`, `total_credit_units`, `total_quality_points`, `cgpa`, `classification`, `remarks`, `generated_by`, `transcript_status`, `pdf_file_path`) VALUES
(1, 1, 'KSP/CST/TR/2025/0001', '2023/2024', '2024/2025', '2025-03-20', 39, 147.25, 3.78, 'Distinction', 'Official Academic Transcript issued for academic/employment verification.', 4, 'Valid', 'storage/transcripts/KSP_CST_TR_2025_0001.pdf');

-- Verification token hash (SHA-256 for: "ksp-valid-token-demo-2025")
INSERT INTO `transcript_verifications` (`id`, `transcript_id`, `verification_token_hash`, `verification_token_preview`, `verification_status`, `issued_at`) VALUES
(1, 1, '4b6cf3bde2eed13dfda0372ed5244d6aa13c76c92215388782963c632bb4f6e4', '4b6cf3bde2eed13d', 'Valid', '2025-03-20 10:15:00');

-- Transcript Details
INSERT INTO `transcript_details` (`id`, `transcript_id`, `course_code`, `course_title`, `credit_unit`, `examination_score`, `letter_grade`, `grade_point`, `quality_points`, `academic_session`, `semester`, `academic_level`, `semester_gpa`, `cumulative_cgpa`) VALUES
(1, 1, 'COM 111', 'Introduction to Computing', 3, 80.00, 'A', 4.00, 12.00, '2023/2024', 'First Semester', 'ND I', 3.69, 3.69),
(2, 1, 'COM 112', 'Digital Electronics', 3, 72.00, 'AB', 3.50, 10.50, '2023/2024', 'First Semester', 'ND I', 3.69, 3.69),
(3, 1, 'COM 113', 'Introduction to Programming', 3, 85.00, 'A', 4.00, 12.00, '2023/2024', 'First Semester', 'ND I', 3.69, 3.69),
(4, 1, 'GNS 101', 'Use of English I', 2, 67.00, 'B', 3.25, 6.50, '2023/2024', 'First Semester', 'ND I', 3.69, 3.69),
(5, 1, 'MTH 111', 'Logic and Linear Algebra', 2, 71.00, 'AB', 3.50, 7.00, '2023/2024', 'First Semester', 'ND I', 3.69, 3.69),
(6, 1, 'COM 121', 'Programming with C/C++', 3, 83.00, 'A', 4.00, 12.00, '2023/2024', 'Second Semester', 'ND I', 3.77, 3.73),
(7, 1, 'COM 122', 'Internet & Web Technology', 3, 77.00, 'A', 4.00, 12.00, '2023/2024', 'Second Semester', 'ND I', 3.77, 3.73),
(8, 1, 'COM 123', 'Computer Architecture', 3, 67.00, 'B', 3.25, 9.75, '2023/2024', 'Second Semester', 'ND I', 3.77, 3.73),
(9, 1, 'COM 124', 'Data Structures & Algorithms', 3, 79.00, 'A', 4.00, 12.00, '2023/2024', 'Second Semester', 'ND I', 3.77, 3.73),
(10, 1, 'GNS 102', 'Communication Skills', 2, 72.00, 'AB', 3.50, 7.00, '2023/2024', 'Second Semester', 'ND I', 3.77, 3.73),
(11, 1, 'COM 211', 'Object Oriented Programming (Java)', 3, 86.00, 'A', 4.00, 12.00, '2024/2025', 'First Semester', 'ND II', 3.88, 3.78),
(12, 1, 'COM 212', 'Database Design I', 3, 83.00, 'A', 4.00, 12.00, '2024/2025', 'First Semester', 'ND II', 3.88, 3.78),
(13, 1, 'COM 213', 'Operating Systems', 3, 76.00, 'A', 4.00, 12.00, '2024/2025', 'First Semester', 'ND II', 3.88, 3.78),
(14, 1, 'COM 214', 'Systems Analysis and Design', 3, 72.00, 'AB', 3.50, 10.50, '2024/2025', 'First Semester', 'ND II', 3.88, 3.78);

-- -----------------------------------------------------------------------------
-- 14. SYSTEM SETTINGS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `system_settings`;
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('institution_name', 'Kano State Polytechnic', 'general', 'Official institution legal name'),
('institution_address', 'P.M.B. 3401, BUK Road, Kano, Kano State, Nigeria', 'general', 'Official physical and postal address'),
('institution_logo', 'assets/images/logo-placeholder.svg', 'general', 'Institution official emblem image path'),
('institution_seal', 'assets/images/seal.svg', 'general', 'Registrar official stamp/seal image path'),
('faculty_name', 'School of Technology', 'academic', 'School or faculty name'),
('department_name', 'Department of Computer Science', 'academic', 'Academic department name'),
('department_code', 'CS', 'academic', 'Official departmental course code prefix'),
('hod_name', 'Dr. Aminu Abdullahi', 'academic', 'Full name of current Head of Department'),
('registrar_name', 'Dr. Mustapha B. Farouk', 'academic', 'Full name of Academic Registrar'),
('grading_scale_type', 'NBTE', 'grading', 'Grading standard (NBTE 4.00 Scale)'),
('min_pass_mark', '40', 'grading', 'Minimum percentage required to pass a course'),
('verification_base_url', 'http://localhost:8000/verify.html?token=', 'verification', 'Base URL for QR verification landing page'),
('transcript_reference_prefix', 'KSP/CST/TR/', 'transcript', 'Prefix used in generating transcript reference numbers');

-- -----------------------------------------------------------------------------
-- 15. AUDIT LOGS
-- -----------------------------------------------------------------------------
TRUNCATE TABLE `audit_logs`;
INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `affected_record`, `ip_address`) VALUES
(1, 'DATABASE_SEED', 'Initial departmental database seeding executed successfully', 'SYSTEM', '127.0.0.1'),
(4, 'TRANSCRIPT_GENERATED', 'Transcript reference KSP/CST/TR/2025/0001 generated for Ibrahim Musa Garba', 'TRANSCRIPT:1', '127.0.0.1');

SET FOREIGN_KEY_CHECKS = 1;
