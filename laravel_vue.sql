-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2022 at 03:21 PM
-- Server version: 10.4.20-MariaDB
-- PHP Version: 7.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laravel_vue`
--

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bus_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chassis_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_of_seats` int(11) DEFAULT NULL,
  `route_permit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fare_class_id` int(11) DEFAULT NULL,
  `seat_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seat_map`)),
  `no_of_rows` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `bus_number`, `chassis_number`, `insurance_number`, `no_of_seats`, `route_permit_number`, `fare_class_id`, `seat_map`, `no_of_rows`, `company_id`, `added_by`, `updated_by`, `time`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Toyota', '21', '12', 12, '12', 1, '[[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":1},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":2}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}]]', 3, 4, 45, 45, '2022-09-22 12:23:55', NULL, '2022-09-22 07:23:55', '2022-09-28 06:44:13'),
(2, 'sfewgferg', '132234', '24234', 234, '12342', 2, '[[{\"reserved\":true,\"seatNo\":1},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":-7},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":-8},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":9},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}]]', 5, 4, 45, 45, '2022-09-22 14:20:55', NULL, '2022-09-22 09:20:55', '2022-09-22 09:21:52'),
(3, 'Lexus', '35435', '312121', 34532, '234345', 2, '[[{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":1},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":2,\"class\":2,\"type\":\"not_for_sale\"},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}]]', 2, 4, 45, 45, '2022-09-24 11:27:20', NULL, '2022-09-24 06:27:20', '2022-09-28 06:44:30'),
(4, 'Hyundai', '23', '5', 345, '234', 2, '[[{\"reserved\":true,\"seatNo\":1,\"class\":\"Business\",\"type\":\"reserved_for_female\"},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":2,\"class\":\"Exective\",\"type\":\"reserved_for_female\"}]]', 2, 4, 45, 45, '2022-09-24 11:31:07', NULL, '2022-09-24 06:31:07', '2022-09-28 06:45:10'),
(5, 'test', '567', '5675', 456, '456', 2, '[[{\"reserved\":true,\"seatNo\":1,\"class\":\"Economy\",\"type\":\"reserved_for_female\"},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":2,\"class\":\"Exective\",\"type\":\"not_for_sale\"}]]', 2, 4, 45, NULL, '2022-09-24 11:55:12', NULL, '2022-09-24 06:55:12', '2022-09-24 06:55:12');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint(20) NOT NULL,
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `company_id`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'FSD', 1, 42, '2022-09-22 07:16:06', '2022-09-03 07:07:10', '2022-09-22 07:16:06'),
(2, 'Lahore', 1, 42, NULL, '2022-09-03 07:07:34', '2022-09-03 07:07:34'),
(3, 'Multan', 1, 42, NULL, '2022-09-03 07:07:47', '2022-09-03 07:07:47'),
(4, 'Pindi', 1, 42, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(5, 'Karachi', 1, 42, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(6, 'RWP', 4, 45, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(7, 'Fsd', 4, 45, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(8, 'Multan', 4, 45, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(9, 'Sukkar', 4, 45, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(10, 'Kharachi', 4, 45, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32');

-- --------------------------------------------------------

--
-- Table structure for table `city_to_city`
--

CREATE TABLE `city_to_city` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `departure_city_id` bigint(20) NOT NULL,
  `destination_city_id` bigint(20) NOT NULL,
  `added_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `city_to_city`
--

INSERT INTO `city_to_city` (`id`, `departure_city_id`, `destination_city_id`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, '2022-09-03 07:07:10', '2022-09-03 07:07:10'),
(2, 2, 1, NULL, NULL, '2022-09-03 07:07:34', '2022-09-03 07:07:34'),
(3, 1, 2, NULL, NULL, '2022-09-03 07:07:34', '2022-09-03 07:07:34'),
(4, 2, 2, NULL, NULL, '2022-09-03 07:07:34', '2022-09-03 07:07:34'),
(5, 3, 1, NULL, NULL, '2022-09-03 07:07:47', '2022-09-03 07:07:47'),
(6, 1, 3, NULL, NULL, '2022-09-03 07:07:47', '2022-09-03 07:07:47'),
(7, 3, 2, NULL, NULL, '2022-09-03 07:07:47', '2022-09-03 07:07:47'),
(8, 2, 3, NULL, NULL, '2022-09-03 07:07:47', '2022-09-03 07:07:47'),
(9, 3, 3, NULL, NULL, '2022-09-03 07:07:47', '2022-09-03 07:07:47'),
(10, 4, 1, NULL, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(11, 1, 4, NULL, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(12, 4, 2, NULL, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(13, 2, 4, NULL, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(14, 4, 3, NULL, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(15, 3, 4, NULL, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(16, 4, 4, NULL, NULL, '2022-09-03 07:07:57', '2022-09-03 07:07:57'),
(17, 5, 1, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(18, 1, 5, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(19, 5, 2, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(20, 2, 5, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(21, 5, 3, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(22, 3, 5, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(23, 5, 4, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(24, 4, 5, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(25, 5, 5, NULL, NULL, '2022-09-03 07:08:03', '2022-09-03 07:08:03'),
(26, 6, 1, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(27, 1, 6, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(28, 6, 2, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(29, 2, 6, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(30, 6, 3, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(31, 3, 6, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(32, 6, 4, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(33, 4, 6, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(34, 6, 5, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(35, 5, 6, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(36, 6, 6, NULL, NULL, '2022-09-08 05:05:49', '2022-09-08 05:05:49'),
(37, 7, 1, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(38, 1, 7, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(39, 7, 2, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(40, 2, 7, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(41, 7, 3, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(42, 3, 7, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(43, 7, 4, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(44, 4, 7, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(45, 7, 5, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(46, 5, 7, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(47, 7, 6, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(48, 6, 7, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(49, 7, 7, NULL, NULL, '2022-09-08 05:06:15', '2022-09-08 05:06:15'),
(50, 8, 1, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(51, 1, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(52, 8, 2, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(53, 2, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(54, 8, 3, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(55, 3, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(56, 8, 4, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(57, 4, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(58, 8, 5, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(59, 5, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(60, 8, 6, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(61, 6, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(62, 8, 7, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(63, 7, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(64, 8, 8, NULL, NULL, '2022-09-08 05:06:21', '2022-09-08 05:06:21'),
(65, 9, 1, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(66, 1, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(67, 9, 2, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(68, 2, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(69, 9, 3, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(70, 3, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(71, 9, 4, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(72, 4, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(73, 9, 5, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(74, 5, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(75, 9, 6, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(76, 6, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(77, 9, 7, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(78, 7, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(79, 9, 8, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(80, 8, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(81, 9, 9, NULL, NULL, '2022-09-08 05:06:26', '2022-09-08 05:06:26'),
(82, 10, 1, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(83, 1, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(84, 10, 2, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(85, 2, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(86, 10, 3, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(87, 3, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(88, 10, 4, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(89, 4, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(90, 10, 5, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(91, 5, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(92, 10, 6, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(93, 6, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(94, 10, 7, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(95, 7, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(96, 10, 8, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(97, 8, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(98, 10, 9, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(99, 9, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32'),
(100, 10, 10, NULL, NULL, '2022-09-08 05:06:32', '2022-09-08 05:06:32');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`modules`)),
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `contact`, `logo`, `location`, `modules`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'SAR ZONE', '3040235224', NULL, 'Islamabad', '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":true,\"childs\":[{\"name\":\"employee\",\"allow\":true},{\"name\":\"salary\",\"allow\":true},{\"name\":\"loan\",\"allow\":true},{\"name\":\"leave managment\",\"allow\":true},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 3, NULL, '2022-09-03 07:05:50', '2022-09-03 07:05:50'),
(2, 'Test', '3333068686', NULL, 'lslamabad', '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":false,\"childs\":[{\"name\":\"employee\",\"allow\":false},{\"name\":\"salary\",\"allow\":false},{\"name\":\"loan\",\"allow\":false},{\"name\":\"leave managment\",\"allow\":false},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 3, NULL, '2022-09-06 04:50:55', '2022-09-06 06:26:24'),
(3, 'testimonial', '31123456789', NULL, NULL, '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":false,\"childs\":[{\"name\":\"employee\",\"allow\":false},{\"name\":\"salary\",\"allow\":false},{\"name\":\"loan\",\"allow\":false},{\"name\":\"leave managment\",\"allow\":false},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 3, NULL, '2022-09-06 06:33:52', '2022-09-06 06:33:52'),
(4, 'Fizan Travels', '3157053558', NULL, 'Jhang', '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":true,\"childs\":[{\"name\":\"employee\",\"allow\":true},{\"name\":\"salary\",\"allow\":true},{\"name\":\"loan\",\"allow\":true},{\"name\":\"leave managment\",\"allow\":true},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 3, NULL, '2022-09-08 05:04:09', '2022-09-08 05:04:09');

-- --------------------------------------------------------

--
-- Table structure for table `company_modules`
--

CREATE TABLE `company_modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `percentage` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `company_id` int(11) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `name`, `percentage`, `is_active`, `company_id`, `added_by`, `updated_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Faizan', 100, 1, 4, NULL, 45, NULL, '2022-09-09 01:26:58', '2022-09-14 05:44:55'),
(2, 'Faizan', 121, 1, 4, NULL, NULL, NULL, '2022-09-09 01:27:34', '2022-09-09 01:27:34'),
(3, 'Faizan', 145, 1, 4, NULL, NULL, NULL, '2022-09-09 01:34:54', '2022-09-09 01:34:54'),
(4, 'ahmad145', 100, 0, 4, NULL, 45, NULL, '2022-09-09 01:35:20', '2022-09-14 05:33:21'),
(5, 'New', 100, 1, 4, NULL, NULL, NULL, '2022-09-09 01:37:54', '2022-09-09 01:37:54'),
(6, 'SAr', 458, 1, 4, NULL, NULL, NULL, '2022-09-09 01:39:04', '2022-09-09 01:39:04'),
(7, 'Faizaan', 456, 0, 4, NULL, NULL, NULL, '2022-09-09 01:44:18', '2022-09-09 01:44:18'),
(8, 'Faizan', 456, 1, 4, NULL, NULL, NULL, '2022-09-09 01:47:52', '2022-09-09 01:47:52'),
(9, 'afsdfsdvcd', 0, 1, 4, NULL, NULL, NULL, '2022-09-09 02:11:26', '2022-09-09 02:11:26'),
(10, 'new', 12, 1, 4, NULL, NULL, NULL, '2022-09-09 02:30:50', '2022-09-09 02:30:50'),
(11, 'add', 15, 0, 4, 45, 45, NULL, '2022-09-14 06:39:50', '2022-09-14 06:40:04');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fare_classes`
--

CREATE TABLE `fare_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `company_id` int(11) DEFAULT NULL,
  `added_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fare_classes`
--

INSERT INTO `fare_classes` (`id`, `name`, `is_active`, `company_id`, `added_by`, `updated_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Economy', 1, 4, 0, 45, NULL, '2022-09-02 08:40:25', '2022-09-30 09:23:44'),
(2, 'Business', 1, 4, 0, NULL, NULL, '2022-09-02 08:40:25', '2022-09-02 08:40:25'),
(3, 'Executive', 1, 4, 0, NULL, NULL, '2022-09-02 08:40:25', '2022-09-02 08:40:25'),
(5, 'Abu Bakar', 1, 4, 45, NULL, NULL, '2022-09-14 06:04:13', '2022-09-14 07:50:03');

-- --------------------------------------------------------

--
-- Table structure for table `fare_tables`
--

CREATE TABLE `fare_tables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fare` decimal(12,2) NOT NULL,
  `fare_class` int(11) NOT NULL,
  `from_city_id` int(11) NOT NULL,
  `to_city_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `time_difference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `distance_in_km` int(11) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 0,
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fare_tables`
--

INSERT INTO `fare_tables` (`id`, `fare`, `fare_class`, `from_city_id`, `to_city_id`, `company_id`, `time_difference`, `distance_in_km`, `is_active`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, '150.00', 1, 7, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:49:18', '2022-09-17 07:53:16'),
(2, '150.00', 1, 10, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:49:18', '2022-09-17 07:53:16'),
(3, '120.00', 1, 7, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:49:31', '2022-09-17 07:49:31'),
(4, '120.00', 1, 8, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:49:31', '2022-09-17 07:49:31'),
(5, '130.00', 1, 7, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:49:50', '2022-09-17 07:49:50'),
(6, '130.00', 1, 6, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:49:50', '2022-09-17 07:49:50'),
(7, '110.00', 1, 7, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:07', '2022-09-17 07:50:07'),
(8, '110.00', 1, 9, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:07', '2022-09-17 07:50:07'),
(9, '100.00', 1, 10, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:20', '2022-09-17 07:50:20'),
(10, '100.00', 1, 8, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:20', '2022-09-17 07:50:20'),
(11, '160.00', 1, 10, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:35', '2022-09-17 07:50:35'),
(12, '160.00', 1, 6, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:35', '2022-09-17 07:50:35'),
(13, '170.00', 1, 10, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:55', '2022-09-17 07:50:55'),
(14, '170.00', 1, 9, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:50:55', '2022-09-17 07:50:55'),
(15, '180.00', 1, 8, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:51:47', '2022-09-17 07:51:47'),
(16, '180.00', 1, 6, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:51:47', '2022-09-17 07:51:47'),
(17, '190.00', 1, 8, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:52:05', '2022-09-17 07:52:05'),
(18, '190.00', 1, 9, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:52:05', '2022-09-17 07:52:05'),
(19, '200.00', 1, 6, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:52:19', '2022-09-17 07:52:19'),
(20, '200.00', 1, 9, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:52:19', '2022-09-17 07:52:19'),
(21, '90.00', 2, 7, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:53:53', '2022-09-17 07:53:53'),
(22, '90.00', 2, 10, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:53:53', '2022-09-17 07:53:53'),
(23, '80.00', 2, 7, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:53:59', '2022-09-17 07:53:59'),
(24, '80.00', 2, 8, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:53:59', '2022-09-17 07:53:59'),
(25, '70.00', 2, 7, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:05', '2022-09-17 07:54:05'),
(26, '70.00', 2, 6, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:05', '2022-09-17 07:54:05'),
(27, '60.00', 2, 7, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:10', '2022-09-17 07:54:10'),
(28, '60.00', 2, 9, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:10', '2022-09-17 07:54:10'),
(29, '50.00', 2, 10, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:17', '2022-09-17 07:54:17'),
(30, '50.00', 2, 8, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:17', '2022-09-17 07:54:17'),
(31, '40.00', 2, 10, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:22', '2022-09-17 07:54:22'),
(32, '40.00', 2, 6, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:22', '2022-09-17 07:54:22'),
(33, '30.00', 2, 10, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:29', '2022-09-17 07:54:29'),
(34, '30.00', 2, 9, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:29', '2022-09-17 07:54:29'),
(35, '20.00', 2, 8, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:35', '2022-09-17 07:54:35'),
(36, '20.00', 2, 6, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:35', '2022-09-17 07:54:35'),
(37, '10.00', 2, 8, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:40', '2022-09-17 07:54:40'),
(38, '10.00', 2, 9, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:40', '2022-09-17 07:54:40'),
(39, '15.00', 2, 6, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:46', '2022-09-17 07:54:46'),
(40, '15.00', 2, 9, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:46', '2022-09-17 07:54:46'),
(41, '25.00', 3, 7, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:56', '2022-09-17 07:54:56'),
(42, '25.00', 3, 10, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:54:56', '2022-09-17 07:54:56'),
(43, '35.00', 3, 7, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:02', '2022-09-17 07:55:02'),
(44, '35.00', 3, 8, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:02', '2022-09-17 07:55:02'),
(45, '45.00', 3, 7, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:10', '2022-09-17 07:55:10'),
(46, '45.00', 3, 6, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:10', '2022-09-17 07:55:10'),
(47, '55.00', 3, 7, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:17', '2022-09-17 07:55:17'),
(48, '55.00', 3, 9, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:17', '2022-09-17 07:55:17'),
(49, '65.00', 3, 10, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:22', '2022-09-17 07:55:22'),
(50, '65.00', 3, 8, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:22', '2022-09-17 07:55:22'),
(51, '75.00', 3, 10, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:28', '2022-09-17 07:55:28'),
(52, '75.00', 3, 6, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:28', '2022-09-17 07:55:28'),
(53, '85.00', 3, 10, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:35', '2022-09-17 07:55:35'),
(54, '85.00', 3, 9, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:35', '2022-09-17 07:55:35'),
(55, '95.00', 3, 8, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:40', '2022-09-17 07:55:40'),
(56, '95.00', 3, 6, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:40', '2022-09-17 07:55:40'),
(57, '125.00', 3, 8, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:54', '2022-09-17 07:55:54'),
(58, '125.00', 3, 6, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:55:54', '2022-09-17 07:55:54'),
(59, '125.00', 3, 8, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:56:07', '2022-09-17 07:56:07'),
(60, '125.00', 3, 9, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:56:07', '2022-09-17 07:56:07'),
(61, '135.00', 3, 6, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:56:15', '2022-09-17 07:56:15'),
(62, '135.00', 3, 9, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-17 07:56:15', '2022-09-17 07:56:15'),
(63, '1.00', 5, 7, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:43:57', '2022-09-20 04:43:57'),
(64, '1.00', 5, 10, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:43:57', '2022-09-20 04:43:57'),
(65, '2.00', 5, 7, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:02', '2022-09-20 04:44:02'),
(66, '2.00', 5, 8, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:02', '2022-09-20 04:44:02'),
(67, '3.00', 5, 7, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:07', '2022-09-20 04:44:07'),
(68, '3.00', 5, 6, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:07', '2022-09-20 04:44:07'),
(69, '4.00', 5, 7, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:12', '2022-09-20 04:44:12'),
(70, '4.00', 5, 9, 7, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:12', '2022-09-20 04:44:12'),
(71, '5.00', 5, 10, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:18', '2022-09-20 04:44:24'),
(72, '5.00', 5, 8, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:18', '2022-09-20 04:44:24'),
(73, '5.00', 5, 10, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:19', '2022-09-20 04:44:19'),
(74, '5.00', 5, 8, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:19', '2022-09-20 04:44:24'),
(75, '6.00', 5, 10, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:29', '2022-09-20 04:44:29'),
(76, '6.00', 5, 6, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:29', '2022-09-20 04:44:29'),
(77, '7.00', 5, 10, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:34', '2022-09-20 04:44:40'),
(78, '7.00', 5, 9, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:34', '2022-09-20 04:44:40'),
(79, '7.00', 5, 10, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:36', '2022-09-20 04:44:36'),
(80, '7.00', 5, 9, 10, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:36', '2022-09-20 04:44:40'),
(81, '8.00', 5, 8, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:46', '2022-09-20 04:44:46'),
(82, '8.00', 5, 6, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:46', '2022-09-20 04:44:46'),
(83, '9.00', 5, 8, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:52', '2022-09-20 04:44:52'),
(84, '9.00', 5, 9, 8, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:52', '2022-09-20 04:44:52'),
(85, '0.00', 5, 6, 9, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:58', '2022-09-20 04:44:58'),
(86, '0.00', 5, 9, 6, 4, NULL, NULL, 0, 45, NULL, '2022-09-20 04:44:58', '2022-09-20 04:44:58');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2022_09_09_062102_create_discounts_table', 1),
(5, '2022_09_09_113737_create_surcharges_table', 3),
(10, '2022_09_15_065956_create_buses_table', 4),
(20, '2022_09_13_104505_create_schedules_table', 5);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `added_by` int(11) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `company_id`, `permissions`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', 1, '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":true,\"childs\":[{\"name\":\"employee\",\"allow\":true},{\"name\":\"salary\",\"allow\":true},{\"name\":\"loan\",\"allow\":true},{\"name\":\"leave managment\",\"allow\":true},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 1, NULL, '2022-09-03 07:05:50', '2022-09-06 08:40:47'),
(2, 'Generic', 1, '[]', 1, NULL, '2022-09-03 07:29:56', '2022-09-03 07:29:56'),
(3, 'admin', 2, '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":false,\"childs\":[{\"name\":\"employee\",\"allow\":false},{\"name\":\"salary\",\"allow\":false},{\"name\":\"loan\",\"allow\":false},{\"name\":\"leave managment\",\"allow\":false},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 1, NULL, '2022-09-06 04:50:55', '2022-09-06 06:26:24'),
(4, 'admin', 3, '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":false,\"childs\":[{\"name\":\"employee\",\"allow\":false},{\"name\":\"salary\",\"allow\":false},{\"name\":\"loan\",\"allow\":false},{\"name\":\"leave managment\",\"allow\":false},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 1, NULL, '2022-09-06 06:33:52', '2022-09-06 06:33:52'),
(5, 'admin', 4, '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":true,\"childs\":[{\"name\":\"employee\",\"allow\":true},{\"name\":\"salary\",\"allow\":true},{\"name\":\"loan\",\"allow\":true},{\"name\":\"leave managment\",\"allow\":true},{\"name\":\"attendance\",\"allow\":true}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 1, NULL, '2022-09-08 05:04:09', '2022-10-01 06:33:01');

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint(20) NOT NULL,
  `added_by` bigint(20) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `name`, `company_id`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(15, 'dsfds', 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(16, 'FSD-KHR', 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `routes_fares`
--

CREATE TABLE `routes_fares` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `route_id` bigint(20) NOT NULL,
  `fare_id` bigint(20) NOT NULL,
  `fare_class_id` int(11) DEFAULT NULL,
  `departure_city_id` bigint(20) NOT NULL,
  `destination_city_id` bigint(20) NOT NULL,
  `company_id` bigint(20) NOT NULL,
  `added_by` bigint(20) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `routes_fares`
--

INSERT INTO `routes_fares` (`id`, `route_id`, `fare_id`, `fare_class_id`, `departure_city_id`, `destination_city_id`, `company_id`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 15, 3, NULL, 7, 8, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(2, 15, 23, NULL, 7, 8, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(3, 15, 43, NULL, 7, 8, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(4, 15, 7, NULL, 7, 9, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(5, 15, 27, NULL, 7, 9, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(6, 15, 47, NULL, 7, 9, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(7, 15, 1, NULL, 7, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(8, 15, 21, NULL, 7, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(9, 15, 63, NULL, 7, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(10, 15, 17, NULL, 8, 9, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(11, 15, 37, NULL, 8, 9, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(12, 15, 59, NULL, 8, 9, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(13, 15, 10, NULL, 8, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(14, 15, 30, NULL, 8, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(15, 15, 50, NULL, 8, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(16, 15, 14, NULL, 9, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(17, 15, 34, NULL, 9, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(18, 15, 54, NULL, 9, 10, 4, 45, NULL, '2022-09-19 05:26:22', '2022-09-19 05:26:22'),
(19, 16, 1, 1, 7, 10, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(20, 16, 21, 2, 7, 10, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(21, 16, 41, 3, 7, 10, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(22, 16, 63, 5, 7, 10, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(23, 16, 3, 1, 7, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(24, 16, 23, 2, 7, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(25, 16, 43, 3, 7, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(26, 16, 65, 5, 7, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(27, 16, 7, 1, 7, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(28, 16, 27, 2, 7, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(29, 16, 47, 3, 7, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(30, 16, 69, 5, 7, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(31, 16, 9, 1, 10, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(32, 16, 29, 2, 10, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(33, 16, 49, 3, 10, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(34, 16, 71, 5, 10, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(35, 16, 73, 5, 10, 8, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(36, 16, 13, 1, 10, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(37, 16, 33, 2, 10, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(38, 16, 53, 3, 10, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(39, 16, 77, 5, 10, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(40, 16, 79, 5, 10, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(41, 16, 17, 1, 8, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(42, 16, 37, 2, 8, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(43, 16, 59, 3, 8, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33'),
(44, 16, 83, 5, 8, 9, 4, 45, NULL, '2022-09-28 06:02:33', '2022-09-28 06:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `routes_terminals`
--

CREATE TABLE `routes_terminals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `route_id` bigint(20) NOT NULL,
  `terminal_id` bigint(20) NOT NULL,
  `company_id` bigint(20) NOT NULL,
  `added_by` bigint(20) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departure_datetime` datetime DEFAULT NULL,
  `destination_datetime` datetime DEFAULT NULL,
  `bus_class_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `seat_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seat_map`)),
  `route_city_terminal` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`route_city_terminal`)),
  `selected_bus_class_id` int(11) DEFAULT NULL,
  `no_of_rows` int(11) DEFAULT NULL,
  `surcharge_id` int(11) DEFAULT NULL,
  `discount_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `name`, `departure_datetime`, `destination_datetime`, `bus_class_id`, `route_id`, `bus_id`, `seat_map`, `route_city_terminal`, `selected_bus_class_id`, `no_of_rows`, `surcharge_id`, `discount_id`, `company_id`, `added_by`, `updated_by`, `time`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'RWP', '2022-10-03 16:47:00', '2022-10-03 20:47:00', 2, 16, 1, '[[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":1},{\"reserved\":false,\"seatNo\":0},{\"reserved\":true,\"seatNo\":2}],[{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0},{\"reserved\":false,\"seatNo\":0}]]', '[{\"city_id\":7,\"terminal_id\":6,\"allow\":false},{\"city_id\":8,\"terminal_id\":9,\"allow\":true},{\"city_id\":9,\"terminal_id\":5,\"allow\":true},{\"city_id\":10,\"terminal_id\":8,\"allow\":true},{\"city_id\":9,\"terminal_id\":11,\"allow\":true},{\"city_id\":7,\"terminal_id\":7,\"allow\":true}]', 1, 3, 2, 3, 4, 45, NULL, '2022-10-03 11:50:39', NULL, '2022-10-03 06:50:39', '2022-10-03 06:50:39');

-- --------------------------------------------------------

--
-- Table structure for table `surcharges`
--

CREATE TABLE `surcharges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `percentage` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `added_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp(),
  `company_id` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surcharges`
--

INSERT INTO `surcharges` (`id`, `name`, `percentage`, `is_active`, `added_by`, `updated_by`, `time`, `company_id`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Faizan', 10, 1, 45, 45, '2022-09-14 10:52:21', 4, '2022-09-14 05:53:10', '2022-09-14 05:52:21', '2022-09-14 05:53:10'),
(2, 'add', 26, 0, 45, 45, '2022-09-14 11:41:01', 4, NULL, '2022-09-14 06:41:01', '2022-09-14 06:41:10'),
(3, 'add', 25, 1, 45, NULL, '2022-09-14 11:41:22', 4, NULL, '2022-09-14 06:41:22', '2022-09-14 06:41:22');

-- --------------------------------------------------------

--
-- Table structure for table `terminals`
--

CREATE TABLE `terminals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time_difference` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_main` tinyint(4) NOT NULL,
  `active_sms` tinyint(4) DEFAULT NULL,
  `city_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `online_terminal_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terminals`
--

INSERT INTO `terminals` (`id`, `name`, `contact`, `address`, `longitude`, `latitude`, `time_difference`, `is_main`, `active_sms`, `city_id`, `company_id`, `online_terminal_name`, `status`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'F-terminal 1', '304', NULL, NULL, NULL, NULL, 0, 0, 1, 1, ' ', '0', 42, NULL, '2022-09-03 07:08:40', '2022-09-03 07:08:40'),
(2, 'F-terminal 2', '304', 'GTS', NULL, NULL, '00:30', 1, 1, 1, 1, 'online F-terminal 2', '1', 42, NULL, '2022-09-03 07:10:49', '2022-09-03 07:10:49'),
(3, 'K-terminal 1', '232', NULL, NULL, NULL, NULL, 1, 0, 5, 1, ' ', '0', 42, NULL, '2022-09-03 07:12:33', '2022-09-03 07:12:33'),
(4, 'K-terminal 2', '304', NULL, NULL, NULL, NULL, 0, 0, 5, 1, ' ', '0', 42, NULL, '2022-09-03 07:13:48', '2022-09-03 07:13:48'),
(5, 'L-terminal 1', '304', ' gts1', NULL, NULL, NULL, 0, 0, 9, 4, ' ', '0', 42, NULL, '2022-09-03 07:14:20', '2022-09-03 07:14:20'),
(6, 'M-terminal 1', '304', ' gts2', NULL, NULL, NULL, 0, 0, 7, 4, ' ', '0', 42, NULL, '2022-09-03 07:14:40', '2022-09-03 07:14:40'),
(7, 'F1', '3333068686', ' gts3', NULL, NULL, '0', 1, 0, 7, 4, ' ', '0', 45, NULL, '2022-09-08 05:08:14', '2022-09-08 05:08:14'),
(8, 'K1', '33333', ' gts4', NULL, NULL, NULL, 1, 0, 10, 4, ' ', '0', 45, NULL, '2022-09-08 05:09:03', '2022-09-08 05:09:03'),
(9, 'M1', '55555', ' gts5', NULL, NULL, NULL, 1, 0, 8, 4, ' ', '0', 45, NULL, '2022-09-08 05:09:35', '2022-09-08 05:09:35'),
(10, 'R1', '555555', ' gts6', NULL, NULL, NULL, 1, 0, 6, 4, ' ', '0', 45, NULL, '2022-09-08 05:10:23', '2022-09-08 05:10:23'),
(11, 'S', '333333', ' gts7', NULL, NULL, NULL, 1, 0, 9, 4, ' ', '0', 45, NULL, '2022-09-08 05:11:20', '2022-09-08 05:11:20');

-- --------------------------------------------------------

--
-- Table structure for table `terminal_allowed_seats_advance`
--

CREATE TABLE `terminal_allowed_seats_advance` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `seats` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terminal_available_seats`
--

CREATE TABLE `terminal_available_seats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `seats` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terminal_commissions`
--

CREATE TABLE `terminal_commissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `per_seat` tinyint(4) NOT NULL DEFAULT 0,
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_super_admin` tinyint(4) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `contact`, `company_id`, `role_id`, `is_super_admin`, `email_verified_at`, `password`, `remember_token`, `deleted_at`, `created_at`, `updated_at`) VALUES
(3, 'Abubakar Pervaiz', 'abubakar@gmail.com', '', 0, 1, 1, NULL, '$2y$10$CQaUr0zN8T3DfIRXeqb/0Orhuk0AO7CU.zIhApRLiP6NjlJLjxp5m', NULL, NULL, '2022-08-02 21:44:49', '2022-08-02 21:44:49'),
(42, 'SAR ZONE', 'sarzone@gmail.com', '3040235224', 1, 1, 0, NULL, '$2y$10$7iHEmRqPQBs07M7tYw4Af.Saanq8pR/tJMG7tlz7P7gPOr4WnW7gy', NULL, NULL, '2022-09-03 07:05:50', '2022-09-06 08:40:47'),
(43, 'Test', 'test12@gmail.com', '3333068686', 2, 3, 0, NULL, '$2y$10$MPHinLBTBlpfHiJFEch/YOla/zB9vOGfmlzKcFnGzVE16RBVQxWbq', NULL, NULL, '2022-09-06 04:50:55', '2022-09-06 06:26:24'),
(44, 'tetimonial', 'testimonial@gmail.com', '31123456789', 3, 4, 0, NULL, '$2y$10$YjS6Y/3rPaGJEFJSxUZrAuJF5C5Ysugm5KIXtH1nNJYfLB3FGvoFi', NULL, NULL, '2022-09-06 06:33:52', '2022-09-06 06:33:52'),
(45, 'FT', 'ft@ft.com', '3157053558', 4, 5, 0, NULL, '$2y$10$EHFbcv86rAI2h7coli1I6uj/0w1xe34Kv/yuqg2Sx1tQSxcKq5.DO', NULL, NULL, '2022-09-08 05:04:09', '2022-09-08 05:04:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `city_to_city`
--
ALTER TABLE `city_to_city`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_modules`
--
ALTER TABLE `company_modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `fare_classes`
--
ALTER TABLE `fare_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fare_tables`
--
ALTER TABLE `fare_tables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `routes_fares`
--
ALTER TABLE `routes_fares`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `routes_terminals`
--
ALTER TABLE `routes_terminals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `surcharges`
--
ALTER TABLE `surcharges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `terminals`
--
ALTER TABLE `terminals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `terminal_allowed_seats_advance`
--
ALTER TABLE `terminal_allowed_seats_advance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `terminal_available_seats`
--
ALTER TABLE `terminal_available_seats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `terminal_commissions`
--
ALTER TABLE `terminal_commissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `city_to_city`
--
ALTER TABLE `city_to_city`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `company_modules`
--
ALTER TABLE `company_modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fare_classes`
--
ALTER TABLE `fare_classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fare_tables`
--
ALTER TABLE `fare_tables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `routes_fares`
--
ALTER TABLE `routes_fares`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `routes_terminals`
--
ALTER TABLE `routes_terminals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `surcharges`
--
ALTER TABLE `surcharges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `terminals`
--
ALTER TABLE `terminals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `terminal_allowed_seats_advance`
--
ALTER TABLE `terminal_allowed_seats_advance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `terminal_available_seats`
--
ALTER TABLE `terminal_available_seats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `terminal_commissions`
--
ALTER TABLE `terminal_commissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
