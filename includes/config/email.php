<?php
/**
 * Email service class for sending emails
 */
class EmailService {
    private static $instance = null;
    private $mailer;
    
    // Email configuration
    private $host = 'smtp.example.com'; // Replace with your SMTP server
    private $username = 'noreply@example.com'; // Replace with your email
    private $password = 'your_password'; // Replace with your password
    private $port = 587;
    private $from_email = 'noreply@example.com';
    private $from_name = 'CSO Scholar Management';
    
    private function __construct() {
        // In a real implementation, you would use PHPMailer or similar library
        // This is a simplified version for demonstration purposes
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Send verification code for password reset
     * 
     * @param string $email Recipient email
     * @param string $code Verification code
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendVerificationCode($email, $code) {
        $subject = 'Password Reset Verification Code';
        $message = "<html><body>";
        $message .= "<h2>Password Reset Request</h2>";
        $message .= "<p>You have requested to reset your password. Please use the following verification code:</p>";
        $message .= "<h3 style='background-color: #f0f0f0; padding: 10px; display: inline-block;'>$code</h3>";
        $message .= "<p>This code will expire in 1 hour.</p>";
        $message .= "<p>If you did not request this password reset, please ignore this email.</p>";
        $message .= "<p>Regards,<br>CSO Scholar Management Team</p>";
        $message .= "</body></html>";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send announcement notification
     * 
     * @param string $email Recipient email
     * @param string $title Announcement title
     * @param string $content Announcement content
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAnnouncementNotification($email, $title, $content) {
        $subject = 'New Announcement: ' . $title;
        $message = "<html><body>";
        $message .= "<h2>New Announcement</h2>";
        $message .= "<h3>$title</h3>";
        $message .= "<div>$content</div>";
        $message .= "<p>Login to your account to view more details.</p>";
        $message .= "<p>Regards,<br>CSO Scholar Management Team</p>";
        $message .= "</body></html>";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send allowance schedule notification
     * 
     * @param string $email Recipient email
     * @param string $title Schedule title
     * @param string $date Release date
     * @param float $amount Allowance amount
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAllowanceNotification($email, $title, $date, $amount) {
        $subject = 'Allowance Schedule: ' . $title;
        $message = "<html><body>";
        $message .= "<h2>Allowance Schedule</h2>";
        $message .= "<h3>$title</h3>";
        $message .= "<p><strong>Release Date:</strong> $date</p>";
        $message .= "<p><strong>Amount:</strong> ₱" . number_format($amount, 2) . "</p>";
        $message .= "<p>Login to your account to view more details.</p>";
        $message .= "<p>Regards,<br>CSO Scholar Management Team</p>";
        $message .= "</body></html>";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message (HTML)
     * @return bool True if email sent successfully, false otherwise
     */
    private function sendEmail($to, $subject, $message) {
        try {
            // In a real implementation, you would use PHPMailer or similar library
            // For demonstration purposes, we'll just log the email and return true
            error_log("Email would be sent to: $to, Subject: $subject");
            
            // For development/testing, you can uncomment this to see the email content
            // error_log("Email content: $message");
            
            // In a real implementation, you would send the email here
            // For now, we'll just return true to simulate successful sending
            return true;
        } catch (Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}