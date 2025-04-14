<?php
session_start();
include 'database.php';
include 'student/login_check.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";

    } else {
        $login_result = checkLogin($email, $password, '', '');


        if ($login_result['success'] ?? false) {
            redirectToDashboard();
        } else {
            $error = $login_result['error'] ?? "Invalid credentials. Please try again.";
            // Log failed login attempt
            error_log("Failed login attempt for email: " . htmlspecialchars($email));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDO Scholar Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            font-family: 'Arial', sans-serif;
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 3rem;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .header.scrolled {
            padding: 1rem 3rem;
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .logo-header {
            display: flex;
            align-items: center;
        }
        
        .logo-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            transition: transform 0.3s ease;
        }
        
        .logo-header img:hover {
            transform: scale(1.1);
        }
        
        .logo-header h1 {
            font-size: 1.5rem;
            color: #003366;
            font-weight: bold;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-links a {
            color: #003366;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            position: relative;
            padding: 0.5rem 0;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #0066cc;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #0066cc;
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }

        /* Login Form Styles */
        .flip-container {
            perspective: 1000px;
            width: 100%;
            height: 100%;
        }

        .flipper {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.8s;
            transform-style: preserve-3d;
        }

        .flip-container.flipped .flipper {
            transform: rotateY(180deg);
        }

        .front, .back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .back {
            transform: rotateY(180deg);
        }

        .main-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
            padding: 120px 40px 40px;
            justify-content: center;
            gap: 80px;
            align-items: center;
        }

        .left-side {
            flex: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-color: transparent;
            max-width: 600px;
        }

        .right-side {
            flex: 1;
            max-width: 360px;
            min-height: 420px;
            background-color: #003366;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 35px;
            color: white;
            border-radius: 15px;
            margin: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .logo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin-bottom: 40px;
            object-fit: cover;
        }

        .title {
            text-align: center;
            font-size: 32px;
            line-height: 1.5;
            margin-bottom: 40px;
            color: #003366;
            font-weight: bold;
        }

        .login-form {
            width: 100%;
            max-width: 300px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group input:focus {
            outline: none;
            border-color: white;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .forgot-password {
            text-align: center;
            margin: 20px 0;
            width: 100%;
        }

        .forgot-password a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.8;
            padding: 8px 16px;
            display: inline-block;
            transition: opacity 0.3s ease;
        }

        .forgot-password a:hover {
            opacity: 1;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: white;
            color: #003366;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }

        .error {
            color: #ff4444;
            margin-bottom: 15px;
            text-align: center;
            background-color: rgba(255, 68, 68, 0.1);
            padding: 8px;
            border-radius: 6px;
            font-size: 12px;
        }

        /* Features Section */
        .features {
            padding: 5rem 3rem;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: #003366;
            margin-bottom: 1rem;
        }
        
        .section-title p {
            font-size: 1.1rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: #0066cc;
            margin-bottom: 1.5rem;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            color: #003366;
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* About Section */
        .about-section {
            padding: 5rem 3rem;
            background: linear-gradient(135deg, #003366 0%, #0066cc 100%);
            color: white;
            text-align: center;
        }
        
        .about-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        
        .about-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #003366 0%, #001a33 100%);
            color: white;
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .footer-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .footer-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 20px rgba(0, 102, 204, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1rem;
            animation: glowing 3s infinite alternate;
        }
        
        @keyframes glowing {
            0% {
                box-shadow: 0 0 10px rgba(0, 102, 204, 0.5), 0 0 20px rgba(0, 102, 204, 0.5);
            }
            100% {
                box-shadow: 0 0 20px rgba(0, 102, 204, 0.8), 0 0 40px rgba(0, 102, 204, 0.8), 0 0 60px rgba(0, 102, 204, 0.5);
            }
        }
        
        .footer-img:hover {
            transform: scale(1.05);
            box-shadow: 0 0 25px rgba(0, 102, 204, 0.8), 0 0 50px rgba(0, 102, 204, 0.6);
            animation: none;
        }
        
        .footer-logo h3 {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
            letter-spacing: 1px;
        }
        
        .footer-info {
            max-width: 80%;
        }
        
        .footer p {
            margin-bottom: 1rem;
            font-size: 1rem;
            letter-spacing: 0.5px;
            opacity: 0.9;
            line-height: 1.6;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .social-links a {
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .social-links a:hover {
            color: #0066cc;
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        
        /* Second footer styling */
        footer.mt-5 {
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 2rem 0;
            color: #6c757d;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        footer.mt-5 p {
            margin-bottom: 1rem;
        }
        
        footer.mt-5 .btn {
            transition: all 0.3s ease;
            margin: 0 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        footer.mt-5 .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        footer.mt-5 a.text-decoration-none {
            color: #6c757d;
            transition: all 0.3s ease;
            padding: 0 5px;
        }
        
        footer.mt-5 a.text-decoration-none:hover {
            color: #0066cc;
            text-decoration: underline !important;
        }

        /* Animation classes */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .fade-in.active {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
                padding: 120px 20px 40px;
                gap: 40px;
            }

            .left-side, .right-side {
                flex: none;
                width: 100%;
                max-width: none;
                padding: 25px;
            }

            .right-side {
                margin: 10px;
                min-height: 380px;
            }

            .logo {
                width: 150px;
                height: 150px;
                margin-bottom: 30px;
            }

            .title {
                font-size: 24px;
                margin-bottom: 30px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem 2rem;
            }
            
            .nav-links {
                display: none;
            }
            

            .features-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .footer {
                padding: 2rem 1rem;
            }
            
            .footer-info {
                max-width: 100%;
            }
            
            .social-links {
                gap: 1rem;
            }
            
            .social-links a {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            footer.mt-5 .btn {
                margin: 0 3px;
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
            }
            
            footer.mt-5 a.text-decoration-none {
                padding: 0 2px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <header class="header">
            <div class="logo-header">
                <img src="assets/image/bluelogo.jpg" alt="CDO Logo">
                <h1>CDO Scholar Management</h1>
            </div>
            <nav class="nav-links">
                <a href="features.php">Features Page</a>
                <a href="about.php">About Page</a>
            </nav>
        </header>
        
        <div class="main-container" id="home">
            <div class="left-side" data-aos="fade-up" data-aos-duration="1000">
                <img src="Assets/image/bluelogo.jpg" alt="CDO Logo" class="logo">
                <h1 class="title">Cagayan de Oro City Scholar<br>Management with Student<br>Development Program<br>Tracker</h1>
            </div>
            <div class="right-side" data-aos="fade-up" data-aos-duration="1000">
                <div class="flip-container">
                    <div class="flipper">
                        <div class="front">
                            <div class="login-form">
                                <h2 style="text-align: center; margin-bottom: 15px; font-size: 20px;">Login</h2>
                                <?php if ($error): ?>
                                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <form method="POST" action="" autocomplete="off">
                                    <div class="form-group">
                                        <input type="email" id="email" name="email" placeholder="Email" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" id="password" name="password" placeholder="Password" required>
                                    </div>
                                    <div class="form-group">

                                    </div>
                                    <div class="forgot-password">
                                        <a href="forgot_password.php">Forgot password?</a>
                                    </div>
                                    <button type="submit" class="login-btn">Login</button>
                                </form>
                            </div>
                        </div>
                        <div class="back">
                            <div class="login-form">
                                <h2 style="text-align: center; margin-bottom: 15px; font-size: 20px;">Forgot Password</h2>
                                <form method="POST" action="forgot_password.php" autocomplete="off">
                                    <div class="form-group">
                                        <input type="email" name="email" placeholder="Enter your email" required>
                                    </div>
                                    <button type="submit" class="login-btn">Reset Password</button>
                                    <div class="forgot-password">
                                        <a href="#" onclick="toggleForms(event)">Back to Login</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <section class="features" id="features">
            <div class="section-title">
                <h2 data-aos="fade-up">Key Features</h2>
                <p data-aos="fade-up" data-aos-delay="200">Discover the powerful tools designed to enhance your scholarship management experience.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Student Status Tracking</h3>
                    <p>Monitor your academic progress, scholarship status, and requirements in real-time.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Event Calendar</h3>
                    <p>Stay updated with important dates, deadlines, and events related to your scholarship.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Announcements</h3>
                    <p>Receive timely notifications and announcements from administrators and staff.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Submission Portal</h3>
                    <p>Easily submit required documents and track your submission history.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="700">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Return Service Activities</h3>
                    <p>Manage and track your community service and return service activities.</p>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="800">
                    <div class="feature-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Allowance Tracking</h3>
                    <p>View your allowance release schedule and history in one convenient place.</p>
                </div>
            </div>
        </section>
        
        <section class="about-section" id="about">
            <h2 data-aos="fade-up">Ready to Get Started?</h2>
            <p data-aos="fade-up" data-aos-delay="200">Join the Cagayan de Oro City Scholar Management System today and take control of your academic journey.</p>
            <p data-aos="fade-up" data-aos-delay="400">Log in using the form on our homepage to access your account.</p>
        </section>
        
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="Assets/image/bluelogo.jpg" alt="CDO Logo" class="footer-img">
                    <h3>CDO Scholar Management</h3>
                </div>
                <div class="footer-info">
                    <p>© 2023 Cagayan de Oro City Scholar Management System. All rights reserved.</p>
                    <p>A comprehensive platform for managing scholarships and tracking student development programs.</p>
                </div>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </footer>

        <footer class="mt-5 text-center text-muted">
            <p>&copy; <?php echo date('Y'); ?> City College of Angeles - Scholars Management System</p>
            <p>
                <a href="direct_login.php?role=admin" class="btn btn-sm btn-primary">LOGIN AS ADMIN</a>
                <a href="direct_login.php?role=staff" class="btn btn-sm btn-success">LOGIN AS STAFF</a>
                <a href="direct_login.php?role=student" class="btn btn-sm btn-info text-white">LOGIN AS STUDENT</a>
            </p>
            <p><small><a href="setup_directories.php" class="text-decoration-none">Setup System Directories</a> | 
            <a href="fix_missing_columns.php" class="text-decoration-none">Fix Database Columns</a> | 
            <a href="run_database_check.php" class="text-decoration-none">Run Database Checker</a> |
            <a href="database_setup.php" class="text-decoration-none">Database Setup Guide</a> |
            <a href="create_test_users.php" class="text-decoration-none">Create Test Users</a> |
            <a href="simple_login.php" class="text-decoration-none">Simple Login</a></small></p>
        </footer>
    </div>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS animation library
        AOS.init();
        
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Toggle login/forgot password forms
        function toggleForms(event) {
            event.preventDefault();
            document.querySelector('.flip-container').classList.toggle('flipped');
        }
        
        document.querySelector('a[href="forgot_password.php"]').onclick = function(e) {
            e.preventDefault();
            toggleForms(e);
        };
        
        // Focus on email field after animation completes
        setTimeout(() => {
            document.getElementById('email').focus();
        }, 1000);
    </script>
</body>
</html>
