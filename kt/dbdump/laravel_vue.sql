-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2022 at 03:21 PM
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
(1, 'FSD', 1, 42, NULL, '2022-09-03 07:07:10', '2022-09-03 07:07:10'),
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
(1, 'Faizan', 100, 1, NULL, NULL, 45, NULL, '2022-09-09 01:26:58', '2022-09-14 05:44:55'),
(2, 'Faizan', 121, 1, NULL, NULL, NULL, NULL, '2022-09-09 01:27:34', '2022-09-09 01:27:34'),
(3, 'Faizan', 145, 1, NULL, NULL, NULL, NULL, '2022-09-09 01:34:54', '2022-09-09 01:34:54'),
(4, 'ahmad145', 100, 0, NULL, NULL, 45, NULL, '2022-09-09 01:35:20', '2022-09-14 05:33:21'),
(5, 'New', 100, 1, NULL, NULL, NULL, NULL, '2022-09-09 01:37:54', '2022-09-09 01:37:54'),
(6, 'SAr', 458, 1, NULL, NULL, NULL, NULL, '2022-09-09 01:39:04', '2022-09-09 01:39:04'),
(7, 'Faizaan', 456, 0, NULL, NULL, NULL, NULL, '2022-09-09 01:44:18', '2022-09-09 01:44:18'),
(8, 'Faizan', 456, 1, NULL, NULL, NULL, NULL, '2022-09-09 01:47:52', '2022-09-09 01:47:52'),
(9, 'afsdfsdvcd', 0, 1, NULL, NULL, NULL, NULL, '2022-09-09 02:11:26', '2022-09-09 02:11:26'),
(10, 'new', 12, 1, NULL, NULL, NULL, NULL, '2022-09-09 02:30:50', '2022-09-09 02:30:50'),
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
(1, 'Economy', 1, 4, 0, NULL, NULL, '2022-09-02 08:40:25', '2022-09-02 08:40:25'),
(2, 'Business', 1, 4, 0, NULL, NULL, '2022-09-02 08:40:25', '2022-09-02 08:40:25'),
(3, 'Executive', 1, 4, 0, NULL, NULL, '2022-09-02 08:40:25', '2022-09-02 08:40:25'),
(4, 'Abu Bakar', 1, 4, 45, 45, '2022-09-14 07:50:03', '2022-09-14 06:04:13', '2022-09-14 07:50:03'),
(5, 'Abu Bakar', 1, 4, 45, NULL, NULL, '2022-09-14 08:11:31', '2022-09-14 08:11:31');

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
  `commission_flat` decimal(12,2) DEFAULT NULL,
  `commission_percentage` decimal(12,2) DEFAULT NULL,
  `terminal_commission` decimal(12,2) DEFAULT NULL,
  `time_difference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surcharge` decimal(12,2) DEFAULT NULL,
  `surcharge_start_date` date DEFAULT NULL,
  `surcharge_end_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `advance_availability` decimal(12,2) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 0,
  `added_by` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fare_tables`
--

INSERT INTO `fare_tables` (`id`, `fare`, `fare_class`, `from_city_id`, `to_city_id`, `company_id`, `commission_flat`, `commission_percentage`, `terminal_commission`, `time_difference`, `surcharge`, `surcharge_start_date`, `surcharge_end_date`, `advance_availability`, `is_active`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, '4500.00', 3, 1, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:18:16', '2022-09-03 07:18:16'),
(2, '4500.00', 3, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:18:16', '2022-09-03 07:18:16'),
(3, '500.00', 3, 1, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:20:38', '2022-09-03 07:20:38'),
(4, '500.00', 3, 2, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:20:38', '2022-09-03 07:20:38'),
(5, '800.00', 3, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:20:50', '2022-09-03 07:20:50'),
(6, '800.00', 3, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:20:50', '2022-09-03 07:20:50'),
(7, '1300.00', 3, 1, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:20:59', '2022-09-03 07:20:59'),
(8, '1300.00', 3, 4, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:20:59', '2022-09-03 07:20:59'),
(9, '5500.00', 3, 5, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:21:19', '2022-09-03 07:21:19'),
(10, '5500.00', 3, 2, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:21:19', '2022-09-03 07:21:19'),
(11, '3500.00', 3, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:21:40', '2022-09-03 07:21:40'),
(12, '3500.00', 3, 3, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:21:40', '2022-09-03 07:21:40'),
(13, '7000.00', 3, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:21:50', '2022-09-03 07:21:50'),
(14, '7000.00', 3, 4, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:21:50', '2022-09-03 07:21:50'),
(15, '2000.00', 3, 2, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:22:04', '2022-09-03 07:22:04'),
(16, '2000.00', 3, 3, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:22:04', '2022-09-03 07:22:04'),
(17, '2000.00', 3, 2, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:22:22', '2022-09-03 07:22:22'),
(18, '2000.00', 3, 4, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:22:22', '2022-09-03 07:22:22'),
(19, '100.00', 1, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:32:06', '2022-09-03 07:32:06'),
(20, '100.00', 1, 1, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:32:06', '2022-09-03 07:32:06'),
(21, '200.00', 1, 2, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:32:24', '2022-09-03 07:32:24'),
(22, '200.00', 1, 1, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:32:24', '2022-09-03 07:32:24'),
(23, '400.00', 1, 2, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:32:44', '2022-09-03 07:32:44'),
(24, '400.00', 1, 5, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:32:44', '2022-09-03 07:32:44'),
(25, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:07', '2022-09-03 07:33:07'),
(26, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:07', '2022-09-03 07:33:07'),
(27, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:09', '2022-09-03 07:33:09'),
(28, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:09', '2022-09-03 07:33:09'),
(29, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:13', '2022-09-03 07:33:13'),
(30, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:13', '2022-09-03 07:33:13'),
(31, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:13', '2022-09-03 07:33:13'),
(32, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:13', '2022-09-03 07:33:13'),
(33, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:23', '2022-09-03 07:33:23'),
(34, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:23', '2022-09-03 07:33:23'),
(35, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:25', '2022-09-03 07:33:25'),
(36, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:25', '2022-09-03 07:33:25'),
(37, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(38, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(39, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(40, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(41, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(42, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(43, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(44, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:26', '2022-09-03 07:33:26'),
(45, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:27', '2022-09-03 07:33:27'),
(46, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:27', '2022-09-03 07:33:27'),
(47, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:27', '2022-09-03 07:33:27'),
(48, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:27', '2022-09-03 07:33:27'),
(49, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:28', '2022-09-03 07:33:28'),
(50, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:28', '2022-09-03 07:33:28'),
(51, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:33', '2022-09-03 07:33:33'),
(52, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:33:33', '2022-09-03 07:33:33'),
(53, '1000.00', 1, 3, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:15', '2022-09-03 07:34:15'),
(54, '1000.00', 1, 1, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:15', '2022-09-03 07:34:15'),
(55, '1000.00', 1, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:49', '2022-09-03 07:34:49'),
(56, '1000.00', 1, 3, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:49', '2022-09-03 07:34:49'),
(57, '1000.00', 1, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:52', '2022-09-03 07:34:52'),
(58, '1000.00', 1, 3, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:52', '2022-09-03 07:34:52'),
(59, '1000.00', 1, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:52', '2022-09-03 07:34:52'),
(60, '1000.00', 1, 3, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:34:52', '2022-09-03 07:34:52'),
(61, '1000.00', 1, 2, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:35:08', '2022-09-03 07:35:08'),
(62, '1000.00', 1, 3, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:35:08', '2022-09-03 07:35:08'),
(63, '2000.00', 1, 4, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:35:56', '2022-09-03 07:35:56'),
(64, '2000.00', 1, 1, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:35:56', '2022-09-03 07:35:56'),
(65, '3000.00', 1, 4, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:36:09', '2022-09-03 07:36:09'),
(66, '3000.00', 1, 1, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:36:09', '2022-09-03 07:36:09'),
(67, '2500.00', 1, 4, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:36:54', '2022-09-03 07:36:54'),
(68, '2500.00', 1, 1, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 42, NULL, '2022-09-03 07:36:54', '2022-09-03 07:36:54'),
(69, '3000.00', 1, 7, 10, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:12:41', '2022-09-08 05:12:41'),
(70, '3000.00', 1, 10, 7, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:12:41', '2022-09-08 05:12:41'),
(71, '2000.00', 1, 7, 8, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:00', '2022-09-08 05:13:00'),
(72, '2000.00', 1, 8, 7, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:00', '2022-09-08 05:13:00'),
(73, '1000.00', 1, 7, 6, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:11', '2022-09-08 05:13:11'),
(74, '1000.00', 1, 6, 7, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:11', '2022-09-08 05:13:11'),
(75, '1500.00', 1, 7, 9, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:23', '2022-09-08 05:13:23'),
(76, '1500.00', 1, 9, 7, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:23', '2022-09-08 05:13:23'),
(77, '400.00', 1, 8, 10, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:35', '2022-09-08 05:13:35'),
(78, '400.00', 1, 10, 8, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:35', '2022-09-08 05:13:35'),
(79, '500.00', 1, 6, 10, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:45', '2022-09-08 05:13:45'),
(80, '500.00', 1, 10, 6, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:45', '2022-09-08 05:13:45'),
(81, '600.00', 1, 9, 10, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:53', '2022-09-08 05:13:53'),
(82, '600.00', 1, 10, 9, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:13:53', '2022-09-08 05:13:53'),
(83, '800.00', 1, 6, 8, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:02', '2022-09-08 05:14:02'),
(84, '800.00', 1, 8, 6, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:02', '2022-09-08 05:14:02'),
(85, '600.00', 1, 9, 8, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:11', '2022-09-08 05:14:11'),
(86, '600.00', 1, 8, 9, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:11', '2022-09-08 05:14:11'),
(87, '700.00', 1, 9, 6, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:20', '2022-09-08 05:14:20'),
(88, '700.00', 1, 6, 9, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:20', '2022-09-08 05:14:20'),
(89, '8000.00', 2, 7, 10, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:38', '2022-09-08 05:14:38'),
(90, '8000.00', 2, 10, 7, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:38', '2022-09-08 05:14:38'),
(91, '1900.00', 1, 7, 6, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:54', '2022-09-08 05:14:54'),
(92, '1900.00', 1, 6, 7, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:14:54', '2022-09-08 05:14:54'),
(93, '2500.00', 3, 8, 10, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:15:13', '2022-09-08 05:15:13'),
(94, '2500.00', 3, 10, 8, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:15:13', '2022-09-08 05:15:13'),
(95, '1200.00', 3, 7, 6, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:15:26', '2022-09-08 05:15:26'),
(96, '1200.00', 3, 6, 7, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 45, NULL, '2022-09-08 05:15:26', '2022-09-08 05:15:26');

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
(4, '2022_09_13_104505_create_schedules_table', 2),
(5, '2022_09_09_113737_create_surcharges_table', 3);

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
(5, 'admin', 4, '[{\"name\":\"admin\",\"allow\":true,\"childs\":[{\"name\":\"dashboard\",\"allow\":true},{\"name\":\"terminal\",\"allow\":true},{\"name\":\"fare-table\",\"allow\":true},{\"name\":\"route\",\"allow\":true}]},{\"name\":\"hrm\",\"allow\":true,\"childs\":[{\"name\":\"employee\",\"allow\":true},{\"name\":\"salary\",\"allow\":true},{\"name\":\"loan\",\"allow\":true},{\"name\":\"leave managment\",\"allow\":true},{\"name\":\"attendance\",\"allow\":false}]},{\"name\":\"users\",\"allow\":true,\"childs\":[{\"name\":\"user\",\"allow\":true},{\"name\":\"roles\",\"allow\":true}]}]', 1, NULL, '2022-09-08 05:04:09', '2022-09-08 05:04:09');

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
(1, 'Pindi to Karachi', 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(2, 'new route', 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(3, 'RWP-KHI', 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(4, 'RWP-FSD', 4, 45, NULL, '2022-09-08 05:17:07', '2022-09-08 05:17:07');

-- --------------------------------------------------------

--
-- Table structure for table `routes_fares`
--

CREATE TABLE `routes_fares` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `route_id` bigint(20) NOT NULL,
  `fare_id` bigint(20) NOT NULL,
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

INSERT INTO `routes_fares` (`id`, `route_id`, `fare_id`, `departure_city_id`, `destination_city_id`, `company_id`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 8, 4, 1, 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(2, 1, 5, 1, 3, 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(3, 1, 12, 3, 5, 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(4, 1, 63, 4, 1, 1, 42, NULL, '2022-09-03 07:35:56', '2022-09-03 07:35:56'),
(5, 1, 65, 4, 1, 1, 42, NULL, '2022-09-03 07:36:09', '2022-09-03 07:36:09'),
(6, 1, 65, 4, 1, 1, 42, NULL, '2022-09-03 07:36:09', '2022-09-03 07:36:09'),
(7, 1, 67, 4, 1, 1, 42, NULL, '2022-09-03 07:36:54', '2022-09-03 07:36:54'),
(8, 1, 67, 4, 1, 1, 42, NULL, '2022-09-03 07:36:54', '2022-09-03 07:36:54'),
(9, 1, 67, 4, 1, 1, 42, NULL, '2022-09-03 07:36:54', '2022-09-03 07:36:54'),
(10, 1, 67, 4, 1, 1, 42, NULL, '2022-09-03 07:36:54', '2022-09-03 07:36:54'),
(11, 2, 1, 1, 5, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(12, 2, 20, 1, 5, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(13, 2, 9, 5, 2, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(14, 2, 24, 5, 2, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(15, 2, 15, 2, 3, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(16, 2, 61, 2, 3, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(17, 3, 74, 6, 7, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(18, 3, 92, 6, 7, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(19, 3, 96, 6, 7, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(20, 3, 71, 7, 8, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(21, 3, 86, 8, 9, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(22, 3, 81, 9, 10, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(23, 4, 74, 6, 7, 4, 45, NULL, '2022-09-08 05:17:07', '2022-09-08 05:17:07'),
(24, 4, 92, 6, 7, 4, 45, NULL, '2022-09-08 05:17:07', '2022-09-08 05:17:07'),
(25, 4, 96, 6, 7, 4, 45, NULL, '2022-09-08 05:17:07', '2022-09-08 05:17:07');

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

--
-- Dumping data for table `routes_terminals`
--

INSERT INTO `routes_terminals` (`id`, `route_id`, `terminal_id`, `company_id`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(2, 1, 2, 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(3, 1, 6, 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(4, 1, 4, 1, 42, NULL, '2022-09-03 07:27:20', '2022-09-03 07:27:20'),
(5, 2, 1, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(6, 2, 3, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(7, 2, 5, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(8, 2, 6, 1, 42, NULL, '2022-09-03 07:38:32', '2022-09-03 07:38:32'),
(9, 3, 10, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(10, 3, 7, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(11, 3, 9, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(12, 3, 11, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(13, 3, 8, 4, 45, NULL, '2022-09-08 05:16:32', '2022-09-08 05:16:32'),
(14, 4, 10, 4, 45, NULL, '2022-09-08 05:17:07', '2022-09-08 05:17:07'),
(15, 4, 7, 4, 45, NULL, '2022-09-08 05:17:07', '2022-09-08 05:17:07');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `departure_date` date DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `destination_date` date DEFAULT NULL,
  `destination_time` time DEFAULT NULL,
  `trip_duration` time DEFAULT NULL,
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

INSERT INTO `schedules` (`id`, `departure_date`, `departure_time`, `destination_date`, `destination_time`, `trip_duration`, `added_by`, `updated_by`, `time`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, '2022-09-13', '05:07:00', '2022-09-13', '19:56:00', '19:56:00', 45, NULL, '2022-09-13 12:07:30', NULL, '2022-09-13 07:07:30', '2022-09-13 07:07:30'),
(2, '2022-09-14', '15:04:00', '2022-09-14', '15:19:00', NULL, 45, NULL, '2022-09-14 10:04:44', NULL, '2022-09-14 05:04:44', '2022-09-14 05:04:44');

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
(1, 'Faizan', 10, 1, 45, 45, '2022-09-14 10:52:21', NULL, '2022-09-14 05:53:10', '2022-09-14 05:52:21', '2022-09-14 05:53:10'),
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
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `longitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time_difference` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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

INSERT INTO `terminals` (`id`, `name`, `contact`, `address`, `longitude`, `latitude`, `time_difference`, `order`, `is_main`, `active_sms`, `city_id`, `company_id`, `online_terminal_name`, `status`, `added_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'F-terminal 1', '304', ' ', NULL, NULL, NULL, NULL, 0, 0, 1, 1, ' ', '0', 42, NULL, '2022-09-03 07:08:40', '2022-09-03 07:08:40'),
(2, 'F-terminal 2', '304', 'GTS', NULL, NULL, '00:30', NULL, 1, 1, 1, 1, 'online F-terminal 2', '1', 42, NULL, '2022-09-03 07:10:49', '2022-09-03 07:10:49'),
(3, 'K-terminal 1', '232', ' ', NULL, NULL, NULL, NULL, 1, 0, 5, 1, ' ', '0', 42, NULL, '2022-09-03 07:12:33', '2022-09-03 07:12:33'),
(4, 'K-terminal 2', '304', ' ', NULL, NULL, NULL, NULL, 0, 0, 5, 1, ' ', '0', 42, NULL, '2022-09-03 07:13:48', '2022-09-03 07:13:48'),
(5, 'L-terminal 1', '304', ' ', NULL, NULL, NULL, NULL, 0, 0, 2, 1, ' ', '0', 42, NULL, '2022-09-03 07:14:20', '2022-09-03 07:14:20'),
(6, 'M-terminal 1', '304', ' ', NULL, NULL, NULL, NULL, 0, 0, 3, 1, ' ', '0', 42, NULL, '2022-09-03 07:14:40', '2022-09-03 07:14:40'),
(7, 'F1', '3333068686', ' ', NULL, NULL, '0', NULL, 1, 0, 7, 4, ' ', '0', 45, NULL, '2022-09-08 05:08:14', '2022-09-08 05:08:14'),
(8, 'K1', '33333', ' ', NULL, NULL, NULL, NULL, 1, 0, 10, 4, ' ', '0', 45, NULL, '2022-09-08 05:09:03', '2022-09-08 05:09:03'),
(9, 'M1', '55555', ' ', NULL, NULL, NULL, NULL, 1, 0, 8, 4, ' ', '0', 45, NULL, '2022-09-08 05:09:35', '2022-09-08 05:09:35'),
(10, 'R1', '555555', ' ', NULL, NULL, NULL, NULL, 1, 0, 6, 4, ' ', '0', 45, NULL, '2022-09-08 05:10:23', '2022-09-08 05:10:23'),
(11, 'S', '333333', ' ', NULL, NULL, NULL, NULL, 1, 0, 9, 4, ' ', '0', 45, NULL, '2022-09-08 05:11:20', '2022-09-08 05:11:20');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fare_tables`
--
ALTER TABLE `fare_tables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `routes_fares`
--
ALTER TABLE `routes_fares`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `routes_terminals`
--
ALTER TABLE `routes_terminals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
