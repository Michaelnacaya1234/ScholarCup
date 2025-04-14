<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$response = ['success' => false, 'message' => ''];

// Check if request is POST and has required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    
    if (empty($id) || empty($type)) {
        $response['message'] = 'Invalid parameters provided.';
    } else {
        try {
            if ($type === 'general') {
                // Delete from announcements table
                $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Announcement deleted successfully.';
                } else {
                    $response['message'] = 'Failed to delete announcement.';
                }
            } elseif ($type === 'return_service') {
                // Delete from return_service_announcements table
                $stmt = $db->prepare("DELETE FROM return_service_announcements WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Return service activity deleted successfully.';
                } else {
                    $response['message'] = 'Failed to delete return service activity.';
                }
            } else {
                $response['message'] = 'Invalid announcement type.';
            }
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
            error_log("Error in delete_announcement.php: " . $e->getMessage());
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);