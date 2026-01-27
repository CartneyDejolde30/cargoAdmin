-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2026 at 07:41 AM
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
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `phone` varchar(11) NOT NULL,
  `profile_image` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `fullname`, `email`, `password`, `phone`, `profile_image`) VALUES
(1, 'cartney dejolde', 'cartney@gmail.com', '12345678', '09770433849', 'uploads/admin/admin_1_1765605888.png');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('booking','payment','verification','report','car','user','system') NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bi-bell',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `read_status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `admin_id`, `type`, `title`, `message`, `link`, `icon`, `priority`, `read_status`, `created_at`, `read_at`) VALUES
(1, NULL, 'booking', 'New Booking Pending', 'Booking #28 requires your approval', 'bookings.php?status=pending', 'bi-calendar-check', 'high', 'read', '2026-01-21 11:18:18', '2026-01-24 05:43:11'),
(2, NULL, 'payment', 'Payment Verification Needed', '3 payments awaiting verification', 'payment.php?status=pending', 'bi-cash-coin', 'high', 'read', '2026-01-21 11:18:18', '2026-01-24 05:42:48'),
(3, NULL, 'verification', 'User Verification Pending', '2 users awaiting identity verification', 'users.php?view=management&verification=pending', 'bi-shield-check', 'medium', 'read', '2026-01-21 11:18:18', '2026-01-24 05:42:58'),
(4, NULL, 'report', 'New Report Filed', 'User reported inappropriate content', 'reports.php?status=pending', 'bi-flag', 'urgent', 'read', '2026-01-21 11:18:18', '2026-01-24 05:42:51'),
(5, NULL, 'car', 'Car Listing Pending', '5 car listings need approval', 'get_cars_admin.php?status=pending', 'bi-car-front', 'medium', 'read', '2026-01-21 11:18:18', '2026-01-24 05:43:06');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `car_id` int(11) NOT NULL,
  `vehicle_type` enum('car','motorcycle') NOT NULL,
  `car_image` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
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
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','ongoing','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','partial','refunded','pending','escrowed','released') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` enum('renter','owner','admin') DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `gcash_number` varbinary(255) DEFAULT NULL,
  `gcash_reference` varbinary(255) DEFAULT NULL,
  `gcash_screenshot` varchar(255) DEFAULT NULL,
  `escrow_status` enum('pending','held','released_to_owner','refunded','released') DEFAULT 'pending',
  `platform_fee` decimal(10,2) DEFAULT 0.00,
  `owner_payout` decimal(10,2) DEFAULT 0.00,
  `payout_reference` varchar(100) DEFAULT NULL,
  `payout_date` timestamp NULL DEFAULT NULL,
  `escrow_held_at` datetime DEFAULT NULL,
  `escrow_released_at` datetime DEFAULT NULL,
  `payout_status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `payout_completed_at` datetime DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `payment_verified_at` datetime DEFAULT NULL,
  `payment_verified_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `is_reviewed` tinyint(1) NOT NULL DEFAULT 0,
  `refund_requested` tinyint(1) DEFAULT 0,
  `refund_status` varchar(20) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `escrow_refunded_at` datetime DEFAULT NULL,
  `escrow_hold_reason` varchar(100) DEFAULT NULL,
  `escrow_hold_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `owner_id`, `car_id`, `vehicle_type`, `car_image`, `location`, `full_name`, `email`, `contact`, `gender`, `book_with_driver`, `rental_period`, `needs_delivery`, `delivery_address`, `special_requests`, `approved_at`, `approved_by`, `rejection_reason`, `rejected_at`, `pickup_date`, `return_date`, `pickup_time`, `return_time`, `price_per_day`, `driver_fee`, `total_amount`, `status`, `payment_status`, `created_at`, `updated_at`, `payment_id`, `payment_method`, `payment_date`, `rating`, `cancellation_reason`, `cancelled_by`, `cancelled_at`, `gcash_number`, `gcash_reference`, `gcash_screenshot`, `escrow_status`, `platform_fee`, `owner_payout`, `payout_reference`, `payout_date`, `escrow_held_at`, `escrow_released_at`, `payout_status`, `payout_completed_at`, `verified_at`, `payment_verified_at`, `payment_verified_by`, `verified_by`, `is_reviewed`, `refund_requested`, `refund_status`, `refund_amount`, `escrow_refunded_at`, `escrow_hold_reason`, `escrow_hold_details`) VALUES
(1, 7, 1, 26, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-13', '2025-12-14', '09:00:00', '05:00:00', 0.00, 0.00, 2100.00, 'approved', 'unpaid', '2025-12-13 06:50:29', '2025-12-20 14:10:53', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(2, 7, 1, 31, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-13', '2025-12-14', '09:00:00', '05:00:00', 0.00, 0.00, 1680.00, 'rejected', 'unpaid', '2025-12-13 07:32:31', '2026-01-12 05:05:19', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 0.00, NULL, NULL, NULL),
(3, 7, 1, 31, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-13', '2025-12-14', '09:00:00', '05:00:00', 0.00, 0.00, 1680.00, 'approved', 'unpaid', '2025-12-13 07:33:18', '2026-01-12 01:19:10', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(4, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09128515463', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-18', '2025-12-19', '09:00:00', '05:00:00', 0.00, 0.00, 2100.00, 'approved', 'unpaid', '2025-12-13 08:14:35', '2025-12-15 12:45:53', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(5, 7, 1, 33, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09451547348', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-22', '2025-12-23', '09:00:00', '05:00:00', 0.00, 0.00, 1743.00, 'rejected', 'pending', '2025-12-22 04:54:45', '2026-01-13 02:37:16', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(6, 11, 1, 33, 'car', NULL, NULL, 'Ethan James Estino', 'saberu1213@gmail.com', '09451547348', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-03', '2026-01-04', '09:00:00', '17:00:00', 830.00, 0.00, 1743.00, 'cancelled', 'paid', '2026-01-03 13:01:25', '2026-01-05 02:26:13', '2', 'gcash', '2026-01-03 13:02:22', 0, NULL, NULL, NULL, 0x3039343531353437333438, 0x31323334353637383931323334, NULL, 'held', 174.30, 1568.70, NULL, NULL, '2026-01-03 21:41:07', NULL, 'pending', NULL, NULL, '2026-01-03 21:41:07', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(7, 7, 1, 34, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-11', '2026-01-12', '09:00:00', '17:00:00', 122.00, 0.00, 256.20, 'approved', 'paid', '2026-01-11 07:04:50', '2026-01-21 02:45:08', '4', 'gcash', '2026-01-11 07:05:05', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 25.62, 230.58, NULL, NULL, '2026-01-21 10:45:08', NULL, 'pending', NULL, NULL, '2026-01-21 10:45:08', 1, NULL, 1, 0, NULL, 0.00, NULL, NULL, NULL),
(8, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-12', '2026-01-13', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'rejected', 'pending', '2026-01-12 02:41:48', '2026-01-20 12:15:54', '6', 'gcash', '2026-01-12 02:42:00', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(9, 7, 1, 26, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-12', '2026-01-13', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'approved', '', '2026-01-12 02:47:38', '2026-01-21 02:37:46', '8', 'gcash', '2026-01-12 02:47:52', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(10, 7, 1, 31, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-12', '2026-01-13', '09:00:00', '17:00:00', 800.00, 0.00, 1680.00, 'rejected', 'pending', '2026-01-12 05:38:38', '2026-01-13 02:37:51', '10', 'gcash', '2026-01-12 05:38:53', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 0.00, NULL, NULL, NULL),
(11, 7, 1, 33, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-12', '2026-01-13', '09:00:00', '17:00:00', 830.00, 0.00, 1743.00, 'rejected', 'pending', '2026-01-12 08:54:57', '2026-01-17 03:59:28', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 0.00, NULL, NULL, NULL),
(14, 7, 1, 1, 'motorcycle', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-12', '2026-01-13', '09:00:00', '17:00:00', 800.00, 0.00, 1680.00, 'pending', 'pending', '2026-01-12 10:45:21', '2026-01-12 10:45:37', '13', 'gcash', '2026-01-12 10:45:37', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(15, 7, 1, 34, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-13', '2026-01-14', '09:00:00', '17:00:00', 122.00, 0.00, 256.20, 'approved', 'paid', '2026-01-12 23:14:35', '2026-01-21 02:47:34', '15', 'gcash', '2026-01-12 23:15:16', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 25.62, 230.58, NULL, NULL, '2026-01-21 10:47:34', NULL, 'pending', NULL, NULL, '2026-01-21 10:47:34', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(16, 7, 1, 34, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-13', '2026-01-14', '09:00:00', '14:00:00', 122.00, 0.00, 256.20, 'approved', 'paid', '2026-01-12 23:15:52', '2026-01-21 02:48:09', '17', 'gcash', '2026-01-12 23:16:15', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 25.62, 230.58, NULL, NULL, '2026-01-21 10:48:09', NULL, 'pending', NULL, NULL, '2026-01-21 10:48:09', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(17, 7, 1, 25, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-13', '2026-01-14', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'cancelled', 'pending', '2026-01-13 02:03:58', '2026-01-21 00:13:37', '19', 'gcash', '2026-01-13 02:04:12', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(18, 7, 1, 34, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-13', '2026-01-14', '09:00:00', '17:00:00', 122.00, 0.00, 1680.00, 'cancelled', 'pending', '2026-01-13 05:26:16', '2026-01-21 00:10:34', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(19, 7, 1, 34, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-13', '2026-01-14', '09:00:00', '17:00:00', 122.00, 0.00, 1680.00, 'approved', 'paid', '2026-01-13 05:26:39', '2026-01-21 02:46:19', '22', 'gcash', '2026-01-13 05:26:51', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 168.00, 1512.00, NULL, NULL, '2026-01-21 10:46:19', NULL, 'pending', NULL, NULL, '2026-01-21 10:46:19', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(23, 7, 1, 35, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-13', '2026-01-14', '09:00:00', '17:00:00', 800.00, 0.00, 1680.00, 'approved', 'pending', '2026-01-13 07:24:40', '2026-01-22 01:34:38', '30', 'gcash', '2026-01-13 07:24:53', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(24, 7, 1, 2, 'motorcycle', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-13', '2026-01-14', '09:00:00', '17:00:00', 500.00, 0.00, 1050.00, 'pending', 'pending', '2026-01-13 07:25:16', '2026-01-13 07:27:27', '32', 'gcash', '2026-01-13 07:25:29', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(25, 7, 1, 1, 'motorcycle', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770436849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-17', '2026-01-18', '09:00:00', '17:00:00', 800.00, 0.00, 1680.00, 'rejected', 'paid', '2026-01-17 00:56:31', '2026-01-21 11:42:47', '34', 'gcash', '2026-01-17 00:56:45', 0, NULL, NULL, NULL, 0x3039373730343336383439, 0x31323334353637383930313233, NULL, 'held', 168.00, 1512.00, NULL, NULL, '2026-01-21 10:55:31', NULL, 'pending', NULL, NULL, '2026-01-21 10:55:31', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(26, 7, 1, 1, 'motorcycle', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-17', '2026-01-18', '09:00:00', '17:00:00', 800.00, 0.00, 1680.00, 'rejected', 'pending', '2026-01-17 01:09:56', '2026-01-21 03:36:17', '36', 'gcash', '2026-01-17 01:10:17', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(27, 7, 1, 31, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-17', '2026-01-18', '09:00:00', '17:00:00', 800.00, 0.00, 1680.00, 'rejected', 'pending', '2026-01-17 01:11:03', '2026-01-20 07:02:33', '38', 'gcash', '2026-01-17 01:11:13', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(28, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433846', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-24', '2026-01-25', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'cancelled', '', '2026-01-18 11:37:05', '2026-01-21 02:37:46', '40', 'gcash', '2026-01-18 11:37:19', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 315.00, 2835.00, NULL, NULL, '2026-01-20 18:14:44', NULL, 'pending', NULL, NULL, '2026-01-20 18:14:44', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(29, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-20', '2026-01-21', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'cancelled', 'pending', '2026-01-20 11:41:41', '2026-01-20 23:55:17', '42', 'gcash', '2026-01-20 11:41:53', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(30, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-29', '2026-01-30', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'rejected', 'paid', '2026-01-20 11:44:53', '2026-01-21 04:34:38', '44', 'gcash', '2026-01-20 11:45:03', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 210.00, 1890.00, NULL, NULL, '2026-01-21 10:54:30', NULL, 'pending', NULL, NULL, '2026-01-21 10:54:30', 1, NULL, 0, 1, 'requested', 2100.00, NULL, NULL, NULL),
(31, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31', '2026-02-01', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'approved', '', '2026-01-20 12:13:30', '2026-01-21 02:37:46', '46', 'gcash', '2026-01-20 12:13:42', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 210.00, 1890.00, NULL, NULL, '2026-01-21 10:01:57', NULL, 'pending', NULL, NULL, '2026-01-21 10:01:57', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(32, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-24', '2026-01-25', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'approved', '', '2026-01-20 12:45:29', '2026-01-21 02:37:46', '48', 'gcash', '2026-01-20 12:45:41', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 210.00, 1890.00, NULL, NULL, '2026-01-21 09:42:38', NULL, 'pending', NULL, NULL, '2026-01-21 09:42:38', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(33, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-25', '2026-01-26', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'approved', 'pending', '2026-01-21 02:56:09', '2026-01-21 03:47:47', '50', 'gcash', '2026-01-21 02:56:25', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(34, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-27', '2026-01-28', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'approved', 'pending', '2026-01-21 02:58:41', '2026-01-21 03:47:55', '52', 'gcash', '2026-01-21 02:58:54', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(35, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21', '2026-01-22', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'completed', 'pending', '2026-01-21 03:07:11', '2026-01-22 00:18:42', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 0.00, 0.00, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(36, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21', '2026-01-22', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'approved', 'paid', '2026-01-21 03:07:11', '2026-01-22 01:17:46', '54', 'gcash', '2026-01-21 03:07:25', 0, NULL, NULL, NULL, 0x3039373730343333383436, 0x31323334353637383930313233, NULL, 'held', 315.00, 2835.00, NULL, NULL, '2026-01-22 09:17:46', NULL, 'pending', NULL, NULL, '2026-01-22 09:17:46', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(37, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31', '2026-02-01', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'approved', 'paid', '2026-01-21 03:19:58', '2026-01-21 03:54:28', '56', 'gcash', '2026-01-21 03:20:13', 0, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 315.00, 2835.00, NULL, NULL, '2026-01-21 11:54:28', NULL, 'pending', NULL, NULL, '2026-01-21 11:54:28', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(38, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433846', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31', '2026-02-01', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'rejected', 'paid', '2026-01-21 03:26:20', '2026-01-21 04:34:10', '58', 'gcash', '2026-01-21 03:26:39', NULL, NULL, NULL, NULL, 0x3039313233343536373839, 0x30393132333435363738393132, NULL, 'held', 315.00, 2835.00, NULL, NULL, '2026-01-21 11:53:44', NULL, 'pending', NULL, NULL, '2026-01-21 11:53:44', 1, NULL, 0, 1, 'requested', 3150.00, NULL, NULL, NULL),
(39, 7, 1, 37, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-28', '2026-01-29', '09:00:00', '17:00:00', 1500.00, 0.00, 3150.00, 'approved', 'paid', '2026-01-21 03:32:32', '2026-01-21 03:52:31', '60', 'gcash', '2026-01-21 03:32:45', NULL, NULL, NULL, NULL, 0x3039313233343536373839, 0x30393132333435363738393132, NULL, 'held', 315.00, 2835.00, NULL, NULL, '2026-01-21 11:52:31', NULL, 'pending', NULL, NULL, '2026-01-21 11:52:31', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(40, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-23', '2026-01-24', '09:00:00', '17:00:00', 1000.00, 0.00, 2100.00, 'cancelled', 'paid', '2026-01-21 10:46:30', '2026-01-24 07:16:37', '62', 'gcash', '2026-01-21 10:46:46', NULL, NULL, NULL, NULL, 0x3039373730343333383439, 0x31323334353637383930313233, NULL, 'held', 210.00, 1890.00, NULL, NULL, '2026-01-24 15:16:37', NULL, 'pending', NULL, NULL, '2026-01-24 15:16:37', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL),
(41, 7, 5, 17, 'car', NULL, NULL, 'ethan jr', 'renter@gmail.com', '09770433849', 'Male', 0, 'Day', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31', '2026-02-28', '09:00:00', '17:00:00', 1000.00, 0.00, 30450.00, 'approved', 'paid', '2026-01-21 10:51:43', '2026-01-25 05:19:43', '64', 'gcash', '2026-01-21 10:51:55', NULL, NULL, NULL, NULL, 0x3039313233343536373839, 0x30393132333435363738393132, NULL, 'held', 3045.00, 27405.00, NULL, NULL, '2026-01-22 09:17:14', NULL, 'pending', NULL, NULL, '2026-01-22 09:17:14', 1, NULL, 0, 0, NULL, 0.00, NULL, NULL, NULL);

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
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
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
  `rating` float DEFAULT 5,
  `transmission` varchar(200) NOT NULL DEFAULT 'Automatic',
  `fuel_type` varchar(200) NOT NULL DEFAULT 'Gasoline',
  `report_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `owner_id`, `color`, `description`, `car_year`, `body_style`, `brand`, `model`, `trim`, `plate_number`, `price_per_day`, `image`, `location`, `issues`, `created_at`, `advance_notice`, `min_trip_duration`, `max_trip_duration`, `delivery_types`, `features`, `rules`, `has_unlimited_mileage`, `mileage_limit`, `daily_rate`, `unlimited_mileage`, `latitude`, `longitude`, `address`, `official_receipt`, `certificate_of_registration`, `extra_images`, `remarks`, `seat`, `status`, `rating`, `transmission`, `fuel_type`, `report_count`) VALUES
(3, 1, 'yellow', NULL, '2020', '4 setter', 'Audi', 'A3', '', '1234-5647', 600.00, 'uploads/car_1763279764.png', 'P1 Lapinigan ADS', 'None', '2025-11-16 07:43:16', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, '', 0, 'approved', 5, '', '', 1),
(16, 1, 'red', 'wow', '2025', '3-Door Hatchback', 'Audi', 'A1', 'N/A', '12345', 2000.00, 'uploads/car_1763396591_4187.jpg', NULL, 'None', '2025-11-17 16:23:11', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\",\"Guest Pickup & Host Collection\"]', '[\"All-wheel drive\",\"Android auto\",\"AUX input\"]', '[\"No pets allowed\",\"No off-roading or driving through flooded areas\"]', 1, '0', 0.00, 0, 8.4312419, 125.9831042, '0', 'uploads/or_1763396591_3720.jpg', 'uploads/cr_1763396591_3812.jpg', '[]', '', 0, 'approved', 5, '', '', 0),
(17, 5, 'ref', 'wee', '2025', '3-Door Hatchback', 'Audi', 'A1', 'N/A', '123', 1000.00, 'uploads/car_1763430946_7051.jpg', NULL, 'None', '2025-11-18 01:55:46', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\",\"All-wheel drive\"]', '[\"No Littering\"]', 1, '0', 0.00, 0, 8.430187499999999, 125.98298439999998, '0', 'uploads/or_1763430946_2587.jpg', 'uploads/cr_1763430946_5849.jpg', '[]', '', 0, 'approved', 5, '', '', 1),
(18, 5, 'black', 'wow', '2025', '3-Door Hatchback', 'Audi', 'A1', 'N/A', '1233678900', 100.00, 'uploads/car_1763433044_8701.jpg', NULL, 'None', '2025-11-18 02:30:44', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.429774758408513, 125.98353150875825, '0', 'uploads/or_1763433044_9642.jpg', 'uploads/cr_1763433044_9489.jpg', '[]', '', 0, 'rejected', 5, '', '', 0),
(24, 8, 'red', 'wow', '2025', '3-Door Hatchback', 'Audi', 'A1', 'N/A', '12334', 5000.00, 'uploads/car_1763532624_4856.jpg', NULL, 'None', '2025-11-19 06:10:24', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\",\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.5104666, 125.9732381, '0', 'uploads/or_1763532624_2368.jpg', 'uploads/cr_1763532624_4208.jpg', '[]', '', 0, 'approved', 5, '', '', 0),
(25, 1, 'red', 'wow', '2025', '3-Door Hatchback', 'Audi', 'A1', 'N/A', '12344', 1000.00, 'uploads/car_1763534450_8284.jpg', NULL, 'None', '2025-11-19 06:40:50', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"Pet-friendly\",\"Keyless entry\"]', '[\"No vaping/smoking\",\"No pets allowed\"]', 1, '0', 0.00, 0, 8.5104666, 125.9732381, '0', 'uploads/or_1763534450_1401.jpg', 'uploads/cr_1763534450_6478.jpg', '[]', '', 0, 'approved', 5, '', '', 0),
(26, 1, 'yellow', 'wow', '2025', '3-Door Hatchback', 'Audi', 'A1', 'N/A', '12234', 1000.00, 'uploads/car_1764057686_2758.jpg', NULL, 'None', '2025-11-25 08:01:26', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\"]', '[\"No Littering\"]', 1, '0', 0.00, 0, 8.5095913, 125.9726732, '0', 'uploads/or_1764057686_7059.jpg', 'uploads/cr_1764057686_8209.jpg', '[]', '', 0, 'approved', 5, '', '', 0),
(28, 1, 'yellow', 'wow', '2017', 'Sedan', 'Toyota', 'Vios', 'Base', '051204', 500.00, 'uploads/car_1764161507_3067.jpg', NULL, 'None', '2025-11-26 12:51:47', '1 hour', '1', '1', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\",\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.4312419, 125.9831042, NULL, 'uploads/or_1764161507_8565.jpg', 'uploads/cr_1764161507_7810.jpg', '[]', '', 4, 'approved', 5, '', '', 0),
(30, 1, 'black', 'wow', '2025', 'Crossover', 'Subaru', 'BRZ', 'Sport', '12345', 600.00, 'uploads/car_1764162427_5356.jpg', 'CXJM+G7X Lapinigan, San Francisco, Caraga', 'None', '2025-11-26 13:07:07', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\",\"All-wheel drive\"]', '[\"Clean As You Go (CLAYGO)\",\"No Littering\"]', 1, '0', 0.00, 0, 8.4312419, 125.9831042, NULL, 'uploads/or_1764162427_2393.jpg', 'uploads/cr_1764162427_5538.jpg', '[]', '', 4, 'approved', 5, '', '', 0),
(31, 1, 'red', 'wow', '2025', 'Sedan', 'Toyota', 'Vios', 'N/A', '11234', 800.00, 'uploads/car_1764549818_8688.jpg', 'Purok 4, San Francisco, Caraga', 'None', '2025-12-01 00:43:38', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\",\"All-wheel drive\"]', '[\"Clean As You Go (CLAYGO)\"]', 1, '0', 0.00, 0, 8.4319398, 125.9830886, NULL, 'uploads/or_1764549818_5991.jpg', 'uploads/cr_1764549818_2590.jpg', '[]', '', 4, 'approved', 5, '', '', 0),
(32, 1, 'blue', 'wow', '2025', 'Sedan', 'Toyota', 'Vios', 'Sport', '4566778', 900.00, 'uploads/car_1764549889_5758.jpg', 'Purok 4, San Francisco, Caraga', 'None', '2025-12-01 00:44:49', '1 hour', '1', '1', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\"]', '[\"No eating or drinking inside\"]', 1, '0', 0.00, 0, 8.4319398, 125.9830886, NULL, 'uploads/or_1764549889_3847.jpg', 'uploads/cr_1764549889_1909.jpg', '[]', 'dont match', 4, 'rejected', 5, '', '', 0),
(33, 1, 'yellow', 'wow', '2025', 'Sedan', 'Toyota', 'Vios', 'N/A', '09876544', 830.00, 'uploads/car_1765107943_3989.jpg', 'P2-Lapinigan, SFADS', 'None', '2025-12-07 11:45:43', '1 hour', '2', '1', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\",\"No eating or drinking inside\"]', 1, '0', 0.00, 0, 8.430216699999999, 125.9751094, NULL, 'uploads/or_1765107943_9142.jpg', 'uploads/cr_1765107943_2706.jpg', '[]', '', 4, 'approved', 5, '', '', 0),
(34, 1, 'red', 'a', '2025', 'Scooter', 'Honda', 'Click 125i', '100-125cc', '1', 122.00, 'uploads/car_1767582182_4309.jpg', '1600 Amphitheatre Pkwy, Mountain View, California', 'None', '2026-01-05 03:03:02', '1 hour', '1', '5', '[\"Guest Pickup & Guest Return\"]', '[\"ABS Brakes\"]', '[\"No Littering\"]', 1, '0', 0.00, 0, 37.4219983, -122.084, NULL, 'uploads/or_1767582182_1623.jpg', 'uploads/cr_1767582182_4905.jpg', '[]', '', 4, 'approved', 5, 'Automatic', 'Gasoline', 0),
(35, 1, 'red', 'wee', '2025', 'Sedan', 'Toyota', 'Vios', 'N/A', '12345', 800.00, 'uploads/car_main_6962ff99bf708.jpg', 'CXJM+G7X, San Francisco, Caraga', 'None', '2026-01-11 01:40:41', '1 hour', '2 days', '1 week', '[\"Guest Pickup & Guest Return\"]', '[\"AUX input\"]', '[\"No Littering\"]', 1, NULL, 0.00, 0, 8.431944, 125.9831046, NULL, 'uploads/or_6962ff99c04bf.jpg', 'uploads/cr_6962ff99c0729.jpg', '[]', '', 4, 'approved', 5, 'Automatic', 'Gasoline', 1),
(36, 1, 'yellow', 'wow', '2025', 'Sedan', 'Toyota', 'Vios', 'N/A', '167e8e9qoqe', 750.00, 'uploads/car_main_69632368c3936.jpg', 'Purok 4, San Francisco, Caraga', 'None', '2026-01-11 04:13:28', '1 hour', '2 days', '5 days', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\"]', '[\"No Littering\"]', 1, NULL, 0.00, 0, 8.4324983, 125.9820836, NULL, 'uploads/or_69632368c498a.jpg', 'uploads/cr_69632368c4a95.jpg', '[]', NULL, 4, 'pending', 5, 'Automatic', 'Gasoline', 0),
(37, 1, 'red', 'amazing', '2025', 'Sedan', 'Mercedes-Benz', 'A-Class', 'Base', '1463829192', 1500.00, 'uploads/car_main_696cb9e818fae.jpg', 'CXJM+G7X, San Francisco, Caraga', 'None', '2026-01-18 10:46:00', '1 hour', '2 days', '5 days', '[\"Guest Pickup & Guest Return\"]', '[\"All-wheel drive\",\"AUX input\"]', '[\"Clean As You Go (CLAYGO)\",\"No Littering\",\"No eating or drinking inside\"]', 1, NULL, 0.00, 0, 8.4319229, 125.9830948, NULL, 'uploads/or_696cb9e819155.jpg', 'uploads/cr_696cb9e819258.jpg', '[\"uploads\\/extra_696cb9e81934c.jpg\",\"uploads\\/extra_696cb9e81944f.jpg\",\"uploads\\/extra_696cb9e819536.jpg\"]', '', 4, 'approved', 5, 'Automatic', 'Gasoline', 1);

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
-- Table structure for table `escrow`
--

CREATE TABLE `escrow` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('held','released','refunded') DEFAULT 'held',
  `held_at` datetime NOT NULL,
  `released_at` datetime DEFAULT NULL,
  `release_reason` text DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `escrow`
--

INSERT INTO `escrow` (`id`, `booking_id`, `payment_id`, `amount`, `status`, `held_at`, `released_at`, `release_reason`, `refunded_at`, `refund_reason`, `processed_by`, `created_at`, `updated_at`) VALUES
(3, 28, 40, 3150.00, 'held', '2026-01-20 17:54:53', NULL, NULL, NULL, NULL, 1, '2026-01-20 09:54:53', '2026-01-20 09:54:53'),
(5, 32, 48, 2100.00, 'held', '2026-01-21 09:42:13', NULL, NULL, NULL, NULL, 1, '2026-01-21 01:42:13', '2026-01-21 01:42:13'),
(7, 31, 46, 2100.00, 'held', '2026-01-21 09:52:05', NULL, NULL, NULL, NULL, 1, '2026-01-21 01:52:05', '2026-01-21 01:52:05'),
(9, 7, 4, 256.20, 'held', '2026-01-21 10:44:34', NULL, NULL, NULL, NULL, 1, '2026-01-21 02:44:34', '2026-01-21 02:44:34'),
(11, 19, 22, 1680.00, 'held', '2026-01-21 10:46:19', NULL, NULL, NULL, NULL, 1, '2026-01-21 02:46:19', '2026-01-21 02:46:19'),
(12, 15, 15, 256.20, 'held', '2026-01-21 10:47:34', NULL, NULL, NULL, NULL, 1, '2026-01-21 02:47:34', '2026-01-21 02:47:34'),
(13, 16, 17, 256.20, 'held', '2026-01-21 10:48:09', NULL, NULL, NULL, NULL, 1, '2026-01-21 02:48:09', '2026-01-21 02:48:09'),
(14, 30, 44, 2100.00, 'held', '2026-01-21 10:54:30', NULL, NULL, NULL, NULL, 1, '2026-01-21 02:54:30', '2026-01-21 02:54:30'),
(15, 25, 34, 1680.00, 'held', '2026-01-21 10:55:18', NULL, NULL, NULL, NULL, 1, '2026-01-21 02:55:18', '2026-01-21 02:55:18'),
(17, 39, 60, 3150.00, 'held', '2026-01-21 11:52:28', NULL, NULL, NULL, NULL, 1, '2026-01-21 03:52:28', '2026-01-21 03:52:28'),
(18, 39, 59, 3150.00, 'held', '2026-01-21 11:52:31', NULL, NULL, NULL, NULL, 1, '2026-01-21 03:52:31', '2026-01-21 03:52:31'),
(19, 38, 58, 3150.00, 'held', '2026-01-21 11:52:48', NULL, NULL, NULL, NULL, 1, '2026-01-21 03:52:48', '2026-01-21 03:52:48'),
(21, 37, 56, 3150.00, 'held', '2026-01-21 11:54:22', NULL, NULL, NULL, NULL, 1, '2026-01-21 03:54:22', '2026-01-21 03:54:22'),
(23, 41, 64, 30450.00, 'held', '2026-01-22 09:17:14', NULL, NULL, NULL, NULL, 1, '2026-01-22 01:17:14', '2026-01-22 01:17:14'),
(24, 36, 54, 3150.00, 'held', '2026-01-22 09:17:46', NULL, NULL, NULL, NULL, 1, '2026-01-22 01:17:46', '2026-01-22 01:17:46'),
(25, 40, 62, 2100.00, 'held', '2026-01-24 15:16:37', NULL, NULL, NULL, NULL, 1, '2026-01-24 07:16:37', '2026-01-24 07:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `escrow_transactions`
--

CREATE TABLE `escrow_transactions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `transaction_type` enum('payment_received','payment_verified','funds_held','payout_to_owner','refund_to_renter') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `gcash_reference` varchar(100) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL COMMENT 'Admin ID who processed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gps_locations`
--

CREATE TABLE `gps_locations` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `speed` decimal(5,2) DEFAULT NULL,
  `accuracy` decimal(6,2) DEFAULT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `gps_locations`
--

INSERT INTO `gps_locations` (`id`, `booking_id`, `latitude`, `longitude`, `speed`, `accuracy`, `timestamp`) VALUES
(2, 7, 8.43190000, 125.98310000, 25.50, 10.00, '2026-01-25 13:23:09'),
(3, 7, 8.43200000, 125.98320000, 30.00, 8.00, '2026-01-25 13:22:09'),
(4, 7, 8.43180000, 125.98300000, 20.00, 12.00, '2026-01-25 13:21:09'),
(40, 41, 8.41190000, 125.96310000, 41.00, 14.00, '2026-01-25 14:29:47'),
(41, 41, 8.41390000, 125.96510000, 48.00, 12.00, '2026-01-25 14:27:47'),
(42, 41, 8.41590000, 125.96710000, 54.00, 9.00, '2026-01-25 14:25:47'),
(43, 41, 8.41790000, 125.96910000, 42.00, 11.00, '2026-01-25 14:23:47'),
(44, 41, 8.41990000, 125.97110000, 29.00, 8.00, '2026-01-25 14:21:47'),
(45, 41, 8.42190000, 125.97310000, 34.00, 11.00, '2026-01-25 14:19:47'),
(46, 41, 8.42390000, 125.97510000, 45.00, 11.00, '2026-01-25 14:17:47'),
(47, 41, 8.42590000, 125.97710000, 54.00, 6.00, '2026-01-25 14:15:47'),
(48, 41, 8.42790000, 125.97910000, 28.00, 11.00, '2026-01-25 14:13:47'),
(49, 41, 8.42990000, 125.98110000, 20.00, 15.00, '2026-01-25 14:11:47'),
(50, 41, 8.43190000, 125.98310000, 26.00, 6.00, '2026-01-25 14:09:47'),
(51, 41, 8.43390000, 125.98510000, 34.00, 5.00, '2026-01-25 14:07:47'),
(52, 41, 8.43590000, 125.98710000, 21.00, 10.00, '2026-01-25 14:05:47'),
(53, 41, 8.43790000, 125.98910000, 57.00, 15.00, '2026-01-25 14:03:47'),
(54, 41, 8.43990000, 125.99110000, 43.00, 12.00, '2026-01-25 14:01:47'),
(55, 41, 8.44190000, 125.99310000, 40.00, 9.00, '2026-01-25 13:59:47'),
(56, 41, 8.44390000, 125.99510000, 51.00, 14.00, '2026-01-25 13:57:47'),
(57, 41, 8.44590000, 125.99710000, 54.00, 15.00, '2026-01-25 13:55:47'),
(58, 41, 8.44790000, 125.99910000, 49.00, 12.00, '2026-01-25 13:53:47'),
(59, 41, 8.44990000, 126.00110000, 22.00, 9.00, '2026-01-25 13:51:47'),
(60, 32, 8.43190000, 125.98310000, 45.00, 10.00, '2026-01-25 14:25:30'),
(61, 32, 8.43200000, 125.98320000, 50.00, 8.00, '2026-01-25 14:27:30'),
(62, 32, 8.43210000, 125.98330000, 40.00, 12.00, '2026-01-25 14:30:30');

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
  `transmission_type` enum('Manual','Automatic','Semi-Automatic') DEFAULT 'Manual',
  `report_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motorcycles`
--

INSERT INTO `motorcycles` (`id`, `owner_id`, `color`, `description`, `motorcycle_year`, `body_style`, `brand`, `model`, `engine_displacement`, `plate_number`, `price_per_day`, `image`, `location`, `created_at`, `advance_notice`, `min_trip_duration`, `max_trip_duration`, `delivery_types`, `features`, `rules`, `has_unlimited_mileage`, `daily_rate`, `latitude`, `longitude`, `official_receipt`, `certificate_of_registration`, `extra_images`, `remarks`, `status`, `rating`, `transmission_type`, `report_count`) VALUES
(1, 1, 'red', 'wow', '2025', 'Scooter', 'Honda', 'Click 125i', '100-125cc', '12345', 800.00, 'uploads/motorcycle_main_69632406d1cc8.jpg', 'CXJM+G7X Lapinigan, San Francisco, Caraga', '2026-01-11 04:16:06', '1 hour', '2 days', '1 week', '[\"Guest Pickup & Guest Return\"]', '[\"Traction Control\"]', '[\"No vaping/smoking\"]', 1, 0.00, 8.4312419, 125.9831042, 'uploads/or_69632406d1f57.jpg', 'uploads/cr_69632406d200f.jpg', '[]', '', 'approved', 5, 'Manual', 0),
(2, 1, 'black', 'wow', '2025', 'Standard/Naked', 'Honda', 'Wave 110', '100-125cc', '12345', 500.00, 'uploads/motorcycle_main_6963250d10290.jpg', 'p2 lapinigan', '2026-01-11 04:20:29', '1 hour', '2 days', '1 week', '[\"Guest Pickup & Guest Return\"]', '[\"Traction Control\",\"Riding Modes\"]', '[\"No eating or drinking inside\"]', 1, 0.00, 8.430216699999999, 125.9751094, 'uploads/or_6963250d103bc.jpg', 'uploads/cr_6963250d10473.jpg', '[\"uploads\\/extra_6963250d10511.jpg\",\"uploads\\/extra_6963250d105f0.jpg\"]', '', 'approved', 5, 'Manual', 0),
(3, 1, 'blue', 'wew', '2025', 'Café Racer', 'CFMoto', '400NK', '100-125cc', '12345677', 750.00, 'uploads/motorcycle_main_696aec2843857.jpg', 'Purok 4, San Francisco, Caraga', '2026-01-17 01:55:52', '3 hours', '3 days', '2 weeks', '[\"Guest Pickup & Guest Return\"]', '[\"ABS Brakes\"]', '[\"No Littering\",\"No eating or drinking inside\"]', 1, 0.00, 8.432009, 125.9829288, 'uploads/or_696aec2843b36.jpg', 'uploads/cr_696aec2843c07.jpg', '[]', '', 'approved', 5, 'Manual', 0),
(4, 5, 'red', 'wew', '2025', 'Touring', 'Kymco', 'Xciting 400i', '100-125cc', '987268191', 850.00, 'uploads/motorcycle_main_696aed424d965.jpg', 'P-2, San Francisco, Caraga', '2026-01-17 02:00:34', '1 hour', '2 days', '1 week', '[\"Guest Pickup & Guest Return\"]', '[\"Traction Control\",\"Riding Modes\"]', '[\"No Littering\",\"No eating or drinking inside\"]', 1, 0.00, 8.4317083, 125.9814032, 'uploads/or_696aed424dc70.jpg', 'uploads/cr_696aed424dd2e.jpg', '[]', '', 'approved', 5, 'Manual', 0);

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
(25, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'read', '2025-11-18 03:28:24'),
(28, 5, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'read', '2025-11-18 03:40:36'),
(29, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: pangit uyy', 'read', '2025-11-18 03:40:52'),
(38, 5, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'read', '2025-11-19 06:08:43'),
(39, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: adf', 'read', '2025-11-19 06:08:46'),
(40, 8, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'unread', '2025-11-19 06:10:32'),
(41, 8, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'unread', '2025-11-19 06:10:59'),
(42, 8, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'unread', '2025-11-19 06:11:08'),
(50, 5, 'Car Approved 🚗', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'read', '2025-11-25 08:05:01'),
(64, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'read', '2025-11-25 13:33:40'),
(65, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'read', '2025-11-25 13:35:24'),
(66, 5, 'Car Rejected ❌', 'Your vehicle \'Audi A1\' was rejected. Reason: ', 'read', '2025-11-25 13:36:49'),
(73, 1, 'Car Approved ✔️', 'Your vehicle \'Subaru BRZ\' has been approved and is now visible to renters.', 'read', '2025-11-29 14:40:48'),
(75, 1, 'Car Rejected ❌', 'Your vehicle \'Toyota Vios\' was rejected. Reason: not match', 'read', '2025-11-29 14:53:57'),
(76, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'read', '2025-11-29 14:54:13'),
(77, 1, 'Car Rejected ❌', 'Your vehicle \'Toyota Vios\' was rejected. Reason: sorry', 'read', '2025-11-29 15:21:33'),
(78, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'read', '2025-12-01 00:45:46'),
(79, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'read', '2025-12-01 00:45:53'),
(80, 7, 'Booking Approved', 'Your booking for Audi has been approved by the owner.', 'unread', '2025-12-07 10:00:53'),
(81, 1, 'You Approved a Booking', 'You approved booking #1 for Audi.', 'read', '2025-12-07 10:00:53'),
(82, 7, 'Booking Approved', 'Your booking for Audi has been approved by the owner.', 'unread', '2025-12-07 10:03:26'),
(83, 1, 'You Approved a Booking', 'You approved booking #4 for Audi.', 'read', '2025-12-07 10:03:26'),
(84, 1, 'Car Approved ✔️', 'Your vehicle \'Audi A1\' has been approved and is now visible to renters.', 'read', '2025-12-07 11:42:32'),
(85, 1, 'Car Approved ✔️', 'Your vehicle \'Subaru BRZ\' has been approved and is now visible to renters.', 'read', '2025-12-07 12:35:55'),
(86, 7, 'Booking Approved', 'Your booking for Toyota has been approved by the owner.', 'unread', '2025-12-07 12:43:13'),
(87, 1, 'You Approved a Booking', 'You approved booking #5 for Toyota.', 'read', '2025-12-07 12:43:13'),
(88, 7, 'Booking Approved', 'Your booking for Audi has been approved by the owner.', 'unread', '2025-12-07 12:43:36'),
(89, 1, 'You Approved a Booking', 'You approved booking #6 for Audi.', 'read', '2025-12-07 12:43:36'),
(90, 7, 'Booking Rejected', 'Your booking for Audi was rejected. Reason: pangit', 'unread', '2025-12-07 13:09:57'),
(91, 1, 'Booking Rejected', 'You rejected booking #7 for Audi.', 'read', '2025-12-07 13:09:57'),
(92, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'read', '2025-12-10 03:43:30'),
(93, 1, 'Car Rejected ❌', 'Your vehicle \'Toyota Vios\' was rejected. Reason: wla lang', 'read', '2025-12-10 11:30:52'),
(94, 1, 'Car Rejected ❌', 'Your vehicle \'Toyota Vios\' was rejected. Reason: dont match', 'read', '2025-12-10 11:37:37'),
(95, 1, 'Car Rejected ❌', 'Your vehicle \'Subaru BRZ\' was rejected. Reason: dont match', 'read', '2025-12-10 11:38:21'),
(96, 7, 'Verification Approved ✓', 'Congratulations! Your identity verification has been approved. You now have full access to all features.', 'unread', '2025-12-10 14:31:38'),
(97, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'read', '2025-12-13 06:34:20'),
(98, 1, 'Verification Approved ✓', 'Congratulations! Your identity verification has been approved. You now have full access to all features.', 'read', '2025-12-13 07:51:08'),
(99, 4, 'Verification Approved ✓', 'Congratulations! Your identity verification has been approved. You now have full access to all features.', 'unread', '2025-12-13 07:54:13'),
(100, 7, 'Booking Approved', 'Your booking for Audi has been approved.', 'unread', '2025-12-15 12:45:53'),
(101, 5, 'You Approved a Booking', 'You approved booking #4 for Audi.', 'read', '2025-12-15 12:45:53'),
(102, 7, 'Booking Approved', 'Your booking for Audi has been approved.', 'unread', '2025-12-20 14:10:53'),
(103, 1, 'You Approved a Booking', 'You approved booking #1 for Audi.', 'read', '2025-12-20 14:10:53'),
(104, 1, 'Car Approved âœ”ï¸', 'Your vehicle \'Subaru BRZ\' has been approved and is now visible to renters.', 'read', '2025-12-22 04:04:47'),
(105, 5, 'Verification Approved ✓', 'Congratulations! Your identity verification has been approved. You now have full access to all features.', 'read', '2025-12-22 05:29:53'),
(108, 1, 'New Booking ðŸš—', 'Booking #6 has been confirmed. Payment received.', 'read', '2026-01-03 13:40:50'),
(110, 1, 'New Booking ðŸš—', 'Booking #6 has been confirmed. Payment received.', 'read', '2026-01-03 13:41:07'),
(111, 1, 'Car Approved âœ”ï¸', 'Your vehicle \'Honda Click 125i\' has been approved and is now visible to renters.', 'read', '2026-01-05 03:04:56'),
(112, 1, 'Car Submitted ✅', 'Your car \'Toyota Vios\' has been submitted for approval.', 'read', '2026-01-11 01:40:41'),
(113, 1, 'Car Approved ✔️', 'Your vehicle \'Toyota Vios\' has been approved and is now visible to renters.', 'read', '2026-01-11 01:41:29'),
(114, 1, 'Car Submitted ✅', 'Your car \'Toyota Vios\' has been submitted for approval.', 'read', '2026-01-11 04:13:28'),
(115, 1, 'Motorcycle Submitted ✅', 'Your motorcycle \'Honda Click 125i\' has been submitted for approval.', 'read', '2026-01-11 04:16:06'),
(116, 1, 'Motorcycle Submitted ✅', 'Your motorcycle \'Honda Wave 110\' has been submitted for approval.', 'read', '2026-01-11 04:20:29'),
(117, 1, 'Motorcycle Approved ✅', 'Your motorcycle \'Honda Wave 110\' has been approved and is now visible to renters.', 'read', '2026-01-11 04:34:52'),
(118, 1, 'Motorcycle Rejected ❌', 'Your motorcycle \'Honda Click 125i\' was rejected. Reason: INvalid', 'read', '2026-01-11 04:45:31'),
(119, 7, 'Booking Approved', 'Your booking for Honda has been approved.', 'unread', '2026-01-11 07:15:39'),
(120, 1, 'You Approved a Booking', 'You approved booking #7 for Honda.', 'read', '2026-01-11 07:15:39'),
(121, 7, 'Booking Rejected', 'Your booking for Toyota was rejected. Reason: not valid', 'unread', '2026-01-11 07:15:50'),
(122, 1, 'Booking Rejected', 'You rejected booking #5 for Toyota.', 'read', '2026-01-11 07:15:50'),
(123, 7, 'Booking Rejected', 'Your booking for Toyota was rejected. Reason: invalid', 'unread', '2026-01-11 07:15:59'),
(124, 1, 'Booking Rejected', 'You rejected booking #2 for Toyota.', 'read', '2026-01-11 07:15:59'),
(125, 1, 'Motorcycle Approved ✅', 'Your motorcycle \'Honda Click 125i\' has been approved and is now visible to renters.', 'read', '2026-01-11 13:00:45'),
(126, 7, 'Booking Approved', 'Your booking for Toyota has been approved.', 'unread', '2026-01-12 01:19:10'),
(127, 1, 'You Approved a Booking', 'You approved booking #3 for Toyota.', 'read', '2026-01-12 01:19:10'),
(128, 7, 'Booking Approved', 'Your booking for Audi has been approved.', 'unread', '2026-01-12 05:39:47'),
(129, 1, 'You Approved a Booking', 'You approved booking #9 for Audi.', 'read', '2026-01-12 05:39:47'),
(130, 7, 'Booking Rejected', 'Your booking for Toyota was rejected. Reason: invalid', 'unread', '2026-01-12 05:39:58'),
(131, 1, 'Booking Rejected', 'You rejected booking #10 for Toyota.', 'read', '2026-01-12 05:39:58'),
(132, 7, 'Booking Approved', 'Your booking for Audi has been approved.', 'unread', '2026-01-13 02:06:18'),
(133, 1, 'You Approved a Booking', 'You approved booking #17 for Audi.', 'read', '2026-01-13 02:06:18'),
(134, 7, 'Booking Rejected', 'Your booking for Toyota was rejected. Reason: invalid', 'unread', '2026-01-13 07:11:44'),
(135, 1, 'Booking Rejected', 'You rejected booking #11 for Toyota.', 'read', '2026-01-13 07:11:44'),
(136, 7, 'Booking Approved', 'Your booking for Honda has been approved.', 'unread', '2026-01-13 07:11:48'),
(137, 1, 'You Approved a Booking', 'You approved booking #15 for Honda.', 'read', '2026-01-13 07:11:48'),
(138, 7, 'Booking Approved', 'Your booking for Honda has been approved.', 'unread', '2026-01-13 07:11:50'),
(139, 1, 'You Approved a Booking', 'You approved booking #16 for Honda.', 'read', '2026-01-13 07:11:50'),
(140, 7, 'Booking Approved', 'Your booking for Honda has been approved.', 'unread', '2026-01-13 07:11:51'),
(141, 1, 'You Approved a Booking', 'You approved booking #18 for Honda.', 'read', '2026-01-13 07:11:51'),
(142, 7, 'Booking Approved', 'Your booking for Honda has been approved.', 'unread', '2026-01-13 07:11:53'),
(143, 1, 'You Approved a Booking', 'You approved booking #19 for Honda.', 'read', '2026-01-13 07:11:53'),
(144, 1, 'Motorcycle Submitted ✅', 'Your motorcycle \'CFMoto 400NK\' has been submitted for approval.', 'read', '2026-01-17 01:55:52'),
(145, 1, 'Motorcycle Approved ✅', 'Your motorcycle \'CFMoto 400NK\' has been approved and is now visible to renters.', 'read', '2026-01-17 01:56:24'),
(146, 5, 'Motorcycle Submitted ✅', 'Your motorcycle \'Kymco Xciting 400i\' has been submitted for approval.', 'read', '2026-01-17 02:00:34'),
(147, 5, 'Motorcycle Approved ✅', 'Your motorcycle \'Kymco Xciting 400i\' has been approved and is now visible to renters.', 'read', '2026-01-17 02:01:34'),
(148, 1, 'Car Submitted ✅', 'Your car \'Mercedes-Benz A-Class\' has been submitted for approval.', 'read', '2026-01-18 10:46:00'),
(149, 1, 'Car Approved ✔️', 'Your vehicle \'Mercedes-Benz A-Class\' has been approved and is now visible to renters.', 'read', '2026-01-18 10:46:53'),
(150, 7, 'Booking Approved', 'Your booking for Mercedes-Benz has been approved.', 'unread', '2026-01-18 11:38:14'),
(151, 1, 'You Approved a Booking', 'You approved booking #28 for Mercedes-Benz.', 'read', '2026-01-18 11:38:14'),
(152, 7, 'Booking Rejected', 'Your booking for Toyota was rejected. Reason: pangit', 'unread', '2026-01-20 07:02:33'),
(153, 1, 'Booking Rejected', 'You rejected booking #27 for Toyota.', 'read', '2026-01-20 07:02:33'),
(154, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-20 09:54:53'),
(155, 1, 'New Booking 🚗', 'Booking #28 has been confirmed. Payment received.', 'read', '2026-01-20 09:54:53'),
(156, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-20 10:14:44'),
(157, 1, 'New Booking 🚗', 'Booking #28 has been confirmed. Payment received.', 'read', '2026-01-20 10:14:44'),
(158, 7, 'Booking Approved', 'Your booking for Audi has been approved.', 'unread', '2026-01-20 11:42:59'),
(159, 5, 'You Approved a Booking', 'You approved booking #29 for Audi.', 'read', '2026-01-20 11:42:59'),
(160, 7, 'Booking Rejected', 'Your booking for Audi was rejected. Reason: pangit', 'unread', '2026-01-20 11:45:28'),
(161, 5, 'Booking Rejected', 'You rejected booking #30 for Audi.', 'read', '2026-01-20 11:45:28'),
(162, 7, 'Booking Rejected', 'Your booking for Audi was rejected. Reason: aaa', 'unread', '2026-01-20 12:14:26'),
(163, 5, 'Booking Rejected', 'You rejected booking #31 for Audi.', 'read', '2026-01-20 12:14:26'),
(164, 7, 'Booking Rejected', 'Your booking for Audi was rejected. Reason: aaa', 'unread', '2026-01-20 12:15:54'),
(165, 5, 'Booking Rejected', 'You rejected booking #8 for Audi.', 'read', '2026-01-20 12:15:54'),
(166, 7, 'Booking Rejected', 'Your booking for Audi was rejected. Reason: assdr', 'unread', '2026-01-20 12:46:03'),
(167, 5, 'Booking Rejected', 'You rejected booking #32 for Audi.', 'read', '2026-01-20 12:46:03'),
(168, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 01:42:13'),
(169, 5, 'New Booking 🚗', 'Booking #32 has been confirmed. Payment received.', 'read', '2026-01-21 01:42:13'),
(170, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 01:42:38'),
(171, 5, 'New Booking 🚗', 'Booking #32 has been confirmed. Payment received.', 'read', '2026-01-21 01:42:38'),
(172, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 01:52:05'),
(173, 5, 'New Booking 🚗', 'Booking #31 has been confirmed. Payment received.', 'read', '2026-01-21 01:52:05'),
(174, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:01:57'),
(175, 5, 'New Booking 🚗', 'Booking #31 has been confirmed. Payment received.', 'read', '2026-01-21 02:01:57'),
(176, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:44:34'),
(177, 1, 'New Booking 🚗', 'Booking #7 has been confirmed. Payment received.', 'read', '2026-01-21 02:44:34'),
(178, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:45:08'),
(179, 1, 'New Booking 🚗', 'Booking #7 has been confirmed. Payment received.', 'read', '2026-01-21 02:45:08'),
(180, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:46:19'),
(181, 1, 'New Booking 🚗', 'Booking #19 has been confirmed. Payment received.', 'read', '2026-01-21 02:46:19'),
(182, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:47:34'),
(183, 1, 'New Booking 🚗', 'Booking #15 has been confirmed. Payment received.', 'read', '2026-01-21 02:47:34'),
(184, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:48:09'),
(185, 1, 'New Booking 🚗', 'Booking #16 has been confirmed. Payment received.', 'read', '2026-01-21 02:48:09'),
(186, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:54:30'),
(187, 5, 'New Booking 🚗', 'Booking #30 has been confirmed. Payment received.', 'read', '2026-01-21 02:54:30'),
(188, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:55:18'),
(189, 1, 'New Booking 🚗', 'Booking #25 has been confirmed. Payment received.', 'read', '2026-01-21 02:55:18'),
(190, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 02:55:31'),
(191, 1, 'New Booking 🚗', 'Booking #25 has been confirmed. Payment received.', 'read', '2026-01-21 02:55:31'),
(192, 7, 'Booking Approved', 'Your booking for Mercedes-Benz has been approved.', 'unread', '2026-01-21 03:47:23'),
(193, 1, 'You Approved a Booking', 'You approved booking #39 for Mercedes-Benz.', 'read', '2026-01-21 03:47:23'),
(194, 7, 'Booking Rejected', 'Your booking for Mercedes-Benz was rejected. Reason: pangit', 'unread', '2026-01-21 03:47:33'),
(195, 1, 'Booking Rejected', 'You rejected booking #38 for Mercedes-Benz.', 'read', '2026-01-21 03:47:33'),
(196, 7, 'Booking Approved', 'Your booking for Mercedes-Benz has been approved.', 'unread', '2026-01-21 03:47:47'),
(197, 1, 'You Approved a Booking', 'You approved booking #33 for Mercedes-Benz.', 'read', '2026-01-21 03:47:47'),
(198, 7, 'Booking Approved', 'Your booking for Mercedes-Benz has been approved.', 'unread', '2026-01-21 03:47:49'),
(199, 1, 'You Approved a Booking', 'You approved booking #37 for Mercedes-Benz.', 'read', '2026-01-21 03:47:49'),
(200, 7, 'Booking Approved', 'Your booking for Mercedes-Benz has been approved.', 'unread', '2026-01-21 03:47:51'),
(201, 1, 'You Approved a Booking', 'You approved booking #35 for Mercedes-Benz.', 'read', '2026-01-21 03:47:51'),
(202, 7, 'Booking Approved', 'Your booking for Mercedes-Benz has been approved.', 'unread', '2026-01-21 03:47:53'),
(203, 1, 'You Approved a Booking', 'You approved booking #36 for Mercedes-Benz.', 'read', '2026-01-21 03:47:53'),
(204, 7, 'Booking Approved', 'Your booking for Mercedes-Benz has been approved.', 'unread', '2026-01-21 03:47:55'),
(205, 1, 'You Approved a Booking', 'You approved booking #34 for Mercedes-Benz.', 'read', '2026-01-21 03:47:55'),
(206, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 03:52:28'),
(207, 1, 'New Booking 🚗', 'Booking #39 has been confirmed. Payment received.', 'read', '2026-01-21 03:52:28'),
(208, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 03:52:31'),
(209, 1, 'New Booking 🚗', 'Booking #39 has been confirmed. Payment received.', 'read', '2026-01-21 03:52:31'),
(210, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 03:52:48'),
(211, 1, 'New Booking 🚗', 'Booking #38 has been confirmed. Payment received.', 'read', '2026-01-21 03:52:48'),
(212, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 03:53:44'),
(213, 1, 'New Booking 🚗', 'Booking #38 has been confirmed. Payment received.', 'read', '2026-01-21 03:53:44'),
(214, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 03:54:22'),
(215, 1, 'New Booking 🚗', 'Booking #37 has been confirmed. Payment received.', 'read', '2026-01-21 03:54:22'),
(216, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-21 03:54:28'),
(218, 7, 'Booking Approved', 'Your booking for Audi has been approved.', 'unread', '2026-01-21 10:50:17'),
(219, 5, 'You Approved a Booking', 'You approved booking #40 for Audi.', 'read', '2026-01-21 10:50:17'),
(220, 7, 'Trip Completed ✓', 'Your rental for booking #35 has been completed. Thank you!', 'unread', '2026-01-22 00:18:42'),
(221, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-22 01:17:14'),
(222, 5, 'New Booking 🚗', 'Booking #41 has been confirmed. Payment received.', 'read', '2026-01-22 01:17:14'),
(223, 7, 'Payment Verified ✓', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-22 01:17:46'),
(224, 1, 'New Booking 🚗', 'Booking #36 has been confirmed. Payment received.', 'read', '2026-01-22 01:17:46'),
(225, 7, 'Booking Approved', 'Your booking for Toyota has been approved.', 'unread', '2026-01-22 01:34:38'),
(226, 1, 'You Approved a Booking', 'You approved booking #23 for Toyota.', 'read', '2026-01-22 01:34:38'),
(227, 7, 'Payment Verified âœ“', 'Your payment has been verified. Booking approved!', 'unread', '2026-01-24 07:16:37'),
(228, 5, 'New Booking ðŸš—', 'Booking #40 has been confirmed. Payment received.', 'unread', '2026-01-24 07:16:37'),
(229, 7, 'Booking Approved', 'Your booking for Audi has been approved.', 'unread', '2026-01-25 05:19:43'),
(230, 5, 'You Approved a Booking', 'You approved booking #41 for Audi.', 'unread', '2026-01-25 05:19:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','verified','rejected','failed','released','refunded') DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `user_id`, `amount`, `payment_method`, `payment_reference`, `payment_status`, `verification_notes`, `verified_by`, `verified_at`, `created_at`, `updated_at`, `payment_date`) VALUES
(4, 7, 7, 256.20, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 10:44:34', '2026-01-11 07:05:05', '2026-01-21 02:44:34', NULL),
(6, 8, 7, 2100.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-12 02:42:00', '2026-01-12 02:42:00', NULL),
(8, 9, 7, 2100.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-12 02:47:52', '2026-01-12 02:47:52', NULL),
(10, 10, 7, 1680.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-12 05:38:53', '2026-01-12 05:38:53', NULL),
(12, 14, 7, 1680.00, 'gcash', 'pi_moiR95hhNKiqT7zJSHzAYikh', 'pending', NULL, NULL, NULL, '2026-01-12 10:45:24', '2026-01-12 10:45:24', NULL),
(13, 14, 7, 1680.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-12 10:45:37', '2026-01-12 10:45:37', NULL),
(15, 15, 7, 256.20, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 10:47:34', '2026-01-12 23:15:16', '2026-01-21 02:47:34', NULL),
(17, 16, 7, 256.20, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 10:48:09', '2026-01-12 23:16:15', '2026-01-21 02:48:09', NULL),
(19, 17, 7, 2100.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-13 02:04:12', '2026-01-13 02:04:12', NULL),
(22, 19, 7, 1680.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 10:46:19', '2026-01-13 05:26:51', '2026-01-21 02:46:19', NULL),
(30, 23, 7, 1680.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-13 07:24:53', '2026-01-13 07:24:53', NULL),
(32, 24, 7, 1050.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-13 07:25:29', '2026-01-13 07:25:29', NULL),
(34, 25, 7, 1680.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 10:55:18', '2026-01-17 00:56:45', '2026-01-21 02:55:18', NULL),
(36, 26, 7, 1680.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-17 01:10:17', '2026-01-17 01:10:17', NULL),
(38, 27, 7, 1680.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-17 01:11:13', '2026-01-17 01:11:13', NULL),
(40, 28, 7, 3150.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-20 17:54:53', '2026-01-18 11:37:19', '2026-01-20 09:54:53', NULL),
(42, 29, 7, 2100.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-20 11:41:53', '2026-01-20 11:41:53', NULL),
(44, 30, 7, 2100.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 10:54:30', '2026-01-20 11:45:03', '2026-01-21 02:54:30', NULL),
(46, 31, 7, 2100.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 09:52:05', '2026-01-20 12:13:42', '2026-01-21 01:52:05', NULL),
(48, 32, 7, 2100.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 09:42:13', '2026-01-20 12:45:41', '2026-01-21 01:42:13', NULL),
(50, 33, 7, 3150.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-21 02:56:25', '2026-01-21 02:56:25', NULL),
(52, 34, 7, 3150.00, 'gcash', '1234567890123', 'pending', NULL, NULL, NULL, '2026-01-21 02:58:54', '2026-01-21 02:58:54', NULL),
(54, 36, 7, 3150.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-22 09:17:46', '2026-01-21 03:07:25', '2026-01-22 01:17:46', NULL),
(56, 37, 7, 3150.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-21 11:54:22', '2026-01-21 03:20:13', '2026-01-21 03:54:22', NULL),
(58, 38, 7, 3150.00, 'gcash', '0912345678912', 'verified', NULL, 1, '2026-01-21 11:52:48', '2026-01-21 03:26:38', '2026-01-21 03:52:48', NULL),
(59, 39, 7, 3150.00, 'paymongo', 'pi_t1ozWc5giVXfHiZVofB54MQg', 'verified', NULL, 1, '2026-01-21 11:52:31', '2026-01-21 03:32:33', '2026-01-21 03:52:31', NULL),
(60, 39, 7, 3150.00, 'gcash', '0912345678912', 'verified', NULL, 1, '2026-01-21 11:52:28', '2026-01-21 03:32:45', '2026-01-21 03:52:28', NULL),
(61, 40, 7, 2100.00, 'paymongo', 'pi_DiGJwgJiyKxCmGFaLBGoYj8G', 'pending', NULL, NULL, NULL, '2026-01-21 10:46:32', '2026-01-21 10:46:32', NULL),
(62, 40, 7, 2100.00, 'gcash', '1234567890123', 'verified', NULL, 1, '2026-01-24 15:16:37', '2026-01-21 10:46:46', '2026-01-24 07:16:37', NULL),
(63, 41, 7, 30450.00, 'paymongo', 'pi_jgtguhJmc9WarZh27bkqVLjp', 'pending', NULL, NULL, NULL, '2026-01-21 10:51:44', '2026-01-21 10:51:44', NULL),
(64, 41, 7, 30450.00, 'gcash', '0912345678912', 'verified', NULL, 1, '2026-01-22 09:17:14', '2026-01-21 10:51:55', '2026-01-22 01:17:14', NULL);

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `trg_payment_verified_to_booking_paid` AFTER UPDATE ON `payments` FOR EACH ROW BEGIN
    -- Only run when status changes to verified
    IF NEW.payment_status = 'verified' 
       AND OLD.payment_status <> 'verified' THEN

        UPDATE bookings
        SET 
            payment_status = 'paid',
            payment_verified_at = NOW()
        WHERE id = NEW.booking_id;

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_attempts`
--

CREATE TABLE `payment_attempts` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `transaction_type` enum('payment','escrow_hold','escrow_release','payout','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`id`, `booking_id`, `transaction_type`, `amount`, `description`, `reference_id`, `metadata`, `created_by`, `created_at`) VALUES
(1, 6, 'payment', 1743.00, 'Payment verified via gcash', NULL, '{\"payment_id\":2,\"payment_reference\":\"1234567891234\",\"payment_method\":\"gcash\"}', 1, '2026-01-03 13:40:50'),
(2, 6, 'escrow_hold', 1743.00, 'Funds held in escrow (ID: 1)', NULL, '{\"escrow_id\":1,\"platform_fee\":174.3,\"owner_payout\":1568.7}', 1, '2026-01-03 13:40:50'),
(3, 6, 'payment', 1743.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":1,\"payment_reference\":\"pi_8THnwFtdixdxoe894rBQok1T\",\"payment_method\":\"paymongo\"}', 1, '2026-01-03 13:41:06'),
(4, 6, 'escrow_hold', 1743.00, 'Funds held in escrow (ID: 2)', NULL, '{\"escrow_id\":2,\"platform_fee\":174.3,\"owner_payout\":1568.7}', 1, '2026-01-03 13:41:07'),
(5, 28, 'payment', 3150.00, 'Payment verified via gcash', NULL, '{\"payment_id\":40,\"payment_reference\":\"1234567890123\",\"payment_method\":\"gcash\"}', 1, '2026-01-20 09:54:53'),
(6, 28, 'escrow_hold', 3150.00, 'Funds held in escrow (ID: 3)', NULL, '{\"escrow_id\":3,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-20 09:54:53'),
(7, 28, 'payment', 3150.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":39,\"payment_reference\":\"pi_iLReMFFhvnuy2MQvnh9KRWoZ\",\"payment_method\":\"paymongo\"}', 1, '2026-01-20 10:14:44'),
(8, 28, 'escrow_hold', 3150.00, 'Funds held in escrow (ID: 4)', NULL, '{\"escrow_id\":4,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-20 10:14:44'),
(9, 32, 'payment', 2100.00, 'Payment verified via gcash', NULL, '{\"payment_id\":48,\"payment_reference\":\"1234567890123\",\"payment_method\":\"gcash\"}', 1, '2026-01-21 01:42:13'),
(10, 32, 'escrow_hold', 2100.00, 'Funds held in escrow (ID: 5)', NULL, '{\"escrow_id\":5,\"platform_fee\":210,\"owner_payout\":1890}', 1, '2026-01-21 01:42:13'),
(11, 32, 'payment', 2100.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":47,\"payment_reference\":\"pi_dJ8xTCaRiT29Fmw9aXQGsDf6\",\"payment_method\":\"paymongo\"}', 1, '2026-01-21 01:42:38'),
(12, 32, 'escrow_hold', 2100.00, 'Funds held in escrow (ID: 6)', NULL, '{\"escrow_id\":6,\"platform_fee\":210,\"owner_payout\":1890}', 1, '2026-01-21 01:42:38'),
(13, 31, 'payment', 2100.00, 'Payment verified via gcash', NULL, '{\"payment_id\":46,\"payment_reference\":\"1234567890123\",\"payment_method\":\"gcash\"}', 1, '2026-01-21 01:52:05'),
(14, 31, 'escrow_hold', 2100.00, 'Funds held in escrow (ID: 7)', NULL, '{\"escrow_id\":7,\"platform_fee\":210,\"owner_payout\":1890}', 1, '2026-01-21 01:52:05'),
(15, 31, 'payment', 2100.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":45,\"payment_reference\":\"pi_GQ7pie212kB4ecK8YTBTGzwT\",\"payment_method\":\"paymongo\"}', 1, '2026-01-21 02:01:57'),
(16, 31, 'escrow_hold', 2100.00, 'Funds held in escrow (ID: 8)', NULL, '{\"escrow_id\":8,\"platform_fee\":210,\"owner_payout\":1890}', 1, '2026-01-21 02:01:57'),
(17, 7, 'payment', 256.20, 'Payment verified via gcash', NULL, '{\"payment_id\":4,\"escrow_id\":9,\"platform_fee\":25.62,\"owner_payout\":230.58}', 1, '2026-01-21 02:44:34'),
(18, 7, 'payment', 256.20, 'Payment verified via paymongo', NULL, '{\"payment_id\":3,\"escrow_id\":10,\"platform_fee\":25.62,\"owner_payout\":230.58}', 1, '2026-01-21 02:45:08'),
(19, 19, 'payment', 1680.00, 'Payment verified via gcash', NULL, '{\"payment_id\":22,\"escrow_id\":11,\"platform_fee\":168,\"owner_payout\":1512}', 1, '2026-01-21 02:46:19'),
(20, 15, 'payment', 256.20, 'Payment verified via gcash', NULL, '{\"payment_id\":15,\"escrow_id\":12,\"platform_fee\":25.62,\"owner_payout\":230.58}', 1, '2026-01-21 02:47:34'),
(21, 16, 'payment', 256.20, 'Payment verified via gcash', NULL, '{\"payment_id\":17,\"escrow_id\":13,\"platform_fee\":25.62,\"owner_payout\":230.58}', 1, '2026-01-21 02:48:09'),
(22, 30, 'payment', 2100.00, 'Payment verified via gcash', NULL, '{\"payment_id\":44,\"escrow_id\":14,\"platform_fee\":210,\"owner_payout\":1890}', 1, '2026-01-21 02:54:30'),
(23, 25, 'payment', 1680.00, 'Payment verified via gcash', NULL, '{\"payment_id\":34,\"escrow_id\":15,\"platform_fee\":168,\"owner_payout\":1512}', 1, '2026-01-21 02:55:18'),
(24, 25, 'payment', 1680.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":33,\"escrow_id\":16,\"platform_fee\":168,\"owner_payout\":1512}', 1, '2026-01-21 02:55:31'),
(25, 39, 'payment', 3150.00, 'Payment verified via gcash', NULL, '{\"payment_id\":60,\"escrow_id\":17,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-21 03:52:28'),
(26, 39, 'payment', 3150.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":59,\"escrow_id\":18,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-21 03:52:31'),
(27, 38, 'payment', 3150.00, 'Payment verified via gcash', NULL, '{\"payment_id\":58,\"escrow_id\":19,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-21 03:52:48'),
(28, 38, 'payment', 3150.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":57,\"escrow_id\":20,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-21 03:53:44'),
(29, 37, 'payment', 3150.00, 'Payment verified via gcash', NULL, '{\"payment_id\":56,\"escrow_id\":21,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-21 03:54:22'),
(30, 37, 'payment', 3150.00, 'Payment verified via paymongo', NULL, '{\"payment_id\":55,\"escrow_id\":22,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-21 03:54:28'),
(31, 41, 'payment', 30450.00, 'Payment verified via gcash', NULL, '{\"payment_id\":64,\"escrow_id\":23,\"platform_fee\":3045,\"owner_payout\":27405}', 1, '2026-01-22 01:17:14'),
(32, 36, 'payment', 3150.00, 'Payment verified via gcash', NULL, '{\"payment_id\":54,\"escrow_id\":24,\"platform_fee\":315,\"owner_payout\":2835}', 1, '2026-01-22 01:17:46'),
(33, 40, 'payment', 2100.00, 'Payment verified via gcash', NULL, '{\"payment_id\":62,\"escrow_id\":25,\"platform_fee\":210,\"owner_payout\":1890}', 1, '2026-01-24 07:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `escrow_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL,
  `net_amount` decimal(10,2) NOT NULL,
  `payout_method` varchar(50) DEFAULT 'gcash',
  `payout_account` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `scheduled_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `completion_reference` varchar(100) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payout_requests`
--

CREATE TABLE `payout_requests` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gcash_number` varchar(15) NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `payout_reference` varchar(100) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` varchar(50) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platform_settings`
--

INSERT INTO `platform_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`, `created_at`) VALUES
(1, 'platform_commission_rate', '10', 'decimal', 'Platform commission percentage (e.g., 10 for 10%)', NULL, '2025-12-15 02:18:11', '2025-12-15 02:18:11'),
(2, 'escrow_release_days', '3', 'integer', 'Days to hold payment in escrow after rental completion', NULL, '2025-12-15 02:18:11', '2025-12-15 02:18:11'),
(3, 'gcash_account_number', '09123456789', 'string', 'Platform GCash account for receiving payments', NULL, '2025-12-15 02:18:11', '2025-12-15 02:18:11'),
(4, 'gcash_account_name', 'CarGO Rentals', 'string', 'Platform GCash account name', NULL, '2025-12-15 02:18:11', '2025-12-15 02:18:11'),
(5, 'minimum_payout_amount', '100', 'decimal', 'Minimum amount required for payout processing', NULL, '2025-12-15 02:18:11', '2025-12-15 02:18:11'),
(6, 'auto_payout_enabled', 'true', 'boolean', 'Enable automatic payout processing after escrow release', NULL, '2025-12-15 02:18:11', '2025-12-15 02:18:11');

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `receipt_url` varchar(500) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'generated',
  `generated_at` datetime DEFAULT current_timestamp(),
  `emailed_at` datetime DEFAULT NULL,
  `email_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `refund_id` varchar(50) DEFAULT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `original_amount` decimal(10,2) DEFAULT NULL,
  `refund_method` varchar(50) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `refund_reason` varchar(100) NOT NULL,
  `reason_details` text DEFAULT NULL,
  `original_payment_method` varchar(50) DEFAULT NULL,
  `original_payment_reference` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completion_reference` varchar(255) DEFAULT NULL,
  `refund_reference` varchar(100) DEFAULT NULL,
  `transfer_proof` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deduction_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`id`, `refund_id`, `booking_id`, `payment_id`, `user_id`, `owner_id`, `refund_amount`, `original_amount`, `refund_method`, `account_number`, `account_name`, `bank_name`, `refund_reason`, `reason_details`, `original_payment_method`, `original_payment_reference`, `status`, `processed_by`, `processed_at`, `approved_at`, `completed_at`, `completion_reference`, `refund_reference`, `transfer_proof`, `rejection_reason`, `created_at`, `deduction_amount`, `deduction_reason`) VALUES
(1, 'REF-20260121-35B5', 30, 44, 7, 5, 2100.00, 2100.00, 'gcash', '09770433849', 'Cartney Dejolde', NULL, 'cancelled_by_user', NULL, 'gcash', '30', 'approved', 1, '2026-01-21 12:42:56', '2026-01-21 12:42:56', NULL, NULL, NULL, NULL, NULL, '2026-01-21 12:34:38', 0.00, NULL),
(2, 'REF-20260121-6907', 38, 58, 7, 1, 3150.00, 3150.00, 'gcash', '09770433849', 'Cartney Dejolde', NULL, 'cancelled_by_user', NULL, 'gcash', '38', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-21 12:34:10', 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `report_type` enum('car','motorcycle','user','booking','chat') NOT NULL,
  `reported_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','under_review','resolved','dismissed') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `reviewed_by` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `reporter_id`, `report_type`, `reported_id`, `reason`, `details`, `status`, `priority`, `reviewed_by`, `admin_notes`, `review_notes`, `reviewed_at`, `created_at`, `updated_at`, `image_path`) VALUES
(1, 7, 'car', 34, 'Fake photos', 'pangit ka bonding', 'pending', 'medium', NULL, NULL, NULL, NULL, '2026-01-17 11:07:30', '2026-01-20 18:36:26', NULL),
(2, 7, 'car', 33, 'Vehicle not as described', 'not as described', 'pending', 'medium', NULL, NULL, NULL, NULL, '2026-01-17 11:15:17', '2026-01-20 18:36:26', NULL),
(5, 7, 'motorcycle', 2, 'Suspicious pricing', 'overpriced', 'pending', 'medium', NULL, NULL, NULL, NULL, '2026-01-17 11:36:10', '2026-01-20 18:36:26', NULL),
(6, 7, 'user', 1, 'Fake profile', 'fake', 'pending', 'medium', NULL, NULL, NULL, NULL, '2026-01-17 17:45:35', '2026-01-20 18:36:26', NULL),
(7, 7, 'user', 1, 'Suspicious activity', 'suspended', 'pending', 'high', NULL, NULL, NULL, NULL, '2026-01-20 15:00:12', '2026-01-20 20:07:29', NULL),
(8, 7, 'car', 35, 'Suspicious pricing', 'suspek okahshakakajhahahq', 'under_review', 'medium', 1, '', NULL, '2026-01-20 20:05:56', '2026-01-20 19:27:34', '2026-01-20 20:05:56', NULL),
(9, 7, 'car', 37, 'Fake photos', 'fake srthuuhhhhhhhggh', 'pending', 'medium', NULL, NULL, NULL, NULL, '2026-01-20 20:25:17', '2026-01-20 20:25:17', NULL),
(10, 7, 'car', 17, 'Fake photos', 'gggggggggggggggggggg', 'pending', 'medium', NULL, NULL, NULL, NULL, '2026-01-20 20:34:00', '2026-01-20 20:34:00', NULL),
(11, 7, 'car', 3, 'Misleading information', 'gghjkkjhffhjkkkkjjhhjj', 'pending', 'medium', NULL, NULL, NULL, NULL, '2026-01-21 17:55:00', '2026-01-21 17:55:00', 'uploads/reports/report_6970a27414316.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `report_logs`
--

CREATE TABLE `report_logs` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `performed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_logs`
--

INSERT INTO `report_logs` (`id`, `report_id`, `action`, `performed_by`, `notes`, `created_at`) VALUES
(1, 8, 'created', 7, 'Report submitted by user', '2026-01-20 19:27:34'),
(2, 8, 'status_changed_to_under_review', 1, '', '2026-01-20 20:05:56'),
(3, 7, 'priority_changed', 1, 'Priority changed to high', '2026-01-20 20:07:29'),
(4, 9, 'created', 7, 'Report submitted by user', '2026-01-20 20:25:17'),
(5, 10, 'created', 7, 'Report submitted by user', '2026-01-20 20:34:00'),
(6, 11, 'created', 7, 'Report submitted by user', '2026-01-21 17:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `renter_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `rating` decimal(3,1) NOT NULL,
  `review` text NOT NULL,
  `categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`categories`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `booking_id`, `car_id`, `renter_id`, `owner_id`, `rating`, `review`, `categories`, `created_at`) VALUES
(1, 10, 31, 7, 1, 4.4, 'CAR REVIEW:\nExactly as described\n\nOWNER REVIEW:\nGreat communication', '{\"car_rating\":4.8,\"owner_rating\":4,\"car\":{\"Cleanliness\":4,\"Condition\":5,\"Accuracy\":5,\"Value\":5},\"owner\":{\"Communication\":4,\"Responsiveness\":4,\"Friendliness\":4}}', '2026-01-13 02:28:06'),
(2, 10, 31, 7, 1, 4.9, 'CAR REVIEW:\nVery clean and well-maintained\n\nOWNER REVIEW:\nGreat communication', '{\"car_rating\":5,\"owner_rating\":4.7,\"car\":{\"Cleanliness\":5,\"Condition\":5,\"Accuracy\":5,\"Value\":5},\"owner\":{\"Communication\":4,\"Responsiveness\":5,\"Friendliness\":5}}', '2026-01-13 02:37:51'),
(3, 11, 33, 7, 1, 4.4, 'CAR REVIEW:\nExactly as described\n\nOWNER REVIEW:\nGreat communication', '{\"car_rating\":4.5,\"owner_rating\":4.3,\"car\":{\"Cleanliness\":4,\"Condition\":4,\"Accuracy\":5,\"Value\":5},\"owner\":{\"Communication\":5,\"Responsiveness\":4,\"Friendliness\":4}}', '2026-01-17 03:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `facebook_id` varchar(100) DEFAULT NULL,
  `google_uid` varchar(255) DEFAULT NULL,
  `auth_provider` enum('email','google','facebook') DEFAULT 'email',
  `password` varchar(255) NOT NULL,
  `role` enum('Owner','Renter') DEFAULT NULL,
  `municipality` varchar(200) NOT NULL,
  `address` varchar(50) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `fcm_token` text DEFAULT NULL,
  `gcash_number` varchar(15) DEFAULT NULL,
  `gcash_name` varchar(100) DEFAULT NULL,
  `report_count` int(11) DEFAULT 0,
  `api_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `facebook_id`, `google_uid`, `auth_provider`, `password`, `role`, `municipality`, `address`, `phone`, `profile_image`, `created_at`, `last_login`, `fcm_token`, `gcash_number`, `gcash_name`, `report_count`, `api_token`) VALUES
(1, 'Cartney Dejolde jr', 'cart@gmail.com', NULL, NULL, 'email', '12345', 'Owner', '', 'Lapinigan SFADS', '09770433849', 'user_1_1768732059.jpg', '2025-11-12 11:38:49', NULL, NULL, NULL, NULL, 0, 'MXwxNzY5MjM0MzM2'),
(3, 'cartney dejolde', 'cartskie@gmail.com', NULL, NULL, 'email', '12345', 'Owner', '', 'lapinigan', '097712345', 'profile_3_1763342696.jpg', '2025-11-12 12:03:33', NULL, NULL, NULL, NULL, 0, NULL),
(4, 'kristian', 'kristian@gmail.com', NULL, NULL, 'email', '12345', 'Renter', '', 'Pasta SFADS', '09770433849', 'user_4_1765375801.jpg', '2025-11-13 06:58:26', NULL, NULL, NULL, NULL, 0, NULL),
(5, 'ethan', 'ethan@gmail.com', NULL, NULL, 'email', '12345', 'Owner', '', 'san Francisco ADS', '0123456789', 'user_5_1769045131.jpg', '2025-11-13 23:47:33', NULL, NULL, NULL, NULL, 0, 'NXwxNzY5MzIzMDY0'),
(6, 'Johan Malanog', 'johan@gmail.com', NULL, NULL, 'email', '12345', 'Owner', '', '', NULL, NULL, '2025-11-16 03:29:43', NULL, NULL, NULL, NULL, 0, NULL),
(7, 'ethan jr', 'renter@gmail.com', NULL, NULL, 'email', '12345', 'Renter', '', 'Lapinigan SFADS', '09123456789', 'user_7_1765092355.jpg', '2025-11-18 09:27:46', NULL, 'eJ43yxPqQImQcFlosByZl1:APA91bGtY5AXvrwaG8LH3WHDIsqRbZztVFvXwBcI2qjebCGfvw0ZHEZkOizgwpoi6Ox4B8EAbpi_7zvIpJOxyx9vSfPs09bpNqORtJtU0tVDZS5nXs57GYo', NULL, NULL, 0, 'N3wxNzY5MzE3MDcz'),
(8, 'migs', 'migs@gmail.com', NULL, NULL, 'email', '12345', 'Owner', '', '', NULL, NULL, '2025-11-19 06:09:08', NULL, NULL, NULL, NULL, 0, NULL),
(9, 'mikko johan', 'johanmalanog@gmail.com', NULL, NULL, 'email', '12345', 'Renter', 'San Francisco', '', NULL, NULL, '2025-11-25 08:49:12', NULL, NULL, NULL, NULL, 0, NULL),
(10, 'cart ney', 'owner@gmail.com', NULL, NULL, 'email', '12345', 'Owner', 'San Francisco', '', NULL, NULL, '2025-11-29 11:49:29', NULL, NULL, NULL, NULL, 0, NULL);

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
  `region` varchar(200) NOT NULL,
  `province` varchar(200) NOT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `barangay` varchar(200) NOT NULL,
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
-- Dumping data for table `user_verifications`
--

INSERT INTO `user_verifications` (`id`, `user_id`, `first_name`, `last_name`, `email`, `mobile_number`, `gender`, `region`, `province`, `municipality`, `barangay`, `date_of_birth`, `id_type`, `id_front_photo`, `id_back_photo`, `selfie_photo`, `status`, `review_notes`, `created_at`, `updated_at`, `verified_at`) VALUES
(1, 7, 'cartney', 'dejolde', 'cart@gmail.com', '09770433849', 'Male', 'Region XIII (Caraga)', 'Agusan del Sur', 'Prosperidad', 'Libertad', '2000-01-01', 'drivers_license', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_front_u7_1765373369_76d78803.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_back_u7_1765373369_8e161a5a.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/selfie_u7_1765373369_28c69452.jpg', 'approved', NULL, '2025-12-10 06:29:29', '2025-12-10 14:31:38', '2025-12-10 14:31:38'),
(2, 1, 'Cartney', 'Dejolde', 'cart@gmail.com', '09770433849', 'Male', 'Region XIII (Caraga)', 'Agusan del Sur', 'San Francisco', 'Lapinigan', '2000-01-01', 'drivers_license', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_front_u1_1765612212_5a86c434.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_back_u1_1765612212_786f7078.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/selfie_u1_1765612212_41279515.jpg', 'approved', NULL, '2025-12-13 00:50:12', '2025-12-13 07:51:07', '2025-12-13 07:51:07'),
(3, 4, 'Kristian', 'Marty', 'kristian@gmail.com', '09123456789', 'Male', 'Region XIII (Caraga)', 'Agusan del Sur', 'Talacogon', 'San Agustin', '2000-01-01', 'drivers_license', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_front_u4_1765612426_691871e5.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_back_u4_1765612426_20ebb0bb.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/selfie_u4_1765612426_caddbf7b.jpg', 'approved', NULL, '2025-12-13 00:53:46', '2025-12-13 07:54:13', '2025-12-13 07:54:13'),
(4, 5, 'Ethan', 'Owner', 'ethan@gmail.com', '0123456789', 'Male', 'Region XIII (Caraga)', 'Agusan del Sur', 'San Francisco', 'San Francisco ADS', '2000-01-01', 'drivers_license', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_front_u5_verified.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/id_back_u5_verified.jpg', 'C:\\xampp\\htdocs\\carGOAdmin\\api/../uploads/verifications/2025/12/selfie_u5_verified.jpg', 'approved', 'Manually verified via SQL', '2025-12-22 05:29:53', NULL, '2025-12-22 05:29:53');

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
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_read` (`admin_id`,`read_status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_escrow_status` (`escrow_status`),
  ADD KEY `idx_payout_status` (`payout_status`);

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
-- Indexes for table `escrow`
--
ALTER TABLE `escrow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_escrow_processed_by` (`processed_by`);

--
-- Indexes for table `escrow_transactions`
--
ALTER TABLE `escrow_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `transaction_type` (`transaction_type`);

--
-- Indexes for table `gps_locations`
--
ALTER TABLE `gps_locations`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `payment_attempts`
--
ALTER TABLE `payment_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`);

--
-- Indexes for table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escrow_id` (`escrow_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_payouts_processed_by` (`processed_by`);

--
-- Indexes for table `payout_requests`
--
ALTER TABLE `payout_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD UNIQUE KEY `refund_id` (`refund_id`),
  ADD KEY `idx_refund_status` (`status`),
  ADD KEY `idx_user_refunds` (`user_id`,`status`),
  ADD KEY `idx_booking_refunds` (`booking_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_id` (`reported_id`),
  ADD KEY `status` (`status`),
  ADD KEY `report_type` (`report_type`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_priority` (`priority`),
  ADD KEY `idx_reports_created` (`created_at`);

--
-- Indexes for table `report_logs`
--
ALTER TABLE `report_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `facebook_id` (`facebook_id`),
  ADD UNIQUE KEY `idx_google_uid` (`google_uid`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_auth_provider` (`auth_provider`);

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
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
-- AUTO_INCREMENT for table `escrow`
--
ALTER TABLE `escrow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `escrow_transactions`
--
ALTER TABLE `escrow_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gps_locations`
--
ALTER TABLE `gps_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `motorcycles`
--
ALTER TABLE `motorcycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `payment_attempts`
--
ALTER TABLE `payment_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payout_requests`
--
ALTER TABLE `payout_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `report_logs`
--
ALTER TABLE `report_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `escrow`
--
ALTER TABLE `escrow`
  ADD CONSTRAINT `escrow_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escrow_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_escrow_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `fk_payouts_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payouts_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payouts_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payouts_ibfk_3` FOREIGN KEY (`escrow_id`) REFERENCES `escrow` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_logs`
--
ALTER TABLE `report_logs`
  ADD CONSTRAINT `report_logs_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_logs_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
