<?php
// Include necessary files and start session
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Manage Allowance Releasing Schedule';
$conn = getConnection();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new allowance schedule
    if (isset($_POST['add_allowance'])) {
        $cashier_no = sanitizeInput($_POST['cashier_no']);
        $payroll_no = sanitizeInput($_POST['payroll_no']);
        $period = sanitizeInput($_POST['period']);
        $venue = sanitizeInput($_POST['venue']);
        $schedule_date = sanitizeInput($_POST['schedule_date']);
        $schedule_time_start = sanitizeInput($_POST['schedule_time_start']);
        $schedule_time_end = sanitizeInput($_POST['schedule_time_end']);
        $status = sanitizeInput($_POST['status']);
        
        $query = "INSERT INTO allowance_releasing_schedule (cashier_no, payroll_no, period, venue, schedule_date, schedule_time_start, schedule_time_end, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'iissssss', $cashier_no, $payroll_no, $period, $venue, $schedule_date, $schedule_time_start, $schedule_time_end, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Allowance schedule added successfully.";
        } else {
            $error = "Error adding allowance schedule: " . mysqli_error($conn);
        }
    }
    
    // Update existing allowance schedule
    if (isset($_POST['update_allowance'])) {
        $id = sanitizeInput($_POST['id']);
        $cashier_no = sanitizeInput($_POST['cashier_no']);
        $payroll_no = sanitizeInput($_POST['payroll_no']);
        $period = sanitizeInput($_POST['period']);
        $venue = sanitizeInput($_POST['venue']);
        $schedule_date = sanitizeInput($_POST['schedule_date']);
        $schedule_time_start = sanitizeInput($_POST['schedule_time_start']);
        $schedule_time_end = sanitizeInput($_POST['schedule_time_end']);
        $status = sanitizeInput($_POST['status']);
        
        $query = "UPDATE allowance_releasing_schedule 
                 SET cashier_no = ?, payroll_no = ?, period = ?, venue = ?, 
                     schedule_date = ?, schedule_time_start = ?, schedule_time_end = ?, status = ? 
                 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'iissssssi', $cashier_no, $payroll_no, $period, $venue, $schedule_date, $schedule_time_start, $schedule_time_end, $status, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Allowance schedule updated successfully.";
        } else {
            $error = "Error updating allowance schedule: " . mysqli_error($conn);
        }
    }
    
    // Delete allowance schedule
    if (isset($_POST['delete_allowance'])) {
        $id = sanitizeInput($_POST['id']);
        
        $query = "DELETE FROM allowance_releasing_schedule WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Allowance schedule deleted successfully.";
        } else {
            $error = "Error deleting allowance schedule: " . mysqli_error($conn);
        }
    }
}

// Get all allowance schedules
$query = "SELECT * FROM allowance_releasing_schedule ORDER BY schedule_date DESC, schedule_time_start ASC";
$result = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Allowance Releasing Schedule</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAllowanceModal">
                    <i class="fas fa-plus"></i> Add New Schedule
                </button>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>CASHIER NO.</th>
                            <th>PAYROLL NO.</th>
                            <th>PERIOD</th>
                            <th>VENUE</th>
                            <th>SCHEDULE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php 
                                    // Format schedule date and time
                                    $schedule_date = date('F j, Y', strtotime($row['schedule_date']));
                                    $start_time = date('g:ia', strtotime($row['schedule_time_start']));
                                    $end_time = date('g:ia', strtotime($row['schedule_time_end']));
                                    $schedule = "$schedule_date - $start_time - $end_time";
                                    
                                    $status_class = $row['status'] === 'claimed' ? 'success' : 'danger';
                                    $status_text = $row['status'] === 'claimed' ? 'CLAIMED' : 'NOT CLAIM';
                                ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['cashier_no']; ?></td>
                                    <td><?php echo $row['payroll_no']; ?></td>
                                    <td><?php echo $row['period']; ?></td>
                                    <td><?php echo $row['venue']; ?></td>
                                    <td><?php echo $schedule; ?></td>
                                    <td class="text-<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                                data-bs-toggle="modal" data-bs-target="#editAllowanceModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-cashier="<?php echo $row['cashier_no']; ?>"
                                                data-payroll="<?php echo $row['payroll_no']; ?>"
                                                data-period="<?php echo $row['period']; ?>"
                                                data-venue="<?php echo $row['venue']; ?>"
                                                data-date="<?php echo $row['schedule_date']; ?>"
                                                data-start="<?php echo $row['schedule_time_start']; ?>"
                                                data-end="<?php echo $row['schedule_time_end']; ?>"
                                                data-status="<?php echo $row['status']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-bs-toggle="modal" data-bs-target="#deleteAllowanceModal"
                                                data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No allowance releasing schedules available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add Allowance Modal -->
<div class="modal fade" id="addAllowanceModal" tabindex="-1" aria-labelledby="addAllowanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addAllowanceModalLabel">Add New Allowance Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cashier_no" class="form-label">Cashier No.</label>
                            <input type="number" class="form-control" id="cashier_no" name="cashier_no" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payroll_no" class="form-label">Payroll No.</label>
                            <input type="number" class="form-control" id="payroll_no" name="payroll_no" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="period" class="form-label">Period</label>
                        <input type="text" class="form-control" id="period" name="period" required>
                    </div>
                    <div class="mb-3">
                        <label for="venue" class="form-label">Venue</label>
                        <input type="text" class="form-control" id="venue" name="venue" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="schedule_date" class="form-label">Schedule Date</label>
                            <input type="date" class="form-control" id="schedule_date" name="schedule_date" required>
                        </div>
                        <div class="col-md-4">
                            <label for="schedule_time_start" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="schedule_time_start" name="schedule_time_start" required>
                        </div>
                        <div class="col-md-4">
                            <label for="schedule_time_end" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="schedule_time_end" name="schedule_time_end" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="not_claim">NOT CLAIM</option>
                            <option value="claimed">CLAIMED</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_allowance" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Allowance Modal -->
<div class="modal fade" id="editAllowanceModal" tabindex="-1" aria-labelledby="editAllowanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editAllowanceModalLabel">Edit Allowance Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_cashier_no" class="form-label">Cashier No.</label>
                            <input type="number" class="form-control" id="edit_cashier_no" name="cashier_no" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_payroll_no" class="form-label">Payroll No.</label>
                            <input type="number" class="form-control" id="edit_payroll_no" name="payroll_no" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_period" class="form-label">Period</label>
                        <input type="text" class="form-control" id="edit_period" name="period" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_venue" class="form-label">Venue</label>
                        <input type="text" class="form-control" id="edit_venue" name="venue" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="edit_schedule_date" class="form-label">Schedule Date</label>
                            <input type="date" class="form-control" id="edit_schedule_date" name="schedule_date" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_schedule_time_start" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="edit_schedule_time_start" name="schedule_time_start" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_schedule_time_end" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="edit_schedule_time_end" name="schedule_time_end" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="not_claim">NOT CLAIM</option>
                            <option value="claimed">CLAIMED</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_allowance" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Allowance Modal -->
<div class="modal fade" id="deleteAllowanceModal" tabindex="-1" aria-labelledby="deleteAllowanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAllowanceModalLabel">Delete Allowance Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" id="delete_id" name="id">
                    <p>Are you sure you want to delete this allowance schedule? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_allowance" class="btn btn-danger">Delete Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Populate edit modal with data
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const cashier = this.getAttribute('data-cashier');
                const payroll = this.getAttribute('data-payroll');
                const period = this.getAttribute('data-period');
                const venue = this.getAttribute('data-venue');
                const date = this.getAttribute('data-date');
                const start = this.getAttribute('data-start');
                const end = this.getAttribute('data-end');
                const status = this.getAttribute('data-status');
                
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_cashier_no').value = cashier;
                document.getElementById('edit_payroll_no').value = payroll;
                document.getElementById('edit_period').value = period;
                document.getElementById('edit_venue').value = venue;
                document.getElementById('edit_schedule_date').value = date;
                document.getElementById('edit_schedule_time_start').value = start;
                document.getElementById('edit_schedule_time_end').value = end;
                document.getElementById('edit_status').value = status;
            });
        });
        
        // Set delete ID
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                document.getElementById('delete_id').value = id;
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>