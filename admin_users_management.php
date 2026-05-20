<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | User Management</title>
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
            <a href="admin_users_management.php" class="nav-item active"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
              <a href="admin_edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">User Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>👥 System Users</strong><button class="btn-primary" id="addUserBtn"><i class="fas fa-plus"></i> Add New User</button></div>
            <table id="usersTable"><thead><tr><th>ID</th><th>Full Name</th><th>Registration No</th><th>Role</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead><tbody id="usersBody"></tbody></table>
        </div>
    </main>
</div>

<!-- ADD/EDIT USER MODAL -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">Add New User</h3><span class="close-modal">&times;</span></div>
        <form id="userForm">
            <div class="form-group"><label>Full Name</label><input type="text" id="userFullName" required></div>
            <div class="form-group"><label>Registration Number / Staff ID</label><input type="text" id="userRegNo" required></div>
            <div class="form-group"><label>Email</label><input type="email" id="userEmail" required></div>
            <div class="form-group"><label>Role</label><select id="userRole"><option value="student">Student</option><option value="lecturer">Lecturer</option><option value="admin">Admin</option><option value="finance">Finance</option><option value="ict">ICT Support</option></select></div>
            <div class="form-group"><label>Password</label><input type="password" id="userPassword" placeholder="Leave blank to keep current"></div>
            <div style="display:flex; gap:10px; justify-content:flex-end;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Save User</button></div>
        </form>
    </div>
</div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('adminName').innerText = loggedUser.name ? loggedUser.name.split('/').pop() : 'Administrator';
    document.getElementById('adminId').innerText = loggedUser.regNo || 'ADMIN/001';
    
    let users = JSON.parse(localStorage.getItem('admin_users') || '[]');
    let nextUserId = parseInt(localStorage.getItem('admin_nextUserId') || '1');
    
    if (users.length === 0) {
        users = [{ id: 1, name: 'Admin User', regNo: 'ADMIN/001', email: 'admin@iaa.ac.tz', role: 'admin', password: 'admin123', status: 'Active' }];
        nextUserId = 2;
        localStorage.setItem('admin_users', JSON.stringify(users));
        localStorage.setItem('admin_nextUserId', nextUserId);
    }
    
    function saveUsers() { localStorage.setItem('admin_users', JSON.stringify(users)); localStorage.setItem('admin_nextUserId', nextUserId); }
    
    function renderUsers() {
        document.getElementById('usersBody').innerHTML = users.map(u => `<tr><td>${u.id}</td><td>${u.name}</td><td>${u.regNo}</td><td><span class="status-badge">${u.role}</span></td><td>${u.email}</td><td><span class="status-badge ${u.status === 'Active' ? 'status-resolved' : ''}">${u.status}</span></td><td><button class="btn-primary edit-user" data-id="${u.id}" style="padding:4px 10px; margin-right:5px;"><i class="fas fa-edit"></i></button><button class="btn-danger delete-user" data-id="${u.id}" style="padding:4px 10px; background:#c0392b;"><i class="fas fa-trash"></i></button></td></tr>`).join('');
        document.querySelectorAll('.edit-user').forEach(btn => btn.addEventListener('click', () => editUser(parseInt(btn.dataset.id))));
        document.querySelectorAll('.delete-user').forEach(btn => btn.addEventListener('click', () => deleteUser(parseInt(btn.dataset.id))));
    }
    
    function addUser(userData) { let newUser = { id: nextUserId++, ...userData, status: 'Active' }; users.push(newUser); saveUsers(); renderUsers(); }
    function editUser(id) { let user = users.find(u => u.id === id); if (user) { document.getElementById('modalTitle').innerText = 'Edit User'; document.getElementById('userFullName').value = user.name; document.getElementById('userRegNo').value = user.regNo; document.getElementById('userEmail').value = user.email; document.getElementById('userRole').value = user.role; document.getElementById('userPassword').value = ''; document.getElementById('userModal').style.display = 'flex'; window.currentEditId = id; } }
    function deleteUser(id) { if (confirm('Delete this user?')) { users = users.filter(u => u.id !== id); saveUsers(); renderUsers(); } }
    
    document.getElementById('addUserBtn').addEventListener('click', () => { document.getElementById('modalTitle').innerText = 'Add New User'; document.getElementById('userForm').reset(); window.currentEditId = null; document.getElementById('userModal').style.display = 'flex'; });
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => el.addEventListener('click', () => document.getElementById('userModal').style.display = 'none'));
    document.getElementById('userForm').addEventListener('submit', (e) => { e.preventDefault(); let userData = { name: document.getElementById('userFullName').value, regNo: document.getElementById('userRegNo').value, email: document.getElementById('userEmail').value, role: document.getElementById('userRole').value, password: document.getElementById('userPassword').value || 'default123' }; if (window.currentEditId) { let index = users.findIndex(u => u.id === window.currentEditId); if (index !== -1) { users[index] = { ...users[index], ...userData }; saveUsers(); renderUsers(); } } else { addUser(userData); } document.getElementById('userModal').style.display = 'none'; });
    
    renderUsers();
</script>
</body>
</html>