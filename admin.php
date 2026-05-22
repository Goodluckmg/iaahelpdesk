<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-shield"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="adminName">Administrator</div>
            <div class="user-role">⚙️ Super Admin</div>
            <div class="user-id" id="adminId">ADMIN/001</div>
        </div>
        <div class="nav-menu">
           <div class="nav-menu">
    <a href="admin.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
    <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
    <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
    <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
    <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
    <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
    <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
    <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
    <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
</div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Admin Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-users"></i><div class="stat-number" id="totalUsers">0</div><div>Total Users</div></div>
            <div class="stat-card"><i class="fas fa-ticket-alt"></i><div class="stat-number" id="totalTickets">0</div><div>Total Tickets</div></div>
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="openTickets">0</div><div>Open Tickets</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="resolvedTickets">0</div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-building"></i><div class="stat-number" id="totalDepts">0</div><div>Departments</div></div>
            <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number" id="resolutionRate">0%</div><div>Resolution Rate</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📋 Recent Tickets</strong><button class="btn-primary" id="refreshDashboardBtn"><i class="fas fa-sync-alt"></i> Refresh</button></div>
            <table id="recentTicketsTable"><thead><tr><th>ID</th><th>Student</th><th>Subject</th><th>Department</th><th>Status</th><th>Date</th></tr></thead><tbody id="recentTicketsBody"></tbody></table>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Statistics by Department</strong></div>
            <div id="deptStats"></div>
        </div>
    </main>
</div>

<script>
    // Set current date
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', options);
    
    // Load data
    let tickets = JSON.parse(localStorage.getItem('admin_tickets') || '[]');
    let users = JSON.parse(localStorage.getItem('admin_users') || '[]');
    let departments = JSON.parse(localStorage.getItem('admin_departments') || '[]');
    
    // Load user from session
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('adminName').innerText = loggedUser.name ? loggedUser.name.split('/').pop() : 'Administrator';
    document.getElementById('adminId').innerText = loggedUser.regNo || 'ADMIN/001';
    
    // Initialize demo data if empty
    if (users.length === 0) {
        users = [
            { id: 1, name: 'Admin User', regNo: 'ADMIN/001', email: 'admin@iaa.ac.tz', role: 'admin', status: 'Active' },
            { id: 2, name: 'John Student', regNo: 'IAA/2024/0789', email: 'john@iaa.ac.tz', role: 'student', status: 'Active' },
            { id: 3, name: 'Dr. Sarah Lecturer', regNo: 'STAFF/2024/001', email: 'sarah@iaa.ac.tz', role: 'lecturer', status: 'Active' }
        ];
        localStorage.setItem('admin_users', JSON.stringify(users));
    }
    if (tickets.length === 0) {
        tickets = [
            { id: 1001, studentName: 'John Student', subject: 'Missing marks in CSC 201', department: 'Examination & Records', status: 'In Progress', date: '2024-06-01' },
            { id: 1002, studentName: 'John Student', subject: 'Portal login error', department: 'ICT Support', status: 'Open', date: '2024-06-02' }
        ];
        localStorage.setItem('admin_tickets', JSON.stringify(tickets));
    }
    if (departments.length === 0) {
        departments = [
            { id: 1, name: 'Examination & Records', head: 'Dr. John Mkono', email: 'exams@iaa.ac.tz' },
            { id: 2, name: 'Finance Office', head: 'Mr. James Peter', email: 'finance@iaa.ac.tz' },
            { id: 3, name: 'ICT Support', head: 'Ms. Anna Kaiza', email: 'ict@iaa.ac.tz' }
        ];
        localStorage.setItem('admin_departments', JSON.stringify(departments));
    }
    
    function updateDashboard() {
        document.getElementById('totalUsers').innerText = users.length;
        document.getElementById('totalTickets').innerText = tickets.length;
        document.getElementById('openTickets').innerText = tickets.filter(t => t.status === 'Open').length;
        document.getElementById('resolvedTickets').innerText = tickets.filter(t => t.status === 'Resolved').length;
        document.getElementById('totalDepts').innerText = departments.length;
        let resolvedCount = tickets.filter(t => t.status === 'Resolved').length;
        document.getElementById('resolutionRate').innerText = tickets.length === 0 ? '0%' : Math.round((resolvedCount / tickets.length) * 100) + '%';
        
        let recent = [...tickets].reverse().slice(0, 10);
        document.getElementById('recentTicketsBody').innerHTML = recent.length === 0 ? '<tr><td colspan="6">No tickets yet</td>' : recent.map(t => `<tr><td>#${t.id}</td><td>${t.studentName}</td><td>${t.subject.substring(0, 30)}</td><td>${t.department}</td><td><span class="status-badge">${t.status}</span></td><td>${t.date}</td></tr>`).join('');
        
        let deptStats = {};
        tickets.forEach(t => { deptStats[t.department] = (deptStats[t.department] || 0) + 1; });
        document.getElementById('deptStats').innerHTML = `<div class="stats-row">${Object.entries(deptStats).map(([dept, count]) => `<div class="stat-card"><i class="fas fa-building"></i><div class="stat-number">${count}</div><div>${dept}</div></div>`).join('') || '<p>No data yet</p>'}</div>`;
    }
    
    updateDashboard();
    document.getElementById('refreshDashboardBtn').addEventListener('click', () => { tickets = JSON.parse(localStorage.getItem('admin_tickets') || '[]'); users = JSON.parse(localStorage.getItem('admin_users') || '[]'); updateDashboard(); });
</script>
</body>
</html>