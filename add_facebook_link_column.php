<?php
require_once 'includes/config/database.php';

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get database connection
$db = Database::getInstance();

// Check if column exists
$check_column = "SHOW COLUMNS FROM staff_profiles LIKE 'facebook_link'";
$result = $db->query($check_column);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $add_column = "ALTER TABLE staff_profiles ADD COLUMN facebook_link VARCHAR(255) DEFAULT NULL";
    
    if ($db->query($add_column)) {
        $message = "Successfully added 'facebook_link' column to staff_profiles table.";
        $success = true;
    } else {
        $message = "Error adding column: " . $db->error;
        $success = false;
    }
} else {
    $message = "Column 'facebook_link' already exists in staff_profiles table.";
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update</title>
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
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
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
        <h1>Database Structure Update</h1>
        
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        
        <p>This script adds the missing 'facebook_link' column to the staff_profiles table.</p>
        
        <a href="staff/profile.php">Return to Staff Profile</a>
        <a href="index.php">Return to Homepage</a>
    </div>
</body>
</html> 