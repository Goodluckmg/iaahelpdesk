<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | System Settings</title>
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
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
              <a href="edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item active"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">System Settings</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>⚙️ System Settings</strong></div>
            <div class="form-group"><label>System Name</label><input type="text" id="sysName" value="IAA Student Helpdesk"></div>
            <div class="form-group"><label>System Email</label><input type="email" id="sysEmail" value="helpdesk@iaa.ac.tz"></div>
            <div class="form-group"><label>Default Response Time (hours)</label><input type="number" id="responseTime" value="24"></div>
            <div class="form-group"><label>Maintenance Mode</label><select id="maintenanceMode"><option value="off">Off</option><option value="on">On</option></select></div>
            <button class="btn-primary" id="saveSettingsBtn">Save Settings</button>
            <hr>
            <button class="btn-danger btn-primary" id="clearAllDataBtn" style="background:#c0392b;">⚠️ Clear All System Data</button>
        </div>
    </main>
</div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('adminName').innerText = loggedUser.name ? loggedUser.name.split('/').pop() : 'Administrator';
    document.getElementById('adminId').innerText = loggedUser.regNo || 'ADMIN/001';
    
    document.getElementById('saveSettingsBtn').addEventListener('click', () => { alert('✅ Settings saved successfully!'); });
    document.getElementById('clearAllDataBtn').addEventListener('click', () => { if (confirm('WARNING: This will delete ALL data! Are you sure?')) { localStorage.clear(); alert('All data cleared! Page will reload.'); location.reload(); } });
</script>
</body>
</html>