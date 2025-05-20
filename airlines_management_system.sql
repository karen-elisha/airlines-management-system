-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2025 at 06:06 PM
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
(1, 'karen_elisha', '$2y$10$EynWZZeeUiOPTKWxNoHB3uMxlbEpw4obFm5RuaL3/pLe1NA1JuzuG', 'karenelisha0204@gmail.com', 'Karen Elisha Chezhiyan', '2025-05-17 09:46:50');

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
('AI', 'Air India', 'logos/ai.png', NULL, NULL, NULL, 1),
('AI05', 'Go First', 'https://gofirst.in/logo.png', 'https://gofirst.in', '1800-210-0999', 'https://gofirst.in/contact', 1),
('AI06', 'AirAsia India', 'https://airasia.com/logo.png', 'https://airasia.com', '080-4747-7474', 'https://airasia.com/contact', 1),
('AI07', 'Alliance Air', 'https://allianceair.in/logo.png', 'https://allianceair.in', '1800-180-1407', 'https://allianceair.in/contact', 1),
('AI08', 'TruJet', 'https://trujet.com/logo.png', 'https://trujet.com', '040-67137137', 'https://trujet.com/contact', 1),
('AI09', 'Star Air', 'https://starair.in/logo.png', 'https://starair.in', '1800-425-1111', 'https://starair.in/contact', 1),
('AI10', 'FlyBig', 'https://flybig.in/logo.png', 'https://flybig.in', '0755-6614141', 'https://flybig.in/contact', 1),
('SG', 'SpiceJet', 'logos/spicejet.png', NULL, NULL, NULL, 1),
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
('BLR', 'Kempegowda International Airport', 'Bangalore', 'India', 'Asia/Kolkata', 'BLR'),
('BOM', 'Chhatrapati Shivaji International Airport', 'Mumbai', 'India', 'Asia/Kolkata', 'BOM'),
('CCU', 'Netaji Subhash Chandra Bose International Airport', 'Kolkata', 'India', 'Asia/Kolkata', 'CCU'),
('DEL', 'Indira Gandhi International Airport', 'Delhi', 'India', 'Asia/Kolkata', 'DEL'),
('HYD', 'Rajiv Gandhi International Airport', 'Hyderabad', 'India', 'Asia/Kolkata', 'HYD'),
('MAA', 'Chennai International Airport', 'Chennai', 'India', 'Asia/Kolkata', 'MAA');

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
(1, 'BK-6828826CBB4A5', 5, 1002, '2025-05-17 14:34:52', '2025-05-20', 1, 5369.00, 'harry@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(2, 'BK-6828840541211', 5, 1002, '2025-05-17 14:41:41', '2025-05-20', 1, 5369.00, 'harry@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(3, 'BK-682884366150F', 5, 1002, '2025-05-17 14:42:30', '2025-05-20', 1, 5369.00, 'karenelisha0204@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(4, 'BK-6828851F17E88', 5, 1002, '2025-05-17 14:46:23', '2025-05-20', 1, 5369.00, 'karenelisha0204@gmail.com', '9591553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(5, 'BK-68288535D8E82', 5, 1002, '2025-05-17 14:46:45', '2025-05-20', 1, 5369.00, 'karenelisha0204@gmail.com', '9591553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(6, 'BK-682885660283A', 5, 1002, '2025-05-17 14:47:34', '2025-05-20', 1, 5369.00, 'karenelisha0204@gmail.com', '9591553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(7, 'BK-6828857E01EE9', 5, 1002, '2025-05-17 14:47:58', '2025-05-20', 1, 5369.00, 'karenelisha0204@gmail.com', '9591553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(8, 'BK-682885CBB90FF', 5, 1002, '2025-05-17 14:49:15', '2025-05-20', 1, 5369.00, 'karenelisha0204@gmail.com', '9591553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(9, 'BK-682886D663E37', 5, 1002, '2025-05-17 14:53:42', '2025-05-21', 1, 5369.00, 'chezanand@gmail.com', '9886026336', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(10, 'BK-682887660CB21', 5, 1001, '2025-05-17 14:56:06', '2025-05-19', 1, 4719.00, 'suresh@gmail.com', '7013631447', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(11, 'BK-68288792784B6', 5, 1001, '2025-05-17 14:56:50', '2025-05-19', 1, 4719.00, 'suresh@gmail.com', '7013631447', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(12, 'BK-68288AEA9B403', 5, 1001, '2025-05-17 15:11:06', '2025-05-23', 1, 4719.00, 'karenelisha0204@gmail.com', '7013631447', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(13, 'BK-68288BBD23F8F', 5, 1001, '2025-05-17 15:14:37', '2025-05-23', 1, 4719.00, 'karenelisha0204@gmail.com', '7013631447', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(14, 'BK-68288CB62F8CC', 5, 1001, '2025-05-17 15:18:46', '2025-05-23', 1, 4719.00, 'robert@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(15, 'BK-68288D6BA268E', 5, 1001, '2025-05-17 15:21:47', '2025-05-23', 1, 4719.00, 'robert@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(16, 'BK-68288DDEADE53', 5, 1001, '2025-05-17 15:23:42', '2025-05-23', 1, 4719.00, 'tom@gmail.com', '9849973543', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(17, 'BK-68288E312F7FF', 5, 1001, '2025-05-17 15:25:05', '2025-05-23', 1, 4719.00, 'tom@gmail.com', '9849973543', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(18, 'BK-68288E730C5A4', 5, 1001, '2025-05-17 15:26:11', '2025-05-23', 1, 4719.00, 'sony@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(20, 'BK-682895B2D25CA', 5, 1001, '2025-05-17 15:57:06', '2025-05-23', 1, 4719.00, 'carolyn@gmail.com', '9886026336', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(21, 'BK-682895F5411E1', 5, 1001, '2025-05-17 15:58:13', '2025-05-23', 1, 4719.00, 'carolyn@gmail.com', '9886026336', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(22, 'BK-682899A8900AC', 5, 1001, '2025-05-17 16:14:00', '2025-05-23', 1, 4719.00, 'carolyn@gmail.com', '9886026336', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(25, 'BK-6828A7F252B81', 5, 1001, '2025-05-17 17:14:58', '2025-05-23', 1, 4719.00, 'harry@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(26, 'BK-6828A94061033', 5, 1001, '2025-05-17 17:20:32', '2025-05-23', 1, 4719.00, 'chezanand@gmail.com', '7013631447', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(27, 'BK-6828AA4FCB117', 5, 1001, '2025-05-17 17:25:03', '2025-05-23', 1, 4719.00, 'harry@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(28, 'BK-6828AC8374FD9', 5, 1001, '2025-05-17 17:34:27', '2025-05-23', 1, 4719.00, 'karenelisha0204@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-17 21:04:42', NULL),
(29, 'BK-6828AEBE03E2A', 5, 1001, '2025-05-17 17:43:58', '2025-05-23', 1, 4719.00, 'karenelisha0204@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-17 21:18:01', NULL),
(30, 'BK-6828AFC8A980E', 5, 1001, '2025-05-17 17:48:24', '2025-05-23', 1, 4719.00, 'karenelisha0204@gmail.com', '7338553820', 'Confirmed', 'Completed', 'card', '0000-00-00 00:00:00', '2025-05-17 21:24:55', NULL),
(31, 'BK-6829D3BACEC9D', 5, 1002, '2025-05-18 14:34:02', '2025-05-21', 1, 5369.00, 'harry@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-18 18:09:11', NULL),
(32, 'BK-6829D4FF5EAF5', 5, 1002, '2025-05-18 14:39:27', '2025-05-21', 1, 5369.00, 'harry@gmail.com', '7338553820', 'Confirmed', 'Pending', 'credit_card', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(33, 'BK-6829EED040C89', 5, 1002, '2025-05-18 16:29:36', '2025-05-21', 1, 5369.00, 'medamritvik@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-18 20:01:14', 'karen@okaxis'),
(34, 'BK-6829EFD83459E', 5, 1002, '2025-05-18 16:34:00', '2025-05-21', 1, 5369.00, 'medamritvik@gmail.com', '7338553820', 'Confirmed', 'Completed', 'card', '0000-00-00 00:00:00', '2025-05-18 20:04:33', 'xxxx-xxxx-xxxx-5678'),
(35, 'BK-6829F12CE2169', 5, 1002, '2025-05-18 16:39:40', '2025-05-21', 1, 5369.00, 'medamritvik@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-18 20:09:50', 'karen@okaxis'),
(36, 'BK-6829FC912D49F', 5, 1002, '2025-05-18 17:28:17', '2025-05-20', 1, 5369.00, 'karenelisha0204@gmail.com', '7338553820', 'Confirmed', 'Completed', 'upi', '0000-00-00 00:00:00', '2025-05-18 20:58:54', 'karen@okaxis');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `name`, `email`, `rating`, `message`, `created_at`) VALUES
(1, 5, 'Dhanya', 'dhanya@gmail.com', 2, '0', '2025-05-17 10:22:54');

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
(1001, '6E101', '6E', 'DEL', 'BOM', '2025-05-22 06:00:00', '2023-05-25 08:15:00', 135, 5600.00, 180, 180, 'Scheduled'),
(1002, 'AI202', 'AI', 'BOM', 'BLR', '2023-12-20 09:30:00', '2023-12-20 11:00:00', 90, 3999.00, 160, 145, 'Scheduled'),
(1008, 'SG303', 'SG', 'BOM', 'DEL', '2025-05-21 20:37:00', '2025-05-23 20:37:00', 2880, 4500.00, 100, 100, 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `flight_schedule`
--

CREATE TABLE `flight_schedule` (
  `schedule_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `flight_date` date NOT NULL,
  `available_seats` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promo_id` int(11) NOT NULL,
  `promo_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `active` tinyint(1) DEFAULT 1
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
(25, 5, 1002, 'Air India', 1, 4550.00, '2025-05-18 20:57:39', 'pending');

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
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `member_since`, `loyalty_points`, `loyalty_tier`, `created_at`) VALUES
(5, 'Karen Elisha Chezhiyan', 'karenelisha0204@gmail.com', '9591553820', '$2y$10$xyaSVBEqCkpFzMS/Gf5eg.niz84jwlhqtThIlBiforda.IAvc.Xaq', '2025-04-26 14:36:37', 0, 'Bronze', '2025-04-26 14:36:37'),
(8, 'Kevin', 'kevin@gmail.com', '6363976507', '$2y$10$WV82FFe7QB0292J1fKo.quV77KDiu3WLPezcKFa7aJO5zOKj1oZne', '2025-05-20 15:20:25', 0, 'Bronze', '2025-05-20 15:20:25'),
(9, 'Dhanya', 'dhanya@gmail.com', '7338553820', '$2y$10$P/28T7NyMT256G0x1o28dOUcV2lxzreo7QWqDBgJg59OHbmSeMT4S', '2025-05-20 15:21:43', 0, 'Bronze', '2025-05-20 15:21:43');

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
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`),
  ADD KEY `airline_id` (`airline_id`),
  ADD KEY `origin_airport` (`origin_airport`),
  ADD KEY `destination_airport` (`destination_airport`);

--
-- Indexes for table `flight_schedule`
--
ALTER TABLE `flight_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `flight_id` (`flight_id`);

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
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `promo_code` (`promo_code`);

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
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `flight_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1009;

--
-- AUTO_INCREMENT for table `flight_schedule`
--
ALTER TABLE `flight_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `passenger_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `flights`
--
ALTER TABLE `flights`
  ADD CONSTRAINT `flights_ibfk_1` FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`airline_id`),
  ADD CONSTRAINT `flights_ibfk_2` FOREIGN KEY (`origin_airport`) REFERENCES `airports` (`airport_id`),
  ADD CONSTRAINT `flights_ibfk_3` FOREIGN KEY (`destination_airport`) REFERENCES `airports` (`airport_id`);

--
-- Constraints for table `flight_schedule`
--
ALTER TABLE `flight_schedule`
  ADD CONSTRAINT `flight_schedule_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `passengers`
--
ALTER TABLE `passengers`
  ADD CONSTRAINT `passengers_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
