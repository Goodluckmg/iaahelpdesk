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

// Handle AJAX request for updating status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $ticket_id = intval($_POST['ticket_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $allowed = ['Open', 'In Progress', 'Resolved'];
    if (in_array($new_status, $allowed)) {
        $db_status = strtolower(str_replace(' ', '_', $new_status));
        $update = "UPDATE tickets SET status = '$db_status' WHERE id = $ticket_id";
        if (mysqli_query($conn, $update)) {
            echo 'success';
        } else {
            echo 'error';
        }
    } else {
        echo 'invalid';
    }
    exit();
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$status_condition = '';
if ($filter !== 'all') {
    $filter_db = strtolower(str_replace(' ', '_', $filter));
    $status_condition = "WHERE t.status = '$filter_db'";
}

// Fetch tickets with student and department names
$query = "
    SELECT t.id, t.ticket_no, t.title, t.priority, t.status, t.created_at,
           s.fullname AS student_name,
           d.name AS department_name
    FROM tickets t
    LEFT JOIN students s ON t.user_id = s.id
    LEFT JOIN departments d ON t.department_id = d.id
    $status_condition
    ORDER BY t.created_at DESC
";
$result = mysqli_query($conn, $query);
$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['display_status'] = ucwords(str_replace('_', ' ', $row['status']));
    $tickets[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | All Tickets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Quick fallback styles */
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
        .status-urgent { background: #fde8e8; color: #c0392b; }
        .btn-primary { background: #1e5a74; border: none; padding: 6px 15px; border-radius: 30px; color: white; cursor: pointer; }
        .btn-primary:hover { background: #0f4057; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-shield"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
            <div class="user-role"><?php echo ($_SESSION['role'] == 'super_admin') ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no']); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">All Tickets</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>🎫 All System Tickets</strong>
                <div>
                    <select id="filterStatus" style="width:150px; margin-right:10px;">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Open" <?php echo $filter == 'Open' ? 'selected' : ''; ?>>Open</option>
                        <option value="In Progress" <?php echo $filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Resolved" <?php echo $filter == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                    <button class="btn-primary" id="refreshTicketsBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>
            <table id="allTicketsTable">
                <thead>
                    <tr><th>Ticket No</th><th>Student</th><th>Subject</th><th>Department</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th>
                </thead>
                <tbody id="allTicketsBody">
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="8">No tickets found.<?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['ticket_no']); ?>侧
                            <td><?php echo htmlspecialchars($t['student_name'] ?? 'Unknown'); ?>侧
                            <td><?php echo htmlspecialchars(substr($t['title'], 0, 50)); ?>侧
                            <td><?php echo htmlspecialchars($t['department_name'] ?? 'Unassigned'); ?>侧
                            <td><span class="status-badge <?php echo ($t['priority'] == 'urgent') ? 'status-urgent' : ''; ?>"><?php echo ucfirst($t['priority']); ?></span>侧
                            <td>
                                <select class="status-select" data-id="<?php echo $t['id']; ?>">
                                    <option value="Open" <?php echo ($t['display_status'] == 'Open') ? 'selected' : ''; ?>>Open</option>
                                    <option value="In Progress" <?php echo ($t['display_status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Resolved" <?php echo ($t['display_status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                            侧
                            <td><?php echo date('d/m/Y', strtotime($t['created_at'])); ?>侧
                            <td><button class="btn-primary view-ticket" data-id="<?php echo $t['id']; ?>" data-title="<?php echo htmlspecialchars($t['title']); ?>"><i class="fas fa-eye"></i> View</button>侧
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });

    // Filter change - reload page with selected filter
    document.getElementById('filterStatus').addEventListener('change', function() {
        let filter = this.value;
        window.location.href = 'admin_tickets_view.php?filter=' + encodeURIComponent(filter);
    });

    // Refresh button
    document.getElementById('refreshTicketsBtn').addEventListener('click', function() {
        window.location.reload();
    });

    // Update ticket status via AJAX
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            let ticketId = this.dataset.id;
            let newStatus = this.value;
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update_status&ticket_id=' + ticketId + '&status=' + encodeURIComponent(newStatus)
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'success') {
                    alert('Status updated successfully!');
                    window.location.reload();
                } else {
                    alert('Error updating status. Please try again.');
                }
            })
            .catch(err => {
                alert('Network error: ' + err);
            });
        });
    });

    // View ticket details (simple alert – you can expand)
    document.querySelectorAll('.view-ticket').forEach(btn => {
        btn.addEventListener('click', function() {
            let ticketId = this.dataset.id;
            let title = this.dataset.title;
            alert('Ticket #' + ticketId + ': ' + title + '\nFull details will be shown here.');
        });
    });
</script>
</body>
</html>