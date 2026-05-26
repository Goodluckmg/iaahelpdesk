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

$logged_user_id = $_SESSION['student_id'];
$photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
$photo_result = mysqli_query($conn, $photo_query);
$admin_data = mysqli_fetch_assoc($photo_result);
$current_photo = $admin_data['profile_photo'] ?? null;

// Get total users
$users_query = "SELECT COUNT(*) as total FROM students";
$users_result = mysqli_query($conn, $users_query);
$total_users = mysqli_fetch_assoc($users_result)['total'];

// Get ticket statistics
$tickets_query = "SELECT 
    COUNT(CASE WHEN status IN ('open', 'pending') THEN 1 END) as open_count,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as progress_count,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
    COUNT(*) as total_tickets
    FROM tickets";
$tickets_result = mysqli_query($conn, $tickets_query);
$ticket_stats = mysqli_fetch_assoc($tickets_result);

$open_count = $ticket_stats['open_count'] ?? 0;
$progress_count = $ticket_stats['progress_count'] ?? 0;
$resolved_count = $ticket_stats['resolved_count'] ?? 0;
$total_tickets = $ticket_stats['total_tickets'] ?? 0;
$resolution_rate = ($total_tickets > 0) ? round(($resolved_count / $total_tickets) * 100) : 0;

// Get tickets by department
$dept_query = "SELECT d.name as department_name, COUNT(t.id) as ticket_count 
               FROM departments d 
               LEFT JOIN tickets t ON d.id = t.department_id 
               GROUP BY d.id 
               ORDER BY ticket_count DESC";
$dept_result = mysqli_query($conn, $dept_query);
$dept_stats = [];
while ($row = mysqli_fetch_assoc($dept_result)) {
    $dept_stats[] = $row;
}

// Get monthly ticket trends (last 6 months)
$monthly_query = "SELECT 
    DATE_FORMAT(created_at, '%b') as month,
    COUNT(*) as ticket_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM tickets 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%b')
    ORDER BY created_at ASC";
$monthly_result = mysqli_query($conn, $monthly_query);
$monthly_data = [];
while ($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_data[] = $row;
}

// Prepare data for charts
$status_data = [
    'Open' => $open_count,
    'In Progress' => $progress_count,
    'Resolved' => $resolved_count
];

$dept_names = array_column($dept_stats, 'department_name');
$dept_counts = array_column($dept_stats, 'ticket_count');

$months = array_column($monthly_data, 'month');
$monthly_tickets = array_column($monthly_data, 'ticket_count');
$monthly_resolved = array_column($monthly_data, 'resolved_count');

// Get popular categories
$category_query = "SELECT category, COUNT(*) as count FROM tickets GROUP BY category ORDER BY count DESC LIMIT 5";
$category_result = mysqli_query($conn, $category_query);
$category_stats = [];
while ($row = mysqli_fetch_assoc($category_result)) {
    $category_stats[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #e74c3c; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #c0392b; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 18px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .chart-container { margin-bottom: 30px; }
        .chart-container canvas { max-height: 300px; width: 100%; }
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
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item active"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Analytics & Reports</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-users"></i><div class="stat-number"><?php echo $total_users; ?></div><div>Total Users</div></div>
            <div class="stat-card"><i class="fas fa-ticket-alt"></i><div class="stat-number"><?php echo $total_tickets; ?></div><div>Total Tickets</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $resolved_count; ?></div><div>Resolved Tickets</div></div>
            <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number"><?php echo $resolution_rate; ?>%</div><div>Resolution Rate</div></div>
        </div>

        <!-- Ticket Status Distribution -->
        <div class="widget-card">
            <div class="flex-between"><strong>📊 Ticket Status Distribution</strong></div>
            <div class="chart-container">
                <canvas id="ticketsChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
            </div>
        </div>

        <!-- Tickets by Department -->
        <div class="widget-card">
            <div class="flex-between"><strong>📊 Tickets by Department</strong></div>
            <div class="chart-container">
                <canvas id="deptChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
            </div>
        </div>

        <!-- Monthly Ticket Trends -->
        <div class="widget-card">
            <div class="flex-between"><strong>📈 Monthly Ticket Trends (Last 6 Months)</strong></div>
            <div class="chart-container">
                <canvas id="trendChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
            </div>
        </div>

        <!-- Popular Categories -->
        <div class="widget-card">
            <div class="flex-between"><strong>📋 Most Common Issue Categories</strong></div>
            <table style="width: 100%;">
                <thead><tr><th>Category</th><th>Number of Tickets</th></tr></thead>
                <tbody>
                    <?php foreach ($category_stats as $cat): ?>
                    <tr><td><?php echo ucfirst(str_replace('_', ' ', $cat['category'])); ?></td><td><?php echo $cat['count']; ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($category_stats)): ?>
                    <tr><td colspan="2">No data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    // Chart data from PHP
    const statusData = {
        labels: ['Open', 'In Progress', 'Resolved'],
        values: [<?php echo $open_count; ?>, <?php echo $progress_count; ?>, <?php echo $resolved_count; ?>]
    };
    
    const deptData = {
        labels: <?php echo json_encode($dept_names); ?>,
        values: <?php echo json_encode($dept_counts); ?>
    };
    
    const monthlyData = {
        labels: <?php echo json_encode($months); ?>,
        tickets: <?php echo json_encode($monthly_tickets); ?>,
        resolved: <?php echo json_encode($monthly_resolved); ?>
    };
    
    // Render Ticket Status Chart (Doughnut)
    const ctx1 = document.getElementById('ticketsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: statusData.labels,
            datasets: [{
                data: statusData.values,
                backgroundColor: ['#e74c3c', '#f39c12', '#27ae60'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    
    // Render Department Chart (Bar)
    const ctx2 = document.getElementById('deptChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: deptData.labels,
            datasets: [{
                label: 'Number of Tickets',
                data: deptData.values,
                backgroundColor: '#3498db',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Number of Tickets' } },
                x: { title: { display: true, text: 'Departments' } }
            }
        }
    });
    
    // Render Monthly Trend Chart (Line)
    if (monthlyData.labels.length > 0) {
        const ctx3 = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx3, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [
                    {
                        label: 'Tickets Received',
                        data: monthlyData.tickets,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Tickets Resolved',
                        data: monthlyData.resolved,
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Tickets' } },
                    x: { title: { display: true, text: 'Month' } }
                }
            }
        });
    } else {
        document.getElementById('trendChart').style.display = 'none';
        document.querySelector('#trendChart').parentElement.innerHTML += '<p style="text-align:center;">No monthly data available yet</p>';
    }
</script>
</body>
</html>