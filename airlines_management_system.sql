-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 07:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `airlines`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `email`, `full_name`, `created_at`) VALUES
(1, 'karen_elisha', '$2y$10$EynWZZeeUiOPTKWxNoHB3uMxlbEpw4obFm5RuaL3/pLe1NA1JuzuG', 'karenelisha0204@gmail.com', 'Karen Elisha Chezhiyan', '2025-05-17 09:46:50'),
(2, 'ritvik_medam', '$2y$10$9wwg83mk1BWsQNQSS8iDT.Eclgy.41lm1pTdI7eCliMHln2Ky/l0u', 'ritvik.medam@gmail.com', 'Medam Ritvik', '2025-05-22 07:18:49');

-- --------------------------------------------------------

--
-- Table structure for table `airlines`
--

CREATE TABLE `airlines` (
  `airline_id` varchar(5) NOT NULL,
  `airline_name` varchar(100) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `customer_care` varchar(50) DEFAULT NULL,
  `contact_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `airlines`
--

INSERT INTO `airlines` (`airline_id`, `airline_name`, `logo_url`, `website`, `customer_care`, `contact_url`, `active`) VALUES
('6E', 'IndiGo', 'logos/indigo.png', 'https://goindigo.in', '0124-6173838', 'https://goindigo.in/contact', 1),
('AA', 'Alliance Air', 'https://allianceair.in/logo.png', 'https://allianceair.in', '1800-180-1407', 'https://allianceair.in/contact', 1),
('AAI', 'AirAsia India', 'https://airasia.com/logo.png', 'https://airasia.com', '080-4747-7474', 'https://airasia.com/contact', 1),
('AI', 'Air India', 'logos/ai.png', NULL, NULL, NULL, 1),
('FB', 'FlyBig', 'https://flybig.in/logo.png', 'https://flybig.in', '0755-6614141', 'https://flybig.in/contact', 1),
('GF', 'Go First', 'https://gofirst.in/logo.png', 'https://gofirst.in', '1800-210-0999', 'https://gofirst.in/contact', 1),
('SA', 'Star Air', 'https://starair.in/logo.png', 'https://starair.in', '1800-425-1111', 'https://starair.in/contact', 1),
('SG', 'SpiceJet', 'logos/spicejet.png', NULL, NULL, NULL, 1),
('TJ', 'TruJet', 'https://www.google.com/imgres?q=https%3A%2F%2Ftrujet.in%2Flogo.png&imgurl=https%3A%2F%2Fairhex.com%2Fimages%2Fairline-logos%2Ftrujet.png&imgrefurl=https%3A%2F%2Fairhex.com%2Fairline-logos%2Ftrujet%2F&docid=YauCG4_VdZQY8M&tbnid=YquCLu2TSJ5EiM&vet=12ahUKEwi', 'https://trujet.com', '040-67137137', 'https://trujet.com/contact', 1),
('UK', 'Vistara', 'logos/vistara.png', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `airports`
--

CREATE TABLE `airports` (
  `airport_id` varchar(10) NOT NULL,
  `airport_name` varchar(100) NOT NULL,
  `city` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `timezone` varchar(50) NOT NULL,
  `airport_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `airports`
--

INSERT INTO `airports` (`airport_id`, `airport_name`, `city`, `country`, `timezone`, `airport_code`) VALUES
('AMD', 'Sardar Vallabhbhai Patel International Airport', 'Ahmedabad', 'India', 'IST', 'AMD'),
('BLR', 'Kempegowda International Airport', 'Bangalore', 'India', 'Asia/Kolkata', 'BLR'),
('BOM', 'Chhatrapati Shivaji International Airport', 'Mumbai', 'India', 'Asia/Kolkata', 'BOM'),
('CCU', 'Netaji Subhash Chandra Bose International Airport', 'Kolkata', 'India', 'Asia/Kolkata', 'CCU'),
('COK', 'Cochin International Airport', 'Kochi', 'India', 'IST', 'COK'),
('DEL', 'Indira Gandhi International Airport', 'Delhi', 'India', 'Asia/Kolkata', 'DEL'),
('GOI', 'Goa International Airport', 'Goa', 'India', 'IST', 'GOI'),
('HYD', 'Rajiv Gandhi International Airport', 'Hyderabad', 'India', 'Asia/Kolkata', 'HYD'),
('MAA', 'Chennai International Airport', 'Chennai', 'India', 'Asia/Kolkata', 'MAA'),
('PNQ', 'Pune Airport', 'Pune', 'India', 'IST', 'PNQ');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `booking_date` datetime NOT NULL,
  `travel_date` date NOT NULL,
  `num_passengers` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `booking_status` enum('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Pending',
  `payment_status` enum('Pending','Completed','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `payment_details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `booking_reference`, `user_id`, `flight_id`, `booking_date`, `travel_date`, `num_passengers`, `total_amount`, `contact_email`, `contact_phone`, `booking_status`, `payment_status`, `payment_method`, `created_at`, `updated_at`, `payment_details`) VALUES
(37, 'BK-682F016E4EE54', 10, 1001, '2025-05-22 12:50:22', '2025-05-30', 1, 4719.00, 'kevinjoseph@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-22 16:20:36', 'kevin@okaxis'),
(38, 'BK-682F167A0973E', 10, 1003, '2025-05-22 14:20:10', '2025-05-29', 1, 6253.00, 'chezanand@gmail.com', '9886026336', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-22 17:50:18', 'kevin@okaxis'),
(39, 'BK-682F1E1C9D99A', 10, 1049, '2025-05-22 14:52:44', '2025-05-23', 1, 6549.00, 'harry@gmail.com', '9591553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-22 18:22:56', 'harry@okaxis'),
(40, 'BK-682F20098FC45', 10, 1049, '2025-05-22 15:00:57', '2025-05-23', 1, 6549.00, 'medamritvik@gmail.com', '7013631447', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-22 18:31:03', 'karen@okaxis'),
(41, 'BK-682F52C759C3C', 10, 1685, '2025-05-22 18:37:27', '2025-05-28', 1, 3835.00, 'harry@gmail.com', '9591553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-22 22:07:32', 'harry@okaxis'),
(42, 'BK-682FFC0A17426', 10, 1046, '2025-05-23 06:39:38', '2025-05-23', 1, 3953.00, 'karenelisha0204@gmail.com', '9591553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 10:09:46', 'karen@okaxis'),
(43, 'BK-6830105F39445', 11, 1706, '2025-05-23 08:06:23', '2025-05-30', 1, 5487.00, 'dhanya@gmail.com', '7013631447', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 11:38:31', 'dhanya@okaxis'),
(44, 'BK-683027B09C896', 12, 1711, '2025-05-23 09:45:52', '2025-05-30', 1, 3717.00, 'robert@gmail.com', '9591553820', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 13:27:25', 'robert@okaxis'),
(45, 'BK-68304D32A76A0', 12, 1652, '2025-05-23 12:25:54', '2025-05-24', 1, 4307.00, 'karenelisha0204@gmail.com', '7338553820', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 16:01:31', 'kevin@okaxis'),
(46, 'BK-68304F7C62478', 12, 1652, '2025-05-23 12:35:40', '2025-05-24', 1, 4307.00, 'harry@gmail.com', '7338553820', 'Cancelled', 'Pending', 'credit_card', '0000-00-00 00:00:00', '2025-05-23 16:31:40', NULL),
(47, 'BK-68304FB37C147', 12, 1652, '2025-05-23 12:36:35', '2025-05-24', 1, 4307.00, 'harry@gmail.com', '7338553820', 'Cancelled', 'Pending', 'credit_card', '0000-00-00 00:00:00', '2025-05-23 16:21:24', NULL),
(48, 'BK-68305284B0AF9', 12, 1656, '2025-05-23 12:48:36', '2025-05-25', 1, 3953.00, 'harry@gmail.com', '7338553820', 'Pending', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 16:20:23', 'kevin@okaxis'),
(49, 'BK-68308C6D9392D', 12, 1656, '2025-05-23 16:55:41', '2025-05-25', 1, 3953.00, 'karenelisha0204@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 20:30:53', 'karen@okaxis'),
(50, 'BK-68309F8744971', 12, 1656, '2025-05-23 18:17:11', '2025-05-25', 1, 5930.00, 'harry@gmail.com', '7013631447', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 21:48:23', 'karen@okaxis'),
(51, 'BK-6830A6836E354', 12, 1656, '2025-05-23 18:46:59', '2025-05-25', 1, 5930.00, 'harry@gmail.com', '7013631447', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-24 11:10:45', 'karen@okaxis'),
(52, 'BK-6830A899E9AE3', 12, 1656, '2025-05-23 18:55:53', '2025-05-25', 1, 3953.00, 'harry@gmail.com', '7013631447', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-23 22:26:00', 'karen@okaxis'),
(53, 'BK-6830AF46CC883', 12, 1656, '2025-05-23 19:24:22', '2025-05-25', 1, 3953.00, 'harry@gmail.com', '7013631447', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-24 10:43:20', 'karen@okaxis'),
(54, 'BK-68316436A3F44', 11, 1648, '2025-05-24 08:16:22', '2025-05-24', 1, 4543.00, 'dhanya@gmail.com', '7013631447', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-24 11:46:31', 'karen@okaxis'),
(55, 'BK-68316516ABC50', 11, 1652, '2025-05-24 08:20:06', '2025-05-24', 1, 4307.00, 'medamritvik@gmail.com', '7013631447', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-24 11:50:15', 'karen@okaxis'),
(56, 'BK-68317D06E612F', 13, 1645, '2025-05-24 10:02:14', '2025-05-24', 1, 3835.00, 'jayden@gmail.com', '9886026336', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-24 13:46:26', 'jayden@okaxis'),
(57, 'BK-68317DA355654', 13, 1645, '2025-05-24 10:04:51', '2025-05-24', 1, 3835.00, 'jayden@gmail.com', '9886026336', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-24 13:35:19', 'robert@okaxis'),
(58, 'BK-68371BF6D5D83', 5, 1696, '2025-05-28 16:21:42', '2025-05-29', 1, 3953.00, 'chezanand@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(59, 'BK-68371BFEC1D53', 5, 1696, '2025-05-28 16:21:50', '2025-05-29', 1, 3953.00, 'chezanand@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-28 19:51:57', 'karen@okaxis'),
(60, 'BK-68371CB2A1779', 5, 1696, '2025-05-28 16:24:50', '2025-05-29', 1, 3953.00, 'karenelisha0204@gmail.com', '7013631447', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-28 19:58:21', 'karen@okaxis'),
(61, 'BK-683720CBD2B5C', 5, 1696, '2025-05-28 16:42:19', '2025-05-29', 1, 3953.00, 'harry@gmail.com', '9591553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-28 20:12:34', 'harry@okaxis'),
(62, 'BK-683721A70303B', 5, 1335, '2025-05-28 16:45:59', '2025-06-01', 1, 3835.00, 'karenelisha0204@gmail.com', '7338553820', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-28 21:04:37', 'karen@okaxis'),
(63, 'BK-683723224FD00', 5, 1335, '2025-05-28 16:52:18', '2025-06-01', 1, 3835.00, 'harry@gmail.com', '9886026336', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-28 20:22:29', 'karen@okaxis'),
(64, 'BK-68372BFF71D86', 5, 1696, '2025-05-28 17:30:07', '2025-05-29', 1, 3953.00, 'harry@gmail.com', '9886026336', 'Cancelled', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-28 21:18:59', 'kevin@okaxis');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `passenger_name` varchar(100) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `flight_number` varchar(20) NOT NULL,
  `journey_date` date NOT NULL,
  `overall_rating` int(1) NOT NULL,
  `punctuality` varchar(20) NOT NULL,
  `additional_feedback` text DEFAULT NULL,
  `complaint_type` varchar(50) DEFAULT NULL,
  `complaint_details` text DEFAULT NULL,
  `contact_info` varchar(100) DEFAULT NULL,
  `website_experience` varchar(20) NOT NULL,
  `website_feedback` text DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `passenger_name`, `flight_id`, `flight_number`, `journey_date`, `overall_rating`, `punctuality`, `additional_feedback`, `complaint_type`, `complaint_details`, `contact_info`, `website_experience`, `website_feedback`, `submission_date`) VALUES
(1, 'Karen', 1043, '6E146', '2025-05-30', 0, 'Yes', 'Good', NULL, NULL, NULL, 'Good', '', '2025-05-23 17:15:01'),
(2, 'Karen', 1043, '', '2025-05-30', 0, 'Yes', 'Good', NULL, NULL, NULL, 'Good', '', '2025-05-23 17:16:49'),
(3, 'Karen', 1043, '', '2025-05-30', 0, 'Yes', 'Good', NULL, NULL, NULL, 'Good', '', '2025-05-23 17:20:28'),
(4, 'Ritvik', 1694, '6E1051', '2025-05-26', 0, 'Yes', '', NULL, NULL, NULL, 'Good', '', '2025-05-24 06:37:53'),
(5, 'John', 1019, '6E717', '2025-05-21', 4, 'Yes', NULL, NULL, NULL, NULL, 'Excellent', 'Great', '2025-05-28 09:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `flight_id` int(11) NOT NULL,
  `flight_number` varchar(10) NOT NULL,
  `airline_id` varchar(5) NOT NULL,
  `origin_airport` varchar(10) NOT NULL,
  `destination_airport` varchar(10) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `duration` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `available_seats` int(11) NOT NULL,
  `flight_status` enum('Scheduled','Delayed','Departed','Arrived','Cancelled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`flight_id`, `flight_number`, `airline_id`, `origin_airport`, `destination_airport`, `departure_time`, `arrival_time`, `duration`, `base_price`, `total_seats`, `available_seats`, `flight_status`) VALUES
(1001, '6E101', '6E', 'DEL', 'BOM', '2025-05-22 06:00:00', '2023-05-25 08:15:00', 90, 5600.00, 180, 100, 'Scheduled'),
(1002, 'AI202', 'AI', 'BOM', 'BLR', '2023-12-20 09:30:00', '2023-12-20 11:00:00', 90, 4999.00, 160, 145, 'Scheduled'),
(1003, '6E101', '6E', 'DEL', 'BOM', '2025-05-20 06:00:00', '2025-05-20 08:15:00', 135, 4500.00, 180, 45, ''),
(1005, 'GF303', 'GF', 'BLR', 'DEL', '2025-05-20 09:00:00', '2025-05-20 11:30:00', 150, 5800.00, 144, 18, ''),
(1006, 'AA404', 'AA', 'MAA', 'HYD', '2025-05-20 10:15:00', '2025-05-20 11:30:00', 75, 3200.00, 186, 56, ''),
(1007, 'UK505', 'UK', 'DEL', 'CCU', '2025-05-20 11:45:00', '2025-05-20 14:00:00', 135, 6100.00, 144, 12, ''),
(1008, 'SG606', 'SG', 'BOM', 'GOI', '2025-05-20 13:00:00', '2025-05-20 14:15:00', 75, 3800.00, 180, 34, ''),
(1009, '6E707', '6E', 'HYD', 'DEL', '2025-05-20 14:30:00', '2025-05-20 16:45:00', 135, 4700.00, 180, 28, ''),
(1010, 'AI808', 'AI', 'CCU', 'BOM', '2025-05-20 16:00:00', '2025-05-20 18:30:00', 150, 5400.00, 160, 15, ''),
(1011, 'GF909', 'GF', 'DEL', 'BLR', '2025-05-20 17:30:00', '2025-05-20 20:00:00', 150, 5900.00, 144, 9, ''),
(1012, 'AA110', 'AA', 'BLR', 'MAA', '2025-05-20 19:00:00', '2025-05-20 20:15:00', 75, 3100.00, 186, 42, ''),
(1013, '6E111', '6E', 'DEL', 'BOM', '2025-05-21 06:00:00', '2025-05-21 08:15:00', 135, 4500.00, 180, 38, ''),
(1014, 'AI212', 'AI', 'BOM', 'BLR', '2025-05-21 07:30:00', '2025-05-21 09:15:00', 105, 5200.00, 160, 19, ''),
(1015, 'GF313', 'GF', 'BLR', 'DEL', '2025-05-21 09:00:00', '2025-05-21 11:30:00', 150, 5800.00, 144, 14, 'Delayed'),
(1016, 'AA414', 'AA', 'MAA', 'HYD', '2025-05-21 10:15:00', '2025-05-21 11:30:00', 75, 3200.00, 186, 51, ''),
(1017, 'UK515', 'UK', 'DEL', 'CCU', '2025-05-21 11:45:00', '2025-05-21 14:00:00', 135, 6100.00, 144, 8, ''),
(1018, 'SG616', 'SG', 'BOM', 'GOI', '2025-05-21 13:00:00', '2025-05-21 14:15:00', 75, 3800.00, 180, 29, ''),
(1019, '6E717', '6E', 'HYD', 'DEL', '2025-05-21 14:30:00', '2025-05-21 16:45:00', 135, 4700.00, 180, 24, ''),
(1020, 'AI818', 'AI', 'CCU', 'BOM', '2025-05-21 16:00:00', '2025-05-21 18:30:00', 150, 5400.00, 160, 12, 'Cancelled'),
(1021, 'GF919', 'GF', 'DEL', 'BLR', '2025-05-21 17:30:00', '2025-05-21 20:00:00', 150, 5900.00, 144, 6, ''),
(1022, 'AA120', 'AA', 'BLR', 'MAA', '2025-05-21 19:00:00', '2025-05-21 20:15:00', 75, 3100.00, 186, 37, ''),
(1023, '6E121', '6E', 'DEL', 'BOM', '2025-05-22 06:00:00', '2025-05-22 08:15:00', 135, 4500.00, 180, 32, ''),
(1024, 'AI222', 'AI', 'BOM', 'BLR', '2025-05-22 07:30:00', '2025-05-22 09:15:00', 105, 5200.00, 160, 16, 'Scheduled'),
(1025, 'GF323', 'GF', 'BLR', 'DEL', '2025-05-22 09:00:00', '2025-05-22 11:30:00', 150, 5800.00, 144, 11, 'Delayed'),
(1026, 'AA424', 'AA', 'MAA', 'HYD', '2025-05-22 10:15:00', '2025-05-22 11:30:00', 75, 3200.00, 186, 46, ''),
(1027, 'UK525', 'UK', 'DEL', 'CCU', '2025-05-22 11:45:00', '2025-05-22 14:00:00', 135, 6100.00, 144, 5, ''),
(1028, 'SG626', 'SG', 'BOM', 'GOI', '2025-05-22 13:00:00', '2025-05-22 14:15:00', 75, 3800.00, 180, 24, ''),
(1029, '6E727', '6E', 'HYD', 'DEL', '2025-05-22 14:30:00', '2025-05-22 16:45:00', 135, 4700.00, 180, 19, ''),
(1030, 'AI828', 'AI', 'CCU', 'BOM', '2025-05-22 16:00:00', '2025-05-22 18:30:00', 150, 5400.00, 160, 9, ''),
(1031, 'GF929', 'GF', 'DEL', 'BLR', '2025-05-22 17:30:00', '2025-05-22 20:00:00', 150, 5900.00, 144, 3, 'Scheduled'),
(1032, 'AA130', 'AA', 'BLR', 'MAA', '2025-05-22 19:00:00', '2025-05-22 20:15:00', 75, 3100.00, 186, 32, ''),
(1033, '6E134', '6E', 'DEL', 'BOM', '2025-05-22 06:30:00', '2025-05-22 08:45:00', 135, 4550.00, 180, 112, 'Scheduled'),
(1034, 'AI245', 'AI', 'BOM', 'BLR', '2025-05-22 08:00:00', '2025-05-22 09:45:00', 105, 5250.00, 160, 87, 'Scheduled'),
(1035, 'GF356', 'GF', 'BLR', 'HYD', '2025-05-22 09:30:00', '2025-05-22 10:45:00', 75, 3250.00, 144, 92, 'Scheduled'),
(1036, 'AA467', 'AA', 'HYD', 'DEL', '2025-05-22 11:00:00', '2025-05-22 13:15:00', 135, 4650.00, 186, 104, 'Scheduled'),
(1037, 'UK578', 'UK', 'DEL', 'GOI', '2025-05-22 12:30:00', '2025-05-22 14:45:00', 135, 6150.00, 144, 76, 'Scheduled'),
(1038, 'SG689', 'SG', 'GOI', 'BOM', '2025-05-22 14:00:00', '2025-05-22 15:15:00', 75, 3850.00, 180, 128, 'Scheduled'),
(1039, 'FB790', 'FB', 'BOM', 'CCU', '2025-05-22 15:30:00', '2025-05-22 18:00:00', 150, 5450.00, 160, 93, 'Scheduled'),
(1040, 'SA801', 'SA', 'CCU', 'MAA', '2025-05-22 17:00:00', '2025-05-22 19:30:00', 150, 5950.00, 144, 67, 'Scheduled'),
(1041, 'TJ912', 'TJ', 'MAA', 'COK', '2025-05-22 18:30:00', '2025-05-22 19:45:00', 75, 3150.00, 186, 142, 'Scheduled'),
(1042, 'AAI023', 'AAI', 'COK', 'AMD', '2025-05-22 20:00:00', '2025-05-22 21:30:00', 90, 3650.00, 160, 118, 'Scheduled'),
(1043, '6E146', '6E', 'AMD', 'DEL', '2025-05-23 06:30:00', '2025-05-23 08:15:00', 105, 4350.00, 180, 108, 'Scheduled'),
(1044, 'AI257', 'AI', 'DEL', 'PNQ', '2025-05-23 08:00:00', '2025-05-23 09:30:00', 90, 4150.00, 160, 82, 'Scheduled'),
(1045, 'GF368', 'GF', 'PNQ', 'BLR', '2025-05-23 09:30:00', '2025-05-23 11:00:00', 90, 3950.00, 144, 96, 'Scheduled'),
(1046, 'AA479', 'AA', 'BLR', 'HYD', '2025-05-23 11:00:00', '2025-05-23 12:15:00', 75, 3350.00, 186, 101, 'Scheduled'),
(1047, 'UK580', 'UK', 'HYD', 'BOM', '2025-05-23 12:30:00', '2025-05-23 14:00:00', 90, 4850.00, 144, 71, 'Scheduled'),
(1048, 'SG691', 'SG', 'BOM', 'GOI', '2025-05-23 14:00:00', '2025-05-23 15:15:00', 75, 3750.00, 180, 124, 'Scheduled'),
(1049, 'FB702', 'FB', 'GOI', 'CCU', '2025-05-23 15:30:00', '2025-05-23 18:15:00', 165, 5550.00, 160, 88, 'Scheduled'),
(1050, 'SA813', 'SA', 'CCU', 'MAA', '2025-05-23 17:00:00', '2025-05-23 19:30:00', 150, 6050.00, 144, 62, 'Scheduled'),
(1051, 'TJ924', 'TJ', 'MAA', 'COK', '2025-05-23 18:30:00', '2025-05-23 19:45:00', 75, 3250.00, 186, 138, 'Scheduled'),
(1052, 'AAI035', 'AAI', 'COK', 'AMD', '2025-05-23 20:00:00', '2025-05-23 21:30:00', 90, 3550.00, 160, 115, 'Scheduled'),
(1333, '6E158', '6E', 'DEL', 'BOM', '2025-06-01 06:30:00', '2025-06-01 08:45:00', 135, 4550.00, 180, 110, 'Scheduled'),
(1334, 'AI269', 'AI', 'BOM', 'BLR', '2025-06-01 08:00:00', '2025-06-01 09:45:00', 105, 5250.00, 160, 85, 'Scheduled'),
(1335, 'GF370', 'GF', 'BLR', 'HYD', '2025-06-01 09:30:00', '2025-06-01 10:45:00', 75, 3250.00, 144, 90, 'Scheduled'),
(1336, 'AA481', 'AA', 'HYD', 'DEL', '2025-06-01 11:00:00', '2025-06-01 13:15:00', 135, 4650.00, 186, 102, 'Scheduled'),
(1337, 'UK592', 'UK', 'DEL', 'GOI', '2025-06-01 12:30:00', '2025-06-01 14:45:00', 135, 6150.00, 144, 74, 'Scheduled'),
(1338, 'SG603', 'SG', 'GOI', 'BOM', '2025-06-01 14:00:00', '2025-06-01 15:15:00', 75, 3850.00, 180, 126, 'Scheduled'),
(1339, 'FB714', 'FB', 'BOM', 'CCU', '2025-06-01 15:30:00', '2025-06-01 18:00:00', 150, 5450.00, 160, 91, 'Scheduled'),
(1340, 'SA825', 'SA', 'CCU', 'MAA', '2025-06-01 17:00:00', '2025-06-01 19:30:00', 150, 5950.00, 144, 65, 'Scheduled'),
(1341, 'TJ936', 'TJ', 'MAA', 'COK', '2025-06-01 18:30:00', '2025-06-01 19:45:00', 75, 3150.00, 186, 140, 'Scheduled'),
(1342, 'AAI047', 'AAI', 'COK', 'AMD', '2025-06-01 20:00:00', '2025-06-01 21:30:00', 90, 3650.00, 160, 116, 'Scheduled'),
(1633, '6E170', '6E', 'AMD', 'DEL', '2025-06-30 06:30:00', '2025-06-30 08:15:00', 105, 4350.00, 180, 106, 'Scheduled'),
(1634, 'AI281', 'AI', 'DEL', 'PNQ', '2025-06-30 08:00:00', '2025-06-30 09:30:00', 90, 4150.00, 160, 80, 'Scheduled'),
(1635, 'GF392', 'GF', 'PNQ', 'BLR', '2025-06-30 09:30:00', '2025-06-30 11:00:00', 90, 3950.00, 144, 94, 'Scheduled'),
(1636, 'AA403', 'AA', 'BLR', 'HYD', '2025-06-30 11:00:00', '2025-06-30 12:15:00', 75, 3350.00, 186, 99, 'Scheduled'),
(1637, 'UK514', 'UK', 'HYD', 'BOM', '2025-06-30 12:30:00', '2025-06-30 14:00:00', 90, 4850.00, 144, 69, 'Scheduled'),
(1638, 'SG625', 'SG', 'BOM', 'GOI', '2025-06-30 14:00:00', '2025-06-30 15:15:00', 75, 3750.00, 180, 122, 'Scheduled'),
(1639, 'FB736', 'FB', 'GOI', 'CCU', '2025-06-30 15:30:00', '2025-06-30 18:15:00', 165, 5550.00, 160, 86, 'Scheduled'),
(1640, 'SA847', 'SA', 'CCU', 'MAA', '2025-06-30 17:00:00', '2025-06-30 19:30:00', 150, 6050.00, 144, 60, 'Scheduled'),
(1641, 'TJ958', 'TJ', 'MAA', 'COK', '2025-06-30 18:30:00', '2025-06-30 19:45:00', 75, 3250.00, 186, 136, 'Scheduled'),
(1642, 'AAI059', 'AAI', 'COK', 'AMD', '2025-06-30 20:00:00', '2025-06-30 21:30:00', 90, 3550.00, 160, 113, 'Scheduled'),
(1643, 'AI1000', 'AI', 'DEL', 'BOM', '2025-05-24 06:30:00', '2025-05-24 08:45:00', 135, 4550.00, 180, 112, 'Scheduled'),
(1644, '6E1001', '6E', 'BOM', 'BLR', '2025-05-24 08:00:00', '2025-05-24 09:45:00', 105, 5250.00, 160, 87, 'Scheduled'),
(1645, 'GF1002', 'GF', 'BLR', 'HYD', '2025-05-24 09:30:00', '2025-05-24 10:45:00', 75, 3250.00, 144, 92, 'Scheduled'),
(1646, 'AA1003', 'AA', 'HYD', 'DEL', '2025-05-24 11:00:00', '2025-05-24 13:15:00', 135, 4650.00, 186, 104, 'Scheduled'),
(1647, 'UK1004', 'UK', 'DEL', 'GOI', '2025-05-24 12:30:00', '2025-05-24 14:45:00', 135, 6150.00, 144, 76, 'Scheduled'),
(1648, 'SG1005', 'SG', 'GOI', 'BOM', '2025-05-24 14:00:00', '2025-05-24 15:15:00', 75, 3850.00, 180, 128, 'Scheduled'),
(1649, 'FB1006', 'FB', 'BOM', 'CCU', '2025-05-24 15:30:00', '2025-05-24 18:00:00', 150, 5450.00, 160, 93, 'Scheduled'),
(1650, 'SA1007', 'SA', 'CCU', 'MAA', '2025-05-24 17:00:00', '2025-05-24 19:30:00', 150, 5950.00, 144, 67, 'Scheduled'),
(1651, 'TJ1008', 'TJ', 'MAA', 'COK', '2025-05-24 18:30:00', '2025-05-24 19:45:00', 75, 3150.00, 186, 142, 'Scheduled'),
(1652, 'AAI1009', 'AAI', 'COK', 'AMD', '2025-05-24 20:00:00', '2025-05-24 21:30:00', 90, 3650.00, 160, 118, 'Scheduled'),
(1653, 'AI1010', 'AI', 'AMD', 'DEL', '2025-05-25 06:30:00', '2025-05-25 08:15:00', 105, 4350.00, 180, 108, 'Scheduled'),
(1654, '6E1011', '6E', 'DEL', 'PNQ', '2025-05-25 08:00:00', '2025-05-25 09:30:00', 90, 4150.00, 160, 82, 'Scheduled'),
(1655, 'GF1012', 'GF', 'PNQ', 'BLR', '2025-05-25 09:30:00', '2025-05-25 11:00:00', 90, 3950.00, 144, 96, 'Scheduled'),
(1656, 'AA1013', 'AA', 'BLR', 'HYD', '2025-05-25 11:00:00', '2025-05-25 12:15:00', 75, 3350.00, 186, 101, 'Scheduled'),
(1657, 'UK1014', 'UK', 'HYD', 'BOM', '2025-05-25 12:30:00', '2025-05-25 14:00:00', 90, 4850.00, 144, 71, 'Scheduled'),
(1658, 'SG1015', 'SG', 'BOM', 'GOI', '2025-05-25 14:00:00', '2025-05-25 15:15:00', 75, 3750.00, 180, 124, 'Scheduled'),
(1659, 'FB1016', 'FB', 'GOI', 'CCU', '2025-05-25 15:30:00', '2025-05-25 18:15:00', 165, 5550.00, 160, 88, 'Scheduled'),
(1660, 'SA1017', 'SA', 'CCU', 'MAA', '2025-05-25 17:00:00', '2025-05-25 19:30:00', 150, 6050.00, 144, 62, 'Scheduled'),
(1661, 'TJ1018', 'TJ', 'MAA', 'COK', '2025-05-25 18:30:00', '2025-05-25 19:45:00', 75, 3250.00, 186, 138, 'Scheduled'),
(1662, 'AAI1019', 'AAI', 'COK', 'AMD', '2025-05-25 20:00:00', '2025-05-25 21:30:00', 90, 3550.00, 160, 115, 'Scheduled'),
(1663, 'AI1020', 'AI', 'DEL', 'BOM', '2025-05-26 06:30:00', '2025-05-26 08:45:00', 135, 4550.00, 180, 110, 'Scheduled'),
(1664, '6E1021', '6E', 'BOM', 'BLR', '2025-05-26 08:00:00', '2025-05-26 09:45:00', 105, 5250.00, 160, 85, 'Scheduled'),
(1665, 'GF1022', 'GF', 'BLR', 'HYD', '2025-05-26 09:30:00', '2025-05-26 10:45:00', 75, 3250.00, 144, 90, 'Scheduled'),
(1666, 'AA1023', 'AA', 'HYD', 'DEL', '2025-05-26 11:00:00', '2025-05-26 13:15:00', 135, 4650.00, 186, 102, 'Scheduled'),
(1667, 'UK1024', 'UK', 'DEL', 'GOI', '2025-05-26 12:30:00', '2025-05-26 14:45:00', 135, 6150.00, 144, 74, 'Scheduled'),
(1668, 'SG1025', 'SG', 'GOI', 'BOM', '2025-05-26 14:00:00', '2025-05-26 15:15:00', 75, 3850.00, 180, 126, 'Scheduled'),
(1669, 'FB1026', 'FB', 'BOM', 'CCU', '2025-05-26 15:30:00', '2025-05-26 18:00:00', 150, 5450.00, 160, 91, 'Scheduled'),
(1670, 'SA1027', 'SA', 'CCU', 'MAA', '2025-05-26 17:00:00', '2025-05-26 19:30:00', 150, 5950.00, 144, 65, 'Scheduled'),
(1671, 'TJ1028', 'TJ', 'MAA', 'COK', '2025-05-26 18:30:00', '2025-05-26 19:45:00', 75, 3150.00, 186, 140, 'Scheduled'),
(1672, 'AAI1029', 'AAI', 'COK', 'AMD', '2025-05-26 20:00:00', '2025-05-26 21:30:00', 90, 3650.00, 160, 116, 'Scheduled'),
(1673, 'AI1030', 'AI', 'AMD', 'DEL', '2025-05-27 06:30:00', '2025-05-27 08:15:00', 105, 4350.00, 180, 106, 'Scheduled'),
(1674, '6E1031', '6E', 'DEL', 'PNQ', '2025-05-27 08:00:00', '2025-05-27 09:30:00', 90, 4150.00, 160, 80, 'Scheduled'),
(1675, 'GF1032', 'GF', 'PNQ', 'BLR', '2025-05-27 09:30:00', '2025-05-27 11:00:00', 90, 3950.00, 144, 94, 'Scheduled'),
(1676, 'AA1033', 'AA', 'BLR', 'HYD', '2025-05-27 11:00:00', '2025-05-27 12:15:00', 75, 3350.00, 186, 99, 'Scheduled'),
(1677, 'UK1034', 'UK', 'HYD', 'BOM', '2025-05-27 12:30:00', '2025-05-27 14:00:00', 90, 4850.00, 144, 69, 'Scheduled'),
(1678, 'SG1035', 'SG', 'BOM', 'GOI', '2025-05-27 14:00:00', '2025-05-27 15:15:00', 75, 3750.00, 180, 122, 'Scheduled'),
(1679, 'FB1036', 'FB', 'GOI', 'CCU', '2025-05-27 15:30:00', '2025-05-27 18:15:00', 165, 5550.00, 160, 86, 'Scheduled'),
(1680, 'SA1037', 'SA', 'CCU', 'MAA', '2025-05-27 17:00:00', '2025-05-27 19:30:00', 150, 6050.00, 144, 60, 'Scheduled'),
(1681, 'TJ1038', 'TJ', 'MAA', 'COK', '2025-05-27 18:30:00', '2025-05-27 19:45:00', 75, 3250.00, 186, 136, 'Scheduled'),
(1682, 'AAI1039', 'AAI', 'COK', 'AMD', '2025-05-27 20:00:00', '2025-05-27 21:30:00', 90, 3550.00, 160, 113, 'Scheduled'),
(1683, 'AI1040', 'AI', 'DEL', 'BOM', '2025-05-28 06:30:00', '2025-05-28 08:45:00', 135, 4550.00, 180, 109, 'Scheduled'),
(1684, '6E1041', '6E', 'BOM', 'BLR', '2025-05-28 08:00:00', '2025-05-28 09:45:00', 105, 5250.00, 160, 84, 'Scheduled'),
(1685, 'GF1042', 'GF', 'BLR', 'HYD', '2025-05-28 09:30:00', '2025-05-28 10:45:00', 75, 3250.00, 144, 89, 'Scheduled'),
(1686, 'AA1043', 'AA', 'HYD', 'DEL', '2025-05-28 11:00:00', '2025-05-28 13:15:00', 135, 4650.00, 186, 101, 'Scheduled'),
(1687, 'UK1044', 'UK', 'DEL', 'GOI', '2025-05-28 12:30:00', '2025-05-28 14:45:00', 135, 6150.00, 144, 73, 'Scheduled'),
(1688, 'SG1045', 'SG', 'GOI', 'BOM', '2025-05-28 14:00:00', '2025-05-28 15:15:00', 75, 3850.00, 180, 125, 'Delayed'),
(1689, 'FB1046', 'FB', 'BOM', 'CCU', '2025-05-28 15:30:00', '2025-05-28 18:00:00', 150, 5000.00, 160, 90, 'Cancelled'),
(1690, 'SA1047', 'SA', 'CCU', 'MAA', '2025-05-28 17:00:00', '2025-05-28 19:30:00', 150, 5950.00, 144, 64, 'Scheduled'),
(1691, 'TJ1048', 'TJ', 'MAA', 'COK', '2025-05-28 18:30:00', '2025-05-28 19:45:00', 75, 3150.00, 186, 139, 'Scheduled'),
(1692, 'AAI1049', 'AAI', 'COK', 'AMD', '2025-05-28 20:00:00', '2025-05-28 21:30:00', 90, 3650.00, 160, 115, 'Scheduled'),
(1693, 'AI1050', 'AI', 'AMD', 'DEL', '2025-05-29 06:30:00', '2025-05-29 08:15:00', 105, 4350.00, 180, 105, 'Scheduled'),
(1694, '6E1051', '6E', 'DEL', 'PNQ', '2025-05-29 08:00:00', '2025-05-29 09:30:00', 90, 4150.00, 160, 79, 'Scheduled'),
(1695, 'GF1052', 'GF', 'PNQ', 'BLR', '2025-05-29 09:30:00', '2025-05-29 11:00:00', 90, 3950.00, 144, 93, 'Scheduled'),
(1696, 'AA1053', 'AA', 'BLR', 'HYD', '2025-05-29 11:00:00', '2025-05-29 12:15:00', 75, 3350.00, 186, 98, 'Scheduled'),
(1697, 'UK1054', 'UK', 'HYD', 'BOM', '2025-05-29 12:30:00', '2025-05-29 14:00:00', 90, 4850.00, 144, 68, 'Scheduled'),
(1698, 'SG1055', 'SG', 'BOM', 'GOI', '2025-05-29 14:00:00', '2025-05-29 15:15:00', 75, 3750.00, 180, 121, 'Scheduled'),
(1699, 'FB1056', 'FB', 'GOI', 'CCU', '2025-05-29 15:30:00', '2025-05-29 18:15:00', 165, 5550.00, 160, 85, 'Scheduled'),
(1700, 'SA1057', 'SA', 'CCU', 'MAA', '2025-05-29 17:00:00', '2025-05-29 19:30:00', 150, 6050.00, 144, 59, 'Scheduled'),
(1701, 'TJ1058', 'TJ', 'MAA', 'COK', '2025-05-29 18:30:00', '2025-05-29 19:45:00', 75, 3250.00, 186, 135, 'Scheduled'),
(1702, 'AAI1059', 'AAI', 'COK', 'AMD', '2025-05-29 20:00:00', '2025-05-29 21:30:00', 90, 3550.00, 160, 112, 'Scheduled'),
(1703, 'AI1060', 'AI', 'DEL', 'BOM', '2025-05-30 06:30:00', '2025-05-30 08:45:00', 135, 4550.00, 180, 108, 'Scheduled'),
(1704, '6E1061', '6E', 'BOM', 'BLR', '2025-05-30 08:00:00', '2025-05-30 09:45:00', 105, 5250.00, 160, 83, 'Scheduled'),
(1705, 'GF1062', 'GF', 'BLR', 'HYD', '2025-05-30 09:30:00', '2025-05-30 10:45:00', 75, 3250.00, 144, 88, 'Scheduled'),
(1706, 'AA1063', 'AA', 'HYD', 'DEL', '2025-05-30 11:00:00', '2025-05-30 13:15:00', 135, 4650.00, 186, 100, 'Scheduled'),
(1707, 'UK1064', 'UK', 'DEL', 'GOI', '2025-05-30 12:30:00', '2025-05-30 14:45:00', 135, 6150.00, 144, 72, 'Scheduled'),
(1708, 'SG1065', 'SG', 'GOI', 'BOM', '2025-05-30 14:00:00', '2025-05-30 15:15:00', 75, 3850.00, 180, 124, 'Scheduled'),
(1709, 'FB1066', 'FB', 'BOM', 'CCU', '2025-05-30 15:30:00', '2025-05-30 18:00:00', 150, 5450.00, 160, 89, 'Scheduled'),
(1710, 'SA1067', 'SA', 'CCU', 'MAA', '2025-05-30 17:00:00', '2025-05-30 19:30:00', 150, 5950.00, 144, 63, 'Scheduled'),
(1711, 'TJ1068', 'TJ', 'MAA', 'COK', '2025-05-30 18:30:00', '2025-05-30 19:45:00', 75, 3150.00, 186, 138, 'Scheduled'),
(1712, 'AAI1069', 'AAI', 'COK', 'AMD', '2025-05-30 20:00:00', '2025-05-30 21:30:00', 90, 3650.00, 160, 114, 'Scheduled'),
(1717, '6E4000', '6E', 'DEL', 'BOM', '2025-06-05 06:00:00', '2025-06-05 08:30:00', 150, 5500.00, 180, 180, 'Scheduled'),
(1718, 'AA4001', 'AA', 'BOM', 'BLR', '2025-06-05 08:15:00', '2025-06-05 10:45:00', 150, 6200.00, 200, 200, 'Scheduled'),
(1719, 'AAI4002', 'AAI', 'BLR', 'MAA', '2025-06-05 10:30:00', '2025-06-05 12:00:00', 90, 4800.00, 150, 150, 'Scheduled'),
(1720, 'AI4003', 'AI', 'MAA', 'CCU', '2025-06-05 12:45:00', '2025-06-05 15:15:00', 150, 7200.00, 220, 220, 'Scheduled'),
(1721, 'FB4004', 'FB', 'CCU', 'DEL', '2025-06-05 16:00:00', '2025-06-05 18:30:00', 150, 6800.00, 190, 190, 'Scheduled'),
(1722, 'GF4005', 'GF', 'DEL', 'AMD', '2025-06-05 19:15:00', '2025-06-05 21:00:00', 105, 5200.00, 160, 160, 'Scheduled'),
(1723, 'SA4006', 'SA', 'AMD', 'HYD', '2025-06-05 07:30:00', '2025-06-05 09:15:00', 105, 4900.00, 170, 170, 'Scheduled'),
(1724, 'SG4007', 'SG', 'HYD', 'PNQ', '2025-06-05 11:00:00', '2025-06-05 12:30:00', 90, 4200.00, 140, 140, 'Scheduled'),
(1725, 'TJ4008', 'TJ', 'PNQ', 'GOI', '2025-06-05 14:20:00', '2025-06-05 15:30:00', 70, 3800.00, 120, 120, 'Scheduled'),
(1726, 'UK4009', 'UK', 'GOI', 'BOM', '2025-06-05 17:45:00', '2025-06-05 19:15:00', 90, 4500.00, 150, 150, 'Scheduled'),
(1727, '6E4010', '6E', 'BOM', 'DEL', '2025-06-06 06:15:00', '2025-06-06 08:45:00', 150, 5600.00, 180, 180, 'Scheduled'),
(1728, 'AA4011', 'AA', 'DEL', 'BLR', '2025-06-06 09:00:00', '2025-06-06 11:30:00', 150, 6300.00, 200, 200, 'Scheduled'),
(1729, 'AAI4012', 'AAI', 'BLR', 'CCU', '2025-06-06 12:15:00', '2025-06-06 14:45:00', 150, 7100.00, 150, 150, 'Scheduled'),
(1730, 'AI4013', 'AI', 'CCU', 'MAA', '2025-06-06 15:30:00', '2025-06-06 17:00:00', 90, 5800.00, 220, 220, 'Scheduled'),
(1731, 'FB4014', 'FB', 'MAA', 'AMD', '2025-06-06 18:45:00', '2025-06-06 20:30:00', 105, 5300.00, 190, 190, 'Scheduled'),
(1732, 'GF4015', 'GF', 'AMD', 'HYD', '2025-06-06 07:00:00', '2025-06-06 08:45:00', 105, 4800.00, 160, 160, 'Scheduled'),
(1733, 'SA4016', 'SA', 'HYD', 'PNQ', '2025-06-06 10:30:00', '2025-06-06 12:00:00', 90, 4300.00, 170, 170, 'Scheduled'),
(1734, 'SG4017', 'SG', 'PNQ', 'GOI', '2025-06-06 13:45:00', '2025-06-06 14:55:00', 70, 3900.00, 140, 140, 'Scheduled'),
(1735, 'TJ4018', 'TJ', 'GOI', 'BOM', '2025-06-06 16:20:00', '2025-06-06 17:50:00', 90, 4600.00, 120, 120, 'Scheduled'),
(1736, 'UK4019', 'UK', 'BOM', 'BLR', '2025-06-06 19:30:00', '2025-06-06 22:00:00', 150, 6100.00, 150, 150, 'Scheduled'),
(1737, '6E4020', '6E', 'BLR', 'DEL', '2025-06-07 06:30:00', '2025-06-07 09:00:00', 150, 5700.00, 180, 180, 'Scheduled'),
(1738, 'AA4021', 'AA', 'DEL', 'MAA', '2025-06-07 09:45:00', '2025-06-07 12:15:00', 150, 6800.00, 200, 200, 'Scheduled'),
(1739, 'AAI4022', 'AAI', 'MAA', 'BOM', '2025-06-07 13:00:00', '2025-06-07 15:30:00', 150, 6400.00, 150, 150, 'Scheduled'),
(1740, 'AI4023', 'AI', 'BOM', 'CCU', '2025-06-07 16:15:00', '2025-06-07 18:45:00', 150, 7300.00, 220, 220, 'Scheduled'),
(1741, 'FB4024', 'FB', 'CCU', 'AMD', '2025-06-07 19:30:00', '2025-06-07 22:00:00', 150, 6900.00, 190, 190, 'Scheduled'),
(1742, 'GF4025', 'GF', 'AMD', 'PNQ', '2025-06-07 07:15:00', '2025-06-07 09:00:00', 105, 5000.00, 160, 160, 'Scheduled'),
(1743, 'SA4026', 'SA', 'PNQ', 'HYD', '2025-06-07 10:45:00', '2025-06-07 12:15:00', 90, 4400.00, 170, 170, 'Scheduled'),
(1744, 'SG4027', 'SG', 'HYD', 'GOI', '2025-06-07 14:00:00', '2025-06-07 15:45:00', 105, 4700.00, 140, 140, 'Scheduled'),
(1745, 'TJ4028', 'TJ', 'GOI', 'BLR', '2025-06-07 17:20:00', '2025-06-07 19:05:00', 105, 5100.00, 120, 120, 'Scheduled'),
(1746, 'UK4029', 'UK', 'BLR', 'BOM', '2025-06-07 20:30:00', '2025-06-07 23:00:00', 150, 6200.00, 150, 150, 'Scheduled'),
(1747, '6E4030', '6E', 'BOM', 'MAA', '2025-06-08 06:45:00', '2025-06-08 09:15:00', 150, 5800.00, 180, 180, 'Scheduled'),
(1748, 'AA4031', 'AA', 'MAA', 'DEL', '2025-06-08 10:00:00', '2025-06-08 12:30:00', 150, 6900.00, 200, 200, 'Scheduled'),
(1749, 'AAI4032', 'AAI', 'DEL', 'CCU', '2025-06-08 13:15:00', '2025-06-08 15:45:00', 150, 7200.00, 150, 150, 'Scheduled'),
(1750, 'AI4033', 'AI', 'CCU', 'BLR', '2025-06-08 16:30:00', '2025-06-08 19:00:00', 150, 6700.00, 220, 220, 'Scheduled'),
(1751, 'FB4034', 'FB', 'BLR', 'AMD', '2025-06-08 19:45:00', '2025-06-08 21:30:00', 105, 5400.00, 190, 190, 'Scheduled'),
(1752, 'GF4035', 'GF', 'AMD', 'HYD', '2025-06-08 07:30:00', '2025-06-08 09:15:00', 105, 4900.00, 160, 160, 'Scheduled'),
(1753, 'SA4036', 'SA', 'HYD', 'GOI', '2025-06-08 11:00:00', '2025-06-08 12:45:00', 105, 4800.00, 170, 170, 'Scheduled'),
(1754, 'SG4037', 'SG', 'GOI', 'PNQ', '2025-06-08 14:15:00', '2025-06-08 15:25:00', 70, 4000.00, 140, 140, 'Scheduled'),
(1755, 'TJ4038', 'TJ', 'PNQ', 'BOM', '2025-06-08 17:40:00', '2025-06-08 19:10:00', 90, 4700.00, 120, 120, 'Scheduled'),
(1756, 'UK4039', 'UK', 'BOM', 'DEL', '2025-06-08 20:45:00', '2025-06-08 23:15:00', 150, 5900.00, 150, 150, 'Scheduled'),
(1757, '6E4040', '6E', 'DEL', 'BLR', '2025-06-09 07:00:00', '2025-06-09 09:30:00', 150, 5900.00, 180, 180, 'Scheduled'),
(1758, 'AA4041', 'AA', 'BLR', 'CCU', '2025-06-09 10:15:00', '2025-06-09 12:45:00', 150, 7000.00, 200, 200, 'Scheduled'),
(1759, 'AAI4042', 'AAI', 'CCU', 'MAA', '2025-06-09 13:30:00', '2025-06-09 15:00:00', 90, 5900.00, 150, 150, 'Scheduled'),
(1760, 'AI4043', 'AI', 'MAA', 'BOM', '2025-06-09 16:45:00', '2025-06-09 19:15:00', 150, 6500.00, 220, 220, 'Scheduled'),
(1761, 'FB4044', 'FB', 'BOM', 'HYD', '2025-06-09 20:00:00', '2025-06-09 22:30:00', 150, 6600.00, 190, 190, 'Scheduled'),
(1762, 'GF4045', 'GF', 'HYD', 'AMD', '2025-06-09 07:45:00', '2025-06-09 09:30:00', 105, 5100.00, 160, 160, 'Scheduled'),
(1763, 'SA4046', 'SA', 'AMD', 'GOI', '2025-06-09 11:15:00', '2025-06-09 13:00:00', 105, 4900.00, 170, 170, 'Scheduled'),
(1764, 'SG4047', 'SG', 'GOI', 'PNQ', '2025-06-09 14:30:00', '2025-06-09 15:40:00', 70, 4100.00, 140, 140, 'Scheduled'),
(1765, 'TJ4048', 'TJ', 'PNQ', 'DEL', '2025-06-09 17:55:00', '2025-06-09 20:25:00', 150, 6300.00, 120, 120, 'Scheduled'),
(1766, 'UK4049', 'UK', 'DEL', 'BLR', '2025-06-09 21:00:00', '2025-06-09 23:30:00', 150, 6000.00, 150, 150, 'Scheduled'),
(1767, '6E4050', '6E', 'BLR', 'MAA', '2025-06-10 07:15:00', '2025-06-10 08:45:00', 90, 4600.00, 180, 180, 'Scheduled'),
(1768, 'AA4051', 'AA', 'MAA', 'CCU', '2025-06-10 10:30:00', '2025-06-10 13:00:00', 150, 7100.00, 200, 200, 'Scheduled'),
(1769, 'AAI4052', 'AAI', 'CCU', 'BOM', '2025-06-10 13:45:00', '2025-06-10 16:15:00', 150, 6600.00, 150, 150, 'Scheduled'),
(1770, 'AI4053', 'AI', 'BOM', 'AMD', '2025-06-10 17:00:00', '2025-06-10 18:45:00', 105, 5500.00, 220, 220, 'Scheduled'),
(1771, 'FB4054', 'FB', 'AMD', 'DEL', '2025-06-10 20:15:00', '2025-06-10 22:00:00', 105, 5600.00, 190, 190, 'Scheduled'),
(1772, 'GF4055', 'GF', 'DEL', 'HYD', '2025-06-10 08:00:00', '2025-06-10 10:30:00', 150, 6400.00, 160, 160, 'Scheduled'),
(1773, 'SA4056', 'SA', 'HYD', 'PNQ', '2025-06-10 11:30:00', '2025-06-10 13:00:00', 90, 4500.00, 170, 170, 'Scheduled'),
(1774, 'SG4057', 'SG', 'PNQ', 'GOI', '2025-06-10 14:45:00', '2025-06-10 15:55:00', 70, 4200.00, 140, 140, 'Scheduled'),
(1775, 'TJ4058', 'TJ', 'GOI', 'BLR', '2025-06-10 18:10:00', '2025-06-10 19:55:00', 105, 5200.00, 120, 120, 'Scheduled'),
(1776, 'UK4059', 'UK', 'BLR', 'CCU', '2025-06-10 21:15:00', '2025-06-10 23:45:00', 150, 7200.00, 150, 150, 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `generated_tickets`
--

CREATE TABLE `generated_tickets` (
  `ticket_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `ticket_html` longtext NOT NULL,
  `flight_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flight_details`)),
  `passenger_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passenger_details`)),
  `status` enum('active','cancelled','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `generated_tickets`
--

INSERT INTO `generated_tickets` (`ticket_id`, `booking_id`, `user_id`, `booking_reference`, `ticket_html`, `flight_details`, `passenger_details`, `status`, `created_at`, `updated_at`) VALUES
(1, 51, 12, 'BK-6830A6836E354', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-6830A6836E354<br>\r\n              <strong>Flight:</strong> AA-AA1013<br>\r\n              <strong>Date:</strong> 25 May 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 11:00 \r\n              <strong>Arrival:</strong> 12:15<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Harry  Potter (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":51,\"booking_reference\":\"BK-6830A6836E354\",\"user_id\":12,\"flight_id\":1656,\"booking_date\":\"2025-05-23 18:46:59\",\"travel_date\":\"2025-05-25\",\"num_passengers\":1,\"total_amount\":\"5930.00\",\"contact_email\":\"harry@gmail.com\",\"contact_phone\":\"7013631447\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-23 22:17:05\",\"payment_details\":\"karen@okaxis\",\"airline_id\":\"AA\",\"flight_number\":\"AA1013\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-05-25 11:00:00\",\"arrival_time\":\"2025-05-25 12:15:00\",\"duration\":75}', '[{\"passenger_id\":15,\"booking_id\":51,\"first_name\":\"Harry \",\"last_name\":\"Potter\",\"gender\":\"male\",\"age\":15,\"seat_number\":\"TBA\"}]', 'active', '2025-05-23 16:47:05', '2025-05-23 16:47:05'),
(3, 52, 12, 'BK-6830A899E9AE3', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-6830A899E9AE3<br>\r\n              <strong>Flight:</strong> AA-AA1013<br>\r\n              <strong>Date:</strong> 25 May 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 11:00 \r\n              <strong>Arrival:</strong> 12:15<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Karan Potter (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":52,\"booking_reference\":\"BK-6830A899E9AE3\",\"user_id\":12,\"flight_id\":1656,\"booking_date\":\"2025-05-23 18:55:53\",\"travel_date\":\"2025-05-25\",\"num_passengers\":1,\"total_amount\":\"3953.00\",\"contact_email\":\"harry@gmail.com\",\"contact_phone\":\"7013631447\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-23 22:26:00\",\"payment_details\":\"karen@okaxis\",\"airline_id\":\"AA\",\"flight_number\":\"AA1013\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-05-25 11:00:00\",\"arrival_time\":\"2025-05-25 12:15:00\",\"duration\":75}', '[{\"passenger_id\":16,\"booking_id\":52,\"first_name\":\"Karan\",\"last_name\":\"Potter\",\"gender\":\"male\",\"age\":15,\"seat_number\":\"TBA\"}]', 'active', '2025-05-23 16:56:00', '2025-05-23 16:56:00'),
(4, 53, 12, 'BK-6830AF46CC883', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-6830AF46CC883<br>\r\n              <strong>Flight:</strong> AA-AA1013<br>\r\n              <strong>Date:</strong> 25 May 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 11:00 \r\n              <strong>Arrival:</strong> 12:15<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Karan Potter (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":53,\"booking_reference\":\"BK-6830AF46CC883\",\"user_id\":12,\"flight_id\":1656,\"booking_date\":\"2025-05-23 19:24:22\",\"travel_date\":\"2025-05-25\",\"num_passengers\":1,\"total_amount\":\"3953.00\",\"contact_email\":\"harry@gmail.com\",\"contact_phone\":\"7013631447\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-23 22:54:28\",\"payment_details\":\"karen@okaxis\",\"airline_id\":\"AA\",\"flight_number\":\"AA1013\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-05-25 11:00:00\",\"arrival_time\":\"2025-05-25 12:15:00\",\"duration\":75}', '[{\"passenger_id\":17,\"booking_id\":53,\"first_name\":\"Karan\",\"last_name\":\"Potter\",\"gender\":\"male\",\"age\":15,\"seat_number\":\"TBA\"}]', 'active', '2025-05-23 17:24:28', '2025-05-23 17:24:28'),
(5, 54, 11, 'BK-68316436A3F44', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-68316436A3F44<br>\r\n              <strong>Flight:</strong> SG-SG1005<br>\r\n              <strong>Date:</strong> 24 May 2025<br>\r\n              <strong>From:</strong> GOI \r\n              <strong>To:</strong> BOM<br>\r\n              <strong>Departure:</strong> 14:00 \r\n              <strong>Arrival:</strong> 15:15<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Dhanya Reddy (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":54,\"booking_reference\":\"BK-68316436A3F44\",\"user_id\":11,\"flight_id\":1648,\"booking_date\":\"2025-05-24 08:16:22\",\"travel_date\":\"2025-05-24\",\"num_passengers\":1,\"total_amount\":\"4543.00\",\"contact_email\":\"dhanya@gmail.com\",\"contact_phone\":\"7013631447\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-24 11:46:31\",\"payment_details\":\"karen@okaxis\",\"airline_id\":\"SG\",\"flight_number\":\"SG1005\",\"origin_airport\":\"GOI\",\"destination_airport\":\"BOM\",\"departure_time\":\"2025-05-24 14:00:00\",\"arrival_time\":\"2025-05-24 15:15:00\",\"duration\":75}', '[{\"passenger_id\":18,\"booking_id\":54,\"first_name\":\"Dhanya\",\"last_name\":\"Reddy\",\"gender\":\"female\",\"age\":20,\"seat_number\":\"TBA\"}]', 'active', '2025-05-24 06:16:31', '2025-05-24 06:16:31'),
(6, 55, 11, 'BK-68316516ABC50', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-68316516ABC50<br>\r\n              <strong>Flight:</strong> AAI-AAI1009<br>\r\n              <strong>Date:</strong> 24 May 2025<br>\r\n              <strong>From:</strong> COK \r\n              <strong>To:</strong> AMD<br>\r\n              <strong>Departure:</strong> 20:00 \r\n              <strong>Arrival:</strong> 21:30<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Medam  Ritvik (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":55,\"booking_reference\":\"BK-68316516ABC50\",\"user_id\":11,\"flight_id\":1652,\"booking_date\":\"2025-05-24 08:20:06\",\"travel_date\":\"2025-05-24\",\"num_passengers\":1,\"total_amount\":\"4307.00\",\"contact_email\":\"medamritvik@gmail.com\",\"contact_phone\":\"7013631447\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-24 11:50:15\",\"payment_details\":\"karen@okaxis\",\"airline_id\":\"AAI\",\"flight_number\":\"AAI1009\",\"origin_airport\":\"COK\",\"destination_airport\":\"AMD\",\"departure_time\":\"2025-05-24 20:00:00\",\"arrival_time\":\"2025-05-24 21:30:00\",\"duration\":90}', '[{\"passenger_id\":19,\"booking_id\":55,\"first_name\":\"Medam \",\"last_name\":\"Ritvik\",\"gender\":\"male\",\"age\":18,\"seat_number\":\"TBA\"}]', 'active', '2025-05-24 06:20:15', '2025-05-24 06:20:15'),
(7, 56, 13, 'BK-68317D06E612F', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-68317D06E612F<br>\r\n              <strong>Flight:</strong> GF-GF1002<br>\r\n              <strong>Date:</strong> 24 May 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 09:30 \r\n              <strong>Arrival:</strong> 10:45<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Jayden D\'Souza  (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":56,\"booking_reference\":\"BK-68317D06E612F\",\"user_id\":13,\"flight_id\":1645,\"booking_date\":\"2025-05-24 10:02:14\",\"travel_date\":\"2025-05-24\",\"num_passengers\":1,\"total_amount\":\"3835.00\",\"contact_email\":\"jayden@gmail.com\",\"contact_phone\":\"9886026336\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-24 13:33:44\",\"payment_details\":\"jayden@okaxis\",\"airline_id\":\"GF\",\"flight_number\":\"GF1002\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-05-24 09:30:00\",\"arrival_time\":\"2025-05-24 10:45:00\",\"duration\":75}', '[{\"passenger_id\":20,\"booking_id\":56,\"first_name\":\"Jayden\",\"last_name\":\"D\'Souza \",\"gender\":\"male\",\"age\":20,\"seat_number\":\"TBA\"}]', 'active', '2025-05-24 08:03:44', '2025-05-24 08:03:44'),
(10, 57, 13, 'BK-68317DA355654', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-68317DA355654<br>\r\n              <strong>Flight:</strong> GF-GF1002<br>\r\n              <strong>Date:</strong> 24 May 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 09:30 \r\n              <strong>Arrival:</strong> 10:45<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Jayden D\'Souza  (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":57,\"booking_reference\":\"BK-68317DA355654\",\"user_id\":13,\"flight_id\":1645,\"booking_date\":\"2025-05-24 10:04:51\",\"travel_date\":\"2025-05-24\",\"num_passengers\":1,\"total_amount\":\"3835.00\",\"contact_email\":\"jayden@gmail.com\",\"contact_phone\":\"9886026336\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-24 13:35:19\",\"payment_details\":\"robert@okaxis\",\"airline_id\":\"GF\",\"flight_number\":\"GF1002\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-05-24 09:30:00\",\"arrival_time\":\"2025-05-24 10:45:00\",\"duration\":75}', '[{\"passenger_id\":21,\"booking_id\":57,\"first_name\":\"Jayden\",\"last_name\":\"D\'Souza \",\"gender\":\"male\",\"age\":20,\"seat_number\":\"TBA\"}]', 'active', '2025-05-24 08:05:19', '2025-05-24 08:05:19'),
(11, 59, 5, 'BK-68371BFEC1D53', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-68371BFEC1D53<br>\r\n              <strong>Flight:</strong> AA-AA1053<br>\r\n              <strong>Date:</strong> 29 May 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 11:00 \r\n              <strong>Arrival:</strong> 12:15<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Anandaraj A (Seat: 12A,Window)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":59,\"booking_reference\":\"BK-68371BFEC1D53\",\"user_id\":5,\"flight_id\":1696,\"booking_date\":\"2025-05-28 16:21:50\",\"travel_date\":\"2025-05-29\",\"num_passengers\":1,\"total_amount\":\"3953.00\",\"contact_email\":\"chezanand@gmail.com\",\"contact_phone\":\"7338553820\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-28 19:51:57\",\"payment_details\":\"karen@okaxis\",\"airline_id\":\"AA\",\"flight_number\":\"AA1053\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-05-29 11:00:00\",\"arrival_time\":\"2025-05-29 12:15:00\",\"duration\":75}', '[{\"passenger_id\":23,\"booking_id\":59,\"first_name\":\"Anandaraj\",\"last_name\":\"A\",\"gender\":\"male\",\"age\":61,\"seat_number\":\"12A,Window\"}]', 'active', '2025-05-28 14:21:57', '2025-05-28 14:21:57'),
(12, 63, 5, 'BK-683723224FD00', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-683723224FD00<br>\r\n              <strong>Flight:</strong> GF-GF370<br>\r\n              <strong>Date:</strong> 01 Jun 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 09:30 \r\n              <strong>Arrival:</strong> 10:45<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Harry  Chezhiyan (Seat: TBA)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":63,\"booking_reference\":\"BK-683723224FD00\",\"user_id\":5,\"flight_id\":1335,\"booking_date\":\"2025-05-28 16:52:18\",\"travel_date\":\"2025-06-01\",\"num_passengers\":1,\"total_amount\":\"3835.00\",\"contact_email\":\"harry@gmail.com\",\"contact_phone\":\"9886026336\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-28 20:22:29\",\"payment_details\":\"karen@okaxis\",\"airline_id\":\"GF\",\"flight_number\":\"GF370\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-06-01 09:30:00\",\"arrival_time\":\"2025-06-01 10:45:00\",\"duration\":75}', '[{\"passenger_id\":27,\"booking_id\":63,\"first_name\":\"Harry \",\"last_name\":\"Chezhiyan\",\"gender\":\"male\",\"age\":19,\"seat_number\":\"TBA\"}]', 'active', '2025-05-28 14:52:29', '2025-05-28 14:52:29'),
(13, 64, 5, 'BK-68372BFF71D86', '            <div>\r\n              <h2>BookMyFlight Ticket</h2>\r\n              <strong>Booking Reference:</strong> BK-68372BFF71D86<br>\r\n              <strong>Flight:</strong> AA-AA1053<br>\r\n              <strong>Date:</strong> 29 May 2025<br>\r\n              <strong>From:</strong> BLR \r\n              <strong>To:</strong> HYD<br>\r\n              <strong>Departure:</strong> 11:00 \r\n              <strong>Arrival:</strong> 12:15<br>\r\n              <strong>Passengers:</strong>\r\n              <ul>\r\n                                  <li>Harry  Anandaraj (Seat: 9A)</li>\r\n                              </ul>\r\n            </div>\r\n            ', '{\"booking_id\":64,\"booking_reference\":\"BK-68372BFF71D86\",\"user_id\":5,\"flight_id\":1696,\"booking_date\":\"2025-05-28 17:30:07\",\"travel_date\":\"2025-05-29\",\"num_passengers\":1,\"total_amount\":\"3953.00\",\"contact_email\":\"harry@gmail.com\",\"contact_phone\":\"9886026336\",\"booking_status\":\"Confirmed\",\"payment_status\":\"Completed\",\"payment_method\":\"upi\",\"created_at\":\"0000-00-00 00:00:00\",\"updated_at\":\"2025-05-28 21:00:20\",\"payment_details\":\"kevin@okaxis\",\"airline_id\":\"AA\",\"flight_number\":\"AA1053\",\"origin_airport\":\"BLR\",\"destination_airport\":\"HYD\",\"departure_time\":\"2025-05-29 11:00:00\",\"arrival_time\":\"2025-05-29 12:15:00\",\"duration\":75}', '[{\"passenger_id\":28,\"booking_id\":64,\"first_name\":\"Harry \",\"last_name\":\"Anandaraj\",\"gender\":\"male\",\"age\":20,\"seat_number\":\"9A\"}]', 'active', '2025-05-28 15:30:20', '2025-05-28 15:30:20');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('Flight Update','Booking','Offer','Reminder','General') DEFAULT 'General',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `notification_type`, `is_read`, `created_at`) VALUES
(13, 11, 'Your booking (Reference: BK-6830105F39445) has been successfully cancelled. Refund of ₹5,487 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-23 06:08:31'),
(14, 12, 'Your booking (Reference: BK-683027B09C896) has been successfully cancelled. Refund of ₹3,717 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-23 07:57:25'),
(15, 12, 'Your booking (Reference: BK-68304D32A76A0) has been successfully cancelled. Refund of ₹4,307 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-23 10:31:31'),
(16, 12, 'Your booking (Reference: BK-68304FB37C147) has been successfully cancelled. Refund of ₹4,307 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-23 10:51:24'),
(17, 12, 'Your booking (Reference: BK-68304F7C62478) has been successfully cancelled. Refund of ₹4,307 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-23 11:01:40'),
(18, 12, 'Your booking (Reference: BK-6830AF46CC883) has been successfully cancelled. Refund of ₹3,953 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-24 05:13:20'),
(19, 12, 'Your booking (Reference: BK-6830A6836E354) has been successfully cancelled. Refund of ₹5,930 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-24 05:40:45'),
(20, 13, 'Your booking (Reference: BK-68317D06E612F) has been successfully cancelled. Refund of ₹3,835 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-24 08:16:26'),
(21, 5, 'Your booking (Reference: BK-683721A70303B) has been successfully cancelled. Refund of ₹3,835 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-28 15:34:37'),
(22, 5, 'Your booking (Reference: BK-68372BFF71D86) has been successfully cancelled. Refund of ₹3,953 will be processed and credited to your account within 7-10 business days.', '', 0, '2025-05-28 15:48:59');

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `passenger_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `age` int(11) NOT NULL,
  `seat_number` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`passenger_id`, `booking_id`, `first_name`, `last_name`, `gender`, `age`, `seat_number`) VALUES
(1, 37, 'Kevin', 'Joseph', 'male', 19, '12A,Window'),
(2, 38, 'Chezhiyan', 'Anandaraj', 'male', 51, '11A,Window'),
(3, 39, 'Harry ', 'Potter', 'male', 236, '12A'),
(4, 40, 'Medam ', 'Ritvik', 'male', 18, '9A'),
(5, 41, 'Harry ', 'Anandaraj', 'male', 26, '12A'),
(6, 42, 'Karen', 'Elisha', 'female', 19, '9A'),
(7, 43, 'Dhanya', 'Reddy', 'female', 23, '9A'),
(8, 44, 'Robert ', 'Thompson', 'male', 51, '11A,Window'),
(9, 45, 'Carolyn', 'Maria', 'female', 11, '12A,Window'),
(10, 46, 'Tom', 'Anandaraj', 'male', 26, '12A,Window'),
(11, 47, 'Harry ', 'Anandaraj', 'male', 25, '12A,Window'),
(12, 48, 'Harry ', 'Potter', 'male', 19, '9A'),
(13, 49, 'Medam ', 'Ritvik', 'male', 15, '9A'),
(14, 50, 'Harry ', 'Potter', 'male', 15, 'TBA'),
(15, 51, 'Harry ', 'Potter', 'male', 15, 'TBA'),
(16, 52, 'Karan', 'Potter', 'male', 15, 'TBA'),
(17, 53, 'Karan', 'Potter', 'male', 15, 'TBA'),
(18, 54, 'Dhanya', 'Reddy', 'female', 20, 'TBA'),
(19, 55, 'Medam ', 'Ritvik', 'male', 18, 'TBA'),
(20, 56, 'Jayden', 'D\'Souza ', 'male', 20, 'TBA'),
(21, 57, 'Jayden', 'D\'Souza ', 'male', 20, 'TBA'),
(22, 58, 'Anandaraj', 'A', 'male', 61, '12A,Window'),
(23, 59, 'Anandaraj', 'A', 'male', 61, '12A,Window'),
(24, 60, 'Karen', 'Elisha', 'female', 19, '10A,Window'),
(25, 61, 'Harry ', 'Potter', 'male', 20, '9A'),
(26, 62, 'Harry ', 'Potter', 'male', 18, '12A,Window'),
(27, 63, 'Harry ', 'Chezhiyan', 'male', 19, 'TBA'),
(28, 64, 'Harry ', 'Anandaraj', 'male', 20, '9A');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `airline_name` varchar(100) NOT NULL,
  `number_of_passengers` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_date` datetime DEFAULT current_timestamp(),
  `status` enum('confirmed','pending','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `user_id`, `flight_id`, `airline_name`, `number_of_passengers`, `total_price`, `booking_date`, `status`) VALUES
(1, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 15:40:32', 'pending'),
(2, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-17 16:04:08', 'pending'),
(3, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 16:04:12', 'pending'),
(4, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 16:15:40', 'pending'),
(5, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 16:16:45', 'pending'),
(7, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 16:18:32', 'pending'),
(8, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-17 16:18:59', 'pending'),
(9, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 16:31:04', 'pending'),
(11, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-17 17:07:12', 'pending'),
(12, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 17:14:41', 'pending'),
(14, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 17:23:44', 'pending'),
(17, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 17:50:23', 'pending'),
(18, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-17 17:55:05', 'pending'),
(19, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 17:59:18', 'pending'),
(20, 5, 1002, 'Air India', 1, 4550.00, '2025-05-17 18:22:58', 'pending'),
(21, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-17 18:25:29', 'pending'),
(22, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-17 18:40:03', 'pending'),
(23, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-17 19:26:26', 'pending'),
(24, 5, 1002, 'Air India', 1, 4550.00, '2025-05-18 18:03:37', 'pending'),
(25, 5, 1002, 'Air India', 1, 4550.00, '2025-05-18 20:57:39', 'pending'),
(26, 5, 1001, 'IndiGo', 1, 3999.00, '2025-05-21 20:33:11', 'pending'),
(27, 10, 1001, 'IndiGo', 1, 3999.00, '2025-05-22 16:19:07', 'pending'),
(28, 10, 1003, 'Vistara', 1, 5299.00, '2025-05-22 17:48:55', 'pending'),
(29, 10, 1026, 'Alliance Air', 1, 3200.00, '2025-05-22 18:02:57', 'pending'),
(30, 10, 1026, 'Alliance Air', 1, 3200.00, '2025-05-22 18:13:12', 'pending'),
(31, 10, 1049, 'FlyBig', 1, 5550.00, '2025-05-22 18:21:53', 'pending'),
(32, 10, 1049, 'FlyBig', 1, 5550.00, '2025-05-22 18:22:06', 'pending'),
(33, 10, 1049, 'FlyBig', 1, 5550.00, '2025-05-22 18:30:23', 'pending'),
(34, 10, 1685, 'Go First', 1, 3250.00, '2025-05-22 22:07:03', 'pending'),
(35, 10, 1046, 'Alliance Air', 1, 3350.00, '2025-05-23 10:09:08', 'pending'),
(36, 11, 1706, 'Alliance Air', 1, 4650.00, '2025-05-23 11:35:03', ''),
(37, 12, 1711, 'TruJet', 1, 3150.00, '2025-05-23 13:12:44', ''),
(38, 12, 1652, 'AirAsia India', 1, 3650.00, '2025-05-23 15:53:28', ''),
(39, 12, 1652, 'AirAsia India', 1, 3650.00, '2025-05-23 16:05:14', ''),
(40, 12, 1652, 'AirAsia India', 1, 3650.00, '2025-05-23 16:06:11', ''),
(41, 12, 1656, 'Alliance Air', 1, 3350.00, '2025-05-23 16:08:06', ''),
(42, 12, 1656, 'Alliance Air', 1, 3350.00, '2025-05-23 21:12:53', ''),
(43, 12, 1656, 'Alliance Air', 1, 3350.00, '2025-05-23 21:18:10', ''),
(44, 12, 1656, 'Alliance Air', 1, 3350.00, '2025-05-23 21:18:26', ''),
(45, 11, 1648, 'SpiceJet', 1, 3850.00, '2025-05-24 11:45:37', 'pending'),
(46, 11, 1652, 'AirAsia India', 1, 3650.00, '2025-05-24 11:49:27', 'pending'),
(47, 13, 1645, 'Go First', 1, 3250.00, '2025-05-24 13:08:38', ''),
(48, 5, 1693, 'Air India', 1, 4350.00, '2025-05-28 19:06:21', 'pending'),
(49, 5, 1693, 'Air India', 1, 4350.00, '2025-05-28 19:08:37', 'pending'),
(50, 5, 1702, 'AirAsia India', 1, 3550.00, '2025-05-28 19:10:03', 'pending'),
(51, 5, 1702, 'AirAsia India', 1, 3550.00, '2025-05-28 19:10:23', 'pending'),
(52, 5, 1702, 'AirAsia India', 1, 3550.00, '2025-05-28 19:17:03', 'pending'),
(53, 5, 1693, 'Air India', 1, 4350.00, '2025-05-28 19:29:36', 'pending'),
(54, 11, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 19:36:48', 'pending'),
(55, 11, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 19:37:29', 'pending'),
(56, 11, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 19:41:50', 'pending'),
(57, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 19:44:49', ''),
(58, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 19:46:11', ''),
(59, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 19:50:32', ''),
(60, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 19:54:21', ''),
(61, 5, 1695, 'Go First', 1, 3950.00, '2025-05-28 20:10:16', 'pending'),
(62, 5, 1334, 'Air India', 1, 5250.00, '2025-05-28 20:11:01', 'pending'),
(63, 5, 1334, 'Air India', 1, 5250.00, '2025-05-28 20:13:07', 'pending'),
(64, 5, 1334, 'Air India', 1, 5250.00, '2025-05-28 20:13:40', 'pending'),
(65, 5, 1335, 'Go First', 1, 3250.00, '2025-05-28 20:14:17', ''),
(66, 5, 1335, 'Go First', 1, 3250.00, '2025-05-28 20:15:10', ''),
(67, 5, 1335, 'Go First', 1, 3250.00, '2025-05-28 20:21:52', ''),
(68, 11, 1705, 'Go First', 1, 3250.00, '2025-05-28 20:32:38', 'pending'),
(69, 11, 1705, 'Go First', 1, 3250.00, '2025-05-28 20:33:11', 'pending'),
(70, 11, 1705, 'Go First', 1, 3250.00, '2025-05-28 20:33:48', 'pending'),
(71, 11, 1705, 'Go First', 1, 3250.00, '2025-05-28 20:34:09', 'pending'),
(72, 11, 1705, 'Go First', 1, 3250.00, '2025-05-28 20:38:05', 'pending'),
(73, 11, 1705, 'Go First', 1, 3250.00, '2025-05-28 20:39:30', 'pending'),
(74, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 20:40:07', ''),
(75, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 20:40:28', ''),
(76, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 20:41:00', ''),
(77, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 20:46:10', ''),
(78, 5, 1335, 'Go First', 1, 3250.00, '2025-05-28 20:46:23', ''),
(79, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 20:47:09', ''),
(80, 5, 1335, 'Go First', 1, 3250.00, '2025-05-28 20:48:42', ''),
(81, 5, 1335, 'Go First', 1, 3250.00, '2025-05-28 20:58:38', ''),
(82, 5, 1335, 'Go First', 1, 3250.00, '2025-05-28 20:58:42', ''),
(83, 5, 1696, 'Alliance Air', 1, 3350.00, '2025-05-28 20:59:38', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `member_since` timestamp NOT NULL DEFAULT current_timestamp(),
  `loyalty_points` int(11) DEFAULT 0,
  `loyalty_tier` enum('Bronze','Silver','Gold','Platinum') DEFAULT 'Bronze',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `reset_code` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `member_since`, `loyalty_points`, `loyalty_tier`, `created_at`, `reset_code`, `reset_expiry`) VALUES
(5, 'Karen Elisha Chezhiyan', 'karenelisha0204@gmail.com', '9591553820', '$2y$10$xyaSVBEqCkpFzMS/Gf5eg.niz84jwlhqtThIlBiforda.IAvc.Xaq', '2025-04-26 14:36:37', 0, 'Bronze', '2025-04-26 14:36:37', NULL, NULL),
(10, 'Kevin Joseph', 'kevinjoseph@gmail.com', '7338553820', '$2y$10$hYQs4dQVDFvxBulZTqKJoeZ60Cm9vSfdnhXm3V13wB9TdnkNlu/XC', '2025-05-22 07:16:22', 0, 'Bronze', '2025-05-22 07:16:22', NULL, NULL),
(11, 'Dhanya', 'dhanya@gmail.com', '6363976507', '$2y$10$pH6NEjxVyjV1mZ7rnPHhqux4S/CQ5rz8oL.gyo/FJb/frDuOo.55m', '2025-05-22 07:16:40', 0, 'Bronze', '2025-05-22 07:16:40', NULL, NULL),
(12, 'Robert Thompson', 'robert@gmail.com', '9591553820', '$2y$10$FcHYelLpB/zKBkjdu./VlOLomZZ5OjvvhZ.hc5.qmrJfYPRh9Cjsm', '2025-05-23 07:24:49', 0, 'Bronze', '2025-05-23 07:24:49', NULL, NULL),
(13, 'Jayden', 'jaydensouza@gmail.com', '9886026336', '$2y$10$dJSeLDeqhPOM85rRYjXerOn7k7aw5lZy6SHJruHIgD70j5aK5Ccd6', '2025-05-24 07:29:53', 0, 'Bronze', '2025-05-24 07:29:53', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `airlines`
--
ALTER TABLE `airlines`
  ADD PRIMARY KEY (`airline_id`);

--
-- Indexes for table `airports`
--
ALTER TABLE `airports`
  ADD PRIMARY KEY (`airport_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`),
  ADD KEY `airline_id` (`airline_id`),
  ADD KEY `origin_airport` (`origin_airport`),
  ADD KEY `destination_airport` (`destination_airport`);

--
-- Indexes for table `generated_tickets`
--
ALTER TABLE `generated_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD UNIQUE KEY `unique_booking_ticket` (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_booking_reference` (`booking_reference`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`passenger_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `flight_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1777;

--
-- AUTO_INCREMENT for table `generated_tickets`
--
ALTER TABLE `generated_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `passenger_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`) ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`);

--
-- Constraints for table `generated_tickets`
--
ALTER TABLE `generated_tickets`
  ADD CONSTRAINT `generated_tickets_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `generated_tickets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
