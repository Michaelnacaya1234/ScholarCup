<?php
session_start();
require_once '../includes/config/database.php';
require_once 'login_check.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: ../index.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];

// Initialize database connection
$db = Database::getInstance();

// Fetch student academic information
$stmt = $db->prepare("SELECT s.*, g.status as grade_status, rs.status as rs_status 
                     FROM student_academic_status s 
                     LEFT JOIN student_grades g ON s.user_id = g.user_id AND s.academic_year = g.academic_year AND s.semester = g.semester
                     LEFT JOIN return_service rs ON s.user_id = rs.user_id AND s.academic_year = rs.academic_year AND s.semester = rs.semester
                     WHERE s.user_id = ? 
                     ORDER BY s.academic_year DESC, s.semester DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Get academic status options
$status_query = $db->query("SELECT * FROM academic_status_options");
$status_options = [];
if ($status_query) {
    while ($row = $status_query->fetch_assoc()) {
        $status_options[] = $row;
    }
}

// Get current academic status
$current_status_query = $db->prepare("SELECT status FROM student_current_status WHERE user_id = ?");
$current_status_query->bind_param('i', $user_id);
$current_status_query->execute();
$current_status_result = $current_status_query->get_result();
$current_status = $current_status_result->fetch_assoc()['status'] ?? '';

// Process status update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['academic_status'])) {
    $new_status = $_POST['academic_status'];
    
    // Update or insert current status
    $update_stmt = $db->prepare("INSERT INTO student_current_status (user_id, status, updated_at) 
                               VALUES (?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE status = ?, updated_at = NOW()");
    $update_stmt->bind_param('iss', $user_id, $new_status, $new_status);
    
    if ($update_stmt->execute()) {
        $current_status = $new_status;
        $success_message = "Academic status updated successfully.";
    } else {
        $error_message = "Failed to update academic status.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Status - Scholar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/student-status.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include_once '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <h1>Student Status</h1>
                
                <!-- Academic Status Selection -->
                <div class="status-selection">
                    <form method="POST" action="">
                        <label for="academic-status">Academic Status</label>
                        <select name="academic_status" id="academic-status" class="status-select">
                            <option value="">Please Select</option>
                            <?php foreach ($status_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['status']); ?>" <?php echo ($current_status === $option['status']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option['status']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
                
                <!-- Status Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <!-- Academic Information Table -->
                <div class="status-table-container">
                    <table class="status-table">
                        <thead>
                            <tr>
                                <th>Year level and Semester</th>
                                <th>TOTAL RETURN SERVICE</th>
                                <th>STATUS</th>
                                <th>GRADE STATUS A.Y.<br>2023-2025 (1ST SEM)</th>
                                <th>RENEWAL STATUS 2ND<br>SEM A.Y 2024-2025</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>SY <?php echo htmlspecialchars($row['academic_year']); ?> SEM <?php echo htmlspecialchars($row['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($row['total_return_service'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['status'] ?? 'COMPLIED'); ?></td>
                                        <td class="grade-status">
                                            <?php 
                                            $grade_status = $row['grade_status'] ?? '';
                                            $status_class = '';
                                            
                                            if ($grade_status === 'approved') {
                                                echo 'EVALUATED';
                                                $status_class = 'status-evaluated';
                                            } elseif ($grade_status === 'pending') {
                                                echo 'PENDING';
                                                $status_class = 'status-pending';
                                            } else {
                                                echo 'NOT SUBMITTED';
                                                $status_class = 'status-not-submitted';
                                            }
                                            ?>
                                            <div class="status-indicator <?php echo $status_class; ?>"></div>
                                        </td>
                                        <td class="renewal-status">
                                            <?php 
                                            $renewal_status = $row['renewal_status'] ?? '';
                                            $renewal_class = '';
                                            
                                            if ($renewal_status === 'renewed') {
                                                echo 'RENEWED';
                                                $renewal_class = 'status-renewed';
                                            } elseif ($renewal_status === 'pending') {
                                                echo 'PENDING';
                                                $renewal_class = 'status-pending';
                                            } else {
                                                echo 'NOT RENEWED';
                                                $renewal_class = 'status-not-renewed';
                                            }
                                            ?>
                                            <div class="status-indicator <?php echo $renewal_class; ?>"></div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No academic records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Download CSR Button -->
                <div class="download-container">
                    <?php
                    // Check if the student has "renewed" and "complied" status
                    $is_eligible_for_csr = false;
                    
                    // Reset the result pointer
                    if ($result->num_rows > 0) {
                        $result->data_seek(0);
                        while ($row = $result->fetch_assoc()) {
                            $renewal_status = $row['renewal_status'] ?? '';
                            $status = $row['status'] ?? '';
                            
                            // Check if any record has both renewed and complied status
                            if ($renewal_status === 'renewed' && $status === 'COMPLIED') {
                                $is_eligible_for_csr = true;
                                break;
                            }
                        }
                    }
                    
                    if ($is_eligible_for_csr) {
                        // Student is eligible to download CSR
                        echo '<a href="download_csr.php" class="btn btn-primary download-btn">Download CSR</a>';
                    } else {
                        // Student is not eligible - disable the button
                        echo '<button disabled class="btn btn-secondary download-btn" title="You need to have renewed and complied status to download CSR">Download CSR</button>';
                        echo '<p class="text-danger mt-2"><small>Note: CSR download is only available when your status is COMPLIED and your renewal status is RENEWED.</small></p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>