<?php
// Include database connection
require_once '../database.php';

// Check if user is logged in and is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Initialize variables
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$firstName = '';
$lastName = '';
$email = '';
$message = '';
$error = '';

// Check if the user exists and is a student
if ($user_id > 0) {
    $stmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Student not found.";
    } else {
        $row = $result->fetch_assoc();
        $firstName = $row['first_name'];
        $lastName = $row['last_name'];
        $email = $row['email'];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Get form data
    $student_id = (int)$_POST['user_id'];
    $school = $db->real_escape_string($_POST['school']);
    $course = $db->real_escape_string($_POST['course']);
    $year_level = (int)$_POST['year_level'];
    $address = $db->real_escape_string($_POST['address']);
    $phone = $db->real_escape_string($_POST['phone']);
    
    // Check if student profile already exists
    $checkStmt = $db->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
    $checkStmt->bind_param("i", $student_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Update existing profile
        $updateStmt = $db->prepare("UPDATE student_profiles SET school = ?, course = ?, year_level = ?, address = ?, phone = ? WHERE user_id = ?");
        $updateStmt->bind_param("ssissi", $school, $course, $year_level, $address, $phone, $student_id);
        
        if ($updateStmt->execute()) {
            $message = "Student profile updated successfully.";
        } else {
            $error = "Error updating student profile: " . $db->error;
        }
    } else {
        // Insert new profile
        $insertStmt = $db->prepare("INSERT INTO student_profiles (user_id, school, course, year_level, address, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ississ", $student_id, $school, $course, $year_level, $address, $phone);
        
        if ($insertStmt->execute()) {
            // Also initialize status records if they don't exist
            $initCurrentStatus = $db->prepare("INSERT IGNORE INTO student_current_status (user_id, status) VALUES (?, 'pending')");
            $initCurrentStatus->bind_param("i", $student_id);
            $initCurrentStatus->execute();
            
            $initAcademicStatus = $db->prepare("INSERT IGNORE INTO student_academic_status (user_id, status, renewal_status, total_return_service) VALUES (?, 'not evaluate yet', 'not renew yet', 0)");
            $initAcademicStatus->bind_param("i", $student_id);
            $initAcademicStatus->execute();
            
            $message = "Student registration completed successfully.";
        } else {
            $error = "Error completing student registration: " . $db->error;
        }
    }
    
    // Redirect after successful operation
    if (!empty($message)) {
        header("Location: students_status.php?reg_completed=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Student Registration - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-status.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-cancel {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>COMPLETE STUDENT REGISTRATION</h1>
                <a href="students_status.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Students</a>
            </div>
            
            <div class="content-wrapper">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php elseif (!empty($message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($error) || !empty($user_id)): ?>
                <div class="form-container">
                    <h2>Complete Registration for: <?php echo htmlspecialchars("$firstName $lastName"); ?></h2>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></p>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        
                        <div class="form-group">
                            <label for="school">School/University:</label>
                            <input type="text" id="school" name="school" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="course">Course/Program:</label>
                            <input type="text" id="course" name="course" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="year_level">Year Level:</label>
                            <select id="year_level" name="year_level" class="form-control" required>
                                <option value="">Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                                <option value="5">5th Year</option>
                                <option value="6">6th Year</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address:</label>
                            <textarea id="address" name="address" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number:</label>
                            <input type="text" id="phone" name="phone" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="submit" class="btn-submit">
                                <i class="fas fa-save"></i> Complete Registration
                            </button>
                            <a href="students_status.php" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>