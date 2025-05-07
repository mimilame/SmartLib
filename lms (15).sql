-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 06, 2025 at 12:57 PM
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
-- Table structure for table `lms_about_us`
--

DROP TABLE IF EXISTS `lms_about_us`;
CREATE TABLE IF NOT EXISTS `lms_about_us` (
  `id` int NOT NULL AUTO_INCREMENT,
  `history` text NOT NULL,
  `mission` text NOT NULL,
  `vision` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_about_us`
--

INSERT INTO `lms_about_us` (`id`, `history`, `mission`, `vision`, `created_at`, `updated_at`) VALUES
(1, '<p>SmartLib is the heart of the Western Mindanao State University - External Studies Unit in Curuan, serving as the intellectual center for our academic community. Since our establishment, we have been committed to providing resources and services that support the university\'s teaching, learning, and research missions.</p><p>Our library has evolved from a small collection of books to a comprehensive resource center equipped with modern technology and a diverse range of materials to meet the needs of our growing student population and faculty.</p>', '<p>At SmartLib, our mission is to empower students, faculty, and the community through access to information resources, technology, and services that enhance learning, teaching, research, and personal growth. We strive to be innovative, responsive, and user-focused while fostering academic excellence and intellectual discovery.</p>', '<p>SmartLib aims to be a leading academic library that inspires intellectual curiosity, promotes digital literacy, and serves as a model for innovative library services. We envision a library that adapts to evolving educational needs while preserving our cultural heritage and contributing to the academic success of our community.</p>', '2025-04-12 03:48:06', '2025-04-12 03:48:06');

-- --------------------------------------------------------

--
-- Table structure for table `lms_admin`
--

DROP TABLE IF EXISTS `lms_admin`;
CREATE TABLE IF NOT EXISTS `lms_admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_email` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `admin_password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
(1, 'roselyn@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 'A87654321', 1, 'admin.jpg'),
(2, 'johnsmith1@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 'A98765432', 1, 'admin_1745293836.jpg');

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
  `author_biography` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `author_created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author_updated_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_author`
--

INSERT INTO `lms_author` (`author_id`, `author_name`, `author_status`, `author_profile`, `author_biography`, `author_created_on`, `author_updated_on`) VALUES
(1, 'Cate Blanchett', 'Disable', 'author.jpg', 'Renowned for her dynamic range and captivating storytelling, Cate brings a masterful touch to every narrative.', '2021-11-11 07:45:14', '2025-04-12 05:12:22'),
(2, 'Tom Butler', 'Enable', 'author.jpg', 'Tom Butler delivers engaging narratives through insightful writing and deep character development.', '2021-11-12 04:48:40', '2025-04-12 05:12:22'),
(3, 'Lynn Beighley', 'Enable', 'author.jpg', 'Lynn Beighley explores human emotions with clarity and humor in her compelling stories.', '2021-11-12 04:49:00', '2025-04-12 05:12:22'),
(4, 'Vikram Vaswani', 'Enable', 'author.jpg', 'Vikram Vaswani is celebrated for his versatile storytelling that bridges diverse cultures and perspectives.', '2021-11-12 04:49:18', '2025-04-12 05:12:22'),
(5, 'Daginn Reiersol', 'Enable', 'author.jpg', 'Daginn Reiersol crafts compelling tales that seamlessly blend tradition with modern insights.', '2021-11-12 04:49:38', '2025-04-12 05:12:22'),
(6, 'Joel Murach', 'Enable', 'author.jpg', 'Joel Murach writes with precision and clarity, offering thoughtful insights that resonate with readers.', '2021-11-12 04:49:54', '2025-04-12 05:12:22'),
(7, 'Robin Nixon', 'Enable', 'author.jpg', 'Robin Nixon is known for vivid imagery and storytelling that reflects real-life experiences.', '2021-11-12 04:50:09', '2025-04-12 05:12:22'),
(8, 'Kevin Tatroe', 'Enable', 'author.jpg', 'Kevin Tatroe captivates audiences with an engaging voice and innovative narrative techniques.', '2021-11-12 04:50:24', '2025-04-12 05:12:22'),
(9, 'Laura Thompson', 'Enable', 'author.jpg', 'Laura Thompson brings warmth and depth to her writing, exploring themes of love and resilience.', '2021-11-12 04:50:42', '2025-04-12 05:12:22'),
(10, 'Brett Shimson', 'Enable', 'author.jpg', 'Brett Shimson is recognized for his crisp, dynamic prose that energizes every story.', '2021-11-12 04:50:55', '2025-04-12 05:12:22'),
(11, 'Sanjib Sinha', 'Enable', 'author.jpg', 'Sanjib Sinha writes with passion and insight, weaving intricate stories that move the heart.', '2021-11-12 04:51:16', '2025-04-12 05:12:22'),
(12, 'Brian Messenlehner', 'Enable', 'author.jpg', 'Brian Messenlehner examines the nuances of everyday life with wit and rich storytelling.', '2021-11-12 04:51:42', '2025-04-12 05:12:22'),
(13, 'Dayle Rees', 'Enable', 'author.jpg', 'Dayle Rees has a unique style that blends humor with deep insights into the human experience.', '2021-11-12 04:52:02', '2025-04-12 05:12:22'),
(14, 'Carlos Buenosvinos', 'Enable', 'author.jpg', 'Carlos Buenosvinos crafts evocative stories that illuminate cultural heritage and personal journeys.', '2021-11-12 04:52:20', '2025-04-12 05:12:22'),
(15, 'Bruce Berke', 'Enable', 'author.jpg', 'Bruce Berke offers thoughtful analysis and engaging storytelling techniques in each of his works.', '2021-11-12 04:52:35', '2025-04-12 05:12:22'),
(16, 'Laura Thomson', 'Enable', 'author.jpg', 'Laura Thomson is admired for her compelling narrative voice and richly layered storytelling.', '2021-11-17 02:39:36', '2025-04-12 05:12:22'),
(18, 'David Herman', 'Enable', 'author.jpg', 'David is known for his sharp wit and ability to transform everyday experiences into memorable tales.', '2021-11-30 06:36:35', '2025-04-12 05:13:38'),
(19, 'Mark Myers', 'Enable', 'author_1745288594.jpg', 'Mark Myers is known for his sharp wit and ability to transform everyday experiences into memorable tales.', '2021-12-08 10:45:15', '2025-04-22 02:23:14'),
(20, 'Rose', 'Enable', 'author_1744626692.png', 'Rose offers a fresh, introspective perspective, blending emotion with keen observations in her evocative style.', '2025-03-14 22:48:22', '2025-04-14 10:31:32'),
(21, 'Yung Kai', 'Enable', 'author_1744434830.jpg', 'Yung Kai is known for his sharp wit and ability to transform everyday experiences into memorable tales.', '2025-04-12 02:43:50', '2025-04-12 02:43:50');

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
  `book_description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `book_edition` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `book_publisher` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `book_published` date DEFAULT NULL,
  `book_added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `book_updated_on` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`book_id`),
  UNIQUE KEY `book_isbn_number` (`book_isbn_number`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_book`
--

INSERT INTO `lms_book` (`book_id`, `category_id`, `book_author`, `book_location_rack`, `book_name`, `book_isbn_number`, `book_no_of_copy`, `book_status`, `book_img`, `book_description`, `book_edition`, `book_publisher`, `book_published`, `book_added_on`, `book_updated_on`) VALUES
(1, 1, 'Tom Butler', 'A1', 'The Joy of PHP Programming', '978152279214', 7, 'Enable', '67fce6b003e4c.png', '', '1st Edition', 'Maglaya Publishing.Co', '2024-07-16', '2021-11-11 09:32:33', '2025-04-19 15:16:19'),
(2, 1, 'Tom Butler', 'A2', 'PHP and MySQL Novice to Ninja', '852369852123', 11, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 04:56:23', '2025-04-15 11:32:51'),
(3, 1, 'Cate Blanchett, Vikram Vaswani', 'A3', 'Head First PHP and MySQL', '7539518526963', 6, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 04:57:04', '2025-04-15 06:00:25'),
(4, 1, 'Tom Butler', 'A4', 'PHP A Beginners Guide', '74114774147', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 04:57:47', '2025-03-31 09:54:55'),
(5, 1, 'Lynn Beighley, Daginn Reiersol', 'A5', 'PHP In Action Objects Design Agility', '85225885258', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 04:58:34', '2025-03-31 09:54:55'),
(6, 1, 'Cate Blanchett, Tom Butler, Vikram Vaswani', 'A6', 'Murachs PHP and MySQL', '8585858596632', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 05:00:15', '2025-03-31 09:54:55'),
(7, 1, 'Lynn Beighley', 'A8', 'Learning PHP MySQL JavaScript and CSS Creating Dynamic Websites', '753852963258', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 05:01:10', '2025-03-31 09:54:55'),
(8, 9, 'Kevin Tatroe, Dayle Rees', 'A8', 'The Adventures of Sherlock Holmes', '969335785842', 6, 'Enable', 'book_placeholder.png', 'lnfdsofneafwagdb dsvfeb', '1st Edition', 'Maglaya Publishing.Co', '2024-07-16', '2021-11-12 05:01:57', '2025-04-21 02:46:33'),
(9, 1, 'Tom Butler, Vikram Vaswani', 'A1', 'PHP Programming and MySQL Database for Web Development', '963369852258', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 05:02:48', '2025-03-31 09:54:55'),
(10, 1, 'Cate Blanchett, Lynn Beighley, Daginn Reiersol', 'A2', 'PHP MySQL The Missing Manual', '85478569856', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 05:03:51', '2025-03-31 09:54:55'),
(11, 1, 'Joel Murach, Sanjib Sinha', 'A3', 'Beginning Laravel A beginners guide', '856325774562', 4, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 05:04:39', '2025-04-15 12:38:31'),
(12, 1, 'Robin Nixon, Brian Messenlehner', 'A3', 'Building Web Apps with WordPress', '96325741258', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 05:05:18', '2025-03-31 09:54:55'),
(13, 1, 'Dayle Rees', 'A5', 'The Laravel Framework Version 5 For Beginners', '336985696363', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-12 05:05:56', '2025-03-31 09:37:07'),
(14, 1, 'Carlos Buenosvinos, Bruce Berke', 'A11', 'My Boo', '23721950629', 5, 'Enable', '67fa1923b348c.jpg', '', '1st Edition', 'Maglaya Publishing.Co', '2024-09-03', '2021-11-12 05:06:35', '2025-04-21 03:34:46'),
(15, 1, 'Bruce Berke', 'A8', 'My Boo', '744785963520', 15, 'Enable', '67fa1923b348c.jpg', '', '1st Edition', 'Maglaya Publishing.Co', '2024-09-03', '2021-11-12 05:07:27', '2025-04-21 03:16:10'),
(16, 1, 'Kevin Tatroe, Laura Thomson', 'A2', 'PHP and MySQL Web Development', '753951852123', 0, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-11-17 02:43:19', '2025-04-15 12:41:13'),
(17, 1, 'Mark Myers', 'A11', 'A Smarter Way to Learn JavaScript', '852369753951', 5, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2021-12-08 10:48:11', '2025-04-19 16:43:07'),
(18, 1, 'Carlos Buenosvinos, Rose', 'A3', 'Happy', '64739929873', 11, 'Enable', 'book_placeholder.png', NULL, NULL, NULL, NULL, '2025-03-14 12:57:42', '2025-04-15 11:52:09'),
(19, 6, 'Bruce Berke', 'A10', 'Rizal 101', '09844888484', 6, 'Enable', 'book_placeholder.png', '', '', '', '0000-00-00', '2025-03-30 15:53:25', '2025-04-21 03:33:32'),
(20, 9, 'Kevin Tatroe, Dayle Rees', 'A8', 'The Adventures of Sherlock Holmes', '894647213656', 6, 'Enable', 'book_placeholder.png', 'lnfdsofneafwagdb dsvfeb', '1st Edition', 'Maglaya Publishing.Co', '2024-07-16', '2025-04-12 04:25:37', '2025-04-21 03:33:50'),
(21, 4, 'Tom Butler, Yung Kai', 'A10', 'Learning PHP for Beginners', '978-0596001461', 2, 'Disable', '67fa10626ed32.jpg', 'A step-by-step guide covering everything from PHP basics to more advanced topics, perfect for those just starting out in web development.', '3rd', 'Maglaya Publishing.Co', '2023-07-03', '2025-04-12 04:34:02', '2025-04-21 02:32:24'),
(22, 1, 'Bruce Berke', 'A8', 'My Boo', '23721950624', 15, 'Enable', '67fa1923b348c.jpg', '', '1st Edition', 'Maglaya Publishing.Co', '2024-09-03', '2025-04-12 05:11:23', '2025-04-21 03:26:21'),
(23, 6, 'Brian Messenlehner, Carlos Buenosvinos', 'A1', 'Test', '25648495498', 2, 'Enable', '6806f1c026574.jpg', '', '1st Edition', 'Maglaya Publishing.Co', '2025-04-22', '2025-04-21 03:27:54', '2025-04-22 01:32:48');

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

--
-- Dumping data for table `lms_book_author`
--

INSERT INTO `lms_book_author` (`book_id`, `author_id`) VALUES
(1, 3),
(1, 5),
(2, 2),
(3, 1),
(3, 4),
(4, 2),
(5, 3),
(5, 5),
(6, 1),
(6, 2),
(6, 4),
(7, 3),
(8, 8),
(8, 13),
(9, 2),
(9, 4),
(10, 1),
(10, 3),
(10, 5),
(11, 6),
(11, 11),
(12, 7),
(12, 12),
(13, 13),
(14, 14),
(14, 15),
(15, 15),
(16, 8),
(16, 16),
(17, 19),
(18, 14),
(18, 20),
(19, 15),
(20, 8),
(20, 13),
(21, 2),
(21, 21),
(22, 15),
(23, 12),
(23, 14);

-- --------------------------------------------------------

--
-- Table structure for table `lms_book_review`
--

DROP TABLE IF EXISTS `lms_book_review`;
CREATE TABLE IF NOT EXISTS `lms_book_review` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `review_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `status` enum('pending','approved','rejected','flagged') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remarks` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `flagged_by` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `flagged_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`review_id`),
  KEY `book_id` (`book_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_book_review`
--

INSERT INTO `lms_book_review` (`review_id`, `book_id`, `user_id`, `rating`, `review_text`, `status`, `created_at`, `updated_at`, `remarks`, `flagged_by`, `flagged_at`) VALUES
(48, 1, 3, 4, 'Excellent introduction to PHP. The step-by-step approach really helped me understand the concepts.', 'approved', '2025-01-20 02:15:42', '2025-04-18 12:48:45', NULL, NULL, NULL),
(49, 1, 13, 5, 'Best PHP book I\'ve read so far. Clear explanations and practical examples.', 'pending', '2025-04-05 08:45:11', '2025-04-18 12:13:38', NULL, NULL, NULL),
(50, 1, 16, 3, 'Very helpful for beginners. The examples made learning PHP much easier.', 'pending', '2025-04-03 01:22:13', '2025-04-18 12:13:42', NULL, NULL, NULL),
(51, 1, 17, 5, 'This book made learning PHP enjoyable! The examples are relevant and easy to follow.', 'pending', '2025-04-02 06:30:22', '2025-04-18 12:13:46', NULL, NULL, NULL),
(52, 2, 4, 5, 'Comprehensive coverage of both PHP and MySQL. The examples are practical and well-explained.', 'pending', '2025-03-16 03:33:27', '2025-04-18 12:13:49', NULL, NULL, NULL),
(53, 2, 6, 3, 'Good content but some examples are outdated. Still a decent resource.', 'pending', '2025-03-27 05:52:16', '2025-04-18 12:13:53', NULL, NULL, NULL),
(54, 2, 11, 4, 'Great book for beginners. The Ninja theme makes learning fun!', 'pending', '2025-04-05 01:22:13', '2025-04-18 12:13:56', NULL, NULL, NULL),
(55, 3, 3, 5, 'The visual approach really works for me. Made complex concepts easier to understand.', 'pending', '2025-02-18 07:14:32', '2025-04-18 12:13:59', NULL, NULL, NULL),
(56, 3, 6, 4, 'Entertaining and educational. The unique format keeps you engaged.', 'pending', '2025-03-28 04:05:19', '2025-04-18 12:14:03', NULL, NULL, NULL),
(57, 4, 4, 4, 'Well-structured content. Good for beginners with some programming background.', 'pending', '2025-03-14 01:18:50', '2025-04-18 08:37:48', NULL, NULL, NULL),
(58, 4, 11, 3, 'Solid introduction but could use more detailed explanations for some concepts.', 'approved', '2025-04-01 02:42:36', '2025-04-17 04:58:55', NULL, NULL, NULL),
(59, 4, 17, 4, 'Clear explanations and helpful code examples. Recommended for newcomers to PHP.', 'approved', '2025-04-05 06:27:33', '2025-04-17 12:00:25', NULL, NULL, NULL),
(60, 5, 3, 5, 'Advanced but approachable. Great resource for learning object-oriented PHP.', 'approved', '2025-02-28 08:39:25', '2025-04-18 12:48:58', NULL, NULL, NULL),
(61, 5, 13, 5, 'Excellent coverage of design patterns in PHP. Changed how I approach PHP development.', 'approved', '2025-04-05 05:24:58', '2025-04-17 12:03:11', NULL, NULL, NULL),
(62, 17, 16, 5, 'Best JavaScript book for beginners. The method works brilliantly.', 'approved', '2025-04-11 06:08:52', '2025-04-18 12:48:33', NULL, NULL, NULL),
(63, 19, 17, 4, 'Great introduction to Rizal studies. Well-researched and engaging.', 'approved', '2025-04-04 02:45:22', '2025-04-17 04:58:33', NULL, NULL, NULL);

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
(1, 'Programming', 'Enable', '2021-11-10 19:02:37', '2025-04-16 20:36:03'),
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
-- Table structure for table `lms_fines`
--

DROP TABLE IF EXISTS `lms_fines`;
CREATE TABLE IF NOT EXISTS `lms_fines` (
  `fines_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `issue_book_id` int NOT NULL,
  `expected_return_date` datetime NOT NULL,
  `return_date` datetime NOT NULL,
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
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_fines`
--

INSERT INTO `lms_fines` (`fines_id`, `user_id`, `issue_book_id`, `expected_return_date`, `return_date`, `days_late`, `fines_amount`, `fines_status`, `fines_created_on`, `fines_updated_on`) VALUES
(10, 17, 4, '2025-04-17 12:00:00', '2025-04-19 11:01:00', 1, 10, 'Paid', '2025-04-19 11:54:28', '2025-04-19 15:09:03'),
(11, 13, 3, '2025-04-19 11:01:00', '2025-04-19 17:06:00', 1, 10, 'Unpaid', '2025-04-19 16:34:07', '2025-04-19 17:26:26'),
(12, 4, 1, '2025-04-19 11:01:00', '2025-04-21 16:39:00', 2, 20, 'Unpaid', '2025-04-19 16:39:24', '2025-04-19 17:26:08'),
(13, 16, 2, '2025-04-19 00:00:00', '0000-00-00 00:00:00', 3, 30, 'Unpaid', '2025-04-22 05:43:19', '2025-04-22 05:43:19'),
(14, 6, 5, '2025-04-20 00:00:00', '0000-00-00 00:00:00', 2, 20, 'Unpaid', '2025-04-22 05:43:19', '2025-04-22 05:43:19'),
(15, 13, 6, '2025-04-19 00:00:00', '0000-00-00 00:00:00', 3, 30, 'Unpaid', '2025-04-22 05:43:19', '2025-04-22 05:43:19'),
(16, 4, 4, '2025-04-18 00:00:00', '2025-05-06 00:00:00', 18, 0, 'Unpaid', '2025-05-06 06:37:39', '2025-05-06 06:37:39');

-- --------------------------------------------------------

--
-- Table structure for table `lms_issue_book`
--

DROP TABLE IF EXISTS `lms_issue_book`;
CREATE TABLE IF NOT EXISTS `lms_issue_book` (
  `issue_book_id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL,
  `user_id` int NOT NULL,
  `issue_date` datetime NOT NULL,
  `expected_return_date` datetime NOT NULL,
  `return_date` datetime NOT NULL,
  `issue_book_status` enum('Issued','Returned','Overdue','Lost') NOT NULL,
  `issued_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `issue_updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `book_condition` enum('Good','Damaged','Missing Pages','Water Damaged','Binding Loose') NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`issue_book_id`),
  KEY `book_id` (`book_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_issue_book`
--

INSERT INTO `lms_issue_book` (`issue_book_id`, `book_id`, `user_id`, `issue_date`, `expected_return_date`, `return_date`, `issue_book_status`, `issued_on`, `issue_updated_on`, `book_condition`, `remarks`) VALUES
(1, 17, 4, '2025-04-18 11:01:00', '2025-04-19 11:01:00', '2025-04-21 16:39:24', 'Returned', '2025-04-17 13:10:08', '2025-04-19 16:45:36', 'Good', NULL),
(2, 18, 16, '2025-04-17 00:00:00', '2025-04-19 00:00:00', '0000-00-00 00:00:00', 'Overdue', '2025-04-17 13:11:45', '2025-04-19 19:14:42', 'Good', NULL),
(3, 22, 13, '2025-04-19 11:01:00', '2025-04-19 11:01:00', '2025-04-19 17:06:14', 'Returned', '2025-04-17 13:14:10', '2025-04-19 17:06:14', 'Binding Loose', ''),
(4, 19, 4, '2025-04-19 00:00:00', '2025-04-18 00:00:00', '0000-00-00 00:00:00', 'Overdue', '2025-04-17 13:37:18', '2025-05-06 06:37:39', '', NULL),
(5, 17, 6, '2025-04-17 00:00:00', '2025-04-20 00:00:00', '0000-00-00 00:00:00', 'Overdue', '2025-04-17 13:37:33', '2025-04-22 05:43:19', 'Good', NULL),
(6, 3, 13, '2025-04-17 00:00:00', '2025-04-19 00:00:00', '0000-00-00 00:00:00', 'Overdue', '2025-04-17 13:37:47', '2025-04-19 19:14:42', 'Good', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lms_librarian`
--

DROP TABLE IF EXISTS `lms_librarian`;
CREATE TABLE IF NOT EXISTS `lms_librarian` (
  `librarian_id` int NOT NULL AUTO_INCREMENT,
  `librarian_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `librarian_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `librarian_contact_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `librarian_profile` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'librarian.jpg',
  `librarian_email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `librarian_password` varchar(255) NOT NULL,
  `role_id` int NOT NULL DEFAULT '2',
  `librarian_verification_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `librarian_verification_status` enum('No','Yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `librarian_unique_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `librarian_status` enum('Enable','Disable') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `lib_created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lib_updated_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`librarian_id`),
  UNIQUE KEY `librarian_unique_id` (`librarian_unique_id`),
  KEY `fk_lms_librarian_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_librarian`
--

INSERT INTO `lms_librarian` (`librarian_id`, `librarian_name`, `librarian_address`, `librarian_contact_no`, `librarian_profile`, `librarian_email`, `librarian_password`, `role_id`, `librarian_verification_code`, `librarian_verification_status`, `librarian_unique_id`, `librarian_status`, `lib_created_on`, `lib_updated_on`) VALUES
(1, 'Honey A. Atilano', 'Curuan, Zamboanga City', '093784757387', 'librarian.jpg', 'honey@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, 'Yes', 'L12345678', 'Enable', '2025-03-20 10:22:31', '2025-03-31 15:33:13'),
(2, 'Roselyn Tarroza', NULL, '09737893974', NULL, 'rose@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, 'L23456789', 'Enable', '2025-03-20 10:22:31', '2025-03-31 15:33:13'),
(3, 'May Natividad', NULL, '098384848', NULL, 'natividad@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, 'L34567890', 'Enable', '2025-03-20 10:22:31', '2025-03-31 15:33:13'),
(4, 'Jeric Palca', NULL, '098349934994', NULL, 'palca@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, NULL, 'Disable', '2025-03-20 10:22:31', '2025-03-31 15:33:13'),
(5, 'Kenneth Cruz', NULL, '0983848748444', NULL, 'cruz@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 10:22:31', '2025-03-31 15:33:13'),
(6, 'Daisy Lamorinas', NULL, '0938923898389', NULL, 'lamorinas@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 10:22:31', '2025-03-31 15:33:13'),
(7, 'Noel Comeros', NULL, '099343284893', NULL, 'comeros@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 10:22:31', '2025-03-31 15:33:13'),
(8, 'Steffi Wong', NULL, '092773782882', '1637041684-15131.jpg', 'wong@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, NULL, 'Enable', '2025-03-20 08:54:00', '2025-04-14 09:15:32'),
(9, 'sample', NULL, '09546213578', '1743038547-737356096.jpg', 'sample.test@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 2, NULL, NULL, 'L52907001', 'Enable', '2025-03-26 21:25:17', '2025-04-14 09:29:47'),
(10, 'Lady Gaga', NULL, '095484656565', '1744623562_Screenshot 2025-03-28 095301.png', 'ladygaga@gmail.com', '$2y$10$QFIsrTQrzD90gjUpHcuCdOQBkRh2eo0lB8nH9iHcwvXWsmmtH5SSK', 2, NULL, NULL, 'L25235740', 'Enable', '2025-04-12 06:47:29', '2025-04-14 09:39:22');

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
-- Table structure for table `lms_library_features`
--

DROP TABLE IF EXISTS `lms_library_features`;
CREATE TABLE IF NOT EXISTS `lms_library_features` (
  `feature_id` int NOT NULL AUTO_INCREMENT,
  `feature_name` varchar(100) NOT NULL,
  `feature_icon` varchar(50) NOT NULL,
  `position_x` int NOT NULL,
  `position_y` int NOT NULL,
  `width` int NOT NULL,
  `height` int NOT NULL,
  `bg_color` varchar(50) NOT NULL,
  `text_color` varchar(50) NOT NULL,
  `feature_status` enum('Enable','Disable') DEFAULT 'Enable',
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`feature_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_library_features`
--

INSERT INTO `lms_library_features` (`feature_id`, `feature_name`, `feature_icon`, `position_x`, `position_y`, `width`, `height`, `bg_color`, `text_color`, `feature_status`, `created_on`, `updated_on`) VALUES
(1, 'Entrance', 'fas fa-door-open', 22, 12, 150, 50, 'bg-secondary', 'text-light', 'Enable', '2025-04-14 07:22:49', '2025-04-15 13:10:42'),
(2, 'Reading Area', 'fas fa-book-reader', 335, 394, 160, 105, 'bg-warning', 'text-primary', 'Enable', '2025-04-14 07:55:10', '2025-04-15 13:59:41'),
(3, 'Computer Desks', 'fas fa-desktop', 55, 419, 100, 60, 'bg-primary', 'text-light', 'Enable', '2025-04-15 14:04:33', '2025-04-15 14:04:33');

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
  `position_x` int DEFAULT '0',
  `position_y` int DEFAULT '0',
  PRIMARY KEY (`location_rack_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_location_rack`
--

INSERT INTO `lms_location_rack` (`location_rack_id`, `location_rack_name`, `location_rack_status`, `rack_created_on`, `rack_updated_on`, `position_x`, `position_y`) VALUES
(1, 'A1', 'Enable', '2021-11-11 08:16:27', '2025-04-12 13:50:47', 60, 80),
(2, 'A2', 'Enable', '2021-11-12 04:53:49', '2025-04-15 06:13:49', 200, 100),
(3, 'A3', 'Enable', '2021-11-12 04:53:57', '2025-04-12 13:50:48', 280, 80),
(4, 'A4', 'Enable', '2021-11-12 04:54:06', '2025-04-12 13:50:48', 390, 80),
(5, 'A5', 'Enable', '2021-11-12 04:54:14', '2025-04-12 13:50:48', 60, 180),
(6, 'A6', 'Enable', '2021-11-12 04:54:22', '2025-04-12 13:50:48', 170, 180),
(7, 'A7', 'Enable', '2021-11-12 04:54:30', '2025-04-12 13:50:48', 280, 180),
(8, 'A8', 'Enable', '2021-11-12 04:54:38', '2025-04-12 13:50:48', 390, 180),
(9, 'Row 3', 'Enable', '2021-11-12 04:54:52', '2025-04-12 13:50:48', 60, 280),
(10, 'A10', 'Enable', '2021-11-12 04:55:02', '2025-04-12 13:50:48', 170, 280),
(11, 'A11', 'Enable', '2021-12-03 10:20:16', '2025-04-12 13:50:48', 280, 280),
(12, 'hy', 'Enable', '2025-03-14 14:21:57', '2025-04-12 13:50:48', 390, 280),
(13, 'Row 1', 'Disable', '2025-03-14 20:23:58', '2025-04-15 06:05:14', 450, 250),
(14, 'A51', 'Enable', '2025-04-15 00:05:32', '2025-04-15 00:05:32', 450, 100);

-- --------------------------------------------------------

--
-- Table structure for table `lms_review_settings`
--

DROP TABLE IF EXISTS `lms_review_settings`;
CREATE TABLE IF NOT EXISTS `lms_review_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `moderate_reviews` tinyint(1) NOT NULL DEFAULT '1',
  `allow_guest_reviews` tinyint(1) NOT NULL DEFAULT '0',
  `reviews_per_page` int NOT NULL DEFAULT '10',
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lms_review_settings`
--

INSERT INTO `lms_review_settings` (`setting_id`, `moderate_reviews`, `allow_guest_reviews`, `reviews_per_page`) VALUES
(1, 1, 1, 10);

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
  `library_max_fine_per_book` decimal(10,2) NOT NULL DEFAULT '50.00',
  `library_issue_total_book_per_user` int NOT NULL,
  `library_currency` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_timezone` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `library_logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'logo.png',
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_setting`
--

INSERT INTO `lms_setting` (`setting_id`, `library_name`, `library_address`, `library_contact_number`, `library_email_address`, `library_open_hours`, `library_total_book_issue_day`, `library_one_day_fine`, `library_max_fine_per_book`, `library_issue_total_book_per_user`, `library_currency`, `library_timezone`, `library_logo`) VALUES
(1, 'SmartLib', 'Poblacion, Curuan', '09657893421', 'wmsu_curuan_lib@gmail.com', '{\"Monday\":{\"open\":\"08:00\",\"close\":\"17:00\"},\"Tuesday\":{\"open\":\"08:00\",\"close\":\"17:00\"},\"Wednesday\":{\"open\":\"13:00\",\"close\":\"17:00\"},\"Thursday\":{\"open\":null,\"close\":null},\"Friday\":{\"open\":null,\"close\":null},\"Saturday\":{\"open\":null,\"close\":null},\"Sunday\":{\"o', 10, 10.00, 50.00, 3, 'PHP', 'Asia/Manila', 'logo.png');

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
  `user_password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `lms_user`
--

INSERT INTO `lms_user` (`user_id`, `user_name`, `user_address`, `user_contact_no`, `user_profile`, `user_email`, `user_password`, `role_id`, `user_verification_code`, `user_verification_status`, `user_unique_id`, `user_status`, `user_created_on`, `user_updated_on`) VALUES
(3, 'Paul Black', '4016 Goldie Lane Cincinnati, OH 45202', '7539518520', '1636699900-2617.jpg', 'paulblake@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, 'b190bcd6e3b29674db036670cf122724', 'Yes', 'U82514529', 'Enable', '2021-11-12 04:21:40', '2025-03-31 15:33:13'),
(4, 'Aaron Lawler', '1616 Broadway Avenue Chattanooga, TN 37421', '8569856321', '1636905360-32007.jpg', 'aaronlawler@live.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 3, 'add84abb895484d12344316eccb78a62', 'Yes', 'F37570190', 'Enable', '2021-11-12 08:39:20', '2025-03-31 15:33:13'),
(5, 'Kathleen Forrest', '4545 Limer Street Greensboro, GA 30642', '85214796930', '1637041684-15131.jpg', 'kathleen@hotmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 4, '7013df5205011ffcb99ea57902c17369', 'Yes', 'S24567871', 'Enable', '2021-11-16 03:18:04', '2025-03-31 15:33:13'),
(6, 'Carol Maney', '2703 Deer Haven Drive Greenville, SC 29607', '8521479630', '1637126571-21753.jpg', 'web-tutorial1@programmer.net', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, 'a6c2623984d590239244f8695df3a30b', 'Yes', 'U52357788', 'Enable', '2021-11-17 02:52:51', '2025-03-31 15:33:13'),
(10, 'Kevin Peterson', '1889 Single Street Waltham, MA 02154', '8523698520', '1639658464-10192.jpg', 'web-tutorial@programmer.net', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, '337ea20da40326d134fe5eca3fb03464', 'Yes', 'U59564819', 'Enable', '2021-12-14 04:56:29', '2025-03-31 15:33:13'),
(11, 'Faye Lacsi', '', '09823830938478', 'user_1744669708_67fd8c0c6038a.png', 'lacsi@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 4, '', 'No', 'S17845470', 'Enable', '2025-03-20 10:10:18', '2025-04-14 22:28:28'),
(12, 'Jake Bruce', '', '09736788383', 'user.jpg', 'bruce@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, '', '', 'U41683769', 'Disable', '2025-03-20 06:02:39', '2025-04-14 22:28:46'),
(13, 'Derriel', '', '093789383837737', 'user.jpg', 'derriel@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, '', '', 'U54882437', 'Enable', '2025-03-20 06:03:24', '2025-03-31 15:33:13'),
(14, 'gina', '', '0965688765', 'user.jpg', 'gina@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, '', '', 'U49360880', 'Enable', '2025-03-20 06:04:40', '2025-03-31 15:33:13'),
(15, 'Kate Pink', '', '0973787366', 'user.jpg', 'pink@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, '', 'No', 'U82157096', 'Enable', '2025-03-20 10:41:02', '2025-04-14 22:28:42'),
(16, 'Barbie Blue', '', '0973837663', 'user.jpg', 'barbie@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, '', 'No', 'U62702842', 'Enable', '2025-03-20 10:46:05', '2025-03-31 15:33:13'),
(17, 'April Manalo', '', '09974749474', 'user.jpg', 'manalo@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, '', 'No', 'U67042925', 'Enable', '2025-03-20 10:58:39', '2025-03-31 15:33:13'),
(18, 'gina thong', 'curuan', '0987544613', '1743038547-737356096.jpg', 'teff.wong@gmail.com', '$2y$10$rjBqnSr3YC9C/qUOPWMVXeaD5ODxZxwIV3QG.Fbf5Ot3IODv69Cru', 5, 'f7ce5b2b89ccbe2430fb8cd54b9426b7', 'No', 'U47091166', 'Enable', '2025-03-26 22:52:27', '2025-03-31 15:33:13'),
(19, 'wassup', '', '097784656646', 'user_1744670305_67fd8e61f0879.png', 'test@gmail.com', '$2y$10$TYDFkNa22EWoFAFOFmNmCep3oY/mfL1vhyVxFQhhIhtF.z3YYQ9Me', 4, '', 'No', 'S53248112', 'Enable', '2025-04-14 22:29:53', '2025-04-14 22:38:25');

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
-- Constraints for table `lms_book_review`
--
ALTER TABLE `lms_book_review`
  ADD CONSTRAINT `fk_book_review_book` FOREIGN KEY (`book_id`) REFERENCES `lms_book` (`book_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_book_review_user` FOREIGN KEY (`user_id`) REFERENCES `lms_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_book` FOREIGN KEY (`book_id`) REFERENCES `lms_book` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `lms_user` (`user_id`) ON DELETE CASCADE;

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
