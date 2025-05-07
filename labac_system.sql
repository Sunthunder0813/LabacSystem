-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2025 at 04:35 PM
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
-- Database: `labac_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `class_advisory`
--

CREATE TABLE `class_advisory` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `section_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `section_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `lrn` varchar(20) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `sex` enum('M','F','Other') NOT NULL,
  `birth_date` date NOT NULL,
  `age` tinyint(4) NOT NULL,
  `mother_tongue` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `house` varchar(100) DEFAULT NULL,
  `barangay` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `father` varchar(100) DEFAULT NULL,
  `mother` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relation` varchar(50) DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `registered_by` varchar(20) DEFAULT NULL,
  `date_time_registered` datetime DEFAULT NULL,
  `status` enum('enrolled','unenrolled') DEFAULT 'enrolled',
  `grade_level` varchar(50) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_history`
--

CREATE TABLE `student_history` (
  `id` int(11) NOT NULL,
  `lrn` varchar(20) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `sex` enum('M','F','Other') NOT NULL,
  `birth_date` date NOT NULL,
  `age` tinyint(4) NOT NULL,
  `mother_tongue` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `house` varchar(100) DEFAULT NULL,
  `barangay` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `father` varchar(100) DEFAULT NULL,
  `mother` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relation` varchar(50) DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `registered_by` varchar(20) DEFAULT NULL,
  `date_time_registered` datetime DEFAULT NULL,
  `status` enum('enrolled','unenrolled','graduated') DEFAULT 'enrolled',
  `archived_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `joining_date` date NOT NULL,
  `qualifications` text NOT NULL,
  `experience` int(11) NOT NULL,
  `previous_schools` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','rejected','approved') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `teachers`
--
DELIMITER $$
CREATE TRIGGER `generate_teacher_id` BEFORE INSERT ON `teachers` FOR EACH ROW BEGIN
    DECLARE current_year VARCHAR(2);
    SET current_year = DATE_FORMAT(NOW(), '%y');
    SET NEW.employee_id = CONCAT(current_year, '-TC-', LPAD(FLOOR(RAND() * 1000000), 6, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `unique_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `unique_id`, `username`, `email`, `password`, `user_type_id`) VALUES
(2, '25-TC-576461', 'Joseph Santander', 'santanderjoseph131@example.com', '123', 2),
(3, '25-AD-940531', 'Santander', 'santanderjoseph13@gmail.com', '123', 3),
(14, '25-TC-503076', 'Karen Mae Enriquez', 'kaeenriquez24@gmail.com', '$2y$10$vUiGune7UmHHl4DB7guj9O0pGLr95a4Ig5Fg17bnhyPoJYgm4kE0S', 2);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `generate_unique_id` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    DECLARE current_year VARCHAR(2);
    SET current_year = DATE_FORMAT(NOW(), '%y');

    IF (SELECT type_name FROM user_types WHERE id = NEW.user_type_id) = 'faculty' THEN
        SET NEW.unique_id = CONCAT(current_year, '-TC-', LPAD(FLOOR(RAND() * 1000000), 6, '0'));
    ELSEIF (SELECT type_name FROM user_types WHERE id = NEW.user_type_id) = 'admin' THEN
        SET NEW.unique_id = CONCAT(current_year, '-AD-', LPAD(FLOOR(RAND() * 1000000), 6, '0'));
    ELSEIF (SELECT type_name FROM user_types WHERE id = NEW.user_type_id) = 'student' THEN
        SET NEW.unique_id = NEW.username; -- Assuming username is the LRN for students
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`id`, `type_name`) VALUES
(3, 'admin'),
(2, 'faculty'),
(1, 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class_advisory`
--
ALTER TABLE `class_advisory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_class_advisory_employee` (`employee_id`),
  ADD KEY `fk_class_advisory_section` (`section_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grade_section` (`grade_level`,`section_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_history`
--
ALTER TABLE `student_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lrn` (`lrn`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_id` (`unique_id`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `class_advisory`
--
ALTER TABLE `class_advisory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_history`
--
ALTER TABLE `student_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class_advisory`
--
ALTER TABLE `class_advisory`
  ADD CONSTRAINT `fk_class_advisory_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`unique_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_advisory_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
