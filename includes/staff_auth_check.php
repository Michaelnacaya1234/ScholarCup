<?php
/**
 * Staff authentication check
 * Verifies if the user is logged in and has staff role
 * Redirects to login page if not authenticated
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has staff role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'staff') {
    // Not logged in or not a staff, redirect to login page
    header('Location: /Scholar/index.php');
    exit;
}