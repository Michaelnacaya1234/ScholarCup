<?php
require_once '../database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $student_id = $_POST['student_id'];
        $current_status = $_POST['current_status'];
        $academic_status = $_POST['academic_status'];
        $renewal_status = $_POST['renewal_status'];
        $total_return_service = $_POST['total_return_service'];

        // Update current status
        $stmt = $db->prepare("INSERT INTO student_current_status (user_id, status) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE status = ?");
        $stmt->bind_param("iss", $student_id, $current_status, $current_status);
        $stmt->execute();

        // Update academic and renewal status
        $stmt = $db->prepare("INSERT INTO student_academic_status (user_id, status, renewal_status, total_return_service) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE status = ?, renewal_status = ?, total_return_service = ?");
        $stmt->bind_param("issiisi", $student_id, $academic_status, $renewal_status, $total_return_service,
                         $academic_status, $renewal_status, $total_return_service);
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = 'Student status updated successfully';
    } catch (Exception $e) {
        $response['message'] = 'Error updating student status: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
