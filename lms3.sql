-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 31, 2025 at 09:47 AM
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
-- Table structure for table `lms_admin`
--

DROP TABLE IF EXISTS `lms_admin`;
CREATE TABLE IF NOT EXISTS `lms_admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_email` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `admin_password` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `admin_unique_id` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `role_id` int NOT NULL DEFAULT '1',
  `admin_profile` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'admin.jpg',
  PRIMARY KEY (`admin_id`),
  KEY `fk_lms_admin_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_admin`
--

INSERT INTO `lms_admin` (`admin_id`, `admin_email`, `admin_password`, `admin_unique_id`, `role_id`, `admin_profile`) VALUES
(1, 'roselyn@gmail.com', 'password', 'A87654321', 1, 'admin.jpg'),
(2, 'johnsmith1@gmail.com', 'password', 'A98765432', 1, 'admin.jpg');

--
-- Triggers `lms_admin`
--
DROP TRIGGER IF EXISTS `before_insert_admin`;
DELIMITER $$
CREATE TRIGGER `before_insert_admin` BEFORE INSERT ON `lms_admin` FOR EACH ROW BEGIN
    DECLARE random_digits VARCHAR(8);
    DECLARE id_exists INT DEFAULT 1;
    
    -- Keep generating until we find a unique ID
    WHILE id_exists > 0 DO
        SET random_digits = LPAD(FLOOR(RAND() * 100000000), 8, '0');
        
        -- Check if the ID already exists
        SELECT COUNT(*) INTO id_exists FROM lms_admin WHERE admin_unique_id = CONCAT('A', random_digits);
    END WHILE;

    SET NEW.admin_unique_id = CONCAT('A', random_digits);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lms_author`
--

DROP TABLE IF EXISTS `lms_author`;
CREATE TABLE IF NOT EXISTS `lms_author` (
  `author_id` int NOT NULL AUTO_INCREMENT,
  `author_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `author_status` enum('Enable','Disable') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `author_profile` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'author.jpg',
  `author_created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author_updated_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_author`
--

INSERT INTO `lms_author` (`author_id`, `author_name`, `author_status`, `author_profile`, `author_created_on`, `author_updated_on`) VALUES
(1, 'Cate Blanchett', 'Disable', 'author.png', '2021-11-11 07:45:14', '2021-12-02 03:32:09'),
(2, 'Tom Butler', 'Enable', 'author.png', '2021-11-12 04:48:40', '2025-03-31 09:36:49'),
(3, 'Lynn Beighley', 'Enable', 'author.png', '2021-11-12 04:49:00', '2025-03-31 09:36:49'),
(4, 'Vikram Vaswani', 'Enable', 'author.png', '2021-11-12 04:49:18', '2025-03-31 09:36:49'),
(5, 'Daginn Reiersol', 'Enable', 'author.png', '2021-11-12 04:49:38', '2025-03-31 09:36:49'),
(6, 'Joel Murach', 'Enable', 'author.png', '2021-11-12 04:49:54', '2025-03-31 09:36:49'),
(7, 'Robin Nixon', 'Enable', 'author.png', '2021-11-12 04:50:09', '2025-03-31 09:36:49'),
(8, 'Kevin Tatroe', 'Enable', 'author.png', '2021-11-12 04:50:24', '2025-03-31 09:36:49'),
(9, 'Laura Thompson', 'Enable', 'author.png', '2021-11-12 04:50:42', '2025-03-31 09:36:49'),
(10, 'Brett Shimson', 'Enable', 'author.png', '2021-11-12 04:50:55', '2021-12-01 03:40:04'),
(11, 'Sanjib Sinha', 'Enable', 'author.png', '2021-11-12 04:51:16', '2025-03-31 09:36:49'),
(12, 'Brian Messenlehner', 'Enable', 'author.png', '2021-11-12 04:51:42', '2021-12-02 03:32:57'),
(13, 'Dayle Rees', 'Enable', 'author.png', '2021-11-12 04:52:02', '2025-03-31 09:36:49'),
(14, 'Carlos Buenosvinos', 'Enable', 'author.png', '2021-11-12 04:52:20', '2025-03-31 09:36:49'),
(15, 'Bruce Berke', 'Enable', 'author.png', '2021-11-12 04:52:35', '2021-12-02 03:33:10'),
(16, 'Laura Thomson', 'Enable', 'author.png', '2021-11-17 02:39:36', '2025-03-31 09:36:49'),
(18, 'David Herman', 'Enable', 'author.png', '2021-11-30 06:36:35', '2021-12-01 03:39:05'),
(19, 'Mark Myers', 'Enable', 'author.png', '2021-12-08 10:45:15', '2025-03-31 09:36:49'),
(20, 'Rose', 'Enable', 'author.png', '2025-03-14 22:48:22', '2025-03-14 22:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `lms_book`
--

DROP TABLE IF EXISTS `lms_book`;
CREATE TABLE IF NOT EXISTS `lms_book` (
  `book_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `book_author` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `book_location_rack` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `book_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `book_isbn_number` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `book_no_of_copy` int NOT NULL,
  `book_status` enum('Enable','Disable') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `book_img` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'book_placeholder.png',
  `book_added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `book_updated_on` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`book_id`),
  UNIQUE KEY `book_isbn_number` (`book_isbn_number`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_book`
--

INSERT INTO `lms_book` (`book_id`, `category_id`, `book_author`, `book_location_rack`, `book_name`, `book_isbn_number`, `book_no_of_copy`, `book_status`, `book_img`, `book_added_on`, `book_updated_on`) VALUES
(1, 1, 'Alan Forbes', 'A1', 'The Joy of PHP Programming', '978152279214', 5, 'Enable', 'book_placeholder.png', '2021-11-11 09:32:33', '2025-03-14 07:35:46'),
(2, 1, 'Tom Butler', 'A2', 'PHP and MySQL Novice to Ninja', '852369852123', 5, 'Enable', 'book_placeholder.png', '2021-11-12 04:56:23', '2021-12-28 09:59:06'),
(3, 1, 'Lynn Beighley', 'A3', 'Head First PHP and MySQL', '7539518526963', 5, 'Enable', 'book_placeholder.png', '2021-11-12 04:57:04', '2025-03-31 09:37:07'),
(4, 1, 'Vikram Vaswani', 'A4', 'PHP A Beginners Guide', '74114774147', 5, 'Enable', 'book_placeholder.png', '2021-11-12 04:57:47', '2025-03-31 09:37:07'),
(5, 1, 'Daginn Reiersol', 'A5', 'PHP In Action Objects Design Agility', '85225885258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 04:58:34', '2025-03-31 09:37:07'),
(6, 1, 'Joel Murach', 'A6', 'Murachs PHP and MySQL', '8585858596632', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:00:15', '2025-03-14 11:31:29'),
(7, 1, 'Robin Nixon', 'A8', 'Learning PHP MySQL JavaScript and CSS Creating Dynamic Websites', '753852963258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:01:10', '2021-11-12 05:02:16'),
(8, 1, 'Kevin Tatroe', 'A10', 'Programming PHP Creating Dynamic Web Pages', '969335785842', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:01:57', '2025-03-31 09:37:07'),
(9, 1, 'Bruce Berke', 'A1', 'PHP Programming and MySQL Database for Web Development', '963369852258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:02:48', '2021-11-17 02:58:27'),
(10, 1, 'Brett McLaughlin', 'A2', 'PHP MySQL The Missing Manual', '85478569856', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:03:51', '2021-11-14 09:07:04'),
(11, 1, 'Sanjib Sinha', 'A3', 'Beginning Laravel A beginners guide', '856325774562', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:04:39', '2025-03-31 09:37:07'),
(12, 1, 'Brian Messenlehner', 'A3', 'Building Web Apps with WordPress', '96325741258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:05:18', '2025-03-31 09:37:07'),
(13, 1, 'Dayle Rees', 'A5', 'The Laravel Framework Version 5 For Beginners', '336985696363', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:05:56', '2025-03-31 09:37:07'),
(14, 1, 'Carlos Buenosvinos', 'A6', 'Domain Driven Design in PHP', '852258963475', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:06:35', '2021-12-11 02:36:01'),
(15, 1, 'Bruce Berke', 'A7', 'Learn PHP The Complete Beginners Guide to Learn PHP Programming', '744785963520', 5, 'Enable', 'book_placeholder.png', '2021-11-12 05:07:27', '2021-12-09 10:37:14'),
(16, 1, 'Laura Thompson', 'A2', 'PHP and MySQL Web Development', '753951852123', 1, 'Enable', 'book_placeholder.png', '2021-11-17 02:43:19', '2021-11-17 03:03:05'),
(17, 1, 'Mark Myers', 'A11', 'A Smarter Way to Learn JavaScript', '852369753951', 1, 'Enable', 'book_placeholder.png', '2021-12-08 10:48:11', '2021-12-28 10:03:30'),
(18, 1, 'Carlos Buenosvinos', 'A3', 'Happy', '64739929873', 5, 'Enable', 'book_placeholder.png', '2025-03-14 12:57:42', '2025-03-31 09:37:07'),
(19, 6, 'Bruce Berke', '10', 'Rizal 101', '09844888484', 5, 'Enable', '\'book_placeholder.png\'', '2025-03-30 15:53:25', '2025-03-30 15:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `lms_book_author`
--

DROP TABLE IF EXISTS `lms_book_author`;
CREATE TABLE IF NOT EXISTS `lms_book_author` (
  `book_id` int NOT NULL,
  `author_id` int NOT NULL,
  PRIMARY KEY (`book_id`,`author_id`),
  KEY `fk_book_author_author` (`author_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lms_category`
--

DROP TABLE IF EXISTS `lms_category`;
CREATE TABLE IF NOT EXISTS `lms_category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `category_status` enum('Enable','Disable') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `category_created_on` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `category_updated_on` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_category`
--

INSERT INTO `lms_category` (`category_id`, `category_name`, `category_status`, `category_created_on`, `category_updated_on`) VALUES
(1, 'Programming', 'Enable', '2021-11-10 19:02:37', '2021-11-27 11:56:18'),
(2, 'Databases', 'Enable', '2021-11-17 10:36:53', '2025-03-19 11:41:54'),
(3, 'Web Design', 'Enable', '2021-11-26 16:14:18', '2021-11-27 12:28:03'),
(4, 'Web Development', 'Enable', '2021-11-26 16:15:38', '2021-11-27 12:28:11'),
(5, 'Sciences', 'Enable', '2025-03-14 22:18:21', '2025-03-30 23:44:32'),
(6, 'Filipino', 'Enable', '2025-03-15 06:41:24', '2025-03-31 17:37:31'),
(7, 'Thesis', 'Disable', '2025-03-19 11:43:36', '2025-03-31 17:37:31'),
(8, 'Comics', 'Disable', '2025-03-20 11:13:40', '2025-03-20 15:40:38'),
(9, 'Social Sciences', 'Enable', '2025-03-30 23:54:26', '2025-03-30 23:54:45');

-- --------------------------------------------------------

--
-- Table structure for table `lms_issue_book`
--

DROP TABLE IF EXISTS `lms_issue_book`;
CREATE TABLE IF NOT EXISTS `lms_issue_book` (
  `issue_book_id` int NOT NULL,
  `book_id` int NOT NULL,
  `user_id` int NOT NULL,
  `issue_date` date NOT NULL,
  `expected_return_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `book_fines` int DEFAULT NULL,
  `issue_book_status` enum('Issued','Returned','Overdue','Lost') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `issued_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `issue_updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`issue_book_id`),
  KEY `fk_issue_book` (`book_id`),
  KEY `fk_issue_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_issue_book`
--

INSERT INTO `lms_issue_book` (`issue_book_id`, `book_id`, `user_id`, `issue_date`, `expected_return_date`, `return_date`, `book_fines`, `issue_book_status`, `issued_on`, `issue_updated_on`) VALUES
(9, 1, 3, '2021-12-28', '2022-01-07', '2022-01-03', 0, 'Returned', '2025-03-30 06:00:16', '2025-03-30 07:54:41'),
(10, 2, 4, '2025-03-14', '2025-03-24', '2025-03-14', 0, 'Returned', '2025-03-30 06:00:16', '2025-03-30 07:54:53'),
(14, 1, 17, '2025-03-30', '2025-04-02', '2025-04-01', 0, 'Returned', '2025-03-30 05:23:52', '2025-03-31 09:08:53'),
(15, 1, 16, '2025-03-30', '2025-04-03', '2025-04-02', 0, 'Returned', '2025-03-30 05:29:44', '2025-03-30 05:48:39'),
(16, 2, 6, '2025-03-31', '2025-04-03', '2025-04-05', 0, 'Overdue', '2025-03-30 05:38:05', '2025-03-30 05:51:13'),
(17, 2, 11, '2025-03-31', '2025-04-02', '2025-04-08', 0, 'Lost', '2025-03-30 05:50:28', '2025-03-30 05:52:25'),
(18, 19, 17, '2025-03-31', '2025-04-02', NULL, 0, 'Lost', '2025-03-30 05:54:59', '2025-03-30 08:38:53'),
(19, 19, 17, '2025-03-31', '2025-04-02', '2025-04-03', 0, 'Returned', '2025-03-30 05:55:04', '2025-03-30 06:10:05'),
(20, 1, 13, '2025-03-31', '2025-04-02', NULL, 0, 'Issued', '2025-03-30 06:01:21', '2025-03-31 09:08:53');

-- --------------------------------------------------------

--
-- Table structure for table `lms_librarian`
--

DROP TABLE IF EXISTS `lms_librarian`;
CREATE TABLE IF NOT EXISTS `lms_librarian` (
  `librarian_id` int NOT NULL AUTO_INCREMENT,
  `librarian_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `librarian_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `librarian_contact_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `librarian_profile` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'librarian.jpg',
  `librarian_email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `librarian_password` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_id` int NOT NULL DEFAULT '2',
  `librarian_verification_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `librarian_verification_status` enum('No','Yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `librarian_unique_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `librarian_status` enum('Enable','Disable') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lib_created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lib_updated_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`librarian_id`),
  UNIQUE KEY `librarian_unique_id` (`librarian_unique_id`),
  KEY `fk_lms_librarian_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_librarian`
--

INSERT INTO `lms_librarian` (`librarian_id`, `librarian_name`, `librarian_address`, `librarian_contact_no`, `librarian_profile`, `librarian_email`, `librarian_password`, `role_id`, `librarian_verification_code`, `librarian_verification_status`, `librarian_unique_id`, `librarian_status`, `lib_created_on`, `lib_updated_on`) VALUES
(1, 'Honey A. Atilano', 'Curuan, Zamboanga City', '093784757387', 'librarian.jpg', 'honey@gmail.com', '$2y$10$GcyPcJF6GhEr7XV2Eb2hkeW', 2, NULL, 'Yes', 'L12345678', 'Enable', '2025-03-20 10:22:31', '2025-03-28 23:55:40'),
(2, 'Roselyn Tarroza', NULL, '09737893974', NULL, 'rose@gmail.com', '$2y$10$q6tGKPQyPBsVbag73Lk0guG', 2, NULL, NULL, 'L23456789', 'Enable', '2025-03-20 10:22:31', '2025-03-20 13:27:06'),
(3, 'May Natividad', NULL, '098384848', NULL, 'natividad@gmail.com', '$2y$10$.qQMDpxYB2piVLgQH0/pVuX', 2, NULL, NULL, 'L34567890', 'Enable', '2025-03-20 10:22:31', '2025-03-20 10:22:31'),
(4, 'Jeric Palca', NULL, '098349934994', NULL, 'palca@gmail.com', '$2y$10$DQMv88DKRBkm6Xs2dKJh9.K', 2, NULL, NULL, NULL, 'Disable', '2025-03-20 10:22:31', '2025-03-20 13:26:45'),
(5, 'Kenneth Cruz', NULL, '0983848748444', NULL, 'cruz@gmail.com', '$2y$10$2AivLF.giwcsRjuVTxH2Y.m', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 10:22:31', '2025-03-20 10:22:31'),
(6, 'Daisy Lamorinas', NULL, '0938923898389', NULL, 'lamorinas@gmail.com', '$2y$10$VoN62S5FG75p6KcBDpQIxe3', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 10:22:31', '2025-03-20 10:22:31'),
(7, 'Noel Comeros', NULL, '099343284893', NULL, 'comeros@gmail.com', '$2y$10$a0zyCTSEjULQhvLOggEC.Oc', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 10:22:31', '2025-03-20 10:22:31'),
(8, 'Steffi Wong', NULL, '092773782882', NULL, 'wong@gmail.com', '$2y$10$xKQkTouz2Eg5FlznHw7ffer', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 08:54:00', '2025-03-20 11:24:00'),
(9, 'sample', NULL, '09546213578', NULL, 'sample.test@gmail.com', '$2y$10$thKZI2TgH7QP4IdVKTvEAux', 2, NULL, NULL, 'L52907001', 'Enable', '2025-03-26 21:25:17', '2025-03-27 00:43:55');

--
-- Triggers `lms_librarian`
--
DROP TRIGGER IF EXISTS `before_insert_librarian`;
DELIMITER $$
CREATE TRIGGER `before_insert_librarian` BEFORE INSERT ON `lms_librarian` FOR EACH ROW BEGIN
    DECLARE random_digits VARCHAR(8);
    DECLARE id_exists INT DEFAULT 1;
    
    -- Keep generating until we find a unique ID
    WHILE id_exists > 0 DO
        SET random_digits = LPAD(FLOOR(RAND() * 100000000), 8, '0');
        
        -- Check if the ID already exists
        SELECT COUNT(*) INTO id_exists FROM lms_librarian WHERE librarian_unique_id = CONCAT('L', random_digits);
    END WHILE;

    SET NEW.librarian_unique_id = CONCAT('L', random_digits);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lms_location_rack`
--

DROP TABLE IF EXISTS `lms_location_rack`;
CREATE TABLE IF NOT EXISTS `lms_location_rack` (
  `location_rack_id` int NOT NULL AUTO_INCREMENT,
  `location_rack_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `location_rack_status` enum('Enable','Disable') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `rack_created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rack_updated_on` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_rack_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_location_rack`
--

INSERT INTO `lms_location_rack` (`location_rack_id`, `location_rack_name`, `location_rack_status`, `rack_created_on`, `rack_updated_on`) VALUES
(1, 'A1', 'Enable', '2021-11-11 08:16:27', '2021-12-07 02:02:00'),
(2, 'A2', 'Enable', '2021-11-12 04:53:49', '2025-03-31 09:38:47'),
(3, 'A3', 'Enable', '2021-11-12 04:53:57', '2025-03-31 09:38:47'),
(4, 'A4', 'Enable', '2021-11-12 04:54:06', '2025-03-31 09:38:47'),
(5, 'A5', 'Enable', '2021-11-12 04:54:14', '2025-03-31 09:38:47'),
(6, 'A6', 'Enable', '2021-11-12 04:54:22', '2025-03-31 09:38:47'),
(7, 'A7', 'Enable', '2021-11-12 04:54:30', '2025-03-31 09:38:47'),
(8, 'A8', 'Enable', '2021-11-12 04:54:38', '2025-03-31 09:38:47'),
(9, 'Row 3', 'Enable', '2021-11-12 04:54:52', '2025-03-14 20:26:32'),
(10, 'A10', 'Enable', '2021-11-12 04:55:02', '2021-12-04 05:03:28'),
(11, 'A11', 'Enable', '2021-12-03 10:20:16', '2021-12-04 04:45:09'),
(12, 'hy', 'Enable', '2025-03-14 14:21:57', '2025-03-31 09:38:47'),
(13, 'Row 1', 'Enable', '2025-03-14 20:23:58', '2025-03-31 09:38:47');

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
(1, 'SmartLib', 'Poblacion, Curuan', '09657893421', 'wmsu_curuan_lib@gmail.com', '8am-5pm MON-SAT', 10, 1.00, 3, 'PHP', 'Asia/Calcutta', 'logo.png');

-- --------------------------------------------------------

--
-- Table structure for table `lms_user`
--

DROP TABLE IF EXISTS `lms_user`;
CREATE TABLE IF NOT EXISTS `lms_user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_address` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_contact_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_profile` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'user.jpg',
  `user_email` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_password` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `role_id` int NOT NULL DEFAULT '5',
  `user_verification_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_verification_status` enum('No','Yes') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_unique_id` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_status` enum('Enable','Disable') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_unique_id` (`user_unique_id`),
  KEY `fk_user_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_user`
--

INSERT INTO `lms_user` (`user_id`, `user_name`, `user_address`, `user_contact_no`, `user_profile`, `user_email`, `user_password`, `role_id`, `user_verification_code`, `user_verification_status`, `user_unique_id`, `user_status`, `user_created_on`, `user_updated_on`) VALUES
(3, 'Paul Black', '4016 Goldie Lane Cincinnati, OH 45202', '7539518520', '1636699900-2617.jpg', 'paulblake@gmail.com', '$2y$10$LMswUk7S85FbsUVITtyNieD', 5, 'b190bcd6e3b29674db036670cf122724', 'Yes', 'U82514529', 'Enable', '2021-11-12 04:21:40', '2025-03-27 01:05:50'),
(4, 'Aaron Lawler', '1616 Broadway Avenue Chattanooga, TN 37421', '8569856321', '1636905360-32007.jpg', 'aaronlawler@live.com', 'password', 3, 'add84abb895484d12344316eccb78a62', 'Yes', 'F37570190', 'Enable', '2021-11-12 08:39:20', '2025-03-26 05:36:08'),
(5, 'Kathleen Forrest', '4545 Limer Street Greensboro, GA 30642', '85214796930', '1637041684-15131.jpg', 'kathleen@hotmail.com', 'password', 4, '7013df5205011ffcb99ea57902c17369', 'Yes', 'S24567871', 'Enable', '2021-11-16 03:18:04', '2025-03-26 05:53:20'),
(6, 'Carol Maney', '2703 Deer Haven Drive Greenville, SC 29607', '8521479630', '1637126571-21753.jpg', 'web-tutorial1@programmer.net', 'password', 5, 'a6c2623984d590239244f8695df3a30b', 'Yes', 'U52357788', 'Enable', '2021-11-17 02:52:51', '2025-03-26 05:36:25'),
(10, 'Kevin Peterson', '1889 Single Street Waltham, MA 02154', '8523698520', '1639658464-10192.jpg', 'web-tutorial@programmer.net', 'password123', 5, '337ea20da40326d134fe5eca3fb03464', 'Yes', 'U59564819', 'Enable', '2021-12-14 04:56:29', '2025-03-26 05:36:28'),
(11, 'Faye Lacsi', '', '09823830938478', 'user.jpg', 'lacsi@gmail.com', '$2y$10$Zj/oONYmk9iSH8puqvCNsOu', 5, '', 'No', 'U17845470', 'Enable', '2025-03-20 10:10:18', '2025-03-31 09:43:02'),
(12, 'Jake Bruce', '', '09736788383', 'user.jpg', 'bruce@gmail.com', '$2y$10$hkYDyWpXBwbnn8BvbrssV.e', 5, '', '', 'U41683769', 'Enable', '2025-03-20 06:02:39', '2025-03-31 09:43:02'),
(13, 'Derriel', '', '093789383837737', 'user.jpg', 'derriel@gmail.com', '$2y$10$n36NAVxz2eQUXc5Focqwf./', 5, '', '', 'U54882437', 'Enable', '2025-03-20 06:03:24', '2025-03-31 09:43:02'),
(14, 'gina', '', '0965688765', 'user.jpg', 'gina@gmail.com', '$2y$10$NTvHtfmzGn6b5IbyHRYoZu6', 5, '', '', 'U49360880', 'Enable', '2025-03-20 06:04:40', '2025-03-31 09:43:02'),
(15, 'Kate Pink', '', '0973787366', 'user.jpg', 'pink@gmail.com', '$2y$10$YSxZru8YualfNhSEJDubV.Q', 5, '', 'No', 'U82157096', 'Enable', '2025-03-20 10:41:02', '2025-03-31 09:43:02'),
(16, 'Barbie Blue', '', '0973837663', 'user.jpg', 'barbie@gmail.com', '$2y$10$MrATmlOi475YSzubS0QvyOp', 5, '', 'No', 'U62702842', 'Enable', '2025-03-20 10:46:05', '2025-03-31 09:43:02'),
(17, 'April Manalo', '', '09974749474', 'user.jpg', 'manalo@gmail.com', '$2y$10$mzjvLfb9TI8nILqq49cSxuZ', 5, '', 'No', 'U67042925', 'Enable', '2025-03-20 10:58:39', '2025-03-31 09:43:02'),
(18, 'gina thong', 'curuan', '0987544613', '1743038547-737356096.jpg', 'teff.wong@gmail.com', '$2y$10$VemqjTGKtQa2qrU5eviwH.t', 5, 'f7ce5b2b89ccbe2430fb8cd54b9426b7', 'No', 'U47091166', 'Enable', '2025-03-26 22:52:27', '2025-03-27 01:22:27');

--
-- Triggers `lms_user`
--
DROP TRIGGER IF EXISTS `before_insert_user`;
DELIMITER $$
CREATE TRIGGER `before_insert_user` BEFORE INSERT ON `lms_user` FOR EACH ROW BEGIN
    DECLARE random_digits VARCHAR(8);
    DECLARE id_exists INT DEFAULT 1;
    DECLARE role_prefix CHAR(1);

    -- Get the role prefix based on role_id
    CASE NEW.role_id
        WHEN 3 THEN SET role_prefix = 'F'; -- Faculty
        WHEN 4 THEN SET role_prefix = 'S'; -- Student
        ELSE SET role_prefix = 'U';        -- Default User
    END CASE;

    -- Generate unique ID
    WHILE id_exists > 0 DO
        SET random_digits = LPAD(FLOOR(RAND() * 100000000), 8, '0');
        SET NEW.user_unique_id = CONCAT(role_prefix, random_digits);
        
        -- Check if this unique ID already exists
        SELECT COUNT(*) INTO id_exists 
        FROM lms_user 
        WHERE user_unique_id = NEW.user_unique_id;
    END WHILE;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`role_id`, `role_name`, `role_description`) VALUES
(1, 'admin', 'System administrator with full access'),
(2, 'librarian', 'Library staff with management privileges'),
(3, 'faculty', 'Teaching staff with extended borrowing rights'),
(4, 'student', 'Regular student with standard library access'),
(5, 'visitor', 'External user with limited access');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lms_admin`
--
ALTER TABLE `lms_admin`
  ADD CONSTRAINT `fk_lms_admin_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `lms_book`
--
ALTER TABLE `lms_book`
  ADD CONSTRAINT `fk_book_category` FOREIGN KEY (`category_id`) REFERENCES `lms_category` (`category_id`);

--
-- Constraints for table `lms_issue_book`
--
ALTER TABLE `lms_issue_book`
  ADD CONSTRAINT `fk_issue_book` FOREIGN KEY (`book_id`) REFERENCES `lms_book` (`book_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_issue_user` FOREIGN KEY (`user_id`) REFERENCES `lms_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lms_librarian`
--
ALTER TABLE `lms_librarian`
  ADD CONSTRAINT `fk_lms_librarian_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `lms_user`
--
ALTER TABLE `lms_user`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
