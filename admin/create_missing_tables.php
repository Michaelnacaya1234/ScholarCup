<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$success_message = '';
$error_message = '';

// Function to execute SQL query
function executeSQL($db, $sql) {
    try {
        if ($db->query($sql)) {
            return ['success' => true, 'message' => 'Query executed successfully'];
        } else {
            return ['success' => false, 'message' => 'Error executing query: ' . $db->error];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    // SQL to create return_service_announcements table
    $sql = "CREATE TABLE IF NOT EXISTS return_service_announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        date DATE NOT NULL,
        description TEXT NOT NULL,
        location VARCHAR(255) NOT NULL,
        hours INT NOT NULL,
        slots INT NOT NULL,
        requirements TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    
    $result = executeSQL($db, $sql);
    
    if ($result['success']) {
        $success_message = "The return_service_announcements table has been created successfully.";
    } else {
        $error_message = $result['message'];
    }
}

// Check if tables exist
$return_service_announcements_exists = $db->query("SHOW TABLES LIKE 'return_service_announcements'")->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Missing Tables - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
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
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table-status th {
            background-color: #f2f2f2;
        }
        .status-icon {
            font-size: 1.2em;
        }
        .status-icon.ok {
            color: #28a745;
        }
        .status-icon.missing {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <h1>Create Missing Tables</h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="table-status">
                <h2>Database Tables Status</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>return_service_announcements</td>
                            <td>
                                <?php if ($return_service_announcements_exists): ?>
                                    <span class="status-icon ok"><i class="fas fa-check-circle"></i> Exists</span>
                                <?php else: ?>
                                    <span class="status-icon missing"><i class="fas fa-times-circle"></i> Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if (!$return_service_announcements_exists): ?>
                <div class="status-container status-missing">
                    <p><strong>Missing Tables Detected!</strong> Some required tables are missing from your database.</p>
                    <p>Click the button below to create the missing tables:</p>
                    
                    <form method="post" action="">
                        <button type="submit" name="create_tables" class="btn btn-primary">Create Missing Tables</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="status-container status-ok">
                    <p><strong>All Required Tables Exist!</strong> Your database schema is up to date.</p>
                    <p><a href="return_service.php" class="btn btn-primary">Go to Return Service Page</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>