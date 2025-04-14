<?php
/**
 * Database Fix Script for Scholar Management System
 * This script will execute the database.sql file and create necessary user accounts
 */

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Scholar Database Setup</h1>";
echo "<p>Starting database setup process...</p>";

// Step 1: Create database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'scholar_db';

try {
    // Connect to MySQL server
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>Connected to MySQL server successfully.</p>";
    
    // Step 2: Create or select database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Database '$dbname' created or already exists.</p>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($dbname);
    echo "<p>Selected database: $dbname</p>";
    
    // Step 3: Create users table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50) NULL,
        last_name VARCHAR(50) NOT NULL,
        role ENUM('admin', 'staff', 'student') NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'users' created or already exists.</p>";
    } else {
        throw new Exception("Error creating users table: " . $conn->error);
    }
    
    // Step 4: Create staff_profiles table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS staff_profiles (
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
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'staff_profiles' created or already exists.</p>";
    } else {
        throw new Exception("Error creating staff_profiles table: " . $conn->error);
    }
    
    // Step 5: Create student_profiles table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS student_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        student_id VARCHAR(20) NULL,
        course VARCHAR(100) NULL,
        year_level INT NULL,
        school VARCHAR(100) NULL,
        contact_number VARCHAR(20) NULL,
        address TEXT NULL,
        profile_picture VARCHAR(255) NULL,
        facebook_link VARCHAR(255) NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'student_profiles' created or already exists.</p>";
    } else {
        throw new Exception("Error creating student_profiles table: " . $conn->error);
    }
    
    // Step 6: Create student_current_status table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS student_current_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        status ENUM('complied', 'not comply', 'pending') DEFAULT 'pending',
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'student_current_status' created or already exists.</p>";
    } else {
        throw new Exception("Error creating student_current_status table: " . $conn->error);
    }
    
    // Step 7: Insert default user accounts if they don't exist
    $check_admin = $conn->query("SELECT id FROM users WHERE email = 'admin@gmail.com'");
    if ($check_admin->num_rows == 0) {
        // Insert admin user - password: admin123
        $admin_password = '$2y$10$WFpCrhH2zc5hcFmGXMZq8OrYa.ZMYt.YfPDi3pj57hDCbfuCjKGqy';
        $sql = "INSERT INTO users (email, password, first_name, last_name, role) 
                VALUES ('admin@gmail.com', '$admin_password', 'Admin', 'User', 'admin')";
        if ($conn->query($sql) === TRUE) {
            echo "<p>Default admin user created: admin@gmail.com (Password: admin123)</p>";
        } else {
            echo "<p>Warning: Failed to create admin user: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Admin user already exists.</p>";
    }
    
    $check_staff = $conn->query("SELECT id FROM users WHERE email = 'staff@gmail.com'");
    if ($check_staff->num_rows == 0) {
        // Insert staff user - password: staff123
        $staff_password = '$2y$10$EDNt7WOOLXZNHwzU3y5W2eQaE3ukDfJP9kRIjlm7CZnNGn0FEgXVW';
        $sql = "INSERT INTO users (email, password, first_name, last_name, role) 
                VALUES ('staff@gmail.com', '$staff_password', 'Staff', 'User', 'staff')";
        if ($conn->query($sql) === TRUE) {
            echo "<p>Default staff user created: staff@gmail.com (Password: staff123)</p>";
        } else {
            echo "<p>Warning: Failed to create staff user: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Staff user already exists.</p>";
    }
    
    $check_student = $conn->query("SELECT id FROM users WHERE email = 'student@gmail.com'");
    if ($check_student->num_rows == 0) {
        // Insert student user - password: student123
        $student_password = '$2y$10$x/B7FjK4m5sYYXvsNWGxJeA2Y/vsxVn3YnVdlAdXAVvG6XLPgLiuq';
        $sql = "INSERT INTO users (email, password, first_name, last_name, role) 
                VALUES ('student@gmail.com', '$student_password', 'Student', 'User', 'student')";
        if ($conn->query($sql) === TRUE) {
            echo "<p>Default student user created: student@gmail.com (Password: student123)</p>";
        } else {
            echo "<p>Warning: Failed to create student user: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Student user already exists.</p>";
    }
    
    // Step 8: Add other necessary tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS student_academic_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            semester VARCHAR(50) NULL,
            school_year VARCHAR(20) NULL,
            grades DECIMAL(5,2) NULL,
            status ENUM('renewed', 'not renewed', 'pending') DEFAULT 'pending',
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            file_path VARCHAR(255) NULL,
            submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            feedback TEXT NULL,
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (reviewed_by) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS return_service (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_name VARCHAR(255) NOT NULL,
            activity_description TEXT NULL,
            hours_rendered INT NOT NULL,
            activity_date DATE NOT NULL,
            location VARCHAR(255) NULL,
            proof_file VARCHAR(255) NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            feedback TEXT NULL,
            approved_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (approved_by) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_pinned BOOLEAN DEFAULT FALSE,
            visibility ENUM('all', 'students', 'staff') DEFAULT 'all',
            FOREIGN KEY (created_by) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS allowance_schedule (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            cashier_no VARCHAR(50) NULL,
            payroll_no VARCHAR(50) NULL,
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
        )",
        
        "CREATE TABLE IF NOT EXISTS allowance_claims (
            id INT AUTO_INCREMENT PRIMARY KEY,
            allowance_id INT NOT NULL,
            student_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            claim_date DATETIME NULL,
            status ENUM('pending', 'claimed', 'unclaimed', 'expired') DEFAULT 'pending',
            remarks TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (allowance_id) REFERENCES allowance_schedule(id),
            FOREIGN KEY (student_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS concerns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            assigned_to INT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS concern_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            concern_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (concern_id) REFERENCES concerns(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS archived_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(100) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50) NULL,
            last_name VARCHAR(50) NOT NULL,
            role ENUM('admin', 'staff', 'student') NOT NULL,
            archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            archived_by INT NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS archived_staff_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            staff_id VARCHAR(20) NULL,
            department VARCHAR(100) NULL,
            position VARCHAR(100) NULL,
            contact_number VARCHAR(20) NULL,
            archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS archived_student_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            student_id VARCHAR(20) NULL,
            course VARCHAR(100) NULL,
            year_level INT NULL,
            school VARCHAR(100) NULL,
            contact_number VARCHAR(20) NULL,
            address TEXT NULL,
            archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $table_sql) {
        if ($conn->query($table_sql) === TRUE) {
            echo "<p>Table created or already exists.</p>";
        } else {
            echo "<p>Warning: " . $conn->error . "</p>";
        }
    }
    
    echo "<h2>Database Setup Complete!</h2>";
    echo "<p>You can now log in using one of these accounts:</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@gmail.com / admin123</li>";
    echo "<li><strong>Staff:</strong> staff@gmail.com / staff123</li>";
    echo "<li><strong>Student:</strong> student@gmail.com / student123</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 