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
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $status = $_POST['status'];

    $query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("sssi", $firstName, $lastName, $email, $studentId);

    if ($stmt->execute()) {
        $query = "UPDATE student_current_status SET status = ? WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("si", $status, $studentId);
        $stmt->execute();

        $message = "Student details updated successfully.";
    } else {
        $message = "Failed to update student details.";
    }
}

$query = "SELECT u.first_name, u.last_name, u.email, scs.status 
          FROM users u 
          LEFT JOIN student_current_status scs ON u.id = scs.user_id 
          WHERE u.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit Student</h1>
        <?php if ($message): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="first_name">First Name:</label>
            <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
            
            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
            
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
            
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="complied" <?php echo $student['status'] === 'complied' ? 'selected' : ''; ?>>Complied</option>
                <option value="not comply" <?php echo $student['status'] === 'not comply' ? 'selected' : ''; ?>>Not Comply</option>
                <option value="pending" <?php echo $student['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
            
            <button type="submit">Update</button>
        </form>
        <a href="students_status.php">Back to Students List</a>
    </div>
</body>
</html>
