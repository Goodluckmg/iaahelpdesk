<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Student Queries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/finance.css">
    <style>
        .query-item { background: #f9fdfe; border-left: 3px solid #f39c12; border-radius: 14px; padding: 15px; margin-bottom: 12px; }
        .query-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 5px; }
        .query-meta { font-size: 0.7rem; color: #7f8c8d; margin-bottom: 8px; }
        .query-description { font-size: 0.8rem; color: #2c3e50; margin-bottom: 10px; }
        .respond-btn { background: #f39c12; border: none; padding: 5px 14px; border-radius: 20px; color: white; cursor: pointer; font-size: 0.7rem; transition: 0.2s; }
        .respond-btn:hover { background: #e67e22; }
        .ticket-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #e2edf2; padding-bottom: 10px; flex-wrap: wrap; }
        .ticket-tab { background: none; border: none; padding: 8px 20px; cursor: pointer; font-weight: 500; color: #7f8c8d; transition: 0.2s; border-radius: 20px; }
        .ticket-tab.active { background: #f39c12; color: white; }
        .document-attachment { margin-top: 10px; padding: 8px; background: #e8f0f5; border-radius: 10px; font-size: 0.75rem; }
        .view-doc-btn { background: #2c7da0; color: white; border: none; padding: 3px 10px; border-radius: 15px; cursor: pointer; margin-left: 10px; font-size: 0.7rem; }
        .filter-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .search-box { padding: 8px 12px; border: 1px solid #cbdbe6; border-radius: 20px; width: 250px; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-coins"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="financeName">Mr. James Peter</div>
            <div class="user-role">💰 Finance Officer</div>
            <div class="user-id" id="financeId">FIN/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="finance.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_queries.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
            <a href="fin_students.php" class="nav-item"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar"><h1 class="page-title">Student Finance Queries</h1><div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div></div>

        <div class="widget-card">
            <div class="flex-between"><strong>📋 All Student Queries</strong><button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button></div>
            <div class="ticket-tabs">
                <button class="ticket-tab active" data-filter="all">All</button>
                <button class="ticket-tab" data-filter="Pending">Pending</button>
                <button class="ticket-tab" data-filter="In Progress">In Progress</button>
                <button class="ticket-tab" data-filter="Resolved">Resolved</button>
            </div>
            <div class="filter-bar"><input type="text" id="searchInput" class="search-box" placeholder="Search by name, reg no or title..."></div>
            <div id="queriesList"></div>
        </div>
    </main>
</div>

<div id="respondModal" class="modal"><div class="modal-content"><div class="modal-header"><h3>Respond to Student Query</h3><span class="close-modal">&times;</span></div>
<form id="respondForm"><div class="form-group"><label>Query ID: <span id="queryIdDisplay"></span></label></div><div class="form-group"><label>Student: <span id="studentNameDisplay"></span></label></div><div class="form-group"><label>Registration: <span id="studentRegDisplay"></span></label></div>
<div class="form-group"><label>Original Query:</label><div id="originalQueryDisplay" style="background:#f8fafc; padding:10px; border-radius:12px; font-size:0.85rem;"></div></div>
<div class="form-group" id="documentDisplay" style="display:none;"></div>
<div class="form-group"><label>Response Message</label><textarea id="responseMsg" rows="4" placeholder="Write your response..."></textarea></div>
<div class="form-group"><label>Update Status</label><select id="responseStatus"><option value="In Progress">In Progress (Being reviewed)</option><option value="Resolved">Resolved (Payment confirmed)</option></select></div>
<div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Send Response</button></div></form></div></div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('financeName').innerText = loggedUser.name || 'Mr. James Peter';
    document.getElementById('financeId').innerText = loggedUser.regNo || 'FIN/2024/001';
    
    let studentQueries = JSON.parse(localStorage.getItem('student_finance_queries') || '[]');
    let currentFilter = 'all', currentQueryId = null;
    
    if (studentQueries.length === 0) { studentQueries = [{ id:2001, studentName:'John Student', studentReg:'IAA/2024/0789', title:'Fee payment not reflected', category:'Tuition Fee', priority:'High', description:'Paid TSh 850,000 but system shows pending', status:'Pending', date:'2024-06-03', hasDocument:true, documentName:'receipt.jpg' },{ id:2002, studentName:'Mary Student', studentReg:'IAA/2024/0456', title:'Library fee verification', category:'Library Fee', priority:'Medium', description:'Paid library fee need confirmation', status:'In Progress', date:'2024-06-02', hasDocument:true },{ id:2003, studentName:'Peter Student', studentReg:'IAA/2024/0890', title:'Scholarship balance', category:'Scholarship', priority:'Low', description:'Confirm remaining balance', status:'Resolved', date:'2024-05-30', hasDocument:false }]; localStorage.setItem('student_finance_queries', JSON.stringify(studentQueries)); }
    
    function saveQueries() { localStorage.setItem('student_finance_queries', JSON.stringify(studentQueries)); }
    
    function renderQueries() {
        let filtered = currentFilter === 'all' ? studentQueries : studentQueries.filter(q => q.status === currentFilter);
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        if (searchTerm) filtered = filtered.filter(q => q.studentName.toLowerCase().includes(searchTerm) || q.studentReg.toLowerCase().includes(searchTerm) || q.title.toLowerCase().includes(searchTerm));
        const container = document.getElementById('queriesList');
        if (filtered.length === 0) { container.innerHTML = '<div style="text-align:center; padding:40px;">No queries found</div>'; return; }
        container.innerHTML = filtered.map(q => `<div class="query-item"><div class="flex-between"><div class="query-title">#${q.id} - ${escapeHtml(q.title)}</div><span class="status-badge ${q.status === 'Resolved' ? 'status-resolved' : q.status === 'In Progress' ? 'status-progress' : 'status-pending'}">${q.status}</span></div><div class="query-meta"><i class="fas fa-user"></i> ${q.studentName} (${q.studentReg}) | 📅 ${q.date} | 🔥 ${q.priority}</div><div class="query-description">${escapeHtml(q.description.substring(0, 100))}${q.description.length > 100 ? '...' : ''}</div>${q.hasDocument ? `<div class="document-attachment"><i class="fas fa-paperclip"></i> Receipt attached: ${q.documentName || 'Document'} <button class="view-doc-btn" data-doc="${q.documentData || ''}" data-name="${q.documentName || 'Document'}">View</button></div>` : ''}${q.status !== 'Resolved' ? `<button class="respond-btn" data-id="${q.id}" data-name="${q.studentName}" data-reg="${q.studentReg}" data-desc="${escapeHtml(q.description)}">📝 Respond</button>` : ''}</div>`).join('');
        document.querySelectorAll('.view-doc-btn').forEach(btn => btn.addEventListener('click', () => alert('Document viewer would open here')));
        document.querySelectorAll('.respond-btn').forEach(btn => btn.addEventListener('click', () => { currentQueryId = parseInt(btn.dataset.id); document.getElementById('queryIdDisplay').innerText = currentQueryId; document.getElementById('studentNameDisplay').innerText = btn.dataset.name; document.getElementById('studentRegDisplay').innerText = btn.dataset.reg; document.getElementById('originalQueryDisplay').innerHTML = btn.dataset.desc; document.getElementById('respondModal').style.display = 'flex'; }));
    }
    
    function respondToQuery(queryId, response, newStatus) { const query = studentQueries.find(q => q.id === queryId); if (query) { query.status = newStatus; query.response = response; query.responseDate = new Date().toLocaleDateString('en-US'); saveQueries(); renderQueries(); alert(`✅ Response sent! Status updated to ${newStatus}.`); } }
    
    document.querySelectorAll('.ticket-tab').forEach(tab => tab.addEventListener('click', () => { document.querySelectorAll('.ticket-tab').forEach(t => t.classList.remove('active')); tab.classList.add('active'); currentFilter = tab.dataset.filter; renderQueries(); }));
    document.getElementById('searchInput').addEventListener('keyup', () => renderQueries());
    document.getElementById('refreshBtn').addEventListener('click', () => renderQueries());
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => el.addEventListener('click', () => document.getElementById('respondModal').style.display = 'none'));
    document.getElementById('respondForm').addEventListener('submit', (e) => { e.preventDefault(); const response = document.getElementById('responseMsg').value; const status = document.getElementById('responseStatus').value; if (!response) { alert('Please write a response'); return; } respondToQuery(currentQueryId, response, status); document.getElementById('respondModal').style.display = 'none'; document.getElementById('responseMsg').value = ''; });
    renderQueries();
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); sessionStorage.clear(); window.location.href = '../login.html'; });
</script>
</body>
</html>