<?php
// Script to create the missing student_grades table
session_start();
require_once 'includes/config/database.php';

// Initialize database connection
$db = Database::getInstance();

// Check if the table already exists
$table_check = $db->query("SHOW TABLES LIKE 'student_grades'");
if ($table_check->num_rows > 0) {
    echo "<p>The student_grades table already exists.</p>";
} else {
    // Create the student_grades table
    $create_table_query = "CREATE TABLE IF NOT EXISTS student_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        academic_year VARCHAR(20) NOT NULL,
        semester INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        gpa DECIMAL(3,2) DEFAULT NULL,
        subjects JSON DEFAULT NULL,
        submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        UNIQUE KEY unique_student_period (user_id, academic_year, semester)
    )";
    
    if ($db->query($create_table_query)) {
        echo "<p>The student_grades table has been created successfully.</p>";
    } else {
        echo "<p>Error creating student_grades table: " . $db->getError() . "</p>";
    }
}

echo "<p><a href='index.php'>Return to homepage</a></p>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Student Grades Table - Scholar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        p {
            margin-bottom: 15px;
        }
        a {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Setup Student Grades Table</h1>
        <!-- PHP output will appear here -->
    </div>
</body>
</html>