-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 04:12 AM
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
-- Database: `dbcargo`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`) VALUES
(1, 'cartney', 'cartney@gmail.com', '12345');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `car_id` int(11) NOT NULL,
  `car_name` varchar(255) NOT NULL,
  `car_image` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `gender` varchar(20) NOT NULL DEFAULT 'Male',
  `book_with_driver` tinyint(1) NOT NULL DEFAULT 0,
  `rental_period` varchar(50) NOT NULL DEFAULT 'Day',
  `needs_delivery` tinyint(1) DEFAULT 0,
  `delivery_address` varchar(500) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `pickup_date` date NOT NULL,
  `return_date` date NOT NULL,
  `pickup_time` time NOT NULL,
  `return_time` time NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `driver_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `number_of_days` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','ongoing','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','partial','refunded') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` enum('renter','owner','admin') DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `color` varchar(100) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `car_year` varchar(50) NOT NULL,
  `body_style` varchar(200) DEFAULT NULL,
  `transmission` varchar(50) DEFAULT 'Automatic',
  `fuel_type` varchar(50) DEFAULT 'Gasoline',
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `car_name` varchar(255) DEFAULT NULL,
  `trim` varchar(100) NOT NULL,
  `plate_number` varchar(30) DEFAULT NULL,
  `price_per_day` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `issues` varchar(255) DEFAULT 'None',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `advance_notice` varchar(100) DEFAULT NULL,
  `min_trip_duration` varchar(100) DEFAULT NULL,
  `max_trip_duration` varchar(100) DEFAULT NULL,
  `delivery_types` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `has_unlimited_mileage` tinyint(1) DEFAULT 1,
  `mileage_limit` varchar(50) DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT 0.00,
  `unlimited_mileage` tinyint(1) DEFAULT 0,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `address` text DEFAULT NULL,
  `official_receipt` text DEFAULT NULL,
  `certificate_of_registration` text DEFAULT NULL,
  `extra_images` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `seat` int(11) DEFAULT 4,
  `status` enum('pending','approved','rejected','disabled') DEFAULT 'pending',
  `rating` float DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `owner_id`, `color`, `description`, `car_year`, `body_style`, `transmission`, `fuel_type`, `brand`, `model`, `car_name`, `trim`, `plate_number`, `price_per_day`, `image`, `location`, `issues`, `created_at`, `advance_notice`, `min_trip_duration`, `max_trip_duration`, `delivery_types`, `features`, `rules`, `has_unlimited_mileage`, `mileage_limit`, `daily_rate`, `unlimited_mileage`, `latitude`, `longitude`, `address`, `official_receipt`, `certificate_of_registration`, `extra_images`, `remarks`, `seat`, `status`, `rating`) VALUES
(3, 1, 'yellow', NULL, '2020', '4 setter', 'Automatic', 'Gasoline', 'Audi', 'A3', 'Audi A3', '', '1234-5647', 600.00, 'uploads/car_1763279764.png', 'P1 Lapinigan ADS', 'None', '2025-11-16 07:43:16', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, '', 0, 'approved', 5),
(16, 1, 'red', 'wow', '2025', '3-Door Hatchback', 'Automatic', 'Gasoline', 'Audi', 'A1', 'Audi A1', 'N/A', '12345', 2000.00, 'uploads/car_1763396591_4187.jpg', NULL, 'None', '2025-11-17 16:23:11', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\",\"Guest Pickup & Host Collection\"]', '[\"All-wheel drive\",\"Android auto\",\"AUX input\"]', '[\"No pets allowed\",\"No off-roading or driving through flooded areas\"]', 1, '0', 0.00, 0, 8.4312419, 125.9831042, '0', 'uploads/or_1763396591_3720.jpg', 'uploads/cr_1763396591_3812.jpg', '[]', NULL, 0, 'pending', 5),
(17, 5, 'ref', 'wee', '2025', '3-Door Hatchback', 'Automatic', 'Gasoline', 'Audi', 'A1', 'Audi A1', 'N/A', '123', 1000.00, 'uploads/car_1763430946_7051.jpg', NULL, 'None', '2025-11-18 01:55:46', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\",\"All-wheel drive\"]', '[\"No Littering\"]', 1, '0', 0.00, 0, 8.430187499999999, 125.98298439999998, '0', 'uploads/or_1763430946_2587.jpg', 'uploads/cr_1763430946_5849.jpg', '[]', '', 0, 'approved', 5),
(18, 5, 'black', 'wow', '2025', '3-Door Hatchback', 'Automatic', 'Gasoline', 'Audi', 'A1', 'Audi A1', 'N/A', '1233678900', 100.00, 'uploads/car_1763433044_8701.jpg', NULL, 'None', '2025-11-18 02:30:44', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.429774758408513, 125.98353150875825, '0', 'uploads/or_1763433044_9642.jpg', 'uploads/cr_1763433044_9489.jpg', '[]', '', 0, 'rejected', 5),
(24, 8, 'red', 'wow', '2025', '3-Door Hatchback', 'Automatic', 'Gasoline', 'Audi', 'A1', 'Audi A1', 'N/A', '12334', 5000.00, 'uploads/car_1763532624_4856.jpg', NULL, 'None', '2025-11-19 06:10:24', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\",\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.5104666, 125.9732381, '0', 'uploads/or_1763532624_2368.jpg', 'uploads/cr_1763532624_4208.jpg', '[]', '', 0, 'approved', 5),
(25, 1, 'red', 'wow', '2025', '3-Door Hatchback', 'Automatic', 'Gasoline', 'Audi', 'A1', 'Audi A1', 'N/A', '12344', 1000.00, 'uploads/car_1763534450_8284.jpg', NULL, 'None', '2025-11-19 06:40:50', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"Pet-friendly\",\"Keyless entry\"]', '[\"No vaping/smoking\",\"No pets allowed\"]', 1, '0', 0.00, 0, 8.5104666, 125.9732381, '0', 'uploads/or_1763534450_1401.jpg', 'uploads/cr_1763534450_6478.jpg', '[]', '', 0, 'approved', 5),
(26, 1, 'yellow', 'wow', '2025', '3-Door Hatchback', 'Automatic', 'Gasoline', 'Audi', 'A1', 'Audi A1', 'N/A', '12234', 1000.00, 'uploads/car_1764057686_2758.jpg', NULL, 'None', '2025-11-25 08:01:26', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\"]', '[\"No Littering\"]', 1, '0', 0.00, 0, 8.5095913, 125.9726732, '0', 'uploads/or_1764057686_7059.jpg', 'uploads/cr_1764057686_8209.jpg', '[]', '', 0, 'approved', 5),
(28, 1, 'yellow', 'wow', '2017', 'Sedan', 'Automatic', 'Gasoline', 'Toyota', 'Vios', 'Toyota Vios', 'Base', '051204', 500.00, 'uploads/car_1764161507_3067.jpg', NULL, 'None', '2025-11-26 12:51:47', '1 hour', '1', '1', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\",\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.4312419, 125.9831042, NULL, 'uploads/or_1764161507_8565.jpg', 'uploads/cr_1764161507_7810.jpg', '[]', 'sorry', 4, 'rejected', 5),
(30, 1, 'black', 'wow', '2025', 'Crossover', 'Automatic', 'Gasoline', 'Subaru', 'BRZ', 'Subaru BRZ', 'Sport', '12345', 600.00, 'uploads/car_1764162427_5356.jpg', 'CXJM+G7X Lapinigan, San Francisco, Caraga', 'None', '2025-11-26 13:07:07', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\",\"All-wheel drive\"]', '[\"Clean As You Go (CLAYGO)\",\"No Littering\"]', 1, '0', 0.00, 0, 8.4312419, 125.9831042, NULL, 'uploads/or_1764162427_2393.jpg', 'uploads/cr_1764162427_5538.jpg', '[]', 'pangit', 4, 'rejected', 5),
(31, 1, 'red', 'wow', '2025', 'Sedan', 'Automatic', 'Gasoline', 'Toyota', 'Vios', 'Toyota Vios', 'N/A', '11234', 800.00, 'uploads/car_1764549818_8688.jpg', 'Purok 4, San Francisco, Caraga', 'None', '2025-12-01 00:43:38', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\",\"All-wheel drive\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.4319398, 125.9830886, NULL, 'uploads/or_1764549818_5991.jpg', 'uploads/cr_1764549818_2590.jpg', '[]', '', 4, 'approved', 5),
(32, 1, 'blue', 'wow', '2025', 'Sedan', 'Automatic', 'Gasoline', 'Toyota', 'Vios', 'Toyota Vios', 'Sport', '4566778', 900.00, 'uploads/car_1764549889_5758.jpg', 'Purok 4, San Francisco, Caraga', 'None', '2025-12-01 00:44:49', '1 hour', '1', '1', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\"]', '[\"No eating or drinking inside\"]', 1, '0', 0.00, 0, 8.4319398, 125.9830886, NULL, 'uploads/or_1764549889_3847.jpg', 'uploads/cr_1764549889_1909.jpg', '[]', '', 4, 'approved', 5);

-- --------------------------------------------------------

--
-- Table structure for table `car_photos`
--

CREATE TABLE `car_photos` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `spot_number` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_ratings`
--

CREATE TABLE `car_ratings` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_rules`
--

CREATE TABLE `car_rules` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `rule` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `motorcycles`
--

CREATE TABLE `motorcycles` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `color` varchar(100) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `motorcycle_year` varchar(50) NOT NULL,
  `body_style` varchar(200) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `engine_displacement` varchar(100) NOT NULL,
  `plate_number` varchar(30) DEFAULT NULL,
  `price_per_day` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `advance_notice` varchar(100) DEFAULT NULL,
  `min_trip_duration` varchar(100) DEFAULT NULL,
  `max_trip_duration` varchar(100) DEFAULT NULL,
  `delivery_types` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `has_unlimited_mileage` tinyint(1) DEFAULT 1,
  `daily_rate` decimal(10,2) DEFAULT 0.00,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `official_receipt` text DEFAULT NULL,
  `certificate_of_registration` text DEFAULT NULL,
  `extra_images` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','disabled') DEFAULT 'pending',
  `rating` float DEFAULT 5,
  `transmission_type` enum('Manual','Automatic','Semi-Automatic') DEFAULT 'Manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `read_status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `read_status`, `created_at`) VALUES
(25, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'unread', '2025-11-18 03:28:24'),
(28, 5, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'unread', '2025-11-18 03:40:36'),
(29, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: pangit uyy', 'unread', '2025-11-18 03:40:52'),
(38, 5, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'unread', '2025-11-19 06:08:43'),
(39, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: adf', 'unread', '2025-11-19 06:08:46'),
(40, 8, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'unread', '2025-11-19 06:10:32'),
(41, 8, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'unread', '2025-11-19 06:10:59'),
(42, 8, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'unread', '2025-11-19 06:11:08'),
(50, 5, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'unread', '2025-11-25 08:05:01'),
(64, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'unread', '2025-11-25 13:33:40'),
(65, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'unread', '2025-11-25 13:35:24'),
(66, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'unread', '2025-11-25 13:36:49'),
(73, 1, 'Car Approved ✔️', 'Your vehicle \'Subaru BRZ\' has been approved and is now visible to renters.', 'unread', '2025-11-29 14:40:48'),
(75, 1, 'Car Rejected ❌', 'Your vehicle \'Toyota Vios\' was rejected. Reason: not match', 'unread', '2025-11-29 14:53:57'),
(76, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'unread', '2025-11-29 14:54:13'),
(77, 1, 'Car Rejected ❌', 'Your vehicle \'Toyota Vios\' was rejected. Reason: sorry', 'unread', '2025-11-29 15:21:33'),
(78, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'unread', '2025-12-01 00:45:46'),
(79, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'unread', '2025-12-01 00:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Owner','Renter') DEFAULT NULL,
  `municipality` varchar(200) NOT NULL,
  `address` varchar(50) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `municipality`, `address`, `phone`, `profile_image`, `created_at`) VALUES
(1, 'Cartney Dejolde jr', 'cart@gmail.com', '12345', 'Owner', '', 'lapinigan SFADS', '09276654209', 'profile_1_1763534567.jpg', '2025-11-12 11:38:49'),
(3, 'cartney dejolde', 'cartskie@gmail.com', '12345', 'Owner', '', 'lapinigan', '097712345', 'profile_3_1763342696.jpg', '2025-11-12 12:03:33'),
(4, 'kristian', 'kristian@gmail.com', '12345', 'Renter', '', '', '0', '', '2025-11-13 06:58:26'),
(5, 'ethan', 'ethan@gmail.com', '12345', 'Owner', '', 'san Francisco ADS', '0123456789', 'profile_5_1763266478.jpg', '2025-11-13 23:47:33'),
(6, 'Johan Malanog', 'johan@gmail.com', '12345', 'Owner', '', '', NULL, NULL, '2025-11-16 03:29:43'),
(7, 'ethan jr', 'renter@gmail.com', '12345', 'Renter', '', 'Lapinigan SFADS', '09123456789', 'user_7_1764555328.jpg', '2025-11-18 09:27:46'),
(8, 'migs', 'migs@gmail.com', '12345', 'Owner', '', '', NULL, NULL, '2025-11-19 06:09:08'),
(9, 'mikko johan', 'johanmalanog@gmail.com', '12345', 'Renter', 'San Francisco', '', NULL, NULL, '2025-11-25 08:49:12'),
(10, 'cart ney', 'owner@gmail.com', '12345', 'Owner', 'San Francisco', '', NULL, NULL, '2025-11-29 11:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile_number` varchar(50) NOT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `id_type` varchar(100) NOT NULL,
  `id_front_photo` varchar(255) NOT NULL,
  `id_back_photo` varchar(255) NOT NULL,
  `selfie_photo` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `user_verifications`
--
DELIMITER $$
CREATE TRIGGER `prevent_duplicate_verification` BEFORE INSERT ON `user_verifications` FOR EACH ROW BEGIN
  DECLARE existing_count INT;
  
  SELECT COUNT(*) INTO existing_count
  FROM user_verifications
  WHERE user_id = NEW.user_id 
    AND status IN ('pending', 'approved');
  
  IF existing_count > 0 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'User already has a pending or approved verification';
  END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `status` (`status`),
  ADD KEY `pickup_date` (`pickup_date`),
  ADD KEY `idx_booking_dates` (`pickup_date`,`return_date`),
  ADD KEY `idx_booking_status` (`status`,`created_at`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_approved_by` (`approved_by`),
  ADD KEY `idx_owner_id` (`owner_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `car_photos`
--
ALTER TABLE `car_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `car_ratings`
--
ALTER TABLE `car_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `car_rules`
--
ALTER TABLE `car_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `motorcycles`
--
ALTER TABLE `motorcycles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_notification_user_status` (`user_id`,`read_status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_verification_status` (`status`),
  ADD KEY `idx_verification_user` (`user_id`,`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `car_photos`
--
ALTER TABLE `car_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_ratings`
--
ALTER TABLE `car_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_rules`
--
ALTER TABLE `car_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motorcycles`
--
ALTER TABLE `motorcycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_car_fk` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `car_photos`
--
ALTER TABLE `car_photos`
  ADD CONSTRAINT `car_photos_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `car_ratings`
--
ALTER TABLE `car_ratings`
  ADD CONSTRAINT `car_ratings_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `car_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `car_rules`
--
ALTER TABLE `car_rules`
  ADD CONSTRAINT `car_rules_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `motorcycles`
--
ALTER TABLE `motorcycles`
  ADD CONSTRAINT `motorcycles_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
