-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2026 at 10:43 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `oop`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `scholarship_id` int(11) NOT NULL,
  `institution_name` varchar(255) DEFAULT NULL,
  `current_course` varchar(255) DEFAULT NULL,
  `previous_percentage` decimal(5,2) DEFAULT NULL,
  `bank_name` varchar(150) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'SUBMITTED',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_remarks` text DEFAULT NULL,
  `snapshot_pic` varchar(255) DEFAULT NULL,
  `snapshot_aadhaar` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_documents`
--

CREATE TABLE `application_documents` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `ai_status` enum('PENDING','PASSED','FAILED','ERROR') DEFAULT 'PENDING',
  `ai_remarks` text DEFAULT NULL,
  `ocr_raw_text` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `application_documents`
--

INSERT INTO `application_documents` (`id`, `application_id`, `document_type`, `file_path`, `document_number`, `issue_date`, `ai_status`, `ai_remarks`, `ocr_raw_text`) VALUES
(44, 20, 'CASTE_CERTIFICATE', 'ST-APP-2026-000009/CASTE.png', '445', '2026-09-18', 'FAILED', 'Cert No: Mismatched | Issue Date: Mismatched', '*AMAN_CASTE.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nCASTE CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 6565856985	\r\nREMARK: AMAR RAJ, FATHER NAME AMRIT KUMAR, BELONGS TO ST CASTE	\r\nLN 10, COL 1	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(45, 20, 'INCOME_CERTIFICATE', 'ST-APP-2026-000009/INCOME.png', '757', '2026-09-10', 'FAILED', 'Cert No: Mismatched | Issue Date: Mismatched', 'J*AMAN_INCOME.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nINCOME CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 7895625425	\r\nTOTAL FAMILY INCOME: 6000|	\r\nLN 11, COL 26	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(46, 21, 'AADHAAR', 'ST-APP-2026-000010/AADHAAR.png', NULL, NULL, 'FAILED', 'Name (Partial): Verified | Aadhaar No: Mismatched', 'I *UNTITLED - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nAADHAR CARD	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nAADHAAR UID: 3252 2562 2545	\r\nLN 5, COL 25	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(47, 21, 'CASTE_CERTIFICATE', 'ST-APP-2026-000010/CASTE.png', '445', '2026-09-09', 'FAILED', 'Cert No: Mismatched | Issue Date: Mismatched', '*AMAN_CASTE.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nCASTE CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 6565856985	\r\nREMARK: AMAR RAJ, FATHER NAME AMRIT KUMAR, BELONGS TO ST CASTE	\r\nLN 10, COL 1	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(48, 21, 'INCOME_CERTIFICATE', 'ST-APP-2026-000010/INCOME.png', '7895625425', '2026-09-18', 'FAILED', 'Cert No: Verified | Issue Date: Mismatched', 'J*AMAN_INCOME.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nINCOME CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 7895625425	\r\nTOTAL FAMILY INCOME: 6000|	\r\nLN 11, COL 26	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(49, 22, 'AADHAAR', 'ST-APP-2026-000011/AADHAAR.png', NULL, NULL, 'FAILED', 'Name (Partial): Verified | Aadhaar No: Mismatched', 'I *UNTITLED - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nAADHAR CARD	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nAADHAAR UID: 3252 2562 2545	\r\nLN 5, COL 25	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(50, 22, 'CASTE_CERTIFICATE', 'ST-APP-2026-000011/CASTE.png', '445', '2026-09-16', 'FAILED', 'Cert No: Mismatched | Issue Date: Mismatched', '*AMAN_CASTE.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nCASTE CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 6565856985	\r\nREMARK: AMAR RAJ, FATHER NAME AMRIT KUMAR, BELONGS TO ST CASTE	\r\nLN 10, COL 1	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(51, 22, 'INCOME_CERTIFICATE', 'ST-APP-2026-000011/INCOME.png', '757', '2026-09-02', 'FAILED', 'Cert No: Mismatched | Issue Date: Mismatched', 'J*AMAN_INCOME.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nINCOME CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 7895625425	\r\nTOTAL FAMILY INCOME: 6000|	\r\nLN 11, COL 26	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(52, 23, 'AADHAAR', 'ST-APP-2026-000012/AADHAAR.png', NULL, NULL, 'PASSED', 'Name (Partial): Verified | Aadhaar No: Verified', 'I *UNTITLED - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nAADHAR CARD	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nAADHAAR UID: 3252 2562 2545	\r\nLN 5, COL 25	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(53, 23, 'CASTE_CERTIFICATE', 'ST-APP-2026-000012/CASTE.png', '6565856985', '2002-01-01', 'PASSED', 'Cert No: Verified | Issue Date: Verified', '*AMAN_CASTE.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nCASTE CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 6565856985	\r\nREMARK: AMAR RAJ, FATHER NAME AMRIT KUMAR, BELONGS TO ST CASTE	\r\nLN 10, COL 1	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(54, 23, 'INCOME_CERTIFICATE', 'ST-APP-2026-000012/INCOME.png', '7895625425', '2002-01-01', 'PASSED', 'Cert No: Verified | Issue Date: Verified', 'J*AMAN_INCOME.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nINCOME CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 7895625425	\r\nTOTAL FAMILY INCOME: 6000|	\r\nLN 11, COL 26	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(55, 24, 'AADHAAR', 'ST-APP-2026-000013/AADHAAR.png', NULL, NULL, 'PASSED', 'Name (Partial): Verified | Aadhaar No: Verified', 'I *UNTITLED - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nAADHAR CARD	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nAADHAAR UID: 3252 2562 2545	\r\nLN 5, COL 25	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(56, 24, 'CASTE_CERTIFICATE', 'ST-APP-2026-000013/CASTE.png', '6565856985', '2002-01-01', 'FAILED', 'Cert No: Mismatched | Issue Date: Verified', 'I *UNTITLED - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nAADHAR CARD	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nAADHAAR UID: 3252 2562 2545	\r\nLN 5, COL 25	100%	WINDOWS (CRLF)	UTF-8	\r\n'),
(57, 24, 'INCOME_CERTIFICATE', 'ST-APP-2026-000013/INCOME.png', '7895625425', '2002-01-01', 'PASSED', 'Cert No: Verified | Issue Date: Verified', 'J*AMAN_INCOME.TXT - NOTEPAD	-	\r\nFILE EDIT FORMAT VIEW HELP	\r\nINCOME CERTIFICATE	\r\nNAME: AMAR RAJ	\r\nFATHER NAME: AMRIT KUMAR	\r\nDATE OF BIRTH: 01/01/2002	\r\nCERTIFICATE NO: 7895625425	\r\nTOTAL FAMILY INCOME: 6000|	\r\nLN 11, COL 26	100%	WINDOWS (CRLF)	UTF-8	\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `scheme_type` varchar(100) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `deadline` date NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `published_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`id`, `title`, `scheme_type`, `short_description`, `benefits`, `start_date`, `deadline`, `status`, `created_at`, `published_date`, `description`) VALUES
(6, 'Pre-Matric Scholarship for ST Students', 'Scholarship', 'Supports ST students studying in Classes 9 and 10 to reduce drop-out rates. \r\nEligible for families with an annual income below ₹2.5 Lakhs.', 'Supports ST students studying in Classes 9 and 10 to reduce drop-out rates. \r\nEligible for families with an annual income below ₹2.5 Lakhs.', '2026-09-23', '2026-12-30', 'ACTIVE', '2026-09-23 16:32:24', '2026-09-23 16:32:24', 'hgbjg'),
(7, 'Post-Matric Scholarship for ST Students', 'State Scheme', NULL, '• Complete compulsory non-refundable fees reimbursement• Maintenance allowance for hostellers and day scholars• Book bank facilities', '2026-09-23', '2026-11-26', 'ACTIVE', '2026-09-23 16:36:48', '2026-09-23 16:36:48', 'Supports ST students pursuing higher education from Class 11 up to Post-Graduation. Family income limit is ₹2.5 Lakhs per year.');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `category` varchar(10) DEFAULT 'ST',
  `annual_income` decimal(10,2) DEFAULT NULL,
  `aadhaar_masked` varchar(14) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL,
  `caste_cert_no` varchar(50) DEFAULT NULL,
  `caste_issue_date` date DEFAULT NULL,
  `income_cert_no` varchar(50) DEFAULT NULL,
  `income_issue_date` date DEFAULT NULL,
  `aadhaar_raw` varchar(12) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `class_10_board` varchar(150) DEFAULT NULL,
  `class_10_year` int(4) DEFAULT NULL,
  `class_10_marks` decimal(5,2) DEFAULT NULL,
  `class_12_board` varchar(150) DEFAULT NULL,
  `class_12_year` int(4) DEFAULT NULL,
  `class_12_marks` decimal(5,2) DEFAULT NULL,
  `current_course` varchar(255) DEFAULT NULL,
  `bank_name` varchar(150) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `phone`, `dob`, `gender`, `category`, `annual_income`, `aadhaar_masked`, `updated_at`, `profile_pic`, `caste_cert_no`, `caste_issue_date`, `income_cert_no`, `income_issue_date`, `aadhaar_raw`, `father_name`, `father_occupation`, `mother_name`, `mother_occupation`, `class_10_board`, `class_10_year`, `class_10_marks`, `class_12_board`, `class_12_year`, `class_12_marks`, `current_course`, `bank_name`, `account_number`, `ifsc_code`) VALUES
(1, 1, NULL, NULL, NULL, 'ST', NULL, NULL, '2026-09-22 16:41:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('STUDENT','VERIFICATION_OFFICER','ADMIN','SUPER_ADMIN') DEFAULT 'STUDENT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$12$HNc5Lb99S336t38VUJbvkOuozEQndBN4gVCY6SabylPbI8.pg.rMK', 'ADMIN', '2026-09-22 16:41:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `scholarship_id` (`scholarship_id`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `application_documents`
--
ALTER TABLE `application_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
