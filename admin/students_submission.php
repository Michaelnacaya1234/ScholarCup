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

// Get student submissions (both general submissions and return service activities)
$submissions = [];

try {
    // Base query for return service activities
    $query = "SELECT activities.id, activities.title, activities.description, activities.hours, 
             activities.proof_document as proof_file, activities.status, activities.created_at as date_submitted, 
             u.id as student_id, u.first_name, u.last_name, u.middle_name, u.school, 'return_service' as type
             FROM return_service_activities activities
             JOIN users u ON activities.user_id = u.id";
    
    // Add search condition if search query is provided
    if (!empty($search_query)) {
        $query .= " WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR activities.title LIKE ?)";
    }
    
    // Add filter condition
    if ($filter !== 'all') {
        $query .= empty($search_query) ? " WHERE" : " AND";
        $query .= " activities.status = ?";
    }
    
    $query .= " ORDER BY activities.created_at DESC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    if (!empty($search_query) && $filter !== 'all') {
        $search_param = "%$search_query%";
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $filter);
    } elseif (!empty($search_query)) {
        $search_param = "%$search_query%";
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    } elseif ($filter !== 'all') {
        $stmt->bind_param("s", $filter);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    
    // Also check the general submissions table if it exists
    $table_check = $db->query("SHOW TABLES LIKE 'submissions'");
    
    if ($table_check->num_rows > 0) {
        // Base query for general submissions
        $query = "SELECT s.id, s.title, s.description, s.file_path as proof_file, 
                 s.status, s.submitted_at as date_submitted, u.id as student_id, 
                 u.first_name, u.last_name, u.middle_name, u.school, 'general' as type
                 FROM submissions s
                 JOIN users u ON s.student_id = u.id";
        
        // Add search condition if search query is provided
        if (!empty($search_query)) {
            $query .= " WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR s.title LIKE ?)";
        }
        
        // Add filter condition
        if ($filter !== 'all') {
            $query .= empty($search_query) ? " WHERE" : " AND";
            $query .= " s.status = ?";
        }
        
        $query .= " ORDER BY s.submitted_at DESC";
        
        $stmt = $db->prepare($query);
        
        // Bind parameters
        if (!empty($search_query) && $filter !== 'all') {
            $search_param = "%$search_query%";
            $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $filter);
        } elseif (!empty($search_query)) {
            $search_param = "%$search_query%";
            $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        } elseif ($filter !== 'all') {
            $stmt->bind_param("s", $filter);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
    }
    
    // Sort all submissions by date (newest first)
    usort($submissions, function($a, $b) {
        return strtotime($b['date_submitted']) - strtotime($a['date_submitted']);
    });
    
} catch (Exception $e) {
    $error_message = "Error retrieving submissions: " . $e->getMessage();
    error_log("Error in submissions page: " . $e->getMessage());
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $submission_id = $_POST['submission_id'] ?? 0;
    $submission_type = $_POST['submission_type'] ?? '';
    $action = $_POST['action'];
    
    if ($submission_id && $submission_type && ($action === 'approve' || $action === 'reject')) {
        try {
            if ($submission_type === 'return_service') {
                $stmt = $db->prepare("UPDATE return_service_activities SET status = ?, approved_by = ? WHERE id = ?");
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                $stmt->bind_param("sii", $status, $_SESSION['user_id'], $submission_id);
            } else { // general submission
                $stmt = $db->prepare("UPDATE submissions SET status = ? WHERE id = ?");
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                $stmt->bind_param("si", $status, $submission_id);
            }
            
            if ($stmt->execute()) {
                $success_message = "Submission has been " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
                // Refresh the page to show updated status
                header("Location: students_submission.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
                exit;
            } else {
                $error_message = "Failed to update submission status. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error updating submission status: " . $e->getMessage());
        }
    }
}

// Pagination
$items_per_page = 10;
$total_items = count($submissions);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get submissions for current page
$current_submissions = array_slice($submissions, $offset, $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submissions - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .search-filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: #f0f2f5;
            padding: 15px;
            border-radius: 8px;
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
        
        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .submissions-table th, .submissions-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .submissions-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .submissions-table tr:hover {
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
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }
        
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .view-btn {
            background-color: #007bff;
            color: white;
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
        
        .no-submissions {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .file-link {
            color: #007bff;
            text-decoration: none;
        }
        
        .file-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>STUDENT SCHOLAR SUBMISSION LIST</h1>
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
            
            <div class="search-filter-container">
                <form action="" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search student name or title..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="filter-dropdown">
                    <form action="" method="GET">
                        <?php if (!empty($search_query)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php endif; ?>
                        <select name="filter" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Submissions</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <?php if (empty($current_submissions)): ?>
                <div class="no-submissions">
                    <i class="fas fa-inbox fa-3x" style="color: #6c757d; margin-bottom: 15px;"></i>
                    <h3>No submissions found</h3>
                    <p>There are no submissions matching your criteria.</p>
                </div>
            <?php else: ?>
                <table class="submissions-table">
                    <thead>
                        <tr>
                            <th>Student name</th>
                            <th>School</th>
                            <th>Date Submitted</th>
                            <th>Activity</th>
                            <th>Render</th>
                            <th>File</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($current_submissions as $submission): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $student_name = htmlspecialchars($submission['first_name']) . ' ';
                                    if (!empty($submission['middle_name'])) {
                                        $student_name .= htmlspecialchars($submission['middle_name']) . ' ';
                                    }
                                    $student_name .= htmlspecialchars($submission['last_name']);
                                    echo $student_name;
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($submission['school']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($submission['date_submitted'])); ?></td>
                                <td><?php echo htmlspecialchars($submission['title']); ?></td>
                                <td>
                                    <?php 
                                    if ($submission['type'] === 'return_service' && isset($submission['hours'])) {
                                        echo htmlspecialchars($submission['hours']) . ' hours';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($submission['proof_file'])): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($submission['proof_file']); ?>" target="_blank" class="file-link">
                                            <i class="fas fa-file-alt"></i> View
                                        </a>
                                    <?php else: ?>
                                        No file
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($submission['status']); ?>">
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($submission['status'] === 'pending'): ?>
                                        <div class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                <input type="hidden" name="submission_type" value="<?php echo $submission['type']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="approve-btn">Approve</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                <input type="hidden" name="submission_type" value="<?php echo $submission['type']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="reject-btn">Reject</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php $counter++; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=1<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>">&laquo;</a>
                            <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>">&lsaquo;</a>
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
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>">&rsaquo;</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?><?php echo $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>">&raquo;</a>
                        <?php else: ?>
                            <span class="disabled">&rsaquo;</span>
                            <span class="disabled">&raquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>