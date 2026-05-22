<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/finance.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area"><div class="avatar"><i class="fas fa-coins"></i></div><div class="welcome-text">Welcome,</div><div class="user-name" id="financeName">Mr. James Peter</div><div class="user-role">💰 Finance Officer</div><div class="user-id" id="financeId">FIN/2024/001</div></div>
        <div class="nav-menu">
            <a href="finance.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
            <a href="fin_students.php" class="nav-item"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="fin_reports.php" class="nav-item active"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar"><h1 class="page-title">Finance Reports & Analytics</h1><div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div></div>

        <div class="stats-row">
            <div class="stat-card"><div class="stat-number" id="totalQueries">0</div><div>Total Queries</div></div>
            <div class="stat-card"><div class="stat-number" id="resolvedQueries">0</div><div>Resolved</div></div>
            <div class="stat-card"><div class="stat-number" id="pendingQueries">0</div><div>Pending</div></div>
            <div class="stat-card"><div class="stat-number" id="responseTime">0</div><div>Avg Response (days)</div></div>
        </div>

        <div class="widget-card"><div class="flex-between"><strong>📊 Query Trends (Last 6 Months)</strong></div><canvas id="trendChart" width="400" height="200" style="max-height: 250px;"></canvas></div>
        <div class="widget-card"><div class="flex-between"><strong>📈 Query Status Distribution</strong></div><canvas id="statusChart" width="400" height="200" style="max-height: 250px;"></canvas></div>
        <div class="widget-card"><div class="flex-between"><strong>🏆 Top Students by Queries</strong></div><div id="topStudentsList"></div></div>
    </main>
</div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('financeName').innerText = loggedUser.name || 'Mr. James Peter';
    document.getElementById('financeId').innerText = loggedUser.regNo || 'FIN/2024/001';
    
    let queries = JSON.parse(localStorage.getItem('student_finance_queries') || '[]');
    if (queries.length === 0) { queries = [{ id:2001, studentName:'John Student', status:'Resolved', date:'2024-06-01' },{ id:2002, studentName:'Mary Student', status:'In Progress', date:'2024-06-02' },{ id:2003, studentName:'Peter Student', status:'Pending', date:'2024-06-03' },{ id:2004, studentName:'John Student', status:'Resolved', date:'2024-05-15' },{ id:2005, studentName:'Mary Student', status:'Resolved', date:'2024-05-20' }]; }
    
    document.getElementById('totalQueries').innerText = queries.length;
    document.getElementById('resolvedQueries').innerText = queries.filter(q => q.status === 'Resolved').length;
    document.getElementById('pendingQueries').innerText = queries.filter(q => q.status === 'Pending' || q.status === 'In Progress').length;
    document.getElementById('responseTime').innerText = Math.round(queries.filter(q => q.status === 'Resolved').length / (queries.length || 1) * 5);
    
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const received = [12, 15, 18, 22, 25, 28];
    const resolved = [10, 12, 16, 20, 23, 26];
    new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: months, datasets: [{ label: 'Queries Received', data: received, borderColor: '#f39c12', fill: true },{ label: 'Queries Resolved', data: resolved, borderColor: '#27ae60', fill: true }] }, options: { responsive: true } });
    
    const statusCounts = { 'Pending': queries.filter(q => q.status === 'Pending').length, 'In Progress': queries.filter(q => q.status === 'In Progress').length, 'Resolved': queries.filter(q => q.status === 'Resolved').length };
    new Chart(document.getElementById('statusChart'), { type: 'doughnut', data: { labels: Object.keys(statusCounts), datasets: [{ data: Object.values(statusCounts), backgroundColor: ['#e74c3c', '#f39c12', '#27ae60'] }] }, options: { responsive: true } });
    
    const studentCounts = {}; queries.forEach(q => { studentCounts[q.studentName] = (studentCounts[q.studentName] || 0) + 1; });
    const topStudents = Object.entries(studentCounts).sort((a,b) => b[1] - a[1]).slice(0, 5);
    document.getElementById('topStudentsList').innerHTML = `<div class="stats-row">${topStudents.map(([name, count]) => `<div class="stat-card"><div class="stat-number">${count}</div><div>${name}</div></div>`).join('')}</div>`;
    
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); sessionStorage.clear(); window.location.href = '../login.html'; });
</script>
</body>
</html>