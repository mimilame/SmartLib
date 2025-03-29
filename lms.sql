-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2025 at 01:02 AM
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
-- Database: `lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `lms_admin`
--

CREATE TABLE `lms_admin` (
  `admin_id` int(11) NOT NULL,
  `admin_email` varchar(200) NOT NULL,
  `admin_password` varchar(100) NOT NULL,
  `admin_unique_id` varchar(20) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `admin_profile` varchar(100) NOT NULL DEFAULT 'admin.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_admin`
--

INSERT INTO `lms_admin` (`admin_id`, `admin_email`, `admin_password`, `admin_unique_id`, `role_id`, `admin_profile`) VALUES
(1, 'roselyn@gmail.com', 'password', 'A87654321', 1, 'admin.jpg'),
(2, 'johnsmith1@gmail.com', 'password', 'A98765432', 1, 'admin.jpg');

--
-- Triggers `lms_admin`
--
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

CREATE TABLE `lms_author` (
  `author_id` int(11) NOT NULL,
  `author_name` varchar(200) NOT NULL,
  `author_status` enum('Enable','Disable') NOT NULL,
  `author_profile` varchar(100) NOT NULL DEFAULT 'author.jpg',
  `author_created_on` varchar(30) NOT NULL,
  `author_updated_on` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_author`
--

INSERT INTO `lms_author` (`author_id`, `author_name`, `author_status`, `author_profile`, `author_created_on`, `author_updated_on`) VALUES
(1, 'Cate Blanchett', 'Enable', 'author.png', '2021-11-11 15:45:14', '2021-12-02 11:32:09'),
(2, 'Tom Butler', 'Enable', 'author.png', '2021-11-12 12:48:40', ''),
(3, 'Lynn Beighley', 'Enable', 'author.png', '2021-11-12 12:49:00', ''),
(4, 'Vikram Vaswani', 'Enable', 'author.png', '2021-11-12 12:49:18', ''),
(5, 'Daginn Reiersol', 'Enable', 'author.png', '2021-11-12 12:49:38', ''),
(6, 'Joel Murach', 'Enable', 'author.png', '2021-11-12 12:49:54', ''),
(7, 'Robin Nixon', 'Enable', 'author.png', '2021-11-12 12:50:09', ''),
(8, 'Kevin Tatroe', 'Enable', 'author.png', '2021-11-12 12:50:24', ''),
(9, 'Laura Thompson', 'Enable', 'author.png', '2021-11-12 12:50:42', ''),
(10, 'Brett Shimson', 'Enable', 'author.png', '2021-11-12 12:50:55', '2021-12-01 11:40:04'),
(11, 'Sanjib Sinha', 'Enable', 'author.png', '2021-11-12 12:51:16', ''),
(12, 'Brian Messenlehner', 'Enable', 'author.png', '2021-11-12 12:51:42', '2021-12-02 11:32:57'),
(13, 'Dayle Rees', 'Enable', 'author.png', '2021-11-12 12:52:02', ''),
(14, 'Carlos Buenosvinos', 'Enable', 'author.png', '2021-11-12 12:52:20', ''),
(15, 'Bruce Berke', 'Enable', 'author.png', '2021-11-12 12:52:35', '2021-12-02 11:33:10'),
(16, 'Laura Thomson', 'Enable', 'author.png', '2021-11-17 10:39:36', ''),
(18, 'David Herman', 'Enable', 'author.png', '2021-11-30 14:36:35', '2021-12-01 11:39:05'),
(19, 'Mark Myers', 'Enable', 'author.png', '2021-12-08 18:45:15', ''),
(20, 'Rose', 'Enable', 'author.png', '2025-03-15 06:48:22', '2025-03-15 06:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `lms_book`
--

CREATE TABLE `lms_book` (
  `book_id` int(11) NOT NULL,
  `book_category` varchar(200) NOT NULL,
  `book_author` varchar(200) NOT NULL,
  `book_location_rack` varchar(100) NOT NULL,
  `book_name` text NOT NULL,
  `book_isbn_number` varchar(30) NOT NULL,
  `book_no_of_copy` int(5) NOT NULL,
  `book_status` enum('Enable','Disable') NOT NULL,
  `book_img` varchar(100) NOT NULL DEFAULT '''book_placeholder.png''',
  `book_added_on` varchar(30) NOT NULL,
  `book_updated_on` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_book`
--

INSERT INTO `lms_book` (`book_id`, `book_category`, `book_author`, `book_location_rack`, `book_name`, `book_isbn_number`, `book_no_of_copy`, `book_status`, `book_img`, `book_added_on`, `book_updated_on`) VALUES
(1, 'Programming Skill', 'Alan Forbes', 'A1', 'The Joy of PHP Programming', '978152279214', 5, 'Enable', 'book_placeholder.png', '2021-11-11 17:32:33', '2025-03-14 15:35:46'),
(2, 'Programming Skill', 'Tom Butler', 'A2', 'PHP and MySQL Novice to Ninja', '852369852123', 5, 'Enable', 'book_placeholder.png', '2021-11-12 12:56:23', '2021-12-28 17:59:06'),
(3, 'Programming Skill', 'Lynn Beighley', 'A3', 'Head First PHP and MySQL', '7539518526963', 5, 'Enable', 'book_placeholder.png', '2021-11-12 12:57:04', ''),
(4, 'Programming Skill', 'Vikram Vaswani', 'A4', 'PHP A Beginners Guide', '74114774147', 5, 'Enable', 'book_placeholder.png', '2021-11-12 12:57:47', ''),
(5, 'Programming Skill', 'Daginn Reiersol', 'A5', 'PHP In Action Objects Design Agility', '85225885258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 12:58:34', ''),
(6, 'Programming Skill', 'Joel Murach', 'A6', 'Murachs PHP and MySQL', '8585858596632', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:00:15', '2025-03-14 19:31:29'),
(7, 'Programming Skill', 'Robin Nixon', 'A8', 'Learning PHP MySQL JavaScript and CSS Creating Dynamic Websites', '753852963258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:01:10', '2021-11-12 13:02:16'),
(8, 'Programming Skill', 'Kevin Tatroe', 'A10', 'Programming PHP Creating Dynamic Web Pages', '969335785842', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:01:57', ''),
(9, 'Programming Skill', 'Bruce Berke', 'A1', 'PHP Programming and MySQL Database for Web Development', '963369852258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:02:48', '2021-11-17 10:58:27'),
(10, 'Programming Skill', 'Brett McLaughlin', 'A2', 'PHP MySQL The Missing Manual', '85478569856', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:03:51', '2021-11-14 17:07:04'),
(11, 'Programming Skill', 'Sanjib Sinha', 'A3', 'Beginning Laravel A beginners guide', '856325774562', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:04:39', ''),
(12, 'Programming Skill', 'Brian Messenlehner', 'A3', 'Building Web Apps with WordPress', '96325741258', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:05:18', ''),
(13, 'Programming Skill', 'Dayle Rees', 'A5', 'The Laravel Framework Version 5 For Beginners', '336985696363', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:05:56', ''),
(14, 'Programming Skill', 'Carlos Buenosvinos', 'A6', 'Domain Driven Design in PHP', '852258963475', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:06:35', '2021-12-11 10:36:01'),
(15, 'Programming', 'Bruce Berke', 'A7', 'Learn PHP The Complete Beginners Guide to Learn PHP Programming', '744785963520', 5, 'Enable', 'book_placeholder.png', '2021-11-12 13:07:27', '2021-12-09 18:37:14'),
(16, 'Database Management', 'Laura Thompson', 'A2', 'PHP and MySQL Web Development', '753951852123', 1, 'Enable', 'book_placeholder.png', '2021-11-17 10:43:19', '2021-11-17 11:03:05'),
(17, 'Web Development', 'Mark Myers', 'A11', 'A Smarter Way to Learn JavaScript', '852369753951', 1, 'Enable', 'book_placeholder.png', '2021-12-08 18:48:11', '2021-12-28 18:03:30'),
(18, 'Web Design', 'Carlos Buenosvinos', 'A3', 'Happy', '64739929873', 5, 'Enable', 'book_placeholder.png', '2025-03-14 20:57:42', '');

-- --------------------------------------------------------

--
-- Table structure for table `lms_category`
--

CREATE TABLE `lms_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(200) NOT NULL,
  `category_status` enum('Enable','Disable') NOT NULL,
  `category_created_on` varchar(30) NOT NULL,
  `category_updated_on` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_category`
--

INSERT INTO `lms_category` (`category_id`, `category_name`, `category_status`, `category_created_on`, `category_updated_on`) VALUES
(1, 'Programming', 'Enable', '2021-11-10 19:02:37', '2021-11-27 11:56:18'),
(2, 'Databases', 'Enable', '2021-11-17 10:36:53', '2025-03-19 11:41:54'),
(3, 'Web Design', 'Enable', '2021-11-26 16:14:18', '2021-11-27 12:28:03'),
(4, 'Web Development', 'Enable', '2021-11-26 16:15:38', '2021-11-27 12:28:11'),
(5, 'Science', 'Enable', '2025-03-14 22:18:21', ''),
(6, 'Filipino', 'Enable', '2025-03-15 06:41:24', ''),
(7, 'Thesis', 'Enable', '2025-03-19 11:43:36', ''),
(8, 'Comics', 'Disable', '2025-03-20 11:13:40', '2025-03-20 15:40:38');

-- --------------------------------------------------------

--
-- Table structure for table `lms_issue_book`
--

CREATE TABLE `lms_issue_book` (
  `issue_book_id` int(11) NOT NULL,
  `book_id` varchar(30) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `issue_date_time` varchar(30) NOT NULL,
  `expected_return_date` varchar(30) NOT NULL,
  `return_date_time` varchar(30) NOT NULL,
  `book_fines` varchar(30) NOT NULL,
  `book_issue_status` enum('Issue','Return','Not Return') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_issue_book`
--

INSERT INTO `lms_issue_book` (`issue_book_id`, `book_id`, `user_id`, `issue_date_time`, `expected_return_date`, `return_date_time`, `book_fines`, `book_issue_status`) VALUES
(4, '96325741258', 'F37570190', '2021-11-13 15:57:29', '2021-11-23 15:57:29', '2021-11-14 16:51:42', '0', 'Return'),
(5, '856325774562', 'S24567871', '2021-11-14 17:04:13', '2021-11-24 17:04:13', '2021-11-14 17:05:47', '0', 'Return'),
(6, '856325774562', 'U59564819', '2021-11-14 17:07:04', '2021-11-24 17:07:04', '2021-11-14 17:07:55', '0', 'Return'),
(7, '856325774562', 'U52357788', '2021-11-17 11:03:04', '2021-11-27 11:03:04', '2021-11-17 11:05:29', '0', 'Return'),
(8, '96325741258', 'S24567871', '2021-12-28 17:59:06', '2022-01-07 17:59:06', '2022-01-03 12:44:15', '0', 'Return'),
(9, '852369852123', 'S24567871', '2021-12-28 18:03:30', '2022-01-07 18:03:30', '2022-01-03 12:43:28', '0', 'Return'),
(10, '852369852123', 'F37570190', '2025-03-14 15:35:46', '2025-03-24 15:35:46', '2025-03-14 16:42:21', '0', 'Return'),
(11, '753951852123', 'U59564819', '2025-03-14 16:23:03', '2025-03-24 16:23:03', '2025-03-14 16:42:37', '0', 'Return'),
(12, '753951852123', 'F37570190', '2025-03-14 16:48:59', '2025-03-24 16:48:59', '2025-03-14 16:49:26', '0', 'Return'),
(13, '753951852123', 'F37570190', '2025-03-14 19:31:29', '2025-03-24 19:31:29', '2025-03-15 04:58:19', '50', 'Return');

-- --------------------------------------------------------

--
-- Table structure for table `lms_librarian`
--

CREATE TABLE `lms_librarian` (
  `librarian_id` int(11) NOT NULL,
  `librarian_name` varchar(200) DEFAULT NULL,
  `librarian_address` text DEFAULT NULL,
  `librarian_contact_no` varchar(30) DEFAULT NULL,
  `librarian_profile` varchar(100) DEFAULT 'librarian.jpg',
  `librarian_email` varchar(200) DEFAULT NULL,
  `librarian_password` varchar(30) DEFAULT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `librarian_verification_code` varchar(100) DEFAULT NULL,
  `librarian_verification_status` enum('No','Yes') DEFAULT NULL,
  `librarian_unique_id` varchar(30) DEFAULT NULL,
  `librarian_status` enum('Enable','Disable') DEFAULT NULL,
  `lib_created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `lib_updated_on` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `lms_location_rack` (
  `location_rack_id` int(11) NOT NULL,
  `location_rack_name` varchar(200) NOT NULL,
  `location_rack_status` enum('Enable','Disable') NOT NULL,
  `location_rack_created_on` varchar(30) NOT NULL,
  `location_rack_updated_on` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_location_rack`
--

INSERT INTO `lms_location_rack` (`location_rack_id`, `location_rack_name`, `location_rack_status`, `location_rack_created_on`, `location_rack_updated_on`) VALUES
(1, 'A1', 'Enable', '2021-11-11 16:16:27', '2021-12-07 10:02:00'),
(2, 'A2', 'Enable', '2021-11-12 12:53:49', ''),
(3, 'A3', 'Enable', '2021-11-12 12:53:57', ''),
(4, 'A4', 'Enable', '2021-11-12 12:54:06', ''),
(5, 'A5', 'Enable', '2021-11-12 12:54:14', ''),
(6, 'A6', 'Enable', '2021-11-12 12:54:22', ''),
(7, 'A7', 'Enable', '2021-11-12 12:54:30', ''),
(8, 'A8', 'Enable', '2021-11-12 12:54:38', ''),
(9, 'Row 3', 'Enable', '2021-11-12 12:54:52', '2025-03-15 04:26:32'),
(10, 'A10', 'Enable', '2021-11-12 12:55:02', '2021-12-04 13:03:28'),
(11, 'A11', 'Enable', '2021-12-03 18:20:16', '2021-12-04 12:45:09'),
(12, 'hy', 'Enable', '2025-03-14 22:21:57', ''),
(13, 'Row 1', 'Enable', '2025-03-15 04:23:58', '');

-- --------------------------------------------------------

--
-- Table structure for table `lms_setting`
--

CREATE TABLE `lms_setting` (
  `setting_id` int(11) NOT NULL,
  `library_name` varchar(200) NOT NULL,
  `library_address` text NOT NULL,
  `library_contact_number` varchar(30) NOT NULL,
  `library_email_address` varchar(100) NOT NULL,
  `library_open_hours` varchar(255) NOT NULL DEFAULT '8am-4pm MON-FRI',
  `library_total_book_issue_day` int(5) NOT NULL,
  `library_one_day_fine` decimal(4,2) NOT NULL,
  `library_issue_total_book_per_user` int(3) NOT NULL,
  `library_currency` varchar(30) NOT NULL,
  `library_timezone` varchar(100) NOT NULL,
  `library_logo` varchar(255) NOT NULL DEFAULT 'logo.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_setting`
--

INSERT INTO `lms_setting` (`setting_id`, `library_name`, `library_address`, `library_contact_number`, `library_email_address`, `library_open_hours`, `library_total_book_issue_day`, `library_one_day_fine`, `library_issue_total_book_per_user`, `library_currency`, `library_timezone`, `library_logo`) VALUES
(1, 'SmartLib', 'Poblacion, Curuan', '09657893421', 'wmsu_curuan_lib@gmail.com', '8am-5pm MON-SAT', 10, 1.00, 3, 'PHP', 'Asia/Calcutta', 'logo.png');

-- --------------------------------------------------------

--
-- Table structure for table `lms_user`
--

CREATE TABLE `lms_user` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(200) NOT NULL,
  `user_address` text NOT NULL,
  `user_contact_no` varchar(30) NOT NULL,
  `user_profile` varchar(100) NOT NULL DEFAULT 'user.jpg',
  `user_email` varchar(200) NOT NULL,
  `user_password` varchar(30) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 5,
  `user_verification_code` varchar(255) NOT NULL,
  `user_verification_status` enum('No','Yes') NOT NULL,
  `user_unique_id` varchar(30) NOT NULL,
  `user_status` enum('Enable','Disable') NOT NULL,
  `user_created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_updated_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lms_user`
--

INSERT INTO `lms_user` (`user_id`, `user_name`, `user_address`, `user_contact_no`, `user_profile`, `user_email`, `user_password`, `role_id`, `user_verification_code`, `user_verification_status`, `user_unique_id`, `user_status`, `user_created_on`, `user_updated_on`) VALUES
(3, 'Paul Black', '4016 Goldie Lane Cincinnati, OH 45202', '7539518520', '1636699900-2617.jpg', 'paulblake@gmail.com', '$2y$10$LMswUk7S85FbsUVITtyNieD', 5, 'b190bcd6e3b29674db036670cf122724', 'Yes', 'U82514529', 'Enable', '2021-11-12 04:21:40', '2025-03-27 01:05:50'),
(4, 'Aaron Lawler', '1616 Broadway Avenue Chattanooga, TN 37421', '8569856321', '1636905360-32007.jpg', 'aaronlawler@live.com', 'password', 3, 'add84abb895484d12344316eccb78a62', 'Yes', 'F37570190', 'Enable', '2021-11-12 08:39:20', '2025-03-26 05:36:08'),
(5, 'Kathleen Forrest', '4545 Limer Street Greensboro, GA 30642', '85214796930', '1637041684-15131.jpg', 'kathleen@hotmail.com', 'password', 4, '7013df5205011ffcb99ea57902c17369', 'Yes', 'S24567871', 'Enable', '2021-11-16 03:18:04', '2025-03-26 05:53:20'),
(6, 'Carol Maney', '2703 Deer Haven Drive Greenville, SC 29607', '8521479630', '1637126571-21753.jpg', 'web-tutorial1@programmer.net', 'password', 5, 'a6c2623984d590239244f8695df3a30b', 'Yes', 'U52357788', 'Enable', '2021-11-17 02:52:51', '2025-03-26 05:36:25'),
(10, 'Kevin Peterson', '1889 Single Street Waltham, MA 02154', '8523698520', '1639658464-10192.jpg', 'web-tutorial@programmer.net', 'password123', 5, '337ea20da40326d134fe5eca3fb03464', 'Yes', 'U59564819', 'Enable', '2021-12-14 04:56:29', '2025-03-26 05:36:28'),
(11, 'Faye Lacsi', '', '09823830938478', '', 'lacsi@gmail.com', '$2y$10$Zj/oONYmk9iSH8puqvCNsOu', 5, '', 'No', 'U17845470', 'Enable', '2025-03-20 10:10:18', '2025-03-26 06:53:13'),
(12, 'Jake Bruce', '', '09736788383', '', 'bruce@gmail.com', '$2y$10$hkYDyWpXBwbnn8BvbrssV.e', 5, '', '', 'U41683769', 'Enable', '2025-03-20 06:02:39', '2025-03-26 06:53:13'),
(13, 'Derriel', '', '093789383837737', '', 'derriel@gmail.com', '$2y$10$n36NAVxz2eQUXc5Focqwf./', 5, '', '', 'U54882437', 'Enable', '2025-03-20 06:03:24', '2025-03-26 06:53:13'),
(14, 'gina', '', '0965688765', '', 'gina@gmail.com', '$2y$10$NTvHtfmzGn6b5IbyHRYoZu6', 5, '', '', 'U49360880', 'Enable', '2025-03-20 06:04:40', '2025-03-26 06:53:13'),
(15, 'Kate Pink', '', '0973787366', '', 'pink@gmail.com', '$2y$10$YSxZru8YualfNhSEJDubV.Q', 5, '', 'No', 'U82157096', 'Enable', '2025-03-20 10:41:02', '2025-03-26 06:53:13'),
(16, 'Barbie Blue', '', '0973837663', '', 'barbie@gmail.com', '$2y$10$MrATmlOi475YSzubS0QvyOp', 5, '', 'No', 'U62702842', 'Enable', '2025-03-20 10:46:05', '2025-03-26 06:53:13'),
(17, 'April Manalo', '', '09974749474', '', 'manalo@gmail.com', '$2y$10$mzjvLfb9TI8nILqq49cSxuZ', 5, '', 'No', 'U67042925', 'Enable', '2025-03-20 10:58:39', '2025-03-26 06:53:13'),
(18, 'gina thong', 'curuan', '0987544613', '1743038547-737356096.jpg', 'teff.wong@gmail.com', '$2y$10$VemqjTGKtQa2qrU5eviwH.t', 5, 'f7ce5b2b89ccbe2430fb8cd54b9426b7', 'No', 'U47091166', 'Enable', '2025-03-26 22:52:27', '2025-03-27 01:22:27');

--
-- Triggers `lms_user`
--
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

CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for dumped tables
--

--
-- Indexes for table `lms_admin`
--
ALTER TABLE `lms_admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `fk_lms_admin_role` (`role_id`);

--
-- Indexes for table `lms_author`
--
ALTER TABLE `lms_author`
  ADD PRIMARY KEY (`author_id`);

--
-- Indexes for table `lms_book`
--
ALTER TABLE `lms_book`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `book_isbn_number` (`book_isbn_number`);

--
-- Indexes for table `lms_category`
--
ALTER TABLE `lms_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `lms_issue_book`
--
ALTER TABLE `lms_issue_book`
  ADD PRIMARY KEY (`issue_book_id`),
  ADD KEY `fk_issue_book_book` (`book_id`),
  ADD KEY `fk_issue_book_user` (`user_id`);

--
-- Indexes for table `lms_librarian`
--
ALTER TABLE `lms_librarian`
  ADD PRIMARY KEY (`librarian_id`),
  ADD UNIQUE KEY `librarian_unique_id` (`librarian_unique_id`),
  ADD KEY `fk_lms_librarian_role` (`role_id`);

--
-- Indexes for table `lms_location_rack`
--
ALTER TABLE `lms_location_rack`
  ADD PRIMARY KEY (`location_rack_id`);

--
-- Indexes for table `lms_setting`
--
ALTER TABLE `lms_setting`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `lms_user`
--
ALTER TABLE `lms_user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_unique_id` (`user_unique_id`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lms_admin`
--
ALTER TABLE `lms_admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lms_author`
--
ALTER TABLE `lms_author`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_book`
--
ALTER TABLE `lms_book`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `lms_category`
--
ALTER TABLE `lms_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lms_issue_book`
--
ALTER TABLE `lms_issue_book`
  MODIFY `issue_book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `lms_librarian`
--
ALTER TABLE `lms_librarian`
  MODIFY `librarian_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lms_location_rack`
--
ALTER TABLE `lms_location_rack`
  MODIFY `location_rack_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `lms_setting`
--
ALTER TABLE `lms_setting`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lms_user`
--
ALTER TABLE `lms_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lms_admin`
--
ALTER TABLE `lms_admin`
  ADD CONSTRAINT `fk_lms_admin_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `lms_issue_book`
--
ALTER TABLE `lms_issue_book`
  ADD CONSTRAINT `fk_issue_book_book` FOREIGN KEY (`book_id`) REFERENCES `lms_book` (`book_isbn_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_issue_book_user` FOREIGN KEY (`user_id`) REFERENCES `lms_user` (`user_unique_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
