<?php
// Include database file if not already included
if (!class_exists('Database')) {
    require_once dirname(__FILE__) . '/config/database.php';
}

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get user data if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    // Get database connection
    $db = Database::getInstance();
    
    // Fetch user data
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_name = $row['first_name'] . ' ' . $row['last_name'];
    }
}
?>

<header class="header">
    <div class="header-right">
        <?php
        // Only show notification bell and messages on dashboard pages
        if ($current_page == 'dashboard.php' || $current_page == 'staff_dashboard.php') {
        ?>
            <!-- Notification Bell -->
            <div class="notification-icon">
                <a href="notifications.php">
                    <i class="fas fa-bell"></i>
                    <?php
                    // Check for unread notifications (placeholder - implement actual logic)
                    $unread_count = 0; // Replace with actual count from database
                    if ($unread_count > 0) {
                        echo "<span class='notification-badge'>$unread_count</span>";
                    }
                    ?>
                </a>
            </div>
            
            <!-- Messages Button -->
            <div class="messages-button">
                <a href="messages.php">
                    <i class="fas fa-envelope"></i> Messages
                </a>
            </div>
        <?php
        }
        ?>
        
        <!-- Logout Button (shown on all pages) -->
        <div class="logout-button">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<style>
/* Header Styles */
.header {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 10px 20px;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    height: 60px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification-icon a,
.messages-button a,
.logout-button a {
    color: #333;
    font-size: 1.2rem;
    position: relative;
    display: inline-block;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #ff4757;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: bold;
}

.notification-icon a:hover,
.messages-button a:hover,
.logout-button a:hover {
    color: #007bff;
}
</style>