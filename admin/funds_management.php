<?php
session_start();
require_once '../includes/config/database.php';
require_once '../includes/Auth.php';

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

// Check if funds_management table exists, create if not
$table_check = $db->query("SHOW TABLES LIKE 'funds_management'");
if ($table_check->num_rows == 0) {
    $create_table_query = "CREATE TABLE IF NOT EXISTS funds_management (
        id INT AUTO_INCREMENT PRIMARY KEY,
        amount DECIMAL(10,2) NOT NULL,
        transaction_type ENUM('deposit', 'withdrawal', 'adjustment') NOT NULL,
        reference_number VARCHAR(50),
        source VARCHAR(100) NOT NULL,
        description TEXT,
        transaction_date DATE NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    
    $db->query($create_table_query);
}

// Handle form submission for adding funds
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_funds') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $transaction_type = filter_input(INPUT_POST, 'transaction_type', FILTER_SANITIZE_STRING);
    $reference_number = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    $source = filter_input(INPUT_POST, 'source', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $transaction_date = filter_input(INPUT_POST, 'transaction_date', FILTER_SANITIZE_STRING);
    
    // Validate inputs
    if (!$amount || $amount <= 0) {
        $error_message = "Please enter a valid amount greater than zero.";
    } elseif (!in_array($transaction_type, ['deposit', 'withdrawal', 'adjustment'])) {
        $error_message = "Please select a valid transaction type.";
    } elseif (empty($source)) {
        $error_message = "Please enter the source of funds.";
    } elseif (empty($transaction_date)) {
        $error_message = "Please select a transaction date.";
    } else {
        // Insert the fund transaction
        try {
            $stmt = $db->prepare("INSERT INTO funds_management (amount, transaction_type, reference_number, source, description, transaction_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("dsssssi", $amount, $transaction_type, $reference_number, $source, $description, $transaction_date, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Funds transaction recorded successfully.";
            } else {
                $error_message = "Failed to record funds transaction. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            error_log("Error in funds management: " . $e->getMessage());
        }
    }
}

// Get total available funds (deposits - withdrawals)
$total_funds_query = $db->query("SELECT 
    COALESCE(SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END), 0) +
    COALESCE(SUM(CASE WHEN transaction_type = 'adjustment' THEN amount ELSE 0 END), 0) as total_funds
    FROM funds_management");
$total_funds = $total_funds_query->fetch_assoc()['total_funds'] ?? 0;

// Get recent transactions
$recent_transactions = [];
$transactions_query = $db->query("SELECT fm.*, u.first_name, u.last_name 
    FROM funds_management fm 
    JOIN users u ON fm.created_by = u.id 
    ORDER BY fm.created_at DESC LIMIT 10");

while ($row = $transactions_query->fetch_assoc()) {
    $recent_transactions[] = $row;
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
    <title>Funds Management - CSO Scholar Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .funds-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .funds-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex: 1;
            min-width: 300px;
        }
        
        .funds-card h2 {
            margin-top: 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .funds-amount {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .funds-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .transactions-table th, .transactions-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .transactions-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .transactions-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .deposit {
            color: #27ae60;
        }
        
        .withdrawal {
            color: #e74c3c;
        }
        
        .adjustment {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Include Admin Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Funds Management</h1>
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

            <!-- Funds Overview -->
            <div class="funds-container">
                <div class="funds-card">
                    <h2>Total Available Funds</h2>
                    <div class="funds-amount">₱ <?php echo number_format($total_funds, 2); ?></div>
                    <p>Current balance for allowance disbursement</p>
                </div>
            </div>

            <!-- Add Funds Form -->
            <div class="funds-form">
                <h2>Record Fund Transaction</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_funds">
                    
                    <div class="form-row" style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="amount">Amount (₱)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="transaction_type">Transaction Type</label>
                            <select class="form-control" id="transaction_type" name="transaction_type" required>
                                <option value="">Select Transaction Type</option>
                                <option value="deposit">Deposit (Add Funds)</option>
                                <option value="withdrawal">Withdrawal (Remove Funds)</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="transaction_date">Transaction Date</label>
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" required>
                        </div>
                    </div>
                    
                    <div class="form-row" style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="reference_number">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number">
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="source">Source</label>
                            <input type="text" class="form-control" id="source" name="source" required placeholder="e.g., Government Budget, Donation, etc.">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">Record Transaction</button>
                </form>
            </div>

            <!-- Recent Transactions -->
            <div class="funds-card">
                <h2>Recent Transactions</h2>
                
                <?php if (empty($recent_transactions)): ?>
                    <p>No transactions recorded yet.</p>
                <?php else: ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Source</th>
                                <th>Reference</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                    <td class="<?php echo $transaction['transaction_type']; ?>">
                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                    </td>
                                    <td class="<?php echo $transaction['transaction_type']; ?>">
                                        <?php if ($transaction['transaction_type'] == 'deposit'): ?>+<?php endif; ?>
                                        <?php if ($transaction['transaction_type'] == 'withdrawal'): ?>-<?php endif; ?>
                                        ₱ <?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['source']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['reference_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        });
    </script>
</body>
</html>