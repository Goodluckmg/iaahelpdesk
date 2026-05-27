<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has finance role
if ($_SESSION['role'] !== 'finance' && $_SESSION['role'] !== 'super_admin') {
    header("Location: ../" . $_SESSION['role'] . "finance.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Get finance officer info from session
$officer_id = $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 0;
$fullname = $_SESSION['fullname'] ?? 'Finance Officer';
$reg_no = $_SESSION['reg_no'] ?? 'FIN/2024/001';

// Get profile photo
$photo_query = "SELECT profile_photo FROM students WHERE id = $officer_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;

// Get statistics for finance queries
$stats_query = "SELECT 
    COUNT(CASE WHEN status IN ('open', 'pending') THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as progress_count,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
    COUNT(*) as total_count
FROM tickets WHERE department_id = 2";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Calculate response rate
$response_rate = $stats['total_count'] > 0 ? round(($stats['resolved_count'] / $stats['total_count']) * 100) : 0;

// Get recent finance queries (last 5)
$recent_query = "SELECT t.*, s.fullname as student_name, s.reg_no as student_reg 
                 FROM tickets t
                 JOIN students s ON t.user_id = s.id
                 WHERE t.department_id = 2 
                 ORDER BY t.created_at DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);

// Get common issues statistics
$issues_query = "SELECT 
    COUNT(CASE WHEN title LIKE '%payment%' OR title LIKE '%fee%' OR category = 'Fee-related query' THEN 1 END) as payment_issues,
    COUNT(CASE WHEN title LIKE '%library%' OR category = 'Library Fee' THEN 1 END) as library_issues,
    COUNT(CASE WHEN title LIKE '%scholarship%' OR category = 'Scholarship' THEN 1 END) as scholarship_issues,
    COUNT(CASE WHEN title LIKE '%balance%' OR title LIKE '%statement%' THEN 1 END) as balance_issues
FROM tickets WHERE department_id = 2";
$issues_result = mysqli_query($conn, $issues_query);
$issues = mysqli_fetch_assoc($issues_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            background: #f5f7fa;
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
            margin: 0;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-card i {
            font-size: 2rem;
            color: #f39c12;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-card div:last-child {
            color: #7f8c8d;
            font-size: 0.85rem;
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
            flex-wrap: wrap;
            gap: 10px;
        }
        .query-item {
            background: #f9fdfe;
            border-left: 3px solid #f39c12;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
        }
        .query-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 5px;
        }
        .query-meta {
            font-size: 0.7rem;
            color: #7f8c8d;
            margin-bottom: 8px;
        }
        .query-description {
            font-size: 0.8rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3e0;
            color: #e67e22;
        }
        .status-progress {
            background: #e3f2fd;
            color: #2196f3;
        }
        .status-resolved {
            background: #d9f0e5;
            color: #1d6f42;
        }
        .btn-primary {
            background: #f39c12;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
        }
        .btn-primary:hover {
            background: #e67e22;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2edf2;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
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
        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
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
            <a href="finance.php" class="nav-item active">
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
            <a href="fin_reports.php" class="nav-item">
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
            <h1 class="page-title">Finance Dashboard</h1>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i> 
                <span id="currentDate"></span>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-number"><?php echo $stats['pending_count'] ?? 0; ?></div>
                <div>Pending Queries</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner"></i>
                <div class="stat-number"><?php echo $stats['progress_count'] ?? 0; ?></div>
                <div>In Progress</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?php echo $stats['resolved_count'] ?? 0; ?></div>
                <div>Resolved</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <div class="stat-number"><?php echo $response_rate; ?>%</div>
                <div>Response Rate</div>
            </div>
        </div>

        <!-- QUERY TRENDS CHART -->
        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-chart-line"></i> Query Trends (Last 7 Days)</strong>
                <button class="btn-primary" onclick="refreshChart()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <canvas id="trendChart" height="100"></canvas>
        </div>

        <!-- RECENT QUERIES -->
        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-list"></i> Recent Student Queries</strong>
                <a href="fin_queries.php" class="btn-primary">
                    <i class="fas fa-eye"></i> View All
                </a>
            </div>
            
            <?php if(mysqli_num_rows($recent_result) == 0): ?>
                <div style="text-align:center; padding:40px; color:#7f8c8d;">
                    <i class="fas fa-inbox" style="font-size:2rem;"></i>
                    <p>No finance queries yet</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket No</th>
                            <th>Student</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($ticket = mysqli_fetch_assoc($recent_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['ticket_no']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['student_name']); ?><br>
                                <small><?php echo htmlspecialchars($ticket['student_reg']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                            <td>
                                <a href="fin_queries.php?ticket=<?php echo $ticket['id']; ?>" class="btn-primary" style="padding:4px 12px; font-size:0.7rem;">
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- COMMON ISSUES & QUICK ACTIONS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
            <div class="widget-card">
                <strong><i class="fas fa-chart-pie"></i> Common Student Issues</strong>
                <div style="margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>💰 Payment Issues</span>
                        <strong><?php echo $issues['payment_issues'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>📚 Library Fee</span>
                        <strong><?php echo $issues['library_issues'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>🎓 Scholarship</span>
                        <strong><?php echo $issues['scholarship_issues'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>💳 Balance Inquiry</span>
                        <strong><?php echo $issues['balance_issues'] ?? 0; ?></strong>
                    </div>
                </div>
            </div>

            <div class="widget-card">
                <strong><i class="fas fa-bolt"></i> Quick Actions</strong>
                <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="fin_queries.php?filter=pending" class="btn-primary" style="background:#e67e22; justify-content:center;">
                        <i class="fas fa-clock"></i> View Pending Queries
                    </a>
                    <a href="fin_students.php" class="btn-primary" style="background:#27ae60; justify-content:center;">
                        <i class="fas fa-check-circle"></i> Verify Payments
                    </a>
                    <a href="fin_reports.php" class="btn-primary" style="background:#3498db; justify-content:center;">
                        <i class="fas fa-download"></i> Generate Report
                    </a>
                </div>
            </div>
        </div>

        <!-- SUPPORT CONTACT -->
        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-headset"></i> Need Help?</strong>
            </div>
            <div style="background: #e8f0f5; padding: 15px; border-radius: 12px; margin-top: 10px;">
                <i class="fas fa-phone-alt"></i> <strong>ICT Support:</strong> +255 712 345 678<br>
                <i class="fas fa-envelope"></i> <strong>Email:</strong> helpdesk@iaa.ac.tz
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
    const trendData = <?php
        // Get last 7 days data
        $trend_query = "SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM tickets 
        WHERE department_id = 2 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC";
        $trend_result = mysqli_query($conn, $trend_query);
        $dates = [];
        $totals = [];
        $resolved = [];
        while($row = mysqli_fetch_assoc($trend_result)) {
            $dates[] = date('d/m', strtotime($row['date']));
            $totals[] = $row['total'];
            $resolved[] = $row['resolved'];
        }
        echo json_encode(['dates' => $dates, 'totals' => $totals, 'resolved' => $resolved]);
    ?>;

    // Initialize chart
    let trendChart;
    function initChart() {
        const ctx = document.getElementById('trendChart').getContext('2d');
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.dates,
                datasets: [
                    {
                        label: 'Queries Received',
                        data: trendData.totals,
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Queries Resolved',
                        data: trendData.resolved,
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
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    function refreshChart() {
        if (trendChart) {
            trendChart.destroy();
        }
        location.reload();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initChart);
</script>
</body>
</html>