<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Admin - Startup Hub Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Additional styles for admin startup hub */
        .startup-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .idea-card, .opportunity-card {
            background: white;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 15px;
            border: 1px solid #e2edf2;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .status-badge-pending { background: #fff3e0; color: #b45f06; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; }
        .status-badge-approved { background: #d9f0e5; color: #1d6f42; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; }
        .status-badge-rejected { background: #fde8e8; color: #c0392b; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; }
        .btn-sm { padding: 5px 12px; font-size: 0.7rem; margin: 2px; }
        .form-container { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; margin-bottom: 0; }
        .startup-tabs { display: flex; gap: 5px; margin-bottom: 20px; background: white; padding: 5px; border-radius: 15px; border: 1px solid #e2edf2; }
        .tab-btn { flex: 1; padding: 10px; border: none; background: transparent; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .tab-btn.active { background: #e74c3c; color: white; }
        .tab-btn:hover:not(.active) { background: #fde8e8; }
    </style>
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
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item active"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Startup Hub Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- Statistics Cards -->
        <div class="startup-stats-row">
            <div class="stat-card"><i class="fas fa-lightbulb"></i><div class="stat-number" id="totalIdeas">0</div><div>Total Ideas</div></div>
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="pendingIdeas">0</div><div>Pending Review</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="approvedIdeas">0</div><div>Approved</div></div>
            <div class="stat-card"><i class="fas fa-briefcase"></i><div class="stat-number" id="totalOpportunities">0</div><div>Opportunities</div></div>
        </div>

        <!-- Tabs Navigation -->
        <div class="startup-tabs">
            <button class="tab-btn active" data-tab="ideas"><i class="fas fa-lightbulb"></i> Student Ideas</button>
            <button class="tab-btn" data-tab="opportunities"><i class="fas fa-briefcase"></i> Opportunities</button>
            <button class="tab-btn" data-tab="statistics"><i class="fas fa-chart-line"></i> Statistics</button>
        </div>

        <!-- TAB 1: Student Ideas Management -->
        <div id="ideasTab" class="tab-content">
            <div class="form-container">
                <h3><i class="fas fa-filter"></i> Filter Ideas</h3>
                <div class="form-row">
                    <div class="form-group"><label>Status</label><select id="filterIdeaStatus" class="form-control"><option value="all">All</option><option value="Pending Review">Pending</option><option value="Approved">Approved</option><option value="Rejected">Rejected</option></select></div>
                    <div class="form-group"><label>Category</label><select id="filterIdeaCategory" class="form-control"><option value="all">All Categories</option><option value="Teknolojia">Technology</option><option value="Kilimo">Agriculture</option><option value="Biashara">Business</option><option value="Huduma">Services</option></select></div>
                    <div class="form-group"><label>Search</label><input type="text" id="searchIdea" placeholder="Search by title or student..."></div>
                </div>
                <button class="btn-primary" id="refreshIdeasBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div id="ideasListContainer"></div>
        </div>

        <!-- TAB 2: Opportunities Management -->
        <div id="opportunitiesTab" class="tab-content" style="display: none;">
            <div class="form-container">
                <div class="flex-between"><h3><i class="fas fa-plus-circle"></i> Add New Opportunity</h3></div>
                <div class="form-row">
                    <div class="form-group"><label>Title</label><input type="text" id="oppTitle" placeholder="e.g., Digital Marketing Training"></div>
                    <div class="form-group"><label>Type</label><select id="oppType"><option value="job">Job / Short Term</option><option value="training">Training</option><option value="internship">Internship</option></select></div>
                    <div class="form-group"><label>Category</label><select id="oppCategory"><option value="job">Job</option><option value="training">Training</option><option value="internship">Internship</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Description</label><textarea id="oppDesc" rows="2" placeholder="Describe the opportunity..."></textarea></div>
                    <div class="form-group"><label>Contact Info</label><input type="text" id="oppContact" placeholder="Email or phone"></div>
                    <div class="form-group"><label>Deadline</label><input type="date" id="oppDeadline"></div>
                </div>
                <button class="btn-primary" id="addOpportunityBtn"><i class="fas fa-save"></i> Add Opportunity</button>
            </div>
            <div class="flex-between" style="margin: 15px 0;"><h3><i class="fas fa-list"></i> Current Opportunities</h3><button class="btn-primary" id="refreshOppBtn"><i class="fas fa-sync-alt"></i> Refresh</button></div>
            <div id="opportunitiesListContainer"></div>
        </div>

        <!-- TAB 3: Statistics -->
        <div id="statisticsTab" class="tab-content" style="display: none;">
            <div class="widget-card"><canvas id="ideasChart" width="400" height="200" style="max-height: 250px;"></canvas></div>
            <div class="widget-card"><canvas id="opportunitiesChart" width="400" height="200" style="max-height: 250px;"></canvas></div>
        </div>
    </main>
</div>

<!-- MODAL FOR VIEWING IDEA DETAILS -->
<div id="ideaModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Idea Details</h3><span class="close-modal">&times;</span></div>
        <div id="ideaDetails"></div>
        <div class="form-group" style="margin-top:15px;"><label>Admin Comments</label><textarea id="adminComment" rows="3" placeholder="Add feedback for the student..."></textarea></div>
        <div style="display:flex; gap:10px; margin-top:15px;">
            <button class="btn-success" id="approveIdeaBtn"><i class="fas fa-check"></i> Approve</button>
            <button class="btn-danger" id="rejectIdeaBtn"><i class="fas fa-times"></i> Reject</button>
        </div>
    </div>
</div>

<script>
    // ============================================
    // ADMIN STARTUP HUB MANAGEMENT
    // ============================================

    let currentIdeaId = null;
    let ideasChart = null, opportunitiesChart = null;

    // Demo data
    let ideas = [
        { id: 1, studentName: 'John Student', studentReg: 'IAA/2024/0789', title: 'Mobile App for Food Delivery', category: 'Teknolojia', description: 'App ya kuagiza chakula kwa students', need: 'Capital and Developer', status: 'Pending Review', submittedDate: '2024-06-01', adminComment: '' },
        { id: 2, studentName: 'Mary Student', studentReg: 'IAA/2024/0456', title: 'Organic Farming Project', category: 'Kilimo', description: 'Kulima mboga na matunda kwa kutumia greenhouse', need: 'Land and Equipment', status: 'Pending Review', submittedDate: '2024-05-28', adminComment: '' },
        { id: 3, studentName: 'Peter Student', studentReg: 'IAA/2024/0890', title: 'Online Tutoring Platform', category: 'Teknolojia', description: 'Platform ya kutoa mafunzo online kwa wanafunzi', need: 'Developers', status: 'Approved', submittedDate: '2024-05-20', adminComment: 'Good idea! We can support with mentorship.' }
    ];

    let opportunities = [
        { id: 1, title: 'Digital Marketing Training', type: 'training', category: 'training', description: 'Mafunzo ya masaa 40 kuhusu Digital Marketing', deadline: '2024-07-15', contact: 'info@iaa.ac.tz', createdAt: '2024-06-01' },
        { id: 2, title: 'Data Entry Clerk', type: 'job', category: 'job', description: 'Nafasi ya kazi ya muda (3 months)', deadline: '2024-06-20', contact: 'hr@iaa.ac.tz', createdAt: '2024-06-01' }
    ];

    let nextIdeaId = 4;
    let nextOppId = 3;

    // Load data from localStorage
    function loadData() {
        const savedIdeas = localStorage.getItem('admin_startup_ideas');
        const savedOpportunities = localStorage.getItem('admin_startup_opportunities');
        if (savedIdeas) ideas = JSON.parse(savedIdeas);
        if (savedOpportunities) opportunities = JSON.parse(savedOpportunities);
        updateStats();
        renderIdeas();
        renderOpportunities();
        renderCharts();
    }

    function saveIdeas() { localStorage.setItem('admin_startup_ideas', JSON.stringify(ideas)); }
    function saveOpportunities() { localStorage.setItem('admin_startup_opportunities', JSON.stringify(opportunities)); }

    function updateStats() {
        document.getElementById('totalIdeas').innerText = ideas.length;
        document.getElementById('pendingIdeas').innerText = ideas.filter(i => i.status === 'Pending Review').length;
        document.getElementById('approvedIdeas').innerText = ideas.filter(i => i.status === 'Approved').length;
        document.getElementById('totalOpportunities').innerText = opportunities.length;
    }

    function renderIdeas() {
        const statusFilter = document.getElementById('filterIdeaStatus').value;
        const categoryFilter = document.getElementById('filterIdeaCategory').value;
        const searchTerm = document.getElementById('searchIdea').value.toLowerCase();
        
        let filtered = ideas;
        if (statusFilter !== 'all') filtered = filtered.filter(i => i.status === statusFilter);
        if (categoryFilter !== 'all') filtered = filtered.filter(i => i.category === categoryFilter);
        if (searchTerm) filtered = filtered.filter(i => i.title.toLowerCase().includes(searchTerm) || i.studentName.toLowerCase().includes(searchTerm));
        
        const container = document.getElementById('ideasListContainer');
        if (filtered.length === 0) { container.innerHTML = '<div class="widget-card" style="text-align:center;">No ideas found</div>'; return; }
        
        container.innerHTML = filtered.map(idea => `
            <div class="idea-card">
                <div class="card-header">
                    <strong>#${idea.id} - ${idea.title}</strong>
                    <span class="${idea.status === 'Pending Review' ? 'status-badge-pending' : idea.status === 'Approved' ? 'status-badge-approved' : 'status-badge-rejected'}">${idea.status}</span>
                </div>
                <div><small><i class="fas fa-user"></i> ${idea.studentName} (${idea.studentReg}) | <i class="fas fa-tag"></i> ${idea.category} | <i class="fas fa-calendar"></i> ${idea.submittedDate}</small></div>
                <p class="card-desc" style="margin-top:10px;">${idea.description.substring(0, 100)}${idea.description.length > 100 ? '...' : ''}</p>
                <div class="card-footer"><span><i class="fas fa-tools"></i> Needs: ${idea.need}</span><button class="btn-primary btn-sm view-idea" data-id="${idea.id}"><i class="fas fa-eye"></i> View & Review</button></div>
            </div>
        `).join('');
        
        document.querySelectorAll('.view-idea').forEach(btn => btn.addEventListener('click', () => showIdeaModal(parseInt(btn.dataset.id))));
    }

    function showIdeaModal(id) {
        currentIdeaId = id;
        const idea = ideas.find(i => i.id === id);
        if (idea) {
            document.getElementById('ideaDetails').innerHTML = `
                <div class="form-group"><strong>Student:</strong> ${idea.studentName} (${idea.studentReg})</div>
                <div class="form-group"><strong>Title:</strong> ${idea.title}</div>
                <div class="form-group"><strong>Category:</strong> ${idea.category}</div>
                <div class="form-group"><strong>Description:</strong> ${idea.description}</div>
                <div class="form-group"><strong>What they need:</strong> ${idea.need}</div>
                <div class="form-group"><strong>Submitted:</strong> ${idea.submittedDate}</div>
                <div class="form-group"><strong>Current Status:</strong> <span class="${idea.status === 'Pending Review' ? 'status-badge-pending' : idea.status === 'Approved' ? 'status-badge-approved' : 'status-badge-rejected'}">${idea.status}</span></div>
                ${idea.adminComment ? `<div class="form-group"><strong>Admin Feedback:</strong> ${idea.adminComment}</div>` : ''}
            `;
            document.getElementById('adminComment').value = idea.adminComment || '';
            document.getElementById('ideaModal').style.display = 'flex';
        }
    }

    function approveIdea() {
        const idea = ideas.find(i => i.id === currentIdeaId);
        if (idea) {
            idea.status = 'Approved';
            idea.adminComment = document.getElementById('adminComment').value;
            saveIdeas();
            renderIdeas();
            updateStats();
            renderCharts();
            document.getElementById('ideaModal').style.display = 'none';
            alert(`✅ Idea "${idea.title}" has been approved!`);
        }
    }

    function rejectIdea() {
        const idea = ideas.find(i => i.id === currentIdeaId);
        if (idea) {
            idea.status = 'Rejected';
            idea.adminComment = document.getElementById('adminComment').value;
            saveIdeas();
            renderIdeas();
            updateStats();
            renderCharts();
            document.getElementById('ideaModal').style.display = 'none';
            alert(`❌ Idea "${idea.title}" has been rejected.`);
        }
    }

    function renderOpportunities() {
        const container = document.getElementById('opportunitiesListContainer');
        if (opportunities.length === 0) { container.innerHTML = '<div class="widget-card" style="text-align:center;">No opportunities available</div>'; return; }
        container.innerHTML = opportunities.map(opp => `
            <div class="opportunity-card">
                <div class="card-header">
                    <strong>${opp.title}</strong>
                    <span class="status-badge-approved">${opp.type === 'job' ? 'Job' : opp.type === 'training' ? 'Training' : 'Internship'}</span>
                </div>
                <div><small><i class="fas fa-calendar"></i> Deadline: ${opp.deadline} | <i class="fas fa-envelope"></i> ${opp.contact}</small></div>
                <p class="card-desc" style="margin-top:10px;">${opp.description}</p>
                <div class="card-footer"><button class="btn-danger btn-sm delete-opp" data-id="${opp.id}"><i class="fas fa-trash"></i> Delete</button></div>
            </div>
        `).join('');
        document.querySelectorAll('.delete-opp').forEach(btn => btn.addEventListener('click', () => deleteOpportunity(parseInt(btn.dataset.id))));
    }

    function addOpportunity() {
        const title = document.getElementById('oppTitle').value;
        const type = document.getElementById('oppType').value;
        const category = document.getElementById('oppCategory').value;
        const description = document.getElementById('oppDesc').value;
        const contact = document.getElementById('oppContact').value;
        const deadline = document.getElementById('oppDeadline').value;
        if (!title || !description) { alert('Please fill title and description'); return; }
        const newOpp = { id: nextOppId++, title, type, category, description, contact, deadline, createdAt: new Date().toLocaleDateString('en-GB') };
        opportunities.push(newOpp);
        saveOpportunities();
        renderOpportunities();
        updateStats();
        renderCharts();
        document.getElementById('oppTitle').value = ''; document.getElementById('oppDesc').value = ''; document.getElementById('oppContact').value = ''; document.getElementById('oppDeadline').value = '';
        alert('Opportunity added successfully!');
    }

    function deleteOpportunity(id) {
        if (confirm('Delete this opportunity?')) { opportunities = opportunities.filter(o => o.id !== id); saveOpportunities(); renderOpportunities(); updateStats(); renderCharts(); }
    }

    function renderCharts() {
        const statusCounts = { 'Pending Review': ideas.filter(i => i.status === 'Pending Review').length, 'Approved': ideas.filter(i => i.status === 'Approved').length, 'Rejected': ideas.filter(i => i.status === 'Rejected').length };
        const typeCounts = { 'Job': opportunities.filter(o => o.type === 'job').length, 'Training': opportunities.filter(o => o.type === 'training').length, 'Internship': opportunities.filter(o => o.type === 'internship').length };
        
        const ctx1 = document.getElementById('ideasChart')?.getContext('2d');
        if (ctx1) { if (ideasChart) ideasChart.destroy(); ideasChart = new Chart(ctx1, { type: 'doughnut', data: { labels: Object.keys(statusCounts), datasets: [{ data: Object.values(statusCounts), backgroundColor: ['#f39c12', '#27ae60', '#e74c3c'] }] }, options: { responsive: true } }); }
        
        const ctx2 = document.getElementById('opportunitiesChart')?.getContext('2d');
        if (ctx2) { if (opportunitiesChart) opportunitiesChart.destroy(); opportunitiesChart = new Chart(ctx2, { type: 'bar', data: { labels: Object.keys(typeCounts), datasets: [{ label: 'Opportunities', data: Object.values(typeCounts), backgroundColor: '#3498db' }] }, options: { responsive: true } }); }
    }

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            document.getElementById('ideasTab').style.display = tab === 'ideas' ? 'block' : 'none';
            document.getElementById('opportunitiesTab').style.display = tab === 'opportunities' ? 'block' : 'none';
            document.getElementById('statisticsTab').style.display = tab === 'statistics' ? 'block' : 'none';
            if (tab === 'statistics') renderCharts();
        });
    });

    document.getElementById('refreshIdeasBtn')?.addEventListener('click', () => renderIdeas());
    document.getElementById('refreshOppBtn')?.addEventListener('click', () => renderOpportunities());
    document.getElementById('filterIdeaStatus')?.addEventListener('change', () => renderIdeas());
    document.getElementById('filterIdeaCategory')?.addEventListener('change', () => renderIdeas());
    document.getElementById('searchIdea')?.addEventListener('keyup', () => renderIdeas());
    document.getElementById('addOpportunityBtn')?.addEventListener('click', addOpportunity);
    document.getElementById('approveIdeaBtn')?.addEventListener('click', approveIdea);
    document.getElementById('rejectIdeaBtn')?.addEventListener('click', rejectIdea);
    document.querySelectorAll('.close-modal').forEach(el => el.addEventListener('click', () => document.getElementById('ideaModal').style.display = 'none'));

    setCurrentDate();
    loadData();

    function setCurrentDate() { const options = { weekday:'short', year:'numeric', month:'short', day:'numeric' }; const dateElement = document.getElementById('currentDate'); if(dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options); }
    
    document.getElementById('adminName').innerText = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}').name || 'Administrator';
    document.getElementById('adminId').innerText = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}').regNo || 'ADMIN/001';
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); sessionStorage.clear(); localStorage.clear(); window.location.href = '../login.html'; });
</script>
</body>
</html>