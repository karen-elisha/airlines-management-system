-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2025 at 11:47 AM
-- Server version: 9.2.0
-- PHP Version: 8.2.12

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
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `flight_id` int NOT NULL,
  `flight_number` varchar(10) NOT NULL,
  `airline_id` varchar(5) NOT NULL,
  `origin_airport` varchar(10) NOT NULL,
  `destination_airport` varchar(10) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `duration` int NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `total_seats` int NOT NULL,
  `available_seats` int NOT NULL,
  `flight_status` enum('Scheduled','Delayed','Departed','Arrived','Cancelled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`flight_id`, `flight_number`, `airline_id`, `origin_airport`, `destination_airport`, `departure_time`, `arrival_time`, `duration`, `base_price`, `total_seats`, `available_seats`, `flight_status`) VALUES
(1001, '6E101', '6E', 'DEL', 'BOM', '2023-12-20 06:00:00', '2023-12-20 08:15:00', 135, 4599.00, 180, 180, 'Scheduled'),
(1002, 'AI202', 'AI', 'BOM', 'BLR', '2023-12-20 09:30:00', '2023-12-20 11:00:00', 90, 3899.00, 160, 145, 'Scheduled'),
(1003, 'SG303', 'SG', 'BLR', 'DEL', '2023-12-20 13:15:00', '2023-12-20 15:45:00', 150, 4199.00, 189, 120, 'Scheduled'),
(1004, 'UK404', 'UK', 'DEL', 'HYD', '2023-12-20 17:00:00', '2023-12-20 19:00:00', 120, 5499.00, 144, 144, 'Scheduled');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`),
  ADD KEY `airline_id` (`airline_id`),
  ADD KEY `origin_airport` (`origin_airport`),
  ADD KEY `destination_airport` (`destination_airport`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `flight_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `flights`
--
ALTER TABLE `flights`
  ADD CONSTRAINT `flights_ibfk_1` FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`airline_id`),
  ADD CONSTRAINT `flights_ibfk_2` FOREIGN KEY (`origin_airport`) REFERENCES `airports` (`airport_id`),
  ADD CONSTRAINT `flights_ibfk_3` FOREIGN KEY (`destination_airport`) REFERENCES `airports` (`airport_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
