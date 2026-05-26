<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Login</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- External CSS - Separated -->
    <link rel="stylesheet" href="css/login.css">
    <style>
        /* Maonyesho ya ujumbe wa kosa au mafanikio */
        .message {
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        .message.show { display: flex; }
        .message-success { background: #d9f0e5; color: #1d6f42; border-left: 4px solid #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; border-left: 4px solid #c0392b; }
        .message-info { background: #e3f2fd; color: #1565c0; border-left: 4px solid #1565c0; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <!-- HEADER - JUU NA LOGO -->
    <div class="login-header">
        <div class="logo-circle">
            <img src="img/iaalogo.jpg" alt="IAA Logo" class="custom-logo"> 
        </div>
        <h1>Institute of Accountancy Arusha</h1>
        <p>Student Digital Helpdesk & Query Management System</p>
    </div>

    <!-- LOGIN CARD -->
    <div class="login-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Login to access your helpdesk portal</p>

        <!-- Sehemu ya kuonyesha ujumbe (kutoka kwa PHP) -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message show message-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message show message-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form - Sasa inatuma data kwa login_process.php -->
        <form id="loginForm" action="login_process.php" method="POST">
            <!-- Registration Number -->
            <div class="form-group">
                <label><i class="fas fa-id-card"></i> Registration Number</label>
                <div class="input-wrapper">
                    <i class="fas fa-hashtag input-icon"></i>
                    <input type="text" id="regNo" name="reg_no" placeholder="e.g., IAA/2024/0789 or ADMIN/001" autocomplete="off" required>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye-slash toggle-password" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
            </div>

            <!-- Role Selection -->
            <div class="form-group">
                <label><i class="fas fa-users"></i> Role / Account Type</label>
                <div class="input-wrapper">
                    <i class="fas fa-user-tag input-icon"></i>
                    <select id="role" name="role" required>
                        <option value="student">🎓 Student</option>
                        <option value="lecturer">📚 Lecturer / Academic Staff</option>
                        <option value="admin">⚙️ Administrator</option>
                        <option value="finance">💰 Finance Office</option>
                        <option value="ict">💻 ICT Support</option>
                    </select>
                </div>
            </div>

            <!-- Options -->
            <div class="form-options">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="remember_me" id="rememberMe"> Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link" id="forgotPassword">Forgot password?</a>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="login-footer">
        <p>&copy; 2024 Institute of Accountancy Arusha. All rights reserved.</p>
    </div>
</div>

<script>
    // Toggle password visibility (bado inafanya kazi)
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Kumbuka: Hatuwezi tena kuwa na demo-buttons za kujaza haraka kwa sababu sasa tunatumia database.
    // Ikiwa unataka, unaweza kuwaweka kwa madhumuni ya majaribio, lakini itabidi waweke credentials halisi.
    // Kwa sasa, nimeamua kuwaondoa ili kuepuka mkanganyiko.
</script>
</body>
</html>