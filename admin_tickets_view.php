<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | All Tickets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <a href="admin_tickets_view.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
              <a href="admin_edit.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
              <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">All Tickets</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>🎫 All System Tickets</strong>
                <div><select id="filterStatus" style="width:150px; margin-right:10px;"><option value="all">All Status</option><option value="Open">Open</option><option value="In Progress">In Progress</option><option value="Resolved">Resolved</option></select><button class="btn-primary" id="refreshTicketsBtn"><i class="fas fa-sync-alt"></i> Refresh</button></div>
            </div>
            <table id="allTicketsTable"><thead><tr><th>ID</th><th>Student</th><th>Subject</th><th>Department</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody id="allTicketsBody"></tbody></table>
        </div>
    </main>
</div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('adminName').innerText = loggedUser.name ? loggedUser.name.split('/').pop() : 'Administrator';
    document.getElementById('adminId').innerText = loggedUser.regNo || 'ADMIN/001';
    
    let tickets = JSON.parse(localStorage.getItem('admin_tickets') || '[]');
    if (tickets.length === 0) { tickets = [{ id: 1001, studentName: 'John Student', subject: 'Missing marks in CSC 201', department: 'Examination & Records', priority: 'High', status: 'In Progress', date: '2024-06-01' }, { id: 1002, studentName: 'John Student', subject: 'Portal login error', department: 'ICT Support', priority: 'Urgent', status: 'Open', date: '2024-06-02' }]; localStorage.setItem('admin_tickets', JSON.stringify(tickets)); }
    
    function renderTickets(filter = 'all') {
        let filtered = filter === 'all' ? tickets : tickets.filter(t => t.status === filter);
        document.getElementById('allTicketsBody').innerHTML = filtered.map(t => `<tr><td>#${t.id}</td><td>${t.studentName}</td><td>${t.subject.substring(0, 30)}</td><td>${t.department}</td><td><span class="status-badge ${t.priority === 'Urgent' ? 'status-urgent' : ''}">${t.priority}</span></td><td><select class="status-select" data-id="${t.id}" style="padding:4px; border-radius:12px;"><option ${t.status === 'Open' ? 'selected' : ''}>Open</option><option ${t.status === 'In Progress' ? 'selected' : ''}>In Progress</option><option ${t.status === 'Resolved' ? 'selected' : ''}>Resolved</option></select></td><td>${t.date}</td><td><button class="btn-primary view-ticket" data-id="${t.id}" style="padding:4px 10px;"><i class="fas fa-eye"></i></button></td></tr>`).join('');
        document.querySelectorAll('.status-select').forEach(select => { select.addEventListener('change', () => { let ticket = tickets.find(t => t.id === parseInt(select.dataset.id)); if (ticket) { ticket.status = select.value; localStorage.setItem('admin_tickets', JSON.stringify(tickets)); renderTickets(filter); } }); });
    }
    
    function refreshTickets() { tickets = JSON.parse(localStorage.getItem('admin_tickets') || '[]'); renderTickets(document.getElementById('filterStatus').value); }
    
    document.getElementById('refreshTicketsBtn').addEventListener('click', refreshTickets);
    document.getElementById('filterStatus').addEventListener('change', () => renderTickets(document.getElementById('filterStatus').value));
    renderTickets('all');
</script>
</body>
</html>