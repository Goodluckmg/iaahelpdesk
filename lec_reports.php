<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Reports & Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            <a href="lec_reports.php" class="nav-item" data-view="resolved"><i class="fas fa-check-circle"></i><span class="nav-label">Resolved</span></a>
            <a href="lec_courses.php" class="nav-item" data-view="courses"><i class="fas fa-book"></i><span class="nav-label">My Courses</span></a>
            <a href="lec_reports.php" class="nav-item active" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Reports & Analytics</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row" id="statsRow">
            <div class="stat-card"><div class="stat-number" id="totalRequests">0</div><div>Total Requests</div></div>
            <div class="stat-card"><div class="stat-number" id="resolvedRate">0%</div><div>Resolution Rate</div></div>
            <div class="stat-card"><div class="stat-number" id="avgResponseTime">0</div><div>Avg Response (days)</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Request Status Distribution</strong></div>
            <canvas id="statusChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📈 Requests by Priority</strong></div>
            <canvas id="priorityChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📅 Monthly Performance Summary</strong></div>
            <canvas id="monthlyChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>
    </main>
</div>

<script src="lecturer.js"></script>
<script>
    let requests = [];
    let statusChart = null;
    let priorityChart = null;
    let monthlyChart = null;

    function loadData() {
        requests = loadRequests();
        updateStats();
        renderStatusChart();
        renderPriorityChart();
        renderMonthlyChart();
    }

    function updateStats() {
        const total = requests.length;
        const resolved = requests.filter(r => r.status === 'Resolved').length;
        const resolvedRate = total === 0 ? 0 : Math.round((resolved / total) * 100);
        
        // Calculate average response time (simulated)
        const avgResponseTime = total === 0 ? 0 : Math.round((resolved * 2.5) / total);
        
        document.getElementById('totalRequests').innerText = total;
        document.getElementById('resolvedRate').innerText = resolvedRate + '%';
        document.getElementById('avgResponseTime').innerText = avgResponseTime;
    }

    function renderStatusChart() {
        const pending = requests.filter(r => r.status === 'Pending').length;
        const inProgress = requests.filter(r => r.status === 'In Progress').length;
        const resolved = requests.filter(r => r.status === 'Resolved').length;
        
        const ctx = document.getElementById('statusChart').getContext('2d');
        if (statusChart) statusChart.destroy();
        
        statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Resolved'],
                datasets: [{
                    data: [pending, inProgress, resolved],
                    backgroundColor: ['#c0392b', '#e67e22', '#27ae60'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    function renderPriorityChart() {
        const high = requests.filter(r => r.priority === 'High').length;
        const medium = requests.filter(r => r.priority === 'Medium').length;
        const low = requests.filter(r => r.priority === 'Low').length;
        
        const ctx = document.getElementById('priorityChart').getContext('2d');
        if (priorityChart) priorityChart.destroy();
        
        priorityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['High', 'Medium', 'Low'],
                datasets: [{
                    label: 'Number of Requests',
                    data: [high, medium, low],
                    backgroundColor: ['#c0392b', '#e67e22', '#27ae60'],
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    function renderMonthlyChart() {
        // Simulated monthly data
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const received = [12, 15, 18, 22, 25, 28];
        const resolved = [10, 12, 16, 20, 23, 26];
        
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        if (monthlyChart) monthlyChart.destroy();
        
        monthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    { label: 'Requests Received', data: received, borderColor: '#3498db', backgroundColor: 'rgba(52,152,219,0.1)', fill: true, tension: 0.3 },
                    { label: 'Requests Resolved', data: resolved, borderColor: '#27ae60', backgroundColor: 'rgba(39,174,96,0.1)', fill: true, tension: 0.3 }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    loadData();
</script>
</body>
</html>