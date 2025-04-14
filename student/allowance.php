<?php
// Include necessary files and start session
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'Allowance Releasing Schedule';

// Get allowance releasing schedules from database
$conn = getConnection();
$query = "SELECT * FROM allowance_releasing_schedule ORDER BY schedule_date DESC, schedule_time_start ASC";
$result = mysqli_query($conn, $query);

// Check if student has claimed any allowances
$claimed_query = "SELECT allowance_schedule_id FROM student_allowance_claims WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $claimed_query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$claimed_result = mysqli_stmt_get_result($stmt);

$claimed_allowances = [];
while ($row = mysqli_fetch_assoc($claimed_result)) {
    $claimed_allowances[] = $row['allowance_schedule_id'];
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">ALLOWANCE RELEASING SCHEDULE</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <span class="badge bg-success">CLAIMED</span>
                        <span class="badge bg-danger">NOT CLAIM</span>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>CASHIER NO.</th>
                            <th>PAYROLL NO.</th>
                            <th>PERIOD</th>
                            <th>VENUE</th>
                            <th>SCHEDULE</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php 
                                    $is_claimed = in_array($row['id'], $claimed_allowances) || $row['status'] === 'claimed';
                                    $status_class = $is_claimed ? 'success' : 'danger';
                                    $status_text = $is_claimed ? 'CLAIMED' : 'NOT CLAIM';
                                    
                                    // Format schedule date and time
                                    $schedule_date = date('F j, Y', strtotime($row['schedule_date']));
                                    $start_time = date('g:ia', strtotime($row['schedule_time_start']));
                                    $end_time = date('g:ia', strtotime($row['schedule_time_end']));
                                    $schedule = "$schedule_date - $start_time - $end_time";
                                ?>
                                <tr>
                                    <td><?php echo $row['cashier_no']; ?></td>
                                    <td><?php echo $row['payroll_no']; ?></td>
                                    <td><?php echo $row['period']; ?></td>
                                    <td><?php echo $row['venue']; ?></td>
                                    <td><?php echo $schedule; ?></td>
                                    <td class="text-<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No allowance releasing schedules available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>