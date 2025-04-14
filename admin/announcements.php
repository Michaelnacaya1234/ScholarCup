<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'announce_general') {
            // Process general announcement
            $title = trim($_POST['title'] ?? '');
            $text = trim($_POST['text'] ?? '');
            
            if (empty($title) || empty($text)) {
                $error_message = "Please fill in all required fields.";
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO announcements (title, content, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("ssi", $title, $text, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $success_message = "Announcement has been posted successfully.";
                    } else {
                        $error_message = "Failed to post announcement. Please try again.";
                    }
                } catch (Exception $e) {
                    $error_message = "An error occurred: " . $e->getMessage();
                    error_log("Error in announcement: " . $e->getMessage());
                }
            }
        } elseif ($_POST['action'] === 'announce_return_service') {
            // Process return service activity announcement
            $title = trim($_POST['rs_title'] ?? '');
            $date = trim($_POST['rs_date'] ?? '');
            $text = trim($_POST['rs_text'] ?? '');
            $location = trim($_POST['rs_location'] ?? ''); 
            $hours = intval($_POST['rs_hours'] ?? 0); 
            $slots = intval($_POST['rs_slots'] ?? 0); 
            $requirements = trim($_POST['rs_requirements'] ?? ''); 
            
            if (empty($title) || empty($date) || empty($text)) {
                $error_message = "Please fill in all required fields for return service announcement.";
            } else {
                try {
                    // Insert into return_service_announcements table
                    $stmt = $db->prepare("INSERT INTO return_service_announcements (title, date, description, location, hours, slots, requirements, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssssiiis", $title, $date, $text, $location, $hours, $slots, $requirements, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        // No longer adding to general announcements table to keep them separate
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

// Get existing general announcements (excluding return service activities)
$general_announcements = [];
try {
    // Query that excludes announcements that contain "Return Service Activity:" in the title
    $stmt = $db->prepare("SELECT * FROM announcements WHERE title NOT LIKE 'Return Service Activity:%' AND title NOT LIKE '%Return Service%' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $general_announcements[] = $row;
    }
} catch (Exception $e) {
    $error_message = "Error retrieving general announcements: " . $e->getMessage();
    error_log("Error in announcements page: " . $e->getMessage());
}

// Get existing return service announcements
$return_service_announcements = [];
try {
    // Check if the table exists first
    $table_check = $db->query("SHOW TABLES LIKE 'return_service_announcements'");
    
    if ($table_check->num_rows > 0) {
        $stmt = $db->prepare("SELECT * FROM return_service_announcements ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $return_service_announcements[] = $row;
        }
    } else {
        // Table doesn't exist, set error message
        $error_message = "The return_service_announcements table does not exist in the database. Please run the database update script to create it.";
    }
} catch (Exception $e) {
    $error_message = "Error retrieving return service announcements: " . $e->getMessage();
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
        
        /* Dropdown styles */
        /* Tab Navigation Styles */
        .tab-navigation {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab-btn {
            background-color: #f8f9fa;
            color: #495057;
            padding: 10px 20px;
            border: 1px solid transparent;
            border-radius: 4px 4px 0 0;
            cursor: pointer;
            font-weight: bold;
            margin-right: 5px;
            transition: all 0.2s ease;
        }
        
        .tab-btn:hover {
            background-color: #e9ecef;
        }
        
        .tab-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
            background-color: #fff;
            border-radius: 0 0 10px 10px;
            margin-bottom: 20px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .section-container {
            margin-bottom: 30px;
        }
        
        /* List style for archive */
        .archive-list {
            list-style: none;
            padding: 0;
        }
        
        .archive-list li {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .archive-list li:hover {
            background-color: #f8f9fa;
        }
        
        .archive-list a {
            text-decoration: none;
            color: #333;
            display: block;
            width: 100%;
        }
        
        .archive-list .date {
            color: #6c757d;
            font-size: 14px;
            min-width: 120px;
            text-align: right;
        }
        
        /* Return Service Activities Styles */
        .rs-activities-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .rs-activity-item {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            border-left: 4px solid #28a745;
        }
        
        .rs-activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .rs-activity-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        
        .rs-activity-title a {
            color: #007bff;
            text-decoration: none;
        }
        
        .rs-activity-title a:hover {
            text-decoration: underline;
        }
        
        .rs-activity-date {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .rs-activity-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .rs-detail {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #495057;
        }
        
        .rs-detail i {
            color: #28a745;
            width: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>ANNOUNCEMENTS & RETURN SERVICE</h1>
                <div class="archive-link">
                    <a href="archive.php?type=all"><i class="fas fa-archive"></i> Archive</a>
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
            
            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button id="announcements-tab" class="tab-btn active" onclick="showTab('announcements-section')"><i class="fas fa-bullhorn"></i> General Announcements</button>
                <button id="return-service-tab" class="tab-btn" onclick="showTab('return-service-section')"><i class="fas fa-hands-helping"></i> Return Service Activities</button>
            </div>
            
            <!-- General Announcements Section -->
            <div id="announcements-section" class="tab-content active">
                <div class="form-container">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="announce_general">
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="text">Text</label>
                            <textarea id="text" name="text" required></textarea>
                        </div>
                        
                        <div class="btn-container">
                            <button type="submit" class="btn btn-primary">Post Announcement</button>
                            <button type="button" class="btn btn-secondary" onclick="document.querySelector('input[name=\'action\']').value='sms'; this.form.submit();">SMS</button>
                        </div>
                    </form>
                </div>
                
               
            </div>
            
            <!-- Return Service Section -->
            <div id="return-service-section" class="tab-content">
                    <div class="form-container">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="announce_return_service">
                            
                            <div class="form-group">
                                <label for="rs_title">Title</label>
                                <input type="text" id="rs_title" name="rs_title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="rs_date">Date</label>
                                <input type="date" id="rs_date" name="rs_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="rs_location">Location</label>
                                <input type="text" id="rs_location" name="rs_location" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="rs_hours">Hours</label>
                                <input type="number" id="rs_hours" name="rs_hours" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="rs_slots">Available Slots</label>
                                <input type="number" id="rs_slots" name="rs_slots" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="rs_requirements">Requirements</label>
                                <textarea id="rs_requirements" name="rs_requirements" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="rs_text">Description</label>
                                <textarea id="rs_text" name="rs_text" required></textarea>
                            </div>
                            
                            <div class="btn-container">
                                <button type="submit" class="btn btn-primary">Post Return Service Activity</button>
                                <button type="button" class="btn btn-secondary" onclick="document.querySelector('input[name=\'action\']').value='sms'; this.form.submit();">SMS</button>
                            </div>
                        </form>
                    </div>
                    
                   
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabId) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to the clicked tab button
            if (tabId === 'announcements-section') {
                document.getElementById('announcements-tab').classList.add('active');
            } else if (tabId === 'return-service-section') {
                document.getElementById('return-service-tab').classList.add('active');
            }
            
            // Save the active tab to localStorage
            localStorage.setItem('activeTab', tabId);
        }
        
        // Show the saved tab or the first tab by default
        document.addEventListener('DOMContentLoaded', function() {
            const savedTab = localStorage.getItem('activeTab');
            if (savedTab && document.getElementById(savedTab)) {
                showTab(savedTab);
            } else {
                showTab('announcements-section');
            }
        });
    </script>
</body>
</html>