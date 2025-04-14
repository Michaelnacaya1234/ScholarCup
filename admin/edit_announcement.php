<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$error_message = '';
$success_message = '';
$announcement = null;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the announcement data for editing
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
        error_log("Error in edit_announcement.php: " . $e->getMessage());
    }
}

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $update_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $update_type = isset($_POST['type']) ? $_POST['type'] : '';
    
    if (empty($update_id) || empty($update_type)) {
        $error_message = "Invalid parameters provided.";
    } else {
        try {
            if ($update_type === 'general') {
                // Update general announcement
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                
                if (empty($title) || empty($content)) {
                    $error_message = "Please fill in all required fields.";
                } else {
                    $stmt = $db->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $title, $content, $update_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Announcement updated successfully.";
                        // Refresh the announcement data
                        $stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
                        $stmt->bind_param("i", $update_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $announcement = $result->fetch_assoc();
                    } else {
                        $error_message = "Failed to update announcement.";
                    }
                }
            } elseif ($update_type === 'return_service') {
                // Update return service announcement
                $title = trim($_POST['title'] ?? '');
                $date = trim($_POST['date'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $hours = intval($_POST['hours'] ?? 0);
                $slots = intval($_POST['slots'] ?? 0);
                $requirements = trim($_POST['requirements'] ?? '');
                
                if (empty($title) || empty($date) || empty($description)) {
                    $error_message = "Please fill in all required fields.";
                } else {
                    $stmt = $db->prepare("UPDATE return_service_announcements SET title = ?, date = ?, description = ?, location = ?, hours = ?, slots = ?, requirements = ? WHERE id = ?");
                    $stmt->bind_param("ssssiiis", $title, $date, $description, $location, $hours, $slots, $requirements, $update_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Return service activity updated successfully.";
                        // Refresh the announcement data
                        $stmt = $db->prepare("SELECT * FROM return_service_announcements WHERE id = ?");
                        $stmt->bind_param("i", $update_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $announcement = $result->fetch_assoc();
                    } else {
                        $error_message = "Failed to update return service activity.";
                    }
                }
            } else {
                $error_message = "Invalid announcement type.";
            }
        } catch (Exception $e) {
            $error_message = "Error updating announcement: " . $e->getMessage();
            error_log("Error in edit_announcement.php: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Announcement - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .edit-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 20px auto;
            max-width: 800px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 150px;
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>EDIT ANNOUNCEMENT</h1>
                <div class="back-link">
                    <a href="archive.php"><i class="fas fa-arrow-left"></i> Back to Archive</a>
                </div>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($announcement): ?>
                <div class="edit-container">
                    <?php if ($type === 'general'): ?>
                        <!-- Edit form for general announcement -->
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="type" value="general">
                            
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Content</label>
                                <textarea id="content" name="content" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                            </div>
                            
                            <div class="btn-container">
                                <a href="archive.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Announcement</button>
                            </div>
                        </form>
                    <?php elseif ($type === 'return_service'): ?>
                        <!-- Edit form for return service announcement -->
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="type" value="return_service">
                            
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($announcement['date']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($announcement['location']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="hours">Hours</label>
                                <input type="number" id="hours" name="hours" value="<?php echo htmlspecialchars($announcement['hours']); ?>" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="slots">Available Slots</label>
                                <input type="number" id="slots" name="slots" value="<?php echo htmlspecialchars($announcement['slots']); ?>" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="requirements">Requirements</label>
                                <textarea id="requirements" name="requirements" required><?php echo htmlspecialchars($announcement['requirements']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" required><?php echo htmlspecialchars($announcement['description']); ?></textarea>
                            </div>
                            
                            <div class="btn-container">
                                <a href="archive.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Return Service Activity</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>