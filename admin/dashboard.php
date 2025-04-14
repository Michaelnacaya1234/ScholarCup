<?php
session_start();
require_once '../includes/config/database.php';
require_once '../includes/Auth.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$full_name = $first_name . ' ' . $last_name;

// Initialize database connection
$db = Database::getInstance();
$auth = new Auth($db);

// Get student concerns count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM student_concerns WHERE status = 'open'");
$stmt->execute();
$result = $stmt->get_result();
$concerns_count = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CSO Scholar Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }

        .dashboard-card-icon i {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Include Admin Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo $full_name; ?></h1>
                <div class="user-actions">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if ($concerns_count > 0): ?>
                        <span class="notification-badge"><?php echo $concerns_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> logout
                    </a>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Students Scholar Status Card -->
                <a href="students_status.php" class="dashboard-card students-status-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="dashboard-card-title">Students Status</div>
                </a>

                <!-- Announcement Card -->
                <a href="announcements.php" class="dashboard-card announcement-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="dashboard-card-title">Announcement</div>
                </a>

                <!-- Return Service Activity Card -->
                <a href="return_service.php" class="dashboard-card return-service-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="dashboard-card-title">Return Service Activity</div>
                </a>

                <!-- Archive Card -->
                <a href="archive.php" class="dashboard-card archive-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div class="dashboard-card-title">Archive</div>
                </a>

                <!-- Students Scholar Submission List Card -->
                <a href="students_submission.php" class="dashboard-card students-submission-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="dashboard-card-title">Students Scholar Submission List</div>
                </a>

                <!-- Allowance Releasing Schedule Card -->
                <a href="allowance_schedule.php" class="dashboard-card allowance-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="dashboard-card-title">Allowance Releasing Schedule</div>
                </a>

                <!-- Staff Account Card -->
                <a href="staff_accounts.php" class="dashboard-card staff-account-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="dashboard-card-title">Staff Account</div>
                </a>

                <!-- Student Scholar Concern Card -->
                <a href="student_concerns.php" class="dashboard-card student-concern-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="dashboard-card-title">Student Scholar Concern</div>
                </a>

                <!-- Registration Card -->
                <a href="registration.php" class="dashboard-card registration-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="dashboard-card-title">Registration</div>
                </a>

                <!-- Student Account Card -->
                <a href="student_accounts.php" class="dashboard-card student-account-card">
                    <div class="dashboard-card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="dashboard-card-title">Student Account</div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for notifications and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle notification dropdown
            const notificationIcon = document.querySelector('.notification-icon');
            
            if (notificationIcon) {
                notificationIcon.addEventListener('click', function() {
                    // Redirect to student concerns page when notification is clicked
                    window.location.href = 'student_concerns.php';
                });
            }
        });
    </script>
</body>
</html>