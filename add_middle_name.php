<?php
require_once 'database.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS middle_name VARCHAR(50) NULL AFTER first_name";
    $db->query($sql);
    echo "Successfully added middle_name column to users table";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
