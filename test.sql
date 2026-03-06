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

ALTER TABLE `admission_queries` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `branches` ADD PRIMARY KEY (`id`);
ALTER TABLE `visitor_log` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `phone_log` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `classes` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`);
ALTER TABLE `complaints` ADD PRIMARY KEY (`id`), ADD KEY `branch_id` (`branch_id`), ADD KEY `created_by` (`created_by`);
ALTER TABLE `exam_types` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_exam_session` (`branch_id`,`session_id`,`name`), ADD KEY `session_id` (`session_id`);
ALTER TABLE `parents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `students` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `admission_queries` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `visitor_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `phone_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `branches` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `exam_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `exam_schedule` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
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
