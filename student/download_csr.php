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

// Fetch student information
$stmt = $db->prepare("SELECT s.first_name, s.last_name, s.student_id, s.course, s.year_level, 
                     u.email FROM student_profiles s 
                     JOIN users u ON s.user_id = u.id 
                     WHERE s.user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Redirect with error if student profile not found
    header('Location: student_status.php?status=error&message=' . urlencode("Student profile not found."));
    exit;
}

$student = $result->fetch_assoc();

// Fetch return service records
try {
    // First check if the required tables exist
    $table_check = $db->query("SHOW TABLES LIKE 'student_academic_status'");
    $academic_table_exists = ($table_check->num_rows > 0);
    
    $table_check = $db->query("SHOW TABLES LIKE 'return_service'");
    $rs_table_exists = ($table_check->num_rows > 0);
    
    if ($academic_table_exists && $rs_table_exists) {
        // Both tables exist, proceed with original query
        $rs_stmt = $db->prepare("SELECT rs.*, s.academic_year, s.semester 
                           FROM return_service rs 
                           JOIN student_academic_status s ON rs.user_id = s.user_id 
                                                        AND rs.academic_year = s.academic_year 
                                                        AND rs.semester = s.semester 
                           WHERE rs.user_id = ? 
                           ORDER BY s.academic_year DESC, s.semester DESC");
        $rs_stmt->bind_param('i', $user_id);
        $rs_stmt->execute();
        $rs_result = $rs_stmt->get_result();
    } else {
        // Try to get data from return_service_activities as fallback
        $rs_stmt = $db->prepare("SELECT id, student_id as user_id, activity_name as activity_description, 
                             hours_rendered as hours_completed, 
                             DATE_FORMAT(created_at, '%Y') as academic_year,
                             'N/A' as semester
                             FROM return_service_activities 
                             WHERE student_id = ? AND status = 'approved'
                             ORDER BY created_at DESC");
        $rs_stmt->bind_param('i', $user_id);
        $rs_stmt->execute();
        $rs_result = $rs_stmt->get_result();
    }
} catch (Exception $e) {
    // Create an empty result set if there's an error
    $rs_result = new mysqli_result();
}

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Certificate_of_Return_Service.pdf"');

// Use TCPDF library to generate PDF
require_once('../includes/tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Scholar System');
$pdf->SetAuthor('City Scholarship Office');
$pdf->SetTitle('Certificate of Return Service');
$pdf->SetSubject('Certificate of Return Service');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add logo
$pdf->Image('../assets/images/logo.png', 15, 15, 30, 30, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

// Add title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'CERTIFICATE OF RETURN SERVICE', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'City Scholarship Office', 0, 1, 'C');
$pdf->Ln(10);

// Add student information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'STUDENT INFORMATION', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$pdf->Cell(50, 10, 'Name:', 0, 0);
$pdf->Cell(0, 10, $student['first_name'] . ' ' . $student['last_name'], 0, 1);

$pdf->Cell(50, 10, 'Student ID:', 0, 0);
$pdf->Cell(0, 10, $student['student_id'], 0, 1);

$pdf->Cell(50, 10, 'Course:', 0, 0);
$pdf->Cell(0, 10, $student['course'], 0, 1);

$pdf->Cell(50, 10, 'Year Level:', 0, 0);
$pdf->Cell(0, 10, $student['year_level'], 0, 1);

$pdf->Cell(50, 10, 'Email:', 0, 0);
$pdf->Cell(0, 10, $student['email'], 0, 1);

$pdf->Ln(10);

// Add return service information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'RETURN SERVICE DETAILS', 0, 1, 'L');

// Create table header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 10, 'Academic Year', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Semester', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Hours Completed', 1, 0, 'C', true);
$pdf->Cell(65, 10, 'Activities', 1, 1, 'C', true);

// Add table rows
$pdf->SetFont('helvetica', '', 10);

$total_hours = 0;

if ($rs_result->num_rows > 0) {
    while ($row = $rs_result->fetch_assoc()) {
        $pdf->Cell(40, 10, $row['academic_year'], 1, 0, 'C');
        $pdf->Cell(30, 10, $row['semester'], 1, 0, 'C');
        $pdf->Cell(40, 10, $row['hours_completed'] . ' hours', 1, 0, 'C');
        $pdf->Cell(65, 10, $row['activity_description'], 1, 1, 'L');
        
        $total_hours += $row['hours_completed'];
    }
} else {
    $pdf->Cell(175, 10, 'No return service records found.', 1, 1, 'C');
}

// Add total hours
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 10, 'TOTAL HOURS', 1, 0, 'R', true);
$pdf->Cell(40, 10, $total_hours . ' hours', 1, 0, 'C', true);
$pdf->Cell(65, 10, '', 1, 1);

$pdf->Ln(20);

// Add certification text
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 10, 'This is to certify that the above-named student has completed the return service hours as indicated in this document as part of the requirements for the City Scholarship Program.', 0, 'L');

$pdf->Ln(20);

// Add signature lines
$pdf->Cell(80, 10, 'Certified by:', 0, 0);
$pdf->Cell(80, 10, 'Approved by:', 0, 1);
$pdf->Ln(15);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(80, 10, '_______________________', 0, 0, 'C');
$pdf->Cell(80, 10, '_______________________', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(80, 10, 'Scholarship Coordinator', 0, 0, 'C');
$pdf->Cell(80, 10, 'City Scholarship Director', 0, 1, 'C');

// Output PDF
$pdf->Output('Certificate_of_Return_Service.pdf', 'D');