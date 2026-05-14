<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            <a href="index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="analytics.php" class="nav-item active"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="index.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Analytics & Institutional Reports</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Performance Metrics</strong></div>
            <div id="analyticsStats" style="margin-bottom: 20px;"></div>
            <canvas id="categoryChart" width="400" height="200" style="max-height: 220px; width: 100%;"></canvas>
            <hr>
            <p><i class="fas fa-chart-line"></i> Data updates in real-time. Use insights for institutional improvement.</p>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    let categoryChart = null;

    function renderAnalytics() {
        const stats = getStats();
        const total = stats.total;
        const resolvedRate = total === 0 ? 0 : Math.round((stats.resolved / total) * 100);
        
        const categories = {
            'Missing Marks': 0,
            'Fee-related': 0,
            'Portal Login': 0,
            'Registration Error': 0,
            'Other': 0
        };
        
        appData.tickets.forEach(t => {
            if (categories[t.category] !== undefined) categories[t.category]++;
            else categories['Other']++;
        });
        
        const topCategory = Object.entries(categories).reduce((a, b) => a[1] > b[1] ? a : b, ['None', 0]);
        
        document.getElementById('analyticsStats').innerHTML = `
            <p><strong>Total queries submitted:</strong> ${total}</p>
            <p><strong>Resolution rate:</strong> ${resolvedRate}% (${stats.resolved} resolved)</p>
            <p><strong>Currently open:</strong> ${stats.open} | <strong>In progress:</strong> ${stats.inProgress}</p>
            <p><strong>Most common issue:</strong> ${topCategory[0]} (${topCategory[1]} queries)</p>
            <p><strong>Department performance:</strong> ICT Support: 2.1h avg | Exams: 3.5h avg</p>
        `;
        
        const ctx = document.getElementById('categoryChart')?.getContext('2d');
        if (ctx) {
            if (categoryChart) categoryChart.destroy();
            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(categories),
                    datasets: [{
                        label: 'Number of queries',
                        data: Object.values(categories),
                        backgroundColor: '#2c7da0',
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });
        }
    }

    loadFromLocalStorage();
    initDemoData();
    setCurrentDate();
    document.getElementById('userName').innerText = appData.currentUser.name;
    document.getElementById('studentId').innerText = appData.currentUser.studentId;
    renderAnalytics();

    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.clear();
        window.location.href = 'index.php';
    });
</script>
</body>
</html>