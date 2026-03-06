-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2023 at 12:30 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ai_school`
--
CREATE DATABASE IF NOT EXISTS `ai_school` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ai_school`;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_types`
--

CREATE TABLE `exam_types` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `publish_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marks_grades`
--

CREATE TABLE `marks_grades` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `grade_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `percent_from` decimal(5,2) NOT NULL,
  `percent_upto` decimal(5,2) NOT NULL,
  `grade_point` decimal(4,2) DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'e.g., 2025-2026',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 for current session, 0 otherwise'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedule`
--

CREATE TABLE `exam_schedule` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `exam_type_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_marks` decimal(5,2) NOT NULL,
  `pass_marks` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_marks`
--

CREATE TABLE `exam_marks` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `exam_schedule_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `attendance_status` enum('present','absent') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `father_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `father_phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `father_cnic` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `father_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_cnic` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `roll_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `admission_no` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `admission_date` date NOT NULL,
  `dob` date DEFAULT NULL,
  `cnic` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile_no` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'SchoolNest'),
('site_logo', NULL);

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','branchadmin','teacher','student','parent') NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `type` enum('theory','practical','optional','mandatory') NOT NULL DEFAULT 'mandatory',
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;
CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`),
  KEY `branch_id` (`branch_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `incharge_class_id` int(11) DEFAULT NULL COMMENT 'If they are incharge of a whole class',
  `incharge_section_id` int(11) DEFAULT NULL COMMENT 'If they are incharge of a specific section',
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `incharge_class_id` (`incharge_class_id`),
  KEY `incharge_section_id` (`incharge_section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_ibfk_3` FOREIGN KEY (`incharge_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teachers_ibfk_4` FOREIGN KEY (`incharge_section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL;
 INTO `users` (`id`, `branch_id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `password_reset_token`, `password_reset_expires`, `last_login`, `created_at`, `updated_at`) VALUES
(1, NULL, 'superadmin', 'superadmin@school.com', '$2y$10$E9e.R1d2DR1I/O.e.aG/P.V.ZJ.p3.H/2.iY.z.z.z.z.z.z.z', 'Super Administrator', 'superadmin', 'active', NULL, NULL, NULL, '2023-10-26 10:30:00', '2023-10-26 10:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_log`
--

CREATE TABLE `visitor_log` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `person_to_meet` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `id_card_details` varchar(255) DEFAULT NULL,
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phone_log`
--

CREATE TABLE `phone_log` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `call_date` datetime NOT NULL,
  `description` text DEFAULT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `call_type` enum('incoming','outgoing') NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_queries`
--
CREATE TABLE `admission_queries` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `class_of_interest` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `query_date` date NOT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `status` enum('active','closed','enrolled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'e.g., Grade 1, Class X',
  `numeric_name` int(11) DEFAULT NULL COMMENT 'e.g., 1, 10 for sorting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `complaint_no` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `branch_id` int(11) NOT NULL,
  `complaint_source` enum('student','teacher','parent','public') COLLATE utf8mb4_general_ci NOT NULL,
  `source_person_id` int(11) DEFAULT NULL COMMENT 'user_id for teacher, parent_id for parent',
  `source_student_ids` text COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Comma-separated student IDs',
  `complaint_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `complaint_date` date NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `action_taken` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `complaint_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','in_progress','resolved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'e.g., A, B, Blue, Green',
  `capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--
ALTER TABLE `admission_queries` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `branches` ADD PRIMARY KEY (`id`);
ALTER TABLE `visitor_log` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `phone_log` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `classes` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`);
ALTER TABLE `complaints` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_complaint_no_branch` (`branch_id`,`complaint_no`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `exam_types` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_exam_session` (`branch_id`,`session_id`,`name`), ADD KEY `session_id` (`session_id`);
ALTER TABLE `exam_schedule` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_exam_schedule` (`session_id`,`exam_type_id`,`class_id`,`subject_id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `exam_type_id` (`exam_type_id`), ADD KEY `class_id` (`class_id`), ADD KEY `subject_id` (`subject_id`);
ALTER TABLE `exam_marks` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_student_mark` (`exam_schedule_id`,`student_id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `session_id` (`session_id`), ADD KEY `student_id` (`student_id`), ADD KEY `class_id` (`class_id`), ADD KEY `section_id` (`section_id`), ADD KEY `subject_id` (`subject_id`);
ALTER TABLE `marks_grades` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`);
ALTER TABLE `sections` ADD PRIMARY KEY (`id`), ADD KEY `class_id` (`class_id`), ADD KEY `branch_id` (`branch_id`);
ALTER TABLE `parents` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `user_id` (`user_id`), ADD UNIQUE KEY `father_cnic_branch` (`father_cnic`,`branch_id`), ADD KEY `branch_id` (`branch_id`);
ALTER TABLE `academic_sessions` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`);
ALTER TABLE `student_enrollments` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_student_session` (`session_id`,`student_id`), ADD KEY `student_id` (`student_id`), ADD KEY `class_id` (`class_id`), ADD KEY `section_id` (`section_id`);
ALTER TABLE `students` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `user_id` (`user_id`), ADD UNIQUE KEY `admission_no_branch` (`admission_no`,`branch_id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `parent_id` (`parent_id`);
ALTER TABLE `settings` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `setting_key` (`setting_key`);
ALTER TABLE `users` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`), ADD UNIQUE KEY `email` (`email`), ADD KEY `branch_id` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--
ALTER TABLE `academic_sessions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `student_enrollments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `parents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `students` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `admission_queries` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `visitor_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `phone_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `branches` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `exam_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `exam_schedule` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `exam_marks` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `marks_grades` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `classes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `complaints` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sections` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--
ALTER TABLE `admission_queries`
  ADD CONSTRAINT `admission_queries_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admission_queries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `visitor_log`
  ADD CONSTRAINT `visitor_log_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visitor_log_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `phone_log`
  ADD CONSTRAINT `phone_log_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phone_log_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `exam_types`
  ADD CONSTRAINT `exam_types_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_types_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE;
ALTER TABLE `exam_schedule`
  ADD CONSTRAINT `exam_schedule_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_schedule_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_schedule_ibfk_3` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_schedule_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_schedule_ibfk_5` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
ALTER TABLE `exam_marks`
  ADD CONSTRAINT `exam_marks_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_marks_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_marks_ibfk_3` FOREIGN KEY (`exam_schedule_id`) REFERENCES `exam_schedule` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_marks_ibfk_4` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_marks_ibfk_5` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_marks_ibfk_6` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_marks_ibfk_7` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
ALTER TABLE `marks_grades` ADD CONSTRAINT `marks_grades_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parents_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;
ALTER TABLE `academic_sessions` ADD CONSTRAINT `academic_sessions_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `student_enrollments_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollments_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollments_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;
ALTER TABLE `students` ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL;
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sections_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;
ALTER TABLE `users` ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
CREATE TABLE `class_routine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `class_routine`
  ADD CONSTRAINT `class_routine_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_5` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
