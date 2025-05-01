-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 08:09 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `plantilla`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','create','update','delete','upload','download') NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'upload', 'Imported 10 records from testscb.csv', '::1', '2025-05-01 04:13:51'),
(2, 1, 'login', 'User logged in', '::1', '2025-05-01 05:58:16'),
(3, 1, 'logout', 'User admin logged out', '::1', '2025-05-01 06:05:00'),
(4, 1, 'login', 'User logged in', '::1', '2025-05-01 06:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('pending','reviewed','shortlisted','rejected') NOT NULL DEFAULT 'pending',
  `resume_path` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`id`, `code`, `name`, `description`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, '00', 'All Divisions', 'All organizational divisions', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(2, 'OA', 'Office of the Administrator', 'Head office division', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(3, 'AD', 'Administrative Division', 'General administration', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(4, 'HR', 'Human Resources Management', 'HR operations', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(5, 'RM', 'Records Management Section', 'Document management', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(6, 'PP', 'Procurement Section', 'Purchasing services', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(7, 'FP', 'Financial Planning', 'Budget management', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(8, 'AC', 'Accounting Section', 'Financial records', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(9, 'BP', 'Budget Planning', 'Fiscal planning', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(10, 'MS', 'Management Services', 'Operational support', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(11, 'ET', 'Engineering Services', 'Technical support', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(12, 'ME', 'Meteorological Equipment', 'Weather instruments', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(13, 'MG', 'Meteorological Guides', 'Forecasting standards', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(14, 'EI', 'Engineering Infrastructure', 'Facilities maintenance', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(15, 'WF', 'Weather Forecasting', 'Daily forecasts', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(16, 'MD', 'Meteorological Data', 'Weather information', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(17, 'TS', 'Techniques Section', 'Analysis methods', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(18, 'AM', 'Aeronautical Meteorology', 'Aviation weather', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(19, 'MM', 'Marine Meteorology', 'Maritime forecasts', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(20, 'HY', 'Hydrometeorology', 'Water systems', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(21, 'HD', 'Hydrological Data', 'Water monitoring', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(22, 'FF', 'Flood Forecasting', 'Flood warnings', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(23, 'HT', 'Hydrometeorological Telemetry', 'Remote sensing', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(24, 'CL', 'Climatology', 'Climate patterns', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(25, 'CM', 'Climate Monitoring', 'Climate tracking', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(26, 'FW', 'Farm Weather', 'Agricultural forecasts', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(27, 'IA', 'Impact Assessment', 'Weather effects', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(28, 'CD', 'Climate Data', 'Climate records', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(29, 'RD', 'Research Development', 'Scientific studies', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(30, 'AS', 'Astronomy Space', 'Celestial events', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(31, 'CR', 'Climate Research', 'Climate studies', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(32, 'HM', 'Hydrometeorology Research', 'Water systems research', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(33, 'NM', 'Numerical Modeling', 'Weather simulations', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(34, 'TP', 'Training Public Info', 'Education outreach', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(35, 'NL', 'Northern Luzon', 'Regional services', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(36, 'AN', 'Agno Flood System', 'Agno river basin', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(37, 'PA', 'Pampanga Flood System', 'Pampanga river basin', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(38, 'SL', 'Southern Luzon', 'Regional services', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(39, 'BI', 'Bicol Flood System', 'Bicol region', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(40, 'VS', 'Visayas', 'Regional services', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(41, 'NMI', 'Northern Mindanao', 'Regional services', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(42, 'SMI', 'Southern Mindanao', 'Regional services', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53'),
(43, 'FS', 'Field Stations', 'Regional field offices', 'active', NULL, NULL, '2025-05-01 02:27:53', '2025-05-01 02:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('csv','xlsx','pdf','image') NOT NULL,
  `file_size` int(11) NOT NULL,
  `status` enum('pending','processed','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `file_uploads`
--

INSERT INTO `file_uploads` (`id`, `user_id`, `file_name`, `file_path`, `file_type`, `file_size`, `status`, `created_at`) VALUES
(1, 1, 'recordsccv.csv', 'upload_6812ed6cce11d1.28894583.csv', 'csv', 615, 'failed', '2025-05-01 03:41:32'),
(2, 1, 'recordsccv.csv', 'upload_6812edf771c0d9.45956972.csv', 'csv', 615, 'failed', '2025-05-01 03:43:51'),
(3, 1, 'recordsccv.csv', 'upload_6812edffaa4cc7.66023940.csv', 'csv', 615, 'failed', '2025-05-01 03:43:59'),
(4, 1, 'recordsccv.csv', 'upload_6812ee103cc4f2.50555243.csv', 'csv', 615, 'failed', '2025-05-01 03:44:16'),
(5, 1, 'recordsccvutf8.csv', 'upload_6812eed0c18ad0.66513728.csv', 'csv', 618, 'failed', '2025-05-01 03:47:28'),
(6, 1, 'recordsccvutf8.csv', 'upload_6812ef3a5a0841.01447542.csv', 'csv', 618, 'failed', '2025-05-01 03:49:14'),
(7, 1, 'recordsccvutf8.csv', 'upload_6812ef49c19f87.13536517.csv', 'csv', 618, 'failed', '2025-05-01 03:49:29'),
(8, 1, 'recordsccvutf8.csv', 'upload_6812efc69875d1.50622142.csv', 'csv', 618, 'failed', '2025-05-01 03:51:34'),
(9, 1, 'records.csv', 'upload_6812efd77d5478.92055016.csv', 'csv', 618, 'failed', '2025-05-01 03:51:51'),
(10, 1, 'recordsccvutf8.csv', 'upload_6812f0076dd207.98829562.csv', 'csv', 618, 'failed', '2025-05-01 03:52:39'),
(11, 1, 'testscb.csv', 'upload_6812f0d9a86cf7.06510935.csv', 'csv', 618, 'failed', '2025-05-01 03:56:09'),
(12, 1, 'testscb.csv', 'upload_6812f18fe2c884.55770814.csv', 'csv', 618, 'failed', '2025-05-01 03:59:11'),
(13, 1, 'testscb.csv', 'upload_6812f1c34769c6.22791262.csv', 'csv', 618, 'failed', '2025-05-01 04:00:03'),
(14, 1, 'testscb.csv', 'upload_6812f23f8cea83.61342912.csv', 'csv', 618, 'failed', '2025-05-01 04:02:07'),
(15, 1, 'testscb.csv', 'upload_6812f314003649.49995338.csv', 'csv', 618, 'failed', '2025-05-01 04:05:40'),
(16, 1, 'testscb.csv', 'upload_6812f3489c8508.01886762.csv', 'csv', 618, 'failed', '2025-05-01 04:06:32'),
(17, 1, 'testscb.csv', 'upload_6812f3580823a0.02781231.csv', 'csv', 618, 'failed', '2025-05-01 04:06:48'),
(18, 1, 'testscb.csv', 'upload_6812f3b4219216.82676686.csv', 'csv', 618, 'failed', '2025-05-01 04:08:20'),
(19, 1, 'testscb.csv', 'upload_6812f3e3584596.11971144.csv', 'csv', 618, 'failed', '2025-05-01 04:09:07'),
(20, 1, 'testscb.csv', 'upload_6812f420a66650.78863354.csv', 'csv', 618, 'failed', '2025-05-01 04:10:08'),
(21, 1, 'testscb.csv', 'upload_6812f4837d0ed3.88930434.csv', 'csv', 618, 'pending', '2025-05-01 04:11:47'),
(22, 1, 'testscb.csv', 'upload_6812f4bf7b3ee1.05560703.csv', 'csv', 618, 'pending', '2025-05-01 04:12:47'),
(23, 1, 'testscb.csv', 'upload_6812f4ff351258.51332350.csv', 'csv', 618, 'processed', '2025-05-01 04:13:51'),
(24, 1, 'testscb.csv', 'upload_6812f6e5392b87.40195457.csv', 'csv', 618, 'failed', '2025-05-01 04:21:57'),
(25, 1, 'recordsccvutf8.csv', 'upload_6812f6f2299581.49176879.csv', 'csv', 62, 'failed', '2025-05-01 04:22:10'),
(26, 1, 'testscb.csv', 'upload_6812f719db6852.79699498.csv', 'csv', 618, 'failed', '2025-05-01 04:22:49'),
(27, 1, 'testscb.csv', 'upload_6812fa0d94e846.84020333.csv', 'csv', 618, 'failed', '2025-05-01 04:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `organizational_codes`
--

CREATE TABLE `organizational_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `salary_grade` varchar(10) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `division_id`, `employee_id`, `name`, `position`, `salary_grade`, `status`, `data`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'EMP-001', 'Juan Dela Cruz', 'Administrative Officer', 'SG-11', 'active', '{\"upload_id\":23,\"row_number\":2,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(2, 3, 'EMP-002', 'Maria Santos', 'HR Specialist', 'SG-12', 'active', '{\"upload_id\":23,\"row_number\":3,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(3, 8, 'EMP-003', 'Robert Lim', 'Accountant', 'SG-15', 'active', '{\"upload_id\":23,\"row_number\":4,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(4, 15, 'EMP-004', 'Sofia Reyes', 'Weather Forecaster', 'SG-14', 'active', '{\"upload_id\":23,\"row_number\":5,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(5, 30, 'EMP-005', 'James Wilson', 'Research Specialist', 'SG-13', 'active', '{\"upload_id\":23,\"row_number\":6,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(6, 43, 'EMP-006', 'Anna Torres', 'Field Technician', 'SG-10', 'active', '{\"upload_id\":23,\"row_number\":7,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(7, 10, 'EMP-007', 'Michael Tan', 'IT Specialist', 'SG-12', 'active', '{\"upload_id\":23,\"row_number\":8,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(8, 6, 'EMP-008', 'Lisa Gomez', 'Procurement Officer', 'SG-11', 'active', '{\"upload_id\":23,\"row_number\":9,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(9, 11, 'EMP-009', 'David Chen', 'Engineer', 'SG-14', 'active', '{\"upload_id\":23,\"row_number\":10,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51'),
(10, 19, 'EMP-010', 'Sarah Lee', 'Marine Meteorologist', 'SG-13', 'active', '{\"upload_id\":23,\"row_number\":11,\"imported_at\":\"2025-05-01 06:13:51\"}', 1, NULL, '2025-05-01 04:13:51', '2025-05-01 04:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Plantilla Management System', 'Name of the system', '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(2, 'site_description', 'A comprehensive plantilla management system', 'Description of the system', '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(3, 'upload_max_size', '10485760', 'Maximum file upload size in bytes (10MB)', '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(4, 'allowed_file_types', 'csv,xlsx,pdf,jpg,jpeg,png', 'Comma-separated list of allowed file types', '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(5, 'session_timeout', '3600', 'Session timeout in seconds (1 hour)', '2025-05-01 02:27:54', '2025-05-01 02:27:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','manager','user') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `photo` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role`, `status`, `photo`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System', 'Administrator', 'admin', 'active', NULL, '2025-05-01 14:05:08', '2025-05-01 02:27:53', '2025-05-01 06:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`, `created_at`, `updated_at`) VALUES
(1, 1, 'dashboard', 1, 1, 1, 1, '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(2, 1, 'organizational_codes', 1, 1, 1, 1, '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(3, 1, 'applicants', 1, 1, 1, 1, '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(4, 1, 'users', 1, 1, 1, 1, '2025-05-01 02:27:54', '2025-05-01 02:27:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `organizational_codes`
--
ALTER TABLE `organizational_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_org_code` (`code`);

--
-- Indexes for table `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_division` (`division_id`),
  ADD KEY `idx_employee_status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_module` (`user_id`,`module`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `organizational_codes`
--
ALTER TABLE `organizational_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `organizational_codes` (`id`),
  ADD CONSTRAINT `applicants_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `organizational_codes` (`id`),
  ADD CONSTRAINT `applicants_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `applicants_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `divisions`
--
ALTER TABLE `divisions`
  ADD CONSTRAINT `divisions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `divisions_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `organizational_codes`
--
ALTER TABLE `organizational_codes`
  ADD CONSTRAINT `organizational_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `organizational_codes_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `records`
--
ALTER TABLE `records`
  ADD CONSTRAINT `records_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  ADD CONSTRAINT `records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `records_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
