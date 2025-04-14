<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$success_message = '';
$error_message = '';

// Handle search functionality
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Handle filter functionality
$filter = 'all';
if (isset($_GET['filter']) && !empty($_GET['filter'])) {
    $filter = $_GET['filter'];
}

// Handle first name filter
$first_name_filter = '';
if (isset($_GET['first_name']) && !empty($_GET['first_name'])) {
    $first_name_filter = trim($_GET['first_name']);
}

// Handle last name filter
$last_name_filter = '';
if (isset($_GET['last_name']) && !empty($_GET['last_name'])) {
    $last_name_filter = trim($_GET['last_name']);
}

// Handle department filter
$department_filter = '';
if (isset($_GET['department']) && !empty($_GET['department'])) {
    $department_filter = trim($_GET['department']);
}

// Get staff accounts
$staff_members = [];

try {
    // Base query for staff accounts
    $query = "SELECT u.id, u.email, u.first_name, u.middle_name, u.last_name, u.status, 
             sp.department, sp.position, sp.contact_number
             FROM users u
             LEFT JOIN staff_profiles sp ON u.id = sp.user_id
             WHERE u.role = 'staff'";
    
    // Initialize where clause flag
    $whereAdded = true; // Already added WHERE u.role = 'staff'
    
    // Add search condition if search query is provided
    if (!empty($search_query)) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)"; 
    }
    
    // Add first name filter
    if (!empty($first_name_filter)) {
        $query .= " AND u.first_name LIKE ?";
    }
    
    // Add last name filter
    if (!empty($last_name_filter)) {
        $query .= " AND u.last_name LIKE ?";
    }
    
    // Add department filter
    if (!empty($department_filter)) {
        $query .= " AND sp.department LIKE ?";
    }
    
    // Add status filter condition
    if ($filter !== 'all') {
        $query .= " AND u.status = ?";
    }
    
    $query .= " ORDER BY u.last_name ASC, u.first_name ASC";
    
    // For debugging
    error_log("Staff query: " . $query);
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $types = "";
    $params = [];
    
    // Add search parameters if provided
    if (!empty($search_query)) {
        $search_param = "%$search_query%";
        $types .= "sss";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Add first name filter parameter
    if (!empty($first_name_filter)) {
        $first_name_param = "%$first_name_filter%";
        $types .= "s";
        $params[] = $first_name_param;
    }
    
    // Add last name filter parameter
    if (!empty($last_name_filter)) {
        $last_name_param = "%$last_name_filter%";
        $types .= "s";
        $params[] = $last_name_param;
    }
    
    // Add department filter parameter
    if (!empty($department_filter)) {
        $department_param = "%$department_filter%";
        $types .= "s";
        $params[] = $department_param;
    }
    
    // Add status filter parameter
    if ($filter !== 'all') {
        $types .= "s";
        $params[] = $filter;
    }
    
    // Only bind parameters if there are any
    if (!empty($params)) {
        // Create array with types as first element
        $bind_params = array_merge([$types], $params);
        
        // Use call_user_func_array to bind parameters dynamically
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Query failed: " . $db->error);
    }
    
    error_log("Found " . $result->num_rows . " staff members");
    
    while ($row = $result->fetch_assoc()) {
        $staff_members[] = $row;
    }
    
} catch (Exception $e) {
    $error_message = "Error retrieving staff accounts: " . $e->getMessage();
    error_log("Error in staff accounts page: " . $e->getMessage());
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $staff_id = $_POST['staff_id'] ?? 0;
    
    if ($staff_id) {
        try {
            // Start transaction
            $db->begin_transaction();
            
            // Get staff data before deletion
            $stmt = $db->prepare("SELECT u.*, sp.* FROM users u 
                                LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
                                WHERE u.id = ? AND u.role = 'staff'");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $staff_data = $stmt->get_result()->fetch_assoc();
            
            if ($staff_data) {
                // Archive user data
                $stmt = $db->prepare("INSERT INTO archived_users 
                    (user_id, email, first_name, middle_name, last_name, role) 
                    VALUES (?, ?, ?, ?, ?, 'staff')");
                $stmt->bind_param("issss", $staff_id, $staff_data['email'], 
                    $staff_data['first_name'], $staff_data['middle_name'], 
                    $staff_data['last_name']);
                $stmt->execute();

                // Archive staff profile
                $stmt = $db->prepare("INSERT INTO archived_staff_profiles 
                    (original_user_id, staff_id, department, position, contact_number) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $staff_id, $staff_data['staff_id'], 
                    $staff_data['department'], $staff_data['position'], 
                    $staff_data['contact_number']);
                $stmt->execute();

                // Delete from staff_profiles
                $stmt = $db->prepare("DELETE FROM staff_profiles WHERE user_id = ?");
                $stmt->bind_param("i", $staff_id);
                $stmt->execute();
                
                // Delete from users
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $staff_id);
                $stmt->execute();

                $db->commit();
                $success_message = "Staff account has been archived successfully.";
            }
            
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollback();
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error deleting staff account: " . $e->getMessage());
        }
    }
}

// Handle status change action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $staff_id = $_POST['staff_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';
    
    if ($staff_id && in_array($new_status, ['active', 'inactive'])) {
        try {
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $staff_id);
            
            if ($stmt->execute()) {
                $status_text = $new_status === 'active' ? 'activated' : 'deactivated';
                $success_message = "Staff account has been successfully " . $status_text . ".";
                // Refresh the page to show updated list
                header("Location: staff_accounts.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
                exit;
            } else {
                $error_message = "Failed to update staff status. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error updating staff status: " . $e->getMessage());
        }
    }
}

// Pagination
$items_per_page = 10;
$total_items = count($staff_members);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get staff members for current page
$current_staff = array_slice($staff_members, $offset, $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Accounts - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .search-filter-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
            background-color: #f0f2f5;
            padding: 15px;
            border-radius: 8px;
        }
        
        .advanced-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        
        .advanced-filters input,
        .advanced-filters select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 150px;
        }
        
        .advanced-filters button {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .advanced-filters button:hover {
            background-color: #0069d9;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: 4px;
            padding: 5px 10px;
            width: 300px;
        }
        
        .search-box input {
            border: none;
            padding: 8px;
            width: 100%;
            outline: none;
        }
        
        .search-box button {
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }
        
        .filter-dropdown select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            outline: none;
        }
        
        .staff-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .staff-table th, .staff-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .staff-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .staff-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-buttons button, .action-buttons a {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .view-btn {
            background-color: #17a2b8;
            color: white;
        }
        
        .edit-btn {
            background-color: #007bff;
            color: white;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .activate-btn {
            background-color: #28a745;
            color: white;
        }
        
        .deactivate-btn {
            background-color: #ffc107;
            color: #212529;
        }
        
        .add-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .disabled {
            color: #6c757d;
            pointer-events: none;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .no-staff {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .archive-btn {
            background-color: #dc6b6b; /* Changed from #6c757d to a softer red */
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .archive-btn:hover {
            background-color: #c85f5f; /* Changed hover color to match */
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>STAFF ACCOUNTS</h1>
                <div class="header-actions">
                    <a href="archive.php?type=staff" class="archive-btn">
                        <i class="fas fa-archive"></i> Archive
                    </a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="action-bar">
                <a href="registration.php?type=staff" class="add-btn"><i class="fas fa-plus"></i> Add New Staff</a>
            </div>
            
            <div class="search-filter-container">
                <form action="" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search by name, email, or staff ID..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="advanced-filters">
                    <form action="" method="GET" class="advanced-filters">
                        <?php if (!empty($search_query)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php endif; ?>
                        
                        <div>
                            <input type="text" name="first_name" placeholder="First name starts with..." value="<?php echo htmlspecialchars($first_name_filter); ?>">
                        </div>
                        
                        <div>
                            <input type="text" name="last_name" placeholder="Last name starts with..." value="<?php echo htmlspecialchars($last_name_filter); ?>">
                        </div>
                        
                        <div>
                            <input type="text" name="department" placeholder="Department contains..." value="<?php echo htmlspecialchars($department_filter); ?>">
                        </div>
                        
                        <div>
                            <select name="filter">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <button type="submit">Apply Filters</button>
                    </form>
                </div>
            </div>
            
            <?php if (empty($current_staff)): ?>
                <div class="no-staff">
                    <i class="fas fa-user-tie fa-3x" style="color: #6c757d; margin-bottom: 15px;"></i>
                    <h3>No staff accounts found</h3>
                    <p>There are no staff accounts matching your criteria.</p>
                </div>
            <?php else: ?>
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>DEPARTMENT</th>
                            <th>POSITION</th>
                            <th>CONTACT NUMBER</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_staff as $staff): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $staff_name = htmlspecialchars($staff['first_name']) . ' ';
                                    if (!empty($staff['middle_name'])) {
                                        $staff_name .= htmlspecialchars($staff['middle_name'][0]) . '. ';
                                    }
                                    $staff_name .= htmlspecialchars($staff['last_name']);
                                    echo $staff_name;
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                <td><?php echo htmlspecialchars($staff['department'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($staff['position'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($staff['contact_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($staff['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($staff['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_staff.php?id=<?php echo $staff['id']; ?>" class="view-btn">View</a>
                                        <a href="edit_staff.php?id=<?php echo $staff['id']; ?>" class="edit-btn">Edit</a>
                                        
                                        <?php if ($staff['status'] === 'active'): ?>
                                            <form method="POST" class="status-form" style="display: inline;">
                                                <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                                <input type="hidden" name="new_status" value="inactive">
                                                <input type="hidden" name="action" value="change_status">
                                                <button type="submit" class="deactivate-btn">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="status-form" style="display: inline;">
                                                <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                                <input type="hidden" name="new_status" value="active">
                                                <input type="hidden" name="action" value="change_status">
                                                <button type="submit" class="activate-btn">Activate</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this staff account? This action cannot be undone.')">
                                            <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php 
                        // Build query string with all filters
                        $query_params = [];
                        if (!empty($search_query)) $query_params['search'] = urlencode($search_query);
                        if ($filter !== 'all') $query_params['filter'] = urlencode($filter);
                        if (!empty($first_name_filter)) $query_params['first_name'] = urlencode($first_name_filter);
                        if (!empty($last_name_filter)) $query_params['last_name'] = urlencode($last_name_filter);
                        if (!empty($department_filter)) $query_params['department'] = urlencode($department_filter);
                        
                        $query_string = http_build_query($query_params);
                        $query_prefix = !empty($query_string) ? "?$query_string&" : "?";
                        
                        // Previous page link
                        if ($current_page > 1) {
                            echo "<a href=\"" . $query_prefix . "page=" . ($current_page - 1) . "\">Previous</a>";
                        } else {
                            echo "<span class=\"disabled\">Previous</span>";
                        }
                        
                        // Page numbers
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $current_page) {
                                echo "<span class=\"active\">$i</span>";
                            } else {
                                echo "<a href=\"" . $query_prefix . "page=$i\">$i</a>";
                            }
                        }
                        
                        // Next page link
                        if ($current_page < $total_pages) {
                            echo "<a href=\"" . $query_prefix . "page=" . ($current_page + 1) . "\">Next</a>";
                        } else {
                            echo "<span class=\"disabled\">Next</span>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Toggle advanced filters visibility
        document.addEventListener('DOMContentLoaded', function() {
            const toggleFiltersBtn = document.getElementById('toggleFiltersBtn');
            const advancedFilters = document.getElementById('advancedFilters');
            
            if (toggleFiltersBtn && advancedFilters) {
                toggleFiltersBtn.addEventListener('click', function() {
                    advancedFilters.style.display = advancedFilters.style.display === 'none' ? 'flex' : 'none';
                    toggleFiltersBtn.innerHTML = advancedFilters.style.display === 'none' ? 
                        '<i class="fas fa-filter"></i> Show Advanced Filters' : 
                        '<i class="fas fa-filter"></i> Hide Advanced Filters';
                });
            }
        });
        
        // Add event listener for status change forms
        document.querySelectorAll('.status-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const newStatus = formData.get('new_status');
                const actionText = newStatus === 'active' ? 'activate' : 'deactivate';
                
                // Check if user wants to skip confirmations
                if (localStorage.getItem('skipStatusConfirmation')) {
                    submitStatusChange(formData, newStatus);
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to ${actionText} this staff account?`,
                    icon: 'warning',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, proceed!',
                    denyButtonText: 'Yes, and don\'t ask again',
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitStatusChange(formData, newStatus);
                    } else if (result.isDenied) {
                        localStorage.setItem('skipStatusConfirmation', 'true');
                        submitStatusChange(formData, newStatus);
                    }
                });
            });
        });

        // Function to handle status change submission
        function submitStatusChange(formData, newStatus) {
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Staff account has been ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully.`,
                    showConfirmButton: false,
                    timer: 3000
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(error => {
                Swal.fire(
                    'Error!',
                    'Something went wrong.',
                    'error'
                );
            });
        }
    </script>
</body>
</html>
