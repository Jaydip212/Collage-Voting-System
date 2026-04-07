-- ============================================================
-- COLLEGE VOTING SYSTEM - Complete Database Schema
-- Import this file in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS `college_voting_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `college_voting_system`;

-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(200) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `profile_photo` VARCHAR(255) DEFAULT 'default.png',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: departments
-- ============================================================
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: hods
-- ============================================================
CREATE TABLE IF NOT EXISTS `hods` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(200) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `teacher_id` VARCHAR(50) NOT NULL UNIQUE,
  `department_id` INT UNSIGNED NOT NULL,
  `designation` VARCHAR(100) DEFAULT 'HOD',
  `mobile` VARCHAR(15) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT 'default.png',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: students
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `roll_number` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(200) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(15) DEFAULT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `year` TINYINT UNSIGNED DEFAULT 1,
  `division` VARCHAR(10) DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT 'default.png',
  `is_approved` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `email_verified` TINYINT(1) DEFAULT 0,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: teachers
-- ============================================================
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `teacher_id` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(200) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(15) DEFAULT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `designation` VARCHAR(100) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT 'default.png',
  `is_approved` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `email_verified` TINYINT(1) DEFAULT 0,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: elections
-- ============================================================
CREATE TABLE IF NOT EXISTS `elections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `election_type` ENUM('student','teacher','hod','cr','cultural','sports','general') NOT NULL DEFAULT 'student',
  `department_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = all departments',
  `description` TEXT DEFAULT NULL,
  `banner_image` VARCHAR(255) DEFAULT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `status` ENUM('upcoming','active','frozen','completed','published') DEFAULT 'upcoming',
  `is_result_published` TINYINT(1) DEFAULT 0,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: candidates
-- ============================================================
CREATE TABLE IF NOT EXISTS `candidates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `election_id` INT UNSIGNED NOT NULL,
  `user_type` ENUM('student','teacher') NOT NULL DEFAULT 'student',
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `position` VARCHAR(100) DEFAULT NULL,
  `candidate_number` INT UNSIGNED DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT 'default.png',
  `symbol` VARCHAR(255) DEFAULT NULL,
  `manifesto` TEXT DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_candidate` (`election_id`,`user_type`,`user_id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: votes
-- ============================================================
CREATE TABLE IF NOT EXISTS `votes` (
  `vote_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `election_id` INT UNSIGNED NOT NULL,
  `candidate_id` INT UNSIGNED NOT NULL,
  `voter_type` ENUM('student','teacher') NOT NULL,
  `voter_id` INT UNSIGNED NOT NULL,
  `voted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `device_info` VARCHAR(500) DEFAULT NULL,
  `vote_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 hash for audit',
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `unique_vote` (`election_id`,`voter_type`,`voter_id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: otp_verification
-- ============================================================
CREATE TABLE IF NOT EXISTS `otp_verification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(200) NOT NULL COMMENT 'email or mobile',
  `otp_code` VARCHAR(10) NOT NULL,
  `purpose` ENUM('register','login','forgot_password','vote_confirm') DEFAULT 'register',
  `is_used` TINYINT(1) DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_identifier` (`identifier`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: announcements
-- ============================================================
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `posted_by_role` ENUM('admin','hod') DEFAULT 'admin',
  `posted_by_id` INT UNSIGNED DEFAULT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = college-wide',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_role` ENUM('admin','student','teacher','hod') NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_role`,`user_id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient_role` ENUM('admin','student','teacher','hod','all') DEFAULT 'all',
  `recipient_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = broadcast',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info','success','warning','danger') DEFAULT 'info',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: results
-- ============================================================
CREATE TABLE IF NOT EXISTS `results` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `election_id` INT UNSIGNED NOT NULL,
  `candidate_id` INT UNSIGNED NOT NULL,
  `total_votes` INT UNSIGNED DEFAULT 0,
  `rank` TINYINT UNSIGNED DEFAULT NULL,
  `is_winner` TINYINT(1) DEFAULT 0,
  `published_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_result` (`election_id`,`candidate_id`)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: failed_logins (Fraud Detection)
-- ============================================================
CREATE TABLE IF NOT EXISTS `failed_logins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(200) DEFAULT NULL,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA: Super Admin
-- Password: admin123
-- ============================================================
INSERT INTO `admins` (`name`,`email`,`password`) VALUES
('Super Admin','admin@college.edu','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
-- SEED DATA: Departments
-- ============================================================
INSERT INTO `departments` (`name`,`code`,`description`) VALUES
('Computer Science','CS','Department of Computer Science and Engineering'),
('Information Technology','IT','Department of Information Technology'),
('Civil Engineering','CE','Department of Civil Engineering'),
('Mechanical Engineering','ME','Department of Mechanical Engineering'),
('Electronics & Telecom','ET','Department of Electronics and Telecommunication'),
('MBA','MBA','Master of Business Administration'),
('BCA','BCA','Bachelor of Computer Applications'),
('MCA','MCA','Master of Computer Applications');

-- ============================================================
-- SEED DATA: HOD for Computer Dept
-- Password: hod123
-- ============================================================
INSERT INTO `hods` (`name`,`email`,`password`,`teacher_id`,`department_id`,`designation`,`mobile`) VALUES
('Dr. Rajesh Sharma','hod.cs@college.edu','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','HOD-CS-001',1,'Head of Department','9876543210');

-- ============================================================
-- SEED DATA: Sample Elections
-- ============================================================
INSERT INTO `elections` (`title`,`election_type`,`department_id`,`description`,`start_datetime`,`end_datetime`,`status`) VALUES
('BCA CR Election 2026','cr',7,'Election for Class Representative of BCA Department',DATE_ADD(NOW(), INTERVAL -1 DAY),DATE_ADD(NOW(), INTERVAL 2 DAY),'active'),
('Computer Department Teacher Representative','teacher',1,'Teacher representative election for CS Department',DATE_ADD(NOW(), INTERVAL 3 DAY),DATE_ADD(NOW(), INTERVAL 7 DAY),'upcoming'),
('Cultural Secretary Election 2026','cultural',NULL,'College-wide Cultural Secretary Election',DATE_ADD(NOW(), INTERVAL 5 DAY),DATE_ADD(NOW(), INTERVAL 9 DAY),'upcoming');

-- ============================================================
-- SEED DATA: Announcements
-- ============================================================
INSERT INTO `announcements` (`title`,`content`,`posted_by_role`,`posted_by_id`) VALUES
('Welcome to College Voting System','The new digital voting system is now live! All students and teachers can register and participate in elections.',1,1),
('Election Schedule Released','The election schedule for 2026 has been released. Please check the elections page for more details.',1,1);

-- ============================================================
-- Add foreign keys after all tables created
-- ============================================================
ALTER TABLE `hods`
  ADD CONSTRAINT `fk_hod_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT;

ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT;

ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teacher_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT;

ALTER TABLE `elections`
  ADD CONSTRAINT `fk_election_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL;

ALTER TABLE `candidates`
  ADD CONSTRAINT `fk_candidate_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE;

ALTER TABLE `votes`
  ADD CONSTRAINT `fk_vote_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vote_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE;

ALTER TABLE `results`
  ADD CONSTRAINT `fk_result_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_result_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE;
