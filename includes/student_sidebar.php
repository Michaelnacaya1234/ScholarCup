<?php
// Student Sidebar navigation component for Scholar system
// This file is included in student pages across the system

// Determine current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure user role is set
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../assets/images/logo.png" alt="CSO Logo" onerror="this.src='../assets/images/default-logo.png'; this.onerror='';"> 
            <h2>CSO Scholar</h2>
        </div>
        <button type="button" id="sidebarCollapseBtn" class="sidebar-toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> <span class="menu-text">Dashboard</span>
        </a>
        <a href="student_status.php" class="sidebar-menu-item <?php echo ($current_page == 'student_status.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> <span class="menu-text">My Status</span>
        </a>
        <a href="submission_form.php" class="sidebar-menu-item <?php echo ($current_page == 'submission_form.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-upload"></i> <span class="menu-text">Submit Documents</span>
        </a>
        <a href="calendar.php" class="sidebar-menu-item <?php echo ($current_page == 'calendar.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> <span class="menu-text">Calendar</span>
        </a>
        <a href="announcements.php" class="sidebar-menu-item <?php echo ($current_page == 'announcements.php') ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i> <span class="menu-text">Announcements</span>
        </a>
        <a href="return_service.php" class="sidebar-menu-item <?php echo ($current_page == 'return_service.php') ? 'active' : ''; ?>">
            <i class="fas fa-hands-helping"></i> <span class="menu-text">Return Service</span>
        </a>
        <a href="allowance_schedule.php" class="sidebar-menu-item <?php echo ($current_page == 'allowance_schedule.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> <span class="menu-text">Allowance</span>
        </a>
        <a href="profile.php" class="sidebar-menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> <span class="menu-text">Profile</span>
        </a>
        <a href="inbox.php" class="sidebar-menu-item <?php echo ($current_page == 'inbox.php') ? 'active' : ''; ?>">
            <i class="fas fa-inbox"></i> <span class="menu-text">Inbox</span>
        </a>
        <a href="../logout.php" class="sidebar-menu-item">
            <i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span>
        </a>
    </div>
</div>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- Add the CSS for collapsible sidebar -->
<style>
    /* Sidebar Toggle Button Styles */
    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-toggle-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Collapsible Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100vh;
        background-color: #1a237e;
        color: #fff;
        overflow-y: auto;
        z-index: 1000;
        transition: all 0.3s ease;
    }
    
    .sidebar.collapsed {
        width: 70px;
    }
    
    .sidebar.collapsed .menu-text {
        display: none;
    }
    
    .sidebar.collapsed .sidebar-logo h2 {
        display: none;
    }
    
    .sidebar-menu-item {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        transition: background-color 0.3s;
    }
    
    .sidebar-menu-item i {
        margin-right: 15px;
        width: 20px;
        text-align: center;
    }
    
    .sidebar.collapsed .sidebar-menu-item {
        padding: 12px 0;
        justify-content: center;
    }
    
    .sidebar.collapsed .sidebar-menu-item i {
        margin-right: 0;
    }
    
    /* Main Content Adjustments */
    .main-content {
        margin-left: 250px;
        transition: margin-left 0.3s ease;
    }
    
    body.sidebar-collapsed .main-content {
        margin-left: 70px;
    }
    
    /* Mobile Styles */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        
        .sidebar.mobile-visible {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    }
</style>

<!-- Add the JavaScript for sidebar functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggleBtn = document.getElementById('sidebarCollapseBtn');
    const mainContent = document.querySelector('.main-content');
    const body = document.body;
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Check for saved sidebar state
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true') {
        sidebar.classList.add('collapsed');
        body.classList.add('sidebar-collapsed');
    }
    
    // Toggle sidebar on button click
    sidebarToggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        
        // Handle mobile sidebar
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('mobile-visible');
            sidebarOverlay.classList.toggle('active');
        }
        
        // Save sidebar state
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });
    
    // Close sidebar when clicking overlay (mobile)
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-visible');
        sidebarOverlay.classList.remove('active');
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
            
            if (sidebar.classList.contains('mobile-visible')) {
                sidebarOverlay.classList.add('active');
            }
        } else {
            sidebarOverlay.classList.remove('active');
            sidebar.classList.remove('mobile-visible');
            
            // Restore collapsed state on desktop
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                body.classList.add('sidebar-collapsed');
            }
        }
    });
});
</script>