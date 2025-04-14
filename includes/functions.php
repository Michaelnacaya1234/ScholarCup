<?php
/**
 * Common functions used throughout the application
 */

/**
 * Get database connection
 * @return mysqli Database connection object
 */
function getConnection() {
    // Check if Database class exists and use it if available
    if (class_exists('Database')) {
        $db = Database::getInstance();
        return $db->getConnection();
    } else {
        // Fallback to direct connection if Database class is not available
        require_once dirname(__FILE__) . '/config/database.php';
        $db = Database::getInstance();
        return $db->getConnection();
    }
}

/**
 * Sanitize user input
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * @param string $role Role to check
 * @return bool True if user has role, false otherwise
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Get user information by ID
 * @param int $user_id User ID
 * @return array|null User data or null if not found
 */
function getUserById($user_id) {
    $conn = getConnection();
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Get student profile by user ID
 * @param int $user_id User ID
 * @return array|null Student profile data or null if not found
 */
function getStudentProfile($user_id) {
    $conn = getConnection();
    $query = "SELECT * FROM student_profiles WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Format date to readable format
 * @param string $date Date string
 * @param string $format Format string (default: 'F j, Y')
 * @return string Formatted date
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Get student allowance claim status
 * @param int $user_id User ID
 * @param int $allowance_id Allowance schedule ID
 * @return bool True if claimed, false otherwise
 */
function hasClaimedAllowance($user_id, $allowance_id) {
    $conn = getConnection();
    $query = "SELECT id FROM student_allowance_claims WHERE user_id = ? AND allowance_schedule_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $allowance_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}