<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | ICT Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/ict.css">
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-laptop-code"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="ictName">Ms. Anna Kaiza</div>
            <div class="user-role">💻 ICT Support</div>
            <div class="user-id" id="ictId">ICT/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="ict.php" class="nav-item" data-view="dashboard"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="ict_tickets.php" class="nav-item" data-view="tickets"><i class="fas fa-ticket-alt"></i><span class="nav-label">Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item" data-view="systems"><i class="fas fa-server"></i><span class="nav-label">System Status</span></a>
            <a href="edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="ict_maintenance.php" class="nav-item" data-view="maintenance"><i class="fas fa-tools"></i><span class="nav-label">Maintenance</span></a>
            <a href="reports.html" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">ICT Support Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row" id="statsRow">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="openTickets">0</div><div>Open Tickets</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number" id="progressTickets">0</div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="resolvedTickets">0</div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div class="stat-number" id="urgentTickets">0</div><div>High Priority</div></div>
            <div class="stat-card"><i class="fas fa-server"></i><div class="stat-number" id="systemsOnline">0</div><div>Systems Online</div></div>
            <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number" id="resolutionRate">0%</div><div>Resolution Rate</div></div>
        </div>

        <!-- RECENT TICKETS -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>🖥️ Recent Support Tickets</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <table id="ticketsTable">
                <thead>
                    <tr><th>ID</th><th>Student/Staff</th><th>Issue</th><th>Category</th><th>Priority</th><th>Status</th><th>Action</th>
                </thead>
                <tbody id="ticketsBody"></tbody>
            </table>
        </div>

        <!-- SYSTEM STATUS OVERVIEW -->
        <div class="widget-card">
            <div class="flex-between"><strong>🖧 System Status Overview</strong><a href="systems.html" class="btn-primary">View All Systems</a></div>
            <div id="systemOverview"></div>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div class="widget-card">
            <div class="flex-between"><strong>📢 ICT Announcements</strong></div>
            <p>✅ Scheduled maintenance on Student Portal: June 10th, 10:00 PM - 2:00 AM<br>🆕 New software available: Microsoft Office 365 for all students<br>🔧 Network upgrade completed - improved speed in all computer labs</p>
        </div>
    </main>
</div>

<!-- MODAL FOR RESPONDING TO TICKET -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Support Ticket</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="respondForm">
            <div class="form-group"><label>Ticket ID: <span id="ticketIdDisplay"></span></label></div>
            <div class="form-group"><label>Update Message / Resolution Notes</label><textarea id="responseMsg" rows="4" placeholder="Describe the action taken..."></textarea></div>
            <div class="form-group"><label>Update Status</label><select id="responseStatus"><option value="In Progress">In Progress</option><option value="Resolved">Resolved</option></select></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Update Ticket</button></div>
        </form>
    </div>
</div>

<script src="ict.js"></script>
<script>
    let tickets = [];
    let systems = [];
    let currentTicketId = null;
    let statusChart = null;

    function loadData() {
        tickets = loadTickets();
        systems = loadSystems();
        updateStats();
        renderRecentTickets();
        renderSystemOverview();
    }

    function updateStats() {
        const stats = getTicketStats(tickets);
        const sysStats = getSystemStats(systems);
        const resolutionRate = stats.total === 0 ? 0 : Math.round((stats.resolved / stats.total) * 100);
        
        document.getElementById('openTickets').innerText = stats.open;
        document.getElementById('progressTickets').innerText = stats.inProgress;
        document.getElementById('resolvedTickets').innerText = stats.resolved;
        document.getElementById('urgentTickets').innerText = stats.urgent;
        document.getElementById('systemsOnline').innerText = sysStats.online;
        document.getElementById('resolutionRate').innerText = resolutionRate + '%';
    }

    function renderRecentTickets() {
        const recent = [...tickets].reverse().slice(0, 10);
        const tbody = document.getElementById('ticketsBody');
        
        if (recent.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7">No tickets found</td></tr>';
            return;
        }
        
        tbody.innerHTML = recent.map(t => `
            <tr>
                <td>#${t.id}</td>
                <td>${t.studentName} (<small>${t.studentReg}</small>)</td>
                <td>${t.issue.substring(0, 35)}${t.issue.length > 35 ? '...' : ''}侧
                <td>${t.category}侧
                <td><span class="status-badge ${t.priority === 'High' ? 'status-urgent' : ''}">${t.priority}</span>侧
                <td><span class="status-badge ${t.status === 'Resolved' ? 'status-resolved' : t.status === 'In Progress' ? 'status-progress' : ''}">${t.status}</span>侧
                <td>${t.status !== 'Resolved' ? `<button class="btn-primary update-ticket" data-id="${t.id}" style="padding:4px 12px;"><i class="fas fa-edit"></i> Update</button>` : '<span class="status-badge status-resolved">✓ Closed</span>'}侧
            </tr>
        `).join('');
        
        document.querySelectorAll('.update-ticket').forEach(btn => {
            btn.addEventListener('click', () => {
                currentTicketId = parseInt(btn.dataset.id);
                document.getElementById('ticketIdDisplay').innerText = currentTicketId;
                document.getElementById('respondModal').style.display = 'flex';
            });
        });
    }

    function renderSystemOverview() {
        const online = systems.filter(s => s.status === 'Online');
        const offline = systems.filter(s => s.status === 'Offline');
        const maintenance = systems.filter(s => s.status === 'Maintenance');
        
        const container = document.getElementById('systemOverview');
        container.innerHTML = `
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex:1; text-align:center;"><span style="color:#27ae60;"><i class="fas fa-circle"></i> Online</span><br><strong>${online.length}</strong> systems</div>
                <div style="flex:1; text-align:center;"><span style="color:#c0392b;"><i class="fas fa-circle"></i> Offline</span><br><strong>${offline.length}</strong> systems</div>
                <div style="flex:1; text-align:center;"><span style="color:#e67e22;"><i class="fas fa-tools"></i> Maintenance</span><br><strong>${maintenance.length}</strong> systems</div>
                <div style="flex:1; text-align:center;"><span style="color:#3498db;"><i class="fas fa-server"></i> Total</span><br><strong>${systems.length}</strong> systems</div>
            </div>
            <div style="margin-top: 15px; height: 8px; background: #e2edf2; border-radius: 10px;">
                <div style="width: ${(online.length/systems.length)*100}%; height: 8px; background: #27ae60; border-radius: 10px;"></div>
            </div>
        `;
    }

    function updateTicket(ticketId, message, newStatus) {
        const ticket = tickets.find(t => t.id === ticketId);
        if (ticket) {
            ticket.status = newStatus;
            ticket.resolution = message;
            ticket.resolvedDate = newStatus === 'Resolved' ? new Date().toLocaleDateString('en-US') : ticket.resolvedDate;
            saveTickets(tickets);
            loadData();
            alert(`✅ Ticket #${ticketId} updated to ${newStatus}.`);
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
        const message = document.getElementById('responseMsg').value;
        const status = document.getElementById('responseStatus').value;
        
        if (!message) {
            alert('Please enter update message');
            return;
        }
        
        updateTicket(currentTicketId, message, status);
        document.getElementById('respondModal').style.display = 'none';
        document.getElementById('responseMsg').value = '';
    });

    document.getElementById('refreshBtn').addEventListener('click', loadData);

    // Initialize
    loadData();
</script>
</body>
</html>