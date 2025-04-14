<?php
session_start();
require_once 'includes/config/database.php';

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

$message = '';
$login_success = false;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simplest possible check
    $db = Database::getInstance();
    
    // Debug info
    $debug_info = [];
    $debug_info['email'] = $email;
    $debug_info['password'] = $password;
    
    // Very basic query - no password check
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $debug_info['user_found'] = true;
        $debug_info['user_role'] = $user['role'];
        $debug_info['user_status'] = $user['status'];
        
        // Super simple authentication - ANY password works
        if ($user['email'] === $email) {
            // Use "password" for all accounts
            if ($password === 'password') {
                
                $login_success = true;
                
                // Set necessary session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                $message = "Login successful! Redirecting...";
                $debug_info['login_success'] = true;
                
                // Redirect after short delay
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '{$user['role']}/index.php';
                    }, 1500);
                </script>";
            } else {
                $message = "Simple password check failed. Use 'password' as password.";
                $debug_info['password_match'] = false;
            }
        } else {
            $message = "Email doesn't match exactly.";
        }
    } else {
        $message = "User with email '$email' not found.";
        $debug_info['user_found'] = false;
        
        // Attempt to show available users
        $query = "SELECT email, role FROM users LIMIT 5";
        $result = $db->query($query);
        $debug_info['available_users'] = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $debug_info['available_users'][] = $row;
            }
        }
    }
}

// Force create admin@gmail.com if it doesn't exist
$db = Database::getInstance();
$email = "admin@gmail.com";
$check_query = "SELECT id FROM users WHERE email = ?";
$stmt = $db->prepare($check_query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Create a new admin user
    $password = password_hash('admin', PASSWORD_DEFAULT);
    $query = "INSERT INTO users (email, password, first_name, middle_name, last_name, role, status) 
              VALUES (?, ?, 'Admin', '', 'User', 'admin', 'active')";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $email, $password);
    $stmt->execute();
    $admin_created = true;
} else {
    $admin_created = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login - Scholar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 600px;
            padding: 2rem;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .debug-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .available-users {
            margin-top: 10px;
            padding: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">Super Simple Login</h2>
        <p class="text-center mb-4">This page bypasses most security checks for easy login</p>
        
        <?php if ($admin_created): ?>
            <div class="alert alert-success">
                <strong>Admin user created!</strong> You can now log in with admin@gmail.com / admin
            </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $login_success ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email (try admin@gmail.com)</label>
                <input type="email" class="form-control" id="email" name="email" required value="admin@gmail.com">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password (try "password")</label>
                <input type="password" class="form-control" id="password" name="password" required value="password">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
        
        <?php if (isset($debug_info)): ?>
            <div class="debug-info">
                <h5>Debug Information (what happened):</h5>
                <pre><?php print_r($debug_info); ?></pre>
                
                <?php if (isset($debug_info['available_users']) && !empty($debug_info['available_users'])): ?>
                    <div class="available-users">
                        <h6>Available Users:</h6>
                        <ul>
                            <?php foreach ($debug_info['available_users'] as $user): ?>
                                <li><?php echo $user['email']; ?> (<?php echo $user['role']; ?>) - Use "password" as password</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-3 text-center">
            <a href="login.php">Back to Normal Login</a> | 
            <a href="create_test_users.php">Create Test Users</a> | 
            <a href="index.php">Homepage</a>
        </div>
    </div>
</body>
</html> 