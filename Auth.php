<?php
/**
 * Authentication class for handling user login, registration, and session management
 */
class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Authenticate a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array Authentication result with success status and user data or error message
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, email, password, first_name, last_name, role, status FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
            
            $user = $result->fetch_assoc();
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                return ['success' => false, 'error' => 'Your account is inactive. Please contact administrator.'];
            }
            
            // Verify password using secure password_verify function
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Update last login time (optional)
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $user['role']
                ]
            ];
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'An error occurred during login. Please try again.'];
        }
    }
    
    /**
     * Log out the current user
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }
    
    /**
     * Register a new user
     * 
     * @param array $userData User data including email, password, first_name, last_name, role
     * @return array Registration result with success status and user ID or error message
     */
    public function register($userData) {
        try {
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $userData['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => false, 'error' => 'Email already exists'];
            }
            
            // Hash password using secure password_hash function
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->db->prepare("INSERT INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', 
                $userData['email'], 
                $hashedPassword, 
                $userData['first_name'], 
                $userData['last_name'], 
                $userData['role']
            );
            $stmt->execute();
            
            if ($stmt->affected_rows === 1) {
                $userId = $this->db->getLastId();
                return ['success' => true, 'user_id' => $userId];
            } else {
                return ['success' => false, 'error' => 'Failed to register user'];
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'An error occurred during registration. Please try again.'];
        }
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in, false otherwise
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user role
     * 
     * @return string|null User role or null if not logged in
     */
    public function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Update last login time for a user
     * 
     * @param int $userId User ID
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }
    
    /**
     * Initiate password reset process
     * 
     * @param string $email User email
     * @param string $code Verification code
     * @return bool True if reset initiated successfully, false otherwise
     */
    public function initiatePasswordReset($email, $code) {
        try {
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Return true for security (prevents username enumeration)
                return true;
            }
            
            // Delete any existing reset tokens for this email
            $stmt = $this->db->prepare("DELETE FROM password_reset WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            
            // Create token and expiry (1 hour from now)
            $token = md5($code . time());
            $expiry = date('Y-m-d H:i:s', time() + 3600);
            
            // Insert reset token
            $stmt = $this->db->prepare("INSERT INTO password_reset (email, token, expiry) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $email, $token, $expiry);
            $stmt->execute();
            
            return $stmt->affected_rows === 1;
        } catch (Exception $e) {
            error_log('Password reset initiation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify password reset code
     * 
     * @param string $email User email
     * @param string $code Verification code
     * @return bool True if code is valid, false otherwise
     */
    public function verifyResetCode($email, $code) {
        try {
            // Get reset token
            $stmt = $this->db->prepare("SELECT token, expiry FROM password_reset WHERE email = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false;
            }
            
            $reset = $result->fetch_assoc();
            
            // Check if token has expired
            if (strtotime($reset['expiry']) < time()) {
                return false;
            }
            
            // Verify code (would need to implement this logic based on how codes are stored/verified)
            // For this example, we'll assume the code is stored in the session
            return isset($_SESSION['reset_code']) && $_SESSION['reset_code'] === $code;
        } catch (Exception $e) {
            error_log('Reset code verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset user password
     * 
     * @param string $email User email
     * @param string $newPassword New password
     * @return bool True if password reset successfully, false otherwise
     */
    public function resetPassword($email, $newPassword) {
        try {
            // Hash new password using secure password_hash function
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param('ss', $hashedPassword, $email);
            $stmt->execute();
            
            if ($stmt->affected_rows === 1) {
                // Delete reset token
                $stmt = $this->db->prepare("DELETE FROM password_reset WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            return false;
        }
    }
}