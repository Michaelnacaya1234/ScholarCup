<?php
// Script to run the database structure checker

require_once 'includes/config/database.php';
require_once 'admin/check_database_structure.php';

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to capture messages
ob_start();

// Create and run the database structure checker
$checker = new Scholar\Admin\DatabaseStructureChecker();
$checker->checkAndUpdate();

// Get the output messages
$output = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Check</title>
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
            max-height: 400px;
            overflow-y: auto;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        a {
            display: inline-block;
            padding: 10px 15px;
            background-color: #1E3A8A;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        a:hover {
            background-color: #152B5E;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Structure Check</h1>
        
        <div class="success-message">
            Database structure check complete! Any missing tables or columns have been added.
        </div>
        
        <?php if (!empty($output)): ?>
            <h3>Changes Made:</h3>
            <div class="output">
                <?php echo $output; ?>
            </div>
        <?php else: ?>
            <p>No changes were needed. Your database structure is up to date.</p>
        <?php endif; ?>
        
        <div class="links">
            <a href="fix_missing_columns.php">Fix Specific Columns</a>
            <a href="setup_directories.php">Setup Directories</a>
            <a href="staff/profile.php">Go to Staff Profile</a>
            <a href="index.php">Return to Homepage</a>
        </div>
    </div>
</body>
</html>