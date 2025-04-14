<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

// Initialize and run database checker
require_once 'check_database_structure.php';
use Scholar\Admin\DatabaseStructureChecker;
$dbChecker = new DatabaseStructureChecker();
$dbChecker->checkAndUpdate();

$success_message = '';
$error_message = '';

// Determine registration type (student or staff)
$registration_type = isset($_GET['type']) ? $_GET['type'] : 'student';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    
    // Validate required fields
    if (empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
        $error_message = "All required fields must be filled out.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Email address is already registered.";
            } else {
                // Start transaction
                $db->begin_transaction();
                
                // Insert into users table
                $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Using bcrypt for secure password hashing
                $stmt = $db->prepare("INSERT INTO users (email, password, first_name, middle_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssss', $email, $hashed_password, $first_name, $middle_name, $last_name, $role);
                
                if ($stmt->execute()) {
                    $user_id = $db->insert_id;
                    
                    if ($role === 'student') {
                        // Get student-specific data
                        $course = $_POST['course'] ?? '';
                        $year_level = $_POST['year_level'] ?? '';
                        $contact_number = $_POST['contact_number'] ?? '';
                        $address = $_POST['address'] ?? '';
                        
                        // Generate student ID
                        $result = $db->query("SELECT MAX(CAST(SUBSTRING(student_id, 5) AS UNSIGNED)) as max_id FROM student_profiles WHERE student_id LIKE 'STU-%'");
                        $row = $result->fetch_assoc();
                        $next_number = ($row['max_id'] ?? 0) + 1;
                        $student_id = 'STU-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
                        
                        // Validate student-specific required fields
                        if (empty($course) || empty($year_level)) {
                            $error_message = "Course and year level are required.";
                            $db->rollback();
                        } else {
                            // Insert into student_profiles table
                            $stmt = $db->prepare("INSERT INTO student_profiles (user_id, course, year_level, contact_number, address) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param('isiss', $user_id, $course, $year_level, $contact_number, $address);
                            
                            if ($stmt->execute()) {
                                $db->commit();
                                $success_message = "Student account has been registered successfully.";
                                // Redirect to student accounts page after successful registration
                                header("Location: student_accounts.php?success=1");
                                exit;
                            } else {
                                $error_message = "Failed to create student profile. Please try again.";
                                $db->rollback();
                            }
                        }
                    } elseif ($role === 'staff') {
                        // Get staff-specific data
                        $department = $_POST['department'] ?? '';
                        $position = $_POST['position'] ?? '';
                        $contact_number = $_POST['contact_number'] ?? '';
                        
                        // Generate staff ID
                        $result = $db->query("SELECT MAX(CAST(SUBSTRING(staff_id, 7) AS UNSIGNED)) as max_id FROM staff_profiles WHERE staff_id LIKE 'STAFF-%'");
                        $row = $result->fetch_assoc();
                        $next_number = ($row['max_id'] ?? 0) + 1;
                        $staff_id = 'STAFF-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
                        
                        // Validate staff-specific required fields
                        if (empty($department) || empty($position)) {
                            $error_message = "Department and position are required.";
                            $db->rollback();
                        } else {
                            // Insert into staff_profiles table
                            $stmt = $db->prepare("INSERT INTO staff_profiles (user_id, staff_id, department, position, contact_number) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param('issss', $user_id, $staff_id, $department, $position, $contact_number);
                            
                            if ($stmt->execute()) {
                                $db->commit();
                                $success_message = "Staff account has been registered successfully.";
                                // Redirect to staff accounts page after successful registration
                                header("Location: staff_accounts.php?success=1");
                                exit;
                            } else {
                                $error_message = "Failed to create staff profile. Please try again.";
                                $db->rollback();
                            }
                        }
                    } else {
                        $error_message = "Invalid role selected.";
                        $db->rollback();
                    }
                } else {
                    $error_message = "Failed to create user account. Please try again.";
                    $db->rollback();
                }
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error in registration page: " . $e->getMessage());
            if (isset($db) && $db->connect_error === null) {
                $db->rollback();
            }
        }
    }
}

// Get the next available student/staff ID
$next_id = '';
try {
    if ($registration_type === 'student') {
        $result = $db->query("SELECT MAX(CAST(SUBSTRING(student_id, 5) AS UNSIGNED)) as max_id FROM student_profiles WHERE student_id LIKE 'STU-%'");
        $row = $result->fetch_assoc();
        $next_number = ($row['max_id'] ?? 0) + 1;
        $next_id = 'STU-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
    } else {
        $result = $db->query("SELECT MAX(CAST(SUBSTRING(staff_id, 7) AS UNSIGNED)) as max_id FROM staff_profiles WHERE staff_id LIKE 'STAFF-%'");
        $row = $result->fetch_assoc();
        $next_number = ($row['max_id'] ?? 0) + 1;
        $next_id = 'STAFF-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {
    error_log("Error generating next ID: " . $e->getMessage());
    // Default IDs if query fails
    $next_id = $registration_type === 'student' ? 'STU-000001' : 'STAFF-000001';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .registration-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        
        .registration-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .registration-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .registration-tab.active {
            border-bottom: 3px solid #007bff;
            color: #007bff;
        }
        
        .registration-form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group select {
            height: 42px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        .submit-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #0069d9;
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
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6c757d;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #343a40;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>REGISTRATION</h1>
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
            
            <div class="registration-container">
                <div class="registration-tabs">
                    <div class="registration-tab <?php echo $registration_type === 'student' ? 'active' : ''; ?>" onclick="window.location.href='registration.php?type=student'">
                        <i class="fas fa-user-graduate"></i> Student Registration
                    </div>
                    <div class="registration-tab <?php echo $registration_type === 'staff' ? 'active' : ''; ?>" onclick="window.location.href='registration.php?type=staff'">
                        <i class="fas fa-user-tie"></i> Staff Registration
                    </div>
                </div>
                
                <?php if ($registration_type === 'student'): ?>
                    <!-- Student Registration Form -->
                    <form class="registration-form" method="POST" action="">
                        <input type="hidden" name="role" value="student">
                        
                        <h2><i class="fas fa-user-graduate"></i> Student Information</h2>
                        
                        <div class="form-group">
                            <label for="email" class="required-field">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="required-field">Password</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password" class="required-field">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="required-field">First Name</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name">
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="required-field">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course" class="required-field">Course</label>
                                <input type="text" id="course" name="course" required>
                            </div>
                            <div class="form-group">
                                <label for="year_level" class="required-field">Year Level</label>
                                <select id="year_level" name="year_level" required>
                                    <option value="">Select Year Level</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                    <option value="5">5th Year</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_number" class="required-field">Phone Number</label>
                                <input type="text" id="contact_number" name="contact_number" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="submit-btn">Register Student</button>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Staff Registration Form -->
                    <form class="registration-form" method="POST" action="">
                        <input type="hidden" name="role" value="staff">
                        
                        <h2><i class="fas fa-user-tie"></i> Staff Information</h2>
                        
                        <div class="form-group">
                            <label for="email" class="required-field">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="required-field">Password</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password" class="required-field">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="required-field">First Name</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name">
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="required-field">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="department" class="required-field">Department</label>
                                <input type="text" id="department" name="department" required>
                            </div>
                            <div class="form-group">
                                <label for="position" class="required-field">Position</label>
                                <input type="text" id="position" name="position" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_number" class="required-field">Phone Number</label>
                                <input type="text" id="contact_number" name="contact_number" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="submit-btn">Register Staff</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.registration-form');
            
            if (form) {
                form.addEventListener('submit', function(event) {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (password !== confirmPassword) {
                        event.preventDefault();
                        alert('Passwords do not match!');
                    }
                });
            }
        });
    </script>
</body>
</html>
