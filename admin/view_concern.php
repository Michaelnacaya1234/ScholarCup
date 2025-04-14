<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

// Check if concern ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid concern ID.</div>';
    exit;
}

$concern_id = intval($_GET['id']);
$concern = null;

try {
    // Get concern details with student information
    $query = "SELECT sc.id, sc.subject, sc.message, sc.status, sc.created_at, 
             u.id as user_id, u.first_name, u.middle_name, u.last_name, u.email,
             sp.course, sp.year_level, sp.contact_number, sp.address
             FROM student_concerns sc
             JOIN users u ON sc.student_id = u.id
             LEFT JOIN student_profiles sp ON u.id = sp.user_id
             WHERE sc.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $concern_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<div class="alert alert-danger">Concern not found.</div>';
        exit;
    }
    
    $concern = $result->fetch_assoc();
} catch (Exception $e) {
    echo '<div class="alert alert-danger">An error occurred: ' . htmlspecialchars($e->getMessage()) . '</div>';
    error_log("Error in view_concern.php: " . $e->getMessage());
    exit;
}

// Format student name
$student_name = htmlspecialchars($concern['first_name']);
if (!empty($concern['middle_name'])) {
    $student_name .= ' ' . htmlspecialchars($concern['middle_name']);
}
$student_name .= ' ' . htmlspecialchars($concern['last_name']);

// Format year level with ordinal suffix
function getOrdinalSuffix($number) {
    if (!in_array(($number % 100), [11, 12, 13])) {
        switch ($number % 10) {
            case 1:  return 'st';
            case 2:  return 'nd';
            case 3:  return 'rd';
        }
    }
    return 'th';
}

$year_level_display = !empty($concern['year_level']) ? 
    htmlspecialchars($concern['year_level']) . getOrdinalSuffix($concern['year_level']) . ' Year' : 
    'N/A';
?>

<h2>Concern Details</h2>

<div class="concern-details">
    <h3><?php echo htmlspecialchars($concern['subject']); ?></h3>
    
    <div class="concern-meta">
        <span>Submitted: <?php echo date('F d, Y h:i A', strtotime($concern['created_at'])); ?></span>
        <span>
            Status: 
            <select class="status-dropdown" onchange="updateConcernStatus(<?php echo $concern_id; ?>, this)">
                <option value="open" <?php echo ($concern['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                <option value="in_progress" <?php echo ($concern['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                <option value="resolved" <?php echo ($concern['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
            </select>
        </span>
    </div>
    
    <div class="student-info">
        <h4>Student Information</h4>
        <p><strong>Name:</strong> <?php echo $student_name; ?></p>
        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($concern['student_id'] ?? 'N/A'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($concern['email']); ?></p>
        <p><strong>Course:</strong> <?php echo htmlspecialchars($concern['course'] ?? 'N/A'); ?></p>
        <p><strong>Year Level:</strong> <?php echo $year_level_display; ?></p>
        <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($concern['contact_number'] ?? 'N/A'); ?></p>
        <?php if (!empty($concern['address'])): ?>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($concern['address']); ?></p>
        <?php endif; ?>
    </div>
    
    <h4>Concern Message</h4>
    <div class="concern-message">
        <?php echo nl2br(htmlspecialchars($concern['message'])); ?>
    </div>
    
    <!-- Add reply functionality here if needed -->
</div>