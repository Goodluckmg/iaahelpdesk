<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Knowledge Base</title>
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
            <a href="user_index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="user_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="user_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="user_knowledge-base.php" class="nav-item active"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="user_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="user_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="user_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar"><h1 class="page-title">Knowledge Base & FAQs</h1><div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div></div>
        <div class="widget-card">
            <div class="flex-between"><strong>📖 IAA Knowledge Base</strong></div>
            <ul class="kb-list">
                <li><i class="fas fa-check-circle"></i> <strong>How to check missing marks?</strong> – Contact Examination department via helpdesk and attach registration proof.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Portal login failure?</strong> – Reset your password using student email or submit an ICT support ticket.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Course registration errors:</strong> – Ensure all fees are paid; contact Academic Registry.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Fee payment confirmation:</strong> – Upload payment slip to Finance Office through helpdesk.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Request for transcript:</strong> – Processed within 7 working days.</li>
                <li><i class="fas fa-check-circle"></i> <strong>Library book renewal:</strong> – Can be done online via library portal.</li>
            </ul>
            <hr>
            <p><i class="fas fa-headset"></i> Still have questions? <a href="user_submit-query.php">Submit a query</a> for personalized support.</p>
            <div style="margin-top: 20px; background: #e8f0f5; padding: 15px; border-radius: 20px;"><i class="fas fa-phone-alt"></i> <strong>ICT Support Hotline:</strong> +255 712 345 678<br><i class="fas fa-envelope"></i> <strong>Email:</strong> helpdesk@iaa.ac.tz</div>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options);
    }
    
    loadFromLocalStorage();
    setCurrentDate();
    
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('userName').innerText = loggedUser.name || appData.currentUser.name;
    document.getElementById('studentId').innerText = loggedUser.regNo || appData.currentUser.studentId;

    document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); sessionStorage.clear(); localStorage.clear(); window.location.href = 'login.html'; });
</script>
</body>
</html>