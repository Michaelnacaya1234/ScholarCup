<?php
require_once '../database.php';

try {
    // Check if created_at column exists in submissions table
    $check = $db->query("SHOW COLUMNS FROM submissions LIKE 'created_at'");
    
    if ($check->num_rows === 0) {
        // Add created_at column
        $db->query("ALTER TABLE submissions 
                   ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        
        echo "Successfully added created_at column to submissions table.";
    } else {
        echo "The created_at column already exists in submissions table.";
    }
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
