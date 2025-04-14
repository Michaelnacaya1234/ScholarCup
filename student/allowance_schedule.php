<?php
// Include necessary files and start session
session_start();
require_once '../includes/config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'Allowance Schedule';

// Get user information
$user = getUserById($user_id);
$student_profile = getStudentProfile($user_id);

// Get all allowance schedules - Using Database class for consistency
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM allowance_releasing_schedule ORDER BY schedule_date DESC");
$stmt->execute();
$allowance_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allowance Schedule - Scholar Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Custom header without logout button -->
            <header class="header">
                <div class="header-right">
                    <!-- Any other header elements can go here if needed -->
                </div>
            </header>

            <div class="content-wrapper">
                <h1 class="page-title">Allowance Schedule</h1>
                
                <!-- Upcoming Schedule Card -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Upcoming Schedule</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Next Allowance Release</h6>
                                <?php
                                // Query to get the next upcoming allowance
                                $upcoming_stmt = $db->prepare("SELECT * FROM allowance_releasing_schedule 
                                                            WHERE schedule_date >= CURDATE() 
                                                            ORDER BY schedule_date ASC LIMIT 1");
                                $upcoming_stmt->execute();
                                $upcoming = $upcoming_stmt->get_result()->fetch_assoc();
                                
                                if ($upcoming): 
                                    $upcoming_date = date('F j, Y', strtotime($upcoming['schedule_date']));
                                    $upcoming_time = date('g:i A', strtotime($upcoming['schedule_time_start']));
                                ?>
                                    <div class="next-schedule-details">
                                        <div class="d-flex mb-2">
                                            <div style="width: 100px;">
                                                <span class="fw-bold">Date:</span>
                                            </div>
                                            <div>
                                                <span><?php echo $upcoming_date; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex mb-2">
                                            <div style="width: 100px;">
                                                <span class="fw-bold">Time:</span>
                                            </div>
                                            <div>
                                                <span><?php echo $upcoming_time; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex mb-2">
                                            <div style="width: 100px;">
                                                <span class="fw-bold">Venue:</span>
                                            </div>
                                            <div>
                                                <span><?php echo $upcoming['venue']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No upcoming allowance release scheduled at this time.</p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="#schedule-table" class="btn btn-sm btn-outline-primary">View Complete Schedule</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Information Card -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Important Information</h5>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Please bring your student ID when claiming your allowance.</li>
                                    <li>Allowances not claimed on the scheduled date may be claimed at the Cashier's Office during regular office hours.</li>
                                    <li>For inquiries about your allowance, please contact the Scholarship Office.</li>
                                </ul>
                            </div>
                        </div>
                </div>
            </div>
            
                <!-- Complete Schedule Table Card -->
                <div class="card mb-4" id="schedule-table">
                    <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Allowance Releasing Schedules</h5>
                </div>
                <div class="card-body">
                        <?php if ($allowance_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Cashier No</th>
                                        <th>Payroll No</th>
                                        <th>Period</th>
                                        <th>Venue</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php while ($allowance = $allowance_result->fetch_assoc()): ?>
                                        <?php 
                                            // Format schedule date and time
                                            $schedule_date = date('F j, Y', strtotime($allowance['schedule_date']));
                                            $start_time = date('g:ia', strtotime($allowance['schedule_time_start']));
                                            $end_time = date('g:ia', strtotime($allowance['schedule_time_end']));
                                            $schedule = "$schedule_date - $start_time - $end_time";
                                            
                                            $status_class = $allowance['status'] === 'claimed' ? 'success' : 'danger';
                                                $status_text = $allowance['status'] === 'claimed' ? 'CLAIMED' : 'NOT CLAIMED';
                                        ?>
                                        <tr>
                                            <td><?php echo $allowance['cashier_no']; ?></td>
                                            <td><?php echo $allowance['payroll_no']; ?></td>
                                            <td><?php echo $allowance['period']; ?></td>
                                            <td><?php echo $allowance['venue']; ?></td>
                                            <td><?php echo $schedule; ?></td>
                                            <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p class="text-center mb-0">No allowance schedules available at this time. Please check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
</div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Toggle sidebar on mobile
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>
</html>