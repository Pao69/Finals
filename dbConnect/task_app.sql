-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 11:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `task_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `password_attempts`
--

CREATE TABLE `password_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expiry` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `code`, `expiry`, `used`, `created_at`) VALUES
(1, 'munggoNiLloyd@gmail.com', '910185', '2025-05-19 05:08:32', 0, '2025-05-19 10:53:32'),
(2, 'munggoNiLloyd@gmail.com', '048606', '2025-05-19 05:11:04', 0, '2025-05-19 10:56:04'),
(3, 'munggoNiLloyd@gmail.com', '534286', '2025-05-19 05:40:41', 0, '2025-05-19 11:25:41'),
(4, 'Shaiyon@gmail.com', '490932', '2025-05-19 06:23:55', 0, '2025-05-19 12:08:55'),
(5, 'Shaiyon@gmail.com', '229755', '2025-05-19 06:24:41', 0, '2025-05-19 12:09:41'),
(6, 'Shaiyon@gmail.com', '414028', '2025-05-19 06:28:46', 0, '2025-05-19 12:13:46'),
(7, 'Shaiyon@gmail.com', '659361', '2025-05-19 06:25:17', 0, '2025-05-19 12:15:17'),
(8, 'Shaiyon@gmail.com', '688082', '2025-05-19 06:36:12', 0, '2025-05-19 12:26:12'),
(9, 'Shaiyon@gmail.com', '857922', '2025-05-19 06:39:01', 0, '2025-05-19 12:29:01'),
(10, 'Shaiyon@gmail.com', '073164', '2025-05-19 12:48:01', 1, '2025-05-19 12:47:46'),
(11, 'shaiyon@gmail.com', '243444', '2025-05-25 04:37:51', 1, '2025-05-25 04:37:24'),
(12, 'shaiyon@gmail.com', '873261', '2025-05-24 22:48:53', 0, '2025-05-25 04:38:53'),
(13, 'shaiyon@gmail.com', '116396', '2025-05-24 22:49:58', 0, '2025-05-25 04:39:58'),
(14, 'shaiyon@gmail.com', '382928', '2025-05-24 22:51:47', 0, '2025-05-25 04:41:47'),
(15, 'shaiyon@gmail.com', '469386', '2025-05-24 22:57:59', 0, '2025-05-25 04:47:59'),
(16, 'shaiyon@gmail.com', '340040', '2025-05-24 23:02:07', 0, '2025-05-25 04:52:07'),
(17, 'shaiyon@gmail.com', '409477', '2025-05-25 00:09:16', 0, '2025-05-25 05:59:16'),
(18, 'shaiyon@gmail.com', '367036', '2025-05-26 01:13:35', 0, '2025-05-26 07:03:35'),
(19, 'shaiyon@gmail.com', '213699', '2025-05-26 01:13:45', 0, '2025-05-26 07:03:45'),
(20, 'shaiyon@gmail.com', '290002', '2025-05-26 01:15:02', 0, '2025-05-26 07:05:02'),
(21, 'rumofart@gmail.com', '225235', '2025-05-26 01:17:32', 0, '2025-05-26 07:07:32'),
(22, 'rumofart@gmail.com', '924173', '2025-05-26 01:19:15', 0, '2025-05-26 07:09:15'),
(23, 'rumofart@gmail.com', '788170', '2025-05-26 01:22:11', 0, '2025-05-26 07:12:11'),
(24, 'rumofart@gmail.com', '763646', '2025-05-26 01:51:46', 0, '2025-05-26 07:41:46'),
(25, 'rumofart@gmail.com', '927876', '2025-05-26 01:56:43', 0, '2025-05-26 07:46:43'),
(26, 'rumofart@gmail.com', '584949', '2025-05-26 01:59:09', 0, '2025-05-26 07:49:09'),
(27, 'rumofart@gmail.com', '622181', '2025-05-26 02:00:18', 0, '2025-05-26 07:50:18'),
(28, 'rumofart@gmail.com', '279043', '2025-05-26 02:02:09', 0, '2025-05-26 07:52:09'),
(29, 'rumofart@gmail.com', '426876', '2025-05-26 02:04:10', 0, '2025-05-26 07:54:10'),
(30, 'rumofart@gmail.com', '856434', '2025-05-26 07:57:01', 1, '2025-05-26 07:56:51'),
(31, 'rumofart@gmail.com', '236585', '2025-05-26 07:59:15', 1, '2025-05-26 07:58:35');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `upload_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `user_id`, `task_id`, `filename`, `original_filename`, `file_type`, `file_size`, `description`, `upload_date`) VALUES
(6, 16, 18, 'resource_6832a62199af6.jpg', '494179745_954918206850069_5988527051095703533_n - Copy.jpg', 'image/jpeg', 27992, 'Skbidi', '2025-05-25 13:09:53'),
(7, 16, 21, 'resource_6833d34bc219e.jpg', '494688483_1508759380085143_2311175477849458383_n.jpg', 'image/jpeg', 126185, 'f', '2025-05-26 10:34:51'),
(9, 17, 27, 'resource_68340f078cbae.docx', 'Chap 1-5.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 598182, 'Task resources to read', '2025-05-26 14:49:43');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `priority` enum('low','medium','high') DEFAULT 'medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `completed`, `created_at`, `updated_at`, `priority`) VALUES
(7, 9, 'f', 'f', '2025-05-19 00:00:00', 0, '2025-05-19 00:46:36', '2025-05-26 12:44:31', 'low'),
(8, 9, 'skbidi', 'fffwa', '2025-05-19 00:00:00', 0, '2025-05-19 18:26:36', '2025-05-19 18:26:36', 'medium'),
(9, 10, 'Skibidi', 'EAT MY ASS', '2025-05-30 00:00:00', 1, '2025-05-19 20:55:04', '2025-05-24 16:03:00', 'medium'),
(10, 10, 'Rape Eaton', 'eaton rape spread the word', '2025-05-27 00:00:00', 1, '2025-05-24 15:46:23', '2025-05-24 16:02:44', 'medium'),
(11, 10, 'fard', 'dwasdwasd', '2025-05-28 00:00:00', 1, '2025-05-24 15:49:26', '2025-05-24 16:02:52', 'medium'),
(12, 10, 'dwasdwasd', 'dwadadada', '2025-05-29 00:00:00', 1, '2025-05-24 15:53:55', '2025-05-24 16:02:58', 'medium'),
(18, 16, 'jerome', 'jerome', '2025-06-07 16:06:00', 0, '2025-05-25 13:09:34', '2025-05-26 10:23:01', 'medium'),
(20, 16, 'aafwwa', 'ffawfaw', '2025-07-05 01:03:00', 0, '2025-05-26 10:05:49', '2025-05-26 10:25:30', 'medium'),
(21, 16, 'tae', 'tae', '2025-05-26 10:30:00', 0, '2025-05-26 10:30:46', '2025-05-26 10:30:46', 'medium'),
(22, 16, 'aafwwaggg', 'ffawfawgg', '2025-08-09 17:09:00', 0, '2025-05-26 10:44:36', '2025-05-26 10:44:36', 'medium'),
(23, 16, 'tae', 'tae', '2025-05-26 02:36:00', 0, '2025-05-26 10:56:39', '2025-05-26 10:56:39', 'medium'),
(24, 16, 'tae', 'tae', '2025-06-07 18:36:00', 1, '2025-05-26 10:56:47', '2025-05-26 10:56:47', 'medium'),
(25, 16, 'rumonide', 'djwkaljlksdklsaj', '2025-06-09 04:53:00', 0, '2025-05-26 12:53:41', '2025-05-26 12:53:41', 'medium'),
(26, 16, 'rumonide', 'djwkaljlksdklsaj', '2025-06-08 12:53:00', 0, '2025-05-26 12:53:53', '2025-05-26 12:56:51', 'high'),
(27, 17, 'Task today', 'do this task asap', '2025-05-27 06:42:00', 0, '2025-05-26 14:43:09', '2025-05-26 14:43:09', 'high');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp(),
  `pfp` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `phone`, `email`, `last_login`, `role`, `created_at`, `pfp`) VALUES
(3, 'Shaiyon', '$2y$10$nthF/P9UqT9sTc/yHl1Pg.gT0Xc1rZqlhL4TQze/alAmSlJCliliu', '09999999999', 'shanshan@gmail.com', '2025-05-25 17:17:49', 'admin', '2025-05-18 18:45:14', '/src/images/profile_3_1748079754.jpg'),
(4, 'Skibidi', '$2y$10$Gh8ErEm/T2stkYG0ok1KGeV8tWCv.gWGGQWmJ/dV/FnnbO5ZsBYAu', '09999999998', 'skbd@gmail.com', NULL, 'user', '2025-05-18 18:45:14', NULL),
(5, 'JiEm', '$2y$10$NqTERoTMuSvZFAY8ZZiroucf2LXKGAYUhY87S8rKSRKwyIHVQr0Vy', '09999999997', 'je-em@gmail.com', NULL, 'user', '2025-05-18 18:45:14', NULL),
(7, 'Shabu', '$2y$10$2GbJW3Mcz89a.WnBIWADEOE4tTCQPzTd8ZuNftoJIkS0skeeln.cy', '09999999996', 'shabunganay@gmail.com', NULL, 'user', '2025-05-18 18:45:14', NULL),
(8, 'Tae', '$2y$10$JLVy5ZEqkuAGARK.IXwFcORuI7OTEB1Tn4EjOdPptY2A/T4aESzhC', '09999999995', 'tae@gmail.com', NULL, 'user', '2025-05-18 18:45:14', NULL),
(9, 'MongoLloyd', '$2y$10$Fb1IJs37dvrlCOK8zh4wwOTzY3ZJgVyN3EfDtMAlD/ECBIi9HakSu', '09999999994', 'munggoNiLloyd@gmail.com', '2025-05-19 19:26:46', 'user', '2025-05-18 18:45:14', NULL),
(10, 'Shai', '$2y$10$ePO1XS5CCfLZT2JxSPb2yebfQiqecNC1yMSTwUqNbUIIcp6b.BxUW', '09545454545', 'shaiyon@gmail.com', '2025-05-24 17:40:39', 'user', '2025-05-19 19:32:56', '/src/images/profile_10_1748079664.jpg'),
(11, 'Rumonite', '$2y$10$/dcGhquHKKwizV5Pz0Ef0udRk.kTpW8R111uiEaEDjiAhtHIdjIsy', '09812390823', 'rumofart@gmail.com', '2025-05-26 15:59:23', 'admin', '2025-05-24 16:44:32', NULL),
(16, 'Jerome', '$2y$10$c8TV7b4hlEiHidw0W3ap4.95tRnJ0Zm05EVOc.hgneYH/mkEA2oOy', '63478962349', 'Jerome@gmail.com', '2025-05-26 14:47:26', 'user', '2025-05-25 13:07:24', '/pfp/profile_16_1748227453.png'),
(17, 'useraccount', '$2y$10$Rvf8BBL.0dahI8S.4biNh.6Ldn3yfVVbccZcuMHuuyEXbpuvTlrH2', '09123451234', 'useracc@gmail.com', '2025-05-26 14:47:47', 'user', '2025-05-26 14:38:55', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `password_attempts`
--
ALTER TABLE `password_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_attempts` (`user_id`,`attempt_time`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_code` (`email`,`code`),
  ADD KEY `idx_expiry` (`expiry`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_completed` (`completed`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `password_attempts`
--
ALTER TABLE `password_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_attempts`
--
ALTER TABLE `password_attempts`
  ADD CONSTRAINT `password_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
