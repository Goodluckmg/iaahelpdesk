<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | System Status</title>
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
            <h1 class="page-title">System Status Monitoring</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row" id="systemStatsRow">
            <div class="stat-card"><i class="fas fa-check-circle" style="color:#27ae60;"></i><div class="stat-number" id="onlineCount">0</div><div>Systems Online</div></div>
            <div class="stat-card"><i class="fas fa-times-circle" style="color:#c0392b;"></i><div class="stat-number" id="offlineCount">0</div><div>Systems Offline</div></div>
            <div class="stat-card"><i class="fas fa-tools" style="color:#e67e22;"></i><div class="stat-number" id="maintenanceCount">0</div><div>Maintenance</div></div>
            <div class="stat-card"><i class="fas fa-percent"></i><div class="stat-number" id="uptimeAvg">0%</div><div>Avg Uptime</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>🖧 System Status Dashboard</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div id="systemsList"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR ADD/EDIT SYSTEM -->
<div id="systemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">Add New System</h3><span class="close-modal">&times;</span></div>
        <form id="systemForm">
            <div class="form-group"><label>System Name</label><input type="text" id="systemName" required></div>
            <div class="form-group"><label>System Type</label><input type="text" id="systemType" placeholder="e.g., Web App, Database"></div>
            <div class="form-group"><label>Status</label><select id="systemStatus"><option value="Online">Online</option><option value="Offline">Offline</option><option value="Maintenance">Maintenance</option></select></div>
            <div class="form-group"><label>Uptime (%)</label><input type="text" id="systemUptime" value="99.9"></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Save System</button></div>
        </form>
    </div>
</div>

<script src="ict.js"></script>
<script>
    let systems = [];
    let currentEditId = null;

    function loadData() {
        systems = loadSystems();
        updateStats();
        renderSystems();
    }

    function updateStats() {
        const stats = getSystemStats(systems);
        const avgUptime = systems.length === 0 ? 0 : Math.round(systems.reduce((sum, s) => sum + parseFloat(s.uptime || 99.9), 0) / systems.length);
        document.getElementById('onlineCount').innerText = stats.online;
        document.getElementById('offlineCount').innerText = stats.offline;
        document.getElementById('maintenanceCount').innerText = stats.maintenance;
        document.getElementById('uptimeAvg').innerText = avgUptime + '%';
    }

    function renderSystems() {
        const container = document.getElementById('systemsList');
        
        if (systems.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">No systems registered. Click "Add System" to start.</div>';
            return;
        }
        
        container.innerHTML = systems.map(s => `
            <div class="ticket-item" style="border-left-color: ${s.status === 'Online' ? '#27ae60' : s.status === 'Offline' ? '#c0392b' : '#e67e22'};">
                <div class="flex-between">
                    <div class="ticket-title"><i class="fas fa-server"></i> ${s.name}</div>
                    <span class="status-badge ${s.status === 'Online' ? 'status-resolved' : s.status === 'Offline' ? 'status-urgent' : 'status-progress'}">${s.status}</span>
                </div>
                <div class="ticket-meta">
                    <i class="fas fa-tag"></i> ${s.type} | 
                    <i class="fas fa-chart-line"></i> Uptime: ${s.uptime || '99.9'}% | 
                    <i class="fas fa-calendar"></i> Last Check: ${s.lastCheck}
                </div>
                <div style="margin-top: 10px;">
                    <button class="btn-primary edit-system" data-id="${s.id}" style="padding:4px 12px;"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn-danger delete-system" data-id="${s.id}" style="padding:4px 12px;"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>
        `).join('');
        
        document.querySelectorAll('.edit-system').forEach(btn => {
            btn.addEventListener('click', () => editSystem(parseInt(btn.dataset.id)));
        });
        document.querySelectorAll('.delete-system').forEach(btn => {
            btn.addEventListener('click', () => deleteSystem(parseInt(btn.dataset.id)));
        });
    }

    function addSystem(systemData) {
        const newId = systems.length > 0 ? Math.max(...systems.map(s => s.id)) + 1 : 1;
        const newSystem = { id: newId, ...systemData, lastCheck: new Date().toLocaleDateString('en-US') };
        systems.push(newSystem);
        saveSystems(systems);
        loadData();
        alert('System added successfully!');
    }

    function editSystem(id) {
        const system = systems.find(s => s.id === id);
        if (system) {
            currentEditId = id;
            document.getElementById('modalTitle').innerText = 'Edit System';
            document.getElementById('systemName').value = system.name;
            document.getElementById('systemType').value = system.type;
            document.getElementById('systemStatus').value = system.status;
            document.getElementById('systemUptime').value = system.uptime || '99.9';
            document.getElementById('systemModal').style.display = 'flex';
        }
    }

    function updateSystem(id, systemData) {
        const index = systems.findIndex(s => s.id === id);
        if (index !== -1) {
            systems[index] = { ...systems[index], ...systemData, lastCheck: new Date().toLocaleDateString('en-US') };
            saveSystems(systems);
            loadData();
            alert('System updated successfully!');
        }
    }

    function deleteSystem(id) {
        if (confirm('Are you sure you want to delete this system?')) {
            systems = systems.filter(s => s.id !== id);
            saveSystems(systems);
            loadData();
            alert('System deleted successfully!');
        }
    }

    // Modal handlers
    document.getElementById('addSystemBtn')?.addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('modalTitle').innerText = 'Add New System';
        document.getElementById('systemForm').reset();
        document.getElementById('systemModal').style.display = 'flex';
    });

    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('systemModal').style.display = 'none');
    });

    document.getElementById('systemForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const systemData = {
            name: document.getElementById('systemName').value,
            type: document.getElementById('systemType').value,
            status: document.getElementById('systemStatus').value,
            uptime: document.getElementById('systemUptime').value
        };
        
        if (currentEditId) {
            updateSystem(currentEditId, systemData);
        } else {
            addSystem(systemData);
        }
        document.getElementById('systemModal').style.display = 'none';
    });

    document.getElementById('refreshBtn').addEventListener('click', loadData);
    
    // Add button to top
    const addBtn = document.createElement('button');
    addBtn.className = 'btn-primary';
    addBtn.id = 'addSystemBtn';
    addBtn.innerHTML = '<i class="fas fa-plus"></i> Add System';
    document.querySelector('.flex-between').appendChild(addBtn);
    addBtn.addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('modalTitle').innerText = 'Add New System';
        document.getElementById('systemForm').reset();
        document.getElementById('systemModal').style.display = 'flex';
    });

    loadData();
</script>
</body>
</html>