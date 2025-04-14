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

// Handle year level filter
$year_level_filter = '';
if (isset($_GET['year_level']) && !empty($_GET['year_level'])) {
    $year_level_filter = trim($_GET['year_level']);
}

// Get allowance schedules
$allowance_schedules = [];

try {
    // Check if allowance_schedule table exists
    $table_check = $db->query("SHOW TABLES LIKE 'allowance_schedule'");
    
    if ($table_check->num_rows == 0) {
        // Create the table if it doesn't exist
        $db->query("CREATE TABLE IF NOT EXISTS allowance_schedule (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            cashier_no VARCHAR(50),
            payroll_no VARCHAR(50),
            period VARCHAR(100) NOT NULL,
            venue VARCHAR(255) NOT NULL,
            schedule DATETIME NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'released', 'cancelled') DEFAULT 'pending',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )");
    }
    
    // Base query for allowance schedules
    $query = "SELECT a.id, a.cashier_no, a.payroll_no, a.period, a.venue, a.schedule, a.amount, a.status, 
             u.id as student_id, u.first_name, u.last_name, u.middle_name, u.school,
             sp.year_level
             FROM allowance_schedule a
             JOIN users u ON a.student_id = u.id
             LEFT JOIN student_profiles sp ON u.id = sp.user_id";
    
    // Initialize where clause flag
    $whereAdded = false;
    
    // Add search condition if search query is provided
    if (!empty($search_query)) {
        $query .= " WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR a.period LIKE ? OR a.venue LIKE ?)"; 
        $whereAdded = true;
    }
    
    // Add first name filter
    if (!empty($first_name_filter)) {
        $query .= $whereAdded ? " AND" : " WHERE";
        $query .= " u.first_name LIKE ?";
        $whereAdded = true;
    }
    
    // Add last name filter
    if (!empty($last_name_filter)) {
        $query .= $whereAdded ? " AND" : " WHERE";
        $query .= " u.last_name LIKE ?";
        $whereAdded = true;
    }
    
    // Add year level filter
    if (!empty($year_level_filter)) {
        $query .= $whereAdded ? " AND" : " WHERE";
        $query .= " sp.year_level = ?";
        $whereAdded = true;
    }
    
    // Add status filter condition
    if ($filter !== 'all') {
        $query .= $whereAdded ? " AND" : " WHERE";
        $query .= " a.status = ?";
        $whereAdded = true;
    }
    
    $query .= " ORDER BY a.schedule ASC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $types = "";
    $params = [];
    
    // Add search parameters if provided
    if (!empty($search_query)) {
        $search_param = "%$search_query%";
        $types .= "ssss";
        $params[] = $search_param;
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
    
    // Add year level filter parameter
    if (!empty($year_level_filter)) {
        $types .= "i";
        $params[] = $year_level_filter;
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
    
    while ($row = $result->fetch_assoc()) {
        $allowance_schedules[] = $row;
    }
    
} catch (Exception $e) {
    $error_message = "Error retrieving allowance schedules: " . $e->getMessage();
    error_log("Error in allowance schedule page: " . $e->getMessage());
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $allowance_id = $_POST['allowance_id'] ?? 0;
    
    if ($allowance_id) {
        try {
            $stmt = $db->prepare("DELETE FROM allowance_schedule WHERE id = ?");
            $stmt->bind_param("i", $allowance_id);
            
            if ($stmt->execute()) {
                $success_message = "Allowance schedule has been deleted successfully.";
                // Refresh the page to show updated list
                header("Location: allowance_schedule.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
                exit;
            } else {
                $error_message = "Failed to delete allowance schedule. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error deleting allowance schedule: " . $e->getMessage());
        }
    }
}

// Pagination
$items_per_page = 10;
$total_items = count($allowance_schedules);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get allowance schedules for current page
$current_allowances = array_slice($allowance_schedules, $offset, $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allowance Releasing Schedule - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
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
        
        .allowance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .allowance-table th, .allowance-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .allowance-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .allowance-table tr:hover {
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
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-released {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
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
        
        .edit-btn {
            background-color: #007bff;
            color: white;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: white;
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
        
        .no-allowances {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .delete-all-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin-left: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>ALLOWANCE RELEASING SCHEDULE</h1>
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
                <a href="add_allowance.php" class="add-btn"><i class="fas fa-plus"></i> Add New Allowance</a>
                <a href="#" class="delete-all-btn" id="deleteAllBtn"><i class="fas fa-trash"></i> Delete All</a>
            </div>
            
            <div class="search-filter-container">
                <form action="" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search student name, period, or venue..." value="<?php echo htmlspecialchars($search_query); ?>">
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
                            <select name="year_level">
                                <option value="">All Year Levels</option>
                                <option value="1" <?php echo $year_level_filter === '1' ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2" <?php echo $year_level_filter === '2' ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3" <?php echo $year_level_filter === '3' ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4" <?php echo $year_level_filter === '4' ? 'selected' : ''; ?>>4th Year</option>
                                <option value="5" <?php echo $year_level_filter === '5' ? 'selected' : ''; ?>>5th Year</option>
                            </select>
                        </div>
                        
                        <div>
                            <select name="filter">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="released" <?php echo $filter === 'released' ? 'selected' : ''; ?>>Released</option>
                                <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <button type="submit">Apply Filters</button>
                    </form>
                </div>
            </div>
            
            <?php if (empty($current_allowances)): ?>
                <div class="no-allowances">
                    <i class="fas fa-money-bill-wave fa-3x" style="color: #6c757d; margin-bottom: 15px;"></i>
                    <h3>No allowance schedules found</h3>
                    <p>There are no allowance schedules matching your criteria.</p>
                </div>
            <?php else: ?>
                <table class="allowance-table">
                    <thead>
                        <tr>
                            <th>NAME OF SCHOLAR</th>
                            <th>CASHIER NO.</th>
                            <th>PAYROLL NO.</th>
                            <th>PERIOD</th>
                            <th>VENUE</th>
                            <th>SCHEDULE</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($current_allowances as $allowance): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $student_name = htmlspecialchars($allowance['first_name']) . ' ';
                                    if (!empty($allowance['middle_name'])) {
                                        $student_name .= htmlspecialchars($allowance['middle_name'][0]) . '. ';
                                    }
                                    $student_name .= htmlspecialchars($allowance['last_name']);
                                    echo $student_name;
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($allowance['cashier_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($allowance['payroll_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($allowance['period']); ?></td>
                                <td><?php echo htmlspecialchars($allowance['venue']); ?></td>
                                <td><?php echo date('F j, Y - g:ia', strtotime($allowance['schedule'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_allowance.php?id=<?php echo $allowance['id']; ?>" class="edit-btn">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this allowance schedule?');">
                                            <input type="hidden" name="allowance_id" value="<?php echo $allowance['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php $counter++; ?>
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
                        if (!empty($year_level_filter)) $query_params['year_level'] = urlencode($year_level_filter);
                        
                        $query_string = '';
                        foreach ($query_params as $key => $value) {
                            $query_string .= '&' . $key . '=' . $value;
                        }
                        ?>
                        
                        <?php if ($current_page > 1): ?>
                            <a href="?page=1<?php echo $query_string; ?>">&laquo;</a>
                            <a href="?page=<?php echo $current_page - 1; ?><?php echo $query_string; ?>">&lsaquo;</a>
                        <?php else: ?>
                            <span class="disabled">&laquo;</span>
                            <span class="disabled">&lsaquo;</span>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <?php if ($i == $current_page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?><?php echo $query_string; ?>">&rsaquo;</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>">&raquo;</a>
                        <?php else: ?>
                            <span class="disabled">&rsaquo;</span>
                            <span class="disabled">&raquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Confirm delete all action
        document.getElementById('deleteAllBtn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete all allowance schedules? This action cannot be undone.')) {
                // Create a form and submit it
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_all_allowances.php';
                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>
</body>
</html>