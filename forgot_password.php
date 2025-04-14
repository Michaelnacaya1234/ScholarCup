<?php
require_once 'includes/Auth.php';
require_once 'includes/config/database.php';
require_once 'includes/config/email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        $error = 'Email is required';
    } else {
        $email = trim($_POST['email']);
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$userExists = $stmt->get_result()->fetch_assoc();
        
        if (strlen($email) < 5 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            try {
                $db = Database::getInstance();
                $auth = new Auth($db);
                
                $code = sprintf('%06d', random_int(0, 999999));
                if ($auth->initiatePasswordReset($email, $code) && EmailService::getInstance()->sendVerificationCode($email, $code)) {
                    $success = 'If an account exists with this email, you will receive a verification code.';
                } else {
                    // Use same message for security (prevents username enumeration)
                    $success = 'If an account exists with this username, you will receive a verification code via email.';
                }
            } catch (Exception $e) {
                error_log('Password reset error for email ' . $email . ': ' . $e->getMessage());
                $error = 'An unexpected error occurred. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            z-index: 10;
        }

        .login-logo {
            text-align: center;
            margin-right: 50px;
        }

        .main-logo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
        }

        .login-form {
            background-color: #1E3A8A;
            padding: 20px;
            border-radius: 10px;
            color: white;
            width: 300px;
        }

        .login-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 15px;
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .alert-danger {
            background-color: #dc3545;
            color: white;
        }

        .alert-success {
            background-color: #28a745;
            color: white;
        }

        .btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .btn-primary {
            background-color: #fff;
            color: #1E3A8A;
            font-weight: bold;
        }

        .btn-link {
            background: none;
            color: #bfdbfe;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-logo">
        <img src="student/CSO-logo.png" alt="Logo" class="main-logo">
        <h1>CSO</h1>
    </div>
    <div class="login-form">
        <h2>Forgot Password</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
            <a href="index.php" class="btn btn-link">Back to Login</a>
        </form>
    </div>
</div>
</body>
</html>