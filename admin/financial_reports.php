<?php
session_start();
require_once '../includes/config/database.php';
require_once '../includes/Auth.php';
require_once '../database/migrations/check_database_structure.php';

// Initialize and check database structure
$dbChecker = new \Scholar\Admin\DatabaseStructureChecker();
$dbChecker->checkAndUpdate();

// Check if allowance_schedule table exists, create if not
$db = Database::getInstance();
$table_check = $db->query("SHOW TABLES LIKE 'allowance_schedule'");
if ($table_check->num_rows == 0) {
    $create_table_query = "CREATE TABLE IF NOT EXISTS allowance_schedule (
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
    )";
    $db->query($create_table_query);
}

// Check if user is logged in and has admin role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$full_name = $first_name . ' ' . $last_name;

// Initialize database connection
$db = Database::getInstance();
$auth = new Auth($db);

// Initialize messages
$success_message = '';
$error_message = '';

// Check if required tables exist
$tables_to_check = ['funds_management', 'allowance_schedule', 'allowance_claims'];
$missing_tables = [];

foreach ($tables_to_check as $table) {
    $table_check = $db->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

// Create allowance_claims table if it doesn't exist
$table_check = $db->query("SHOW TABLES LIKE 'allowance_claims'");
if ($table_check->num_rows == 0) {
    $create_table_query = "CREATE TABLE IF NOT EXISTS allowance_claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        allowance_id INT NOT NULL,
        student_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        claim_date DATETIME,
        status ENUM('pending', 'claimed', 'unclaimed', 'expired') DEFAULT 'pending',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (allowance_id) REFERENCES allowance_releasing_schedule(id),
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";
    
    $db->query($create_table_query);
}

// Create financial_reports table if it doesn't exist
$table_check = $db->query("SHOW TABLES LIKE 'financial_reports'");
if ($table_check->num_rows == 0) {
    $create_table_query = "CREATE TABLE IF NOT EXISTS financial_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_type ENUM('monthly', 'yearly', 'custom') NOT NULL,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        total_funds DECIMAL(10,2) NOT NULL,
        total_disbursed DECIMAL(10,2) NOT NULL,
        total_unclaimed DECIMAL(10,2) NOT NULL,
        report_data JSON,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    
    $db->query($create_table_query);
}

// Get report type from request (default to monthly)
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';

// Get date range from request
$current_year = date('Y');
$current_month = date('m');

$year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;
$month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;

// Set date range based on report type
$start_date = '';
$end_date = '';
$period_label = '';

switch ($report_type) {
    case 'monthly':
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date)); // Last day of the month
        $period_label = date('F Y', strtotime($start_date));
        break;
        
    case 'yearly':
        $start_date = "$year-01-01";
        $end_date = "$year-12-31";
        $period_label = $year;
        break;
        
    case 'custom':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $period_label = date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date));
        break;
}

// Get financial data for the selected period
$financial_data = [];

// 1. Get total funds added during the period
if (!empty($missing_tables) && in_array('funds_management', $missing_tables)) {
    $total_funds_added = 0;
} else {
    $funds_query = $db->prepare("SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END), 0) +
        COALESCE(SUM(CASE WHEN transaction_type = 'adjustment' THEN amount ELSE 0 END), 0) as total_funds
        FROM funds_management
        WHERE transaction_date BETWEEN ? AND ?");
    $funds_query->bind_param('ss', $start_date, $end_date);
    $funds_query->execute();
    $result = $funds_query->get_result();
    $total_funds_added = $result->fetch_assoc()['total_funds'] ?? 0;
}

// 2. Get total allowances released during the period
if (!empty($missing_tables) && in_array('allowance_schedule', $missing_tables)) {
    $total_allowances = 0;
    $allowance_data = [];
} else {
    $allowance_query = $db->prepare("SELECT 
        COALESCE(SUM(amount), 0) as total_allowances,
        COUNT(*) as total_students
        FROM allowance_schedule
        WHERE schedule BETWEEN ? AND ?");
    $allowance_query->bind_param('ss', $start_date, $end_date);
    $allowance_query->execute();
    $result = $allowance_query->get_result();
    $allowance_data = $result->fetch_assoc();
    $total_allowances = $allowance_data['total_allowances'] ?? 0;
}

// 3. Get unclaimed allowances
if (!empty($missing_tables) && (in_array('allowance_claims', $missing_tables) || in_array('allowance_schedule', $missing_tables))) {
    $total_unclaimed = 0;
    $unclaimed_count = 0;
} else {
    $unclaimed_query = $db->prepare("SELECT 
        COALESCE(SUM(ac.amount), 0) as total_unclaimed,
        COUNT(*) as unclaimed_count
        FROM allowance_claims ac
        JOIN allowance_schedule a ON ac.allowance_id = a.id
        WHERE a.schedule BETWEEN ? AND ? AND ac.status = 'unclaimed'");
    $unclaimed_query->bind_param('ss', $start_date, $end_date);
    $unclaimed_query->execute();
    $result = $unclaimed_query->get_result();
    $unclaimed_data = $result->fetch_assoc();
    $total_unclaimed = $unclaimed_data['total_unclaimed'] ?? 0;
    $unclaimed_count = $unclaimed_data['unclaimed_count'] ?? 0;
}

// 4. Get monthly distribution data for charts
$monthly_data = [];

if ($report_type === 'yearly' && empty($missing_tables)) {
    $monthly_query = $db->prepare("SELECT 
        MONTH(schedule) as month,
        COALESCE(SUM(amount), 0) as total_amount,
        COUNT(*) as student_count
        FROM allowance_schedule
        WHERE YEAR(schedule) = ?
        GROUP BY MONTH(schedule)
        ORDER BY MONTH(schedule)");
    $monthly_query->bind_param('i', $year);
    $monthly_query->execute();
    $result = $monthly_query->get_result();
    
    // Initialize all months with zero
    for ($i = 1; $i <= 12; $i++) {
        $monthly_data[$i] = [
            'month' => date('F', mktime(0, 0, 0, $i, 1, $year)),
            'total_amount' => 0,
            'student_count' => 0
        ];
    }
    
    while ($row = $result->fetch_assoc()) {
        $month_num = intval($row['month']);
        $monthly_data[$month_num] = [
            'month' => date('F', mktime(0, 0, 0, $month_num, 1, $year)),
            'total_amount' => floatval($row['total_amount']),
            'student_count' => intval($row['student_count'])
        ];
    }
}

// Get student concerns count for notification
$stmt = $db->prepare("SELECT COUNT(*) as count FROM student_concerns WHERE status = 'open'");
$stmt->execute();
$result = $stmt->get_result();
$concerns_count = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - CSO Scholar Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- Include Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .report-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex: 1;
            min-width: 250px;
        }
        
        .report-card h2 {
            margin-top: 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .report-amount {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .report-filters {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            margin-bottom: 15px;
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .filter-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn-filter {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            height: 40px;
        }
        
        .btn-filter:hover {
            background-color: #2980b9;
        }
        
        .chart-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .chart-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .chart-card {
            flex: 1;
            min-width: 300px;
            height: 300px;
            position: relative;
        }
        
        .report-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .report-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            background-color: #f8f9fa;
        }
        
        .report-tab.active {
            background-color: #fff;
            border-color: #ddd;
            border-bottom-color: #fff;
            font-weight: bold;
        }
        
        .unclaimed-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .unclaimed-table th, .unclaimed-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .unclaimed-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .unclaimed-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .no-data-message {
            text-align: center;
            padding: 20px;
            color: #777;
            font-style: italic;
        }
        
        .positive {
            color: #27ae60;
        }
        
        .negative {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>


        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Financial Reports</h1>
                <div class="user-actions">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if ($concerns_count > 0): ?>
                        <span class="notification-badge"><?php echo $concerns_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> logout
                    </a>
                </div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($missing_tables)): ?>
                <div class="alert alert-warning">
                    <p>Some required tables are missing. Please run the database setup script or contact the system administrator.</p>
                    <p>Missing tables: <?php echo implode(', ', $missing_tables); ?></p>
                </div>
            <?php endif; ?>

            <!-- Report Type Tabs -->
            <div class="report-tabs">
                <a href="?report_type=monthly&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="report-tab <?php echo $report_type === 'monthly' ? 'active' : ''; ?>">
                    Monthly Report
                </a>
                <a href="?report_type=yearly&year=<?php echo $year; ?>" class="report-tab <?php echo $report_type === 'yearly' ? 'active' : ''; ?>">
                    Yearly Report
                </a>
                <a href="?report_type=custom" class="report-tab <?php echo $report_type === 'custom' ? 'active' : ''; ?>">
                    Custom Date Range
                </a>
            </div>

            <!-- Report Filters -->
            <div class="report-filters">
                <form method="GET" action="">
                    <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                    
                    <div class="filter-row">
                        <?php if ($report_type === 'monthly'): ?>
                            <div class="filter-group">
                                <label for="month">Month</label>
                                <select class="filter-control" id="month" name="month">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $month == $i ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $i, 1, $year)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="year">Year</label>
                                <select class="filter-control" id="year" name="year">
                                    <?php for ($i = $current_year - 5; $i <= $current_year + 1; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        <?php elseif ($report_type === 'yearly'): ?>
                            <div class="filter-group">
                                <label for="year">Year</label>
                                <select class="filter-control" id="year" name="year">
                                    <?php for ($i = $current_year - 5; $i <= $current_year + 1; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        <?php elseif ($report_type === 'custom'): ?>
                            <div class="filter-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="filter-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="end_date">End Date</label>
                                <input type="date" class="filter-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="filter-group" style="flex: 0;">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <h2>Financial Summary for <?php echo $period_label; ?></h2>

            <!-- Financial Summary Cards -->
            <div class="report-container">
                <div class="report-card">
                    <h2>Total Funds Added</h2>
                    <div class="report-amount">₱ <?php echo number_format($total_funds_added, 2); ?></div>
                    <p>Funds added during this period</p>
                </div>
                
                <div class="report-card">
                    <h2>Total Allowances Released</h2>
                    <div class="report-amount">₱ <?php echo number_format($total_allowances, 2); ?></div>
                    <p><?php echo isset($allowance_data['total_students']) ? number_format($allowance_data['total_students']) : 0; ?> students received allowances</p>
                </div>
                
                <div class="report-card">
                    <h2>Unclaimed Allowances</h2>
                    <div class="report-amount">₱ <?php echo number_format($total_unclaimed, 2); ?></div>
                    <p><?php echo number_format($unclaimed_count); ?> students did not claim their allowances</p>
                </div>
                
                <div class="report-card">
                    <h2>Net Balance</h2>
                    <div class="report-amount <?php echo ($total_funds_added - $total_allowances) >= 0 ? 'positive' : 'negative'; ?>">
                        ₱ <?php echo number_format($total_funds_added - $total_allowances, 2); ?>
                    </div>
                    <p>Difference between funds added and allowances released</p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="chart-container">
                <h2>Financial Visualization</h2>
                
                <?php if ($report_type === 'yearly' && !empty($monthly_data)): ?>
                    <div class="chart-row">
                        <div class="chart-card">
                            <canvas id="monthlyAllowanceChart"></canvas>
                        </div>
                        
                        <div class="chart-card">
                            <canvas id="monthlyStudentChart"></canvas>
                        </div>
                    </div>
                <?php elseif (empty($missing_tables)): ?>
                    <div class="chart-row">
                        <div class="chart-card">
                            <canvas id="summaryChart"></canvas>
                        </div>
                        
                        <div class="chart-card">
                            <canvas id="claimStatusChart"></canvas>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-data-message">
                        <p>Charts will be available once the required database tables are set up.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Unclaimed Allowances Section -->
            <div class="report-card">
                <h2>Students Who Did Not Claim Their Allowances</h2>
                
                <?php if (!empty($missing_tables)): ?>
                    <div class="no-data-message">
                        <p>Data will be available once the required database tables are set up.</p>
                    </div>
                <?php else: ?>
                    <?php
                    // Get list of students who didn't claim their allowances
                    $unclaimed_students_query = $db->prepare("SELECT 
                        u.id, u.first_name, u.last_name, u.email,
                        ac.amount, a.schedule, ac.status
                        FROM allowance_claims ac
                        JOIN allowance_schedule a ON ac.allowance_id = a.id
                        JOIN users u ON ac.student_id = u.id
                        WHERE a.schedule BETWEEN ? AND ? AND ac.status = 'unclaimed'
                        ORDER BY a.schedule DESC");
                    $unclaimed_students_query->bind_param('ss', $start_date, $end_date);
                    $unclaimed_students_query->execute();
                    $result = $unclaimed_students_query->get_result();
                    $unclaimed_students = [];
                    
                    while ($row = $result->fetch_assoc()) {
                        $unclaimed_students[] = $row;
                    }
                    ?>
                    
                    <?php if (empty($unclaimed_students)): ?>
                        <div class="no-data-message">
                            <p>No unclaimed allowances found for this period.</p>
                        </div>
                    <?php else: ?>
                        <table class="unclaimed-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Amount</th>
                                    <th>Schedule Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unclaimed_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>₱ <?php echo number_format($student['amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($student['schedule'])); ?></td>
                                        <td><?php echo ucfirst($student['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for notifications and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle notification dropdown
            const notificationIcon = document.querySelector('.notification-icon');
            
            if (notificationIcon) {
                notificationIcon.addEventListener('click', function() {
                    // Redirect to student concerns page when notification is clicked
                    window.location.href = 'student_concerns.php';
                });
            }
            
            <?php if ($report_type === 'yearly' && !empty($monthly_data)): ?>
            // Monthly Allowance Chart
            const monthlyAllowanceCtx = document.getElementById('monthlyAllowanceChart').getContext('2d');
            new Chart(monthlyAllowanceCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['month'] . "'"; }, $monthly_data)); ?>],
                    datasets: [{

            <?php endif; ?>