<?php
// Script to update all user emails to use @gmail.com domain
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

$success = false;
$error = '';
$updated_count = 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_emails'])) {
    try {
        $conn = getConnection();
        
        // Get all users
        $query = "SELECT id, email FROM users";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception("Error fetching users: " . mysqli_error($conn));
        }
        
        // Update each email to use @gmail.com
        while ($user = mysqli_fetch_assoc($result)) {
            $current_email = $user['email'];
            $email_parts = explode('@', $current_email);
            $username = $email_parts[0];
            
            // Only update if not already @gmail.com
            if (count($email_parts) > 1 && strtolower($email_parts[1]) !== 'gmail.com') {
                $new_email = $username . '@gmail.com';
                
                $update_query = "UPDATE users SET email = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, 'si', $new_email, $user['id']);
                $update_result = mysqli_stmt_execute($stmt);
                
                if ($update_result) {
                    $updated_count++;
                } else {
                    throw new Exception("Error updating email for user ID {$user['id']}: " . mysqli_error($conn));
                }
            }
        }
        
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User Emails - Scholar</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Update User Emails to @gmail.com</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Successfully updated <?php echo $updated_count; ?> email(s) to use @gmail.com domain.
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> Error: <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-4">This utility will update all user email addresses in the system to use the @gmail.com domain. The username portion of each email will remain unchanged.</p>
                        
                        <form method="post" action="">
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_emails" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Update All Emails to @gmail.com
                                </button>
                                <a href="admin/index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>