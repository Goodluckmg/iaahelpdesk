<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has finance role
if ($_SESSION['role'] !== 'finance' && $_SESSION['role'] !== 'super_admin') {
    header("Location: finance.php");
    exit();
}

require_once 'config/database.php';

$officer_id = $_SESSION['student_id'] ?? 0;
$fullname = $_SESSION['fullname'] ?? 'Finance Officer';
$reg_no = $_SESSION['reg_no'] ?? 'FIN/2024/001';

// Get profile photo
$photo_query = "SELECT profile_photo FROM students WHERE id = $officer_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;

// Get statistics from database
$stats_query = "SELECT 
    COUNT(*) as total_queries,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status IN ('pending', 'open', 'in_progress') THEN 1 ELSE 0 END) as pending
FROM tickets WHERE department_id = 2";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Calculate average response time
$avg_response = "SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days 
                 FROM tickets 
                 WHERE department_id = 2 AND status = 'resolved'";
$avg_result = mysqli_query($conn, $avg_response);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_response_time = round($avg_data['avg_days'] ?? 0);

// Get monthly trends (last 6 months)
$trend_query = "SELECT 
    DATE_FORMAT(created_at, '%b') as month,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
FROM tickets 
WHERE department_id = 2 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY MIN(created_at) ASC";
$trend_result = mysqli_query($conn, $trend_query);

$months = [];
$received = [];
$resolved = [];
while($row = mysqli_fetch_assoc($trend_result)) {
    $months[] = $row['month'];
    $received[] = $row['total'];
    $resolved[] = $row['resolved'];
}

// If no data, use default
if(empty($months)) {
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $received = [0, 0, 0, 0, 0, 0];
    $resolved = [0, 0, 0, 0, 0, 0];
}

// Get status distribution
$status_query = "SELECT 
    SUM(CASE WHEN status IN ('pending', 'open') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
FROM tickets WHERE department_id = 2";
$status_result = mysqli_query($conn, $status_query);
$status_data = mysqli_fetch_assoc($status_result);

// Get top students by queries
$top_students = "SELECT s.fullname, COUNT(t.id) as total
                 FROM tickets t
                 JOIN students s ON t.user_id = s.id
                 WHERE t.department_id = 2
                 GROUP BY s.id
                 ORDER BY total DESC
                 LIMIT 5";
$top_result = mysqli_query($conn, $top_students);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0a2b38, #0d3b4c);
            color: white;
            padding: 20px;
            min-height: 100vh;
        }
        .profile-area {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(135deg, #2c7da0, #1f5068);
        }
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar i {
            font-size: 35px;
            color: white;
        }
        .welcome-text {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        .user-name {
            font-weight: bold;
            margin: 5px 0;
        }
        .user-role {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        .user-id {
            font-size: 0.65rem;
            opacity: 0.6;
        }
        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .nav-item {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
        }
        .logout-item {
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
        }
        .nav-label {
            font-size: 0.9rem;
        }
        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-title {
            font-size: 1.5rem;
            color: #2c3e50;
        }
        .date-badge {
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-size: 0.85rem;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .widget-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        canvas {
            max-height: 250px;
            width: 100% !important;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            .nav-label, .welcome-text, .user-name, .user-role, .user-id {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
                <?php if ($current_photo): ?>
                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($current_photo); ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-coins"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
            <div class="user-role">💰 Finance Officer</div>
            <div class="user-id"><?php echo htmlspecialchars($reg_no); ?></div>
        </div>
        <div class="nav-menu">
            <a href="finance.php" class="nav-item">
                <i class="fas fa-chart-pie"></i>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="fin_queries.php" class="nav-item">
                <i class="fas fa-ticket-alt"></i>
                <span class="nav-label">Student Queries</span>
            </a>
            <a href="fin_students.php" class="nav-item">
                <i class="fas fa-user-check"></i>
                <span class="nav-label">Verification</span>
            </a>
            <a href="fin_reports.php" class="nav-item active">
                <i class="fas fa-chart-line"></i>
                <span class="nav-label">Reports</span>
            </a>
            <a href="fin_edit.php" class="nav-item">
                <i class="fas fa-camera"></i>
                <span class="nav-label">Edit Photo</span>
            </a>
            <div class="logout-item">
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-label">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Finance Reports & Analytics</h1>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i> 
                <span id="currentDate"></span>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_queries'] ?? 0; ?></div>
                <div>Total Queries</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div>Resolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                <div>Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $avg_response_time; ?></div>
                <div>Avg Response (days)</div>
            </div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📊 Query Trends (Last 6 Months)</strong>
            </div>
            <canvas id="trendChart"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📈 Query Status Distribution</strong>
            </div>
            <canvas id="statusChart"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>🏆 Top Students by Queries</strong>
            </div>
            <div id="topStudentsList" class="stats-row">
                <?php if(mysqli_num_rows($top_result) > 0): ?>
                    <?php while($student = mysqli_fetch_assoc($top_result)): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $student['total']; ?></div>
                            <div><?php echo htmlspecialchars($student['fullname']); ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div>No data available</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    // Set current date
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            dateElement.innerText = new Date().toLocaleDateString('en-US', options);
        }
    }
    setCurrentDate();

    // Chart data from PHP
    const months = <?php echo json_encode($months); ?>;
    const received = <?php echo json_encode($received); ?>;
    const resolved = <?php echo json_encode($resolved); ?>;

    // Initialize Trend Chart
    const ctx1 = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Queries Received',
                    data: received,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Queries Resolved',
                    data: resolved,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.05)',
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
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Initialize Status Chart
    const statusCounts = {
        'Pending': <?php echo $status_data['pending'] ?? 0; ?>,
        'In Progress': <?php echo $status_data['in_progress'] ?? 0; ?>,
        'Resolved': <?php echo $status_data['resolved'] ?? 0; ?>
    };

    const ctx2 = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusCounts),
            datasets: [{
                data: Object.values(statusCounts),
                backgroundColor: ['#e74c3c', '#f39c12', '#27ae60']
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
</script>
</body>
</html>