<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance - Edit Profile Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/finance.css">
    <style>
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
            background: #fff3e0;
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #e67e22;
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
            border: 4px solid #f39c12;
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
            color: #f39c12;
            margin-bottom: 15px;
        }
        .upload-requirements {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        .file-input-area {
            margin-bottom: 10px;
        }
        .custom-file-label {
            background: #f39c12;
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
            background: #e67e22;
            transform: translateY(-2px);
        }
        .selected-file-name {
            font-size: 0.75rem;
            color: #e67e22;
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
        .error-alert.show { display: flex; }
        .save-photo-btn {
            width: 100%;
            background: linear-gradient(135deg, #f39c12, #e67e22);
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
            background: linear-gradient(135deg, #e67e22, #d35400);
            transform: translateY(-2px);
        }
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
            animation: slideInRight 0.3s ease;
        }
        .photo-notification.error { background: #c0392b; }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        @media (max-width: 550px) {
            .photo-circle-preview { width: 120px; height: 120px; }
            .user-fullname { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-coins"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="financeName">Mr. James Peter</div>
            <div class="user-role">💰 Finance Officer</div>
            <div class="user-id" id="financeId">FIN/2024/001</div>
        </div>
       <div class="nav-menu">
            <a href="finance.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
             <a href="fin_students.php" class="nav-item"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_edit.php" class="nav-item active"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Edit Profile Photo | Finance</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="edit-photo-wrapper">
            <div class="user-info-card">
                 
            </div>

            <div class="photo-preview-card">
              

            
                <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <div class="upload-requirements"><i class="fas fa-info-circle"></i> Required format PNG, JPEG & JPG Only (size less than 1 MB)</div>
                <div class="file-input-area">
                    <label class="custom-file-label" id="fileLabel"><i class="fas fa-folder-open"></i> Choose File</label>
                    <input type="file" id="photoFile" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                </div>
                <div class="selected-file-name" id="fileNameDisplay">No file chosen</div>
            </div>

            <div id="errorMessage" class="error-alert"><i class="fas fa-exclamation-circle"></i><span id="errorText"></span></div>
            <button class="save-photo-btn" id="savePhotoBtn"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </main>
</div>

<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options);
    }
    setCurrentDate();
    
    const photoFileInput = document.getElementById('photoFile');
    const fileLabel = document.getElementById('fileLabel');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const previewImage = document.getElementById('previewImage');
    const saveBtn = document.getElementById('savePhotoBtn');
    const errorMessageDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const displayFinanceName = document.getElementById('displayFinanceName');
    const displayFinanceId = document.getElementById('displayFinanceId');
    const sidebarFinanceName = document.getElementById('financeName');
    const sidebarFinanceId = document.getElementById('financeId');
    
    let currentFinance = {};
    let currentFinanceRegNo = 'FIN/2024/001';
    let currentFinanceName = 'Mr. James Peter';
    let selectedFileData = null;
    
    try {
        const storedUser = sessionStorage.getItem('loggedInUser');
        if (storedUser) {
            currentFinance = JSON.parse(storedUser);
            currentFinanceRegNo = currentFinance.regNo || 'FIN/2024/001';
            currentFinanceName = currentFinance.name || 'Mr. James Peter';
        }
    } catch(e) {}
    
    if (displayFinanceName) displayFinanceName.textContent = currentFinanceName;
    if (displayFinanceId) displayFinanceId.textContent = currentFinanceRegNo;
    if (sidebarFinanceName) sidebarFinanceName.textContent = currentFinanceName;
    if (sidebarFinanceId) sidebarFinanceId.textContent = currentFinanceRegNo;
    
    function loadSavedProfilePhoto() {
        try {
            const savedPhoto = localStorage.getItem('finance_profilePhoto_' + currentFinanceRegNo);
            if (savedPhoto && savedPhoto !== 'null') {
                previewImage.src = savedPhoto;
            }
        } catch(e) {}
    }
    
    function showError(message) {
        errorText.textContent = message;
        errorMessageDiv.classList.add('show');
        setTimeout(() => errorMessageDiv.classList.remove('show'), 4000);
    }
    function hideError() { errorMessageDiv.classList.remove('show'); }
    
    function showNotification(message, isError = false) {
        const existingNotif = document.querySelector('.photo-notification');
        if (existingNotif) existingNotif.remove();
        const notification = document.createElement('div');
        notification.className = 'photo-notification';
        if (isError) notification.classList.add('error');
        notification.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i><span>${message}</span>`;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    if (!document.querySelector('#photoAnimations')) {
        const style = document.createElement('style');
        style.id = 'photoAnimations';
        style.textContent = `@keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }`;
        document.head.appendChild(style);
    }
    
    function validateFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) { showError('Invalid format! Use PNG, JPEG or JPG'); return false; }
        if (file.size > 1 * 1024 * 1024) { showError('File too large! Max 1MB'); return false; }
        return true;
    }
    
    if (fileLabel) fileLabel.addEventListener('click', () => photoFileInput.click());
    
    if (photoFileInput) {
        photoFileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            hideError();
            if (!file) { fileNameDisplay.textContent = 'No file chosen'; selectedFileData = null; return; }
            if (!validateFile(file)) { this.value = ''; fileNameDisplay.textContent = 'No file chosen'; selectedFileData = null; return; }
            fileNameDisplay.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function(e) { selectedFileData = e.target.result; previewImage.src = selectedFileData; };
            reader.readAsDataURL(file);
        });
    }
    
    function saveProfilePhoto() {
        if (!selectedFileData) { showError('Please select a photo first'); return; }
        try {
            localStorage.setItem('finance_profilePhoto_' + currentFinanceRegNo, selectedFileData);
            showNotification('Profile photo updated successfully!');
            photoFileInput.value = '';
            fileNameDisplay.textContent = 'No file chosen';
            selectedFileData = null;
        } catch(e) { showError('Error saving photo'); }
    }
    
    if (saveBtn) saveBtn.addEventListener('click', saveProfilePhoto);
    loadSavedProfilePhoto();
    
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sessionStorage.clear();
        window.location.href = 'login.html';
    });
</script>
</body>
</html>