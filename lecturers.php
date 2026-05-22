<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Lecturer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/lecturers.css">
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-chalkboard-user"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="lecturerName">Dr. Sarah Lecturer</div>
            <div class="user-role">📚 Lecturer</div>
            <div class="user-id" id="lecturerId">STAFF/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="lecturers.php" class="nav-item active" data-view="dashboard"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="lec_pending.php" class="nav-item" data-view="pending"><i class="fas fa-clock"></i><span class="nav-label">Pending Requests</span></a>
            <a href="lec_resolved.php" class="nav-item" data-view="resolved"><i class="fas fa-check-circle"></i><span class="nav-label">Resolved</span></a>
            <a href="lec_courses.php" class="nav-item" data-view="courses"><i class="fas fa-book"></i><span class="nav-label">My Courses</span></a>
            <a href="lec_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Lecturer Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row" id="statsRow">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="pendingCount">0</div><div>Pending Requests</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number" id="progressCount">0</div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="resolvedCount">0</div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-users"></i><div class="stat-number" id="totalCount">0</div><div>Total Requests</div></div>
        </div>

        <!-- RECENT REQUESTS -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Recent Student Requests</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <table id="requestsTable">
                <thead>
                    <tr><th>ID</th><th>Student Name</th><th>Student Reg</th><th>Subject</th><th>Course</th><th>Priority</th><th>Status</th><th>Action</th>
                </thead>
                <tbody id="requestsBody"></tbody>
            </table>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div class="widget-card">
            <div class="flex-between"><strong>📢 Department Announcements</strong></div>
            <p>✅ Department meeting on Friday at 10:00 AM in Conference Room<br>📝 Exam scripts submission deadline: 20th June 2024<br>🆕 New course materials uploaded to portal</p>
        </div>
    </main>
</div>

<!-- MODAL FOR RESPONDING TO REQUEST -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Respond to Student Request</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="respondForm">
            <div class="form-group">
                <label>Request ID: <span id="requestIdDisplay"></span></label>
            </div>
            <div class="form-group">
                <label>Response Message</label>
                <textarea id="responseMsg" rows="4" placeholder="Write your response here..."></textarea>
            </div>
            <div class="form-group">
                <label>Update Status</label>
                <select id="responseStatus">
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Submit Response</button>
            </div>
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
        renderRecentRequests();
    }

    function updateStats() {
        const stats = getStats(requests);
        document.getElementById('pendingCount').innerText = stats.pending;
        document.getElementById('progressCount').innerText = stats.inProgress;
        document.getElementById('resolvedCount').innerText = stats.resolved;
        document.getElementById('totalCount').innerText = stats.total;
    }

    function renderRecentRequests() {
        const recent = [...requests].reverse().slice(0, 10);
        const tbody = document.getElementById('requestsBody');
        
        if (recent.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8">No requests found</td>*⁠.html';
            return;
        }
        
        tbody.innerHTML = recent.map(r => `
            <tr>
                <td>#${r.id}</td>
                <td>${r.studentName}</td>
                <td>${r.studentReg}</td>
                <td>${r.subject.substring(0, 35)}${r.subject.length > 35 ? '...' : ''}</td>
                <td>${r.course}</td>
                <td><span class="status-badge ${r.priority === 'High' ? 'status-pending' : ''}">${r.priority}</span></td>
                <td><span class="status-badge ${r.status === 'Resolved' ? 'status-resolved' : r.status === 'In Progress' ? 'status-progress' : 'status-pending'}">${r.status}</span></td>
                <td>${r.status !== 'Resolved' ? `<button class="btn-primary respond-btn" data-id="${r.id}" style="padding:4px 12px;"><i class="fas fa-reply"></i> Respond</button>` : '<span class="status-badge status-resolved">✓ Completed</span>'}</td>
            </tr>
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
            alert(`✅ Response sent to student! Request #${requestId} status updated to ${newStatus}.`);
        }
    }

    // Modal handlers
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => {
            document.getElementById('respondModal').style.display = 'none';
            document.getElementById('responseMsg').value = '';
        });
    });

    document.getElementById('respondForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const response = document.getElementById('responseMsg').value;
        const status = document.getElementById('responseStatus').value;
        
        if (!response) {
            alert('Please write a response message');
            return;
        }
        
        respondToRequest(currentRequestId, response, status);
        document.getElementById('respondModal').style.display = 'none';
        document.getElementById('responseMsg').value = '';
    });

    document.getElementById('refreshBtn').addEventListener('click', () => {
        loadData();
    });

    // Initialize
    loadData();
</script>
</body>
</html>