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

// Get current month and year (default to current month/year)
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month and year
if ($month < 1 || $month > 12) {
    $month = date('m');
}
if ($year < 2000 || $year > 2100) {
    $year = date('Y');
}

// Calculate previous and next month/year
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get the first day of the month
$first_day = mktime(0, 0, 0, $month, 1, $year);

// Get the number of days in the month
$num_days = date('t', $first_day);

// Get the first day of the week (0 = Sunday, 6 = Saturday)
$first_day_of_week = date('w', $first_day);

// Get month name
$month_name = date('F', $first_day);

// Fetch events/activities for this month
$start_date = "$year-$month-01";
$end_date = "$year-$month-$num_days";

// Query to get all activities for the current month
$stmt = $db->prepare("SELECT e.id, e.title, e.description, e.start_date, e.end_date, 
                     COALESCE(rs.status, 'not_submitted') as status
                     FROM events e
                     LEFT JOIN return_service_activities rs ON e.id = rs.event_id AND rs.user_id = ?
                     WHERE (DATE(e.start_date) BETWEEN ? AND ? OR DATE(e.end_date) BETWEEN ? AND ?)
                     ORDER BY e.start_date ASC");
$stmt->bind_param('issss', $user_id, $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $event_date = date('Y-m-d', strtotime($row['start_date']));
    $events[$event_date][] = $row;
}

// Function to determine event status class
function getStatusClass($status) {
    switch ($status) {
        case 'approved':
            return 'status-completed'; // Green
        case 'pending':
            return 'status-pending'; // Orange
        case 'not_submitted':
        case 'rejected':
            return 'status-not-submitted'; // Red
        default:
            return 'status-not-submitted'; // Default to red
    }
}

// Function to get event details URL
function getEventDetailsUrl($event_id, $status) {
    // Determine which page to redirect to based on status
    switch ($status) {
        case 'approved':
            return "event_details.php?id=$event_id&status=completed";
        case 'pending':
            return "event_details.php?id=$event_id&status=pending";
        case 'not_submitted':
        case 'rejected':
            return "event_details.php?id=$event_id&status=not_submitted";
        default:
            return "event_details.php?id=$event_id&status=not_submitted";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Scholar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
                <h1>Calendar</h1>
                
                <!-- Calendar Legend -->
                <div class="calendar-legend">
                    <div class="legend-item">
                        <div class="legend-color status-completed"></div>
                        <span>DONE - Activity completed</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color status-pending"></div>
                        <span>PENDING - Activity submitted/pending approval</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color status-not-submitted"></div>
                        <span>UNDONE - Activity not yet completed</span>
                    </div>
                </div>
                
                <!-- Calendar Container -->
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h2><?php echo $month_name . ' ' . $year; ?></h2>
                        <div class="calendar-nav">
                            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                            <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-primary">Today</a>
                            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-secondary">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="calendar-grid">
                        <!-- Day headers -->
                        <div class="calendar-day-header">Sunday</div>
                        <div class="calendar-day-header">Monday</div>
                        <div class="calendar-day-header">Tuesday</div>
                        <div class="calendar-day-header">Wednesday</div>
                        <div class="calendar-day-header">Thursday</div>
                        <div class="calendar-day-header">Friday</div>
                        <div class="calendar-day-header">Saturday</div>
                        
                        <!-- Calendar days -->
                        <?php
                        // Add empty cells for days before the first day of the month
                        for ($i = 0; $i < $first_day_of_week; $i++) {
                            echo '<div class="calendar-day other-month"></div>';
                        }
                        
                        // Add cells for each day of the month
                        for ($day = 1; $day <= $num_days; $day++) {
                            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $is_today = ($date == date('Y-m-d'));
                            $day_class = $is_today ? 'calendar-day today' : 'calendar-day';
                            
                            echo "<div class=\"$day_class\">";
                            echo "<div class=\"day-number\">$day</div>";
                            
                            // Display events for this day
                            if (isset($events[$date])) {
                                foreach ($events[$date] as $event) {
                                    $status_class = getStatusClass($event['status']);
                                    $event_url = getEventDetailsUrl($event['id'], $event['status']);
                                    
                                    echo "<a href=\"$event_url\" class=\"calendar-event $status_class\" title=\"" . htmlspecialchars($event['description']) . "\">";
                                    echo htmlspecialchars($event['title']);
                                    echo "</a>";
                                }
                            }
                            
                            echo '</div>';
                        }
                        
                        // Add empty cells for days after the last day of the month
                        $last_day_of_week = ($first_day_of_week + $num_days) % 7;
                        if ($last_day_of_week > 0) {
                            for ($i = $last_day_of_week; $i < 7; $i++) {
                                echo '<div class="calendar-day other-month"></div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>