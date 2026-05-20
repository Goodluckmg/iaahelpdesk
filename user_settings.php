<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="student-name" id="userName">Goodluck</div>
            <div class="student-id"><i class="fas fa-id-card"></i> <span id="studentId">BCS-01-0131-2023</span></div>
        </div>
        <div class="nav-menu">
             <a href="user_index.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="user_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="user_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="user_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="user_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="user_edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="user_startup.php" class="nav-item active"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
           
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="index.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Settings & Preferences</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>⚙️ Account Settings</strong></div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="settingsName" value="Goodluck">
            </div>
            <div class="form-group">
                <label>Email Address (for notifications)</label>
                <input type="email" id="settingsEmail" value="goodluck@iaa.ac.tz">
            </div>
            <div class="form-group">
                <label>Phone Number (SMS notifications)</label>
                <input type="text" id="settingsPhone" value="+255 712 345 678">
            </div>
            <div class="form-group">
                <label>Default Department for queries</label>
                <select id="defaultDept">
                    <option>ICT Support</option>
                    <option>Examination & Records</option>
                    <option>Finance Office</option>
                    <option>Academic Registry</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notification Preference</label>
                <select id="notifPref">
                    <option>Email & SMS</option>
                    <option>Email only</option>
                    <option>SMS only</option>
                    <option>None</option>
                </select>
            </div>
            <button id="saveSettingsBtn" class="btn-primary">Save Changes</button>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>🔒 Privacy & Data</strong></div>
            <p>Your data is stored locally on your device. You can clear your data at any time.</p>
            <button id="clearDataBtn" class="btn-primary" style="background:#c0392b; margin-top:10px;">Clear All My Data</button>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    loadFromLocalStorage();
    initDemoData();
    setCurrentDate();
    
    document.getElementById('userName').innerText = appData.currentUser.name;
    document.getElementById('studentId').innerText = appData.currentUser.studentId;
    document.getElementById('settingsName').value = appData.currentUser.name;
    document.getElementById('settingsEmail').value = appData.currentUser.email;
    document.getElementById('settingsPhone').value = appData.currentUser.phone;

    document.getElementById('saveSettingsBtn').addEventListener('click', () => {
        appData.currentUser.name = document.getElementById('settingsName').value;
        appData.currentUser.email = document.getElementById('settingsEmail').value;
        appData.currentUser.phone = document.getElementById('settingsPhone').value;
        saveToLocalStorage();
        document.getElementById('userName').innerText = appData.currentUser.name;
        showMessage('✅ Settings saved successfully!');
    });

    document.getElementById('clearDataBtn').addEventListener('click', () => {
        if (confirm('Are you sure? This will delete ALL your tickets and data!')) {
            localStorage.clear();
            showMessage('All data cleared! Page will reload.');
            window.location.href = 'index.php';
        }
    });

    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.clear();
        window.location.href = 'index.php';
    });
</script>
</body>
</html>