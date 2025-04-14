<?php
namespace Scholar\Admin;
require_once '../includes/config/database.php';

use Database;

class DatabaseStructureChecker {
    private $db;

    public function __construct() {
        $this->db = \Database::getInstance();
    }

    public function checkAndUpdate() {
        try {
            // First drop existing archive tables if they exist
            $this->db->query("DROP TABLE IF EXISTS archived_staff_profiles");
            $this->db->query("DROP TABLE IF EXISTS archived_student_profiles");
            $this->db->query("DROP TABLE IF EXISTS archived_users");

            // Create staff_profiles table if it doesn't exist
            $this->db->query("CREATE TABLE IF NOT EXISTS staff_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                staff_id VARCHAR(20) NULL,
                department VARCHAR(100) NULL,
                position VARCHAR(100) NULL,
                contact_number VARCHAR(20) NULL,
                profile_picture VARCHAR(255) NULL,
                phone VARCHAR(20) NULL,
                address TEXT NULL,
                birthday DATE NULL,
                school_graduated VARCHAR(100) NULL,
                course VARCHAR(100) NULL,
                facebook_link VARCHAR(255) NULL,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");

            // Create student_profiles table if it doesn't exist
            $this->db->query("CREATE TABLE IF NOT EXISTS student_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                student_id VARCHAR(20) NULL,
                course VARCHAR(100) NULL,
                year_level INT NULL,
                school VARCHAR(100) NULL,
                contact_number VARCHAR(20) NULL,
                address TEXT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");

            // Check if users table exists
            $result = $this->db->query("SHOW TABLES LIKE 'users'");
            if ($result->num_rows === 0) {
                // Create users table
                $this->db->query("CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    first_name VARCHAR(50) NOT NULL,
                    middle_name VARCHAR(50) NULL,
                    last_name VARCHAR(50) NOT NULL,
                    role ENUM('admin', 'staff', 'student') NOT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }

            // Create student_current_status table if it doesn't exist
            $this->db->query("CREATE TABLE IF NOT EXISTS student_current_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                status ENUM('complied', 'not comply', 'pending') DEFAULT 'pending',
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");

            // Check if middle_name column exists
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'middle_name'");
            if ($result->num_rows === 0) {
                // Add middle_name column if it doesn't exist
                $this->db->query("ALTER TABLE users ADD COLUMN middle_name VARCHAR(50) NULL AFTER first_name");
            }
            
            // Check if student_profiles table has school column
            $result = $this->db->query("SHOW COLUMNS FROM student_profiles LIKE 'school'");
            if ($result->num_rows === 0) {
                $this->db->query("ALTER TABLE student_profiles ADD COLUMN school VARCHAR(100) NULL");
            }
            
            // Check if student_profiles table has student_id column
            $result = $this->db->query("SHOW COLUMNS FROM student_profiles LIKE 'student_id'");
            if ($result->num_rows === 0) {
                // Add student_id column if it doesn't exist
                $this->db->query("ALTER TABLE student_profiles ADD COLUMN student_id VARCHAR(20) NULL");
                echo "Added missing student_id column to student_profiles table.<br>";
            }
            
            // Check if staff_profiles table has staff_id column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'staff_id'");
            if ($result->num_rows === 0) {
                // Add staff_id column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN staff_id VARCHAR(20) NULL");
                echo "Added missing staff_id column to staff_profiles table.<br>";
            }
            
            // Check if staff_profiles table has contact_number column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'contact_number'");
            if ($result->num_rows === 0) {
                // Add contact_number column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN contact_number VARCHAR(20) NULL");
            }
            
            // Check if staff_profiles table has facebook_link column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'facebook_link'");
            if ($result->num_rows === 0) {
                // Add facebook_link column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN facebook_link VARCHAR(255) NULL");
                echo "Added missing facebook_link column to staff_profiles table.<br>";
            }
            
            // Check if staff_profiles table has profile_picture column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'profile_picture'");
            if ($result->num_rows === 0) {
                // Add profile_picture column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN profile_picture VARCHAR(255) NULL");
                echo "Added missing profile_picture column to staff_profiles table.<br>";
            }
            
            // Check if staff_profiles table has phone column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'phone'");
            if ($result->num_rows === 0) {
                // Add phone column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN phone VARCHAR(20) NULL");
                echo "Added missing phone column to staff_profiles table.<br>";
            }
            
            // Check if staff_profiles table has address column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'address'");
            if ($result->num_rows === 0) {
                // Add address column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN address TEXT NULL");
                echo "Added missing address column to staff_profiles table.<br>";
            }
            
            // Check if staff_profiles table has birthday column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'birthday'");
            if ($result->num_rows === 0) {
                // Add birthday column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN birthday DATE NULL");
                echo "Added missing birthday column to staff_profiles table.<br>";
            }
            
            // Check if staff_profiles table has school_graduated column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'school_graduated'");
            if ($result->num_rows === 0) {
                // Add school_graduated column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN school_graduated VARCHAR(100) NULL");
                echo "Added missing school_graduated column to staff_profiles table.<br>";
            }
            
            // Check if staff_profiles table has course column
            $result = $this->db->query("SHOW COLUMNS FROM staff_profiles LIKE 'course'");
            if ($result->num_rows === 0) {
                // Add course column if it doesn't exist
                $this->db->query("ALTER TABLE staff_profiles ADD COLUMN course VARCHAR(100) NULL");
                echo "Added missing course column to staff_profiles table.<br>";
            }
            
            // Check if student_profiles table has contact_number column
            $result = $this->db->query("SHOW COLUMNS FROM student_profiles LIKE 'contact_number'");
            if ($result->num_rows === 0) {
                // Add contact_number column if it doesn't exist
                $this->db->query("ALTER TABLE student_profiles ADD COLUMN contact_number VARCHAR(20) NULL");
                echo "Added missing contact_number column to student_profiles table.<br>";
            }

            // Create archived_users table
            $this->db->query("CREATE TABLE IF NOT EXISTS archived_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email VARCHAR(100) NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                middle_name VARCHAR(50) NULL,
                last_name VARCHAR(50) NOT NULL,
                role ENUM('admin', 'staff', 'student') NOT NULL,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                archived_by INT NOT NULL,
                FOREIGN KEY (archived_by) REFERENCES users(id)
            )");

            // Create archived_staff_profiles table
            $this->db->query("CREATE TABLE IF NOT EXISTS archived_staff_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                staff_id VARCHAR(20) NULL,
                department VARCHAR(100) NULL,
                position VARCHAR(100) NULL,
                contact_number VARCHAR(20) NULL,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Create archived_student_profiles table
            $this->db->query("CREATE TABLE IF NOT EXISTS archived_student_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                student_id VARCHAR(20) NULL,
                course VARCHAR(100) NULL,
                year_level INT NULL,
                school VARCHAR(100) NULL,
                contact_number VARCHAR(20) NULL,
                address TEXT NULL,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Create allowance_schedule table if it doesn't exist
            $this->db->query("CREATE TABLE IF NOT EXISTS allowance_schedule (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                cashier_no VARCHAR(50),
                payroll_no VARCHAR(50),
                period VARCHAR(100) NOT NULL,
                venue VARCHAR(255) NOT NULL,
                schedule DATETIME NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'released', 'cancelled') DEFAULT 'pending',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id),
                FOREIGN KEY (created_by) REFERENCES users(id)
            )");

            // Create allowance_claims table if it doesn't exist
            $this->db->query("CREATE TABLE IF NOT EXISTS allowance_claims (
                id INT AUTO_INCREMENT PRIMARY KEY,
                allowance_id INT NOT NULL,
                student_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                claim_date DATETIME,
                status ENUM('pending', 'claimed', 'unclaimed', 'expired') DEFAULT 'pending',
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (allowance_id) REFERENCES allowance_schedule(id),
                FOREIGN KEY (student_id) REFERENCES users(id)
            )");
            
        } catch (\Exception $e) {
            echo "Error updating database structure: " . $e->getMessage() . "<br>";
            error_log("Database structure update error: " . $e->getMessage());
        }
    }
}
?>
