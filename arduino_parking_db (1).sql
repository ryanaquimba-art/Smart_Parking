-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 06:31 AM
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
-- Database: `arduino_parking_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` char(6) NOT NULL,
  `purpose` enum('registration','login','password_reset') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_status`
--

CREATE TABLE `parking_status` (
  `id` int(11) NOT NULL,
  `slots_available` int(11) NOT NULL DEFAULT 4,
  `max_slots` int(11) NOT NULL DEFAULT 4,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_status`
--

INSERT INTO `parking_status` (`id`, `slots_available`, `max_slots`, `updated_at`) VALUES
(1, 0, 4, '2026-06-28 16:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `email_verified`, `last_login`, `created_at`) VALUES
(1, 'peter1111', 'angelo.m.serbo@isu.edu.ph', '$2y$10$8AjNF9Dmr7LUUGzvvMIoue4AkmHsD46.BFjFy6PUDQa.fhs9pxBm.', 0, NULL, '2026-06-25 06:59:00'),
(3, 'peterr', 'angelo.m.serbo@isu.e', '$2y$10$UPSKYhNCyrzU0dBxU5pqw.8jpDM74WHKdsI.wOJFWThuh1f5Y3BCe', 1, NULL, '2026-06-25 07:29:05'),
(5, 'ryan11', 'ryanaquimba@gmail.com', '$2y$10$ah034NS02OyjuhQAFS.xJ.3nticOSrgmplT6FBcz3qnJGqvVjY7E6', 0, '2026-06-29 00:33:28', '2026-06-25 07:57:06'),
(7, 'Antonio', 'alvengerdelacruz@gmail.com', '$2y$10$tloxRvj4nFegUlKsTqXzke777gKIxBy2Okulgx.d5BXtiWFe3oQ3O', 1, '2026-06-25 17:15:23', '2026-06-25 09:14:23'),
(8, 'bamboo', 'pinayflix.26@gmail.com', '$2y$10$MHipOp81zaamYEIX1z13Cemg1wmwT2QVPsE6/bpL1UsqR9Yendbdu', 1, '2026-06-28 20:51:42', '2026-06-28 12:50:47'),
(9, 'gelo', 'realmerealme273@gmail.com', '$2y$10$RPI2t3XS2gHqxno4c4zRgeCKK25HkVT0dvT2uEjzQa7lNl4kxjt3e', 1, '2026-06-28 20:54:01', '2026-06-28 12:51:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `parking_status`
--
ALTER TABLE `parking_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `parking_status`
--
ALTER TABLE `parking_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD CONSTRAINT `otp_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
