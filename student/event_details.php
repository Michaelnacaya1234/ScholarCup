<?php
session_start();
require_once '../includes/config/database.php';
require_once 'login_check.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: ../index.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];

// Initialize database connection
$db = Database::getInstance();

// Check if event ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: calendar.php');
    exit;
}

$event_id = intval($_GET['id']);
$status = isset($_GET['status']) ? $_GET['status'] : 'not_submitted';

// Fetch event details
$stmt = $db->prepare("SELECT e.*, 
                     COALESCE(rs.status, 'not_submitted') as activity_status,
                     rs.id as rs_id, rs.hours, rs.proof_file, rs.created_at as submission_date,
                     rs.description as submission_description
                     FROM events e
                     LEFT JOIN return_service_activities rs ON e.id = rs.event_id AND rs.user_id = ?
                     WHERE e.id = ?");
$stmt->bind_param('ii', $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Event not found
    header('Location: calendar.php');
    exit;
}

$event = $result->fetch_assoc();

// Get admin announcements related to this event
$stmt = $db->prepare("SELECT * FROM announcements WHERE event_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param('i', $event_id);
$stmt->execute();
$announcement_result = $stmt->get_result();
$announcement = $announcement_result->fetch_assoc();

// Process form submission if the student is submitting activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_activity'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $hours = $_POST['hours'] ?? 0;
    
    $errors = [];
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = "Activity title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Activity description is required";
    }
    
    if (!is_numeric($hours) || $hours <= 0) {
        $errors[] = "Hours must be a positive number";
    }
    
    // Handle file upload
    $proof_file = '';
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/return_service/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['proof_file']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Check file type
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only PDF, JPG, JPEG, PNG, DOC, and DOCX files are allowed";
        } else if ($_FILES['proof_file']['size'] > 5000000) { // 5MB max
            $errors[] = "File is too large. Maximum size is 5MB";
        } else if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $target_file)) {
            $proof_file = $file_name;
        } else {
            $errors[] = "Failed to upload file";
        }
    } else if ($_FILES['proof_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error uploading file: " . $_FILES['proof_file']['error'];
    } else {
        $errors[] = "Proof file is required";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            // Check if already submitted
            if ($event['rs_id']) {
                // Update existing submission
                $stmt = $db->prepare("UPDATE return_service_activities 
                                     SET title = ?, description = ?, hours = ?, proof_file = ?, status = 'pending', updated_at = NOW() 
                                     WHERE id = ? AND user_id = ?");
                $stmt->bind_param('ssissi', $title, $description, $hours, $proof_file, $event['rs_id'], $user_id);
            } else {
                // New submission
                $stmt = $db->prepare("INSERT INTO return_service_activities 
                                     (user_id, event_id, title, description, hours, proof_file, status, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->bind_param('iissis', $user_id, $event_id, $title, $description, $hours, $proof_file);
            }
            
            if ($stmt->execute()) {
                // Redirect to show success message
                header('Location: event_details.php?id=' . $event_id . '&status=pending&message=success');
                exit;
            } else {
                $errors[] = "Database error: " . $db->error;
            }
        } catch (Exception $e) {
            $errors[] = "An error occurred: " . $e->getMessage();
            error_log('Error submitting activity: ' . $e->getMessage());
        }
    }
}

// Get status message if any
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - Scholar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/event-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include_once '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="back-link">
                    <a href="calendar.php"><i class="fas fa-arrow-left"></i> Back to Calendar</a>
                </div>
                
                <div class="event-details-container">
                    <!-- Event Status Indicator -->
                    <div class="event-status-banner status-<?php echo $status; ?>">
                        <div class="status-text">
                            <?php 
                            if ($status === 'completed') {
                                echo '<i class="fas fa-check-circle"></i> DONE - Activity Completed';
                            } elseif ($status === 'pending') {
                                echo '<i class="fas fa-clock"></i> PENDING - Activity Submitted/Awaiting Approval';
                            } else {
                                echo '<i class="fas fa-times-circle"></i> UNDONE - Activity Not Completed';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Success Message -->
                    <?php if ($message === 'success'): ?>
                    <div class="alert alert-success">
                        Your activity submission has been received and is pending approval.
                    </div>
                    <?php endif; ?>
                    
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Event Information -->
                    <div class="event-info-card">
                        <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                        
                        <div class="event-meta">
                            <div class="event-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php 
                                $start_date = new DateTime($event['start_date']);
                                $end_date = new DateTime($event['end_date']);
                                
                                if ($start_date->format('Y-m-d') === $end_date->format('Y-m-d')) {
                                    echo $start_date->format('F j, Y');
                                } else {
                                    echo $start_date->format('F j') . ' - ' . $end_date->format('F j, Y');
                                }
                                ?>
                            </div>
                            
                            <div class="event-time">
                                <i class="far fa-clock"></i>
                                <?php 
                                echo $start_date->format('g:i A') . ' - ' . $end_date->format('g:i A');
                                ?>
                            </div>
                        </div>
                        
                        <div class="event-description">
                            <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                        </div>
                        
                        <?php if ($announcement): ?>
                        <div class="event-announcement">
                            <h3>Admin Announcement</h3>
                            <div class="announcement-content">
                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                            </div>
                            <div class="announcement-date">
                                Posted on <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Activity Submission Form or Details -->
                    <?php if ($status === 'completed'): ?>
                    <!-- Completed Activity Details -->
                    <div class="activity-details-card">
                        <h3>Your Submitted Activity</h3>
                        
                        <div class="activity-detail-item">
                            <span class="detail-label">Title:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($event['submission_description'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="activity-detail-item">
                            <span class="detail-label">Hours Completed:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($event['hours'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="activity-detail-item">
                            <span class="detail-label">Submission Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($event['submission_date'] ?? 'now')); ?></span>
                        </div>
                        
                        <?php if (!empty($event['proof_file'])): ?>
                        <div class="activity-detail-item">
                            <span class="detail-label">Proof Document:</span>
                            <a href="../uploads/return_service/<?php echo htmlspecialchars($event['proof_file']); ?>" target="_blank" class="btn btn-secondary">
                                <i class="fas fa-file-download"></i> View Document
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="activity-status-approved">
                            <i class="fas fa-check-circle"></i> This activity has been approved
                        </div>
                    </div>
                    
                    <?php elseif ($status === 'pending'): ?>
                    <!-- Pending Activity Details -->
                    <div class="activity-details-card">
                        <h3>Your Submitted Activity</h3>
                        
                        <div class="activity-detail-item">
                            <span class="detail-label">Title:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($event['submission_description'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="activity-detail-item">
                            <span class="detail-label">Hours Completed:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($event['hours'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="activity-detail-item">
                            <span class="detail-label">Submission Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($event['submission_date'] ?? 'now')); ?></span>
                        </div>
                        
                        <?php if (!empty($event['proof_file'])): ?>
                        <div class="activity-detail-item">
                            <span class="detail-label">Proof Document:</span>
                            <a href="../uploads/return_service/<?php echo htmlspecialchars($event['proof_file']); ?>" target="_blank" class="btn btn-secondary">
                                <i class="fas fa-file-download"></i> View Document
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="activity-status-pending">
                            <i class="fas fa-clock"></i> Your submission is pending approval
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- Activity Submission Form -->
                    <div class="activity-submission-card">
                        <h3>Submit Your Activity</h3>
                        
                        <form method="POST" action="" enctype="multipart/form-data" class="activity-form">
                            <div class="form-group">
                                <label for="title">Activity Title:</label>
                                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($event['title']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Activity Description:</label>
                                <textarea id="description" name="description" required rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="hours">Hours Completed:</label>
                                <input type="number" id="hours" name="hours" required min="1" step="0.5" value="1">
                            </div>
                            
                            <div class="form-group">
                                <label for="proof_file">Proof Document (PDF, Image, or Document):</label>
                                <input type="file" id="proof_file" name="proof_file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <small>Upload a document or image as proof of your activity completion (max 5MB)</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="submit_activity" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Activity
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>