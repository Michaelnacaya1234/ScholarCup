<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$success_message = '';
$error_message = '';

// Get all students for dropdown
try {
    $stmt = $db->prepare("SELECT id, first_name, middle_name, last_name, school FROM users WHERE role = 'student' ORDER BY last_name, first_name");
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} catch (Exception $e) {
    $error_message = "Error retrieving students: " . $e->getMessage();
    error_log("Error in add allowance page: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? 0;
    $cashier_no = $_POST['cashier_no'] ?? '';
    $payroll_no = $_POST['payroll_no'] ?? '';
    $period = $_POST['period'] ?? '';
    $venue = $_POST['venue'] ?? '';
    $schedule_date = $_POST['schedule_date'] ?? '';
    $schedule_time = $_POST['schedule_time'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    
    // Validate required fields
    if (empty($student_id) || empty($period) || empty($venue) || empty($schedule_date) || empty($schedule_time) || empty($amount)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Combine date and time
            $schedule = $schedule_date . ' ' . $schedule_time;
            
            // Check if allowance_schedule table exists
            $table_check = $db->query("SHOW TABLES LIKE 'allowance_schedule'");
            
            if ($table_check->num_rows == 0) {
                // Create the table if it doesn't exist
                $db->query("CREATE TABLE IF NOT EXISTS allowance_schedule (
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
                )");
            }
            
            // Insert new allowance schedule
            $stmt = $db->prepare("INSERT INTO allowance_schedule (student_id, cashier_no, payroll_no, period, venue, schedule, amount, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssdssi", $student_id, $cashier_no, $payroll_no, $period, $venue, $schedule, $amount, $status, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success_message = "Allowance schedule has been added successfully.";
                // Clear form data
                $student_id = $cashier_no = $payroll_no = $period = $venue = $schedule_date = $schedule_time = $amount = '';
                $status = 'pending';
            } else {
                $error_message = "Failed to add allowance schedule. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error adding allowance schedule: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Allowance Schedule - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
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
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>ADD ALLOWANCE SCHEDULE</h1>
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
            
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="student_id">Scholar Name *</label>
                        <select name="student_id" id="student_id" class="form-control" required>
                            <option value="">Select Scholar</option>
                            <?php foreach ($students as $student): ?>
                                <?php 
                                $student_name = htmlspecialchars($student['last_name']) . ', ' . htmlspecialchars($student['first_name']);
                                if (!empty($student['middle_name'])) {
                                    $student_name .= ' ' . htmlspecialchars($student['middle_name'][0]) . '.';
                                }
                                $student_name .= ' (' . htmlspecialchars($student['school']) . ')';
                                ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo $student_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cashier_no">Cashier No.</label>
                            <input type="text" name="cashier_no" id="cashier_no" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="payroll_no">Payroll No.</label>
                            <input type="text" name="payroll_no" id="payroll_no" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="period">Period *</label>
                        <input type="text" name="period" id="period" class="form-control" required placeholder="e.g., OCTOBER - DECEMBER 2024">
                    </div>
                    
                    <div class="form-group">
                        <label for="venue">Venue *</label>
                        <input type="text" name="venue" id="venue" class="form-control" required placeholder="e.g., City Hall Quadrangle">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="schedule_date">Schedule Date *</label>
                            <input type="date" name="schedule_date" id="schedule_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="schedule_time">Schedule Time *</label>
                            <input type="time" name="schedule_time" id="schedule_time" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount *</label>
                            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="pending">Pending</option>
                                <option value="released">Released</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-primary">Add Allowance Schedule</button>
                        <a href="allowance_schedule.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>