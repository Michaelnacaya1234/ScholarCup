<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'announce') {
            // Process return service activity announcement
            $title = trim($_POST['title'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $text = trim($_POST['text'] ?? '');
            $location = trim($_POST['location'] ?? ''); // Added location field
            $hours = intval($_POST['hours'] ?? 0); // Added hours field
            $slots = intval($_POST['slots'] ?? 0); // Added slots field
            $requirements = trim($_POST['requirements'] ?? ''); // Added requirements field
            
            if (empty($title) || empty($date) || empty($text)) {
                $error_message = "Please fill in all required fields.";
            } else {
                try {
                    // Insert into return_service_announcements table
                    $stmt = $db->prepare("INSERT INTO return_service_announcements (title, date, description, location, hours, slots, requirements, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssssiiis", $title, $date, $text, $location, $hours, $slots, $requirements, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        // Also add to general announcements table
                        $announcement_content = "Return Service Activity: $title\n\nDate: $date\n\nLocation: $location\n\nHours: $hours\n\nSlots Available: $slots\n\nRequirements: $requirements\n\n$text";
                        
                        $stmt = $db->prepare("INSERT INTO announcements (title, content, created_by, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("ssi", $title, $announcement_content, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        $success_message = "Return service activity has been announced successfully.";
                    } else {
                        $error_message = "Failed to announce return service activity. Please try again.";
                    }
                } catch (Exception $e) {
                    $error_message = "An error occurred: " . $e->getMessage();
                    error_log("Error in return service announcement: " . $e->getMessage());
                }
            }
        } elseif ($_POST['action'] === 'sms') {
            // SMS functionality would go here if implemented
            $error_message = "SMS functionality is not implemented yet.";
        }
    }
}

// Get existing return service announcements
$announcements = [];
try {
    // Check if the table exists first
    $table_check = $db->query("SHOW TABLES LIKE 'return_service_announcements'");
    
    if ($table_check->num_rows > 0) {
        $stmt = $db->prepare("SELECT * FROM return_service_announcements ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    } else {
        // Table doesn't exist, set error message
        $error_message = "The return_service_announcements table does not exist in the database. Please run the database update script to create it.";
    }
} catch (Exception $e) {
    $error_message = "Error retrieving announcements: " . $e->getMessage();
    error_log("Error in return service page: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements & Return Service - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .announcement-list {
            margin-top: 30px;
        }
        
        .announcement-item {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .announcement-title {
            font-size: 18px;
            font-weight: bold;
        }
        
        .announcement-date {
            color: #6c757d;
            font-size: 14px;
        }
        
        .announcement-content {
            margin-bottom: 15px;
        }
        
        .announcement-footer {
            display: flex;
            justify-content: flex-end;
        }
        
        .archive-link {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .archive-link a {
            color: #6c757d;
            text-decoration: none;
        }
        
        .archive-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>RETURN SERVICE ACTIVITY</h1>
                <div class="archive-link">
                    <a href="archive.php?type=return_service"><i class="fas fa-archive"></i> Archive</a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                    <?php if (strpos($error_message, "return_service_announcements table does not exist") !== false): ?>
                        <p><a href="create_missing_tables.php" class="btn btn-primary">Create Missing Tables</a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="announce">
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hours">Hours</label>
                        <input type="number" id="hours" name="hours" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slots">Available Slots</label>
                        <input type="number" id="slots" name="slots" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements</label>
                        <textarea id="requirements" name="requirements" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="text">Description</label>
                        <textarea id="text" name="text" required></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">Announcement</button>
                        <button type="button" class="btn btn-secondary" onclick="document.querySelector('input[name=\'action\']').value='sms'; this.form.submit();">SMS</button>
                    </div>
                </form>
            </div>
            
            <div class="announcement-list">
                <h2>Recent Return Service Activity Announcements</h2>
                
                <?php if (empty($announcements)): ?>
                    <p>No return service activities have been announced yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item">
                            <div class="announcement-header">
                                <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                <div class="announcement-date"><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></div>
                            </div>
                            <div class="announcement-content">
                                <p><strong>Date:</strong> <?php echo htmlspecialchars($announcement['date']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($announcement['location']); ?></p>
                                <p><strong>Hours:</strong> <?php echo htmlspecialchars($announcement['hours']); ?></p>
                                <p><strong>Slots:</strong> <?php echo htmlspecialchars($announcement['slots']); ?></p>
                                <p><strong>Requirements:</strong> <?php echo nl2br(htmlspecialchars($announcement['requirements'])); ?></p>
                                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($announcement['description'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>