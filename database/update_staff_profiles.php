<?php
require_once '../database.php';

try {
    // Check if birthday column exists
    $check = $db->query("SHOW COLUMNS FROM staff_profiles LIKE 'birthday'");
    
    if ($check->num_rows === 0) {
        // Add birthday column
        $db->query("ALTER TABLE staff_profiles 
                   ADD COLUMN birthday DATE DEFAULT NULL");
        
        echo "Successfully added birthday column to staff_profiles table.";
    } else {
        echo "The birthday column already exists in staff_profiles table.";
    }
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
