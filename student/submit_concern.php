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
    $title = trim($_POST['concern_title'] ?? '');
    $details = trim($_POST['concern_details'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($details)) {
        $errors[] = "Concern details are required";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            // Initialize database connection
            $db = Database::getInstance();
            
            // Prepare and execute query
            $stmt = $db->prepare("INSERT INTO student_concerns (user_id, title, details, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->bind_param('iss', $user_id, $title, $details);
            $result = $stmt->execute();
            
            if ($result) {
                // Redirect with success message
                header('Location: submission_form.php?tab=concern&status=success&message=' . urlencode("Your concern has been submitted successfully."));
                exit;
            } else {
                // Redirect with error message
                header('Location: submission_form.php?tab=concern&status=error&message=' . urlencode("Failed to submit your concern. Please try again."));
                exit;
            }
        } catch (Exception $e) {
            // Log error and redirect with error message
            error_log('Error submitting student concern: ' . $e->getMessage());
            header('Location: submission_form.php?tab=concern&status=error&message=' . urlencode("An error occurred. Please try again later."));
            exit;
        }
    } else {
        // Redirect with error messages
        $error_string = implode(", ", $errors);
        header('Location: submission_form.php?tab=concern&status=error&message=' . urlencode($error_string));
        exit;
    }
} else {
    // If not POST request, redirect to form page
    header('Location: submission_form.php?tab=concern');
    exit;
}