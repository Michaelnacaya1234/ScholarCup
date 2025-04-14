<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$error_message = '';
$announcement = null;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($id)) {
    $error_message = "Invalid announcement ID.";
} else {
    try {
        if ($type === 'general') {
            // Fetch general announcement
            $stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $announcement = $result->fetch_assoc();
            
            if (!$announcement) {
                $error_message = "Announcement not found.";
            }
        } elseif ($type === 'return_service') {
            // Fetch return service announcement
            $stmt = $db->prepare("SELECT * FROM return_service_announcements WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $announcement = $result->fetch_assoc();
            
            if (!$announcement) {
                $error_message = "Return service activity not found.";
            }
        } else {
            $error_message = "Invalid announcement type.";
        }
    } catch (Exception $e) {
        $error_message = "Error retrieving announcement: " . $e->getMessage();
        error_log("Error in view_announcement.php: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $announcement ? htmlspecialchars($announcement['title']) : 'Announcement Details'; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .announcement-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 20px auto;
            max-width: 800px;
        }
        
        .announcement-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .announcement-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .announcement-date {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .announcement-content {
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .announcement-meta {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .announcement-meta p {
            margin: 5px 0;
        }
        
        .back-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .back-button:hover {
            background-color: #0069d9;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content" style="margin-left: 0; width: 100%;">
            <?php if ($error_message): ?>
                <div class="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <a href="announcements_and_return_service.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Announcements</a>
            <?php elseif ($announcement): ?>
                <div class="announcement-container">
                    <div class="announcement-header">
                        <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                        <div class="announcement-date">
                            Posted on: <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($type === 'return_service'): ?>
                        <div class="announcement-meta">
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($announcement['date']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($announcement['location']); ?></p>
                            <p><strong>Hours:</strong> <?php echo htmlspecialchars($announcement['hours']); ?></p>
                            <p><strong>Available Slots:</strong> <?php echo htmlspecialchars($announcement['slots']); ?></p>
                            <p><strong>Requirements:</strong></p>
                            <div><?php echo nl2br(htmlspecialchars($announcement['requirements'])); ?></div>
                        </div>
                        
                        <div class="announcement-content">
                            <h3>Description:</h3>
                            <?php echo nl2br(htmlspecialchars($announcement['description'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="announcements_and_return_service.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Announcements</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>