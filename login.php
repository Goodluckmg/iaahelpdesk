<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Login</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    

        /* RESET & BASE - HAKUNA MOVEMENT YOYOTE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:  #0b2b4a;; /* Rangi ya mfumo mzima */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        /* CARD NDOGO - Imepunguzwa ukubwa */
        .login-wrapper {
            width: 100%;
            max-width: 400px; /* Ilikuwa 480px, sasa 400px */
            background: #ffffff;
            border-radius: 20px; /* Ilikuwa 24px */
            box-shadow: 0 8px 24px rgba(11, 43, 74, 0.08);
            padding: 28px 24px 20px; /* Ilikuwa 40px 32px, sasa imepungua */
            text-align: center;
        }

        /* HEADER - NDOGO */
        .login-header .logo-circle {
            width: 64px; /* Ilikuwa 80px */
            height: 64px;
            border-radius: 50%;
            background: #ffffff;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(11, 43, 74, 0.05);
        }
        .login-header .logo-circle img {
            width: 54px; /* Ilikuwa 70px */
            height: 54px;
            border-radius: 50%;
            object-fit: cover;
        }
        .login-header h1 {
            font-size: 1.0rem; /* Ilikuwa 1.3rem */
            font-weight: 700;
            color: #0b2b4a;
            letter-spacing: -0.2px;
            margin-bottom: 2px;
        }
        .login-header p {
            font-size: 0.75rem; /* Ilikuwa 0.85rem */
            color: #64748b;
            margin-bottom: 18px;
        }

        /* CARD BODY - NDOGO */
        .login-card h2 {
            font-size: 1.3rem; /* Ilikuwa 1.5rem */
            font-weight: 700;
            color: #0b2b4a;
            margin-bottom: 2px;
        }
        .login-card .subtitle {
            font-size: 0.8rem; /* Ilikuwa 0.9rem */
            color: #94a3b8;
            margin-bottom: 20px;
        }

        /* MESSAGES - HAKUNA MOVEMENT */
        .message {
            padding: 8px 12px; /* Ilikuwa 10px 14px */
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.78rem;
            display: none;
            align-items: center;
            gap: 8px;
            text-align: left;
        }
        .message.show { display: flex; }
        .message-success { background: #d9f0e5; color: #1d6f42; border-left: 4px solid #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; border-left: 4px solid #c0392b; }
        .message-info { background: #e3f2fd; color: #1a5e9c; border-left: 4px solid #1a5e9c; }

        /* FORM - NDOGO */
        .form-group {
            text-align: left;
            margin-bottom: 16px; /* Ilikuwa 20px */
        }
        .form-group label {
            display: block;
            font-size: 0.75rem; /* Ilikuwa 0.8rem */
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .form-group label i {
            margin-right: 6px;
            color: #0b2b4a;
        }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px; /* Ilikuwa 14px */
            padding: 0 14px; /* Ilikuwa 0 16px */
            /* HAKUNA transition - HAKUNA MOVEMENT */
        }
        /* Focus haibadilishi chochote - tuli kabisa */
        .input-wrapper .input-icon {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-right: 8px;
        }
        .input-wrapper input {
            width: 100%;
            padding: 10px 0; /* Ilikuwa 14px 0 */
            border: none;
            background: transparent;
            font-size: 0.85rem; /* Ilikuwa 0.95rem */
            outline: none; /* HAKUNA outline */
            color: #0b2b4a;
        }
        .input-wrapper input::placeholder {
            color: #b0c4d9;
            font-weight: 400;
        }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%); /* Hii ni ya kuweka katikati, sio animation */
            cursor: pointer;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        /* OPTIONS - NDOGO */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 4px 0 20px; /* Ilikuwa 6px 0 28px */
            font-size: 0.78rem; /* Ilikuwa 0.85rem */
        }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #475569;
            cursor: default; /* Badala ya pointer, tuli */
        }
        .checkbox-wrapper input[type="checkbox"] {
            width: 16px; /* Ilikuwa 18px */
            height: 16px;
            accent-color: #0b2b4a;
            cursor: default;
        }
        .forgot-link {
            color: #1a5e9c;
            text-decoration: underline; /* Daima underline, haina hover effect */
            font-weight: 500;
            cursor: default;
        }
        /* HAKUNA :hover kwenye forgot-link */

        /* BUTTON - NDOGO NA TULI KABISA */
        .btn-login {
            width: 100%;
            padding: 11px 0; /* Ilikuwa 14px */
            background: #0b2b4a;
            border: none;
            border-radius: 12px; /* Ilikuwa 14px */
            color: #fff;
            font-size: 0.9rem; /* Ilikuwa 1rem */
            font-weight: 600;
            cursor: default; /* Badala ya pointer, tuli */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-login i {
            font-size: 1rem;
        }
        /* HAKUNA :hover kwenye button - rangi inabakia vile vile */

        /* FOOTER - NDOGO */
        .login-footer {
            margin-top: 24px; /* Ilikuwa 32px */
            font-size: 0.7rem; /* Ilikuwa 0.75rem */
            color: #94a3b8;
            border-top: 1px solid #f0f4f9;
            padding-top: 16px; /* Ilikuwa 20px */
        }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            .login-wrapper { padding: 20px 16px 16px; max-width: 100%; }
            .login-header h1 { font-size: 0.9rem; }
            .form-options { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- HEADER -->
    <div class="login-header">
        <div class="logo-circle">
            <img src="img/iaalogo.jpg" alt="IAA Logo" class="custom-logo">
        </div>
        <h1>Institute of Accountancy Arusha</h1>
        <p>Digital Helpdesk &amp; Query Management System</p>
    </div>

    <!-- LOGIN CARD -->
    <div class="login-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Login to access your portal</p>

        <!-- Ujumbe wa session -->
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

        <!-- Login Form -->
        <form id="loginForm" action="login_process.php" method="POST">
            <div class="form-group">
                <label><i class="fas fa-id-card"></i> Registration / Staff ID</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="e.g., IAA/2024/0789" autocomplete="off" required>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye-slash toggle-password"></i>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="remember_me" id="rememberMe"> Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
    </div>

    <div class="login-footer">
        <p>&copy; 2025 Institute of Accountancy Arusha. All rights reserved.</p>
    </div>
</div>

<script>
    // Toggle password visibility - KAZI YAIWE TU, HAKUNA MOVEMENT
    const toggle = document.querySelector('.toggle-password');
    const pwd = document.getElementById('password');
    if (toggle && pwd) {
        toggle.addEventListener('click', function() {
            const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
            pwd.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
</script>

</body>
</html>