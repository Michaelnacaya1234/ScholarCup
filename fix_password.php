<?php
/**
 * Fix Password Script
 * This script updates user passwords directly in the database with proper hashes
 */

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fixing User Passwords</h1>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'scholar_db';

try {
    // Connect to database
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Admin password: admin123
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Staff password: staff123
    $staff_hash = password_hash('staff123', PASSWORD_DEFAULT);
    
    // Student password: student123
    $student_hash = password_hash('student123', PASSWORD_DEFAULT);
    
    // Update admin password
    $sql = "UPDATE users SET password = ? WHERE email = 'admin@gmail.com'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $admin_hash);
    if ($stmt->execute()) {
        echo "<p>Admin password has been updated.</p>";
    } else {
        echo "<p>Failed to update admin password: " . $conn->error . "</p>";
    }
    
    // Update staff password
    $sql = "UPDATE users SET password = ? WHERE email = 'staff@gmail.com'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $staff_hash);
    if ($stmt->execute()) {
        echo "<p>Staff password has been updated.</p>";
    } else {
        echo "<p>Failed to update staff password: " . $conn->error . "</p>";
    }
    
    // Update student password
    $sql = "UPDATE users SET password = ? WHERE email = 'student@gmail.com'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $student_hash);
    if ($stmt->execute()) {
        echo "<p>Student password has been updated.</p>";
    } else {
        echo "<p>Failed to update student password: " . $conn->error . "</p>";
    }
    
    // Verify the passwords were properly updated
    $sql = "SELECT id, email, role FROM users";
    $result = $conn->query($sql);
    
    echo "<h2>User Accounts</h2>";
    echo "<p>The following accounts can now be used to log in:</p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>" . htmlspecialchars($row['role']) . ":</strong> " . htmlspecialchars($row['email']) . "</li>";
    }
    echo "</ul>";
    
    echo "<p>Passwords have been updated. You can now log in with:</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@gmail.com / admin123</li>";
    echo "<li><strong>Staff:</strong> staff@gmail.com / staff123</li>";
    echo "<li><strong>Student:</strong> student@gmail.com / student123</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 