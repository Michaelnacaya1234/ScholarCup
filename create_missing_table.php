<?php
/**
 * Create Missing Table Script
 * This script creates the missing return_service_announcements table
 */

// Include database connection
require_once 'database.php';

// SQL to create the missing table
$sql = "CREATE TABLE IF NOT EXISTS return_service_announcements (
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
)";

// Execute the query
$result = $db->query($sql);

// Check if table was created successfully
if ($result) {
    echo "<h2>Success!</h2>";
    echo "<p>The 'return_service_announcements' table has been created successfully.</p>";
    echo "<p>You can now use the return service functionality in the admin panel.</p>";
    echo "<p><a href='/Scholar/admin/return_service.php'>Go to Return Service Page</a></p>";
} else {
    echo "<h2>Error</h2>";
    echo "<p>Failed to create the table: " . $db->error . "</p>";
}

// Check if the table exists now
$check_query = "SHOW TABLES LIKE 'return_service_announcements'";
$check_result = $db->query($check_query);

if ($check_result->num_rows > 0) {
    echo "<p>Verification: The table exists in the database.</p>";
} else {
    echo "<p>Verification: The table does not exist in the database. There might be an issue with the creation process.</p>";
}