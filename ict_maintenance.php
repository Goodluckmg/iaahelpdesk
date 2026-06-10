<?php
session_start();

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check ICT role
if ($_SESSION['role'] !== 'ict') {
    header("Location: ../" . $_SESSION['role'] . "_dashboard.php");
    exit();
}

require_once 'config/database.php';

// Get staff info
$staff_id = $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? null;
$staff_name = $_SESSION['fullname'] ?? 'ICT Support';
$staff_no = $_SESSION['staff_no'] ?? $_SESSION['reg_no'] ?? 'ICT/001';

// Get profile photo
$current_photo = null;
if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) {
    $current_photo = $_SESSION['profile_photo'];
} else {
    $photo_query = "SELECT profile_photo FROM students WHERE id = $staff_id";
    $photo_result = mysqli_query($conn, $photo_query);
    if ($photo_result && mysqli_num_rows($photo_result) > 0) {
        $staff_data = mysqli_fetch_assoc($photo_result);
        $current_photo = $staff_data['profile_photo'] ?? null;
    }
    $_SESSION['profile_photo'] = $current_photo;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Add announcement
    if ($_POST['action'] === 'add_announcement') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $target = mysqli_real_escape_string($conn, $_POST['target']);
        $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : "NULL";
        $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : "NULL";
        
        $insert = "INSERT INTO announcements (title, message, type, target_audience, start_date, end_date, created_by) 
                   VALUES ('$title', '$message', '$type', '$target', $start_date, $end_date, '$staff_id')";
        
        if (mysqli_query($conn, $insert)) {
            echo json_encode(['success' => true, 'message' => 'Announcement posted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit();
    }
    
    // Update announcement status
    if ($_POST['action'] === 'toggle_status') {
        $id = (int)$_POST['id'];
        $current = mysqli_real_escape_string($conn, $_POST['current']);
        $new = $current == '1' ? '0' : '1';
        $update = "UPDATE announcements SET is_active = '$new' WHERE id = $id";
        
        if (mysqli_query($conn, $update)) {
            echo json_encode(['success' => true, 'new_status' => $new]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit();
    }
    
    // Delete announcement
    if ($_POST['action'] === 'delete_announcement') {
        $id = (int)$_POST['id'];
        $delete = "DELETE FROM announcements WHERE id = $id";
        
        if (mysqli_query($conn, $delete)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit();
    }
}

// Get all announcements
$announcements_query = "SELECT * FROM announcements ORDER BY 
                        CASE WHEN is_active = 1 THEN 0 ELSE 1 END,
                        created_at DESC";
$announcements_result = mysqli_query($conn, $announcements_query);
$announcements = [];
while ($row = mysqli_fetch_assoc($announcements_result)) {
    $announcements[] = $row;
}

// Get statistics
$stats_query = "SELECT 
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active,
                    COUNT(CASE WHEN type = 'maintenance' THEN 1 END) as maintenance,
                    COUNT(CASE WHEN type = 'info' THEN 1 END) as info
                FROM announcements";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Announcements | IAA Helpdesk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; }
        .sidebar { width: 280px; background: #0a1c2a; color: #e0edf5; display: flex; flex-direction: column; overflow-y: auto; }
        .profile-area { padding: 25px 20px; text-align: center; border-bottom: 1px solid #1a3a4f; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: linear-gradient(135deg, #2c7da0, #1f5068); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar i { font-size: 40px; color: white; }
        .welcome-text { font-size: 0.85rem; color: #94a3b8; }
        .user-name { font-size: 1.2rem; font-weight: 600; margin: 5px 0; }
        .user-role { font-size: 0.7rem; background: #2c7da0; display: inline-block; padding: 3px 12px; border-radius: 20px; }
        .user-id { font-size: 0.7rem; margin-top: 8px; color: #94a3b8; }
        .nav-menu { flex: 1; padding: 15px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; color: #cbdbe6; text-decoration: none; margin-bottom: 5px; transition: 0.2s; }
        .nav-item:hover { background: #1a3a4f; color: white; }
        .nav-item.active { background: #2c7da0; color: white; }
        .logout-item { margin-top: auto; border-top: 1px solid #1a3a4f; padding-top: 15px; }
        .main-content { flex: 1; padding: 20px 25px; background: #f8fafc; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.6rem; color: #0a2b38; }
        .date-badge { background: white; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #2c7da0; text-align: center; }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #2c7da0; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .announcement-item { background: #f8fafc; border-radius: 16px; padding: 18px; margin-bottom: 15px; border: 1px solid #e2edf2; }
        .announcement-title { font-weight: 600; font-size: 1rem; margin-bottom: 8px; }
        .announcement-meta { font-size: 0.7rem; color: #64748b; margin-bottom: 8px; }
        .announcement-message { font-size: 0.85rem; color: #334155; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .badge-maintenance { background: #fde8e8; color: #c0392b; }
        .badge-info { background: #e0f0f5; color: #165a72; }
        .badge-warning { background: #fff3e0; color: #b45f06; }
        .badge-success { background: #d9f0e5; color: #1d6f42; }
        .badge-active { background: #d9f0e5; color: #1d6f42; }
        .badge-inactive { background: #e2e8f0; color: #475569; }
        .btn-primary { background: #2c7da0; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; transition: 0.2s; }
        .btn-primary:hover { background: #1f5068; }
        .btn-danger { background: #c0392b; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; }
        input, select, textarea { padding: 8px 12px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none; width: 100%; margin-top: 4px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .row-2cols { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar span { display: none; } .row-2cols { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
                <?php if ($current_photo): ?>
                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($current_photo); ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-laptop-code"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($staff_name); ?></div>
            <div class="user-role">💻 ICT Support</div>
            <div class="user-id"><?php echo htmlspecialchars($staff_no); ?></div>
        </div>
        <div class="nav-menu">
            <a href="ict.php" class="nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="ict_tickets.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item"><i class="fas fa-server"></i><span>System Status</span></a>
            <a href="ict_maintenance.php" class="nav-item active"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="ict_photo.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <a href="ict_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Reports</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Announcements Manager</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-bullhorn"></i><div class="stat-number"><?php echo $stats['active'] ?? 0; ?></div><div>Active</div></div>
            <div class="stat-card"><i class="fas fa-tools"></i><div class="stat-number"><?php echo $stats['maintenance'] ?? 0; ?></div><div>Maintenance</div></div>
            <div class="stat-card"><i class="fas fa-info-circle"></i><div class="stat-number"><?php echo $stats['info'] ?? 0; ?></div><div>Information</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📢 Announcements</strong>
                <button class="btn-primary" id="addAnnouncementBtn"><i class="fas fa-plus"></i> New Announcement</button>
            </div>
            <div id="announcementsList">
                <?php if (empty($announcements)): ?>
                    <div style="text-align:center; padding:40px;">No announcements yet.</div>
                <?php else: ?>
                    <?php foreach ($announcements as $ann): 
                        $type_class = 'badge-' . $ann['type'];
                        $status_class = $ann['is_active'] ? 'badge-active' : 'badge-inactive';
                        $status_text = $ann['is_active'] ? 'Active' : 'Inactive';
                    ?>
                        <div class="announcement-item" data-id="<?php echo $ann['id']; ?>">
                            <div class="flex-between">
                                <div class="announcement-title">
                                    <span class="badge <?php echo $type_class; ?>"><?php echo ucfirst($ann['type']); ?></span>
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </div>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            <div class="announcement-meta">
                                <i class="fas fa-users"></i> Target: <?php echo ucfirst($ann['target_audience']); ?> | 
                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($ann['created_at'])); ?>
                                <?php if ($ann['start_date']): ?>
                                    | <i class="fas fa-hourglass-start"></i> Starts: <?php echo date('d/m/Y', strtotime($ann['start_date'])); ?>
                                <?php endif; ?>
                                <?php if ($ann['end_date']): ?>
                                    | <i class="fas fa-hourglass-end"></i> Ends: <?php echo date('d/m/Y', strtotime($ann['end_date'])); ?>
                                <?php endif; ?>
                            </div>
                            <div class="announcement-message"><?php echo nl2br(htmlspecialchars($ann['message'])); ?></div>
                            <div style="margin-top: 12px;">
                                <button class="btn-primary toggle-announcement" data-id="<?php echo $ann['id']; ?>" data-current="<?php echo $ann['is_active']; ?>" style="padding:4px 12px;">
                                    <i class="fas fa-<?php echo $ann['is_active'] ? 'eye-slash' : 'eye'; ?>"></i> <?php echo $ann['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                                <button class="btn-danger delete-announcement" data-id="<?php echo $ann['id']; ?>" style="padding:4px 12px;">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Add/Edit Announcement -->
<div id="announcementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">New Announcement</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="announcementForm">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" id="annTitle" required>
            </div>
            <div class="form-group">
                <label>Message *</label>
                <textarea id="annMessage" rows="4" required></textarea>
            </div>
            <div class="row-2cols">
                <div class="form-group">
                    <label>Type</label>
                    <select id="annType">
                        <option value="info">📢 Information</option>
                        <option value="warning">⚠️ Warning</option>
                        <option value="maintenance">🔧 Maintenance</option>
                        <option value="success">✅ Success</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Audience</label>
                    <select id="annTarget">
                        <option value="all">👥 All Users</option>
                        <option value="students">🎓 Students Only</option>
                        <option value="staff">👔 Staff Only</option>
                        <option value="ict">💻 ICT Only</option>
                    </select>
                </div>
            </div>
            <div class="row-2cols">
                <div class="form-group">
                    <label>Start Date (Optional)</label>
                    <input type="datetime-local" id="annStartDate">
                </div>
                <div class="form-group">
                    <label>End Date (Optional)</label>
                    <input type="datetime-local" id="annEndDate">
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Post Announcement</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('announcementModal');
    
    function showToast(message, isError = false) {
        alert(message);
    }
    
    async function addAnnouncement(data) {
        const formData = new FormData();
        formData.append('action', 'add_announcement');
        formData.append('title', data.title);
        formData.append('message', data.message);
        formData.append('type', data.type);
        formData.append('target', data.target);
        formData.append('start_date', data.startDate || '');
        formData.append('end_date', data.endDate || '');
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                window.location.reload();
            } else {
                showToast(result.message, true);
            }
        } catch (error) {
            showToast('Error posting announcement', true);
        }
    }
    
    async function toggleStatus(id, current) {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', id);
        formData.append('current', current);
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                showToast('Error updating status', true);
            }
        } catch (error) {
            showToast('Error updating status', true);
        }
    }
    
    async function deleteAnnouncement(id) {
        if (!confirm('Delete this announcement?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete_announcement');
        formData.append('id', id);
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showToast('Announcement deleted');
                window.location.reload();
            } else {
                showToast('Error deleting announcement', true);
            }
        } catch (error) {
            showToast('Error deleting announcement', true);
        }
    }
    
    // Modal handlers
    document.getElementById('addAnnouncementBtn').addEventListener('click', () => {
        document.getElementById('announcementForm').reset();
        document.getElementById('modalTitle').innerText = 'New Announcement';
        modal.style.display = 'flex';
    });
    
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => modal.style.display = 'none');
    });
    
    window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    
    document.getElementById('announcementForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const data = {
            title: document.getElementById('annTitle').value,
            message: document.getElementById('annMessage').value,
            type: document.getElementById('annType').value,
            target: document.getElementById('annTarget').value,
            startDate: document.getElementById('annStartDate').value,
            endDate: document.getElementById('annEndDate').value
        };
        addAnnouncement(data);
    });
    
    // Toggle and delete buttons
    document.querySelectorAll('.toggle-announcement').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const current = btn.dataset.current;
            toggleStatus(id, current);
        });
    });
    
    document.querySelectorAll('.delete-announcement').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            deleteAnnouncement(id);
        });
    });
</script>
</body>
</html>