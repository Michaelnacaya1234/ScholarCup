<?php
// Simple script to directly log in as admin, staff, or student
session_start();

// Force user login without password verification
$role = isset($_GET['role']) ? $_GET['role'] : 'admin';

// Validate role
if (!in_array($role, ['admin', 'staff', 'student'])) {
    $role = 'admin'; // Default to admin if invalid role
}

// Clear any existing session
$_SESSION = array();
session_destroy();
session_start();

// Start a new database connection
require_once 'includes/config/database.php';
$db = Database::getInstance();

// Find a user with the specified role
$query = "SELECT * FROM users WHERE role = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bind_param('s', $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Set session variables directly
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Redirect to appropriate dashboard
    header("Location: {$role}/index.php");
    exit;
} else {
    // If no user found, create one
    $email = "{$role}@gmail.com";
    $password = password_hash($role, PASSWORD_DEFAULT);
    $firstName = ucfirst($role);
    
    $query = "INSERT INTO users (email, password, first_name, middle_name, last_name, role, status) 
              VALUES (?, ?, ?, '', 'User', ?, 'active')";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssss', $email, $password, $firstName, $role);
    
    if ($stmt->execute()) {
        // Get the new user's ID
        $user_id = $db->getLastId();
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name'] = $firstName . ' User';
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['logged_in'] = true;
        
        // Redirect to appropriate dashboard
        header("Location: {$role}/index.php");
        exit;
    } else {
        echo "Failed to create user: " . $db->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 50px;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .btn-group {
            margin-top: 30px;
        }
        .btn {
            margin: 0 10px;
            padding: 10px 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Direct Login Failed</h1>
        <p>If you see this page, something went wrong with the automatic login.</p>
        <p>Try clicking one of these buttons:</p>
        
        <div class="btn-group">
            <a href="direct_login.php?role=admin" class="btn btn-primary">Login as Admin</a>
            <a href="direct_login.php?role=staff" class="btn btn-success">Login as Staff</a>
            <a href="direct_login.php?role=student" class="btn btn-info">Login as Student</a>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Homepage</a>
        </div>
    </div>
</body>
</html> 