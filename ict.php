<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check if user has ICT role
if ($_SESSION['role'] !== 'ict') {
    header("Location: ../" . $_SESSION['role'] . "_dashboard.php");
    exit();
}

require_once 'config/database.php';

// Get ICT staff info
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
    } else {
        $photo_query = "SELECT profile_photo FROM staff WHERE id = $staff_id";
        $photo_result = mysqli_query($conn, $photo_query);
        if ($photo_result && mysqli_num_rows($photo_result) > 0) {
            $staff_data = mysqli_fetch_assoc($photo_result);
            $current_photo = $staff_data['profile_photo'] ?? null;
        }
    }
    $_SESSION['profile_photo'] = $current_photo;
}

// Get ticket statistics for ICT department only
$stats_query = "SELECT 
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as progress_count,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
                    COUNT(CASE WHEN priority = 'urgent' OR priority = 'high' THEN 1 END) as urgent_count,
                    COUNT(*) as total_count
                FROM tickets 
                WHERE department_id = (SELECT id FROM departments WHERE name = 'ICT')";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent tickets for ICT department
$recent_query = "SELECT t.*, s.fullname as student_name, s.reg_no 
                  FROM tickets t
                  JOIN students s ON t.user_id = s.id
                  WHERE t.department_id = (SELECT id FROM departments WHERE name = 'ICT')
                  ORDER BY t.created_at DESC 
                  LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
$recent_tickets = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent_tickets[] = $row;
}

// Get system status (you can customize these)
$systems = [
    ['name' => 'Student Portal', 'status' => 'Online'],
    ['name' => 'E-Learning Platform', 'status' => 'Online'],
    ['name' => 'Library System', 'status' => 'Online'],
    ['name' => 'Email Server', 'status' => 'Online'],
    ['name' => 'Finance System', 'status' => 'Maintenance'],
    ['name' => 'Database Server', 'status' => 'Online']
];

// Calculate resolution rate
$resolution_rate = ($stats['total_count'] > 0) ? round(($stats['resolved_count'] / $stats['total_count']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Support Dashboard | IAA Helpdesk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #2c7da0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #2c7da0; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #e9eef3; font-size: 0.85rem; }
        th { background: #f1f5f9; font-weight: 600; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        .status-open { background: #e0f0f5; color: #165a72; }
        .status-in_progress { background: #fff3e0; color: #b45f06; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        .status-urgent { background: #fde8e8; color: #c0392b; }
        .btn-primary { background: #2c7da0; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        textarea, select { width: 100%; padding: 10px; border-radius: 12px; border: 1px solid #cbd5e1; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar span { display: none; } }
    </style>
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
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
            <a href="ict.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="ict_tickets.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item"><i class="fas fa-server"></i><span>System Status</span></a>
            <a href="ict_maintenance.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="ict_photo.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <a href="ict_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Reports</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">ICT Support Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number"><?php echo $stats['open_count'] ?? 0; ?></div><div>Open Tickets</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number"><?php echo $stats['progress_count'] ?? 0; ?></div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $stats['resolved_count'] ?? 0; ?></div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div class="stat-number"><?php echo $stats['urgent_count'] ?? 0; ?></div><div>High Priority</div></div>
            <div class="stat-card"><i class="fas fa-server"></i><div class="stat-number"><?php 
                $online_count = 0;
                foreach ($systems as $sys) { if ($sys['status'] == 'Online') $online_count++; }
                echo $online_count;
            ?></div><div>Systems Online</div></div>
            <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number"><?php echo $resolution_rate; ?>%</div><div>Resolution Rate</div></div>
        </div>

        <!-- RECENT TICKETS -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>🖥️ Recent Support Tickets (ICT Department)</strong>
                <a href="ict_tickets.php" class="btn-primary">View All Tickets</a>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Student</th><th>Issue</th><th>Priority</th><th>Status</th><th>Date</th><th>Action</th>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_tickets)): ?>
                            <tr><td colspan="7" style="text-align: center;">No tickets found for ICT department.<?php else: ?>
                            <?php foreach ($recent_tickets as $ticket): ?>
                                <tr>
                                    <td>#<?php echo $ticket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['student_name']); ?><br><small><?php echo $ticket['reg_no']; ?></small></td>
                                    <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?>...侧
                                    <td><span class="status-badge <?php echo ($ticket['priority'] == 'urgent') ? 'status-urgent' : ''; ?>"><?php echo ucfirst($ticket['priority']); ?></span>侧
                                    <td><span class="status-badge status-<?php echo $ticket['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span>侧
                                    <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?>侧
                                    <td><a href="ict_view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-primary" style="padding:4px 12px;">View</a>侧
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SYSTEM STATUS OVERVIEW -->
        <div class="widget-card">
            <div class="flex-between"><strong>🖧 System Status Overview</strong><a href="ict_systems.php" class="btn-primary">Manage Systems</a></div>
            <div id="systemOverview">
                <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php 
                    $online = 0; $offline = 0; $maintenance = 0;
                    foreach ($systems as $sys) {
                        if ($sys['status'] == 'Online') $online++;
                        elseif ($sys['status'] == 'Offline') $offline++;
                        else $maintenance++;
                    }
                    ?>
                    <div style="flex:1; text-align:center;"><span style="color:#27ae60;"><i class="fas fa-circle"></i> Online</span><br><strong><?php echo $online; ?></strong></div>
                    <div style="flex:1; text-align:center;"><span style="color:#c0392b;"><i class="fas fa-circle"></i> Offline</span><br><strong><?php echo $offline; ?></strong></div>
                    <div style="flex:1; text-align:center;"><span style="color:#e67e22;"><i class="fas fa-tools"></i> Maintenance</span><br><strong><?php echo $maintenance; ?></strong></div>
                </div>
                <div style="height: 8px; background: #e2edf2; border-radius: 10px;">
                    <div style="width: <?php echo ($online/count($systems))*100; ?>%; height: 8px; background: #27ae60; border-radius: 10px;"></div>
                </div>
                <div style="margin-top: 15px;">
                    <?php foreach ($systems as $sys): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.8rem;">
                            <span><?php echo $sys['name']; ?></span>
                            <span style="color: <?php 
                                echo $sys['status'] == 'Online' ? '#27ae60' : ($sys['status'] == 'Offline' ? '#c0392b' : '#e67e22'); 
                            ?>;"><?php echo $sys['status']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
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
        <form id="respondForm" method="POST" action="ict_update_ticket.php">
            <input type="hidden" name="ticket_id" id="ticketId">
            <div class="form-group"><label>Ticket ID: <span id="ticketIdDisplay"></span></label></div>
            <div class="form-group"><label>Update Message / Resolution Notes</label><textarea name="message" id="responseMsg" rows="4" placeholder="Describe the action taken..." required></textarea></div>
            <div class="form-group"><label>Update Status</label><select name="status" id="responseStatus"><option value="in_progress">In Progress</option><option value="resolved">Resolved</option></select></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Update Ticket</button></div>
        </form>
    </div>
</div>

<script>
    // Modal handlers
    const modal = document.getElementById('respondModal');
    const closeModal = document.querySelectorAll('.close-modal, #cancelModalBtn');
    
    closeModal.forEach(el => {
        el.addEventListener('click', () => {
            modal.style.display = 'none';
            document.getElementById('responseMsg').value = '';
        });
    });
    
    window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    
    // Update ticket function
    document.getElementById('respondForm')?.addEventListener('submit', (e) => {
        // Form will submit normally to ict_update_ticket.php
        // No need for extra JS
    });
    
    // Handle update buttons from table
    document.querySelectorAll('.update-ticket').forEach(btn => {
        btn.addEventListener('click', () => {
            const ticketId = btn.dataset.id;
            document.getElementById('ticketId').value = ticketId;
            document.getElementById('ticketIdDisplay').innerText = ticketId;
            modal.style.display = 'flex';
        });
    });
</script>
</body>
</html>