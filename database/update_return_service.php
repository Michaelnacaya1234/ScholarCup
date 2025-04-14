<?php
require_once '../database.php';

try {
    // Check if the created_by column exists
    $check = $db->query("SHOW COLUMNS FROM return_service_activities LIKE 'created_by'");
    
    if ($check->num_rows === 0) {
        // Add created_by column
        $db->query("ALTER TABLE return_service_activities 
                   ADD COLUMN created_by INT,
                   ADD CONSTRAINT fk_rs_created_by 
                   FOREIGN KEY (created_by) REFERENCES users(id)");
        
        // Update existing records to set created_by from user_id
        $db->query("UPDATE return_service_activities SET created_by = user_id");
        
        echo "Successfully added created_by column to return_service_activities table.";
    } else {
        echo "The created_by column already exists.";
    }
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
