<?php
session_start();
include '../database.php';
include 'login_check.php';

// Get return service announcements
$stmt = $db->prepare("SELECT rsa.*, u.first_name, u.last_name 
                     FROM return_service_announcements rsa 
                     JOIN users u ON rsa.created_by = u.id 
                     ORDER BY rsa.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

// Get student's return service activities
$stmt = $db->prepare("SELECT * FROM return_service_activities WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

// Calculate total hours
$stmt = $db->prepare("SELECT SUM(hours) as total_hours FROM return_service_activities WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$total_hours = $result->fetch_assoc()['total_hours'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Service Activities - Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .activities-container {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }
        
        .activities-list, .announcements-list {
            flex: 1;
        }
        
        .activity-card, .announcement-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .activity-header, .announcement-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .activity-title, .announcement-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .activity-date, .announcement-date {
            color: #6c757d;
            font-size: 14px;
        }
        
        .activity-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .activity-details, .announcement-details {
            margin-bottom: 15px;
        }
        
        .activity-details p, .announcement-details p {
            margin-bottom: 8px;
        }
        
        .activity-actions {
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .section-title {
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .activities-container {
                flex-direction: column;
            }
            
            .dashboard-stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Return Service Activities</h1>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-label">Total Approved Hours</div>
                    <div class="stat-value"><?php echo $total_hours; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Pending Activities</div>
                    <div class="stat-value"><?php echo count(array_filter($activities, function($a) { return $a['status'] === 'pending'; })); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Available Opportunities</div>
                    <div class="stat-value"><?php echo count($announcements); ?></div>
                </div>
            </div>
            
            <div class="activities-container">
                <div class="announcements-list">
                    <h2 class="section-title">Available Return Service Activities</h2>
                    
                    <?php if (empty($announcements)): ?>
                        <div class="empty-state">
                            <p>No return service activities are currently available.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-card">
                                <div class="announcement-header">
                                    <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                    <div class="announcement-date"><?php echo date('F j, Y', strtotime($announcement['date'])); ?></div>
                                </div>
                                <div class="announcement-details">
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($announcement['location']); ?></p>
                                    <p><strong>Hours:</strong> <?php echo htmlspecialchars($announcement['hours']); ?></p>
                                    <p><strong>Available Slots:</strong> <?php echo htmlspecialchars($announcement['slots']); ?></p>
                                    <p><strong>Requirements:</strong> <?php echo nl2br(htmlspecialchars($announcement['requirements'])); ?></p>
                                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($announcement['description'])); ?></p>
                                    <p><strong>Posted by:</strong> <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?></p>
                                </div>
                                <div class="activity-actions">
                                    <a href="submission_form.php?tab=rs_submission" class="btn btn-primary">Apply</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="activities-list">
                    <h2 class="section-title">My Return Service Activities</h2>
                    
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <p>You haven't submitted any return service activities yet.</p>
                            <a href="submission_form.php?tab=rs_submission" class="btn btn-primary">Submit Activity</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-card">
                                <div class="activity-header">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div>
                                        <span class="activity-status status-<?php echo $activity['status']; ?>">
                                            <?php echo ucfirst($activity['status']); ?>
                                        </span>
                                        <span class="activity-date"><?php echo date('F j, Y', strtotime($activity['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="activity-details">
                                    <p><strong>Hours:</strong> <?php echo htmlspecialchars($activity['hours']); ?></p>
                                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($activity['description'])); ?></p>
                                    <?php if (!empty($activity['proof_file'])): ?>
                                        <p><strong>Proof:</strong> <a href="../uploads/return_service/<?php echo htmlspecialchars($activity['proof_file']); ?>" target="_blank">View Document</a></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>