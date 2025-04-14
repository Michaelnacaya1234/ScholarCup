<?php
/**
 * Fix Missing Tables Script
 * This script creates the missing student_concerns table needed by the admin dashboard
 */

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Creating Missing Tables</h1>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'scholar_db';

try {
    // Connect to database
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Tables to create
    $tables = [
        // Student concerns table
        "CREATE TABLE IF NOT EXISTS student_concerns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        // Return service activities table
        "CREATE TABLE IF NOT EXISTS return_service_activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            hours INT NOT NULL DEFAULT 0,
            proof_file VARCHAR(255) NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        // Return service announcements table
        "CREATE TABLE IF NOT EXISTS return_service_announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(255) NOT NULL,
            hours INT NOT NULL,
            slots INT NOT NULL,
            requirements TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )",
        
        // Student grades table
        "CREATE TABLE IF NOT EXISTS student_grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            semester VARCHAR(50) NOT NULL,
            academic_year VARCHAR(50) NOT NULL,
            grade_file VARCHAR(255) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        // Events table
        "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            location VARCHAR(255) NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )",
        
        // Additional tables that might be missing
        "CREATE TABLE IF NOT EXISTS student_allowance_claims (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            allowance_schedule_id INT NOT NULL,
            claimed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS allowance_releasing_schedule (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cashier_no VARCHAR(50) NOT NULL,
            payroll_no VARCHAR(50) NOT NULL,
            period VARCHAR(100) NOT NULL,
            venue VARCHAR(255) NOT NULL,
            schedule_date DATE NOT NULL,
            schedule_time_start TIME NOT NULL,
            schedule_time_end TIME NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    // Create each table
    foreach ($tables as $sql) {
        if ($conn->query($sql) === TRUE) {
            echo "<p>Table created successfully.</p>";
        } else {
            echo "<p>Warning: " . $conn->error . "</p>";
        }
    }
    
    echo "<h2>Database Update Complete!</h2>";
    echo "<p>Missing tables have been created.</p>";
    echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 