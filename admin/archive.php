<?php
session_start();
include '../database.php';
include '../includes/admin_auth_check.php';

$type = $_GET['type'] ?? 'student';
$success_message = '';
$error_message = '';

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'restore' || $_POST['action'] === 'restore_all') {
        $archive_ids = $_POST['action'] === 'restore' ? [$_POST['archive_id']] : $_POST['archive_ids'];
        
        try {
            $db->begin_transaction();
            
            foreach ($archive_ids as $archive_id) {
                // Get archived data
                $stmt = $db->prepare("SELECT * FROM archived_users WHERE id = ?");
                $stmt->bind_param("i", $archive_id);
                $stmt->execute();
                $archived = $stmt->get_result()->fetch_assoc();
                
                if ($archived) {
                    // Restore user
                    $stmt = $db->prepare("INSERT INTO users (id, email, first_name, last_name, middle_name, role, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, 'inactive')");
                    $stmt->bind_param("isssss", 
                        $archived['user_id'], 
                        $archived['email'],
                        $archived['first_name'],
                        $archived['last_name'],
                        $archived['middle_name'],
                        $archived['role']
                    );
                    $stmt->execute();
                    
                    // Restore profile data
                    $profile_data = json_decode($archived['profile_data'], true);
                    if ($archived['role'] === 'student') {
                        $stmt = $db->prepare("INSERT INTO student_profiles (user_id, course, year_level, contact_number) 
                                            VALUES (?, ?, ?, ?)");
                    } else {
                        $stmt = $db->prepare("INSERT INTO staff_profiles (user_id, department, position, contact_number) 
                                            VALUES (?, ?, ?, ?)");
                    }
                    $stmt->bind_param("isss", 
                        $archived['user_id'],
                        $profile_data['department'] ?? $profile_data['course'],
                        $profile_data['position'] ?? $profile_data['year_level'],
                        $profile_data['contact_number']
                    );
                    $stmt->execute();
                    
                    // Remove from archive
                    $stmt = $db->prepare("DELETE FROM archived_users WHERE id = ?");
                    $stmt->bind_param("i", $archive_id);
                    $stmt->execute();
                }
            }
            
            $db->commit();
            $success_message = "Account(s) restored successfully.";
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Error restoring account(s): " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete' || $_POST['action'] === 'delete_all') {
        $archive_ids = $_POST['action'] === 'delete' ? [$_POST['archive_id']] : $_POST['archive_ids'];
        
        try {
            $stmt = $db->prepare("DELETE FROM archived_users WHERE id IN (" . str_repeat('?,', count($archive_ids) - 1) . "?)");
            $stmt->bind_param(str_repeat('i', count($archive_ids)), ...$archive_ids);
            
            if ($stmt->execute()) {
                $success_message = "Account(s) permanently deleted.";
            } else {
                $error_message = "Failed to delete account(s).";
            }
        } catch (Exception $e) {
            $error_message = "Error deleting account(s): " . $e->getMessage();
        }
    }
}

// Get archived accounts
try {
    $stmt = $db->prepare("SELECT * FROM archived_users WHERE role = ? ORDER BY archived_at DESC");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $archived_accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error retrieving archived accounts: " . $e->getMessage();
    $archived_accounts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived <?= ucfirst($type) ?> Accounts - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Copy relevant styles from accounts pages */
        .bulk-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .restore-all-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .delete-all-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .archived-date {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>ARCHIVED <?= strtoupper($type) ?> ACCOUNTS</h1>
                <div class="header-actions">
                    <a href="<?= $type ?>_accounts.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to <?= ucfirst($type) ?> Accounts
                    </a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($archived_accounts)): ?>
                <div class="bulk-actions">
                    <button class="restore-all-btn" onclick="restoreAll()">
                        <i class="fas fa-undo"></i> Restore All
                    </button>
                    <button class="delete-all-btn" onclick="deleteAll()">
                        <i class="fas fa-trash"></i> Delete All Permanently
                    </button>
                </div>
                
                <table class="accounts-table">
                    <thead>
                        <tr>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>ARCHIVED DATE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archived_accounts as $account): ?>
                            <tr>
                                <td><?= htmlspecialchars($account['first_name'] . ' ' . $account['last_name']) ?></td>
                                <td><?= htmlspecialchars($account['email']) ?></td>
                                <td class="archived-date">
                                    <?= date('F j, Y g:i A', strtotime($account['archived_at'])) ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="restore-btn" onclick="restore(<?= $account['id'] ?>)">
                                            Restore
                                        </button>
                                        <button class="delete-btn" onclick="deletePermanently(<?= $account['id'] ?>)">
                                            Delete Permanently
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">
                    <i class="fas fa-archive fa-3x"></i>
                    <h3>No Archived Accounts</h3>
                    <p>There are no archived <?= $type ?> accounts.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function restore(id) {
            Swal.fire({
                title: 'Restore Account?',
                text: "This account will be restored and reactivated.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('restore', id);
                }
            });
        }
        
        function deletePermanently(id) {
            Swal.fire({
                title: 'Delete Permanently?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('delete', id);
                }
            });
        }
        
        function restoreAll() {
            Swal.fire({
                title: 'Restore All Accounts?',
                text: "All archived accounts will be restored and reactivated.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('restore_all');
                }
            });
        }
        
        function deleteAll() {
            Swal.fire({
                title: 'Delete All Permanently?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('delete_all');
                }
            });
        }
        
        function submitForm(action, id = null) {
            const form = document.createElement('form');
            form.method = 'POST';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            if (id) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'archive_id';
                idInput.value = id;
                form.appendChild(idInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>