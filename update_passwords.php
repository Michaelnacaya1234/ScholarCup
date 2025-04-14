<?php
// Script to update all user passwords to "password"
require_once 'includes/config/database.php';

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get database connection
$db = Database::getInstance();

// Generate password hash for "password"
$password_hash = password_hash('password', PASSWORD_DEFAULT);

// Update all users
$update_query = "UPDATE users SET password = ?";
$stmt = $db->prepare($update_query);
$stmt->bind_param("s", $password_hash);

$success = $stmt->execute();
$affected_rows = $stmt->affected_rows;

// Get all users for display
$users = [];
$query = "SELECT id, first_name, last_name, email, role FROM users ORDER BY role, id";
$result = $db->query($query);

if ($result) {
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwords Updated - Scholar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .login-info {
            margin-top: 30px;
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Password Reset</h1>
        
        <?php if ($success): ?>
            <div class="alert-success">
                <strong>Success!</strong> All passwords have been updated to "password". 
                <?php echo $affected_rows; ?> user account(s) were updated.
            </div>
        <?php else: ?>
            <div class="alert-danger">
                <strong>Error!</strong> Failed to update passwords: <?php echo $db->error; ?>
            </div>
        <?php endif; ?>
        
        <h3>User Accounts:</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Password</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><strong>password</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="login-info">
            <h4>Login Information</h4>
            <p>You can now log in using any of the accounts listed above with the password: <strong>"password"</strong></p>
            <p><a href="simple_login.php" class="btn btn-primary">Go to Login Page</a></p>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html> 