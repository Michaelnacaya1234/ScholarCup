<?php
session_start();
require_once '../includes/config/database.php';
require_once '../includes/Auth.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Initialize database connection
$db = Database::getInstance();

// Function to execute SQL file
function executeSQLFile($db, $file) {
    $success = true;
    $error = '';
    
    if (file_exists($file)) {
        $sql = file_get_contents($file);
        
        // Split SQL by semicolon
        $queries = explode(';', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            
            if (!empty($query)) {
                $result = $db->query($query);
                
                if (!$result) {
                    $success = false;
                    $error .= "Error executing query: " . $db->error . "<br>";
                }
            }
        }
    } else {
        $success = false;
        $error = "SQL file not found: $file";
    }
    
    return ['success' => $success, 'error' => $error];
}

// Process form submission
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $result = executeSQLFile($db, '../database_update.sql');
    
    if ($result['success']) {
        $status = 'success';
        $message = 'Database updated successfully!';
    } else {
        $status = 'error';
        $message = 'Error updating database: ' . $result['error'];
    }
}

// Check if tables exist
$student_academic_status_exists = $db->query("SHOW TABLES LIKE 'student_academic_status'")->num_rows > 0;
$return_service_exists = $db->query("SHOW TABLES LIKE 'return_service'")->num_rows > 0;
$academic_status_options_exists = $db->query("SHOW TABLES LIKE 'academic_status_options'")->num_rows > 0;
$student_current_status_exists = $db->query("SHOW TABLES LIKE 'student_current_status'")->num_rows > 0;

// All tables exist flag
$all_tables_exist = $student_academic_status_exists && $return_service_exists && 
                   $academic_status_options_exists && $student_current_status_exists;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Database - Scholar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-container {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .status-ok {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-missing {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-status {
            margin-top: 20px;
        }
        .table-status table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-status th, .table-status td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table-status th {
            background-color: #f2f2f2;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-green {
            background-color: #28a745;
        }
        .status-red {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include_once '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <h1>Database Update</h1>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($all_tables_exist): ?>
                <div class="status-container status-ok">
                    <h3>Database Status: OK</h3>
                    <p>All required tables exist in the database.</p>
                </div>
                <?php else: ?>
                <div class="status-container status-missing">
                    <h3>Database Status: Update Required</h3>
                    <p>Some required tables are missing from the database. Please click the update button below to create them.</p>
                    
                    <form method="POST" action="">
                        <button type="submit" name="update" class="btn btn-primary">Update Database</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="table-status">
                    <h3>Table Status</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>student_academic_status</td>
                                <td>
                                    <span class="status-indicator <?php echo $student_academic_status_exists ? 'status-green' : 'status-red'; ?>"></span>
                                    <?php echo $student_academic_status_exists ? 'Exists' : 'Missing'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>return_service</td>
                                <td>
                                    <span class="status-indicator <?php echo $return_service_exists ? 'status-green' : 'status-red'; ?>"></span>
                                    <?php echo $return_service_exists ? 'Exists' : 'Missing'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>academic_status_options</td>
                                <td>
                                    <span class="status-indicator <?php echo $academic_status_options_exists ? 'status-green' : 'status-red'; ?>"></span>
                                    <?php echo $academic_status_options_exists ? 'Exists' : 'Missing'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>student_current_status</td>
                                <td>
                                    <span class="status-indicator <?php echo $student_current_status_exists ? 'status-green' : 'status-red'; ?>"></span>
                                    <?php echo $student_current_status_exists ? 'Exists' : 'Missing'; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>