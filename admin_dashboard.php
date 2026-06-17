<?php
session_start();

// 1. Angalia kama ameingia
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Angalia kama ana role ya admin (super_admin au admin)
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student/student_index.php");
    exit();
}

require_once 'config/database.php';

// ========== FIXED: Admin ID handling ==========
// Admin anaweza kuwa na user_id (kutoka login_process) au admin_id
$logged_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

// Kama hakuna ID, logout
if (!$logged_user_id) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$logged_role = $_SESSION['role'];
$is_super_admin = ($logged_role === 'super_admin');

// ========== GET PROFILE PHOTO - FIXED ==========
// Query from students table (admin yupo kwenye students table)
// Get profile photo - priority session first
$current_photo = null;
if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) {
    $current_photo = $_SESSION['profile_photo'];
} else {
    $photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
    $photo_result = mysqli_query($conn, $photo_query);
    if ($photo_result && mysqli_num_rows($photo_result) > 0) {
        $admin_data = mysqli_fetch_assoc($photo_result);
        $current_photo = $admin_data['profile_photo'] ?? null;
        $_SESSION['profile_photo'] = $current_photo; // Store in session
    }
}
// ============================================

// --- STATISTICS KUTOKA DATABASE ---
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE role = 'student'"))['total'];
$total_tickets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tickets"))['total'];
$open_tickets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tickets WHERE status IN ('open','pending')"))['total'];
$resolved_tickets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tickets WHERE status = 'resolved'"))['total'];
$total_depts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM departments WHERE status = 'active'"))['total'];
$resolution_rate = ($total_tickets > 0) ? round(($resolved_tickets / $total_tickets) * 100) : 0;

// Tiketi 10 za hivi karibuni
$recent_tickets = [];
$res = mysqli_query($conn, "
    SELECT t.id, t.ticket_no, t.title, t.status, t.created_at, 
           s.fullname AS student_name, d.name AS department_name
    FROM tickets t
    LEFT JOIN students s ON t.user_id = s.id
    LEFT JOIN departments d ON t.department_id = d.id
    ORDER BY t.created_at DESC LIMIT 10
");
while ($row = mysqli_fetch_assoc($res)) {
    $recent_tickets[] = $row;
}

// Takwimu za tiketi kwa kila idara
$dept_stats = [];
$res2 = mysqli_query($conn, "
    SELECT d.name AS dept_name, COUNT(t.id) AS ticket_count
    FROM departments d
    LEFT JOIN tickets t ON d.id = t.department_id
    GROUP BY d.id
    ORDER BY ticket_count DESC
");
while ($row = mysqli_fetch_assoc($res2)) {
    $dept_stats[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Quick fallback styles */
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
        .date-badge { background: white; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #2c7da0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-number { font-size: 1.8rem; font-weight: 800; color:#2c7da0; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #e9eef3; font-size: 0.85rem; }
        th { background: #f1f5f9; font-weight: 600; }
        .status-badge { background: #e0f0f5; color: #165a72; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        .btn-primary { background: #e74c3c; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; }
        .btn-primary:hover { background: #c0392b; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar span, .welcome-text, .user-name, .user-role, .user-id { display: none; } .nav-item { justify-content: center; } }
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
            <a href="admin_dashboard.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
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
            <h1 class="page-title">Admin Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-users"></i><div class="stat-number"><?php echo $total_users; ?></div><div>Total Students</div></div>
            <div class="stat-card"><i class="fas fa-ticket-alt"></i><div class="stat-number"><?php echo $total_tickets; ?></div><div>Total Tickets</div></div>
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number"><?php echo $open_tickets; ?></div><div>Open Tickets</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $resolved_tickets; ?></div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-building"></i><div class="stat-number"><?php echo $total_depts; ?></div><div>Departments</div></div>
            <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number"><?php echo $resolution_rate; ?>%</div><div>Resolution Rate</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Recent Tickets</strong>
                <a href="admin_tickets_view.php" class="btn-primary">View All</a>
            </div>
            <table>
                <thead><tr><th>Ticket No</th><th>Student</th><th>Subject</th><th>Department</th><th>Status</th><th>Date</th></thead>
                <tbody>
                    <?php if (empty($recent_tickets)): ?>
                        <tr><td colspan="6" style="text-align: center;">No tickets found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_tickets as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['ticket_no']); ?></td>
                            <td><?php echo htmlspecialchars($t['student_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars(substr($t['title'], 0, 40)); ?>...</td>
                            <td><?php echo htmlspecialchars($t['department_name'] ?? 'Unassigned'); ?></td>
                            <td><span class="status-badge <?php echo ($t['status'] == 'resolved') ? 'status-resolved' : ''; ?>"><?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($t['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Tickets by Department</strong></div>
            <div class="stats-row">
                <?php foreach ($dept_stats as $dept): ?>
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <div class="stat-number"><?php echo $dept['ticket_count']; ?></div>
                    <div><?php echo htmlspecialchars($dept['dept_name']); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($dept_stats)): ?><p>No department data yet</p><?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>