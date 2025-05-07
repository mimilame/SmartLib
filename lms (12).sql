-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 19, 2025 at 12:04 PM
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
-- Table structure for table `lms_fines`
--

DROP TABLE IF EXISTS `lms_fines`;
CREATE TABLE IF NOT EXISTS `lms_fines` (
  `fines_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `issue_book_id` int NOT NULL,
  `expected_return_date` date NOT NULL,
  `return_date` date NOT NULL,
  `days_late` int NOT NULL,
  `fines_amount` decimal(10,0) NOT NULL,
  `fines_status` enum('Paid','Unpaid') NOT NULL,
  `fines_created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fines_updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fines_id`),
  KEY `user_id` (`user_id`),
  KEY `issue_book_id` (`issue_book_id`),
  KEY `expected_return_date` (`expected_return_date`),
  KEY `return_date` (`return_date`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_fines`
--

INSERT INTO `lms_fines` (`fines_id`, `user_id`, `issue_book_id`, `expected_return_date`, `return_date`, `days_late`, `fines_amount`, `fines_status`, `fines_created_on`, `fines_updated_on`) VALUES
(10, 17, 4, '2025-04-18', '2025-04-19', 1, 5, 'Unpaid', '2025-04-19 11:54:28', '2025-04-19 11:58:31');

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
(1, 17, 4, '2025-04-17', '2025-04-20', '2025-04-18', 'Returned', '2025-04-17 13:10:08', '2025-04-18 14:39:49', 'Missing Pages'),
(2, 18, 16, '2025-04-17', '2025-04-19', '0000-00-00', 'Issued', '2025-04-17 13:11:45', '2025-04-17 13:11:45', 'Good'),
(3, 22, 13, '2025-04-18', '2025-04-19', '2025-04-19', 'Returned', '2025-04-17 13:14:10', '2025-04-19 11:53:35', 'Damaged'),
(4, 19, 17, '2025-04-19', '2025-04-18', '2025-04-19', 'Overdue', '2025-04-17 13:37:18', '2025-04-19 11:59:39', ''),
(5, 17, 6, '2025-04-17', '2025-04-20', '0000-00-00', 'Issued', '2025-04-17 13:37:33', '2025-04-17 13:37:33', 'Good'),
(6, 3, 13, '2025-04-17', '2025-04-19', '0000-00-00', 'Issued', '2025-04-17 13:37:47', '2025-04-17 13:37:47', 'Good');

-- --------------------------------------------------------

--
-- Table structure for table `lms_setting`
--

DROP TABLE IF EXISTS `lms_setting`;
CREATE TABLE IF NOT EXISTS `lms_setting` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `library_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_address` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_contact_number` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_email_address` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_open_hours` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '8am-4pm MON-FRI',
  `library_total_book_issue_day` int NOT NULL,
  `library_one_day_fine` decimal(4,2) NOT NULL,
  `library_issue_total_book_per_user` int NOT NULL,
  `library_currency` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_timezone` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'logo.png',
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_setting`
--

INSERT INTO `lms_setting` (`setting_id`, `library_name`, `library_address`, `library_contact_number`, `library_email_address`, `library_open_hours`, `library_total_book_issue_day`, `library_one_day_fine`, `library_issue_total_book_per_user`, `library_currency`, `library_timezone`, `library_logo`) VALUES
(1, 'SmartLib', 'Poblacion, Curuan', '09657893421', 'wmsu_curuan_lib@gmail.com', '8am-5pm MON-SAT', 10, 10.00, 3, 'PHP', 'Asia/Manila', 'logo.png');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
