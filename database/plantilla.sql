-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 10:51 AM
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
(1, 1, 'create', 'Created new record with Plantilla No: 1-39', '::1', '2025-05-05 08:25:58'),
(2, 1, 'create', 'Created new record with Plantilla No: 1-39', '::1', '2025-05-05 08:45:56');

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
(1, 1, 'samplefilecsv.csv', '../uploads/upload_681875549e159.csv', 'csv', 10839, 'processed', '2025-05-05 08:22:44'),
(2, 1, 'samplefilecsv.csv', '../uploads/upload_6818761518ac0.csv', 'csv', 10839, 'processed', '2025-05-05 08:25:57'),
(3, 1, 'samplefilecsv.csv', '../uploads/upload_68187ac39d76e.csv', 'csv', 10839, 'processed', '2025-05-05 08:45:55');

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
  `date_of_birth` varchar(100) DEFAULT NULL,
  `date_orig_appt` varchar(100) DEFAULT NULL,
  `date_govt_srvc` varchar(100) DEFAULT NULL,
  `date_last_promotion` varchar(100) DEFAULT NULL,
  `date_last_increment` varchar(100) DEFAULT NULL,
  `date_longevity` varchar(100) DEFAULT NULL,
  `date_vacated` varchar(100) DEFAULT NULL,
  `vacated_due_to` varchar(255) DEFAULT NULL,
  `vacated_by` varchar(255) DEFAULT NULL,
  `id_no` varchar(50) DEFAULT NULL,
  `status` enum('Not Yet For Filing','On-Hold','On Process') NOT NULL DEFAULT 'Not Yet For Filing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `division_id`, `plantilla_no`, `plantilla_division`, `plantilla_section`, `equivalent_division`, `plantilla_division_definition`, `plantilla_section_definition`, `fullname`, `last_name`, `first_name`, `middle_name`, `ext_name`, `mi`, `sex`, `position_title`, `item_number`, `tech_code`, `level`, `appointment_status`, `sg`, `step`, `monthly_salary`, `date_of_birth`, `date_orig_appt`, `date_govt_srvc`, `date_last_promotion`, `date_last_increment`, `date_longevity`, `date_vacated`, `vacated_due_to`, `vacated_by`, `id_no`, `status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 0, '1', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', 'SERVANDO, Nathaniel T.', 'SERVANDO', 'Nathaniel', 'Tabujara', '', 'T.', 'Male', 'Administrator III', 'PAGASAB-AD3-1-2020', 'Administrative', '3', 'Permanent', '30', '1', '196.00', '10/08/65', '01/31/19', '01/31/19', '12/04/23', '02/03/23', '01/31/19', '', '', '', '1687', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(2, 0, '2', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', '', '', '', '', '', '', '', 'Director III', 'PAGASAB-DIR3-1-1998', 'Administrative', '3', '', '27', '', '0.00', '', '', '', '', '', '', '12/04/23', 'PROMOTION', 'SERVANDO, Nathaniel T.', '', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(3, 0, '3', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', '', '', '', '', '', '', '', 'Director III', 'PAGASAB-DIR3-2-1998', 'Administrative', '3', '', '27', '', '0.00', '', '', '', '', '', '', '05/09/24', 'COMPULSORY RETIREMENT', 'PAJUELAS, Bonifacio G.', '', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(4, 0, '4', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', 'VILLAFUERTE, Marcelino II Q.', 'VILLAFUERTE', 'Marcelino', 'Quilates', 'II', 'Q.', 'Male', 'Director III', 'PAGASAB-DIR3-3-1998', 'Administrative', '3', 'Temporary', '27', '1', '136.00', '10/20/81', '10/28/10', '10/28/10', '04/01/24', '-', '10/28/10', '', '', '', '1551', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(5, 0, '5', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', 'SIERRA, Evangielyn L.', 'SIERRA', 'Evangielyn', 'Lunas', '', 'L.', 'Female', 'Administrative Assistant III', 'PAGASAB-ADAS3-5-2004', 'Administrative', '1', 'Permanent', '9', '1', '22.00', '04/25/96', '01/21/21', '01/21/21', '09/08/24', '-', '01/21/21', '', '', '', '1740', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(6, 0, '6', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', 'MAGUMCIA, Symon A.', 'MAGUMCIA', 'Symon', 'Alcantara', '', 'A.', 'Male', 'Administrative Assistant I', 'PAGASAB-ADAS1-6-2004', 'Administrative', '1', 'Permanent', '7', '1', '19.00', '07/24/92', '12/01/21', '12/01/21', '-', '-', '12/01/21', '', '', '', '1794', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(7, 0, '7', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', '', '', '', '', '', '', '', 'Administrative Assistant I', 'PAGASAB-ADAS1-7-2004', 'Administrative', '1', '', '7', '', '0.00', '', '', '', '', '', '', '09/08/24', 'PROMOTION', 'SIERRA, Evangielyn L.', '', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(8, 0, '8', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', 'LONTOC, Arwin Matthew R.', 'LONTOC', 'Arwin Matthew', 'Rivera', '', 'R.', 'Male', 'Administrative Aide VI', 'PAGASAB-ADA6-14-2004', 'Administrative', '1', 'Permanent', '6', '1', '18.00', '03/22/94', '10/29/20', '10/29/20', '12/04/23', '-', '10/29/20', '', '', '', '1719', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(9, 0, '9', 'OA', 'OA', 'AO', 'Office of the Administrator', 'Office of the Administrator', '', '', '', '', '', '', '', 'Administrative Aide VI', 'PAGASAB-ADA6-18-2004', 'Administrative', '1', '', '6', '', '0.00', '', '', '', '', '', '', '02/16/24', 'RESIGNATION', 'GENSON, Crislyn P.', '', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(10, 0, '10', 'AD', 'OCAD', 'AD', 'Administrative Division', 'Office of the Chief, AD', 'ARROYO, Arceli S.', 'ARROYO', 'Arceli', 'Sadural', '', 'S.', 'Female', 'Chief Administrative Officer', 'PAGASAB-CADOF-9-2004', 'Administrative', '2', 'Permanent', '24', '1', '94.00', '08/13/62', '08/28/89', '08/28/89', '08/01/22', '11/22/07', '08/28/89', '', '', '', '62', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(11, 0, '11', 'AD', 'OCAD', 'AD', 'Administrative Division', 'Office of the Chief, AD', 'AGSAOAY, Eric Christopher Amado J.', 'AGSAOAY', 'Eric Christopher Amado', 'Juguilon', '', 'J.', 'Male', 'Attorney III', 'PAGASAB-ATY3-1-2010', 'Administrative', '2', 'Permanent', '21', '1', '67.00', '09/13/68', '02/08/21', '02/08/21', '-', '-', '02/08/21', '', '', '', '1750', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(12, 0, '12', 'AD', 'OCAD', 'AD', 'Administrative Division', 'Office of the Chief, AD', 'MARATAS, Marmel A.', 'MARATAS', 'Marmel', 'Aleria', '', 'A.', 'Female', 'Administrative Aide IV', 'PAGASAB-ADA4-12-2004', 'Administrative', '1', 'Permanent', '4', '1', '16.00', '09/15/92', '07/13/23', '07/13/23', '-', '-', '07/13/23', '', '', '', '1873', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(13, 0, '13', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'GONZALES, Adelaida P.', 'GONZALES', 'Adelaida', 'Pilande', '', 'P.', 'Female', 'Supervising Administrative Officer', 'PAGASAB-SADOF-6-2004', 'Administrative', '2', 'Permanent', '22', '1', '74.00', '12/09/77', '04/04/03', '04/04/03', '12/12/22', '04/04/09', '04/04/03', '', '', '', '1444', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(14, 0, '14', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'SANTOS-ZERRUDO, Christine R.', 'SANTOS-ZERRUDO', 'Christine', 'Rabulan', '', 'R.', 'Female', 'Administrative Officer V', 'PAGASAB-ADOF5-3-2004', 'Administrative', '2', 'Permanent', '18', '1', '49.00', '07/02/89', '12/22/10', '12/22/10', '05/15/24', '-', '12/22/10', '', '', '', '30', 'Not Yet For Filing', '2025-05-05 08:45:55', '2025-05-05 08:45:55', 1),
(15, 0, '15', 'AD', 'HRMDS', 'AD', 'Administrative Divisions', 'Human Resource Management and Development Section', 'ITORALBA, Noli Francis B.', 'ITORALBA', 'Noli Francis', 'Bongga', '', 'B.', 'Male', 'Administrative Officer V', 'PAGASAB-ADOF5-15-2004', 'Administrative', '2', 'Permanent', '18', '1', '49.00', '01/29/69', '04/23/03', '04/23/03', '12/12/22', '04/23/09', '04/23/03', '', '', '', '1475', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:46:09', 1),
(16, 0, '16', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', '', '', '', '', '', '', '', 'Administrative Officer V', 'PAGASAB-ADOF5-17-2004', 'Administrative', '2', '', '18', '', '0.00', '', '', '', '', '', '', '12/12/22', 'PROMOTION', 'GONZALES, Adelaida P.', '', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(17, 0, '17', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'BAUSA, Jan Ivy L.', 'BAUSA', 'Jan Ivy', 'Lunas', '', 'L.', 'Female', 'Administrative Officer III', 'PAGASAB-ADOF3-13-2004', 'Administrative', '2', 'Permanent', '14', '1', '35.00', '07/17/84', '03/04/10', '03/04/10', '10/10/14', '-', '03/04/10', '', '', '', '1241', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(18, 0, '18', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'ROSALES-SORIANO, Kalayaan V.', 'ROSALES-SORIANO', 'Kalayaan', 'Vergara', '', 'V.', 'Female', 'Administrative Officer II', 'PAGASAB-ADOF2-6-2004', 'Administrative', '2', 'Permanent', '11', '1', '28.00', '02/10/93', '07/27/15', '07/27/15', '12/18/23', '-', '07/27/15', '', '', '', '348', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(19, 0, '19', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', '', '', '', '', '', '', '', 'Administrative Assistant II', 'PAGASAB-ADAS2-4-2004', 'Administrative', '1', '', '8', '', '0.00', '', '', '', '', '', '', '09/25/24', 'PROMOTION', 'LAGRIMAS, Alleli Marie U.', '', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(20, 0, '20', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'BABALA, Jenny B.', 'BABALA', 'Jenny', 'Boral', '', 'B.', 'Female', 'Administrative Assistant II', 'PAGASAB-ADAS2-5-2004', 'Administrative', '1', 'Permanent', '8', '1', '20.00', '11/29/84', '11/04/13', '11/04/13', '10/23/20', '-', '11/04/13', '', '', '', '274', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(21, 0, '21', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'SISON, Mary Ann A.', 'SISON', 'Mary Ann', 'Alcantara', '', 'A.', 'Female', 'Administrative Assistant II', 'PAGASAB-ADAS2-6-2004', 'Administrative', '1', 'Permanent', '8', '1', '20.00', '09/07/85', '02/27/18', '02/27/18', '12/12/22', '-', '02/27/18', '', '', '', '1639', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(22, 0, '22', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'ROMBAON-FORTU, Jenelyn P.', 'ROMBAON-FORTU', 'Jenelyn', 'Poryalloste', '', 'P.', 'Female', 'Administrative Aide VI', 'PAGASAB-ADA6-7-2004', 'Administrative', '1', 'Permanent', '6', '1', '18.00', '08/04/89', '07/08/19', '07/08/19', '05/15/24', '-', '07/08/19', '', '', '', '1688', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(23, 0, '23', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'FELICITAS, Karizza Joy M.', 'FELICITAS', 'Karizza Joy', 'Mataro', '', 'M.', 'Female', 'Administrative Aide IV', 'PAGASAB-ADA4-7-2004', 'Administrative', '1', 'Permanent', '4', '1', '16.00', '02/08/93', '12/18/23', '12/18/23', '-', '-', '12/18/23', '', '', '', '1897', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(24, 0, '24', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'BACANI, Marthie R.', 'BACANI', 'Marthie', 'Ramos', '', 'R.', 'Female', 'Administrative Aide IV', 'PAGASAB-ADA4-9-2004', 'Administrative', '1', 'Permanent', '4', '1', '16.00', '12/14/92', '12/18/23', '12/18/23', '-', '-', '12/18/23', '', '', '', '1895', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(25, 0, '25', 'AD', 'HRMDS', 'AD', 'Administrative Division', 'Human Resource Management and Development Section', 'DE LUNA, Heisei Ruth Angelina F.', 'DE LUNA', 'Heisei Ruth Angelina', 'Farrales', '', 'F.', 'Female', 'Administrative Aide IV', 'PAGASAB-ADA4-11-2004', 'Administrative', '1', 'Permanent', '4', '1', '16.00', '08/02/89', '12/12/22', '12/12/22', '-', '-', '12/12/22', '', '', '', '1847', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(26, 0, '26', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'NOLASCO, Ma. Annalyn S.', 'NOLASCO', 'Ma. Annalyn', 'Santos', '', 'S.', 'Female', 'Supervising Administrative Officer', 'PAGASAB-SADOF-7-2004', 'Administrative', '2', 'Permanent', '22', '1', '74.00', '05/24/73', '02/01/96', '02/01/96', '12/12/22', '11/16/07', '02/01/96', '', '', '', '1255', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(27, 0, '27', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', '', '', '', '', '', '', '', 'Administrative Officer V', 'PAGASAB-ADOF5-6-2004', 'Administrative', '2', '', '18', '', '0.00', '', '', '', '', '', '', '12/21/24', 'COMPULSORY RETIREMENT', 'GONZALES, Lynne T.', '', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(28, 0, '28', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'ALBACITE, Rosalie A.', 'ALBACITE', 'Rosalie', 'Aguilar', '', 'A.', 'Female', 'Administrative Officer V', 'PAGASAB-ADOF5-7-2004', 'Administrative', '2', 'Permanent', '18', '1', '49.00', '07/03/74', '07/14/03', '07/14/03', '10/12/17', '07/14/09', '07/14/03', '', '', '', '1235', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(29, 0, '29', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'GAINTANO, Julie Faith I.', 'GAINTANO', 'Julie Faith', 'Isip', '', 'I.', 'Female', 'Administrative Officer III', 'PAGASAB-ADOF3-5-2008', 'Administrative', '2', 'Permanent', '14', '1', '35.00', '07/01/94', '07/31/15', '07/31/15', '09/25/24', '-', '07/31/15', '', '', '', '352', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(30, 0, '30', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'TAMBO, Mark Ervin G.', 'TAMBO', 'Mark Ervin', 'Garganta', '', 'G.', 'Male', 'Administrative Officer I', 'PAGASAB-ADOF1-8-2004', 'Administrative', '2', 'Permanent', '10', '1', '24.00', '09/28/91', '09/17/14', '09/17/14', '03/26/19', '-', '09/17/14', '', '', '', '290', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(31, 0, '31', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'TAN, Rea V.', 'TAN', 'Rea', 'Viernes', '', 'V.', 'Female', 'Administrative Officer I', 'PAGASAB-ADOF1-9-2004', 'Administrative', '2', 'Permanent', '10', '1', '24.00', '10/28/82', '08/03/17', '08/03/17', '07/13/23', '-', '08/03/17', '', '', '', '1617', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(32, 0, '32', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'BAJAR, Jocel Asela B.', 'BAJAR', 'Jocel Asela', 'Banguis', '', 'B.', 'Female', 'Administrative Assistant II', 'PAGASAB-ADAS2-8-2004', 'Administrative', '1', 'Permanent', '8', '1', '20.00', '07/26/68', '09/03/12', '09/03/12', '01/20/15', '-', '09/03/12', '02/02/15', 'SWAPPING OF ITEM', 'SAAVEDRA, Rhoda A.', '169', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(33, 0, '33', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'ZAMORA, Christine Juliet B.', 'ZAMORA', 'Christine Juliet', 'Belmonte', '', 'B.', 'Female', 'Administrative Aide IV', 'PAGASAB-ADA4-6-2004', 'Administrative', '1', 'Permanent', '4', '1', '16.00', '07/24/79', '12/09/11', '12/09/11', '06/09/23', '-', '12/09/11', '', '', '', '98', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(34, 0, '34', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', '', '', '', '', '', '', '', 'Administrative Aide IV', 'PAGASAB-ADA4-8-2004', 'Administrative', '1', '', '4', '', '0.00', '', '', '', '', '', '', '05/15/24', 'PROMOTION', 'ROMBAON-FORTU, Jenelyn P.', '', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(35, 0, '35', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'DATUL, Baby Jean C.', 'DATUL', 'Baby Jean', 'Colobong', '', 'C.', 'Female', 'Administrative Aide IV', 'PAGASAB-ADA4-18-2004', 'Administrative', '1', 'Permanent', '4', '1', '16.00', '05/07/89', '02/27/18', '02/27/18', '12/12/22', '-', '02/27/18', '', '', '', '1640', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(36, 0, '36', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'RONDON, Archie I.', 'RONDON', 'Archie', 'Intod', '', 'I.', 'Male', 'Administrative Aide II', 'PAGASAB-ADA2-19-2004', 'Administrative', '1', 'Permanent', '2', '1', '14.00', '10/24/89', '12/22/23', '12/22/23', '-', '-', '12/22/23', '', '', '', '1896', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(37, 0, '37', 'AD', 'RMS', 'AD', 'Administrative Division', 'Records Management Section', 'ALABADO, Yvonne G.', 'ALABADO', 'Yvonne', 'Gamboa', '', 'G.', 'Female', 'Administrative Aide II', 'PAGASAB-ADA2-20-2004', 'Administrative', '1', 'Permanent', '2', '1', '14.00', '08/17/79', '12/04/23', '12/04/23', '-', '-', '12/04/23', '', '', '', '1890', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(38, 0, '38', 'AD', 'PPGSS', 'AD', 'Administrative Division', 'Procurement, Property and General Services Section', 'RIVERA, Joel C.', 'RIVERA', 'Joel', 'Cabanela', '', 'C.', 'Male', 'Supervising Administrative Officer', 'PAGASAB-SADOF-8-2004', 'Administrative', '2', 'Permanent', '22', '1', '74.00', '02/13/76', '07/13/09', '07/23/09', '04/10/17', '-', '07/13/09', '', '', '', '488', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1),
(39, 0, '39', 'AD', 'PPGSS', 'AD', 'Administrative Division', 'Procurement, Property and General Services Section', '', '', '', '', '', '', '', 'Administrative Officer V', 'PAGASAB-ADOF5-2-2004', 'Administrative', '2', '', '18', '', '0.00', '', '', '', '', '', '', '08/27/24', 'COMPULSORY RETIREMENT', 'DELA CRUZ, Liceria A.', '', 'Not Yet For Filing', '2025-05-05 08:45:56', '2025-05-05 08:45:56', 1);

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
(1, 'admin', '$2y$10$LL3ggOMo8yBN2sIzyMnNluNGQcAO8fs.ol2AbqZhn.XZIMIHJnulK', 'admin@example.com', 'System', 'Administrator', 'admin', 'active', 'uploads/profile_photos/profile_681442783b2c9_sample .png', '2025-05-05 15:59:30', '2025-05-01 02:27:53', '2025-05-05 07:59:30'),
(3, 'kent', '$2y$10$EUBq1J8/4S2B9VqH1DEQz.8ftD0EHpoyyTesMI41Vwi8Gnu3HX.cq', 'kentargie@gmail.com', 'kent', 'argie', 'user', 'active', 'uploads/profile_photos/profile_6815ba37ebfb2_logo.jpg', '2025-05-03 15:04:01', '2025-05-03 05:11:25', '2025-05-03 07:04:01'),
(6, 'jomar', '$2y$10$lSQ8yaDUVAEJv/Tb5BInX.Yas7wzUwGDGbxCw0pvSgLdqQ3O3lazi', 'jomarpandamon@gmail.com', 'Jomar', 'Pandamon', 'user', 'active', NULL, NULL, '2025-05-03 13:00:47', '2025-05-03 13:00:47');

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
(8, 3, 'dashboard', 1, 0, 0, 0, '2025-05-03 05:11:25', '2025-05-03 05:11:25'),
(9, 3, 'organizational_codes', 1, 0, 0, 0, '2025-05-03 05:11:25', '2025-05-03 05:11:25'),
(10, 3, 'applicants', 1, 0, 0, 0, '2025-05-03 05:11:25', '2025-05-03 05:11:25'),
(17, 6, 'dashboard', 1, 0, 0, 0, '2025-05-03 13:00:47', '2025-05-03 13:00:47'),
(18, 6, 'organizational_codes', 1, 0, 0, 0, '2025-05-03 13:00:47', '2025-05-03 13:00:47'),
(19, 6, 'applicants', 1, 0, 0, 0, '2025-05-03 13:00:47', '2025-05-03 13:00:47');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `organizational_codes`
--
ALTER TABLE `organizational_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
