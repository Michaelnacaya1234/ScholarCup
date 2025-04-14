<?php
session_start();
require_once '../includes/config/database.php';
require_once '../student/login_check.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: ../index.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$full_name = $first_name . ' ' . $last_name;

// Initialize database connection
$db = Database::getInstance();

// Get announcements count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM announcements");
$stmt->execute();
$result = $stmt->get_result();
$announcements_count = $result->fetch_assoc()['count'];

// Get inbox messages count (placeholder for actual implementation)
$inbox_count = 0; // This would be replaced with actual count from database

// Determine which tab to show (default to student concern)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'concern';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Forms - CSO Scholar Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        /* Tab styling */
        .tab-container {
            width: 100%;
            margin: 20px 0;
        }
        .tab-nav {
            display: flex;
            background-color: #f5f5f5;
            border-radius: 5px 5px 0 0;
            overflow: hidden;
        }
        .tab-link {
            padding: 15px 20px;
            background-color: #f5f5f5;
            border: none;
            cursor: pointer;
            flex: 1;
            text-align: center;
            font-weight: bold;
            transition: background-color 0.3s;
            text-decoration: none;
            color: #333;
        }
        .tab-link.active {
            background-color: #0056b3;
            color: white;
        }
        .tab-link:hover:not(.active) {
            background-color: #e0e0e0;
        }
        .tab-content {
            display: none;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tab-content.active {
            display: block;
        }
        .form-container {
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
        }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-description {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e8f4ff;
            border-left: 4px solid #0056b3;
            border-radius: 4px;
        }
        .upload-area {
            border: 2px dashed #ccc;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f8f8f8;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #0056b3;
        }
        .upload-icon {
            font-size: 48px;
            color: #0056b3;
            margin-bottom: 10px;
        }
        .submit-btn {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            float: right;
        }
        .submit-btn:hover {
            background-color: #003d82;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Submission Forms</h1>
                <div class="user-actions">
                    
                    
                    
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-container">
                <div class="tab-nav">
                    <a href="?tab=concern" class="tab-link <?php echo $active_tab == 'concern' ? 'active' : ''; ?>">
                        <i class="fas fa-comment-alt"></i> Student Concern
                    </a>
                    <a href="?tab=rs_submission" class="tab-link <?php echo $active_tab == 'rs_submission' ? 'active' : ''; ?>">
                        <i class="fas fa-file-upload"></i> RS Submission Form
                    </a>
                    <a href="?tab=grades" class="tab-link <?php echo $active_tab == 'grades' ? 'active' : ''; ?>">
                        <i class="fas fa-graduation-cap"></i> Grades Form
                    </a>
                </div>

                <!-- Student Concern Tab -->
                <div id="concern" class="tab-content <?php echo $active_tab == 'concern' ? 'active' : ''; ?>">
                    <div class="form-description">
                        <p><strong>Student Concern Form</strong> - Use this form to submit any concerns, questions, or issues you're experiencing as a scholar. Our staff will review your submission and respond as soon as possible.</p>
                    </div>
                    <div class="form-container">
                        <form action="submit_concern.php" method="post">
                            <div class="form-group">
                                <label for="concern_title">Title:</label>
                                <input type="text" id="concern_title" name="concern_title" required placeholder="Brief title of your concern">
                            </div>
                            <div class="form-group">
                                <label for="concern_details">Concern:</label>
                                <textarea id="concern_details" name="concern_details" required placeholder="Describe your concern in detail"></textarea>
                            </div>
                            <button type="submit" class="submit-btn">Submit Request</button>
                            <div style="clear:both;"></div>
                        </form>
                    </div>
                </div>

                <!-- RS Submission Form Tab -->
                <div id="rs_submission" class="tab-content <?php echo $active_tab == 'rs_submission' ? 'active' : ''; ?>">
                    <div class="form-description">
                        <p><strong>Return Service Submission Form</strong> - Use this form to submit documentation of your completed return service activities. Please upload proof such as certificates, photos, or signed documents.</p>
                    </div>
                    <div class="form-container">
                        <form action="submit_rs.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="rs_title">Activity Title:</label>
                                <input type="text" id="rs_title" name="rs_title" required placeholder="Title of your return service activity">
                            </div>
                            <div class="form-group">
                                <label for="rs_description">Activity Description:</label>
                                <textarea id="rs_description" name="rs_description" required placeholder="Describe the return service activity you completed"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="rs_hours">Hours Completed:</label>
                                <input type="number" id="rs_hours" name="rs_hours" required min="1" placeholder="Number of hours">
                            </div>
                            <div class="form-group">
                                <label>Upload Proof:</label>
                                <div class="upload-area" onclick="document.getElementById('rs_proof').click()">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p>Click to upload proof or drag and drop files here</p>
                                    <input type="file" id="rs_proof" name="rs_proof" style="display: none;" required>
                                    <p id="rs_file_name">No file selected</p>
                                </div>
                            </div>
                            <button type="submit" class="submit-btn">Submit</button>
                            <div style="clear:both;"></div>
                        </form>
                    </div>
                </div>

                <!-- Grades Form Tab -->
                <div id="grades" class="tab-content <?php echo $active_tab == 'grades' ? 'active' : ''; ?>">
                    <div class="form-description">
                        <p><strong>Grades Submission Form</strong> - Use this form to submit your semester grades. Please upload a clear scan or screenshot of your official grade report from your school.</p>
                    </div>
                    <div class="form-container">
                        <form action="submit_grades.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="semester">Semester:</label>
                                <select id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="academic_year">Academic Year:</label>
                                <input type="text" id="academic_year" name="academic_year" required placeholder="e.g., 2023-2024">
                            </div>
                            <div class="form-group">
                                <label>Upload Grade Report:</label>
                                <div class="upload-area" onclick="document.getElementById('grade_proof').click()">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p>Click to upload grade report or drag and drop file here</p>
                                    <input type="file" id="grade_proof" name="grade_proof" style="display: none;" required>
                                    <p id="grade_file_name">No file selected</p>
                                </div>
                            </div>
                            <button type="submit" class="submit-btn">Submit</button>
                            <div style="clear:both;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for tab switching and file upload preview
        document.addEventListener('DOMContentLoaded', function() {
            // File upload preview for RS Submission
            const rsProofInput = document.getElementById('rs_proof');
            const rsFileName = document.getElementById('rs_file_name');
            
            if (rsProofInput) {
                rsProofInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        rsFileName.textContent = this.files[0].name;
                    } else {
                        rsFileName.textContent = 'No file selected';
                    }
                });
            }
            
            // File upload preview for Grades
            const gradeProofInput = document.getElementById('grade_proof');
            const gradeFileName = document.getElementById('grade_file_name');
            
            if (gradeProofInput) {
                gradeProofInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        gradeFileName.textContent = this.files[0].name;
                    } else {
                        gradeFileName.textContent = 'No file selected';
                    }
                });
            }
        });
    </script>
</body>
</html>