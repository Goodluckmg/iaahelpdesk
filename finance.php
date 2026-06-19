<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has finance role
if ($_SESSION['role'] !== 'finance' && $_SESSION['role'] !== 'super_admin') {
    header("Location: " . $_SESSION['role'] . ".php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Get finance officer info from session
$officer_id = $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 0;
$fullname = $_SESSION['fullname'] ?? 'Finance Officer';
$reg_no = $_SESSION['reg_no'] ?? 'FIN/2024/001';

// --- Get profile photo using prepared statement ---
$photo_query = "SELECT profile_photo FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $photo_query);
mysqli_stmt_bind_param($stmt, "i", $officer_id);
mysqli_stmt_execute($stmt);
$photo_result = mysqli_stmt_get_result($stmt);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;
mysqli_stmt_close($stmt);

// --- Get statistics for finance queries ---
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

// --- Get recent finance queries (last 5) ---
$recent_query = "SELECT t.*, s.fullname as student_name, s.reg_no as student_reg 
                 FROM tickets t
                 JOIN students s ON t.user_id = s.id
                 WHERE t.department_id = 2 
                 ORDER BY t.created_at DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);

// --- Get common issues statistics ---
$issues_query = "SELECT 
    COUNT(CASE WHEN title LIKE '%payment%' OR title LIKE '%fee%' OR category = 'Fee-related query' THEN 1 END) as payment_issues,
    COUNT(CASE WHEN title LIKE '%library%' OR category = 'Library Fee' THEN 1 END) as library_issues,
    COUNT(CASE WHEN title LIKE '%scholarship%' OR category = 'Scholarship' THEN 1 END) as scholarship_issues,
    COUNT(CASE WHEN title LIKE '%balance%' OR title LIKE '%statement%' THEN 1 END) as balance_issues
FROM tickets WHERE department_id = 2";
$issues_result = mysqli_query($conn, $issues_query);
$issues = mysqli_fetch_assoc($issues_result);

// Set active page for sidebar
$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* =========================================
           FINANCE DASHBOARD STYLES - TULI KABISA
           RANGI ZA MFUMO MZIMA
           ========================================= */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f9; /* Rangi ya mfumo mzima */
            min-height: 100vh;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        /* SIDEBAR STYLES - ZIMEHAMISHIWA KWENYE sidebar.php */
        /* Lakini kwa kuwa bado hatujaweka include, nitaweka hapa chini */
        .sidebar {
            width: 260px;
            background:  #0a1c2a;
            color: white;
            padding: 20px;
            min-height: 100vh;
            flex-shrink: 0;
        }
        .profile-area { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: rgba(255,255,255,0.1); }
        .avatar {
            width: 70px; height: 70px; border-radius: 50%; margin: 0 auto 12px;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
            background: #1a3f60;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar i { font-size: 35px; color: white; }
        .welcome-text { font-size: 0.75rem; opacity: 0.8; }
        .user-name { font-weight: bold; margin: 5px 0; }
        .user-role { font-size: 0.7rem; opacity: 0.7; }
        .user-id { font-size: 0.65rem; opacity: 0.6; }
        .nav-menu { display: flex; flex-direction: column; gap: 5px; }
        .nav-item {
            color: white; text-decoration: none; padding: 12px 15px; border-radius: 8px;
            display: flex; align-items: center; gap: 12px;
        }
        .nav-item.active { background: #2c7da0; }
        /* HAKUNA HOVER EFFECT */
        .nav-item:hover { background: transparent; }
        .logout-item { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        .nav-label { font-size: 0.9rem; }

        /* MAIN CONTENT */
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
            color: #0b2b4a;
            margin: 0;
        }
        .date-badge {
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 1px 3px #2c7da0;
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
            box-shadow: 0 1px 3px #2c7da0;
            text-align: center;
        }
        .stat-card i {
            font-size: 2rem;
            color: #1a5e9c;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c7da0;
        }
        .stat-card div:last-child {
            color: #2c7da0;
            font-size: 0.85rem;
        }
        .widget-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px #2c7da0; */
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .flex-between strong { color: #2c7da0; }
        .btn-primary {
            background: #2c7da0;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
        }
        /* HAKUNA HOVER - button haibadiliki */
        .btn-primary:hover { background: #0b2b4a; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2edf2;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #2c7da0
        }
        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .status-pending, .status-open { background: #fff3e0; color: #e67e22; }
        .status-in_progress { background: #e3f2fd; color: #1a5e9c; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        canvas {
            max-height: 250px;
            width: 100% !important;
        }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .nav-label, .welcome-text, .user-name, .user-role, .user-id { display: none; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- SIDEBAR - BADO IMO KWENYE FILE, LAKINI UNAWEZA KUICHAINJA KUWA INCLUDE -->
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
            <a href="finance.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
            <a href="fin_students.php" class="nav-item"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <a href="fin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
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
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="fas fa-inbox" style="font-size:2rem;"></i>
                    <p>No finance queries yet</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
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
                </div>
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
                    <a href="fin_queries.php?filter=pending" class="btn-primary" style="background:#1a5e9c; justify-content:center;">
                        <i class="fas fa-clock"></i> View Pending Queries
                    </a>
                    <a href="fin_students.php" class="btn-primary" style="background:#1d6f42; justify-content:center;">
                        <i class="fas fa-check-circle"></i> Verify Payments
                    </a>
                    <a href="fin_reports.php" class="btn-primary" style="background:#b9770e; justify-content:center;">
                        <i class="fas fa-download"></i> Generate Report
                    </a>
                </div>
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
        $dates = []; $totals = []; $resolved = [];
        while($row = mysqli_fetch_assoc($trend_result)) {
            $dates[] = date('d/m', strtotime($row['date']));
            $totals[] = $row['total'];
            $resolved[] = $row['resolved'];
        }
        // If no data, provide default
        if(empty($dates)) {
            $dates = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $totals = [0,0,0,0,0,0,0];
            $resolved = [0,0,0,0,0,0,0];
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
                        borderColor: '#1a5e9c',
                        backgroundColor: 'rgba(26, 94, 156, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Queries Resolved',
                        data: trendData.resolved,
                        borderColor: '#1d6f42',
                        backgroundColor: 'rgba(29, 111, 66, 0.05)',
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

    document.addEventListener('DOMContentLoaded', initChart);
</script>
</body>
</html>