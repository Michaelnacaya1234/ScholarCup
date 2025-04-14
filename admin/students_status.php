<?php
// Include database connection and class definition
require_once '../database.php';
require_once __DIR__ . '/check_database_structure.php';

use Scholar\Admin\DatabaseStructureChecker;

// Initialize and run database checker
$dbChecker = new DatabaseStructureChecker();
$dbChecker->checkAndUpdate();

// Check if user is logged in and is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Initialize variables
$students = [];
$search = '';
$filter = 'all';

// Process search and filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
}

if (isset($_GET['filter']) && !empty($_GET['filter'])) {
    $filter = $_GET['filter'];
}

// Only fetch students if they have been registered
// This query will only return results for students who have been registered
$query = "SELECT u.id, u.first_name, 
    u.middle_name, 
    u.last_name, u.email, 
    sp.course, sp.year_level,
    scs.status as current_status,
    sas.status as academic_status,
    sas.renewal_status,
    sas.total_return_service
    FROM users u 
    LEFT JOIN student_profiles sp ON u.id = sp.user_id 
    LEFT JOIN student_current_status scs ON u.id = scs.user_id 
    LEFT JOIN student_academic_status sas ON u.id = sas.user_id
    WHERE u.role = 'student'";

// Add search condition if search is provided
if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
}

// Add filter condition if filter is not 'all'
if ($filter !== 'all') {
    $query .= " AND scs.status = ?";
}

$query .= " ORDER BY u.last_name ASC";

$stmt = $db->prepare($query);

// Bind parameters based on search and filter
if (!empty($search) && $filter !== 'all') {
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $filter);
} elseif (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("ss", $searchParam, $searchParam);
} elseif ($filter !== 'all') {
    $stmt->bind_param("s", $filter);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch students only if they exist in the database
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Function to delete student and archive their data
function deleteStudent($student_id) {
    global $db;
    try {
        // Start transaction
        $db->begin_transaction();
        
        // Get student data before deletion
        $stmt = $db->prepare("SELECT u.*, sp.* FROM users u 
                            LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                            WHERE u.id = ? AND u.role = 'student'");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student_data = $stmt->get_result()->fetch_assoc();
        
        if ($student_data) {
            // Archive user data
            $stmt = $db->prepare("INSERT INTO archived_users 
                (original_id, email, first_name, middle_name, last_name, role) 
                VALUES (?, ?, ?, ?, ?, 'student')");
            $stmt->bind_param("issss", $student_id, $student_data['email'], 
                $student_data['first_name'], $student_data['middle_name'], 
                $student_data['last_name']);
            $stmt->execute();

            // Archive student profile
            $stmt = $db->prepare("INSERT INTO archived_student_profiles 
                (original_user_id, student_id, course, year_level, school, contact_number, address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isisiss", $student_id, $student_data['student_id'], 
                $student_data['course'], $student_data['year_level'], 
                $student_data['school'], $student_data['contact_number'], 
                $student_data['address']);
            $stmt->execute();

            // Delete from student_profiles and related tables
            $stmt = $db->prepare("DELETE FROM student_profiles WHERE user_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            
            $stmt = $db->prepare("DELETE FROM student_current_status WHERE user_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            
            // Delete from users table
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();

            $db->commit();
            return true;
        }
        return false;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error archiving student: " . $e->getMessage());
        return false;
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $student_id = $_POST['student_id'] ?? 0;
    if ($student_id && deleteStudent($student_id)) {
        $success_message = "Student has been archived successfully.";
        // Refresh the page to show updated list
        header("Location: students_status.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        exit;
    } else {
        $error_message = "Failed to archive student. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Scholar Status - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-status.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>STUDENTS SCHOLAR STATUS</h1>
            
            </div>
            
            <div class="content-wrapper">
                <div class="filters-row">
                    <div class="search-container">
                        <form action="" method="GET">
                            <input type="text" name="search" placeholder="Search Student..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    
                    <div class="filter-container">
                        <form action="" method="GET">
                            <select name="filter" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="complied" <?php echo $filter === 'complied' ? 'selected' : ''; ?>>Complied</option>
                                <option value="not comply" <?php echo $filter === 'not comply' ? 'selected' : ''; ?>>Not Comply</option>
                                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>NAME OF SCHOLAR</th>
                                <th>SCHOOL</th>
                                <th>TOTAL RETURN SERVICE</th>
                                <th>STATUS</th>
                                <th>GRADE STATUS A.Y<br>2023-2024<br>(1ST SEM)</th>
                                <th>RENEWAL STATUS<br>2ND SEM A.Y<br>2024-2025</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" class="no-data">No students registered yet. Register students to view their status.</td>
                                </tr>
                            <?php else: ?>
                                <?php $counter = 1; ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?>.</td>
                                        <td>
                                            <?php 
                                                $fullName = htmlspecialchars($student['last_name']) . ', ' . 
                                                          htmlspecialchars($student['first_name']);
                                                if (!empty($student['middle_name'])) {
                                                    $fullName .= ' ' . htmlspecialchars($student['middle_name'][0]) . '.';
                                                }
                                                echo $fullName;
                                            ?>
                                        </td>
                                        <td>Not Specified</td>
                                        <td><?php echo htmlspecialchars($student['total_return_service'] ?? '0'); ?></td>
                                        <td class="<?php echo strtolower(str_replace(' ', '_', $student['current_status'] ?? 'pending')); ?>">
                                            <?php echo strtoupper($student['current_status'] ?? 'PENDING'); ?>
                                        </td>
                                        <td>
                                            <div class="<?php echo strtolower(str_replace(' ', '_', $student['academic_status'] ?? 'not_evaluate_yet')); ?>">
                                                <?php echo strtoupper($student['academic_status'] ?? 'NOT EVALUATE YET'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="<?php echo strtolower(str_replace(' ', '_', $student['renewal_status'] ?? 'not_renew_yet')); ?>">
                                                <?php echo strtoupper($student['renewal_status'] ?? 'NOT RENEW YET'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-edit" onclick="editStudentDetails(<?php echo htmlspecialchars(json_encode($student)); ?>)">Edit</button>
                                            <form action="" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to archive this student?');">Delete</button>
                                            </form>
                                            <button class="btn btn-view" onclick="viewStudentDetails(<?php echo htmlspecialchars(json_encode($student)); ?>)">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal for viewing student details -->
                <div id="studentModal" class="modal">
                    <div class="modal-content view-modal">
                        <div class="modal-header">
                            <h2>Scholar Details</h2>
                            <span class="close" onclick="closeModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div id="studentDetails" class="details-grid"></div>
                        </div>
                    </div>
                </div>

                <!-- Modal for editing student details -->
                <div id="editStudentModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeEditModal()">&times;</span>
                        <h2>Edit Student Details</h2>
                        <div id="updateMessage" class="alert" style="display: none;"></div>
                        <form id="editStudentForm" method="POST" onsubmit="updateStudent(event)">
                            <input type="hidden" id="edit_student_id" name="student_id">
                            <div class="form-group">
                                <label for="edit_current_status">Current Status:</label>
                                <select id="edit_current_status" name="current_status" required>
                                    <option value="complied">Complied</option>
                                    <option value="not comply">Not Comply</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_academic_status">Academic Status:</label>
                                <select id="edit_academic_status" name="academic_status" required>
                                    <option value="passed">Passed</option>
                                    <option value="failed">Failed</option>
                                    <option value="incomplete">Incomplete</option>
                                    <option value="not_evaluate_yet">Not Evaluate Yet</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_renewal_status">Renewal Status:</label>
                                <select id="edit_renewal_status" name="renewal_status" required>
                                    <option value="renewed">Renewed</option>
                                    <option value="not_renewed">Not Renewed</option>
                                    <option value="not_renew_yet">Not Renew Yet</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_total_return_service">Total Return Service:</label>
                                <input type="number" id="edit_total_return_service" name="total_return_service" min="0" required>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-save">Save Changes</button>
                                <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <style>
                    .form-group {
                        margin-bottom: 15px;
                    }
                    .form-group label {
                        display: block;
                        margin-bottom: 5px;
                        font-weight: bold;
                    }
                    .form-group select,
                    .form-group input {
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                    }
                    .form-buttons {
                        margin-top: 20px;
                        text-align: right;
                    }
                    .btn-save {
                        background-color: #4CAF50;
                        color: white;
                        padding: 8px 16px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        margin-left: 10px;
                    }
                    .btn-cancel {
                        background-color: #f44336;
                        color: white;
                    }
                    .alert {
                        padding: 10px;
                        margin-bottom: 15px;
                        border-radius: 4px;
                    }
                    .alert-success {
                        background-color: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                    }
                    .alert-error {
                        background-color: #f8d7da;
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                    }
                </style>

                <!-- Add these styles -->
                <style>
                    .view-modal {
                        max-width: 600px;
                        width: 90%;
                        margin: 5% auto;
                    }
                    
                    .modal-header {
                        background: #1a237e;
                        color: white;
                        padding: 15px 20px;
                        border-radius: 5px 5px 0 0;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    
                    .modal-header .close {
                        color: white;
                    }
                    
                    .modal-body {
                        padding: 20px;
                    }
                    
                    .details-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 15px;
                    }
                    
                    .details-grid .detail-item {
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 5px;
                        border-left: 4px solid #1a237e;
                    }
                    
                    .detail-item .label {
                        font-size: 0.9em;
                        color: #666;
                        margin-bottom: 5px;
                    }
                    
                    .detail-item .value {
                        font-size: 1.1em;
                        color: #333;
                        font-weight: 500;
                    }

                    .status-indicator {
                        display: inline-block;
                        padding: 5px 10px;
                        border-radius: 15px;
                        font-weight: bold;
                        text-align: center;
                        width: fit-content;
                    }

                    .status-complied { background: #d4edda; color: #155724; }
                    .status-not-comply { background: #f8d7da; color: #721c24; }
                    .status-pending { background: #fff3cd; color: #856404; }
                    .status-passed { background: #d4edda; color: #155724; }
                    .status-failed { background: #f8d7da; color: #721c24; }
                    .status-incomplete { background: #fff3cd; color: #856404; }
                    .status-renewed { background: #d4edda; color: #155724; }
                    .status-not-renewed { background: #f8d7da; color: #721c24; }
                </style>

                <?php if (!empty($students) && count($students) > 10): ?>
                <div class="pagination">
                    <a href="#" class="pagination-btn">&laquo;</a>
                    <a href="#" class="pagination-btn active">1</a>
                    <a href="#" class="pagination-btn">2</a>
                    <a href="#" class="pagination-btn">3</a>
                    <a href="#" class="pagination-btn">4</a>
                    <a href="#" class="pagination-btn">5</a>
                    <a href="#" class="pagination-btn">Next</a>
                    <a href="#" class="pagination-btn">&raquo;</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function viewStudentDetails(student) {
            const modal = document.getElementById('studentModal');
            const detailsContainer = document.getElementById('studentDetails');
            
            const getStatusClass = (status, type) => {
                status = status?.toLowerCase() || 'pending';
                return `status-${status.replace(/\s+/g, '-')}`;
            };

            detailsContainer.innerHTML = `
                <div class="detail-item">
                    <div class="label">Scholar Name</div>
                    <div class="value">${student.last_name}, ${student.first_name} ${student.middle_name || ''}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Email Address</div>
                    <div class="value">${student.email || 'Not Specified'}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Course</div>
                    <div class="value">${student.course || 'Not Specified'}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Year Level</div>
                    <div class="value">${student.year_level || 'Not Specified'}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Current Status</div>
                    <div class="value">
                        <span class="status-indicator ${getStatusClass(student.current_status)}">
                            ${student.current_status?.toUpperCase() || 'PENDING'}
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="label">Academic Status</div>
                    <div class="value">
                        <span class="status-indicator ${getStatusClass(student.academic_status)}">
                            ${student.academic_status?.toUpperCase() || 'NOT EVALUATE YET'}
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="label">Renewal Status</div>
                    <div class="value">
                        <span class="status-indicator ${getStatusClass(student.renewal_status)}">
                            ${student.renewal_status?.toUpperCase() || 'NOT RENEW YET'}
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="label">Total Return Service</div>
                    <div class="value">${student.total_return_service || '0'} hours</div>
                </div>
            `;
            modal.style.display = 'block';
        }

        function closeModal() {
            const modal = document.getElementById('studentModal');
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('studentModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };

        function editStudentDetails(student) {
            const modal = document.getElementById('editStudentModal');
            document.getElementById('edit_student_id').value = student.id;
            document.getElementById('edit_current_status').value = student.current_status || 'pending';
            document.getElementById('edit_academic_status').value = student.academic_status || 'not_evaluate_yet';
            document.getElementById('edit_renewal_status').value = student.renewal_status || 'not_renew_yet';
            document.getElementById('edit_total_return_service').value = student.total_return_service || '0';
            modal.style.display = 'block';
        }

        function closeEditModal() {
            const modal = document.getElementById('editStudentModal');
            modal.style.display = 'none';
        }

        function updateStudent(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('updateMessage');

            fetch('update_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.textContent = data.message;
                messageDiv.className = 'alert ' + (data.success ? 'alert-success' : 'alert-error');
                messageDiv.style.display = 'block';
                
                if (data.success) {
                    setTimeout(() => {
                        closeEditModal();
                        location.reload(); // Refresh the page to show updated data
                    }, 1500);
                }
            })
            .catch(error => {
                messageDiv.textContent = 'Error updating student status';
                messageDiv.className = 'alert alert-error';
                messageDiv.style.display = 'block';
            });
        }
    </script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</body>
</html>