<?php
session_start();

// 1. Angalia kama ameingia
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Angalia kama ana role ya admin (super_admin au admin)
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student_dashboard.php");
    exit();
}

require_once 'config/database.php';

// ========== ADD THIS LINE ==========
$logged_user_id = $_SESSION['student_id'];
// ===================================

// Get profile photo
$photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
$photo_result = mysqli_query($conn, $photo_query);
$admin_data = mysqli_fetch_assoc($photo_result);
$current_photo = $admin_data['profile_photo'] ?? null;

// Create tables if not exists
$create_ideas_table = "
CREATE TABLE IF NOT EXISTS startup_ideas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    student_name VARCHAR(100) DEFAULT NULL,
    student_reg VARCHAR(50) DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    resources_needed TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
mysqli_query($conn, $create_ideas_table);

$create_opportunities_table = "
CREATE TABLE IF NOT EXISTS startup_opportunities (
    id INT(11) NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    type ENUM('job', 'training', 'internship') NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    description TEXT NOT NULL,
    deadline DATE DEFAULT NULL,
    contact_info VARCHAR(200) DEFAULT NULL,
    status ENUM('active', 'expired', 'closed') DEFAULT 'active',
    created_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
mysqli_query($conn, $create_opportunities_table);

// Handle idea status update (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_idea_status') {
        $idea_id = intval($_POST['idea_id']);
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);
        $admin_comment = mysqli_real_escape_string($conn, $_POST['admin_comment']);
        
        $update = "UPDATE startup_ideas SET status = '$new_status', admin_comment = '$admin_comment' WHERE id = $idea_id";
        if (mysqli_query($conn, $update)) {
            echo 'success';
        } else {
            echo 'error';
        }
        exit();
    }
    
    // Handle add opportunity
    if ($_POST['action'] === 'add_opportunity') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $contact = mysqli_real_escape_string($conn, $_POST['contact']);
        $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
        $created_by = $_SESSION['student_id'];
        
        $insert = "INSERT INTO startup_opportunities (title, type, category, description, deadline, contact_info, created_by) 
                   VALUES ('$title', '$type', '$category', '$description', '$deadline', '$contact', '$created_by')";
        if (mysqli_query($conn, $insert)) {
            echo 'success';
        } else {
            echo 'error';
        }
        exit();
    }
    
    // Handle delete opportunity
    if ($_POST['action'] === 'delete_opportunity') {
        $opp_id = intval($_POST['opp_id']);
        $delete = "DELETE FROM startup_opportunities WHERE id = $opp_id";
        if (mysqli_query($conn, $delete)) {
            echo 'success';
        } else {
            echo 'error';
        }
        exit();
    }
}

// Fetch ideas from database
$ideas_query = "SELECT * FROM startup_ideas ORDER BY created_at DESC";
$ideas_result = mysqli_query($conn, $ideas_query);
$ideas = [];
while ($row = mysqli_fetch_assoc($ideas_result)) {
    $ideas[] = $row;
}

// Fetch opportunities from database
$opp_query = "SELECT * FROM startup_opportunities WHERE status = 'active' ORDER BY created_at DESC";
$opp_result = mysqli_query($conn, $opp_query);
$opportunities = [];
while ($row = mysqli_fetch_assoc($opp_result)) {
    $opportunities[] = $row;
}

// Get counts for stats
$total_ideas = count($ideas);
$pending_ideas = count(array_filter($ideas, function($i) { return $i['status'] === 'pending'; }));
$approved_ideas = count(array_filter($ideas, function($i) { return $i['status'] === 'approved'; }));
$total_opportunities = count($opportunities);
?>

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
        .startup-stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #e74c3c; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #c0392b; margin-top: 5px; }
        .idea-card, .opportunity-card { background: white; border-radius: 16px; padding: 18px; margin-bottom: 15px; border: 1px solid #e2edf2; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
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
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .close-modal:hover { color: #c0392b; }
        .btn-success { background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 30px; cursor: pointer; }
        .btn-danger { background: #c0392b; color: white; border: none; padding: 8px 16px; border-radius: 30px; cursor: pointer; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
                <?php if ($current_photo): ?>
                    <img src="data:image/jpeg;base64,<?php echo $current_photo; ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
            <div class="user-role"><?php echo ($_SESSION['role'] == 'super_admin') ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no']); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item active"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Startup Hub Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <div class="startup-stats-row">
            <div class="stat-card"><i class="fas fa-lightbulb"></i><div class="stat-number"><?php echo $total_ideas; ?></div><div>Total Ideas</div></div>
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number"><?php echo $pending_ideas; ?></div><div>Pending Review</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $approved_ideas; ?></div><div>Approved</div></div>
            <div class="stat-card"><i class="fas fa-briefcase"></i><div class="stat-number"><?php echo $total_opportunities; ?></div><div>Opportunities</div></div>
        </div>

        <div class="startup-tabs">
            <button class="tab-btn active" data-tab="ideas"><i class="fas fa-lightbulb"></i> Student Ideas</button>
            <button class="tab-btn" data-tab="opportunities"><i class="fas fa-briefcase"></i> Opportunities</button>
            <button class="tab-btn" data-tab="statistics"><i class="fas fa-chart-line"></i> Statistics</button>
        </div>

        <!-- TAB 1: Student Ideas -->
        <div id="ideasTab" class="tab-content">
            <div class="form-container">
                <h3><i class="fas fa-filter"></i> Filter Ideas</h3>
                <div class="form-row">
                    <div class="form-group"><label>Status</label><select id="filterIdeaStatus"><option value="all">All</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select></div>
                    <div class="form-group"><label>Category</label><select id="filterIdeaCategory"><option value="all">All Categories</option><option value="Teknolojia">Technology</option><option value="Kilimo">Agriculture</option><option value="Biashara">Business</option></select></div>
                    <div class="form-group"><label>Search</label><input type="text" id="searchIdea" placeholder="Search by title..."></div>
                </div>
                <button class="btn-primary" id="refreshIdeasBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div id="ideasListContainer">
                <?php foreach ($ideas as $idea): ?>
                <div class="idea-card" data-status="<?php echo $idea['status']; ?>" data-category="<?php echo $idea['category']; ?>">
                    <div class="card-header">
                        <strong>#<?php echo $idea['id']; ?> - <?php echo htmlspecialchars($idea['title']); ?></strong>
                        <span class="status-badge-<?php echo $idea['status'] == 'pending' ? 'pending' : ($idea['status'] == 'approved' ? 'approved' : 'rejected'); ?>">
                            <?php echo ucfirst($idea['status']); ?>
                        </span>
                    </div>
                    <div><small><i class="fas fa-user"></i> <?php echo htmlspecialchars($idea['student_name']); ?> | <i class="fas fa-tag"></i> <?php echo htmlspecialchars($idea['category']); ?></small></div>
                    <p style="margin-top:10px;"><?php echo htmlspecialchars(substr($idea['description'], 0, 100)); ?>...</p>
                    <button class="btn-primary btn-sm view-idea" data-id="<?php echo $idea['id']; ?>"><i class="fas fa-eye"></i> View & Review</button>
                </div>
                <?php endforeach; ?>
                <?php if (empty($ideas)): ?>
                <div class="widget-card" style="text-align:center;">No ideas submitted yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 2: Opportunities -->
        <div id="opportunitiesTab" class="tab-content" style="display:none;">
            <div class="form-container">
                <h3><i class="fas fa-plus-circle"></i> Add New Opportunity</h3>
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
            <div style="margin:15px 0;"><button class="btn-primary" id="refreshOppBtn"><i class="fas fa-sync-alt"></i> Refresh Opportunities</button></div>
            <div id="opportunitiesListContainer">
                <?php foreach ($opportunities as $opp): ?>
                <div class="opportunity-card">
                    <div class="card-header">
                        <strong><?php echo htmlspecialchars($opp['title']); ?></strong>
                        <span class="status-badge-approved"><?php echo ucfirst($opp['type']); ?></span>
                    </div>
                    <div><small><i class="fas fa-calendar"></i> Deadline: <?php echo $opp['deadline']; ?> | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($opp['contact_info']); ?></small></div>
                    <p><?php echo htmlspecialchars(substr($opp['description'], 0, 100)); ?></p>
                    <button class="btn-danger btn-sm delete-opp" data-id="<?php echo $opp['id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                </div>
                <?php endforeach; ?>
                <?php if (empty($opportunities)): ?>
                <div class="widget-card" style="text-align:center;">No opportunities available</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 3: Statistics -->
        <div id="statisticsTab" class="tab-content" style="display:none;">
            <div class="widget-card"><canvas id="ideasChart" width="400" height="200" style="max-height:250px;"></canvas></div>
            <div class="widget-card"><canvas id="opportunitiesChart" width="400" height="200" style="max-height:250px;"></canvas></div>
        </div>
    </main>
</div>

<!-- Modal for Viewing Idea -->
<div id="ideaModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Idea Details</h3><span class="close-modal">&times;</span></div>
        <div id="ideaDetails"></div>
        <div class="form-group" style="margin-top:15px;"><label>Admin Comments</label><textarea id="adminComment" rows="3" placeholder="Add feedback..."></textarea></div>
        <div style="display:flex; gap:10px; margin-top:15px;">
            <button class="btn-success" id="approveIdeaBtn"><i class="fas fa-check"></i> Approve</button>
            <button class="btn-danger" id="rejectIdeaBtn"><i class="fas fa-times"></i> Reject</button>
        </div>
    </div>
</div>

<script>
    let currentIdeaId = null;
    let ideasChart = null, opportunitiesChart = null;
    let allIdeas = <?php echo json_encode($ideas); ?>;
    let allOpportunities = <?php echo json_encode($opportunities); ?>;

    function filterAndRenderIdeas() {
        const statusFilter = document.getElementById('filterIdeaStatus').value;
        const categoryFilter = document.getElementById('filterIdeaCategory').value;
        const searchTerm = document.getElementById('searchIdea').value.toLowerCase();
        
        let filtered = allIdeas;
        if (statusFilter !== 'all') filtered = filtered.filter(i => i.status === statusFilter);
        if (categoryFilter !== 'all') filtered = filtered.filter(i => i.category === categoryFilter);
        if (searchTerm) filtered = filtered.filter(i => i.title.toLowerCase().includes(searchTerm));
        
        const container = document.getElementById('ideasListContainer');
        if (filtered.length === 0) { container.innerHTML = '<div class="widget-card" style="text-align:center;">No ideas found</div>'; return; }
        
        container.innerHTML = filtered.map(idea => `
            <div class="idea-card">
                <div class="card-header">
                    <strong>#${idea.id} - ${escapeHtml(idea.title)}</strong>
                    <span class="status-badge-${idea.status === 'pending' ? 'pending' : (idea.status === 'approved' ? 'approved' : 'rejected')}">${idea.status.charAt(0).toUpperCase() + idea.status.slice(1)}</span>
                </div>
                <div><small><i class="fas fa-user"></i> ${escapeHtml(idea.student_name)} | <i class="fas fa-tag"></i> ${escapeHtml(idea.category)}</small></div>
                <p style="margin-top:10px;">${escapeHtml(idea.description.substring(0, 100))}...</p>
                <button class="btn-primary btn-sm view-idea" data-id="${idea.id}"><i class="fas fa-eye"></i> View & Review</button>
            </div>
        `).join('');
        
        document.querySelectorAll('.view-idea').forEach(btn => btn.addEventListener('click', () => showIdeaModal(parseInt(btn.dataset.id))));
    }

    function showIdeaModal(id) {
        const idea = allIdeas.find(i => i.id === id);
        if (idea) {
            currentIdeaId = id;
            document.getElementById('ideaDetails').innerHTML = `
                <div><strong>Student:</strong> ${escapeHtml(idea.student_name)}</div>
                <div><strong>Title:</strong> ${escapeHtml(idea.title)}</div>
                <div><strong>Category:</strong> ${escapeHtml(idea.category)}</div>
                <div><strong>Description:</strong> ${escapeHtml(idea.description)}</div>
                <div><strong>Resources Needed:</strong> ${escapeHtml(idea.resources_needed || 'Not specified')}</div>
                <div><strong>Submitted:</strong> ${idea.created_at}</div>
                <div><strong>Current Status:</strong> <span class="status-badge-${idea.status === 'pending' ? 'pending' : (idea.status === 'approved' ? 'approved' : 'rejected')}">${idea.status}</span></div>
                ${idea.admin_comment ? `<div><strong>Previous Feedback:</strong> ${escapeHtml(idea.admin_comment)}</div>` : ''}
            `;
            document.getElementById('adminComment').value = idea.admin_comment || '';
            document.getElementById('ideaModal').style.display = 'flex';
        }
    }

    function updateIdeaStatus(status) {
        const comment = document.getElementById('adminComment').value;
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_idea_status&idea_id=${currentIdeaId}&status=${status}&admin_comment=${encodeURIComponent(comment)}`
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                alert(`Idea ${status === 'approved' ? 'approved' : 'rejected'} successfully!`);
                window.location.reload();
            } else {
                alert('Error updating status');
            }
        });
    }

    function renderCharts() {
        const statusCounts = {
            'Pending': allIdeas.filter(i => i.status === 'pending').length,
            'Approved': allIdeas.filter(i => i.status === 'approved').length,
            'Rejected': allIdeas.filter(i => i.status === 'rejected').length
        };
        const typeCounts = {
            'Job': allOpportunities.filter(o => o.type === 'job').length,
            'Training': allOpportunities.filter(o => o.type === 'training').length,
            'Internship': allOpportunities.filter(o => o.type === 'internship').length
        };
        
        const ctx1 = document.getElementById('ideasChart')?.getContext('2d');
        if (ctx1) { if (ideasChart) ideasChart.destroy(); ideasChart = new Chart(ctx1, { type: 'doughnut', data: { labels: Object.keys(statusCounts), datasets: [{ data: Object.values(statusCounts), backgroundColor: ['#f39c12', '#27ae60', '#e74c3c'] }] }, options: { responsive: true } }); }
        
        const ctx2 = document.getElementById('opportunitiesChart')?.getContext('2d');
        if (ctx2) { if (opportunitiesChart) opportunitiesChart.destroy(); opportunitiesChart = new Chart(ctx2, { type: 'bar', data: { labels: Object.keys(typeCounts), datasets: [{ label: 'Opportunities', data: Object.values(typeCounts), backgroundColor: '#3498db' }] }, options: { responsive: true } }); }
    }

    function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }

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

    document.getElementById('filterIdeaStatus')?.addEventListener('change', () => filterAndRenderIdeas());
    document.getElementById('filterIdeaCategory')?.addEventListener('change', () => filterAndRenderIdeas());
    document.getElementById('searchIdea')?.addEventListener('keyup', () => filterAndRenderIdeas());
    document.getElementById('refreshIdeasBtn')?.addEventListener('click', () => window.location.reload());
    document.getElementById('refreshOppBtn')?.addEventListener('click', () => window.location.reload());
    document.getElementById('approveIdeaBtn')?.addEventListener('click', () => updateIdeaStatus('approved'));
    document.getElementById('rejectIdeaBtn')?.addEventListener('click', () => updateIdeaStatus('rejected'));
    document.querySelectorAll('.close-modal').forEach(el => el.addEventListener('click', () => document.getElementById('ideaModal').style.display = 'none'));

    // Add opportunity
    document.getElementById('addOpportunityBtn')?.addEventListener('click', () => {
        const title = document.getElementById('oppTitle').value;
        const type = document.getElementById('oppType').value;
        const category = document.getElementById('oppCategory').value;
        const description = document.getElementById('oppDesc').value;
        const contact = document.getElementById('oppContact').value;
        const deadline = document.getElementById('oppDeadline').value;
        if (!title || !description) { alert('Please fill title and description'); return; }
        
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add_opportunity&title=${encodeURIComponent(title)}&type=${type}&category=${category}&description=${encodeURIComponent(description)}&contact=${encodeURIComponent(contact)}&deadline=${deadline}`
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                alert('Opportunity added successfully!');
                window.location.reload();
            } else {
                alert('Error adding opportunity');
            }
        });
    });

    // Delete opportunity
    document.querySelectorAll('.delete-opp').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Delete this opportunity?')) {
                const oppId = btn.dataset.id;
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_opportunity&opp_id=${oppId}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'success') {
                        window.location.reload();
                    } else {
                        alert('Error deleting opportunity');
                    }
                });
            }
        });
    });

    filterAndRenderIdeas();
</script>
</body>
</html>