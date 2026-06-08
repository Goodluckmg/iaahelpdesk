<?php
session_start();

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check admin role
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student/student_index.php");
    exit();
}

require_once 'config/database.php';

// ========== FIXED: Admin ID handling ==========
$logged_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$logged_user_id) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$is_super_admin = ($_SESSION['role'] === 'super_admin');

// ========== GET PROFILE PHOTO - FIXED ==========
$photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
$photo_result = mysqli_query($conn, $photo_query);

if ($photo_result && mysqli_num_rows($photo_result) > 0) {
    $admin_data = mysqli_fetch_assoc($photo_result);
    $current_photo = $admin_data['profile_photo'] ?? null;
} else {
    $current_photo = null;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter by status
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$where = "";
if ($status_filter && $status_filter != 'all') {
    $where = "WHERE t.status = '$status_filter'";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM tickets t $where";
$count_result = mysqli_query($conn, $count_query);
$total = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total / $limit);

// Get tickets with student and department info
$tickets_query = "SELECT t.*, s.fullname as student_name, s.reg_no, d.name as dept_name
                  FROM tickets t
                  JOIN students s ON t.user_id = s.id
                  LEFT JOIN departments d ON t.department_id = d.id
                  $where
                  ORDER BY t.created_at DESC
                  LIMIT $limit OFFSET $offset";
$tickets_result = mysqli_query($conn, $tickets_query);

// Handle ticket actions (resolve, assign, etc.)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $ticket_id = (int)$_POST['ticket_id'];
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);
        $update = "UPDATE tickets SET status = '$new_status', updated_at = NOW() WHERE id = $ticket_id";
        if (mysqli_query($conn, $update)) {
            $success = "Ticket status updated successfully!";
        } else {
            $error = "Error updating ticket: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['assign_ticket']) && $is_super_admin) {
        $ticket_id = (int)$_POST['ticket_id'];
        $staff_id = (int)$_POST['staff_id'];
        $update = "UPDATE tickets SET assigned_to = $staff_id WHERE id = $ticket_id";
        mysqli_query($conn, $update);
        $success = "Ticket assigned successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tickets - Admin | IAA Helpdesk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; }
        .sidebar { width: 280px; background: #0a1c2a; color: #e0edf5; display: flex; flex-direction: column; overflow-y: auto; }
        .profile-area { padding: 25px 20px; text-align: center; border-bottom: 1px solid #1a3a4f; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: linear-gradient(135deg, #e74c3c, #c0392b); }
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
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-bar { display: flex; gap: 10px; }
        .filter-bar select, .filter-bar input { padding: 8px 12px; border-radius: 20px; border: 1px solid #cbd5e1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #e9eef3; font-size: 0.85rem; }
        th { background: #f1f5f9; font-weight: 600; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        .status-open { background: #e0f0f5; color: #165a72; }
        .status-in_progress { background: #fff3e0; color: #b45f06; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        .status-closed { background: #e2e8f0; color: #475569; }
        .btn-primary { background: #2c7da0; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; }
        .btn-sm { padding: 4px 12px; font-size: 0.7rem; }
        .btn-view { background: #2c7da0; color: white; padding: 4px 10px; border-radius: 15px; text-decoration: none; font-size: 0.7rem; }
        .pagination { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .pagination a { padding: 8px 12px; background: #e2e8f0; border-radius: 8px; text-decoration: none; color: #0f172a; }
        .pagination a.active { background: #2c7da0; color: white; }
        .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
        .alert-success { background: #d9f0e5; color: #1d6f42; border-left: 4px solid #1d6f42; }
        .alert-error { background: #fde8e8; color: #c0392b; border-left: 4px solid #c0392b; }
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
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></div>
            <div class="user-role"><?php echo $is_super_admin ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no'] ?? ''); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span>User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span>All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span>Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">All Support Tickets</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Ticket List</strong>
                <div class="filter-bar">
                    <select id="statusFilter" onchange="window.location.href='?status='+this.value">
                        <option value="all" <?php echo $status_filter == 'all' || !$status_filter ? 'selected' : ''; ?>>All Tickets</option>
                        <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ticket No</th>
                            <th>Student</th>
                            <th>Department</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($tickets_result) == 0): ?>
                            <tr><td colspan="9" style="text-align: center;">No tickets found.<?php else: ?>
                            <?php while ($ticket = mysqli_fetch_assoc($tickets_result)): ?>
                                <tr>
                                    <td><?php echo $ticket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['ticket_no']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['student_name']); ?><br><small><?php echo $ticket['reg_no']; ?></small></td>
                                    <td><?php echo htmlspecialchars($ticket['dept_name'] ?? 'General'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?>...</td>
                                    <td>
                                        <span class="status-badge <?php 
                                            echo $ticket['priority'] == 'urgent' ? 'status-open' : 
                                                ($ticket['priority'] == 'high' ? 'status-in_progress' : ''); 
                                        ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                    <td>
                                        <a href="admin_view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-view">View</a>
                                        <?php if ($is_super_admin): ?>
                                            <form method="POST" style="display: inline-block;">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" style="padding: 2px; font-size: 0.7rem;">
                                                    <option value="">Update</option>
                                                    <option value="open">Open</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="resolved">Resolved</option>
                                                    <option value="closed">Closed</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>