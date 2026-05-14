<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Maintenance Schedule</title>
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
            <a href="ict_ystems.php" class="nav-item" data-view="systems"><i class="fas fa-server"></i><span class="nav-label">System Status</span></a>
            <a href="ict_maintenance.php" class="nav-item" data-view="maintenance"><i class="fas fa-tools"></i><span class="nav-label">Maintenance</span></a>
            <a href="ict_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Scheduled Maintenance</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-calendar"></i><div class="stat-number" id="upcomingCount">0</div><div>Upcoming</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="completedCount">0</div><div>Completed</div></div>
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="inProgressCount">0</div><div>In Progress</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>🔧 Maintenance Schedule</strong>
                <button class="btn-primary" id="addMaintenanceBtn"><i class="fas fa-plus"></i> Schedule Maintenance</button>
            </div>
            <div id="maintenanceList"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR ADD/EDIT MAINTENANCE -->
<div id="maintenanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">Schedule Maintenance</h3><span class="close-modal">&times;</span></div>
        <form id="maintenanceForm">
            <div class="form-group"><label>Title</label><input type="text" id="mainTitle" required></div>
            <div class="form-group"><label>System Affected</label><input type="text" id="mainSystem" required></div>
            <div class="form-group"><label>Date</label><input type="date" id="mainDate" required></div>
            <div class="form-group"><label>Start Time</label><input type="time" id="startTime" required></div>
            <div class="form-group"><label>End Time</label><input type="time" id="endTime" required></div>
            <div class="form-group"><label>Status</label><select id="mainStatus"><option value="Scheduled">Scheduled</option><option value="In Progress">In Progress</option><option value="Completed">Completed</option></select></div>
            <div class="form-group"><label>Description</label><textarea id="mainDesc" rows="3"></textarea></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Save Maintenance</button></div>
        </form>
    </div>
</div>

<script src="ict.js"></script>
<script>
    let maintenance = [];
    let currentEditId = null;

    function loadData() {
        maintenance = loadMaintenance();
        updateStats();
        renderMaintenance();
    }

    function updateStats() {
        const upcoming = maintenance.filter(m => m.status === 'Scheduled').length;
        const inProgress = maintenance.filter(m => m.status === 'In Progress').length;
        const completed = maintenance.filter(m => m.status === 'Completed').length;
        document.getElementById('upcomingCount').innerText = upcoming;
        document.getElementById('inProgressCount').innerText = inProgress;
        document.getElementById('completedCount').innerText = completed;
    }

    function renderMaintenance() {
        const container = document.getElementById('maintenanceList');
        
        if (maintenance.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">No maintenance scheduled.</div>';
            return;
        }
        
        container.innerHTML = maintenance.map(m => `
            <div class="ticket-item" style="border-left-color: ${m.status === 'Scheduled' ? '#3498db' : m.status === 'In Progress' ? '#e67e22' : '#27ae60'};">
                <div class="flex-between">
                    <div class="ticket-title"><i class="fas fa-tools"></i> ${m.title}</div>
                    <span class="status-badge ${m.status === 'Completed' ? 'status-resolved' : m.status === 'In Progress' ? 'status-progress' : ''}">${m.status}</span>
                </div>
                <div class="ticket-meta">
                    <i class="fas fa-server"></i> System: ${m.system} | 
                    <i class="fas fa-calendar"></i> Date: ${m.date} | 
                    <i class="fas fa-clock"></i> Time: ${m.startTime} - ${m.endTime}
                </div>
                <div class="ticket-description"><strong>Description:</strong> ${m.description}</div>
                <div style="margin-top: 10px;">
                    <button class="btn-primary edit-maintenance" data-id="${m.id}" style="padding:4px 12px;"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn-danger delete-maintenance" data-id="${m.id}" style="padding:4px 12px;"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>
        `).join('');
        
        document.querySelectorAll('.edit-maintenance').forEach(btn => {
            btn.addEventListener('click', () => editMaintenance(parseInt(btn.dataset.id)));
        });
        document.querySelectorAll('.delete-maintenance').forEach(btn => {
            btn.addEventListener('click', () => deleteMaintenance(parseInt(btn.dataset.id)));
        });
    }

    function addMaintenance(mainData) {
        const newId = maintenance.length > 0 ? Math.max(...maintenance.map(m => m.id)) + 1 : 1;
        const newMain = { id: newId, ...mainData };
        maintenance.push(newMain);
        saveMaintenance(maintenance);
        loadData();
        alert('Maintenance scheduled successfully!');
    }

    function editMaintenance(id) {
        const main = maintenance.find(m => m.id === id);
        if (main) {
            currentEditId = id;
            document.getElementById('modalTitle').innerText = 'Edit Maintenance';
            document.getElementById('mainTitle').value = main.title;
            document.getElementById('mainSystem').value = main.system;
            document.getElementById('mainDate').value = main.date;
            document.getElementById('startTime').value = main.startTime;
            document.getElementById('endTime').value = main.endTime;
            document.getElementById('mainStatus').value = main.status;
            document.getElementById('mainDesc').value = main.description;
            document.getElementById('maintenanceModal').style.display = 'flex';
        }
    }

    function updateMaintenance(id, mainData) {
        const index = maintenance.findIndex(m => m.id === id);
        if (index !== -1) {
            maintenance[index] = { ...maintenance[index], ...mainData };
            saveMaintenance(maintenance);
            loadData();
            alert('Maintenance updated successfully!');
        }
    }

    function deleteMaintenance(id) {
        if (confirm('Are you sure you want to delete this maintenance record?')) {
            maintenance = maintenance.filter(m => m.id !== id);
            saveMaintenance(maintenance);
            loadData();
            alert('Maintenance deleted successfully!');
        }
    }

    // Modal handlers
    document.getElementById('addMaintenanceBtn').addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('modalTitle').innerText = 'Schedule Maintenance';
        document.getElementById('maintenanceForm').reset();
        document.getElementById('maintenanceModal').style.display = 'flex';
    });

    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('maintenanceModal').style.display = 'none');
    });

    document.getElementById('maintenanceForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const mainData = {
            title: document.getElementById('mainTitle').value,
            system: document.getElementById('mainSystem').value,
            date: document.getElementById('mainDate').value,
            startTime: document.getElementById('startTime').value,
            endTime: document.getElementById('endTime').value,
            status: document.getElementById('mainStatus').value,
            description: document.getElementById('mainDesc').value
        };
        
        if (currentEditId) {
            updateMaintenance(currentEditId, mainData);
        } else {
            addMaintenance(mainData);
        }
        document.getElementById('maintenanceModal').style.display = 'none';
    });

    loadData();
</script>
</body>
</html>