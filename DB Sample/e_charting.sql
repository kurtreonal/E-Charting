-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 08, 2026 at 05:10 AM
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
-- Database: `e_charting`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_next_doses` (IN `p_med_id` INT, IN `p_how_many` INT)   proc_label: BEGIN
  DECLARE v_start DATETIME;
  DECLARE v_now DATETIME;
  DECLARE v_spacing BIGINT;
  DECLARE v_next DATETIME;
  DECLARE i INT DEFAULT 0;

  -- 1. Get Start Time (Prioritize start_datetime, fallback to date_prescribed)
  SELECT COALESCE(start_datetime, date_prescribed) INTO v_start
  FROM medication
  WHERE medication_id = p_med_id
  LIMIT 1;

  -- If med not found, exit
  IF v_start IS NULL THEN
    SELECT 'Medication not found or no date' AS message;
    LEAVE proc_label;
  END IF;

  -- 2. Compute spacing in seconds
  SELECT CASE
           WHEN interval_minutes IS NOT NULL AND interval_minutes > 0 THEN (interval_minutes * 60)
           WHEN times_per_day IS NOT NULL AND times_per_day > 0 THEN FLOOR((24 * 60 * 60) / times_per_day)
           ELSE NULL
         END INTO v_spacing
  FROM medication
  WHERE medication_id = p_med_id
  LIMIT 1;

  -- If spacing invalid, exit
  IF v_spacing IS NULL THEN
    SELECT 'No schedule defined (times_per_day or interval_minutes)' AS message;
    LEAVE proc_label;
  END IF;

  SET v_now = NOW();

  -- 3. Calculate First Next Dose
  IF v_start > v_now THEN
    -- If start date is in the future, that is the first dose
    SET v_next = v_start;
  ELSE
    -- If started in the past, calculate the next future slot
    -- Logic: Start + (Interval * Ceil(SecondsPassed / Interval))
    SET v_next = DATE_ADD(
      v_start,
      INTERVAL CEIL(GREATEST(0, TIMESTAMPDIFF(SECOND, v_start, v_now)) / v_spacing) * v_spacing
      SECOND
    );
  END IF;

  -- 4. Generate Rows
  DROP TEMPORARY TABLE IF EXISTS tmp_doses;
  CREATE TEMPORARY TABLE tmp_doses (dose_dt DATETIME);

  WHILE i < p_how_many DO
    INSERT INTO tmp_doses (dose_dt) VALUES (v_next);
    SET v_next = DATE_ADD(v_next, INTERVAL v_spacing SECOND);
    SET i = i + 1;
  END WHILE;

  SELECT * FROM tmp_doses;

  DROP TEMPORARY TABLE IF EXISTS tmp_doses;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `action_type` varchar(255) NOT NULL,
  `action_description` text NOT NULL,
  `affected_table` varchar(255) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_data`
--

CREATE TABLE `admission_data` (
  `admission_data_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `admission_date` date NOT NULL,
  `admission_time` time NOT NULL,
  `mode_of_arrival` enum('wheelchair','stretcher') DEFAULT NULL,
  `instructed` enum('wardset','medication','hospital-rules','special') DEFAULT NULL,
  `glasses_or_contactlens` enum('yes','no') DEFAULT NULL,
  `dentures` enum('yes','no') DEFAULT NULL,
  `ambulatory_or_prosthesis` enum('yes','no') DEFAULT NULL,
  `smoker` enum('yes','no') DEFAULT NULL,
  `drinker` enum('yes','no') DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointment_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_status` varchar(255) NOT NULL,
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `history_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `history_date` datetime NOT NULL,
  `allergies` varchar(255) NOT NULL,
  `duration_of_symptoms` varchar(255) NOT NULL,
  `regular_medication` varchar(255) NOT NULL,
  `dietary_habits` varchar(255) NOT NULL,
  `elimination_habits` varchar(255) NOT NULL,
  `sleep_patterns` varchar(255) NOT NULL,
  `personal_care` enum('yes','no') DEFAULT NULL,
  `ambulation` enum('yes','no') DEFAULT NULL,
  `communication_problem` enum('yes','no') DEFAULT NULL,
  `isolation` enum('yes','no') DEFAULT NULL,
  `skin_care` enum('yes','no') DEFAULT NULL,
  `wound_care` enum('yes','no') DEFAULT NULL,
  `others` varchar(255) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `lab_result_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_name` varchar(255) NOT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medication`
--

CREATE TABLE `medication` (
  `medication_id` int(11) NOT NULL,
  `nurse_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `medication_name` varchar(255) DEFAULT NULL,
  `medication_type` varchar(255) DEFAULT NULL,
  `dose` varchar(255) DEFAULT NULL,
  `times_per_day` int(11) DEFAULT NULL,
  `interval_minutes` int(11) DEFAULT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `date_prescribed` datetime DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `medication_next_dose`
-- (See below for the actual view)
--
CREATE TABLE `medication_next_dose` (
`medication_id` int(11)
,`patient_id` int(11)
,`nurse_id` int(11)
,`schedule_start` datetime
,`now_time` datetime
,`spacing_seconds` bigint(13)
,`next_dose` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `nurse_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `medication_id` int(11) DEFAULT NULL,
  `notification_type_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `message_status` enum('pending','sent','failed','confirmed','declined') DEFAULT 'pending',
  `is_confirmed` tinyint(1) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `scheduled_date` datetime DEFAULT NULL,
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_type`
--

CREATE TABLE `notification_type` (
  `notification_type_id` int(11) NOT NULL,
  `notification_type` varchar(100) NOT NULL,
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse`
--

CREATE TABLE `nurse` (
  `nurse_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurse`
--

INSERT INTO `nurse` (`nurse_id`, `user_id`, `created_date`) VALUES
(1, 1, '2026-01-08 12:09:17'),
(2, 2, '2026-01-08 12:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(255) NOT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `patient_status` varchar(50) NOT NULL,
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `physical_assessment`
--

CREATE TABLE `physical_assessment` (
  `physical_assessment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `nurse_id` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `bp_lft` int(11) NOT NULL,
  `pulse` int(11) NOT NULL,
  `status` enum('weak','irregular') DEFAULT NULL,
  `orientation` enum('time','person','event-disoriented','confused') DEFAULT NULL,
  `skin_color` enum('normal','pale','cyanotic','jaundiced','dusky','modified') DEFAULT NULL,
  `skin_turgor` enum('loose','tight','edema') DEFAULT NULL,
  `skin_temp` enum('warm','dry','clammy','cool','diaphoretic','moist') DEFAULT NULL,
  `mucous_membrane` enum('moist','dry','cracked','sore') DEFAULT NULL,
  `peripheral_sounds` enum('audible','sound') DEFAULT NULL,
  `neck_vein_distention` enum('absent','flat') DEFAULT NULL,
  `respiratory_status` enum('labored','unlabored','sob','accessory') DEFAULT NULL,
  `respiratory_sounds` enum('rules','bronchi-wheezing','clear') DEFAULT NULL,
  `cough` enum('none','productive','non-productive') DEFAULT NULL,
  `sputum` enum('large','thin','thick','mucoid','tenacious') DEFAULT NULL,
  `temp_ranges` int(11) NOT NULL,
  `temperature` enum('oral','axilla','rectal') DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `created_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_type_id`, `email`, `password`, `first_name`, `last_name`, `middle_name`, `created_date`) VALUES
(1, 1, 'nurse@gmail.com', '123', 'Rin', 'Tohsaka', NULL, '2026-01-08 12:09:17'),
(2, 1, 'nurse2@gmail.com', '123', 'Shizuku', 'Osaka', NULL, '2026-01-08 12:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_type`
--

CREATE TABLE `user_type` (
  `user_type_id` int(11) NOT NULL,
  `user_type_desc` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type`
--

INSERT INTO `user_type` (`user_type_id`, `user_type_desc`) VALUES
(1, 'nurse');

-- --------------------------------------------------------

--
-- Structure for view `medication_next_dose`
--
DROP TABLE IF EXISTS `medication_next_dose`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `medication_next_dose`  AS SELECT `m`.`medication_id` AS `medication_id`, `m`.`patient_id` AS `patient_id`, `m`.`nurse_id` AS `nurse_id`, coalesce(`m`.`start_datetime`,`m`.`date_prescribed`) AS `schedule_start`, current_timestamp() AS `now_time`, CASE WHEN `m`.`interval_minutes` is not null THEN `m`.`interval_minutes`* 60 WHEN `m`.`times_per_day` is not null AND `m`.`times_per_day` > 0 THEN floor(1440 * 60 / `m`.`times_per_day`) ELSE NULL END AS `spacing_seconds`, CASE END FROM `medication` AS `m` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `nurse_id` (`nurse_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `admission_data`
--
ALTER TABLE `admission_data`
  ADD PRIMARY KEY (`admission_data_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `nurse_id` (`nurse_id`);

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `nurse_id` (`nurse_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `nurse_id` (`nurse_id`);

--
-- Indexes for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD PRIMARY KEY (`lab_result_id`),
  ADD KEY `nurse_id` (`nurse_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_upload_date` (`upload_date`);

--
-- Indexes for table `medication`
--
ALTER TABLE `medication`
  ADD PRIMARY KEY (`medication_id`),
  ADD KEY `nurse_id` (`nurse_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `nurse_id` (`nurse_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `medication_id` (`medication_id`),
  ADD KEY `notification_type_id` (`notification_type_id`);

--
-- Indexes for table `notification_type`
--
ALTER TABLE `notification_type`
  ADD PRIMARY KEY (`notification_type_id`),
  ADD UNIQUE KEY `notification_type` (`notification_type`);

--
-- Indexes for table `nurse`
--
ALTER TABLE `nurse`
  ADD PRIMARY KEY (`nurse_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `physical_assessment`
--
ALTER TABLE `physical_assessment`
  ADD PRIMARY KEY (`physical_assessment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `nurse_id` (`nurse_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `user_type`
--
ALTER TABLE `user_type`
  ADD PRIMARY KEY (`user_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_data`
--
ALTER TABLE `admission_data`
  MODIFY `admission_data_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `lab_result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medication`
--
ALTER TABLE `medication`
  MODIFY `medication_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_type`
--
ALTER TABLE `notification_type`
  MODIFY `notification_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurse`
--
ALTER TABLE `nurse`
  MODIFY `nurse_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `physical_assessment`
--
ALTER TABLE `physical_assessment`
  MODIFY `physical_assessment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_type`
--
ALTER TABLE `user_type`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_log_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE SET NULL;

--
-- Constraints for table `admission_data`
--
ALTER TABLE `admission_data`
  ADD CONSTRAINT `admission_data_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `admission_data_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`);

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`),
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `history_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`);

--
-- Constraints for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD CONSTRAINT `lab_results_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_results_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`) ON DELETE CASCADE;

--
-- Constraints for table `medication`
--
ALTER TABLE `medication`
  ADD CONSTRAINT `medication_ibfk_1` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`),
  ADD CONSTRAINT `medication_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`),
  ADD CONSTRAINT `notification_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`appointment_id`),
  ADD CONSTRAINT `notification_ibfk_4` FOREIGN KEY (`medication_id`) REFERENCES `medication` (`medication_id`),
  ADD CONSTRAINT `notification_ibfk_5` FOREIGN KEY (`notification_type_id`) REFERENCES `notification_type` (`notification_type_id`);

--
-- Constraints for table `nurse`
--
ALTER TABLE `nurse`
  ADD CONSTRAINT `nurse_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `physical_assessment`
--
ALTER TABLE `physical_assessment`
  ADD CONSTRAINT `physical_assessment_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `physical_assessment_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `nurse` (`nurse_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_type` (`user_type_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
