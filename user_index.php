<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR - USER VERSION (FIXED) -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="student-name" id="userName">Goodluck</div>
            <div class="student-id"><i class="fas fa-id-card"></i> <span id="studentId">BCS-01-0131-2023</span></div>
        </div>
        <div class="nav-menu">
            <!-- Dashboard - ACTIVE (this is the current page) -->
            <a href="user_index.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            
            <!-- Submit Query -->
            <a href="user_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            
            <!-- My Queries -->
            <a href="user_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            
            <!-- Knowledge Base -->
            <a href="user_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            
            <!-- Feedback -->
            <a href="user_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            
            <!-- Edit Photo -->
            <a href="user_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            
            <!-- Startup Hub - NO active class (because this is Dashboard page) -->
            <a href="user_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            
            <!-- Settings -->
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            
            <!-- Logout -->
            <div class="logout-item">
                <a href="login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Dashboard | IAA Helpdesk</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="openCount">0</div><div>Open Queries</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number" id="progressCount">0</div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="resolvedCount">0</div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-tachometer-alt"></i><div class="stat-number" id="totalCount">0</div><div>Total Queries</div></div>
        </div>

        <!-- RECENT QUERIES -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Recent student queries</strong>
                <a href="user_submit-query.php" class="btn-primary"><i class="fas fa-plus"></i> New Query</a>
            </div>
            <table>
                <thead>
                    <tr><th>ID</th><th>Subject</th><th>Department</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody id="recentTableBody"></tbody>
            </table>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div class="widget-card">
            <div class="flex-between"><strong>📢 Announcement from IAA</strong></div>
            <p>✅ Exam results will be released on 15th June. Use Helpdesk for missing marks queries.<br>🛠️ E-learning portal maintenance on Saturday from 8pm to 10pm.</p>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    // Set current date
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            dateElement.innerText = new Date().toLocaleDateString('en-US', options);
        }
    }

    function renderDashboard() {
        const stats = getStats();
        document.getElementById('openCount').innerText = stats.open;
        document.getElementById('progressCount').innerText = stats.inProgress;
        document.getElementById('resolvedCount').innerText = stats.resolved;
        document.getElementById('totalCount').innerText = stats.total;
        
        // Get user from session or appData
        let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
        if (loggedUser.name) {
            document.getElementById('userName').innerText = loggedUser.name;
        } else {
            document.getElementById('userName').innerText = appData.currentUser.name;
        }
        
        if (loggedUser.regNo) {
            document.getElementById('studentId').innerText = loggedUser.regNo;
        } else {
            document.getElementById('studentId').innerText = appData.currentUser.studentId;
        }

        const recent = getRecentTickets(5);
        const tbody = document.getElementById('recentTableBody');
        if (recent.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">No queries yet. Submit one. <a href="user_submit-query.php">Submit Query</a></td></tr>';
        } else {
            tbody.innerHTML = recent.map(t => `
                <tr>
                    <td>#${t.id}</td>
                    <td>${escapeHtml(t.title.substring(0, 40))}侧
                    <td>${t.department}侧
                    <td><span class="status-badge">${t.status}</span>侧
                    <td>${t.date}侧
                </tr>
            `).join('');
        }
    }

    loadFromLocalStorage();
    initDemoData();
    setCurrentDate();
    renderDashboard();

    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sessionStorage.clear();
        localStorage.clear();
        showMessage('Logged out successfully!');
        window.location.href = 'login.html';
    });
</script>
</body>
</html>