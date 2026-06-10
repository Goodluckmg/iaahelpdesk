<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
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

// ========== GET TICKET STATISTICS ==========

// Total tickets
$total_query = "SELECT COUNT(*) as total FROM tickets WHERE department_id = (SELECT id FROM departments WHERE name = 'ICT')";
$total_result = mysqli_query($conn, $total_query);
$total_tickets = mysqli_fetch_assoc($total_result)['total'];

// Tickets by status
$status_query = "SELECT 
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as progress_count,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count
                 FROM tickets WHERE department_id = (SELECT id FROM departments WHERE name = 'ICT')";
$status_result = mysqli_query($conn, $status_query);
$status_data = mysqli_fetch_assoc($status_result);

// Calculate resolution rate
$resolution_rate = ($total_tickets > 0) ? round(($status_data['resolved_count'] / $total_tickets) * 100) : 0;

// Get average response time (using replies)
$avg_response_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, r.created_at)) as avg_hours
                        FROM tickets t
                        JOIN ticket_replies r ON t.id = r.ticket_id
                        WHERE t.department_id = (SELECT id FROM departments WHERE name = 'ICT')
                        AND r.user_type = 'staff'";
$avg_response_result = mysqli_query($conn, $avg_response_query);
$avg_response = mysqli_fetch_assoc($avg_response_result);
$avg_response_time = round($avg_response['avg_hours'] ?? 0, 1);

// Get system uptime from system_status table
$uptime_query = "SELECT AVG(uptime) as avg_uptime FROM system_status";
$uptime_result = mysqli_query($conn, $uptime_query);
$avg_uptime = round(mysqli_fetch_assoc($uptime_result)['avg_uptime'] ?? 0, 1);

// ========== TICKETS BY CATEGORY ==========
$category_query = "SELECT 
                    COUNT(CASE WHEN category = 'Login Issue' OR category LIKE '%login%' THEN 1 END) as login_issue,
                    COUNT(CASE WHEN category = 'Email' OR category LIKE '%email%' THEN 1 END) as email,
                    COUNT(CASE WHEN category = 'Network' OR category LIKE '%network%' THEN 1 END) as network,
                    COUNT(CASE WHEN category = 'Software' OR category LIKE '%software%' THEN 1 END) as software,
                    COUNT(CASE WHEN category = 'Hardware' OR category LIKE '%hardware%' THEN 1 END) as hardware,
                    COUNT(CASE WHEN category NOT LIKE '%login%' AND category NOT LIKE '%email%' 
                              AND category NOT LIKE '%network%' AND category NOT LIKE '%software%' 
                              AND category NOT LIKE '%hardware%' OR category IS NULL THEN 1 END) as other
                  FROM tickets WHERE department_id = (SELECT id FROM departments WHERE name = 'ICT')";
$category_result = mysqli_query($conn, $category_query);
$category_data = mysqli_fetch_assoc($category_result);

// ========== WEEKLY TRENDS (Last 4 weeks) ==========
$trend_query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%W') as week,
                    COUNT(*) as received,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                FROM tickets 
                WHERE department_id = (SELECT id FROM departments WHERE name = 'ICT')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
                GROUP BY DATE_FORMAT(created_at, '%Y-%W')
                ORDER BY week ASC
                LIMIT 4";
$trend_result = mysqli_query($conn, $trend_query);
$trend_labels = [];
$trend_received = [];
$trend_resolved = [];
while ($row = mysqli_fetch_assoc($trend_result)) {
    $trend_labels[] = 'Week ' . substr($row['week'], -2);
    $trend_received[] = $row['received'];
    $trend_resolved[] = $row['resolved'];
}

// If no data, use zeros
if (empty($trend_labels)) {
    $trend_labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $trend_received = [0, 0, 0, 0];
    $trend_resolved = [0, 0, 0, 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | ICT Reports</title>
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
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #2c7da0; text-align: center; }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #2c7da0; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
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
            <a href="ict_tickets.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item"><i class="fas fa-server"></i><span>System Status</span></a>
            <a href="ict_announcements.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="ict_photo.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <a href="ict_reports.php" class="nav-item active"><i class="fas fa-chart-line"></i><span>Reports</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">ICT Performance Reports</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><div class="stat-number"><?php echo $total_tickets; ?></div><div>Total Tickets</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $resolution_rate; ?>%</div><div>Resolution Rate</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $avg_response_time; ?></div><div>Avg Response (hrs)</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $avg_uptime; ?>%</div><div>System Uptime</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Ticket Status Distribution</strong></div>
            <canvas id="statusChart" style="max-height: 300px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📈 Tickets by Category</strong></div>
            <canvas id="categoryChart" style="max-height: 300px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📅 Weekly Ticket Trends (Last 4 Weeks)</strong></div>
            <canvas id="trendChart" style="max-height: 300px; width: 100%;"></canvas>
        </div>
    </main>
</div>

<script>
    // Data from PHP
    const statusData = {
        open: <?php echo $status_data['open_count'] ?? 0; ?>,
        progress: <?php echo $status_data['progress_count'] ?? 0; ?>,
        resolved: <?php echo $status_data['resolved_count'] ?? 0; ?>
    };
    
    const categoryData = {
        login_issue: <?php echo $category_data['login_issue'] ?? 0; ?>,
        email: <?php echo $category_data['email'] ?? 0; ?>,
        network: <?php echo $category_data['network'] ?? 0; ?>,
        software: <?php echo $category_data['software'] ?? 0; ?>,
        hardware: <?php echo $category_data['hardware'] ?? 0; ?>,
        other: <?php echo $category_data['other'] ?? 0; ?>
    };
    
    const trendLabels = <?php echo json_encode($trend_labels); ?>;
    const trendReceived = <?php echo json_encode($trend_received); ?>;
    const trendResolved = <?php echo json_encode($trend_resolved); ?>;
    
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Open', 'In Progress', 'Resolved'],
            datasets: [{
                data: [statusData.open, statusData.progress, statusData.resolved],
                backgroundColor: ['#e74c3c', '#f39c12', '#27ae60'],
                borderWidth: 0
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: ['Login Issue', 'Email', 'Network', 'Software', 'Hardware', 'Other'],
            datasets: [{
                label: 'Number of Tickets',
                data: [categoryData.login_issue, categoryData.email, categoryData.network, 
                       categoryData.software, categoryData.hardware, categoryData.other],
                backgroundColor: '#3498db',
                borderRadius: 8
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            plugins: { legend: { position: 'top' } }
        }
    });
    
    // Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                { 
                    label: 'Tickets Received', 
                    data: trendReceived, 
                    borderColor: '#e74c3c', 
                    backgroundColor: 'rgba(231,76,60,0.1)', 
                    fill: true, 
                    tension: 0.3 
                },
                { 
                    label: 'Tickets Resolved', 
                    data: trendResolved, 
                    borderColor: '#27ae60', 
                    backgroundColor: 'rgba(39,174,96,0.1)', 
                    fill: true, 
                    tension: 0.3 
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            plugins: { legend: { position: 'top' } }
        }
    });
</script>
</body>
</html>