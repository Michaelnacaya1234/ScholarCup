<?php
require_once '../database.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: students_status.php');
    exit;
}

$studentId = $_GET['id'];

$query = "DELETE FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $studentId);

if ($stmt->execute()) {
    $query = "DELETE FROM student_profiles WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();

    $query = "DELETE FROM student_current_status WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();

    $query = "DELETE FROM student_academic_status WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
}

header('Location: students_status.php');
exit;
?>
