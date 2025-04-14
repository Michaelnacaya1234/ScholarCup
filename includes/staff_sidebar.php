<?php
// Staff sidebar navigation component for Scholar system
// This file is included in staff pages to ensure consistent navigation

// Determine current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../student/CSO-logo.png" alt="CSO Logo">
            <h2>CSO</h2>
        </div>
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> <span>Home</span>
        </a>
        <a href="students_status.php" class="sidebar-menu-item <?php echo ($current_page == 'students_status.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> <span>Students Scholar Status</span>
        </a>
        <a href="announcements.php" class="sidebar-menu-item <?php echo ($current_page == 'announcements.php') ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i> <span>Announcement</span>
        </a>
        <a href="students_submission.php" class="sidebar-menu-item <?php echo ($current_page == 'students_submission.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> <span>Students Scholar Submission List</span>
        </a>
        <a href="return_service.php" class="sidebar-menu-item <?php echo ($current_page == 'return_service.php') ? 'active' : ''; ?>">
            <i class="fas fa-hands-helping"></i> <span>Return Service Activity</span>
        </a>
        <a href="allowance_schedule.php" class="sidebar-menu-item <?php echo ($current_page == 'allowance_schedule.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> <span>Allowance Releasing Schedule</span>
        </a>
        <a href="funds_management.php" class="sidebar-menu-item <?php echo ($current_page == 'funds_management.php') ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i> <span>Funds Management</span>
        </a>
        <a href="financial_reports.php" class="sidebar-menu-item <?php echo ($current_page == 'financial_reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> <span>Financial Reports</span>
        </a>
        <a href="profile.php" class="sidebar-menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> <span>Profile</span>
        </a>
        <a href="student_concerns.php" class="sidebar-menu-item <?php echo ($current_page == 'student_concerns.php') ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i> <span>Student Scholar Concern</span>
        </a>
        <a href="../logout.php" class="sidebar-menu-item">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>

<!-- Overlay for mobile sidebar -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<style>
/* Sidebar styles */
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background-color: #1E3A8A;
    color: #fff;
    transition: all 0.3s ease;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 15px;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    padding: 20px 15px;
}

.sidebar-logo img {
    width: 40px;
    height: 40px;
    margin-right: 10px;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
}

.sidebar-menu {
    padding: 10px 0;
}

.sidebar-menu-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #fff;
    text-decoration: none;
    transition: background-color 0.3s;
}

.sidebar-menu-item:hover, .sidebar-menu-item.active {
    background-color: #152B5E;
}

.sidebar-menu-item i {
    font-size: 1.2rem;
    min-width: 30px;
}

.sidebar-menu-item span {
    transition: opacity 0.3s, visibility 0.3s;
    white-space: nowrap;
}

.sidebar.collapsed .sidebar-menu-item span {
    opacity: 0;
    visibility: hidden;
}

.main-content {
    margin-left: 250px;
    padding: 20px;
    transition: margin-left 0.3s ease;
}

.main-content.expanded {
    margin-left: 70px;
}

/* Mobile sidebar overlay */
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

/* Mobile responsive styles */
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
    
    .sidebar-overlay.visible {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Function to check and set initial sidebar state from localStorage
    function initSidebar() {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
        
        // Handle mobile view
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('mobile-view');
            mainContent.classList.remove('expanded');
        }
    }
    
    // Toggle sidebar collapse state
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        
        // For mobile view, toggle visibility instead of collapse
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('mobile-visible');
            sidebarOverlay.classList.toggle('visible');
        } else {
            // Save state to localStorage for desktop
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    }
    
    // Event listeners
    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', toggleSidebar);
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('mobile-view');
            mainContent.classList.remove('expanded');
        } else {
            sidebar.classList.remove('mobile-view', 'mobile-visible');
            sidebarOverlay.classList.remove('visible');
            
            // Restore desktop state
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        }
    });
    
    // Initialize sidebar state
    initSidebar();
});
</script>