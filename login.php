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

        <!-- Message Container -->
        <div id="messageBox" class="message">
            <i id="messageIcon" class="fas fa-info-circle"></i>
            <span id="messageText"></span>
        </div>

        <!-- Login Form -->
        <form id="loginForm">
            <!-- Registration Number -->
            <div class="form-group">
                <label><i class="fas fa-id-card"></i> Registration Number</label>
                <div class="input-wrapper">
                    <i class="fas fa-hashtag input-icon"></i>
                    <input type="text" id="regNo" placeholder="e.g., IAA/2024/0789 or STAFF/001" autocomplete="off">
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" id="password" placeholder="Enter your password">
                    <i class="fas fa-eye-slash toggle-password" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
            </div>

            <!-- Role Selection -->
            <div class="form-group">
                <label><i class="fas fa-users"></i> Role / Account Type</label>
                <div class="input-wrapper">
                    <i class="fas fa-user-tag input-icon"></i>
                    <select id="role">
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
                    <input type="checkbox" id="rememberMe"> Remember me
                </label>
                <a href="#" class="forgot-link" id="forgotPassword">Forgot password?</a>
            </div>

            <!-- Login Button -->
            <button type="button" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <!-- Demo Info -->
        <div class="demo-info">
            <p><i class="fas fa-info-circle"></i> <strong>Demo Mode</strong> — No real authentication required</p>
            <div class="demo-buttons">
                <button type="button" class="demo-quick" data-reg="IAA/2024/0789" data-role="student">🎓 Student</button>
                <button type="button" class="demo-quick" data-reg="STAFF/2024/001" data-role="lecturer">📚 Lecturer</button>
                <button type="button" class="demo-quick" data-reg="ADMIN/001" data-role="admin">⚙️ Admin</button>
                <button type="button" class="demo-quick" data-reg="FIN/2024/001" data-role="finance">💰 Finance</button>
                <button type="button" class="demo-quick" data-reg="ICT/2024/001" data-role="ict">💻 ICT Support</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="login-footer">
        <p>&copy; 2024 Institute of Accountancy Arusha. All rights reserved.</p>
    </div>
</div>

<!-- External JavaScript -->
<script>
    // Get DOM elements
    const loginBtn = document.getElementById('loginBtn');
    const regNoInput = document.getElementById('regNo');
    const passwordInput = document.getElementById('password');
    const roleSelect = document.getElementById('role');
    const rememberCheckbox = document.getElementById('rememberMe');
    const forgotLink = document.getElementById('forgotPassword');
    const messageBox = document.getElementById('messageBox');
    const messageIcon = document.getElementById('messageIcon');
    const messageText = document.getElementById('messageText');

    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Function to show message
    function showMessage(message, type = 'error') {
        messageBox.className = 'message show';
        if (type === 'success') {
            messageBox.classList.add('message-success');
            messageIcon.className = 'fas fa-check-circle';
        } else if (type === 'error') {
            messageBox.classList.add('message-error');
            messageIcon.className = 'fas fa-exclamation-circle';
        } else {
            messageBox.classList.add('message-info');
            messageIcon.className = 'fas fa-info-circle';
        }
        messageText.innerText = message;
        
        setTimeout(() => {
            messageBox.classList.remove('show');
        }, 3000);
    }

    // Validation function
    function validateInputs() {
        const regNo = regNoInput.value.trim();
        const password = passwordInput.value.trim();

        if (!regNo) {
            showMessage('Please enter your Registration Number', 'error');
            regNoInput.focus();
            return false;
        }
        if (!password) {
            showMessage('Please enter your password', 'error');
            passwordInput.focus();
            return false;
        }
        if (password.length < 3) {
            showMessage('Password must be at least 3 characters', 'error');
            passwordInput.focus();
            return false;
        }
        return true;
    }

    // Get role display name
    function getRoleDisplayName(role) {
        const roles = {
            'student': 'Student',
            'lecturer': 'Lecturer / Academic Staff',
            'admin': 'Administrator',
            'finance': 'Finance Office',
            'ict': 'ICT Support'
        };
        return roles[role] || 'User';
    }

    // Simulate login
    function simulateLogin() {
        if (!validateInputs()) {
            return;
        }

        const regNo = regNoInput.value.trim();
        const role = roleSelect.value;
        const roleName = getRoleDisplayName(role);

        // Save to localStorage if remember me is checked
        if (rememberCheckbox.checked) {
            localStorage.setItem('remembered_regNo', regNo);
            localStorage.setItem('remembered_role', role);
        } else {
            localStorage.removeItem('remembered_regNo');
            localStorage.removeItem('remembered_role');
        }

        // Show success message
        showMessage(`✅ Welcome ${roleName}! Registration No: ${regNo}`, 'success');

        // Simulate redirect after 1.5 seconds
        setTimeout(() => {
            alert(`🎉 Welcome to IAA Student Helpdesk!\n\nRegistration Number: ${regNo}\nRole: ${roleName}\n\n(This is a demo - no actual authentication required)\n\nClick OK to continue to dashboard.`);
            
            // Uncomment below when dashboard is ready
            // window.location.href = 'index.html';
        }, 1500);
    }

    // Forgot password handler
    function handleForgotPassword(e) {
        e.preventDefault();
        showMessage('📧 Password reset link has been sent to your registered email address!', 'info');
    }

    // Load remembered data
    function loadRememberedData() {
        const rememberedRegNo = localStorage.getItem('remembered_regNo');
        const rememberedRole = localStorage.getItem('remembered_role');
        if (rememberedRegNo && regNoInput) {
            regNoInput.value = rememberedRegNo;
            rememberCheckbox.checked = true;
        }
        if (rememberedRole && roleSelect) {
            roleSelect.value = rememberedRole;
        }
    }

    // Quick fill demo data
    function quickFillDemo(regNo, role) {
        regNoInput.value = regNo;
        roleSelect.value = role;
        passwordInput.value = 'demo123';
        passwordInput.focus();
        showMessage(`📋 Demo account loaded: ${regNo} (${getRoleDisplayName(role)})`, 'info');
    }

    // Demo buttons event listeners
    document.querySelectorAll('.demo-quick').forEach(btn => {
        btn.addEventListener('click', () => {
            const regNo = btn.getAttribute('data-reg');
            const role = btn.getAttribute('data-role');
            quickFillDemo(regNo, role);
        });
    });

    // Event listeners
    loginBtn.addEventListener('click', simulateLogin);
    forgotLink.addEventListener('click', handleForgotPassword);

    // Enter key press to submit
    passwordInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            simulateLogin();
        }
    });

    regNoInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            passwordInput.focus();
        }
    });

    // Load saved data on page load
    loadRememberedData();

    // Add some visual feedback on focus
    const inputs = document.querySelectorAll('.input-wrapper input, .input-wrapper select');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.style.transform = 'scale(1.01)';
        });
        input.addEventListener('blur', () => {
            input.style.transform = 'scale(1)';
        });
    });
</script>
</body>
</html>