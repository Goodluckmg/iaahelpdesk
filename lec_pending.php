<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Pending Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/lecturers.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-chalkboard-user"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="lecturerName">Dr. Sarah Lecturer</div>
            <div class="user-role">📚 Lecturer</div>
            <div class="user-id" id="lecturerId">STAFF/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="lecturers.php" class="nav-item" data-view="dashboard"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="lec_pending.php" class="nav-item" data-view="pending"><i class="fas fa-clock"></i><span class="nav-label">Pending Requests</span></a>
            <a href="lec_resolved.php" class="nav-item" data-view="resolved"><i class="fas fa-check-circle"></i><span class="nav-label">Resolved</span></a>
            <a href="lec_courses.php" class="nav-item" data-view="courses"><i class="fas fa-book"></i><span class="nav-label">My Courses</span></a>
            <a href="lec_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Pending Requests</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row" id="pendingStatsRow">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="pendingCount">0</div><div>Awaiting Response</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number" id="progressCount">0</div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-hourglass-half"></i><div class="stat-number" id="totalPending">0</div><div>Total Pending</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>⏳ Requests Awaiting Your Response</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div id="pendingList"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR RESPONDING -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Respond to Student Request</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="respondForm">
            <div class="form-group"><label>Request ID: <span id="requestIdDisplay"></span></label></div>
            <div class="form-group"><label>Response Message</label><textarea id="responseMsg" rows="4"></textarea></div>
            <div class="form-group"><label>Update Status</label><select id="responseStatus"><option value="In Progress">In Progress</option><option value="Resolved">Resolved</option></select></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Submit Response</button></div>
        </form>
    </div>
</div>

<script src="lecturer.js"></script>
<script>
    let requests = [];
    let currentRequestId = null;

    function loadData() {
        requests = loadRequests();
        updateStats();
        renderPendingList();
    }

    function updateStats() {
        const pending = requests.filter(r => r.status === 'Pending').length;
        const inProgress = requests.filter(r => r.status === 'In Progress').length;
        document.getElementById('pendingCount').innerText = pending;
        document.getElementById('progressCount').innerText = inProgress;
        document.getElementById('totalPending').innerText = pending + inProgress;
    }

    function renderPendingList() {
        const pending = requests.filter(r => r.status === 'Pending');
        const inProgress = requests.filter(r => r.status === 'In Progress');
        const allPending = [...pending, ...inProgress];
        
        const container = document.getElementById('pendingList');
        
        if (allPending.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">🎉 No pending requests! All caught up.</div>';
            return;
        }
        
        container.innerHTML = allPending.map(r => `
            <div class="request-item">
                <div class="request-title">#${r.id} - ${r.subject}</div>
                <div class="request-meta">
                    <i class="fas fa-user"></i> ${r.studentName} (${r.studentReg}) | 
                    <i class="fas fa-book"></i> ${r.course} | 
                    <i class="fas fa-calendar"></i> ${r.date} |
                    <span class="status-badge ${r.priority === 'High' ? 'status-pending' : ''}">${r.priority}</span> |
                    <span class="status-badge ${r.status === 'In Progress' ? 'status-progress' : 'status-pending'}">${r.status}</span>
                </div>
                <div class="request-description"><strong>Description:</strong> ${r.description}</div>
                <button class="btn-primary respond-btn" data-id="${r.id}" style="margin-top:10px;"><i class="fas fa-reply"></i> Respond to Request</button>
            </div>
        `).join('');
        
        document.querySelectorAll('.respond-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentRequestId = parseInt(btn.dataset.id);
                document.getElementById('requestIdDisplay').innerText = currentRequestId;
                document.getElementById('respondModal').style.display = 'flex';
            });
        });
    }

    function respondToRequest(requestId, response, newStatus) {
        const request = requests.find(r => r.id === requestId);
        if (request) {
            request.status = newStatus;
            request.response = response;
            request.responseDate = new Date().toLocaleDateString('en-US');
            saveRequests(requests);
            loadData();
            alert(`✅ Response sent! Request #${requestId} updated to ${newStatus}.`);
        }
    }

    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('respondModal').style.display = 'none');
    });

    document.getElementById('respondForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const response = document.getElementById('responseMsg').value;
        const status = document.getElementById('responseStatus').value;
        if (!response) { alert('Please write a response'); return; }
        respondToRequest(currentRequestId, response, status);
        document.getElementById('respondModal').style.display = 'none';
        document.getElementById('responseMsg').value = '';
    });

    document.getElementById('refreshBtn').addEventListener('click', loadData);
    loadData();
</script>
</body>
</html>