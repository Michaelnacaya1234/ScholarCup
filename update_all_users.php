<?php
// Script to update all user emails to @gmail.com and set all passwords to "password"
session_start();
require_once 'includes/config/database.php';

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get database connection
$db = Database::getInstance();

// Generate password hash for "password"
$password_hash = password_hash('password', PASSWORD_DEFAULT);

// Arrays to store results
$updated_users = [];
$errors = [];

// Step 1: Get all users
$query = "SELECT id, email, role, first_name, last_name FROM users";
$result = $db->query($query);

if ($result) {
    // Step 2: Update each user
    while ($user = $result->fetch_assoc()) {
        $user_id = $user['id'];
        $role = $user['role'];
        $current_email = $user['email'];
        
        // Create new email in the format role@gmail.com
        $new_email = $role . '@gmail.com';
        
        // Update email and password
        $update_query = "UPDATE users SET email = ?, password = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("ssi", $new_email, $password_hash, $user_id);
        
        if ($stmt->execute()) {
            $updated_users[] = [
                'id' => $user_id,
                'old_email' => $current_email,
                'new_email' => $new_email,
                'role' => $role,
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'password' => 'password' // Showing the plaintext password in our results
            ];
        } else {
            $errors[] = "Failed to update user ID {$user_id}: " . $db->error;
        }
    }
} else {
    $errors[] = "Failed to retrieve users: " . $db->error;
}

// Ensure admin@gmail.com exists
$check_admin = "SELECT id FROM users WHERE email = 'admin@gmail.com'";
$admin_result = $db->query($check_admin);

if ($admin_result->num_rows == 0) {
    // Create admin user if it doesn't exist
    $admin_query = "INSERT INTO users (email, password, first_name, middle_name, last_name, role, status) 
                    VALUES ('admin@gmail.com', ?, 'Admin', '', 'User', 'admin', 'active')";
    $stmt = $db->prepare($admin_query);
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        $updated_users[] = [
            'id' => $db->insert_id,
            'old_email' => 'N/A (new user)',
            'new_email' => 'admin@gmail.com',
            'role' => 'admin',
            'name' => 'Admin User',
            'password' => 'password'
        ];
    } else {
        $errors[] = "Failed to create admin user: " . $db->error;
    }
}

// Do the same for staff and student
$check_staff = "SELECT id FROM users WHERE email = 'staff@gmail.com'";
$staff_result = $db->query($check_staff);

if ($staff_result->num_rows == 0) {
    $staff_query = "INSERT INTO users (email, password, first_name, middle_name, last_name, role, status) 
                   VALUES ('staff@gmail.com', ?, 'Staff', '', 'User', 'staff', 'active')";
    $stmt = $db->prepare($staff_query);
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        $updated_users[] = [
            'id' => $db->insert_id,
            'old_email' => 'N/A (new user)',
            'new_email' => 'staff@gmail.com',
            'role' => 'staff',
            'name' => 'Staff User',
            'password' => 'password'
        ];
    } else {
        $errors[] = "Failed to create staff user: " . $db->error;
    }
}

$check_student = "SELECT id FROM users WHERE email = 'student@gmail.com'";
$student_result = $db->query($check_student);

if ($student_result->num_rows == 0) {
    $student_query = "INSERT INTO users (email, password, first_name, middle_name, last_name, role, status) 
                     VALUES ('student@gmail.com', ?, 'Student', '', 'User', 'student', 'active')";
    $stmt = $db->prepare($student_query);
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        $updated_users[] = [
            'id' => $db->insert_id,
            'old_email' => 'N/A (new user)',
            'new_email' => 'student@gmail.com',
            'role' => 'student',
            'name' => 'Student User',
            'password' => 'password'
        ];
    } else {
        $errors[] = "Failed to create student user: " . $db->error;
    }
}

// Update direct_login.php to use password instead of role-based passwords
$file_path = 'simple_login.php';
if (file_exists($file_path)) {
    $file_content = file_get_contents($file_path);
    
    // Replace the role-based password checks with the simple 'password' check
    $file_content = preg_replace(
        '/if \(\(\$user\[\'role\'\] === \'admin\' && \$password === \'admin\'\) \|\|.*?\(\$user\[\'role\'\] === \'student\' && \$password === \'student\'\)\) {/s',
        'if ($password === \'password\') {',
        $file_content
    );
    
    file_put_contents($file_path, $file_content);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts Updated</title>
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
        .user-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-list {
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
        <h1 class="mb-4">User Accounts Updated</h1>
        
        <div class="alert-success">
            <strong>All user accounts have been updated!</strong> All passwords have been set to "password" and emails updated to @gmail.com format.
        </div>
        
        <?php if (count($errors) > 0): ?>
            <div class="error-list">
                <h3>Errors Encountered:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <h3>Updated User Accounts:</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Old Email</th>
                    <th>New Email</th>
                    <th>Role</th>
                    <th>Password</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($updated_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['old_email']); ?></td>
                        <td><strong><?php echo htmlspecialchars($user['new_email']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><strong><?php echo htmlspecialchars($user['password']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="login-info">
            <h4>Login Information</h4>
            <p>You can now log in using any of the following accounts:</p>
            <ul>
                <li><strong>Admin:</strong> admin@gmail.com / password</li>
                <li><strong>Staff:</strong> staff@gmail.com / password</li>
                <li><strong>Student:</strong> student@gmail.com / password</li>
            </ul>
            <p><a href="simple_login.php" class="btn btn-primary">Go to Login Page</a></p>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 