<?php
session_start();
require_once '../includes/config/database.php';
require_once 'login_check.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: ../index.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user data
    $user_id = $_SESSION['user_id'];
    
    // Get form data
    $title = trim($_POST['rs_title'] ?? '');
    $description = trim($_POST['rs_description'] ?? '');
    $hours = intval($_POST['rs_hours'] ?? 0);
    
    // Validate form data
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Activity title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Activity description is required";
    }
    
    if ($hours <= 0) {
        $errors[] = "Hours must be greater than zero";
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['rs_proof']) || $_FILES['rs_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Proof document is required";
    }
    
    // If no errors, process file upload and save to database
    if (empty($errors)) {
        try {
            // Initialize database connection
            $db = Database::getInstance();
            
            // Process file upload
            $file = $_FILES['rs_proof'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];
            
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Allowed file extensions
            $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            
            // Validate file
            if ($file_error !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading file. Error code: {$file_error}");
            }
            
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowed_ext);
                throw new Exception("File type not allowed");
            }
            
            if ($file_size > 5242880) { // 5MB max
                $errors[] = "File size too large. Maximum size: 5MB";
                throw new Exception("File size too large");
            }
            
            // Create unique filename
            $new_file_name = uniqid('rs_') . '.' . $file_ext;
            
            // Set upload directory
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Scholar/uploads/rs_proofs/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_file_name;
            
            // Move uploaded file
            if (!move_uploaded_file($file_tmp, $upload_path)) {
                throw new Exception("Failed to move uploaded file");
            }
            
            // Save to database
            $file_path = '/Scholar/uploads/rs_proofs/' . $new_file_name;
            
            // Prepare and execute query
            $stmt = $db->prepare("INSERT INTO return_service_activities (user_id, title, description, hours, proof_file, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param('issis', $user_id, $title, $description, $hours, $file_path);
            $result = $stmt->execute();
            
            if ($result) {
                // Redirect with success message
                header('Location: submission_form.php?tab=rs_submission&status=success&message=' . urlencode("Your return service activity has been submitted successfully."));
                exit;
            } else {
                // Redirect with error message
                header('Location: submission_form.php?tab=rs_submission&status=error&message=' . urlencode("Failed to submit your return service activity. Please try again."));
                exit;
            }
        } catch (Exception $e) {
            // Log error and redirect with error message
            error_log('Error submitting return service activity: ' . $e->getMessage());
            header('Location: submission_form.php?tab=rs_submission&status=error&message=' . urlencode("An error occurred. Please try again later."));
            exit;
        }
    } else {
        // Redirect with error messages
        $error_string = implode(", ", $errors);
        header('Location: submission_form.php?tab=rs_submission&status=error&message=' . urlencode($error_string));
        exit;
    }
} else {
    // If not POST request, redirect to form page
    header('Location: submission_form.php?tab=rs_submission');
    exit;
}