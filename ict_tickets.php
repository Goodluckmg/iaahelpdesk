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
    }
    $_SESSION['profile_photo'] = $current_photo;
}

// Get ticket statistics for ICT department only
$stats_query = "SELECT 
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as progress_count,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
                    COUNT(*) as total_count
                FROM tickets 
                WHERE department_id = (SELECT id FROM departments WHERE name = 'ICT')";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get filter from URL or POST
$status_filter = isset($_GET['status']) ? $_GET['status'] : (isset($_POST['filter_status']) ? $_POST['filter_status'] : 'all');

// Build query for tickets
$where_condition = "WHERE t.department_id = (SELECT id FROM departments WHERE name = 'ICT')";
if ($status_filter != 'all') {
    $where_condition .= " AND t.status = '$status_filter'";
}

// Get all tickets for ICT department
$tickets_query = "SELECT t.*, s.fullname as student_name, s.reg_no, s.email as student_email,
                         d.name as dept_name
                  FROM tickets t
                  JOIN students s ON t.user_id = s.id
                  LEFT JOIN departments d ON t.department_id = d.id
                  $where_condition
                  ORDER BY 
                      CASE WHEN t.status = 'open' THEN 1
                           WHEN t.status = 'in_progress' THEN 2
                           ELSE 3 END,
                      t.created_at DESC";
$tickets_result = mysqli_query($conn, $tickets_query);
$tickets = [];
while ($row = mysqli_fetch_assoc($tickets_result)) {
    $tickets[] = $row;
}

// Handle ticket update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ticket') {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Insert reply
    $insert = "INSERT INTO ticket_replies (ticket_id, user_id, user_type, message) 
               VALUES ($ticket_id, $staff_id, 'staff', '$message')";
    mysqli_query($conn, $insert);
    
    // Update ticket status
    $update = "UPDATE tickets SET status = '$status', updated_at = NOW() WHERE id = $ticket_id";
    mysqli_query($conn, $update);
    
    echo json_encode(['success' => true]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Support Tickets | IAA Helpdesk</title>
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
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #2c7da0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-align: center; }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #2c7da0; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .ticket-item { background: #f8fafc; border-radius: 16px; padding: 18px; margin-bottom: 15px; border: 1px solid #e2edf2; transition: 0.2s; }
        .ticket-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .ticket-title { font-weight: 600; font-size: 1rem; margin-bottom: 8px; color: #0a2b38; }
        .ticket-meta { font-size: 0.75rem; color: #64748b; margin-bottom: 10px; }
        .ticket-description { font-size: 0.85rem; color: #334155; margin-top: 8px; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .status-open { background: #e0f0f5; color: #165a72; }
        .status-in_progress { background: #fff3e0; color: #b45f06; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        .status-urgent { background: #fde8e8; color: #c0392b; }
        .btn-primary { background: #2c7da0; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; transition: 0.2s; }
        .btn-primary:hover { background: #1f5068; }
        select, textarea { padding: 8px 12px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar span { display: none; } }
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
            <a href="ict_tickets.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item"><i class="fas fa-server"></i><span>System Status</span></a>
            <a href="ict_maintenance.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="ict_photo.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
              <a href="ict_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Reports</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Support Tickets Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number"><?php echo $stats['open_count'] ?? 0; ?></div><div>Open</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number"><?php echo $stats['progress_count'] ?? 0; ?></div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $stats['resolved_count'] ?? 0; ?></div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-ticket-alt"></i><div class="stat-number"><?php echo $stats['total_count'] ?? 0; ?></div><div>Total Tickets</div></div>
        </div>

        <!-- FILTERS -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>🎫 All Support Tickets (ICT Department)</strong>
                <div>
                    <form method="GET" style="display: inline;">
                        <select name="status" onchange="this.form.submit()" style="width:140px; margin-right:10px;">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Tickets</option>
                            <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </form>
                    <a href="ict_tickets.php" class="btn-primary"><i class="fas fa-sync-alt"></i> Refresh</a>
                </div>
            </div>
            
            <div id="allTicketsList">
                <?php if (empty($tickets)): ?>
                    <div class="widget-card" style="text-align:center;">No tickets found for ICT department.</div>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-item" data-id="<?php echo $ticket['id']; ?>">
                            <div class="ticket-title">
                                #<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['title']); ?>
                            </div>
                            <div class="ticket-meta">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($ticket['student_name']); ?> (<?php echo $ticket['reg_no']; ?>) | 
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($ticket['dept_name'] ?? 'ICT'); ?> | 
                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?> |
                                <span class="status-badge <?php echo $ticket['priority'] == 'urgent' ? 'status-urgent' : ''; ?>"><?php echo ucfirst($ticket['priority']); ?></span> |
                                <span class="status-badge status-<?php echo $ticket['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span>
                            </div>
                            <div class="ticket-description">
                                <strong>Description:</strong> <?php echo nl2br(htmlspecialchars(substr($ticket['description'], 0, 200))); ?>
                                <?php if (strlen($ticket['description']) > 200): ?>...<?php endif; ?>
                            </div>
                            
                            <!-- Display attachment if exists -->
                            <?php 
                            $attachment_path = !empty($ticket['attachment']) ? $ticket['attachment'] : ($ticket['document_path'] ?? '');
                            if (!empty($attachment_path) && file_exists($attachment_path)): ?>
                            <div style="margin-top: 10px;">
                                <i class="fas fa-paperclip"></i> 
                                <a href="<?php echo htmlspecialchars($attachment_path); ?>" target="_blank" style="color: #2c7da0;">View Attachment</a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                                <button class="btn-primary update-ticket" data-id="<?php echo $ticket['id']; ?>" style="margin-top: 12px;">
                                    <i class="fas fa-edit"></i> Update Ticket
                                </button>
                            <?php else: ?>
                                <div style="margin-top: 12px;">
                                    <span class="status-badge status-resolved"><i class="fas fa-check-circle"></i> Ticket Closed</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- MODAL FOR UPDATING TICKET -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Update Ticket</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="updateForm">
            <div class="form-group">
                <label>Ticket ID: <span id="ticketIdDisplay"></span></label>
            </div>
            <div class="form-group">
                <label>Update Message / Resolution Notes *</label>
                <textarea id="resolutionMsg" rows="4" placeholder="Describe the action taken or resolution..." required></textarea>
            </div>
            <div class="form-group">
                <label>Update Status</label>
                <select id="updateStatus">
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Update Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentTicketId = null;
    const modal = document.getElementById('updateModal');
    const closeModal = document.querySelectorAll('.close-modal, #cancelModalBtn');
    
    closeModal.forEach(el => {
        el.addEventListener('click', () => {
            modal.style.display = 'none';
            document.getElementById('resolutionMsg').value = '';
        });
    });
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.getElementById('resolutionMsg').value = '';
        }
    });
    
    document.querySelectorAll('.update-ticket').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTicketId = btn.dataset.id;
            document.getElementById('ticketIdDisplay').innerText = currentTicketId;
            modal.style.display = 'flex';
        });
    });
    
    document.getElementById('updateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const message = document.getElementById('resolutionMsg').value;
        const status = document.getElementById('updateStatus').value;
        
        if (!message) {
            alert('Please enter update message');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'update_ticket');
        formData.append('ticket_id', currentTicketId);
        formData.append('message', message);
        formData.append('status', status);
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert('✅ Ticket updated successfully!');
                window.location.reload();
            } else {
                alert('❌ Error updating ticket');
            }
        } catch (error) {
            alert('❌ Error updating ticket');
        }
    });
</script>
</body>
</html>