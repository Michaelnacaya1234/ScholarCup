<?php
/**
 * Login check and redirection functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Redirect user to appropriate dashboard based on role
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: /Scholar/index.php');
        exit;
    }
    
    $role = $_SESSION['role'] ?? '';
    $base_url = '/Scholar/';
    
    switch ($role) {
        case 'admin':
            header('Location: ' . $base_url . 'admin/dashboard.php');
            break;
        case 'staff':
            header('Location: ' . $base_url . 'staff/dashboard.php');
            break;
        case 'student':
            header('Location: ' . $base_url . 'student/dashboard.php');
            break;
        default:
            // If role is not recognized, log out and redirect to login
            session_destroy();
            header('Location: ' . $base_url . 'index.php?error=invalid_role');
            break;
    }
    exit;
}

/**
 * Check if user has required role
 * 
 * @param string|array $requiredRoles Required role(s)
 * @return bool True if user has required role, false otherwise
 */
function hasRole($requiredRoles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? '';
    
    if (is_array($requiredRoles)) {
        return in_array($userRole, $requiredRoles);
    }
    
    return $userRole === $requiredRoles;
}

/**
 * Verify user has required role or redirect
 * 
 * @param string|array $requiredRoles Required role(s)
 * @param string $redirectUrl URL to redirect to if user doesn't have required role
 */
function requireRole($requiredRoles, $redirectUrl = 'index.php') {
    if (!hasRole($requiredRoles)) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Check login credentials
 * 
 * @param string $email User email
 * @param string $password User password
 * @param string $remember Remember me option
 * @param string $captcha Captcha value
 * @return array Login result with success status and user data or error message
 */
function checkLogin($email, $password, $remember, $captcha) {
    try {
        // Include database connection using absolute paths
        $root_path = $_SERVER['DOCUMENT_ROOT'] . '/Scholar/';
        require_once $root_path . 'includes/config/database.php';
        require_once $root_path . 'includes/Auth.php';
        
        $db = Database::getInstance();
        $auth = new Auth($db);
        
        // Authenticate user
        $result = $auth->login($email, $password);
        
        // If login successful and remember me is checked, set cookie
        if ($result['success'] && $remember) {
            // Set cookie for 30 days
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/');
            
            // Store token in database (would need to implement this)
            // storeRememberToken($result['user']['id'], $token);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('Login check error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'An error occurred during login. Please try again.'];
    }
}