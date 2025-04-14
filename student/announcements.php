<?php
session_start();
include '../database.php';
include 'login_check.php';

// Get general announcements
$stmt = $db->prepare("SELECT a.*, u.first_name, u.last_name 
                     FROM announcements a 
                     JOIN users u ON a.created_by = u.id 
                     ORDER BY a.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

// Get return service announcements
$stmt = $db->prepare("SELECT rsa.*, u.first_name, u.last_name 
                     FROM return_service_announcements rsa 
                     JOIN users u ON rsa.created_by = u.id 
                     ORDER BY rsa.created_at DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
$rs_announcements = [];
while ($row = $result->fetch_assoc()) {
    $rs_announcements[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .announcements-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .announcement-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .announcement-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .announcement-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .announcement-date {
            color: #6c757d;
            font-size: 14px;
        }
        
        .announcement-details {
            margin-bottom: 15px;
        }
        
        .announcement-details p {
            margin-bottom: 8px;
        }
        
        .announcement-author {
            text-align: right;
            font-style: italic;
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .announcement-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .type-general {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .type-return-service {
            background-color: #d4edda;
            color: #155724;
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
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid transparent;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            margin-bottom: -1px;
        }
        
        .tab.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .announcements-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/student_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Announcements</h1>
            </div>
            
            <div class="tabs">
                <div class="tab active" onclick="openTab(event, 'all')">All Announcements</div>
                <div class="tab" onclick="openTab(event, 'general')">General</div>
                <div class="tab" onclick="openTab(event, 'return-service')">Return Service Activities</div>
            </div>
            
            <div id="all" class="tab-content active">
                <div class="announcements-container">
                    <?php if (empty($announcements) && empty($rs_announcements)): ?>
                        <div class="empty-state">
                            <p>No announcements available at this time.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        // Combine and sort all announcements by date
                        $all_announcements = [];
                        
                        foreach ($announcements as $announcement) {
                            $all_announcements[] = [
                                'type' => 'general',
                                'data' => $announcement,
                                'date' => strtotime($announcement['created_at'])
                            ];
                        }
                        
                        foreach ($rs_announcements as $rs_announcement) {
                            $all_announcements[] = [
                                'type' => 'return-service',
                                'data' => $rs_announcement,
                                'date' => strtotime($rs_announcement['created_at'])
                            ];
                        }
                        
                        // Sort by date, newest first
                        usort($all_announcements, function($a, $b) {
                            return $b['date'] - $a['date'];
                        });
                        
                        foreach ($all_announcements as $item):
                            if ($item['type'] === 'general'):
                                $announcement = $item['data'];
                        ?>
                                <div class="announcement-card">
                                    <div class="announcement-header">
                                        <div>
                                            <span class="announcement-type type-general">General</span>
                                            <span class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></span>
                                        </div>
                                        <div class="announcement-date"><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></div>
                                    </div>
                                    <div class="announcement-details">
                                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                    </div>
                                    <div class="announcement-author">
                                        Posted by: <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?>
                                    </div>
                                </div>
                            <?php else: 
                                $rs_announcement = $item['data'];
                            ?>
                                <div class="announcement-card">
                                    <div class="announcement-header">
                                        <div>
                                            <span class="announcement-type type-return-service">Return Service</span>
                                            <span class="announcement-title"><?php echo htmlspecialchars($rs_announcement['title']); ?></span>
                                        </div>
                                        <div class="announcement-date"><?php echo date('F j, Y', strtotime($rs_announcement['created_at'])); ?></div>
                                    </div>
                                    <div class="announcement-details">
                                        <p><strong>Date:</strong> <?php echo htmlspecialchars($rs_announcement['date']); ?></p>
                                        <p><strong>Location:</strong> <?php echo htmlspecialchars($rs_announcement['location']); ?></p>
                                        <p><strong>Hours:</strong> <?php echo htmlspecialchars($rs_announcement['hours']); ?></p>
                                        <p><strong>Available Slots:</strong> <?php echo htmlspecialchars($rs_announcement['slots']); ?></p>
                                        <p><strong>Requirements:</strong> <?php echo nl2br(htmlspecialchars($rs_announcement['requirements'])); ?></p>
                                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($rs_announcement['description'])); ?></p>
                                    </div>
                                    <div class="announcement-author">
                                        Posted by: <?php echo htmlspecialchars($rs_announcement['first_name'] . ' ' . $rs_announcement['last_name']); ?>
                                    </div>
                                    <div style="text-align: right; margin-top: 15px;">
                                        <a href="submission_form.php?tab=rs_submission" class="btn btn-primary">Apply</a>
                                    </div>
                                </div>
                            <?php endif; 
                        endforeach; 
                        ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="general" class="tab-content">
                <div class="announcements-container">
                    <?php if (empty($announcements)): ?>
                        <div class="empty-state">
                            <p>No general announcements available at this time.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-card">
                                <div class="announcement-header">
                                    <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                    <div class="announcement-date"><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></div>
                                </div>
                                <div class="announcement-details">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </div>
                                <div class="announcement-author">
                                    Posted by: <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="return-service" class="tab-content">
                <div class="announcements-container">
                    <?php if (empty($rs_announcements)): ?>
                        <div class="empty-state">
                            <p>No return service activities available at this time.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($rs_announcements as $rs_announcement): ?>
                            <div class="announcement-card">
                                <div class="announcement-header">
                                    <div class="announcement-title"><?php echo htmlspecialchars($rs_announcement['title']); ?></div>
                                    <div class="announcement-date"><?php echo date('F j, Y', strtotime($rs_announcement['created_at'])); ?></div>
                                </div>
                                <div class="announcement-details">
                                    <p><strong>Date:</strong> <?php echo htmlspecialchars($rs_announcement['date']); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($rs_announcement['location']); ?></p>
                                    <p><strong>Hours:</strong> <?php echo htmlspecialchars($rs_announcement['hours']); ?></p>
                                    <p><strong>Available Slots:</strong> <?php echo htmlspecialchars($rs_announcement['slots']); ?></p>
                                    <p><strong>Requirements:</strong> <?php echo nl2br(htmlspecialchars($rs_announcement['requirements'])); ?></p>
                                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($rs_announcement['description'])); ?></p>
                                </div>
                                <div class="announcement-author">
                                    Posted by: <?php echo htmlspecialchars($rs_announcement['first_name'] . ' ' . $rs_announcement['last_name']); ?>
                                </div>
                                <div style="text-align: right; margin-top: 15px;">
                                    <a href="submission_form.php?tab=rs_submission" class="btn btn-primary">Apply</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            // Hide all tab content
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Remove active class from all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the selected tab content and mark the button as active
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>