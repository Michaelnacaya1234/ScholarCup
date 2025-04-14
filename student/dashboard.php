<?php
session_start();
require_once '../includes/config/database.php';
require_once '../includes/Auth.php';

// Check if user is logged in and has student role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
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

// Get student profile data
$stmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student_profile = $result->fetch_assoc();

// Get announcements count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM announcements");
$stmt->execute();
$result = $stmt->get_result();
$announcements_count = $result->fetch_assoc()['count'];

// Get inbox messages count (placeholder for actual implementation)
$inbox_count = 0; // This would be replaced with actual count from database

// Get various counts and statuses
$stmt = $db->prepare("SELECT 
    SUM(CASE WHEN status = 'approved' THEN hours ELSE 0 END) as total_rs_hours,
    COUNT(DISTINCT CASE WHEN status = 'pending' THEN id END) as pending_activities,
    COUNT(DISTINCT CASE WHEN status = 'approved' THEN id END) as completed_activities
    FROM return_service_activities 
    WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$rs_stats = $stmt->get_result()->fetch_assoc();

// Get latest grades submission
$stmt = $db->prepare("SELECT * FROM student_grades WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$latest_grades = $stmt->get_result()->fetch_assoc();

// Get upcoming allowance schedule
$stmt = $db->prepare("SELECT * FROM allowance_releasing_schedule WHERE schedule_date >= CURDATE() ORDER BY schedule_date ASC LIMIT 1");
$stmt->execute();
$next_allowance = $stmt->get_result()->fetch_assoc();

// Get latest announcements
$stmt = $db->prepare("SELECT * FROM announcements ORDER BY updated_at DESC LIMIT 5");
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if submissions table exists before querying it
$table_check = $db->query("SHOW TABLES LIKE 'submissions'");
if ($table_check->num_rows > 0) {
    // Get recent submissions 
    $query = "SELECT * FROM submissions WHERE student_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Table doesn't exist, set submissions to empty array
    $submissions = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - CSO Scholar Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            background: rgba(0, 123, 255, 0.1); /* Changed to match admin */
            color: #007bff; /* Changed to match admin */
        }
        
        .stat-icon i {
            font-size: 24px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #0056b3;
            margin: 10px 0;
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .stat-subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .announcements-section, .schedule-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .announcement-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .announcement-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-good { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-danger { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php include '../includes/header.php'; ?>

            <div class="content-wrapper">
                <div class="welcome-section">
                    <h1>Welcome, <?php echo htmlspecialchars($full_name); ?>!</h1>
                    <p>Here's your scholarship status overview</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-title">Return Service Hours</div>
                        <div class="stat-value"><?php echo $rs_stats['total_rs_hours'] ?? 0; ?> hours</div>
                        <div class="stat-subtitle">Total Completed Hours</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-title">Pending Activities</div>
                        <div class="stat-value"><?php echo $rs_stats['pending_activities'] ?? 0; ?></div>
                        <div class="stat-subtitle">Awaiting Approval</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-title">Completed Activities</div>
                        <div class="stat-value"><?php echo $rs_stats['completed_activities'] ?? 0; ?></div>
                        <div class="stat-subtitle">Approved Activities</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-title">Academic Status</div>
                        <div class="stat-value">
                            <?php 
                            if ($latest_grades) {
                                echo $latest_grades['status'] == 'approved' ? 'Good Standing' : 'Pending Review';
                            } else {
                                echo 'No Data';
                            }
                            ?>
                        </div>
                        <div class="stat-subtitle">Current Academic Period</div>
                    </div>
                </div>

                <div class="dashboard-content">
                    <div class="announcements-section">
                        <h2><i class="fas fa-bullhorn"></i> Latest Announcements</h2>
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item">
                                    <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?></p>
                                    <span class="announcement-date">
                                        <i class="far fa-calendar-alt"></i> 
                                        <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <a href="announcements.php" class="btn btn-primary mt-3">View All Announcements</a>
                        <?php else: ?>
                            <p>No announcements at this time.</p>
                        <?php endif; ?>
                    </div>

                    <div class="schedule-section">
                        <h2><i class="fas fa-calendar"></i> Upcoming Schedule</h2>
                        <?php if ($next_allowance): ?>
                            <div class="schedule-item">
                                <h3>Next Allowance Release</h3>
                                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($next_allowance['schedule_date'])); ?></p>
                                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($next_allowance['schedule_time_start'])); ?></p>
                                <p><strong>Venue:</strong> <?php echo htmlspecialchars($next_allowance['venue']); ?></p>
                            </div>
                        <?php else: ?>
                            <p>No upcoming allowance schedule.</p>
                        <?php endif; ?>
                        <a href="allowance_schedule.php" class="btn btn-secondary mt-3">View Complete Schedule</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for notifications and other interactive elements can be added here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Toggle notification dropdown
            const notificationIcons = document.querySelectorAll('.notification-icon');
            
            notificationIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    // Toggle notification dropdown (would be implemented in a real application)
                    console.log('Notification icon clicked');
                });
            });
        });
    </script>
</body>
</html>