<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Edit Profile Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Additional styles specific to edit photo page */
        .edit-photo-wrapper {
            max-width: 550px;
            margin: 0 auto;
        }

        .user-info-card {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2edf2;
        }

        .welcome-badge {
            background: #e0f0f5;
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #2c7da0;
            margin-bottom: 12px;
        }

        .user-fullname {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0a2b38;
            margin-bottom: 5px;
        }

        .user-reg-number {
            font-size: 0.8rem;
            color: #7f8c8d;
            background: #f1f5f9;
            display: inline-block;
            padding: 5px 15px;
            border-radius: 30px;
        }

        .photo-preview-card {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2edf2;
        }

        .photo-preview-label {
            color: #7f8c8d;
            font-size: 0.8rem;
            margin-bottom: 15px;
            display: block;
        }

        .photo-circle-preview {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #2c7da0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .photo-circle-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .isms-info {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #fef9e6;
            border-radius: 12px;
            font-size: 0.75rem;
            color: #b45f06;
            border-left: 3px solid #f39c12;
        }

        .isms-info i {
            margin-right: 5px;
        }

        .upload-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px dashed #cbd5e1;
            text-align: center;
        }

        .upload-icon {
            font-size: 48px;
            color: #2c7da0;
            margin-bottom: 15px;
        }

        .upload-requirements {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .upload-requirements i {
            margin-right: 5px;
            color: #2c7da0;
        }

        .file-input-area {
            margin-bottom: 10px;
        }

        .custom-file-label {
            background: #2c7da0;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .custom-file-label:hover {
            background: #1f5a70;
            transform: translateY(-2px);
        }

        .selected-file-name {
            font-size: 0.75rem;
            color: #2c7da0;
            margin-top: 10px;
        }

        .error-alert {
            background: #fde8e8;
            color: #c0392b;
            padding: 12px 15px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            border-left: 3px solid #c0392b;
        }

        .error-alert.show {
            display: flex;
        }

        .save-photo-btn {
            width: 100%;
            background: linear-gradient(135deg, #2c7da0, #1f5068);
            border: none;
            padding: 14px;
            border-radius: 40px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .save-photo-btn:hover {
            background: linear-gradient(135deg, #1f5a70, #143d4d);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Notification */
        .photo-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1d6f42;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 0.85rem;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease;
        }

        .photo-notification.error {
            background: #c0392b;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        @media (max-width: 550px) {
            .photo-circle-preview {
                width: 120px;
                height: 120px;
            }
            .user-fullname {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR - SAME AS OTHER PAGES (NO ACTIVE CLASS ON EDIT PHOTO) -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="student-name" id="userName">Goodluck</div>
            <div class="student-id"><i class="fas fa-id-card"></i> <span id="studentId">BCS-01-0131-2023</span></div>
        </div>
        <div class="nav-menu">
            <a href="user_index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="user_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="user_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="user_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="user_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="user_edit-photo.php" class="nav-item active"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="user_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="index.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Edit Profile Photo | IAA Helpdesk</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- Edit Photo Content -->
        <div class="edit-photo-wrapper">
            <!-- User Info Section -->
            <div class="user-info-card">
                <div class="welcome-badge">
                    <i class="fas fa-smile"></i> Welcome
                </div>
                <div class="user-fullname" id="displayUserName">Goodluck</div>
                <div class="user-reg-number" id="displayUserReg">BCS-01-0131-2023</div>
            </div>

            <!-- Photo Preview -->
            <div class="photo-preview-card">
                <div class="photo-preview-label"><i class="fas fa-image"></i> Current Profile Photo</div>
                <div class="photo-circle-preview">
                    <img src="https://ui-avatars.com/api/?background=2c7da0&color=fff&size=150&name=Goodluck" alt="Profile Photo" id="previewImage">
                </div>
            </div>

            <!-- ISMS Requirement -->
            <div class="isms-info">
                <i class="fas fa-shield-alt"></i> ISMS / Required format PNG, JPG & JPEG Only
            </div>

            <!-- File Upload Section -->
            <div class="upload-card">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-requirements">
                    <i class="fas fa-info-circle"></i> Required format PNG, JPEG & JPG Only (size less than 1 MB)
                </div>
                
                <div class="file-input-area">
                    <label class="custom-file-label" id="fileLabel">
                        <i class="fas fa-folder-open"></i> Choose File
                    </label>
                    <input type="file" id="photoFile" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                </div>
                <div class="selected-file-name" id="fileNameDisplay">No file chosen</div>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText"></span>
            </div>

            <!-- Save Button -->
            <button class="save-photo-btn" id="savePhotoBtn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </main>
</div>

<script>
    // Set current date
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            dateElement.innerText = new Date().toLocaleDateString('en-US', options);
        }
    }
    setCurrentDate();
    
    // Get DOM elements
    const photoFileInput = document.getElementById('photoFile');
    const fileLabel = document.getElementById('fileLabel');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const previewImage = document.getElementById('previewImage');
    const saveBtn = document.getElementById('savePhotoBtn');
    const errorMessageDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const displayUserName = document.getElementById('displayUserName');
    const displayUserReg = document.getElementById('displayUserReg');
    const sidebarUserName = document.getElementById('userName');
    const sidebarStudentId = document.getElementById('studentId');
    
    // Get current user from session storage
    let currentUser = {};
    let currentRegNo = 'BCS-01-0131-2023';
    let currentUserName = 'Goodluck';
    let selectedFileData = null;
    
    try {
        const storedUser = sessionStorage.getItem('loggedInUser');
        if (storedUser) {
            currentUser = JSON.parse(storedUser);
            currentRegNo = currentUser.regNo || 'BCS-01-0131-2023';
            currentUserName = currentUser.name || 'Goodluck';
        }
    } catch(e) {
        console.log('Using default user');
    }
    
    // Update displayed user info
    if (displayUserName) displayUserName.textContent = currentUserName;
    if (displayUserReg) displayUserReg.textContent = currentRegNo;
    if (sidebarUserName) sidebarUserName.textContent = currentUserName;
    if (sidebarStudentId) sidebarStudentId.textContent = currentRegNo;
    
    // Load saved profile photo
    function loadSavedProfilePhoto() {
        try {
            const savedPhoto = localStorage.getItem('profilePhoto_' + currentRegNo);
            if (savedPhoto && savedPhoto !== 'null' && savedPhoto !== 'undefined') {
                previewImage.src = savedPhoto;
            } else {
                const firstName = currentUserName.split(' ')[0] || 'User';
                previewImage.src = 'https://ui-avatars.com/api/?background=2c7da0&color=fff&size=150&name=' + encodeURIComponent(firstName);
            }
        } catch(e) {
            console.log('Error loading photo:', e);
        }
    }
    
    // Show error message
    function showError(message) {
        errorText.textContent = message;
        errorMessageDiv.classList.add('show');
        setTimeout(() => {
            errorMessageDiv.classList.remove('show');
        }, 4000);
    }
    
    // Hide error
    function hideError() {
        errorMessageDiv.classList.remove('show');
    }
    
    // Show notification
    function showNotification(message, isError = false) {
        const existingNotif = document.querySelector('.photo-notification');
        if (existingNotif) existingNotif.remove();
        
        const notification = document.createElement('div');
        notification.className = 'photo-notification';
        if (isError) notification.classList.add('error');
        notification.innerHTML = `
            <i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Add animations
    if (!document.querySelector('#photoAnimations')) {
        const style = document.createElement('style');
        style.id = 'photoAnimations';
        style.textContent = `
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Validate file
    function validateFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            showError('Invalid file format! Please select PNG, JPEG or JPG image only.');
            return false;
        }
        
        if (file.size > 1 * 1024 * 1024) {
            showError('File size too large! Maximum 1MB allowed.');
            return false;
        }
        return true;
    }
    
    // Handle file selection
    if (fileLabel) {
        fileLabel.addEventListener('click', () => {
            photoFileInput.click();
        });
    }
    
    if (photoFileInput) {
        photoFileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            hideError();
            
            if (!file) {
                fileNameDisplay.textContent = 'No file chosen';
                selectedFileData = null;
                return;
            }
            
            if (!validateFile(file)) {
                this.value = '';
                fileNameDisplay.textContent = 'No file chosen';
                selectedFileData = null;
                return;
            }
            
            fileNameDisplay.textContent = file.name;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                selectedFileData = e.target.result;
                previewImage.src = selectedFileData;
            };
            reader.readAsDataURL(file);
        });
    }
    
    // Save photo
    function saveProfilePhoto() {
        if (!selectedFileData) {
            showError('Please select a photo to upload');
            return;
        }
        
        try {
            localStorage.setItem('profilePhoto_' + currentRegNo, selectedFileData);
            
            if (sessionStorage.getItem('loggedInUser')) {
                const userData = JSON.parse(sessionStorage.getItem('loggedInUser'));
                userData.profilePhoto = selectedFileData;
                sessionStorage.setItem('loggedInUser', JSON.stringify(userData));
            }
            
            showNotification('Profile photo updated successfully!');
            
            photoFileInput.value = '';
            fileNameDisplay.textContent = 'No file chosen';
            selectedFileData = null;
            
        } catch(e) {
            showError('Error saving photo. Please try again.');
        }
    }
    
    if (saveBtn) {
        saveBtn.addEventListener('click', saveProfilePhoto);
    }
    
    // Load saved photo on page load
    loadSavedProfilePhoto();
    
    // Logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sessionStorage.removeItem('loggedInUser');
            window.location.href = 'login.html';
        });
    }
</script>
</body>
</html>