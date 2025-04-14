<?php
// Script to create test users with simple passwords
require_once 'includes/config/database.php';

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get database connection
$db = Database::getInstance();

// Define test users
$test_users = [
    [
        'email' => 'admin@gmail.com',
        'password' => 'admin',
        'first_name' => 'Admin',
        'middle_name' => '',
        'last_name' => 'User',
        'role' => 'admin'
    ],
    [
        'email' => 'staff@gmail.com',
        'password' => 'staff',
        'first_name' => 'Staff',
        'middle_name' => '',
        'last_name' => 'User',
        'role' => 'staff'
    ],
    [
        'email' => 'student@gmail.com',
        'password' => 'student',
        'first_name' => 'Student',
        'middle_name' => '',
        'last_name' => 'User',
        'role' => 'student'
    ],
    [
        'email' => 'admin@example.com',
        'password' => 'admin',
        'first_name' => 'Admin',
        'middle_name' => '',
        'last_name' => 'Example',
        'role' => 'admin'
    ],
    [
        'email' => 'staff@example.com',
        'password' => 'staff',
        'first_name' => 'Staff',
        'middle_name' => '',
        'last_name' => 'Example',
        'role' => 'staff'
    ],
    [
        'email' => 'student@example.com',
        'password' => 'student',
        'first_name' => 'Student',
        'middle_name' => '',
        'last_name' => 'Example',
        'role' => 'student'
    ]
];

// Create users
$results = [];
foreach ($test_users as $user) {
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $stmt = $db->prepare($check_query);
    $stmt->bind_param('s', $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists, update password
        $row = $result->fetch_assoc();
        $user_id = $row['id'];
        
        // Simple plain password for easy login
        $plain_password = $user['password'];
        // Also hash it for normal login method
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param('si', $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $results[] = [
                'email' => $user['email'],
                'password' => $plain_password,
                'status' => 'updated',
                'message' => 'Password updated'
            ];
        } else {
            $results[] = [
                'email' => $user['email'],
                'status' => 'error',
                'message' => 'Failed to update: ' . $db->error
            ];
        }
    } else {
        // Create new user
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (email, password, first_name, middle_name, last_name, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $db->prepare($insert_query);
        $stmt->bind_param('ssssss', 
            $user['email'], 
            $hashed_password, 
            $user['first_name'], 
            $user['middle_name'], 
            $user['last_name'], 
            $user['role']
        );
        
        if ($stmt->execute()) {
            $results[] = [
                'email' => $user['email'],
                'password' => $user['password'],
                'status' => 'created',
                'message' => 'User created successfully'
            ];
        } else {
            $results[] = [
                'email' => $user['email'],
                'status' => 'error',
                'message' => 'Failed to create: ' . $db->error
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Test Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }
        .header {
            background-color: #1E3A8A;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .user-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .user-card.created {
            border-left: 4px solid #28a745;
        }
        .user-card.updated {
            border-left: 4px solid #17a2b8;
        }
        .user-card.error {
            border-left: 4px solid #dc3545;
        }
        .credentials {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Test Users Created</h1>
        </div>
        
        <div class="mb-4">
            <p>The following test users have been created or updated with simple passwords for easy login testing.</p>
            <p>You can now log in using any of these accounts with their simple passwords.</p>
        </div>
        
        <div class="user-results">
            <?php foreach ($results as $result): ?>
                <div class="user-card <?php echo $result['status']; ?>">
                    <h5><?php echo htmlspecialchars($result['email']); ?></h5>
                    <p class="mb-0"><strong>Status:</strong> <?php echo ucfirst($result['status']); ?></p>
                    <p class="mb-0"><strong>Message:</strong> <?php echo htmlspecialchars($result['message']); ?></p>
                    
                    <?php if (isset($result['password'])): ?>
                        <div class="credentials">
                            <strong>Email:</strong> <?php echo htmlspecialchars($result['email']); ?><br>
                            <strong>Password:</strong> <?php echo htmlspecialchars($result['password']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary">Go to Login Page</a>
            <a href="index.php" class="btn btn-secondary">Return to Homepage</a>
        </div>
    </div>
</body>
</html> 