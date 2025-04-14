<?php
// Script to fix missing columns in the staff_profiles table
require_once 'includes/config/database.php';

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get database connection
$db = Database::getInstance();

// Start collecting results
$results = [];
$success = true;

// Check if the staff_profiles table exists
$check_table = "SHOW TABLES LIKE 'staff_profiles'";
$result = $db->query($check_table);

if ($result->num_rows == 0) {
    // Create staff_profiles table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS staff_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        staff_id VARCHAR(20) NULL,
        department VARCHAR(100) NULL,
        position VARCHAR(100) NULL,
        contact_number VARCHAR(20) NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    if ($db->query($create_table)) {
        $results[] = "Created staff_profiles table.";
    } else {
        $results[] = "Error creating staff_profiles table: " . $db->error;
        $success = false;
    }
}

// List of columns to check and add
$columns = [
    'facebook_link' => 'VARCHAR(255) DEFAULT NULL',
    'profile_picture' => 'VARCHAR(255) DEFAULT NULL',
    'phone' => 'VARCHAR(20) DEFAULT NULL',
    'address' => 'TEXT DEFAULT NULL',
    'birthday' => 'DATE DEFAULT NULL',
    'school_graduated' => 'VARCHAR(100) DEFAULT NULL',
    'course' => 'VARCHAR(100) DEFAULT NULL'
];

// Check and add each column
foreach ($columns as $column => $definition) {
    $check_column = "SHOW COLUMNS FROM staff_profiles LIKE '$column'";
    $result = $db->query($check_column);
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $add_column = "ALTER TABLE staff_profiles ADD COLUMN $column $definition";
        
        if ($db->query($add_column)) {
            $results[] = "Added missing '$column' column to staff_profiles table.";
        } else {
            $results[] = "Error adding '$column' column: " . $db->error;
            $success = false;
        }
    } else {
        $results[] = "Column '$column' already exists in staff_profiles table.";
    }
}

// Check if there are existing staff profiles that need updating
$check_profiles = "SELECT COUNT(*) as count FROM staff_profiles";
$result = $db->query($check_profiles);
$row = $result->fetch_assoc();
$profile_count = $row['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Missing Columns</title>
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
        .results {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .results ul {
            margin: 0;
            padding-left: 20px;
        }
        .results li {
            margin-bottom: 5px;
        }
        a {
            display: inline-block;
            margin-top: 10px;
            margin-right: 10px;
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
        <h1>Fix Missing Columns in Database</h1>
        
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? 'All missing columns have been added successfully!' : 'There were some errors adding columns. Please check the results below.'; ?>
        </div>
        
        <div class="results">
            <h3>Results:</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <p>Your staff_profiles table now has all required columns including:</p>
        <ul>
            <li>facebook_link - For storing Facebook profile URLs</li>
            <li>profile_picture - For storing profile image filenames</li>
            <li>phone - For storing phone numbers</li>
            <li>address - For storing addresses</li>
            <li>birthday - For storing birth dates</li>
            <li>school_graduated - For storing school information</li>
            <li>course - For storing course information</li>
        </ul>
        
        <p>There are currently <strong><?php echo $profile_count; ?></strong> staff profiles in the database.</p>
        
        <div>
            <a href="staff/profile.php">Go to Staff Profile</a>
            <a href="setup_directories.php">Setup Upload Directories</a>
            <a href="index.php">Return to Homepage</a>
        </div>
    </div>
</body>
</html> 