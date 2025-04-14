<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$success_message = '';
$error_message = '';

// Handle delete all action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if the table exists first
        $table_check = $db->query("SHOW TABLES LIKE 'allowance_schedule'");
        
        if ($table_check->num_rows > 0) {
            // Delete all records from the allowance_schedule table
            $stmt = $db->prepare("DELETE FROM allowance_schedule");
            
            if ($stmt->execute()) {
                $success_message = "All allowance schedules have been deleted successfully.";
            } else {
                $error_message = "Failed to delete allowance schedules. Please try again.";
            }
        } else {
            $error_message = "Allowance schedule table does not exist.";
        }
    } catch (Exception $e) {
        $error_message = "An error occurred: " . $e->getMessage();
        error_log("Error deleting all allowance schedules: " . $e->getMessage());
    }
}

// Redirect back to allowance schedule page
header("Location: allowance_schedule.php?" . ($success_message ? "success=" . urlencode($success_message) : "error=" . urlencode($error_message)));
exit;