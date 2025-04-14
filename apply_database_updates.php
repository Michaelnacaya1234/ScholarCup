<?php
/**
 * Database Update Script
 * 
 * This script applies the necessary updates to the database schema
 * to add missing columns required by the application.
 */

// Include database connection
require_once 'includes/config/database.php';

// Initialize database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    return $result->num_rows > 0;
}

// Start transaction
$conn->begin_transaction();

try {
    // Add event_id column to return_service_activities table if it doesn't exist
    if (!columnExists($conn, 'return_service_activities', 'event_id')) {
        $conn->query("ALTER TABLE return_service_activities ADD COLUMN event_id INT NULL");
        $conn->query("ALTER TABLE return_service_activities ADD CONSTRAINT fk_rs_event_id FOREIGN KEY (event_id) REFERENCES events(id)");
        echo "<p>Added event_id column to return_service_activities table.</p>";
    }
    
    // Add event_id column to announcements table if it doesn't exist
    if (!columnExists($conn, 'announcements', 'event_id')) {
        $conn->query("ALTER TABLE announcements ADD COLUMN event_id INT NULL");
        $conn->query("ALTER TABLE announcements ADD CONSTRAINT fk_announcement_event_id FOREIGN KEY (event_id) REFERENCES events(id)");
        echo "<p>Added event_id column to announcements table.</p>";
    }
    
    // Add hours column to return_service_activities if it doesn't exist
    if (!columnExists($conn, 'return_service_activities', 'hours')) {
        $conn->query("ALTER TABLE return_service_activities ADD COLUMN hours INT NULL");
        echo "<p>Added hours column to return_service_activities table.</p>";
    }
    
    // Add proof_file column to return_service_activities if it doesn't exist
    if (!columnExists($conn, 'return_service_activities', 'proof_file')) {
        $conn->query("ALTER TABLE return_service_activities ADD COLUMN proof_file VARCHAR(255) NULL");
        echo "<p>Added proof_file column to return_service_activities table.</p>";
    }
    
    // Add title column to return_service_activities if it doesn't exist
    if (!columnExists($conn, 'return_service_activities', 'title')) {
        $conn->query("ALTER TABLE return_service_activities ADD COLUMN title VARCHAR(255) NULL");
        echo "<p>Added title column to return_service_activities table.</p>";
    }
    
    // Add user_id column to return_service_activities if it doesn't exist (to replace student_id)
    if (!columnExists($conn, 'return_service_activities', 'user_id')) {
        $conn->query("ALTER TABLE return_service_activities ADD COLUMN user_id INT NULL");
        $conn->query("ALTER TABLE return_service_activities ADD CONSTRAINT fk_rs_user_id FOREIGN KEY (user_id) REFERENCES users(id)");
        echo "<p>Added user_id column to return_service_activities table.</p>";
    }
    
    // Commit the transaction
    $conn->commit();
    echo "<p style='color: green; font-weight: bold;'>Database updates applied successfully!</p>";
    echo "<p>You can now use the calendar and event details features.</p>";
    echo "<p><a href='index.php'>Return to homepage</a></p>";
    
} catch (Exception $e) {
    // Rollback the transaction if an error occurred
    $conn->rollback();
    echo "<p style='color: red; font-weight: bold;'>Error applying database updates: " . $e->getMessage() . "</p>";
    echo "<p>Please contact the system administrator.</p>";
}
?>