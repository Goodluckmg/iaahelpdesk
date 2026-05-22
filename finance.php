<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/finance.css">
    <style>
        .query-item {
            background: #f9fdfe;
            border-left: 3px solid #f39c12;
            border-radius: 14px;
            padding: 15px;
            margin-bottom: 12px;
        }
        .query-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 5px;
        }
        .query-meta {
            font-size: 0.7rem;
            color: #7f8c8d;
            margin-bottom: 8px;
        }
        .query-description {
            font-size: 0.8rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .respond-btn {
            background: #f39c12;
            border: none;
            padding: 5px 14px;
            border-radius: 20px;
            color: white;
            cursor: pointer;
            font-size: 0.7rem;
            transition: 0.2s;
        }
        .respond-btn:hover {
            background: #e67e22;
        }
        .ticket-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2edf2;
            padding-bottom: 10px;
        }
        .ticket-tab {
            background: none;
            border: none;
            padding: 8px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #7f8c8d;
            transition: 0.2s;
            border-radius: 20px;
        }
        .ticket-tab.active {
            background: #f39c12;
            color: white;
        }
        .document-attachment {
            margin-top: 10px;
            padding: 8px;
            background: #e8f0f5;
            border-radius: 10px;
            font-size: 0.75rem;
        }
        .view-doc-btn {
            background: #2c7da0;
            color: white;
            border: none;
            padding: 3px 10px;
            border-radius: 15px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-coins"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="financeName">Mr. James Peter</div>
            <div class="user-role">💰 Finance Office</div>
            <div class="user-id" id="financeId">FIN/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="finance.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
             <a href="fin_students.php" class="nav-item"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Finance Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS CARDS (Payment-related removed) -->
        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-number" id="pendingQueries">0</div>
                <div>Pending Queries</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner"></i>
                <div class="stat-number" id="progressQueries">0</div>
                <div>In Progress</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number" id="resolvedQueries">0</div>
                <div>Resolved</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <div class="stat-number" id="responseRate">0%</div>
                <div>Response Rate</div>
            </div>
        </div>

        <!-- Student Queries Section (Main focus) -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Student Finance Queries</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div class="ticket-tabs">
                <button class="ticket-tab active" data-filter="all">All Queries</button>
                <button class="ticket-tab" data-filter="Pending">Pending</button>
                <button class="ticket-tab" data-filter="In Progress">In Progress</button>
                <button class="ticket-tab" data-filter="Resolved">Resolved</button>
            </div>
            <div id="studentQueriesList"></div>
        </div>

        <!-- Quick Stats - Most Common Issues -->
        <div class="widget-card">
            <div class="flex-between"><strong>📊 Common Student Issues</strong></div>
            <div id="commonIssues"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR RESPONDING TO STUDENT QUERY -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Respond to Student Query</h3><span class="close-modal">&times;</span></div>
        <form id="respondForm">
            <div class="form-group"><label>Query ID: <span id="queryIdDisplay"></span></label></div>
            <div class="form-group"><label>Student: <span id="studentNameDisplay"></span></label></div>
            <div class="form-group"><label>Registration: <span id="studentRegDisplay"></span></label></div>
            <div class="form-group"><label>Original Query:</label><div id="originalQueryDisplay" style="background:#f8fafc; padding:10px; border-radius:12px; font-size:0.85rem; max-height:150px; overflow-y:auto;"></div></div>
            <div class="form-group" id="documentDisplay" style="display:none;"></div>
            <div class="form-group"><label>Response Message</label><textarea id="responseMsg" rows="4" placeholder="Write your response..."></textarea></div>
            <div class="form-group"><label>Update Status</label>
                <select id="responseStatus">
                    <option value="In Progress">In Progress (Being reviewed)</option>
                    <option value="Resolved">Resolved (Payment confirmed / Issue fixed)</option>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Send Response</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Set current date
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', options);
    
    // Load logged in user
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('financeName').innerText = loggedUser.name || 'Mr. James Peter';
    document.getElementById('financeId').innerText = loggedUser.regNo || 'FIN/2024/001';
    
    // Student queries (finance-related only)
    let studentQueries = JSON.parse(localStorage.getItem('student_finance_queries') || '[]');
    let currentFilter = 'all';
    let currentQueryId = null;
    
    // Demo data if empty
    if (studentQueries.length === 0) {
        studentQueries = [
            { id: 2001, studentName: 'John Student', studentReg: 'IAA/2024/0789', title: 'Fee payment not reflected - Need access to exams', category: 'Fee-related query', department: 'Finance Office', priority: 'High', description: 'I paid my tuition fee of TSh 850,000 on May 28th but the system still shows pending balance. Attached payment receipt. I need access to my upcoming exams.', status: 'Pending', date: '2024-06-03', hasDocument: true, documentName: 'payment_receipt.jpg' },
            { id: 2002, studentName: 'Mary Student', studentReg: 'IAA/2024/0456', title: 'Library fee payment verification', category: 'Fee-related query', department: 'Finance Office', priority: 'Medium', description: 'I paid library fee of TSh 350,000 but library system says I haven\'t paid. Attached receipt.', status: 'In Progress', date: '2024-06-02', hasDocument: true, documentName: 'library_receipt.png' },
            { id: 2003, studentName: 'Peter Student', studentReg: 'IAA/2024/0890', title: 'Scholarship balance inquiry', category: 'Fee-related query', department: 'Finance Office', priority: 'Low', description: 'I have a scholarship that covers 50% of tuition. Please confirm my remaining balance.', status: 'Resolved', date: '2024-05-30', hasDocument: false }
        ];
        localStorage.setItem('student_finance_queries', JSON.stringify(studentQueries));
    }
    
    function saveStudentQueries() { localStorage.setItem('student_finance_queries', JSON.stringify(studentQueries)); }
    
    function updateStats() {
        const pending = studentQueries.filter(q => q.status === 'Pending').length;
        const inProgress = studentQueries.filter(q => q.status === 'In Progress').length;
        const resolved = studentQueries.filter(q => q.status === 'Resolved').length;
        const total = studentQueries.length;
        const responseRate = total === 0 ? 0 : Math.round((resolved / total) * 100);
        
        document.getElementById('pendingQueries').innerText = pending;
        document.getElementById('progressQueries').innerText = inProgress;
        document.getElementById('resolvedQueries').innerText = resolved;
        document.getElementById('responseRate').innerText = responseRate + '%';
    }
    
    function viewDocument(documentData, documentName) {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10000; display:flex; align-items:center; justify-content:center;';
        const content = document.createElement('div');
        content.style.cssText = 'max-width:90%; max-height:90%; background:white; border-radius:16px; padding:20px; position:relative;';
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '✕ Close';
        closeBtn.style.cssText = 'position:absolute; top:10px; right:20px; background:#c0392b; color:white; border:none; padding:8px 16px; border-radius:30px; cursor:pointer;';
        closeBtn.onclick = () => document.body.removeChild(modal);
        
        if (documentData && documentData.startsWith('data:image')) {
            const img = document.createElement('img');
            img.src = documentData;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '80vh';
            content.appendChild(img);
        } else {
            const msg = document.createElement('p');
            msg.innerHTML = `<i class="fas fa-file"></i> Document: ${documentName}`;
            content.appendChild(msg);
        }
        
        content.appendChild(closeBtn);
        modal.appendChild(content);
        document.body.appendChild(modal);
    }
    
    function renderStudentQueries() {
        let filtered = studentQueries;
        if (currentFilter !== 'all') {
            filtered = studentQueries.filter(q => q.status === currentFilter);
        }
        
        const container = document.getElementById('studentQueriesList');
        if (filtered.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:40px;">No student queries found</div>';
            return;
        }
        
        container.innerHTML = filtered.map(q => `
            <div class="query-item">
                <div class="flex-between" style="margin-bottom:5px;">
                    <div class="query-title">#${q.id} - ${escapeHtml(q.title)}</div>
                    <span class="status-badge ${q.status === 'Resolved' ? 'status-resolved' : q.status === 'In Progress' ? 'status-progress' : 'status-pending'}">${q.status}</span>
                </div>
                <div class="query-meta">
                    <i class="fas fa-user"></i> ${q.studentName} (${q.studentReg}) | 
                    <i class="fas fa-calendar"></i> ${q.date} | 
                    🔥 ${q.priority}
                </div>
                <div class="query-description">${escapeHtml(q.description.substring(0, 120))}${q.description.length > 120 ? '...' : ''}</div>
                ${q.hasDocument ? `<div class="document-attachment"><i class="fas fa-paperclip"></i> Receipt attached: ${q.documentName} <button class="view-doc-btn" data-doc="${q.documentData || ''}" data-name="${q.documentName}">View Receipt</button></div>` : ''}
                ${q.status !== 'Resolved' ? `<button class="respond-btn" data-id="${q.id}" data-name="${q.studentName}" data-reg="${q.studentReg}" data-desc="${escapeHtml(q.description)}" data-doc="${q.documentData || ''}" data-docname="${q.documentName || ''}">📝 Respond to Query</button>` : ''}
            </div>
        `).join('');
        
        // View document buttons
        document.querySelectorAll('.view-doc-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const docData = btn.getAttribute('data-doc');
                const docName = btn.getAttribute('data-name');
                viewDocument(docData, docName);
            });
        });
        
        // Respond buttons
        document.querySelectorAll('.respond-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentQueryId = parseInt(btn.dataset.id);
                document.getElementById('queryIdDisplay').innerText = currentQueryId;
                document.getElementById('studentNameDisplay').innerText = btn.dataset.name;
                document.getElementById('studentRegDisplay').innerText = btn.dataset.reg;
                document.getElementById('originalQueryDisplay').innerHTML = btn.dataset.desc;
                
                const hasDoc = btn.getAttribute('data-doc') && btn.getAttribute('data-doc') !== '';
                const docDisplay = document.getElementById('documentDisplay');
                if (hasDoc) {
                    docDisplay.style.display = 'block';
                    docDisplay.innerHTML = `<label>Attached Receipt:</label><div><a href="#" onclick="viewDocument('${btn.dataset.doc}', '${btn.dataset.docname}'); return false;"><i class="fas fa-receipt"></i> View Receipt</a></div>`;
                } else {
                    docDisplay.style.display = 'none';
                }
                
                document.getElementById('respondModal').style.display = 'flex';
            });
        });
    }
    
    function respondToQuery(queryId, response, newStatus) {
        const query = studentQueries.find(q => q.id === queryId);
        if (query) {
            query.status = newStatus;
            query.response = response;
            query.responseDate = new Date().toLocaleDateString('en-US');
            saveStudentQueries();
            renderStudentQueries();
            updateStats();
            alert(`✅ Response sent to student! Query status updated to ${newStatus}.`);
        }
    }
    
    // Common issues stats
    function renderCommonIssues() {
        const issues = {
            'Fee payment not reflected': studentQueries.filter(q => q.title.includes('payment')).length,
            'Library fee issues': studentQueries.filter(q => q.title.includes('Library')).length,
            'Scholarship inquiries': studentQueries.filter(q => q.title.includes('Scholarship')).length,
            'Balance confirmation': studentQueries.filter(q => q.title.includes('balance')).length
        };
        const container = document.getElementById('commonIssues');
        container.innerHTML = `<div class="stats-row">${Object.entries(issues).map(([issue, count]) => `<div class="stat-card"><div class="stat-number">${count}</div><div style="font-size:0.75rem;">${issue}</div></div>`).join('')}</div>`;
    }
    
    // Tab switching
    document.querySelectorAll('.ticket-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.ticket-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.dataset.filter;
            renderStudentQueries();
        });
    });
    
    // Modal handlers
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('respondModal').style.display = 'none');
    });
    
    document.getElementById('respondForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const response = document.getElementById('responseMsg').value;
        const status = document.getElementById('responseStatus').value;
        if (!response) { alert('Please write a response'); return; }
        respondToQuery(currentQueryId, response, status);
        document.getElementById('respondModal').style.display = 'none';
        document.getElementById('responseMsg').value = '';
    });
    
    document.getElementById('refreshBtn').addEventListener('click', () => renderStudentQueries());
    
    // Initialize
    updateStats();
    renderStudentQueries();
    renderCommonIssues();
    
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sessionStorage.clear();
        window.location.href = 'login.html';
    });
</script>
</body>
</html>