-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql309.infinityfree.com
-- Generation Time: Feb 24, 2026 at 02:05 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_39968952_schoolnest`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'e.g., 2025-2026',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 for current session, 0 otherwise'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_sessions`
--

INSERT INTO `academic_sessions` (`id`, `branch_id`, `name`, `start_date`, `end_date`, `is_current`) VALUES
(1, 1, '2025-2026', '2025-03-01', '2026-03-01', 0),
(2, 1, '2026-2027', '2026-03-01', '2027-03-01', 1),
(3, 2, '2025-2026', '2025-03-01', '2026-03-01', 1),
(4, 2, '2026-2027', '2026-03-01', '2027-03-01', 0),
(6, 3, '22026-2027', '2026-02-20', '2027-02-18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username_attempt` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `username_attempt`, `ip_address`, `user_agent`, `action`, `details`, `timestamp`) VALUES
(1, 1, 'superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:18:01'),
(2, 2, 'Sarwai', '192.168.154.11', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:28:15'),
(3, 15, 'ikkaptan20@gmail.com', '192.168.154.11', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-16 21:32:04'),
(4, 15, 'ikkaptan20@gmail.com', '192.168.154.11', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:32:47'),
(5, 2, 'sarwai', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:34:00'),
(6, 2, 'sarwai', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-16 21:36:30'),
(7, 1, 'superadmin', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-16 21:36:40'),
(8, 1, 'superadmin', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:36:49'),
(9, 2, 'sarwai', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:37:13'),
(10, 2, 'sarwai', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:38:29'),
(11, 15, 'ikkaptan20@gmail.com', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-16 21:39:57'),
(12, 15, 'ikkaptan20@gmail.com', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:40:49'),
(13, 1, 'superadmin', '192.168.154.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-16 21:48:14'),
(14, 1, 'superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 10:33:55'),
(15, 1, 'superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 10:37:40'),
(16, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-20 10:39:50'),
(17, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 10:41:12'),
(18, 2, 'sarwai', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 10:48:42'),
(19, 1, 'superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:07:11'),
(20, 2, 'sarwai', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:14:14'),
(21, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-20 12:18:27'),
(22, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:21:57'),
(23, 2, 'sarwai', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:22:23'),
(24, 2, 'sarwai', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:23:29'),
(25, 1, 'superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:23:53'),
(26, 2, 'sarwai', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:24:42'),
(27, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-20 12:39:49'),
(28, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-20 12:40:18'),
(29, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:40:35'),
(30, 3, 'itsmohsink6@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-20 12:42:44'),
(31, 2, 'sarwai', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:43:04'),
(32, 2, 'sarwai', '154.81.230.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 12:54:25'),
(33, 2, 'sarwai', '154.81.230.204', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 13:05:11'),
(34, 2, 'sarwai', '154.81.230.204', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 13:05:12'),
(35, 1, 'superadmin', '154.198.91.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 13:55:14'),
(36, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 13:56:40'),
(37, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 13:57:27'),
(38, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 13:58:05'),
(39, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:00:54'),
(40, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:00:55'),
(41, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:01:23'),
(42, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:01:50'),
(43, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:03:22'),
(44, 1, 'superadmin', '154.198.91.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:03:32'),
(45, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:03:40'),
(46, 1, 'superadmin', '154.198.91.204', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:04:39'),
(47, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:20:31'),
(48, 2, 'sarwai', '154.198.91.204', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 14:20:32'),
(49, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 16:42:38'),
(50, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 16:42:39'),
(51, 1, 'superadmin', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 16:43:08'),
(52, 1, 'superadmin', '154.81.230.204', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-20 18:56:36'),
(53, 3, 'itsmohsink6@gmail.com', '154.81.228.5', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-21 09:19:21'),
(54, 3, 'itsmohsink6@gmail.com', '154.81.228.5', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-10-21 09:19:23'),
(55, 1, 'superadmin', '154.81.228.5', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 09:20:09'),
(56, 1, 'superadmin', '154.81.228.5', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 09:20:10'),
(57, 1, 'superadmin', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 14:48:28'),
(58, 1, 'superadmin', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 14:49:43'),
(59, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 14:52:06'),
(60, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 14:52:07'),
(61, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 14:52:31'),
(62, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 14:52:31'),
(63, 2, 'sarwai', '149.40.209.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-10-21 14:59:22'),
(64, 3, 'itsmohsink6@gmail.com', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-11-02 19:19:30'),
(65, 3, 'itsmohsink6@gmail.com', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-11-02 19:19:31'),
(66, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-02 19:19:37'),
(67, 2, 'sarwai', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-02 19:19:38'),
(68, 7, 'Toot', '103.147.86.236', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-02 19:20:31'),
(69, 3, 'itsmohsink6@gmail.com', '154.198.85.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-11-14 19:27:28'),
(70, 3, 'itsmohsink6@gmail.com', '154.198.85.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-11-14 19:27:31'),
(71, 3, 'itsmohsink6@gmail.com', '154.198.85.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-11-14 19:27:37'),
(72, 3, 'itsmohsink6@gmail.com', '154.198.85.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-11-14 19:27:47'),
(73, 3, 'itsmohsink6@gmail.com', '154.198.85.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-14 19:28:03'),
(74, 2, 'sarwai', '154.198.85.82', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-14 19:28:59'),
(75, 2, 'sarwai', '154.198.85.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-14 19:30:56'),
(76, 2, 'sarwai', '154.198.85.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-14 19:30:57'),
(77, 2, 'sarwai', '203.215.174.114', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_8 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/125.0.6422.145 Mobile/15E148 Safari/604.1', 'login_success', 'User logged in successfully.', '2025-11-21 00:31:22'),
(78, 2, 'sarwai', '203.215.174.114', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_5) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/125 Version/11.1.1 Safari/605.1.15', 'login_success', 'User logged in successfully.', '2025-11-21 00:31:44'),
(79, 3, 'itsmohsink6@gmail.com', '154.198.93.207', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2025-11-23 21:11:12'),
(80, 3, 'itsmohsink6@gmail.com', '154.198.93.207', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-23 21:12:05'),
(81, 2, 'sarwai', '154.198.93.207', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2025-11-23 21:12:42'),
(82, 1, 'superadmin', '203.215.174.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-12-04 16:14:20'),
(83, 1, 'superadmin', '203.215.174.114', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2025-12-04 16:14:21'),
(84, 1, 'superadmin', '154.91.165.219', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'login_success', 'User logged in successfully.', '2026-01-08 22:25:57'),
(85, 3, 'itsmohsink6@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:14'),
(86, 3, 'itsmohsink6@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:16'),
(87, NULL, 'mohsinstudioms@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:24'),
(88, 3, 'itsmohsink6@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:34'),
(89, 3, 'itsmohsink6@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:35'),
(90, 3, 'itsmohsink6@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:37'),
(91, NULL, 'mohsinstudioms@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:41'),
(92, 3, 'itsmohsink6@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-01-23 13:49:46'),
(93, 3, 'itsmohsink6@gmail.com', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-01-23 13:50:01'),
(94, 1, 'superadmin', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-01-23 13:50:43'),
(95, 2, 'sarwai', '103.53.162.99', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-01-23 13:50:51'),
(96, 1, 'superadmin', '102.209.109.169', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-01-23 14:05:35'),
(97, 2, 'sarwai', '102.209.109.169', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-01-23 14:10:04'),
(98, NULL, 'sarwaibranch', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-02-20 10:40:29'),
(99, NULL, 'sarwaibranch', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-02-20 10:41:08'),
(100, 2, 'sarwai', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-02-20 10:43:01'),
(101, 2, 'sarwai', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-02-20 11:46:42'),
(102, 1, 'superadmin', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-02-20 11:48:18'),
(103, 1, 'superadmin', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-02-20 11:48:28'),
(104, 16, 'felconttp', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-02-20 11:56:20'),
(105, 16, 'felconttp', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_fail', 'Invalid username or password.', '2026-02-20 11:57:26'),
(106, 16, 'falconttp', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-02-20 11:58:20'),
(107, 16, 'falconttp', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-02-23 11:36:22'),
(108, 2, 'sarwai', '154.192.139.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'login_success', 'User logged in successfully.', '2026-02-23 11:36:50');

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
  `contact_email` varchar(100) DEFAULT NULL,
  `class_of_interest` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `query_date` date NOT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `status` enum('active','closed','enrolled') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_queries`
--

INSERT INTO `admission_queries` (`id`, `branch_id`, `student_name`, `contact_person`, `contact_phone`, `contact_email`, `class_of_interest`, `source`, `notes`, `query_date`, `next_follow_up_date`, `status`, `created_by`, `created_at`) VALUES
(0, 1, 'Malik Mohsin Abbas', 'Ghulam Abbas', '+923044011996', 'alhaidergroupmarkhal@gmail.com', 'Class 1 - A', 'A Student Muhammad Umair Class 7 B', '', '2025-10-07', NULL, 'active', 2, '2025-10-07 06:01:24');

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

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `address`, `phone`, `email`, `logo`, `created_at`) VALUES
(1, 'Sarwai Branch', 'Sarwai, Pakistan', '03044011996', 'Itsmohsink6+sarwai@gmail.com', 'assets/uploads/logos/branch_1_1758251751.png', '2025-09-18 00:26:45'),
(2, 'Toot Branch', 'Toot, pakistan', '+923012345678', 'Itsmohsink6+toot@gmail.com', NULL, '2025-09-22 06:19:06'),
(3, 'Felcon TTP', 'Islamabad', '', 'itsmohsink6+ammar@gmail.com', NULL, '2026-02-20 06:53:04');

-- --------------------------------------------------------

--
-- Table structure for table `branch_smtp_settings`
--

CREATE TABLE `branch_smtp_settings` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `use_custom` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = Use Global, 1 = Use Custom',
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_pass` varchar(512) DEFAULT NULL COMMENT 'Encrypted password',
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_secure` varchar(10) DEFAULT NULL,
  `smtp_from_email` varchar(255) DEFAULT NULL,
  `smtp_from_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_smtp_settings`
--

INSERT INTO `branch_smtp_settings` (`id`, `branch_id`, `use_custom`, `smtp_host`, `smtp_user`, `smtp_pass`, `smtp_port`, `smtp_secure`, `smtp_from_email`, `smtp_from_name`) VALUES
(1, 1, 1, 'smtp.gmail.com', 'alhaidergroupmarkhal@gmail.com', 'zguwdoydfxtuhszr', 587, 'tls', 'alhaidergroupmarkhal@gmail.com', 'QSF SCHOOL Sarwai Branch');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'e.g., Grade 1, Class X',
  `numeric_name` int(11) DEFAULT NULL COMMENT 'e.g., 1, 10 for sorting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `branch_id`, `name`, `numeric_name`) VALUES
(1, 1, 'Class 1', 1),
(2, 1, 'Class 2', 2),
(3, 2, 'Class 1', 1),
(4, 2, 'Class 2', 2);

-- --------------------------------------------------------

--
-- Table structure for table `class_fee_structure`
--

CREATE TABLE `class_fee_structure` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `fee_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_fee_structure`
--

INSERT INTO `class_fee_structure` (`id`, `branch_id`, `session_id`, `class_id`, `fee_type_id`, `amount`, `created_at`) VALUES
(5, 1, 1, 1, 1, '1500.00', '2025-09-23 05:59:24'),
(7, 1, 1, 1, 3, '1500.00', '2025-09-23 05:59:44'),
(8, 1, 1, 1, 2, '1500.00', '2025-09-23 05:59:52');

-- --------------------------------------------------------

--
-- Table structure for table `class_routine`
--

CREATE TABLE `class_routine` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_no` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_routine`
--

INSERT INTO `class_routine` (`id`, `branch_id`, `class_id`, `section_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room_no`) VALUES
(1, 1, 1, 1, 1, 5, 'Monday', '08:30:00', '09:00:00', '1'),
(2, 1, 1, 1, 4, 5, 'Monday', '10:00:00', '10:30:00', '1'),
(3, 1, 1, 1, 3, 5, 'Monday', '09:30:00', '10:00:00', '1');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `branch_id`, `class_id`, `subject_id`) VALUES
(3, 1, 1, 1),
(4, 1, 1, 4),
(5, 1, 1, 3),
(6, 1, 1, 2),
(7, 1, 2, 8),
(8, 1, 2, 1),
(9, 1, 2, 4),
(10, 1, 2, 3),
(11, 1, 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `complaint_no` varchar(20) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `complaint_source` enum('student','teacher','parent','public') NOT NULL,
  `source_person_id` int(11) DEFAULT NULL COMMENT 'user_id for teacher, parent_id for parent',
  `source_student_ids` text DEFAULT NULL COMMENT 'Comma-separated student IDs',
  `complaint_by` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `complaint_date` date NOT NULL,
  `description` text NOT NULL,
  `action_taken` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `complaint_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','in_progress','resolved') NOT NULL DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `complaint_no`, `branch_id`, `complaint_source`, `source_person_id`, `source_student_ids`, `complaint_by`, `phone`, `complaint_date`, `description`, `action_taken`, `notes`, `complaint_type`, `status`, `created_by`, `created_at`) VALUES
(1, 'cmp000001', 1, 'student', NULL, '4,1', '2 student(s)', '03044011996', '2025-10-16', 'I Have Sumited the complaint Many Time Please Fix it', 'Principal', '', 'School Teacher', 'pending', 2, '2025-10-16 15:06:44'),
(2, 'cmp000002', 1, 'parent', NULL, NULL, 'Ghafoor Ahmed', '03048902227', '2025-10-20', 'the person said that the teacher have some personal issue with him beacuse he he is betting my son without any issue so please fix it not \r\nthe teacher name is Malik Yaseem and his son Name is Muhammad Naveed Class 7 A', 'Principle', '', 'Teacher', 'resolved', 2, '2025-10-20 06:26:28'),
(4, 'cmp000003', 1, 'student', NULL, '4,1', '2 student(s)', '03044011997', '2025-10-20', 'the person said that the teacher have some personal issue with him beacuse he he is betting my son without any issue so please fix it not \r\nthe teacher name is Malik Yaseem and his son Name is Muhammad Naveed Class 7 A', '', '', '', 'pending', 2, '2025-10-20 06:49:08');

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
  `attendance_status` enum('present','absent') NOT NULL DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_marks`
--

INSERT INTO `exam_marks` (`id`, `branch_id`, `session_id`, `exam_schedule_id`, `student_id`, `class_id`, `section_id`, `subject_id`, `marks_obtained`, `attendance_status`) VALUES
(0, 1, 1, 5, 1, 1, 1, 1, '59.00', 'present'),
(0, 1, 1, 6, 1, 1, 1, 4, '42.00', 'present'),
(0, 1, 1, 7, 1, 1, 1, 3, '53.00', 'present'),
(0, 1, 1, 8, 1, 1, 1, 2, '45.00', 'present');

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
  `room_no` varchar(50) DEFAULT NULL,
  `full_marks` decimal(5,2) NOT NULL,
  `pass_marks` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_schedule`
--

INSERT INTO `exam_schedule` (`id`, `branch_id`, `session_id`, `exam_type_id`, `class_id`, `subject_id`, `exam_date`, `start_time`, `end_time`, `room_no`, `full_marks`, `pass_marks`) VALUES
(1, 1, 1, 1, 1, 1, '2025-09-24', '08:30:00', '10:30:00', '1', '75.00', '33.00'),
(2, 1, 1, 1, 1, 4, '2025-09-26', '08:30:00', '10:00:00', '1', '50.00', '20.00'),
(3, 1, 1, 1, 1, 3, '2025-09-27', '08:30:00', '10:30:00', '1', '75.00', '33.00'),
(4, 1, 1, 1, 1, 2, '2025-09-30', '08:30:00', '10:30:00', '1', '75.00', '33.00'),
(5, 1, 1, 6, 1, 1, '2025-09-24', '08:30:00', '10:30:00', '1', '75.00', '33.00'),
(6, 1, 1, 6, 1, 4, '2025-09-25', '08:30:00', '09:30:00', '1', '50.00', '20.00'),
(7, 1, 1, 6, 1, 3, '2025-09-26', '08:30:00', '10:30:00', '1', '75.00', '33.00'),
(8, 1, 1, 6, 1, 2, '2025-09-28', '08:30:00', '10:30:00', '1', '75.00', '33.00');

-- --------------------------------------------------------

--
-- Table structure for table `exam_types`
--

CREATE TABLE `exam_types` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `publish_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_types`
--

INSERT INTO `exam_types` (`id`, `branch_id`, `session_id`, `name`, `publish_date`) VALUES
(6, 1, 1, 'First Term', '2025-09-22');

-- --------------------------------------------------------

--
-- Table structure for table `fee_concession_types`
--

CREATE TABLE `fee_concession_types` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_concession_types`
--

INSERT INTO `fee_concession_types` (`id`, `branch_id`, `name`, `type`, `value`, `description`) VALUES
(1, 1, 'Father Death', 'percentage', '50.00', ''),
(2, 1, '3 Brother', 'percentage', '25.00', '');

-- --------------------------------------------------------

--
-- Table structure for table `fee_groups`
--

CREATE TABLE `fee_groups` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_groups`
--

INSERT INTO `fee_groups` (`id`, `branch_id`, `session_id`, `name`, `description`, `created_at`) VALUES
(1, 1, 1, 'School Fees', '', '2025-09-23 05:56:33');

-- --------------------------------------------------------

--
-- Table structure for table `fee_invoices`
--

CREATE TABLE `fee_invoices` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `invoice_month` varchar(20) NOT NULL,
  `gross_amount` decimal(10,2) NOT NULL,
  `concession_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `concession_details` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `status` enum('unpaid','paid','partially_paid') NOT NULL DEFAULT 'unpaid',
  `barcode` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_invoices`
--

INSERT INTO `fee_invoices` (`id`, `branch_id`, `session_id`, `student_id`, `class_id`, `invoice_month`, `gross_amount`, `concession_amount`, `concession_details`, `total_amount`, `amount_paid`, `due_date`, `status`, `barcode`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2025-10', '1500.00', '375.00', '3 Brother (25.00%)', '1125.00', '1125.00', '2025-10-08', 'paid', '15511108102025', '2025-09-23 07:54:25', '2025-09-23 08:35:58'),
(2, 1, 1, 4, 1, '2025-10', '1500.00', '375.00', '3 Brother (25.00%)', '1125.00', '1125.00', '2025-10-08', 'paid', '15521208102025', '2025-09-23 07:54:25', '2025-09-23 08:36:13'),
(3, 1, 1, 4, 1, '2025-11', '1500.00', '375.00', '3 Brother (25.00%)', '1125.00', '0.00', '2025-11-20', 'unpaid', '15521220112025', '2025-11-14 14:29:43', '2025-11-14 14:29:43'),
(4, 1, 1, 1, 1, '2025-11', '1500.00', '375.00', '3 Brother (25.00%)', '1125.00', '0.00', '2025-11-20', 'unpaid', '15511020112025', '2025-11-14 14:29:43', '2025-11-14 14:29:43'),
(5, 1, 1, 4, 1, '2026-01', '1500.00', '375.00', '3 Brother (25.00%)', '1125.00', '0.00', '2026-01-08', 'unpaid', '15521208012026', '2025-11-20 19:33:22', '2025-11-20 19:33:22'),
(6, 1, 1, 1, 1, '2026-01', '1500.00', '375.00', '3 Brother (25.00%)', '1125.00', '0.00', '2026-01-08', 'unpaid', '15511008012026', '2025-11-20 19:33:22', '2025-11-20 19:33:22');

-- --------------------------------------------------------

--
-- Table structure for table `fee_invoice_details`
--

CREATE TABLE `fee_invoice_details` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `fee_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_invoice_details`
--

INSERT INTO `fee_invoice_details` (`id`, `invoice_id`, `fee_type_id`, `amount`) VALUES
(1, 1, 2, '1500.00'),
(2, 2, 2, '1500.00'),
(3, 3, 2, '1500.00'),
(4, 4, 2, '1500.00'),
(5, 5, 2, '1500.00'),
(6, 6, 2, '1500.00');

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `collected_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_payments`
--

INSERT INTO `fee_payments` (`id`, `invoice_id`, `branch_id`, `amount`, `payment_date`, `payment_method`, `notes`, `collected_by`, `created_at`) VALUES
(1, 1, 1, '1125.00', '2025-09-23', 'Cash', '', 2, '2025-09-23 08:35:58'),
(2, 2, 1, '1125.00', '2025-09-23', 'Cash', '', 2, '2025-09-23 08:36:13');

-- --------------------------------------------------------

--
-- Table structure for table `fee_types`
--

CREATE TABLE `fee_types` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `fee_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_types`
--

INSERT INTO `fee_types` (`id`, `branch_id`, `session_id`, `group_id`, `name`, `fee_code`, `description`, `is_default`, `created_at`) VALUES
(1, 1, 1, 1, 'Admission Fees', 'admfees', '', 0, '2025-09-23 05:57:00'),
(2, 1, 1, 1, 'Tuition Fees', 'tfees', '', 1, '2025-09-23 05:57:29'),
(3, 1, 1, 1, 'Exam Fees', 'exmfees', '', 0, '2025-09-23 05:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invitation_templates`
--

CREATE TABLE `invitation_templates` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `language` varchar(10) NOT NULL COMMENT 'e.g., en, ur',
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invitation_templates`
--

INSERT INTO `invitation_templates` (`id`, `branch_id`, `title`, `language`, `content`, `created_at`, `updated_at`) VALUES
(1, 1, 'Result 2025', 'ur', 'بالخدمت جناب [parent_name] صاحب/صاحبہ\r\nالسلام علیکم ورحمۃ اللہ وبرکاتہ\r\n\r\nباعرضِ مسرت اطلاع دی جاتی ہے کہ سکول ہذا میں بچوں کے سالانہ امتحانات کے نتائج کے سلسلہ میں مورخہ [event_date] بروز بدھ بوقت [event_time] ایک پر وقار تقریب کا انعقاد کیا جا رہا ہے، جس میں بچوں کو ان کے نتائج کی بنیاد پر انعامات سے نوازا جائے گا۔\r\nآپ کے صاحبزادے/صاحبزادی [student_name] جماعت [class_name] کے طالب علم/طالبہ ہیں۔ اس خوشی کے موقع پر آپ کی شرکت ہمارے لیے باعثِ اعزاز ہوگی۔\r\n\r\nبرائے کرم وقت پر تشریف لائیں۔\r\nمقام: [venue]\r\n\r\nوالسلام\r\nبذریعہ\r\nانتظامیہ، سکول ہذا', '2025-10-08 17:03:37', '2025-10-08 17:03:37'),
(2, 1, 'Result for Public', 'ur', 'بالخدمت جناب [parent_name] صاحب\r\n\r\nالسلام علیکم ورحمۃ اللہ وبرکاتہ\r\n\r\nباعرضِ مسرت اطلاع دی جاتی ہے کہ سکول ہذا میں بچوں کے سالانہ امتحانات کے نتائج کے سلسلہ میں مورخہ [event_date] بروز بدھ بوقت [event_time] ایک پر وقار تقریب کا انعقاد کیا جا رہا ہے، جس میں بچوں کو ان کے نتائج کی بنیاد پر انعامات سے نوازا جائے گا۔\r\n\r\nبرائے کرم وقت پر تشریف لائیں۔\r\nمقام: [venue]\r\n\r\nوالسلام\r\nبذریعہ\r\nانتظامیہ، سکول ہذا', '2025-10-08 17:18:54', '2025-10-08 17:18:54');

-- --------------------------------------------------------

--
-- Table structure for table `marks_grades`
--

CREATE TABLE `marks_grades` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `grade_name` varchar(50) NOT NULL,
  `percent_from` decimal(5,2) NOT NULL,
  `percent_upto` decimal(5,2) NOT NULL,
  `grade_point` decimal(4,2) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks_grades`
--

INSERT INTO `marks_grades` (`id`, `branch_id`, `grade_name`, `percent_from`, `percent_upto`, `grade_point`, `remarks`) VALUES
(1, 1, 'A+', '90.00', '100.00', NULL, ''),
(2, 1, 'A', '80.00', '90.00', NULL, ''),
(3, 1, 'B+', '70.00', '80.00', NULL, ''),
(4, 1, 'B', '65.00', '70.00', NULL, ''),
(5, 1, 'C', '55.00', '65.00', NULL, ''),
(6, 1, 'D', '45.00', '55.00', NULL, ''),
(7, 1, 'E', '35.00', '45.00', NULL, ''),
(8, 1, 'F', '0.00', '35.00', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `news_and_events`
--

CREATE TABLE `news_and_events` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `type` enum('news','event') NOT NULL DEFAULT 'news',
  `status` enum('published','draft') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_and_events`
--

INSERT INTO `news_and_events` (`id`, `branch_id`, `title`, `content`, `image_path`, `event_date`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'School Result', '<h4>School Annual Result is Headling in 20 October 2025. Everyone can Visit&nbsp;<br><strong>Thanks</strong></h4>', 'assets/uploads/news/68e60c39daf5e_qsf.png', '2025-10-20', 'event', 'published', '2025-10-08 07:01:13', '2025-10-21 09:51:23');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Link to the users table for login',
  `branch_id` int(11) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `father_phone` varchar(20) NOT NULL,
  `father_cnic` varchar(20) NOT NULL,
  `father_email` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_cnic` varchar(20) DEFAULT NULL,
  `mother_phone` varchar(20) DEFAULT NULL,
  `mother_email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `user_id`, `branch_id`, `father_name`, `father_phone`, `father_cnic`, `father_email`, `mother_name`, `mother_cnic`, `mother_phone`, `mother_email`) VALUES
(1, 6, 1, 'Ghulam Abbas', '03000578074', '3720315277101', 'ghulamabbas@gmail.com', 'Naseem Beagum', '3720380809702', '03005383847', 'naseembeagum@gmail.com'),
(2, 12, 1, 'Ghafoor Ahmed', '03048902227', '3720315277119', 'ghafoorahmed@gmail.com', '', '', '', ''),
(3, 14, 1, 'Ameer Khan', '03000123456', '3720315277111', 'ameerkhan@gmail.com', '', '', '', '');

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

--
-- Dumping data for table `phone_log`
--

INSERT INTO `phone_log` (`id`, `branch_id`, `name`, `phone`, `call_date`, `description`, `next_follow_up_date`, `call_type`, `created_by`, `created_at`) VALUES
(0, 1, 'Malik Mohsin Abbas', '03044011996', '2025-10-08 10:54:00', 'The Person Said He Want admit his son in class 8th', NULL, 'incoming', 2, '2025-10-08 05:55:19');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'e.g., A, B, Blue, Green',
  `capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `class_id`, `branch_id`, `name`, `capacity`) VALUES
(1, 1, 1, 'A', 30),
(2, 2, 1, 'A', 30),
(3, 2, 1, 'B', 30),
(4, 3, 2, 'A', 35),
(5, 3, 2, 'B', 35),
(6, 4, 2, 'A', 35),
(7, 4, 2, 'B', 35);

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

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'site_name', 'Quiad Science Foundation School System'),
(2, 'site_logo', 'assets/images/logo.png'),
(3, 'card_bg_color', '#ffffff'),
(4, 'card_text_color', '#000000'),
(5, 'card_label_color', NULL),
(24, 'card_header_bg_color', '#007bff'),
(43, 'card_bg_opacity', '0.77'),
(44, 'card_header_bg_opacity', '1'),
(90, 'card_photo_shape', 'box'),
(134, 'sms_gateway_host', 'http://10.81.165.176:8082'),
(135, 'sms_gateway_token', 'f32eddd4-f43a-4894-b374-0cd8a501b37c'),
(136, 'backup_time', '09:34'),
(137, 'backup_retention_days', '30'),
(160, 'public_page_hero_title', 'Welcome'),
(161, 'public_page_hero_subtitle', 'Welcome'),
(162, 'public_page_about_us', '<h1>Welcome<br><br></h1>\r\n<h3>to <strong>QSF SCHOOL SYSTE<img src=\"E:\\wamp64\\www\\ai_school\\assets\\images\\logo.png\" alt=\"\">M</strong></h3>\r\n<h2><img src=\"E:\\wamp64\\www\\ai_school\\assets\\images\\logo.png\" alt=\"\"><img src=\"https://schoolnest.free.nf//assets/uploads/logos/branch_1_1758251751.png\" alt=\"\" width=\"66\" height=\"66\"></h2>'),
(163, 'public_page_contact_address', 'Sarwai'),
(164, 'public_page_contact_phone', '03048902227'),
(165, 'public_page_contact_email', ''),
(178, 'public_page_main_branch_id', '1'),
(235, 'login_notifications', '1'),
(236, 'smtp_host', 'smtp.gmail.com'),
(237, 'smtp_user', 'alhaidergroupmarkhal@gmail.com'),
(238, 'smtp_port', '587'),
(239, 'smtp_secure', 'tls'),
(240, 'smtp_from_email', 'alhaidergroupmarkhal@gmail.com'),
(241, 'smtp_from_name', 'QSF SCHOOL SYSTEM'),
(242, 'smtp_pass', 'K+Xdrl0wYqPgS3rkyThvr1l0SnA1SGRLTm1uTVJQWW1rUEI2TlhickxtQ0tpcjNNc2lydS9JbTRTTUE9');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `admission_no` varchar(50) NOT NULL,
  `admission_date` date NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `caste` varchar(50) DEFAULT NULL,
  `mobile_no` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `branch_id`, `parent_id`, `admission_no`, `admission_date`, `dob`, `gender`, `cnic`, `blood_group`, `religion`, `caste`, `mobile_no`, `photo`, `current_address`, `permanent_address`) VALUES
(1, 3, 1, 1, '1551', '2013-03-15', '2007-03-05', 'male', '', NULL, NULL, NULL, '03048902227', 'assets/uploads/students/68cba5ffb5202_student_6881fe051fd47.jpg', NULL, NULL),
(4, 11, 1, 1, '1552', '2025-09-22', '2007-01-01', 'male', '3720320569708', NULL, NULL, NULL, '03041819112', 'assets/uploads/students/68d0f34ca47cf_691a5948-b84e-4d94-8338-6ba0411a870a.jpg', NULL, NULL),
(5, 13, 1, 2, '1553', '2025-10-07', '2006-03-05', 'male', '3720310103029', NULL, NULL, NULL, '03044011996', 'assets/uploads/students/68e4946c6f23a_Azhar1 (1).jpg', NULL, NULL),
(6, 15, 1, 3, '1554', '2025-10-16', '2000-01-01', 'male', '3720315277164', NULL, NULL, NULL, '03044011996', 'assets/uploads/students/68f0f6a29e360_IMG-20251012-WA0104.jpg', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` enum('present','late','absent','half_day') NOT NULL,
  `attendance_date` date NOT NULL,
  `remark` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `branch_id`, `student_id`, `class_id`, `section_id`, `teacher_id`, `status`, `attendance_date`, `remark`) VALUES
(1, 1, 1, 1, 1, 5, 'present', '2025-09-18', 'On Time');

-- --------------------------------------------------------

--
-- Table structure for table `student_concessions`
--

CREATE TABLE `student_concessions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `concession_type_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_concessions`
--

INSERT INTO `student_concessions` (`id`, `student_id`, `session_id`, `concession_type_id`, `notes`) VALUES
(1, 1, 1, 2, ''),
(2, 4, 1, 2, '');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `document_title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `student_id`, `branch_id`, `document_title`, `file_path`, `file_type`, `uploaded_at`, `uploaded_by`) VALUES
(3, 1, 1, 'Result Card', 'assets/uploads/student_documents/result_card_malik_mohsin_abbas_class_1_a_2025-2026_branch_68e6934ade79a.jpg', 'image/jpeg', '2025-10-08 16:37:30', 2);

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
  `roll_no` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`id`, `session_id`, `student_id`, `class_id`, `section_id`, `roll_no`) VALUES
(2, 2, 1, 1, 1, NULL),
(4, 1, 4, 1, 1, '2'),
(5, 1, 5, 2, 2, NULL),
(7, 3, 1, 3, 4, '1'),
(9, 1, 1, 1, 1, NULL),
(10, 1, 6, 2, 3, '1');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `type` enum('theory','practical','optional','mandatory') NOT NULL DEFAULT 'mandatory'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `branch_id`, `name`, `code`, `type`) VALUES
(1, 1, 'English', 'Eng1', 'mandatory'),
(2, 1, 'Urdu', 'Urdu1', 'mandatory'),
(3, 1, 'Math', 'Math', 'mandatory'),
(4, 1, 'Islamiyat', 'Isl', 'mandatory'),
(5, 1, 'Physics', 'Phy', 'optional'),
(6, 1, 'Biology', 'Bio', 'optional'),
(7, 1, 'Chemistry', 'CHE', 'optional'),
(8, 1, 'Computer', 'Cpt', 'mandatory');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `incharge_class_id` int(11) DEFAULT NULL COMMENT 'If they are incharge of a whole class',
  `incharge_section_id` int(11) DEFAULT NULL COMMENT 'If they are incharge of a specific section',
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `branch_id`, `incharge_class_id`, `incharge_section_id`, `dob`, `gender`, `cnic`, `joining_date`, `photo`) VALUES
(1, 5, 1, 1, 1, '2007-03-05', 'male', '3720320569709', '2001-01-01', 'assets/uploads/teachers/68cbad20e1a51_Azhar1.png');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `branch_id`, `teacher_id`, `section_id`, `subject_id`) VALUES
(1, 1, 5, 1, 6),
(2, 1, 5, 1, 7),
(3, 1, 5, 1, 1);

-- --------------------------------------------------------

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

INSERT INTO `users` (`id`, `branch_id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `password_reset_token`, `password_reset_expires`, `last_login`, `created_at`, `updated_at`) VALUES
(1, NULL, 'superadmin', 'itsmohsink6+superadmin@gmail.com', '$2y$10$AFMlrbJsarCC4wM7NjDi3e6LmF4ae.3VvxiA1e8aF8Rgm1wKkyJZ.', 'Super Administrator', 'superadmin', 'active', NULL, NULL, '2025-10-07 05:40:42', '2023-10-26 05:30:00', '2025-10-20 05:42:17'),
(2, 1, 'sarwai', 'demobymohsin+sarwai@gmail.com', '$2y$10$yrN6/TKKaELWg3csd7hnQuneEeNg.rTCEOAiUX8pezmrdbjZSaMcC', 'Amir Anjum', 'branchadmin', 'active', NULL, NULL, '2025-10-08 05:53:13', '2025-09-18 00:30:37', '2025-10-20 07:13:57'),
(3, 1, 'itsmohsink6@gmail.com', 'itsmohsink6@gmail.com', '$2y$10$4AGRkZRJOc6VZtkfMmdJT.yvXf5P..HztmBWt8fNYUj8D9fJxL4B.', 'Malik Mohsin Abbas', 'student', 'active', '0a19fdcce7790b47c72215c67d33f85bee671d0084963dfef5a6af0618a2b06953d60f987da92f6c458d12931477e91e4c79', '2026-02-23 13:52:05', '2025-09-22 06:28:04', '2025-09-18 06:17:16', '2026-02-23 07:52:05'),
(5, 1, 'azhariqbal@gmail.com', 'azhariqbal@gmail.com', '$2y$10$mzXvSLL8CsS63pN4ra7w1.y.9GFFWbEkJP8lW4RGt4I965BehfIf6', 'Azhar Iqbal', 'teacher', 'active', NULL, NULL, '2025-10-08 05:43:46', '2025-09-18 06:56:33', '2025-10-08 05:43:46'),
(6, 1, '03000578074', 'ghulamabbas@gmail.com', '$2y$10$jZlHuUN98lG/dmkyAdJ8fut/EJD19innPUhwmKhpV2RXE3xjnPZdC', 'Ghulam Abbas', 'parent', 'active', NULL, NULL, '2025-09-23 08:36:31', '2025-09-18 09:13:16', '2025-09-23 08:36:31'),
(7, 2, 'toot', 'itsmohsink6+toot@gmail.com', '$2y$10$/xhUiL1pqtRbOArnzrm8Cu6s6XkQlEuzVjDaOwu1ybBr80zDaudJi', 'Zohaib Hassan', 'branchadmin', 'active', NULL, NULL, '2025-10-07 05:31:30', '2025-09-22 06:19:46', '2025-10-20 13:57:13'),
(11, 1, 'aftabahmed81', 'mohsinabbas10yhoo@gmail.com', '$2y$10$fe.VCgqzIrqKoDmcxbEz7uK7p6fT5r1Zwy7dKx8AGlxKcQP6o5z4i', 'Aftab Ahmed', 'student', 'active', NULL, NULL, NULL, '2025-09-22 06:57:16', '2025-09-22 06:57:16'),
(12, 1, '03048902227', '68e493f48c6ea_03048902227@school.local', '$2y$10$vqq4bCkPBHlU6R5jjMuDj./pjn3kbHec0C1qY.IZ25gvZjtel8DAi', 'Ghafoor Ahmed', 'parent', 'active', NULL, NULL, NULL, '2025-10-07 04:15:48', '2025-10-07 04:15:48'),
(13, 1, 'waseemahmed@gmail.com', 'waseemahmed@gmail.com', '$2y$10$thCvHM0D8CeAOykxlHdm1e5AkiQYH0ZvprC6zzY8DyAzfcvVFYJ6O', 'Waseem Ahmed', 'student', 'active', NULL, NULL, NULL, '2025-10-07 04:15:48', '2025-10-07 04:17:48'),
(14, 1, '03000123456', '68f0f6648cc35_03000123456@school.local', '$2y$10$nCkMtme3rJZUQ5eMmSQ5Pen1tzia8QT6D/auDri2VHfOWfkVw.F82', 'Ameer Khan', 'parent', 'active', NULL, NULL, NULL, '2025-10-16 13:43:00', '2025-10-16 13:43:00'),
(15, 1, 'ikkaptan20@gmail.com', 'ikkaptan20@gmail.com', '$2y$10$H8E6/IPFjju205rtEuAUTe3H9QU6ZijN2LDtr.pjkNqDQ870CZxJe', 'Muhammad Imran', 'student', 'active', NULL, NULL, NULL, '2025-10-16 13:43:01', '2025-10-16 13:44:02'),
(16, 3, 'falconttp', 'itsmohsink6+ammar@gmail.com', '$2y$10$9tjQDCjizYDm0FLtLg1SuuNpm.Yi5j1.C1uFHJbDXA6.VHKix5H6O', 'Ammar Abbasi', 'branchadmin', 'active', NULL, NULL, NULL, '2026-02-20 06:54:06', '2026-02-20 06:57:58');

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

--
-- Dumping data for table `visitor_log`
--

INSERT INTO `visitor_log` (`id`, `branch_id`, `visitor_name`, `purpose`, `person_to_meet`, `phone`, `id_card_details`, `entry_time`, `exit_time`, `notes`, `created_by`, `created_at`) VALUES
(0, 1, 'Malik Mohsin Abbas', 'Visiting', 'Principal', '+923044011996', '3720320569709', '2025-10-07 11:03:00', '2025-10-07 06:11:33', '', 2, '2025-10-07 06:03:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branch_smtp_settings`
--
ALTER TABLE `branch_smtp_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_id` (`branch_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `class_fee_structure`
--
ALTER TABLE `class_fee_structure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_fee` (`branch_id`,`class_id`,`fee_type_id`),
  ADD UNIQUE KEY `session_class_fee_type` (`session_id`,`class_id`,`fee_type_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `fee_type_id` (`fee_type_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `class_routine`
--
ALTER TABLE `class_routine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_schedule` (`session_id`,`exam_type_id`,`class_id`,`subject_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `exam_type_id` (`exam_type_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `exam_types`
--
ALTER TABLE `exam_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_session` (`branch_id`,`session_id`,`name`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `fee_concession_types`
--
ALTER TABLE `fee_concession_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `fee_groups`
--
ALTER TABLE `fee_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_session_name` (`branch_id`,`session_id`,`name`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `fee_invoices`
--
ALTER TABLE `fee_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_month` (`student_id`,`invoice_month`),
  ADD UNIQUE KEY `student_session_month` (`student_id`,`session_id`,`invoice_month`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `fee_invoice_details`
--
ALTER TABLE `fee_invoice_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `fee_types`
--
ALTER TABLE `fee_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fee_code_branch` (`branch_id`,`fee_code`),
  ADD UNIQUE KEY `branch_session_code` (`branch_id`,`session_id`,`fee_code`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invitation_templates`
--
ALTER TABLE `invitation_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `marks_grades`
--
ALTER TABLE `marks_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `news_and_events`
--
ALTER TABLE `news_and_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `father_cnic` (`branch_id`,`father_cnic`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `admission_no` (`branch_id`,`admission_no`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_date` (`student_id`,`attendance_date`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `student_concessions`
--
ALTER TABLE `student_concessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_session_concession` (`student_id`,`session_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `concession_type_id` (`concession_type_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_session` (`session_id`,`student_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `incharge_class_id` (`incharge_class_id`),
  ADD KEY `incharge_section_id` (`incharge_section_id`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_section_subject` (`teacher_id`,`section_id`,`subject_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `branch_id` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `branch_smtp_settings`
--
ALTER TABLE `branch_smtp_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class_fee_structure`
--
ALTER TABLE `class_fee_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `class_routine`
--
ALTER TABLE `class_routine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `exam_types`
--
ALTER TABLE `exam_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fee_concession_types`
--
ALTER TABLE `fee_concession_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fee_groups`
--
ALTER TABLE `fee_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fee_invoices`
--
ALTER TABLE `fee_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fee_invoice_details`
--
ALTER TABLE `fee_invoice_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fee_types`
--
ALTER TABLE `fee_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invitation_templates`
--
ALTER TABLE `invitation_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `marks_grades`
--
ALTER TABLE `marks_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `news_and_events`
--
ALTER TABLE `news_and_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_concessions`
--
ALTER TABLE `student_concessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD CONSTRAINT `academic_sessions_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `branch_smtp_settings`
--
ALTER TABLE `branch_smtp_settings`
  ADD CONSTRAINT `fk_branch_smtp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_routine`
--
ALTER TABLE `class_routine`
  ADD CONSTRAINT `class_routine_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_routine_ibfk_5` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_types`
--
ALTER TABLE `exam_types`
  ADD CONSTRAINT `exam_types_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invitation_templates`
--
ALTER TABLE `invitation_templates`
  ADD CONSTRAINT `invitation_templates_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marks_grades`
--
ALTER TABLE `marks_grades`
  ADD CONSTRAINT `marks_grades_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parents_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sections_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_5` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_documents_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `student_enrollments_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollments_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollments_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_ibfk_3` FOREIGN KEY (`incharge_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teachers_ibfk_4` FOREIGN KEY (`incharge_section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
