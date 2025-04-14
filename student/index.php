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
$page_title = 'Student Dashboard';

// Get user information
$user = getUserById($user_id);
$student_profile = getStudentProfile($user_id);

// Get latest announcements
$conn = getConnection();
$query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$announcements_result = mysqli_query($conn, $query);

// Get upcoming events
$query = "SELECT * FROM events WHERE end_date >= CURDATE() ORDER BY start_date ASC LIMIT 5";
$events_result = mysqli_query($conn, $query);

// Get latest allowance schedule
$query = "SELECT * FROM allowance_releasing_schedule ORDER BY schedule_date DESC LIMIT 1";
$allowance_result = mysqli_query($conn, $query);
$latest_allowance = mysqli_fetch_assoc($allowance_result);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <?php include '../includes/student_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Student Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <span class="badge bg-primary"><?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Welcome Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Welcome, <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>!</h5>
                    <p class="card-text">This is your scholarship management dashboard. Here you can view your status, submit requirements, and track your scholarship progress.</p>
                </div>
            </div>
            
            <div class="row">
                <!-- Announcements -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Latest Announcements</h5>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($announcement = mysqli_fetch_assoc($announcements_result)): ?>
                                        <li class="list-group-item">
                                            <h6><?php echo $announcement['title']; ?></h6>
                                            <p class="small text-muted"><?php echo formatDate($announcement['created_at']); ?></p>
                                            <p><?php echo substr($announcement['content'], 0, 100) . '...'; ?></p>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="announcement.php" class="btn btn-sm btn-outline-primary">View All Announcements</a>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No announcements available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">Upcoming Events</h5>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($events_result) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                        <li class="list-group-item">
                                            <h6><?php echo $event['title']; ?></h6>
                                            <p class="small text-muted">
                                                <i class="fas fa-calendar-alt"></i> <?php echo formatDate($event['start_date']); ?>
                                                <br>
                                                <i class="fas fa-map-marker-alt"></i> <?php echo $event['location']; ?>
                                            </p>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="calendar.php" class="btn btn-sm btn-outline-success">View Calendar</a>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No upcoming events.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Latest Allowance Schedule -->
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Latest Allowance Schedule</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($latest_allowance): ?>
                                <?php 
                                    // Format schedule date and time
                                    $schedule_date = date('F j, Y', strtotime($latest_allowance['schedule_date']));
                                    $start_time = date('g:ia', strtotime($latest_allowance['schedule_time_start']));
                                    $end_time = date('g:ia', strtotime($latest_allowance['schedule_time_end']));
                                    $schedule = "$schedule_date - $start_time - $end_time";
                                    
                                    $status_class = $latest_allowance['status'] === 'claimed' ? 'success' : 'danger';
                                    $status_text = $latest_allowance['status'] === 'claimed' ? 'CLAIMED' : 'NOT CLAIM';
                                ?>
                                <div class="row">
                                    <div class="col-md-2">
                                        <p><strong>CASHIER NO:</strong> <?php echo $latest_allowance['cashier_no']; ?></p>
                                    </div>
                                    <div class="col-md-2">
                                        <p><strong>PAYROLL NO:</strong> <?php echo $latest_allowance['payroll_no']; ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>PERIOD:</strong> <?php echo $latest_allowance['period']; ?></p>
                                    </div>
                                    <div class="col-md-2">
                                        <p><strong>VENUE:</strong> <?php echo $latest_allowance['venue']; ?></p>
                                    </div>
                                    <div class="col-md-2">
                                        <p><strong>SCHEDULE:</strong> <?php echo $schedule; ?></p>
                                    </div>
                                    <div class="col-md-1">
                                        <p><strong>STATUS:</strong> <span class="text-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="allowance.php" class="btn btn-sm btn-outline-info">View All Allowance Schedules</a>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No allowance schedule available.</p>
                                <div class="mt-3 text-center">
                                    <a href="allowance.php" class="btn btn-sm btn-outline-info">Check Allowance Schedules</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>