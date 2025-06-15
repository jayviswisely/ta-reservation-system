-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2025 at 10:33 AM
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
-- Database: `ta_reservation_system`
--
CREATE DATABASE IF NOT EXISTS ta_reservation_system;
USE ta_reservation_system;
-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `course_id`, `author_id`, `title`, `content`, `created_at`) VALUES
(2, 1, 14, 'BEIDANG', 'you guys all failed', '2025-06-15 08:21:43');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reactions`
--

CREATE TABLE `announcement_reactions` (
  `reaction_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_reactions`
--

INSERT INTO `announcement_reactions` (`reaction_id`, `announcement_id`, `user_id`) VALUES
(6, 2, 8);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `student_id`, `schedule_id`) VALUES
(1, 6, 1),
(2, 8, 2),
(3, 6, 6);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_feedback`
--

CREATE TABLE `appointment_feedback` (
  `feedback_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `ta_id` int(11) NOT NULL,
  `feedback_text` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `admin_id`) VALUES
(1, 'IIM 3121', 'DATABASE MANAGEMENT', 14),
(2, 'IIM 3122', 'OPERATIONS RESEARCH', 15);

-- --------------------------------------------------------

--
-- Table structure for table `course_students`
--

CREATE TABLE `course_students` (
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_students`
--

INSERT INTO `course_students` (`course_id`, `user_id`) VALUES
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(2, 6),
(2, 7),
(2, 8),
(2, 9);

-- --------------------------------------------------------

--
-- Table structure for table `course_tas`
--

CREATE TABLE `course_tas` (
  `course_id` int(11) NOT NULL,
  `ta_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_tas`
--

INSERT INTO `course_tas` (`course_id`, `ta_id`) VALUES
(1, 10),
(1, 11),
(1, 12),
(1, 13);

-- --------------------------------------------------------

--
-- Table structure for table `discussion_posts`
--

CREATE TABLE `discussion_posts` (
  `post_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL COMMENT 'For replies, NULL for original posts',
  `image_path` varchar(255) DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussion_posts`
--

INSERT INTO `discussion_posts` (`post_id`, `course_id`, `user_id`, `title`, `content`, `created_at`, `parent_id`, `image_path`, `is_pinned`) VALUES
(1, 1, 10, '老師我會被當嗎', 'jdbhfjkfbwejkfbewjkfbjew', '2025-06-09 14:34:22', NULL, NULL, 0),
(2, 1, 10, 'Stupid', 'you are TA', '2025-06-09 14:34:40', 1, NULL, 0),
(3, 1, 10, 'Untitled', 'dsgbsebg', '2025-06-09 14:34:51', 1, NULL, 0),
(4, 1, 14, 'i will fire you', 'youre fired nigga', '2025-06-09 14:35:41', 1, NULL, 0),
(5, 1, 14, 'i will fire you', 'youre fired nigga', '2025-06-09 14:36:42', 1, NULL, 0),
(6, 1, 14, 'Untitled', 'sfbdsfb', '2025-06-09 14:36:52', 1, NULL, 0),
(7, 1, 8, 'is this the songjiu poster from last year', 'yes or no', '2025-06-15 08:23:04', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'student'),
(2, 'ta'),
(3, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `ta_schedule`
--

CREATE TABLE `ta_schedule` (
  `schedule_id` int(11) NOT NULL,
  `ta_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `is_booked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ta_schedule`
--

INSERT INTO `ta_schedule` (`schedule_id`, `ta_id`, `course_id`, `date`, `time`, `is_booked`) VALUES
(1, 10, 1, '2025-06-10', '10:00:00', 1),
(2, 10, 1, '2025-06-11', '14:00:00', 1),
(3, 11, 1, '2025-06-10', '11:00:00', 0),
(4, 11, 1, '2025-06-12', '15:00:00', 0),
(5, 12, 1, '2025-06-13', '09:00:00', 0),
(6, 12, 1, '2025-06-13', '13:00:00', 1),
(7, 13, 1, '2025-06-14', '10:00:00', 0),
(8, 13, 1, '2025-06-15', '14:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `full_name`, `password`, `email`) VALUES
(2, 'student', NULL, 'password', 'student@gmail.com'),
(4, 'teacher', NULL, 'password', 'teacher@gmail.com'),
(5, 'ta', NULL, 'password', 'ta@gmail.com'),
(6, 'H34125325', '黃健維', 'password', 'H34125325@gs.ncku.edu.tw'),
(7, 'H34125016', '李展陞', 'password', 'H34125016@gs.ncku.edu.tw'),
(8, 'H34125317', '梁家銓', 'password', 'H34125317@gs.ncku.edu.tw'),
(9, 'H34127018', '黎益福', 'password', 'H34127018@gs.ncku.edu.tw'),
(10, 'R76134068', '劉宇宸', 'password', 'R76134068@gs.ncku.edu.tw'),
(11, 'R76131159', '李芷昀', 'password', 'R76131159@gs.ncku.edu.tw'),
(12, 'R76121162', '林祐君', 'password', 'R76121162@gs.ncku.edu.tw'),
(13, 'R76131167', '郭宸逢', 'password', 'R76131167@gs.ncku.edu.tw'),
(14, 'H3012', '王惠嘉', 'password', 'hcwang@mail.ncku.edu.tw'),
(15, 'H3049', '林仁彥', 'password', 'z11108035@email.ncku.edu.tw');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(2, 1),
(4, 3),
(5, 2),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(10, 2),
(11, 1),
(11, 2),
(12, 1),
(12, 2),
(13, 1),
(13, 2),
(14, 3),
(15, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `announcement_id` (`announcement_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `appointment_feedback`
--
ALTER TABLE `appointment_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `ta_id` (`ta_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `course_students`
--
ALTER TABLE `course_students`
  ADD PRIMARY KEY (`course_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `course_tas`
--
ALTER TABLE `course_tas`
  ADD PRIMARY KEY (`course_id`,`ta_id`),
  ADD KEY `user_id` (`ta_id`);

--
-- Indexes for table `discussion_posts`
--
ALTER TABLE `discussion_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `ta_schedule`
--
ALTER TABLE `ta_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `ta_id` (`ta_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  MODIFY `reaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointment_feedback`
--
ALTER TABLE `appointment_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `discussion_posts`
--
ALTER TABLE `discussion_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ta_schedule`
--
ALTER TABLE `ta_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  ADD CONSTRAINT `announcement_reactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `ta_schedule` (`schedule_id`);

--
-- Constraints for table `appointment_feedback`
--
ALTER TABLE `appointment_feedback`
  ADD CONSTRAINT `appointment_feedback_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`),
  ADD CONSTRAINT `appointment_feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `appointment_feedback_ibfk_3` FOREIGN KEY (`ta_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `course_students`
--
ALTER TABLE `course_students`
  ADD CONSTRAINT `course_students_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `course_students_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `course_tas`
--
ALTER TABLE `course_tas`
  ADD CONSTRAINT `course_tas_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `course_tas_ibfk_2` FOREIGN KEY (`ta_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `discussion_posts`
--
ALTER TABLE `discussion_posts`
  ADD CONSTRAINT `discussion_posts_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `discussion_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `discussion_posts_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `discussion_posts` (`post_id`);

--
-- Constraints for table `ta_schedule`
--
ALTER TABLE `ta_schedule`
  ADD CONSTRAINT `ta_schedule_ibfk_1` FOREIGN KEY (`ta_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `ta_schedule_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
