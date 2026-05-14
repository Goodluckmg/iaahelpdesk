<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Support Tickets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/ict.css">
</head>
<body>
<div class="app-container">
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
            <a href="ict_maintenance.php" class="nav-item" data-view="maintenance"><i class="fas fa-tools"></i><span class="nav-label">Maintenance</span></a>
            <a href="ict_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Support Tickets Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="openCount">0</div><div>Open</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number" id="progressCount">0</div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="resolvedCount">0</div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-ticket-alt"></i><div class="stat-number" id="totalCount">0</div><div>Total Tickets</div></div>
        </div>

        <!-- FILTERS -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>🎫 All Support Tickets</strong>
                <div>
                    <select id="filterStatus" style="width:140px; margin-right:10px;">
                        <option value="all">All Tickets</option>
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                    <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>
            <div id="allTicketsList"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR UPDATING TICKET -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Update Ticket</h3><span class="close-modal">&times;</span></div>
        <form id="updateForm">
            <div class="form-group"><label>Ticket ID: <span id="ticketIdDisplay"></span></label></div>
            <div class="form-group"><label>Resolution Notes</label><textarea id="resolutionMsg" rows="4"></textarea></div>
            <div class="form-group"><label>Status</label><select id="updateStatus"><option value="In Progress">In Progress</option><option value="Resolved">Resolved</option></select></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Update Ticket</button></div>
        </form>
    </div>
</div>

<script src="ict.js"></script>
<script>
    let tickets = [];
    let currentTicketId = null;

    function loadData() {
        tickets = loadTickets();
        updateStats();
        renderAllTickets(document.getElementById('filterStatus').value);
    }

    function updateStats() {
        const stats = getTicketStats(tickets);
        document.getElementById('openCount').innerText = stats.open;
        document.getElementById('progressCount').innerText = stats.inProgress;
        document.getElementById('resolvedCount').innerText = stats.resolved;
        document.getElementById('totalCount').innerText = stats.total;
    }

    function renderAllTickets(filter) {
        let filtered = filter === 'all' ? tickets : tickets.filter(t => t.status === filter);
        const container = document.getElementById('allTicketsList');
        
        if (filtered.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">No tickets found</div>';
            return;
        }
        
        container.innerHTML = filtered.map(t => `
            <div class="ticket-item">
                <div class="ticket-title">#${t.id} - ${t.issue}</div>
                <div class="ticket-meta">
                    <i class="fas fa-user"></i> ${t.studentName} (${t.studentReg}) | 
                    <i class="fas fa-tag"></i> ${t.category} | 
                    <i class="fas fa-calendar"></i> ${t.date} |
                    <span class="status-badge ${t.priority === 'High' ? 'status-urgent' : ''}">${t.priority}</span> |
                    <span class="status-badge ${t.status === 'Resolved' ? 'status-resolved' : t.status === 'In Progress' ? 'status-progress' : ''}">${t.status}</span>
                </div>
                <div class="ticket-description"><strong>Description:</strong> ${t.description}</div>
                ${t.resolution ? `<div class="ticket-description" style="background:#d9f0e5; padding:10px; border-radius:10px; margin-top:10px;"><strong>📝 Resolution Notes:</strong> ${t.resolution}</div>` : ''}
                ${t.status !== 'Resolved' ? `<button class="btn-primary update-ticket" data-id="${t.id}" style="margin-top:10px;"><i class="fas fa-edit"></i> Update Ticket</button>` : ''}
            </div>
        `).join('');
        
        document.querySelectorAll('.update-ticket').forEach(btn => {
            btn.addEventListener('click', () => {
                currentTicketId = parseInt(btn.dataset.id);
                document.getElementById('ticketIdDisplay').innerText = currentTicketId;
                document.getElementById('updateModal').style.display = 'flex';
            });
        });
    }

    function updateTicket(ticketId, resolution, newStatus) {
        const ticket = tickets.find(t => t.id === ticketId);
        if (ticket) {
            ticket.status = newStatus;
            ticket.resolution = resolution;
            ticket.resolvedDate = newStatus === 'Resolved' ? new Date().toLocaleDateString('en-US') : ticket.resolvedDate;
            saveTickets(tickets);
            loadData();
            alert(`✅ Ticket #${ticketId} updated to ${newStatus}.`);
        }
    }

    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('updateModal').style.display = 'none');
    });

    document.getElementById('updateForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const resolution = document.getElementById('resolutionMsg').value;
        const status = document.getElementById('updateStatus').value;
        if (!resolution) { alert('Please enter resolution notes'); return; }
        updateTicket(currentTicketId, resolution, status);
        document.getElementById('updateModal').style.display = 'none';
        document.getElementById('resolutionMsg').value = '';
    });

    document.getElementById('refreshBtn').addEventListener('click', loadData);
    document.getElementById('filterStatus').addEventListener('change', () => renderAllTickets(document.getElementById('filterStatus').value));

    loadData();
</script>
</body>
</html>