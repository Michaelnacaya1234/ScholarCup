<?php
// Sidebar navigation component for Scholar system
// This file is included in various pages across the system

// Determine current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Determine user role for showing appropriate menu items
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="CSO Logo" onerror="this.src='../assets/images/default-logo.png'; this.onerror='';"> 
        <h2>CSO Scholar</h2>
    </div>
    
    <div class="sidebar-menu">
        <?php if($user_role == 'student'): ?>
            <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="student_status.php" class="sidebar-menu-item <?php echo ($current_page == 'student_status.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> My Status
            </a>
            <a href="submission_form.php" class="sidebar-menu-item <?php echo ($current_page == 'submission_form.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-upload"></i> Submit Documents
            </a>
            <a href="calendar.php" class="sidebar-menu-item <?php echo ($current_page == 'calendar.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> Calendar
            </a>
            <a href="announcements.php" class="sidebar-menu-item <?php echo ($current_page == 'announcements.php') ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            <a href="return_service.php" class="sidebar-menu-item <?php echo ($current_page == 'return_service.php') ? 'active' : ''; ?>">
                <i class="fas fa-hands-helping"></i> Return Service
            </a>
            <a href="allowance_schedule.php" class="sidebar-menu-item <?php echo ($current_page == 'allowance_schedule.php') ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i> Allowance
            </a>
            <a href="profile.php" class="sidebar-menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="inbox.php" class="sidebar-menu-item <?php echo ($current_page == 'inbox.php') ? 'active' : ''; ?>">
                <i class="fas fa-inbox"></i> Inbox
            </a>
        <?php elseif($user_role == 'admin'): ?>
            <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="students_status.php" class="sidebar-menu-item <?php echo ($current_page == 'students_status.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Students Status
            </a>
            <a href="announcements_and_return_service.php" class="sidebar-menu-item <?php echo ($current_page == 'announcements_and_return_service.php' || $current_page == 'announcements.php' || $current_page == 'return_service.php') ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements & Return Service
            </a>
            <a href="students_submission.php" class="sidebar-menu-item <?php echo ($current_page == 'students_submission.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-upload"></i> Submissions
            </a>
            <a href="allowance_schedule.php" class="sidebar-menu-item <?php echo ($current_page == 'allowance_schedule.php') ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i> Allowance
            </a>
            <a href="archive.php" class="sidebar-menu-item <?php echo ($current_page == 'archive.php') ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i> Archive
            </a>
            <a href="staff_accounts.php" class="sidebar-menu-item <?php echo ($current_page == 'staff_accounts.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i> Staff Accounts
            </a>
            <a href="student_accounts.php" class="sidebar-menu-item <?php echo ($current_page == 'student_accounts.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Student Accounts
            </a>
            <a href="student_concerns.php" class="sidebar-menu-item <?php echo ($current_page == 'student_concerns.php') ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> Student Concerns
            </a>
            <a href="registration.php" class="sidebar-menu-item <?php echo ($current_page == 'registration.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Registration
            </a>
        <?php elseif($user_role == 'staff'): ?>
            <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="students_status.php" class="sidebar-menu-item <?php echo ($current_page == 'students_status.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Students Status
            </a>
            <a href="announcements_and_return_service.php" class="sidebar-menu-item <?php echo ($current_page == 'announcements_and_return_service.php' || $current_page == 'announcements.php' || $current_page == 'return_service.php') ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements & Return Service
            </a>
            <a href="students_submission.php" class="sidebar-menu-item <?php echo ($current_page == 'students_submission.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-upload"></i> Submissions
            </a>
            <a href="allowance_schedule.php" class="sidebar-menu-item <?php echo ($current_page == 'allowance_schedule.php') ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i> Allowance
            </a>
            <a href="profile.php" class="sidebar-menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="student_concerns.php" class="sidebar-menu-item <?php echo ($current_page == 'student_concerns.php') ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> Student Concerns
            </a>
        <?php endif; ?>
        
        <a href="../logout.php" class="sidebar-menu-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>