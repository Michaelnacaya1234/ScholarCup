<?php
// Admin sidebar navigation component for Scholar system
// This file is included in admin pages to ensure consistent navigation

// Determine current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="../student/CSO-logo.png" alt="CSO Logo">
        <h2>CSO</h2>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="students_status.php" class="sidebar-menu-item <?php echo ($current_page == 'students_status.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> Students Scholar Status
        </a>
        <a href="announcements.php" class="sidebar-menu-item <?php echo ($current_page == 'announcements.php') ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i> Announcement
        </a>
        <a href="students_submission.php" class="sidebar-menu-item <?php echo ($current_page == 'students_submission.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Students Scholar Submission List
        </a>

        <a href="allowance_schedule.php" class="sidebar-menu-item <?php echo ($current_page == 'allowance_schedule.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> Allowance Releasing Schedule
        </a>
        <a href="funds_management.php" class="sidebar-menu-item <?php echo ($current_page == 'funds_management.php') ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i> Funds Management
        </a>
        <a href="financial_reports.php" class="sidebar-menu-item <?php echo ($current_page == 'financial_reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Financial Reports
        </a>
        <a href="archive.php" class="sidebar-menu-item <?php echo ($current_page == 'archive.php') ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i> Archive
        </a>
        <a href="staff_accounts.php" class="sidebar-menu-item <?php echo ($current_page == 'staff_accounts.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i> Staff Account
        </a>
        <a href="student_accounts.php" class="sidebar-menu-item <?php echo ($current_page == 'student_accounts.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> Student Account
        </a>
        <a href="student_concerns.php" class="sidebar-menu-item <?php echo ($current_page == 'student_concerns.php') ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i> Student Scholar Concern
        </a>

    </div>
</div>