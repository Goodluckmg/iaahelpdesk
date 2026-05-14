<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | ICT Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/ict.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-laptop-code"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="ictName">Ms. Anna Kaiza</div>
            <div class="user-role">💻 ICT Support</div>
            <div class="user-id" id="ictId">ICT/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="ict.php" class="nav-item" data-view="dashboard"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="ict_tickets.php" class="nav-item" data-view="tickets"><i class="fas fa-ticket-alt"></i><span class="nav-label">Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item" data-view="systems"><i class="fas fa-server"></i><span class="nav-label">System Status</span></a>
            <a href="ict_maintenance.php" class="nav-item" data-view="maintenance"><i class="fas fa-tools"></i><span class="nav-label">Maintenance</span></a>
            <a href="ict_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">ICT Performance Reports</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row" id="statsRow">
            <div class="stat-card"><div class="stat-number" id="totalTickets">0</div><div>Total Tickets</div></div>
            <div class="stat-card"><div class="stat-number" id="resolutionRate">0%</div><div>Resolution Rate</div></div>
            <div class="stat-card"><div class="stat-number" id="avgResponseTime">0</div><div>Avg Response (hrs)</div></div>
            <div class="stat-card"><div class="stat-number" id="systemUptime">0%</div><div>System Uptime</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Ticket Status Distribution</strong></div>
            <canvas id="statusChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📈 Tickets by Category</strong></div>
            <canvas id="categoryChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📅 Weekly Ticket Trends</strong></div>
            <canvas id="trendChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>
    </main>
</div>

<script src="ict.js"></script>
<script>
    let tickets = [];
    let systems = [];
    let statusChart = null;
    let categoryChart = null;
    let trendChart = null;

    function loadData() {
        tickets = loadTickets();
        systems = loadSystems();
        updateStats();
        renderStatusChart();
        renderCategoryChart();
        renderTrendChart();
    }

    function updateStats() {
        const stats = getTicketStats(tickets);
        const resolutionRate = stats.total === 0 ? 0 : Math.round((stats.resolved / stats.total) * 100);
        const sysStats = getSystemStats(systems);
        const avgUptime = systems.length === 0 ? 0 : Math.round(systems.reduce((sum, s) => sum + parseFloat(s.uptime || 99.9), 0) / systems.length);
        
        document.getElementById('totalTickets').innerText = stats.total;
        document.getElementById('resolutionRate').innerText = resolutionRate + '%';
        document.getElementById('avgResponseTime').innerText = stats.resolved === 0 ? 0 : Math.round((stats.resolved * 24) / stats.total);
        document.getElementById('systemUptime').innerText = avgUptime + '%';
    }

    function renderStatusChart() {
        const open = tickets.filter(t => t.status === 'Open').length;
        const inProgress = tickets.filter(t => t.status === 'In Progress').length;
        const resolved = tickets.filter(t => t.status === 'Resolved').length;
        
        const ctx = document.getElementById('statusChart').getContext('2d');
        if (statusChart) statusChart.destroy();
        
        statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Open', 'In Progress', 'Resolved'],
                datasets: [{
                    data: [open, inProgress, resolved],
                    backgroundColor: ['#e74c3c', '#f39c12', '#27ae60'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    function renderCategoryChart() {
        const categories = {
            'Login Issue': 0, 'Email': 0, 'Network': 0, 'Software': 0, 'Hardware': 0, 'Other': 0
        };
        tickets.forEach(t => {
            if (categories[t.category] !== undefined) categories[t.category]++;
            else categories['Other']++;
        });
        
        const ctx = document.getElementById('categoryChart').getContext('2d');
        if (categoryChart) categoryChart.destroy();
        
        categoryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(categories),
                datasets: [{
                    label: 'Number of Tickets',
                    data: Object.values(categories),
                    backgroundColor: '#3498db',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    function renderTrendChart() {
        const weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        const received = [12, 18, 15, 22];
        const resolved = [10, 15, 14, 20];
        
        const ctx = document.getElementById('trendChart').getContext('2d');
        if (trendChart) trendChart.destroy();
        
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: weeks,
                datasets: [
                    { label: 'Tickets Received', data: received, borderColor: '#e74c3c', backgroundColor: 'rgba(231,76,60,0.1)', fill: true, tension: 0.3 },
                    { label: 'Tickets Resolved', data: resolved, borderColor: '#27ae60', backgroundColor: 'rgba(39,174,96,0.1)', fill: true, tension: 0.3 }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    loadData();
</script>
</body>
</html>