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

// Handle course filter
$course_filter = '';
if (isset($_GET['course']) && !empty($_GET['course'])) {
    $course_filter = trim($_GET['course']);
}

// Get student accounts
$students = [];

try {
    // Base query for student accounts
    $query = "SELECT u.id, u.email, u.first_name, u.middle_name, u.last_name, u.status, 
             sp.course, sp.year_level, sp.contact_number
             FROM users u
             LEFT JOIN student_profiles sp ON u.id = sp.user_id
             WHERE u.role = 'student'";
    
    // For debugging
    error_log("Student query: " . $query);
    
    // Initialize where clause flag
    $whereAdded = true; // Already added WHERE u.role = 'student'
    
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
    
    // Add year level filter
    if (!empty($year_level_filter)) {
        $query .= " AND sp.year_level = ?";
    }
    
    // Add course filter
    if (!empty($course_filter)) {
        $query .= " AND sp.course LIKE ?";
    }
    
    // Add status filter condition
    if ($filter !== 'all') {
        $query .= " AND u.status = ?";
    }
    
    $query .= " ORDER BY u.last_name ASC, u.first_name ASC";
    
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
    
    // Add year level filter parameter
    if (!empty($year_level_filter)) {
        $types .= "i";
        $params[] = $year_level_filter;
    }
    
    // Add course filter parameter
    if (!empty($course_filter)) {
        $course_param = "%$course_filter%";
        $types .= "s";
        $params[] = $course_param;
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
    
    error_log("Found " . $result->num_rows . " students");
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
} catch (Exception $e) {
    $error_message = "Error retrieving student accounts: " . $e->getMessage();
    error_log("Error in student accounts page: " . $e->getMessage());
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $student_id = $_POST['student_id'] ?? 0;
    
    if ($student_id) {
        try {
            // Start transaction
            $db->begin_transaction();
            
            // Delete from student_profiles first (due to foreign key constraint)
            $stmt = $db->prepare("DELETE FROM student_profiles WHERE user_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            
            // Then delete from users table
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            
            if ($stmt->execute()) {
                // Commit transaction
                $db->commit();
                $success_message = "Student account has been deleted successfully.";
                // Refresh the page to show updated list
                header("Location: student_accounts.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
                exit;
            } else {
                // Rollback transaction
                $db->rollback();
                $error_message = "Failed to delete student account. Please try again.";
            }
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollback();
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error deleting student account: " . $e->getMessage());
        }
    }
}

// Handle status change action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $student_id = $_POST['student_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';
    
    if ($student_id && in_array($new_status, ['active', 'inactive'])) {
        try {
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $student_id);
            
            if ($stmt->execute()) {
                $status_text = $new_status === 'active' ? 'activated' : 'deactivated';
                $success_message = "Student account has been successfully " . $status_text . ".";
                // Refresh the page to show updated list
                header("Location: student_accounts.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
                exit;
            } else {
                $error_message = "Failed to update student status. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error updating student status: " . $e->getMessage());
        }
    }
}

// Pagination
$items_per_page = 10;
$total_items = count($students);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get students for current page
$current_students = array_slice($students, $offset, $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Accounts - Admin</title>
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
        
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .student-table th, .student-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .student-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .student-table tr:hover {
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
        
        .no-students {
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
                <h1>STUDENT ACCOUNTS</h1>
                <div class="header-actions">
                    <a href="archive.php?type=student" class="archive-btn">
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
                <a href="registration.php?type=student" class="add-btn"><i class="fas fa-plus"></i> Add New Student</a>
            </div>
            
            <div class="search-filter-container">
                <form action="" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search by name, email, or student ID..." value="<?php echo htmlspecialchars($search_query); ?>">
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
                            <input type="text" name="course" placeholder="Course contains..." value="<?php echo htmlspecialchars($course_filter); ?>">
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
            
            <?php if (empty($current_students)): ?>
                <div class="no-students">
                    <i class="fas fa-user-graduate fa-3x" style="color: #6c757d; margin-bottom: 15px;"></i>
                    <h3>No student accounts found</h3>
                    <p>There are no student accounts matching your criteria.</p>
                </div>
            <?php else: ?>
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>COURSE</th>
                            <th>YEAR LEVEL</th>
                            <th>CONTACT NUMBER</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_students as $student): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $student_name = htmlspecialchars($student['first_name']) . ' ';
                                    if (!empty($student['middle_name'])) {
                                        $student_name .= htmlspecialchars($student['middle_name'][0]) . '. ';
                                    }
                                    $student_name .= htmlspecialchars($student['last_name']);
                                    echo $student_name;
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($student['year_level'])) {
                                        $year_level = intval($student['year_level']);
                                        $suffix = '';
                                        switch ($year_level) {
                                            case 1: $suffix = 'st'; break;
                                            case 2: $suffix = 'nd'; break;
                                            case 3: $suffix = 'rd'; break;
                                            default: $suffix = 'th';
                                        }
                                        echo $year_level . $suffix . ' Year';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($student['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($student['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_student.php?id=<?php echo $student['id']; ?>" class="view-btn">View</a>
                                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="edit-btn">Edit</a>
                                        
                                        <?php if ($student['status'] === 'active'): ?>
                                            <form method="POST" class="status-form" style="display: inline;">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <input type="hidden" name="new_status" value="inactive">
                                                <input type="hidden" name="action" value="change_status">
                                                <button type="submit" class="deactivate-btn">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="status-form" style="display: inline;">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <input type="hidden" name="new_status" value="active">
                                                <input type="hidden" name="action" value="change_status">
                                                <button type="submit" class="activate-btn">Activate</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student account? This action cannot be undone.')">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
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
                        if (!empty($year_level_filter)) $query_params['year_level'] = urlencode($year_level_filter);
                        if (!empty($course_filter)) $query_params['course'] = urlencode($course_filter);
                        
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
                    text: `Do you want to ${actionText} this student account?`,
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
                    title: `Student account has been ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully.`,
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
