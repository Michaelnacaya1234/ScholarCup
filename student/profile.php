<?php
session_start();
require_once '../includes/config/database.php';
require_once '../includes/Auth.php';

// Check if user is logged in and has student role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$success_message = '';
$error_message = '';
$student_id = $_SESSION['user_id'];

// Initialize database connection
$db = Database::getInstance();

// Get student profile information
try {
    $query = "SELECT u.*, sp.* FROM users u 
              LEFT JOIN student_profiles sp ON u.id = sp.user_id 
              WHERE u.id = ? AND u.role = 'student'";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Student profile not found.";
    } else {
        $student = $result->fetch_assoc();
    }
} catch (Exception $e) {
    $error_message = "Error retrieving student profile: " . $e->getMessage();
    error_log("Error in student profile page: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $birthday = trim($_POST['birthday']);
    $school = trim($_POST['school']);
    $course = trim($_POST['course']);
    $year_level = trim($_POST['year_level']);
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = "First name, last name, and email are required.";
    } else {
        try {
            // Begin transaction
            $db->begin_transaction();
            
            // Update users table
            $update_user = "UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ? WHERE id = ?";
            $stmt = $db->prepare($update_user);
            $stmt->bind_param('ssssi', $first_name, $middle_name, $last_name, $email, $student_id);
            $stmt->execute();
            
            // Check if student profile exists
            $check_profile = "SELECT * FROM student_profiles WHERE user_id = ?";
            $stmt = $db->prepare($check_profile);
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update student_profiles table
                $update_profile = "UPDATE student_profiles SET phone = ?, address = ?, birthday = ?, 
                                   school = ?, course = ?, year_level = ? WHERE user_id = ?";
                $stmt = $db->prepare($update_profile);
                $stmt->bind_param('ssssssi', $phone, $address, $birthday, $school, $course, $year_level, $student_id);
                $stmt->execute();
            } else {
                // Insert into student_profiles table
                $insert_profile = "INSERT INTO student_profiles (user_id, phone, address, birthday, school, course, year_level) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($insert_profile);
                $stmt->bind_param('issssss', $student_id, $phone, $address, $birthday, $school, $course, $year_level);
                $stmt->execute();
            }
            
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                    throw new Exception("Only JPG, PNG, and GIF files are allowed.");
                }
                
                if ($_FILES['profile_picture']['size'] > $max_size) {
                    throw new Exception("File size must be less than 2MB.");
                }
                
                $upload_dir = '../uploads/profile_pictures/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        throw new Exception("Failed to create upload directory.");
                    }
                    chmod($upload_dir, 0777);
                }
                
                $filename = $student_id . '_' . time() . '_' . basename($_FILES['profile_picture']['name']);
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    // Update profile picture in database
                    $update_picture = "UPDATE student_profiles SET profile_picture = ? WHERE user_id = ?";
                    $stmt = $db->prepare($update_picture);
                    $stmt->bind_param('si', $filename, $student_id);
                    $stmt->execute();
                } else {
                    throw new Exception("Failed to upload profile picture. Error code: " . $_FILES['profile_picture']['error']);
                }
            }
            
            // Commit transaction
            $db->commit();
            
            $success_message = "Profile updated successfully.";
            
            // Refresh student data
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            $error_message = "Error updating profile: " . $e->getMessage();
            error_log("Error updating student profile: " . $e->getMessage());
        }
    }
}

// Handle Facebook link update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_facebook'])) {
    $facebook_link = trim($_POST['facebook_link']);
    
    try {
        // Update Facebook link in student_profiles table
        $update_facebook = "UPDATE student_profiles SET facebook_link = ? WHERE user_id = ?";
        $stmt = $db->prepare($update_facebook);
        $stmt->bind_param('si', $facebook_link, $student_id);
        $stmt->execute();
        
        $success_message = "Facebook link updated successfully.";
        
        // Refresh student data
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        
    } catch (Exception $e) {
        $error_message = "Error updating Facebook link: " . $e->getMessage();
        error_log("Error updating student Facebook link: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .profile-picture-section {
            flex: 1;
            min-width: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #007bff;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .facebook-link {
            margin-top: 15px;
            width: 100%;
        }
        
        .facebook-link a {
            display: block;
            color: #3b5998;
            text-decoration: none;
            margin-bottom: 10px;
            word-break: break-all;
        }
        
        .facebook-link button {
            background-color: #3b5998;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
        }
        
        .facebook-link button:hover {
            background-color: #2d4373;
        }
        
        .profile-details {
            flex: 2;
            min-width: 300px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .detail-label {
            flex: 1;
            font-weight: bold;
            color: #495057;
        }
        
        .detail-value {
            flex: 2;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 10px;
        }
        
        .edit-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .edit-btn:hover {
            background-color: #218838;
        }
        
        .save-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .save-btn:hover {
            background-color: #0069d9;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .alert {
            padding: 10px 15px;
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
        <?php include '../includes/student_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Profile</h1>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($student)): ?>
                <div class="profile-container">
                    <div class="profile-picture-section">
                        <?php if (!empty($student['profile_picture'])): ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                        <?php else: ?>
                            <img src="../assets/images/default_profile.png" alt="Default Profile" class="profile-picture">
                        <?php endif; ?>
                        
                        <div class="profile-name">
                            <?php 
                            $name = htmlspecialchars($student['first_name']);
                            if (!empty($student['middle_name'])) {
                                $name .= ' ' . htmlspecialchars($student['middle_name'][0]) . '.';
                            }
                            $name .= ' ' . htmlspecialchars($student['last_name']);
                            echo $name;
                            ?>
                        </div>
                        
                        <div class="facebook-link">
                            <p>Facebook Link</p>
                            <?php if (!empty($student['facebook_link'])): ?>
                                <a href="<?php echo htmlspecialchars($student['facebook_link']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($student['facebook_link']); ?>
                                </a>
                                <button id="fbLinkBtn">Edit Facebook Link</button>
                            <?php else: ?>
                                <p>No Facebook link added</p>
                                <button id="fbLinkBtn">Add Facebook Link</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profile-details">
                        <div class="detail-row">
                            <div class="detail-label">Full name</div>
                            <div class="detail-value"><?php echo $name; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($student['email']); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Birthday</div>
                            <div class="detail-value">
                                <?php echo !empty($student['birthday']) ? date('F j, Y', strtotime($student['birthday'])) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">
                                <?php echo !empty($student['phone']) ? htmlspecialchars($student['phone']) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Address</div>
                            <div class="detail-value">
                                <?php echo !empty($student['address']) ? htmlspecialchars($student['address']) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">School</div>
                            <div class="detail-value">
                                <?php echo !empty($student['school']) ? htmlspecialchars($student['school']) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Course</div>
                            <div class="detail-value">
                                <?php echo !empty($student['course']) ? htmlspecialchars($student['course']) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Year Level</div>
                            <div class="detail-value">
                                <?php echo !empty($student['year_level']) ? htmlspecialchars($student['year_level']) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="edit-btn" id="editProfileBtn">Edit</button>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Profile Modal -->
                <div id="editProfileModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Profile</h2>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="profile_picture">Profile Picture</label>
                                <input type="file" name="profile_picture" id="profile_picture">
                            </div>
                            
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="birthday">Birthday</label>
                                <input type="date" name="birthday" id="birthday" value="<?php echo !empty($student['birthday']) ? date('Y-m-d', strtotime($student['birthday'])) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea name="address" id="address" rows="3"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="school">School</label>
                                <input type="text" name="school" id="school" value="<?php echo htmlspecialchars($student['school'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="course">Course</label>
                                <input type="text" name="course" id="course" value="<?php echo htmlspecialchars($student['course'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="year_level">Year Level</label>
                                <input type="text" name="year_level" id="year_level" value="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Facebook Link Modal -->
                <div id="fbLinkModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Update Facebook Link</h2>
                        <form action="" method="POST">
                            <div class="form-group">
                                <label for="facebook_link">Facebook Profile URL</label>
                                <input type="url" name="facebook_link" id="facebook_link" value="<?php echo htmlspecialchars($student['facebook_link'] ?? ''); ?>" placeholder="https://facebook.com/your.profile">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_facebook" class="save-btn">Save Link</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Get the modals
        var editProfileModal = document.getElementById("editProfileModal");
        var fbLinkModal = document.getElementById("fbLinkModal");
        
        // Get the buttons that open the modals
        var editProfileBtn = document.getElementById("editProfileBtn");
        var fbLinkBtn = document.getElementById("fbLinkBtn");
        
        // Get the <span> elements that close the modals
        var closeButtons = document.getElementsByClassName("close");
        
        // When the user clicks the button, open the modal
        editProfileBtn.onclick = function() {
            editProfileModal.style.display = "block";
        }
        
        fbLinkBtn.onclick = function() {
            fbLinkModal.style.display = "block";
        }
        
        // When the user clicks on <span> (x), close the modal
        for (var i = 0; i < closeButtons.length; i++) {
            closeButtons[i].onclick = function() {
                editProfileModal.style.display = "none";
                fbLinkModal.style.display = "none";
            }
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == editProfileModal) {
                editProfileModal.style.display = "none";
            }
            if (event.target == fbLinkModal) {
                fbLinkModal.style.display = "none";
            }
        }
    </script>
</body>
</html>