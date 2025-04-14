<?php
/**
 * Create Student Current Status Table Script
 * This script creates the missing student_current_status table
 */

// Include database connection
require_once 'database.php';

// SQL to create the missing table
$sql = "CREATE TABLE IF NOT EXISTS student_current_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

// Execute the query
$result = $db->query($sql);

// Check if table was created successfully
if ($result) {
    echo "<h2>Success!</h2>";
    echo "<p>The 'student_current_status' table has been created successfully.</p>";
    echo "<p>You can now use the student status functionality in the staff panel.</p>";
    echo "<p><a href='/Scholar/staff/students_status.php'>Go to Students Status Page</a></p>";
} else {
    echo "<h2>Error</h2>";
    echo "<p>Failed to create the table: " . $db->error . "</p>";
}

// Check if the table exists now
$check_query = "SHOW TABLES LIKE 'student_current_status'";
$check_result = $db->query($check_query);

if ($check_result->num_rows > 0) {
    echo "<p>Verification: The table exists in the database.</p>";
} else {
    echo "<p>Verification: The table does not exist in the database. There might be an issue with the creation process.</p>";
}