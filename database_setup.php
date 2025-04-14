<?php
// Database setup guide page
$error_type = isset($_GET['error']) ? $_GET['error'] : '';
$error_message = '';

switch ($error_type) {
    case 'no_database':
        $error_message = 'The database <strong>scholar_db</strong> does not exist. Please follow the steps below to create it.';
        break;
    default:
        if (!empty($error_type)) {
            $error_message = 'Database error: ' . htmlspecialchars(urldecode($error_type));
        }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Guide - Scholar Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
        }
        .guide-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .header {
            background-color: #1E3A8A;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .code-block {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #1E3A8A;
            font-family: monospace;
            margin: 15px 0;
        }
        .step {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .step h3 {
            color: #1E3A8A;
            margin-bottom: 15px;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container guide-container">
        <div class="header">
            <h1 class="text-center mb-0">Database Setup Guide</h1>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <strong>Database Error:</strong> <?php echo $error_message; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>Error "No database selected"?</strong> Follow this guide to properly set up your database.
            </div>
        <?php endif; ?>
        
        <div class="step">
            <h3>Option 1: Using phpMyAdmin</h3>
            <ol>
                <li>Open phpMyAdmin in your browser (usually at <code>http://localhost/phpmyadmin</code>)</li>
                <li>Create a new database by clicking on "New" in the left sidebar</li>
                <li>Enter "scholar_db" as the database name and select "utf8mb4_general_ci" as the collation</li>
                <li>Click "Create"</li>
                <li>Select the newly created "scholar_db" database from the left sidebar</li>
                <li>Click on the "Import" tab in the top menu</li>
                <li>Click "Choose File" and select the <code>database.sql</code> file from your project</li>
                <li>Click "Go" to import the database structure</li>
            </ol>
        </div>
        
        <div class="step">
            <h3>Option 2: Using MySQL Command Line</h3>
            <p>Run these commands in your terminal or command prompt:</p>
            <div class="code-block">
                # Create the database<br>
                mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS scholar_db"<br><br>
                
                # Import the database structure<br>
                mysql -u root -p scholar_db < database.sql
            </div>
            <p><small>Note: Replace "root" with your MySQL username if different.</small></p>
        </div>
        
        <div class="step">
            <h3>Option 3: Using the SQL Script Directly</h3>
            <p>The updated <code>database.sql</code> file already includes the database creation command:</p>
            <div class="code-block">
                -- Create and select database<br>
                CREATE DATABASE IF NOT EXISTS scholar_db;<br>
                USE scholar_db;<br>
                <br>
                -- Create tables...
            </div>
            <p>You can simply import this file using phpMyAdmin or the command line without manually creating the database.</p>
        </div>
        
        <div class="step">
            <h3>Verifying Your Connection</h3>
            <p>Make sure your database connection settings in <code>includes/config/database.php</code> match your database:</p>
            <div class="code-block">
                // Database configuration<br>
                private $host = 'localhost';<br>
                private $username = 'root'; // update this<br>
                private $password = ''; // update this<br>
                private $database = 'scholar_db'; // should match the database name
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="run_database_check.php" class="btn btn-primary me-2">Run Database Structure Check</a>
            <a href="index.php" class="btn btn-secondary">Return to Homepage</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 