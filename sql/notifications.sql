-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2024 at 02:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Drop the existing `notifications` table if it exists
DROP TABLE IF EXISTS `notifications`;

-- Recreate the `notifications` table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0, -- Default value is unread (0)
  `activity_type` varchar(99) NOT NULL,
  `activity_title` varchar(99) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`), -- Index for faster lookup by student ID
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure the AUTO_INCREMENT starts fresh
ALTER TABLE `notifications` AUTO_INCREMENT = 1;

-- Insert sample data (optional)
INSERT INTO `notifications` (`student_id`, `is_read`, `activity_type`, `activity_title`) VALUES
(1, 0, 'Assignment', 'Complete your math assignment'),
(2, 0, 'Quiz', 'Upcoming quiz on science topics'),
(3, 0, 'Exam', 'Final exam scheduled next week');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
