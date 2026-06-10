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
$logged_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$logged_user_id) {
    session_destroy();
    header("Location: login.php");
    exit();
}

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

// ========== ANALYTICS DATA ==========

// Total tickets by status
$status_query = "SELECT status, COUNT(*) as count FROM tickets GROUP BY status";
$status_result = mysqli_query($conn, $status_query);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row['count'];
}

// Tickets by department
$dept_query = "SELECT d.name, COUNT(t.id) as count 
                FROM departments d 
                LEFT JOIN tickets t ON d.id = t.department_id 
                GROUP BY d.id 
                ORDER BY count DESC";
$dept_result = mysqli_query($conn, $dept_query);
$dept_data = [];
while ($row = mysqli_fetch_assoc($dept_result)) {
    $dept_data[] = $row;
}

// Tickets by priority
$priority_query = "SELECT priority, COUNT(*) as count FROM tickets GROUP BY priority";
$priority_result = mysqli_query($conn, $priority_query);
$priority_data = [];
while ($row = mysqli_fetch_assoc($priority_result)) {
    $priority_data[$row['priority']] = $row['count'];
}

// Tickets per month (last 12 months)
$monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                   FROM tickets 
                   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                   GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                   ORDER BY month ASC";
$monthly_result = mysqli_query($conn, $monthly_query);
$monthly_data = [];
while ($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_data[] = $row;
}

// Top students by tickets
$top_students_query = "SELECT s.fullname, s.reg_no, COUNT(t.id) as ticket_count 
                        FROM students s 
                        LEFT JOIN tickets t ON s.id = t.user_id 
                        GROUP BY s.id 
                        ORDER BY ticket_count DESC 
                        LIMIT 5";
$top_students_result = mysqli_query($conn, $top_students_query);
$top_students = [];
while ($row = mysqli_fetch_assoc($top_students_result)) {
    $top_students[] = $row;
}

// Total counts
$total_tickets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tickets"))['total'];
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE role = 'student'"))['total'];
$total_staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM staff"))['total'];
$total_resolved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tickets WHERE status = 'resolved'"))['total'];

$resolution_rate = ($total_tickets > 0) ? round(($total_resolved / $total_tickets) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #e74c3c; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #c0392b; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .chart-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 20px; }
        canvas { max-height: 300px; width: 100%; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #e9eef3; font-size: 0.85rem; }
        th { background: #f1f5f9; font-weight: 600; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar span { display: none; } .chart-row { grid-template-columns: 1fr; } }
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
            <div class="user-role"><?php echo ($_SESSION['role'] == 'super_admin') ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no'] ?? ''); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span>User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span>Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item active"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Analytics Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-ticket-alt"></i><div class="stat-number"><?php echo $total_tickets; ?></div><div>Total Tickets</div></div>
            <div class="stat-card"><i class="fas fa-user-graduate"></i><div class="stat-number"><?php echo $total_students; ?></div><div>Total Students</div></div>
            <div class="stat-card"><i class="fas fa-user-tie"></i><div class="stat-number"><?php echo $total_staff; ?></div><div>Total Staff</div></div>
            <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number"><?php echo $resolution_rate; ?>%</div><div>Resolution Rate</div></div>
        </div>

        <!-- Charts Row 1 -->
        <div class="chart-row">
            <div class="widget-card">
                <strong>📊 Tickets by Status</strong>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="widget-card">
                <strong>📊 Tickets by Priority</strong>
                <canvas id="priorityChart"></canvas>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="chart-row">
            <div class="widget-card">
                <strong>📊 Tickets by Department</strong>
                <canvas id="deptChart"></canvas>
            </div>
            <div class="widget-card">
                <strong>📈 Monthly Ticket Trends</strong>
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <!-- Top Students -->
        <div class="widget-card">
            <div class="flex-between"><strong>🏆 Top Students (Most Tickets)</strong></div>
            <table>
                <thead><tr><th>Student Name</th><th>Registration No</th><th>Tickets</th></tr></thead>
                <tbody>
                    <?php foreach ($top_students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($student['reg_no']); ?></td>
                        <td><?php echo $student['ticket_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($top_students)): ?>
                    <tr><td colspan="3">No data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($status_data)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($status_data)); ?>,
                backgroundColor: ['#f39c12', '#3498db', '#27ae60', '#e74c3c', '#95a5a6']
            }]
        },
        options: { responsive: true }
    });

    // Priority Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    new Chart(priorityCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($priority_data)); ?>,
            datasets: [{
                label: 'Tickets',
                data: <?php echo json_encode(array_values($priority_data)); ?>,
                backgroundColor: '#e74c3c'
            }]
        },
        options: { responsive: true }
    });

    // Department Chart
    const deptCtx = document.getElementById('deptChart').getContext('2d');
    new Chart(deptCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($dept_data, 'name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($dept_data, 'count')); ?>,
                backgroundColor: ['#2c7da0', '#61a5c2', '#89c2d9', '#e74c3c', '#f39c12']
            }]
        },
        options: { responsive: true }
    });

    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
            datasets: [{
                label: 'Tickets Created',
                data: <?php echo json_encode(array_column($monthly_data, 'count')); ?>,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231,76,60,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true }
    });
</script>
</body>
</html>