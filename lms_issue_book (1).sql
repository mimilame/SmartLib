-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 17, 2025 at 01:40 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `lms_issue_book`
--

DROP TABLE IF EXISTS `lms_issue_book`;
CREATE TABLE IF NOT EXISTS `lms_issue_book` (
  `issue_book_id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL,
  `user_id` int NOT NULL,
  `issue_date` date NOT NULL,
  `expected_return_date` date NOT NULL,
  `return_date` date NOT NULL,
  `issue_book_status` enum('Issued','Returned','Overdue','Lost') NOT NULL,
  `issued_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `issue_updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `book_condition` enum('Good','Damaged','Missing Pages','Water Damaged','Binding Loose') NOT NULL,
  PRIMARY KEY (`issue_book_id`),
  KEY `book_id` (`book_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_issue_book`
--

INSERT INTO `lms_issue_book` (`issue_book_id`, `book_id`, `user_id`, `issue_date`, `expected_return_date`, `return_date`, `issue_book_status`, `issued_on`, `issue_updated_on`, `book_condition`) VALUES
(1, 17, 4, '2025-04-17', '2025-04-20', '2025-04-17', 'Returned', '2025-04-17 13:10:08', '2025-04-17 13:39:15', 'Missing Pages'),
(2, 18, 16, '2025-04-17', '2025-04-19', '0000-00-00', 'Issued', '2025-04-17 13:11:45', '2025-04-17 13:11:45', 'Good'),
(3, 22, 13, '2025-04-17', '2025-04-20', '0000-00-00', 'Issued', '2025-04-17 13:14:10', '2025-04-17 13:38:29', ''),
(4, 19, 17, '2025-04-17', '2025-04-18', '0000-00-00', 'Issued', '2025-04-17 13:37:18', '2025-04-17 13:37:18', 'Good'),
(5, 17, 6, '2025-04-17', '2025-04-20', '0000-00-00', 'Issued', '2025-04-17 13:37:33', '2025-04-17 13:37:33', 'Good'),
(6, 3, 13, '2025-04-17', '2025-04-19', '0000-00-00', 'Issued', '2025-04-17 13:37:47', '2025-04-17 13:37:47', 'Good');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
