<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Departments</title>
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
            <a href="admin_departments.php" class="nav-item active"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
              <a href="admin_edit.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Departments</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>🏢 Departments</strong><button class="btn-primary" id="addDeptBtn"><i class="fas fa-plus"></i> Add Department</button></div>
            <table id="deptsTable"><thead><tr><th>ID</th><th>Department Name</th><th>Head of Department</th><th>Email</th><th>Tickets</th><th>Actions</th></tr></thead><tbody id="deptsBody"></tbody></table>
        </div>
    </main>
</div>

<!-- ADD DEPARTMENT MODAL -->
<div id="deptModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="deptModalTitle">Add Department</h3><span class="close-dept-modal">&times;</span></div>
        <form id="deptForm">
            <div class="form-group"><label>Department Name</label><input type="text" id="deptName" required></div>
            <div class="form-group"><label>Head of Department</label><input type="text" id="deptHead" required></div>
            <div class="form-group"><label>Department Email</label><input type="email" id="deptEmail" required></div>
            <div style="display:flex; gap:10px; justify-content:flex-end;"><button type="button" class="btn-primary" id="cancelDeptModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Save Department</button></div>
        </form>
    </div>
</div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('adminName').innerText = loggedUser.name ? loggedUser.name.split('/').pop() : 'Administrator';
    document.getElementById('adminId').innerText = loggedUser.regNo || 'ADMIN/001';
    
    let departments = JSON.parse(localStorage.getItem('admin_departments') || '[]');
    let tickets = JSON.parse(localStorage.getItem('admin_tickets') || '[]');
    let nextDeptId = parseInt(localStorage.getItem('admin_nextDeptId') || '1');
    
    if (departments.length === 0) { departments = [{ id: 1, name: 'Examination & Records', head: 'Dr. John Mkono', email: 'exams@iaa.ac.tz' }, { id: 2, name: 'Finance Office', head: 'Mr. James Peter', email: 'finance@iaa.ac.tz' }, { id: 3, name: 'ICT Support', head: 'Ms. Anna Kaiza', email: 'ict@iaa.ac.tz' }]; nextDeptId = 4; saveData(); }
    
    function saveData() { localStorage.setItem('admin_departments', JSON.stringify(departments)); localStorage.setItem('admin_nextDeptId', nextDeptId); }
    
    function renderDepartments() {
        document.getElementById('deptsBody').innerHTML = departments.map(d => `<tr><td>${d.id}</td><td>${d.name}</td><td>${d.head}</td><td>${d.email}</td><td>${tickets.filter(t => t.department === d.name).length}</td><td><button class="btn-primary edit-dept" data-id="${d.id}" style="padding:4px 10px; margin-right:5px;"><i class="fas fa-edit"></i></button><button class="btn-danger delete-dept" data-id="${d.id}" style="padding:4px 10px; background:#c0392b;"><i class="fas fa-trash"></i></button></td></tr>`).join('');
    }
    
    function addDepartment(deptData) { let newDept = { id: nextDeptId++, ...deptData }; departments.push(newDept); saveData(); renderDepartments(); }
    
    document.getElementById('addDeptBtn').addEventListener('click', () => { document.getElementById('deptForm').reset(); document.getElementById('deptModal').style.display = 'flex'; });
    document.querySelectorAll('.close-dept-modal, #cancelDeptModalBtn').forEach(el => el.addEventListener('click', () => document.getElementById('deptModal').style.display = 'none'));
    document.getElementById('deptForm').addEventListener('submit', (e) => { e.preventDefault(); addDepartment({ name: document.getElementById('deptName').value, head: document.getElementById('deptHead').value, email: document.getElementById('deptEmail').value }); document.getElementById('deptModal').style.display = 'none'; });
    
    renderDepartments();
</script>
</body>
</html>