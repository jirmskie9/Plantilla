-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2025 at 06:52 AM
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
(4, 1, 'login', 'User logged in', '::1', '2025-05-01 06:05:08'),
(5, 1, 'logout', 'User admin logged out', '::1', '2025-05-02 03:45:55'),
(6, 1, 'login', 'User logged in', '::1', '2025-05-02 03:46:02'),
(7, 1, 'logout', 'User admin logged out', '::1', '2025-05-02 04:01:58'),
(8, 1, 'login', 'User logged in', '::1', '2025-05-02 04:02:10'),
(9, 1, 'create', 'Created new user: jirmy', '::1', '2025-05-02 04:06:23'),
(10, 1, 'logout', 'User admin logged out', '::1', '2025-05-02 04:21:53'),
(11, 1, 'login', 'User logged in', '::1', '2025-05-02 04:22:01'),
(12, 1, 'logout', 'User admin logged out', '::1', '2025-05-02 04:22:05'),
(13, 1, 'login', 'User logged in', '::1', '2025-05-02 04:22:11');

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
(1, 1, 'samplefilecsv.csv', '../uploads/upload_68143acf3692d.csv', 'csv', 10839, 'pending', '2025-05-02 03:23:59'),
(2, 1, 'samplefilecsv.csv', '../uploads/upload_68143b2ab2951.csv', 'csv', 10839, 'pending', '2025-05-02 03:25:30'),
(3, 1, 'samplefilecsv.csv', '../uploads/upload_68143b6acd9ca.csv', 'csv', 10839, 'pending', '2025-05-02 03:26:34'),
(4, 1, 'samplefilecsv.csv', '../uploads/upload_68143b729e9ee.csv', 'csv', 10839, 'pending', '2025-05-02 03:26:42'),
(5, 1, 'samplefilecsv.csv', '../uploads/upload_68143b97398d2.csv', 'csv', 10839, 'pending', '2025-05-02 03:27:19'),
(6, 1, 'samplefilecsv.csv', '../uploads/upload_68143bd94aade.csv', 'csv', 10839, 'pending', '2025-05-02 03:28:25'),
(7, 1, 'samplefilecsv.csv', '../uploads/upload_68143bdcd5adb.csv', 'csv', 10839, 'pending', '2025-05-02 03:28:28'),
(8, 1, 'samplefilecsv.csv', '../uploads/upload_68143c478d7ad.csv', 'csv', 10839, 'pending', '2025-05-02 03:30:15'),
(9, 1, 'samplefilecsv.csv', '../uploads/upload_68143c7be9073.csv', 'csv', 10839, 'pending', '2025-05-02 03:31:07'),
(10, 1, 'samplefilecsv.csv', '../uploads/upload_68143cb548dc1.csv', 'csv', 10839, 'pending', '2025-05-02 03:32:05'),
(11, 1, 'samplefilecsv.csv', '../uploads/upload_68143ceae16f8.csv', 'csv', 10839, 'pending', '2025-05-02 03:32:58'),
(12, 1, 'samplefilecsv.csv', '../uploads/upload_68143d50c405f.csv', 'csv', 10839, 'pending', '2025-05-02 03:34:40'),
(13, 1, 'samplefilecsv.csv', '../uploads/upload_68143da26487c.csv', 'csv', 10839, 'pending', '2025-05-02 03:36:02'),
(14, 1, 'samplefilecsv.csv', '../uploads/upload_68143e2acdca3.csv', 'csv', 10839, 'pending', '2025-05-02 03:38:18'),
(15, 1, 'samplefilecsv.csv', '../uploads/upload_68143e36eae7f.csv', 'csv', 10839, 'pending', '2025-05-02 03:38:30'),
(16, 1, 'samplefilecsv.csv', '../uploads/upload_68143e6f7dd7b.csv', 'csv', 10839, 'pending', '2025-05-02 03:39:27'),
(17, 1, 'samplefilecsv.csv', '../uploads/upload_68144e0959433.csv', 'csv', 10839, 'pending', '2025-05-02 04:46:01'),
(18, 1, 'samplefilecsv.csv', '../uploads/upload_68144e35ebbd9.csv', 'csv', 10839, 'pending', '2025-05-02 04:46:45'),
(19, 1, 'samplefilecsv.csv', '../uploads/upload_68144ec884f31.csv', 'csv', 10839, 'pending', '2025-05-02 04:49:12'),
(20, 1, 'samplefilecsv.csv', '../uploads/upload_68144f0b8c557.csv', 'csv', 10839, 'pending', '2025-05-02 04:50:19'),
(21, 1, 'samplefilecsv.csv', '../uploads/upload_68144f1c40c54.csv', 'csv', 10839, 'pending', '2025-05-02 04:50:36'),
(22, 1, 'samplefilecsv.csv', '../uploads/upload_68144f6e442fb.csv', 'csv', 10839, 'pending', '2025-05-02 04:51:58'),
(23, 1, 'samplefilecsv.csv', '../uploads/upload_68144f8d0d46b.csv', 'csv', 10839, 'pending', '2025-05-02 04:52:29');

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
  `division_id` int(11) NOT NULL DEFAULT 0,
  `plantilla_no` varchar(50) DEFAULT NULL,
  `plantilla_division` varchar(255) DEFAULT NULL,
  `plantilla_section` varchar(255) DEFAULT NULL,
  `equivalent_division` varchar(255) DEFAULT NULL,
  `plantilla_division_definition` text DEFAULT NULL,
  `plantilla_section_definition` text DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `ext_name` varchar(50) DEFAULT NULL,
  `mi` varchar(10) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `position_title` varchar(255) DEFAULT NULL,
  `item_number` varchar(50) DEFAULT NULL,
  `tech_code` varchar(50) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `appointment_status` varchar(100) DEFAULT NULL,
  `sg` varchar(10) DEFAULT NULL,
  `step` varchar(10) DEFAULT NULL,
  `monthly_salary` decimal(15,2) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `date_orig_appt` date DEFAULT NULL,
  `date_govt_srvc` date DEFAULT NULL,
  `date_last_promotion` date DEFAULT NULL,
  `date_last_increment` date DEFAULT NULL,
  `date_longevity` date DEFAULT NULL,
  `date_vacated` date DEFAULT NULL,
  `vacated_due_to` varchar(255) DEFAULT NULL,
  `vacated_by` varchar(255) DEFAULT NULL,
  `id_no` varchar(50) DEFAULT NULL,
  `remarks` varchar(100) NOT NULL DEFAULT 'Not Yet for Filling up',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System', 'Administrator', 'admin', 'active', 'uploads/profile_photos/profile_681442783b2c9_sample .png', '2025-05-02 12:22:11', '2025-05-01 02:27:53', '2025-05-02 04:22:11'),
(2, 'jirmy', '$2y$10$obFQ1wSJsXsYx/kOwa9U..1.Bs/.XzbyuivuSYxmjnu2AM3r7GOX2', 'jirmskie9@gmail.com', 'Jirmy', 'Nacario', 'user', 'active', NULL, NULL, '2025-05-02 04:06:23', '2025-05-02 04:06:23');

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
(4, 1, 'users', 1, 1, 1, 1, '2025-05-01 02:27:54', '2025-05-01 02:27:54'),
(5, 2, 'dashboard', 1, 0, 0, 0, '2025-05-02 04:06:23', '2025-05-02 04:06:23'),
(6, 2, 'organizational_codes', 1, 0, 0, 0, '2025-05-02 04:06:23', '2025-05-02 04:06:23'),
(7, 2, 'applicants', 1, 0, 0, 0, '2025-05-02 04:06:23', '2025-05-02 04:06:23');

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
  ADD KEY `plantilla_no` (`plantilla_no`),
  ADD KEY `last_name` (`last_name`),
  ADD KEY `first_name` (`first_name`),
  ADD KEY `position_title` (`position_title`),
  ADD KEY `sg` (`sg`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `organizational_codes`
--
ALTER TABLE `organizational_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
