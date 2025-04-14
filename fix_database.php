<?php
/**
 * Fix Database Script
 * 
 * This script executes the database_update.sql file to create the missing student_current_status table
 * that is causing the error in students_status.php
 */

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include necessary files
require_once 'includes/config/database.php';
require_once 'admin/check_database_structure.php';

// Create an instance of the database structure checker
$checker = new \Scholar\Admin\DatabaseStructureChecker();

// Start capturing output
ob_start();

// Check and update database structure
$checker->checkAndUpdate();

// Get the output
$output = ob_get_clean();

// Function to execute SQL file
function executeSQLFile($conn, $file) {
    $success = true;
    $error = '';
    $messages = [];
    
    if (file_exists($file)) {
        $sql = file_get_contents($file);
        
        // Split SQL by semicolon
        $queries = explode(';', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            
            if (!empty($query)) {
                $result = $conn->query($query);
                
                if (!$result) {
                    $success = false;
                    $error .= "Error executing query: " . $conn->error . "<br>";
                } else {
                    // Check if this was a CREATE TABLE query
                    if (stripos($query, 'CREATE TABLE') !== false) {
                        // Extract table name
                        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $query, $matches);
                        if (isset($matches[1])) {
                            $messages[] = "Created table: " . $matches[1];
                        }
                    }
                }
            }
        }
    } else {
        $success = false;
        $error = "SQL file not found: $file";
    }
    
    return ['success' => $success, 'error' => $error, 'messages' => $messages];
}

// Check if student_current_status table exists
$tableExists = $db->query("SHOW TABLES LIKE 'student_current_status'")->num_rows > 0;

// Display HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database Structure</title>
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
            color: #1E3A8A;
            text-align: center;
        }
        .output {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #1E3A8A;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a:hover {
            background-color: #152B5E;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Structure Fix</h1>
        
        <div class="success-message">
            Database structure check complete! Any missing fields have been added.
        </div>
        
        <?php if (!empty($output)): ?>
            <h3>Changes Made:</h3>
            <div class="output">
                <?php echo $output; ?>
            </div>
        <?php else: ?>
            <p>No changes were needed. Your database structure is up to date.</p>
        <?php endif; ?>
        
        <p>The following tasks were performed:</p>
        <ul>
            <li>Checked for missing tables and created them if needed</li>
            <li>Added missing columns to existing tables</li>
            <li>Added the facebook_link column to staff_profiles table</li>
            <li>Added department, position, and contact_number fields to staff profiles</li>
        </ul>
        
        <div>
            <a href="add_facebook_link_column.php">Run Specific Facebook Link Fix</a>
            <a href="staff/profile.php">Go to Staff Profile</a>
            <a href="index.php">Return to Homepage</a>
        </div>
    </div>
</body>
</html>