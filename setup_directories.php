<?php
/**
 * Directory setup script for Scholar Management System
 * 
 * This script ensures all required directories are created and have proper permissions
 */

// Define required directories
$directories = [
    'uploads/',
    'uploads/profile_pictures/',
    'uploads/documents/',
    'uploads/return_service/',
    'uploads/submissions/',
    'uploads/temp/'
];

// Function to create directory with proper permissions
function createDirectory($path) {
    if (!is_dir($path)) {
        if (mkdir($path, 0777, true)) {
            chmod($path, 0777); // Ensure directory is writable
            echo "<p>Created directory: $path</p>";
            return true;
        } else {
            echo "<p class='error'>Failed to create directory: $path</p>";
            return false;
        }
    } else {
        // Directory exists, ensure it has proper permissions
        chmod($path, 0777);
        echo "<p>Directory already exists (permissions updated): $path</p>";
        return true;
    }
}

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

$success = true;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Directories - Scholar Management System</title>
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
        p {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
        }
        p.error {
            background-color: #ffebee;
            color: #c62828;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .failure {
            background-color: #ffebee;
            color: #c62828;
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
        <h1>Scholar Management System - Directory Setup</h1>
        
        <?php
        // Create each directory
        foreach ($directories as $dir) {
            if (!createDirectory($dir)) {
                $success = false;
            }
        }
        
        // Display summary
        if ($success) {
            echo '<div class="summary success">';
            echo 'All directories were created successfully! The system is now ready to handle file uploads.';
            echo '</div>';
        } else {
            echo '<div class="summary failure">';
            echo 'There were issues creating some directories. Please check the error messages above and ensure your web server has write permissions to the application directory.';
            echo '</div>';
        }
        ?>
        
        <a href="index.php">Return to Homepage</a>
    </div>
</body>
</html> 