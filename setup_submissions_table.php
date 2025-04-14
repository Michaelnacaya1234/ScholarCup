<?php
// Script to create the submissions table
require_once 'includes/config/database.php';

// Initialize database connection
$db = Database::getInstance();

// Check if the table already exists
$table_check = $db->query("SHOW TABLES LIKE 'submissions'");
if ($table_check->num_rows > 0) {
    echo "<p>The submissions table already exists.</p>";
} else {
    // Create the submissions table
    $create_table_query = "CREATE TABLE submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        submission_type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        feedback TEXT,
        submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_student (student_id),
        INDEX idx_status (status),
        INDEX idx_date (submission_date, created_at)
    )";
    
    if ($db->query($create_table_query)) {
        echo "<p>The submissions table has been created successfully.</p>";
        echo "<p>You can now uncomment the submissions query in student/dashboard.php.</p>";
    } else {
        echo "<p>Error creating submissions table: " . $db->getError() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Submissions Table - Scholar</title>
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
        <h1>Setup Submissions Table</h1>
        <!-- PHP output will appear here -->
        <p><a href="index.php">Return to homepage</a></p>
        <p><a href="student/dashboard.php">Go to Student Dashboard</a></p>
    </div>
</body>
</html> 