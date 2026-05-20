<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-shield"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="adminName">Administrator</div>
            <div class="user-role">⚙️ Super Admin</div>
            <div class="user-id" id="adminId">ADMIN/001</div>
        </div>
        <div class="nav-menu">
            <a href="admin.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
              <a href="admin_edit.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="admin_analytics.php" class="nav-item active"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Analytics & Reports</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📈 System Analytics</strong></div>
            <div id="statsSummary" style="margin-bottom: 20px;"></div>
            <canvas id="ticketsChart" width="400" height="200" style="max-height: 250px; width: 100%; margin-bottom: 30px;"></canvas>
            <canvas id="deptChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>
    </main>
</div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('adminName').innerText = loggedUser.name ? loggedUser.name.split('/').pop() : 'Administrator';
    document.getElementById('adminId').innerText = loggedUser.regNo || 'ADMIN/001';
    
    let tickets = JSON.parse(localStorage.getItem('admin_tickets') || '[]');
    let users = JSON.parse(localStorage.getItem('admin_users') || '[]');
    
    let statusCounts = { 'Open': tickets.filter(t => t.status === 'Open').length, 'In Progress': tickets.filter(t => t.status === 'In Progress').length, 'Resolved': tickets.filter(t => t.status === 'Resolved').length };
    let deptCounts = {};
    tickets.forEach(t => { deptCounts[t.department] = (deptCounts[t.department] || 0) + 1; });
    let resolvedRate = tickets.length === 0 ? 0 : Math.round((statusCounts.Resolved / tickets.length) * 100);
    
    document.getElementById('statsSummary').innerHTML = `<div class="stats-row"><div class="stat-card"><i class="fas fa-users"></i><div class="stat-number">${users.length}</div><div>Total Users</div></div><div class="stat-card"><i class="fas fa-ticket-alt"></i><div class="stat-number">${tickets.length}</div><div>Total Tickets</div></div><div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number">${resolvedRate}%</div><div>Resolution Rate</div></div></div>`;
    
    let ctx1 = document.getElementById('ticketsChart').getContext('2d');
    new Chart(ctx1, { type: 'doughnut', data: { labels: Object.keys(statusCounts), datasets: [{ data: Object.values(statusCounts), backgroundColor: ['#2c7da0', '#e67e22', '#1d6f42'] }] }, options: { responsive: true } });
    
    let ctx2 = document.getElementById('deptChart').getContext('2d');
    new Chart(ctx2, { type: 'bar', data: { labels: Object.keys(deptCounts), datasets: [{ label: 'Tickets by Department', data: Object.values(deptCounts), backgroundColor: '#2c7da0', borderRadius: 8 }] }, options: { responsive: true } });
</script>
</body>
</html>