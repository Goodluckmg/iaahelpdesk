<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Resolved Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/lecturers.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-chalkboard-user"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="lecturerName">Dr. Sarah Lecturer</div>
            <div class="user-role">📚 Lecturer</div>
            <div class="user-id" id="lecturerId">STAFF/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="lecturers.php" class="nav-item" data-view="dashboard"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="lec_pending.php" class="nav-item" data-view="pending"><i class="fas fa-clock"></i><span class="nav-label">Pending Requests</span></a>
            <a href="lec_resolved.php" class="nav-item" data-view="resolved"><i class="fas fa-check-circle"></i><span class="nav-label">Resolved</span></a>
            <a href="lec_courses.php" class="nav-item" data-view="courses"><i class="fas fa-book"></i><span class="nav-label">My Courses</span></a>
             <a href="lec_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Resolved Requests</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="resolvedCount">0</div><div>Total Resolved</div></div>
            <div class="stat-card"><i class="fas fa-star"></i><div class="stat-number" id="satisfactionRate">0%</div><div>Satisfaction Rate</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>✅ Resolved Student Requests</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div id="resolvedList"></div>
        </div>
    </main>
</div>

<script src="lecturer.js"></script>
<script>
    let requests = [];

    function loadData() {
        requests = loadRequests();
        updateStats();
        renderResolvedList();
    }

    function updateStats() {
        const resolved = requests.filter(r => r.status === 'Resolved').length;
        const total = requests.length;
        const satisfaction = total === 0 ? 0 : Math.round((resolved / total) * 100);
        document.getElementById('resolvedCount').innerText = resolved;
        document.getElementById('satisfactionRate').innerText = satisfaction + '%';
    }

    function renderResolvedList() {
        const resolved = requests.filter(r => r.status === 'Resolved');
        const container = document.getElementById('resolvedList');
        
        if (resolved.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">📭 No resolved requests yet. Start responding to pending requests.</div>';
            return;
        }
        
        container.innerHTML = resolved.map(r => `
            <div class="request-item" style="border-left-color: #1d6f42;">
                <div class="request-title">#${r.id} - ${r.subject}</div>
                <div class="request-meta">
                    <i class="fas fa-user"></i> ${r.studentName} (${r.studentReg}) | 
                    <i class="fas fa-book"></i> ${r.course} | 
                    <i class="fas fa-calendar"></i> ${r.date} |
                    <span class="status-badge status-resolved">Resolved</span>
                </div>
                <div class="request-description"><strong>Description:</strong> ${r.description}</div>
                ${r.response ? `<div class="request-description" style="background:#d9f0e5; padding:10px; border-radius:10px; margin-top:10px;"><strong>📝 Your Response:</strong> ${r.response}<br><small>Responded on: ${r.responseDate || r.date}</small></div>` : ''}
            </div>
        `).join('');
    }

    document.getElementById('refreshBtn').addEventListener('click', loadData);
    loadData();
</script>
</body>
</html>