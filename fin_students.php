<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Student Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/finance.css">
    <style>
        .student-card { background: white; border-radius: 16px; padding: 15px; margin-bottom: 12px; border: 1px solid #e2edf2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .student-info h4 { margin-bottom: 5px; color: #0a2b38; }
        .student-info p { font-size: 0.75rem; color: #7f8c8d; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
        .status-verified { background: #d9f0e5; color: #1d6f42; }
        .status-pending { background: #fff3e0; color: #b45f06; }
        .btn-verify { background: #27ae60; color: white; border: none; padding: 6px 16px; border-radius: 20px; cursor: pointer; font-size: 0.7rem; }
        .btn-verify:hover { background: #1e8449; }
        .search-section { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .search-input { flex: 1; padding: 10px; border: 1px solid #cbdbe6; border-radius: 12px; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area"><div class="avatar"><i class="fas fa-coins"></i></div><div class="welcome-text">Welcome,</div><div class="user-name" id="financeName">Mr. James Peter</div><div class="user-role">💰 Finance Officer</div><div class="user-id" id="financeId">FIN/2024/001</div></div>
        <div class="nav-menu">
            <a href="finance.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
            <a href="fin_students.php" class="nav-item active"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar"><h1 class="page-title">Student Payment Verification</h1><div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div></div>
        <div class="widget-card">
            <div class="flex-between"><strong>👨‍🎓 Students Awaiting Verification</strong><button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button></div>
            <div class="search-section"><input type="text" id="searchInput" class="search-input" placeholder="Search by name or registration number..."></div>
            <div id="studentsList"></div>
        </div>
    </main>
</div>

<div id="verifyModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Verify Student Payment</h3><span class="close-modal">&times;</span></div>
<form id="verifyForm"><div class="form-group"><label>Student: <span id="verifyStudentName"></span></label></div><div class="form-group"><label>Registration: <span id="verifyStudentReg"></span></label></div><div class="form-group"><label>Payment Amount</label><input type="text" id="verifyAmount" placeholder="Enter amount paid"></div><div class="form-group"><label>Transaction Reference</label><input type="text" id="verifyTransaction" placeholder="Enter transaction reference"></div><div class="form-group"><label>Verification Notes</label><textarea id="verifyNotes" rows="3" placeholder="Add verification notes..."></textarea></div><div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelVerifyBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Confirm & Verify</button></div></form></div></div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('financeName').innerText = loggedUser.name || 'Mr. James Peter';
    document.getElementById('financeId').innerText = loggedUser.regNo || 'FIN/2024/001';
    
    let students = JSON.parse(localStorage.getItem('finance_students') || '[]');
    if (students.length === 0) { students = [{ id:1, name:'John Student', regNo:'IAA/2024/0789', program:'Bachelor of Accounting', status:'Pending', amount:850000, date:'2024-06-01' },{ id:2, name:'Mary Student', regNo:'IAA/2024/0456', program:'Bachelor of Finance', status:'Pending', amount:450000, date:'2024-06-02' },{ id:3, name:'Peter Student', regNo:'IAA/2024/0890', program:'Bachelor of Accounting', status:'Verified', amount:850000, date:'2024-05-28' }]; localStorage.setItem('finance_students', JSON.stringify(students)); }
    
    function saveStudents() { localStorage.setItem('finance_students', JSON.stringify(students)); }
    function renderStudents() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        let filtered = students.filter(s => s.status === 'Pending' || s.status === 'Verified');
        if (searchTerm) filtered = filtered.filter(s => s.name.toLowerCase().includes(searchTerm) || s.regNo.toLowerCase().includes(searchTerm));
        const container = document.getElementById('studentsList');
        if (filtered.length === 0) { container.innerHTML = '<div style="text-align:center; padding:40px;">No students found</div>'; return; }
        container.innerHTML = filtered.map(s => `<div class="student-card"><div class="student-info"><h4>${s.name}</h4><p><i class="fas fa-id-card"></i> ${s.regNo} | <i class="fas fa-book"></i> ${s.program} | Amount: TSh ${s.amount.toLocaleString()}</p></div><div><span class="status-badge ${s.status === 'Verified' ? 'status-verified' : 'status-pending'}">${s.status}</span>${s.status === 'Pending' ? `<button class="btn-verify verify-btn" data-id="${s.id}" data-name="${s.name}" data-reg="${s.regNo}" style="margin-left:10px;"><i class="fas fa-check"></i> Verify</button>` : ''}</div></div>`).join('');
        document.querySelectorAll('.verify-btn').forEach(btn => btn.addEventListener('click', () => { document.getElementById('verifyStudentName').innerText = btn.dataset.name; document.getElementById('verifyStudentReg').innerText = btn.dataset.reg; document.getElementById('verifyModal').style.display = 'flex'; window.currentVerifyId = parseInt(btn.dataset.id); }));
    }
    
    function verifyStudent(id, amount, transaction, notes) { const student = students.find(s => s.id === id); if (student) { student.status = 'Verified'; student.verifiedAmount = amount; student.transactionRef = transaction; student.verifiedDate = new Date().toLocaleDateString('en-US'); student.notes = notes; saveStudents(); renderStudents(); alert(`✅ ${student.name} has been verified successfully!`); } }
    
    document.getElementById('searchInput').addEventListener('keyup', () => renderStudents());
    document.getElementById('refreshBtn').addEventListener('click', () => renderStudents());
    document.querySelectorAll('.close-modal, #cancelVerifyBtn').forEach(el => el.addEventListener('click', () => document.getElementById('verifyModal').style.display = 'none'));
    document.getElementById('verifyForm').addEventListener('submit', (e) => { e.preventDefault(); const amount = document.getElementById('verifyAmount').value; const transaction = document.getElementById('verifyTransaction').value; const notes = document.getElementById('verifyNotes').value; if (!amount) { alert('Please enter amount'); return; } verifyStudent(window.currentVerifyId, amount, transaction, notes); document.getElementById('verifyModal').style.display = 'none'; document.getElementById('verifyAmount').value = ''; document.getElementById('verifyTransaction').value = ''; document.getElementById('verifyNotes').value = ''; });
    renderStudents();
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); sessionStorage.clear(); window.location.href = '../login.html'; });
</script>
</body>
</html>