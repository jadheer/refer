CREATE DATABASE IF NOT EXISTS `sample_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sample_db`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;

-- Drop tables if they exist
DROP TABLE IF EXISTS `department`;
DROP TABLE IF EXISTS `employee`;
DROP TABLE IF EXISTS `project`;
DROP TABLE IF EXISTS `employee_project`;

-- Table 1: Department
CREATE TABLE `department` (
  `dept_id` INT AUTO_INCREMENT NOT NULL,
  `dept_name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`dept_id`)
);

-- Table 2: Employee
CREATE TABLE `employee` (
  `emp_id` INT AUTO_INCREMENT NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `dob` DATE DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone_number` VARCHAR(15) DEFAULT NULL,
  `salary` DECIMAL(10, 2) NOT NULL,
  `hire_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `dept_id` INT DEFAULT NULL,
  PRIMARY KEY (`emp_id`),
  FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`) ON DELETE SET NULL
);

-- Table 3: Project
CREATE TABLE `project` (
  `proj_id` INT AUTO_INCREMENT NOT NULL,
  `proj_name` VARCHAR(255) NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `budget` DECIMAL(12, 2) NOT NULL,
  PRIMARY KEY (`proj_id`)
);

-- Table 4: Employee_Project (Many-to-Many relationship)
CREATE TABLE `employee_project` (
  `emp_id` INT NOT NULL,
  `proj_id` INT NOT NULL,
  `role` VARCHAR(50) DEFAULT NULL,
  `hours_worked` DECIMAL(5, 2) DEFAULT 0.00,
  PRIMARY KEY (`emp_id`, `proj_id`),
  FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON DELETE CASCADE,
  FOREIGN KEY (`proj_id`) REFERENCES `project` (`proj_id`) ON DELETE CASCADE
);
