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
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

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

// Handle course filter
$course_filter = '';
if (isset($_GET['course']) && !empty($_GET['course'])) {
    $course_filter = trim($_GET['course']);
}

// Handle year level filter
$year_level_filter = '';
if (isset($_GET['year_level']) && !empty($_GET['year_level'])) {
    $year_level_filter = trim($_GET['year_level']);
}

// Get student concerns with student information
$concerns = [];

try {
    // Base query for student concerns with student information
    $query = "SELECT sc.id, sc.subject, sc.message, sc.status, sc.created_at, 
             u.id as user_id, u.first_name, u.middle_name, u.last_name, u.email,
             sp.course, sp.year_level, sp.contact_number
             FROM student_concerns sc
             JOIN users u ON sc.student_id = u.id
             LEFT JOIN student_profiles sp ON u.id = sp.user_id
             WHERE 1=1";
    
    // Add search condition if search query is provided
    if (!empty($search_query)) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR sc.subject LIKE ?)";
    }
    
    // Add first name filter
    if (!empty($first_name_filter)) {
        $query .= " AND u.first_name LIKE ?";
    }
    
    // Add last name filter
    if (!empty($last_name_filter)) {
        $query .= " AND u.last_name LIKE ?";
    }
    
    // Add course filter
    if (!empty($course_filter)) {
        $query .= " AND sp.course LIKE ?";
    }
    
    // Add year level filter
    if (!empty($year_level_filter)) {
        $query .= " AND sp.year_level = ?";
    }
    
    // Add status filter condition
    if ($filter_status !== 'all') {
        $query .= " AND sc.status = ?";
    }
    
    $query .= " ORDER BY sc.created_at DESC";
    
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
        $types .= "s";
        $params[] = "%$first_name_filter%";
    }
    
    // Add last name filter parameter
    if (!empty($last_name_filter)) {
        $types .= "s";
        $params[] = "%$last_name_filter%";
    }
    
    // Add course filter parameter
    if (!empty($course_filter)) {
        $types .= "s";
        $params[] = "%$course_filter%";
    }
    
    // Add year level filter parameter
    if (!empty($year_level_filter)) {
        $types .= "s";
        $params[] = $year_level_filter;
    }
    
    // Add status filter parameter
    if ($filter_status !== 'all') {
        $types .= "s";
        $params[] = $filter_status;
    }
    
    // If parameters exist, bind them
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $concerns[] = $row;
    }
} catch (Exception $e) {
    $error_message = "An error occurred: " . $e->getMessage();
    error_log("Error in student concerns page: " . $e->getMessage());
}

// Get unique courses for filter dropdown
$courses = [];
try {
    $stmt = $db->prepare("SELECT DISTINCT course FROM student_profiles WHERE course IS NOT NULL AND course != '' ORDER BY course ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row['course'];
    }
} catch (Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}

// Handle concern status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $concern_id = $_POST['concern_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';
    
    if ($concern_id && in_array($new_status, ['open', 'in_progress', 'resolved'])) {
        try {
            $stmt = $db->prepare("UPDATE student_concerns SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $concern_id);
            
            if ($stmt->execute()) {
                $success_message = "Concern status updated successfully.";
                // Redirect to refresh the page and avoid form resubmission
                header("Location: student_concerns.php?status=$filter_status&success=1");
                exit;
            } else {
                $error_message = "Failed to update concern status.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error updating concern status: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Concerns - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .concerns-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        
        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .filter-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .filter-btn:hover {
            background-color: #0069d9;
        }
        
        .reset-btn {
            background-color: #6c757d;
        }
        
        .reset-btn:hover {
            background-color: #5a6268;
        }
        
        .concerns-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .concerns-table th,
        .concerns-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .concerns-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .concerns-table tr:hover {
            background-color: #f1f1f1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-open {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-in-progress {
            background-color: #b8daff;
            color: #004085;
        }
        
        .status-resolved {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .view-btn {
            background-color: #17a2b8;
            color: white;
        }
        
        .view-btn:hover {
            background-color: #138496;
        }
        
        .status-dropdown {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .no-concerns {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }
        
        .alert {
            padding: 15px;
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
        
        .concern-detail-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 70%;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: black;
        }
        
        .concern-details {
            margin-top: 20px;
        }
        
        .concern-details h3 {
            margin-bottom: 15px;
            color: #343a40;
        }
        
        .concern-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .concern-message {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            white-space: pre-line;
        }
        
        .student-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .student-info h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #343a40;
        }
        
        .student-info p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>STUDENT CONCERNS</h1>
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
            
            <div class="concerns-container">
                <form action="" method="GET" class="filter-section">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name, email, ID...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name_filter); ?>" placeholder="Filter by first name">
                    </div>
                    
                    <div class="filter-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name_filter); ?>" placeholder="Filter by last name">
                    </div>
                    
                    <div class="filter-group">
                        <label for="course">Course</label>
                        <select id="course" name="course">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($course_filter === $course) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level">
                            <option value="">All Year Levels</option>
                            <option value="1" <?php echo ($year_level_filter === '1') ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo ($year_level_filter === '2') ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo ($year_level_filter === '3') ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo ($year_level_filter === '4') ? 'selected' : ''; ?>>4th Year</option>
                            <option value="5" <?php echo ($year_level_filter === '5') ? 'selected' : ''; ?>>5th Year</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="open" <?php echo ($filter_status === 'open') ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo ($filter_status === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo ($filter_status === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                        <a href="student_concerns.php" class="filter-btn reset-btn">Reset</a>
                    </div>
                </form>
                
                <?php if (empty($concerns)): ?>
                    <div class="no-concerns">
                        <p>No student concerns found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <table class="concerns-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Subject</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concerns as $concern): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            $full_name = htmlspecialchars($concern['first_name']);
                                            if (!empty($concern['middle_name'])) {
                                                $full_name .= ' ' . htmlspecialchars(substr($concern['middle_name'], 0, 1) . '.');
                                            }
                                            $full_name .= ' ' . htmlspecialchars($concern['last_name']);
                                            echo $full_name;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($concern['student_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($concern['course'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                            if (!empty($concern['year_level'])) {
                                                echo htmlspecialchars($concern['year_level']) . getOrdinalSuffix($concern['year_level']) . ' Year';
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($concern['subject']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($concern['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $concern['status'])); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $concern['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="action-btn view-btn" onclick="viewConcernDetails(<?php echo $concern['id']; ?>)">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Concern Detail Modal -->
            <div id="concernDetailModal" class="concern-detail-modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeConcernModal()">&times;</span>
                    <div id="concernDetailsContent"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Function to view concern details
        function viewConcernDetails(concernId) {
            // AJAX request to get concern details
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'view_concern.php?id=' + concernId, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    document.getElementById('concernDetailsContent').innerHTML = this.responseText;
                    document.getElementById('concernDetailModal').style.display = 'block';
                }
            };
            xhr.send();
        }
        
        // Function to close the modal
        function closeConcernModal() {
            document.getElementById('concernDetailModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('concernDetailModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
        
        // Function to update concern status
        function updateConcernStatus(concernId, selectElement) {
            const newStatus = selectElement.value;
            document.getElementById('concern_id').value = concernId;
            document.getElementById('new_status').value = newStatus;
            document.getElementById('updateStatusForm').submit();
        }
    </script>
    
    <?php
    // Helper function to get ordinal suffix
    function getOrdinalSuffix($number) {
        if (!in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1:  return 'st';
                case 2:  return 'nd';
                case 3:  return 'rd';
            }
        }
        return 'th';
    }
    ?>
    
    <!-- Hidden form for status updates -->
    <form id="updateStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" id="concern_id" name="concern_id" value="">
        <input type="hidden" id="new_status" name="new_status" value="">
    </form>
</body>
</html>