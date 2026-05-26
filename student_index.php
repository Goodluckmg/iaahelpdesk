<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has student role
if ($_SESSION['role'] !== 'student') {
    header("Location: " . $_SESSION['role'] . "_dashboard.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

$student_id = $_SESSION['student_id'];
$fullname = $_SESSION['fullname'];
$reg_no = $_SESSION['reg_no'];

// Get student statistics from database
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'open' OR status = 'pending' THEN 1 END) as open_count,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as progress_count,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
    COUNT(*) as total_count
    FROM tickets WHERE user_id = '$student_id'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent tickets
$recent_query = "SELECT id, ticket_no, title, department_id, status, created_at 
                 FROM tickets WHERE user_id = '$student_id' 
                 ORDER BY created_at DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);

// Get department names
$dept_query = "SELECT id, name FROM departments WHERE status = 'active'";
$dept_result = mysqli_query($conn, $dept_query);
$departments = [];
while($dept = mysqli_fetch_assoc($dept_result)) {
    $departments[$dept['id']] = $dept['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="student-name"><?php echo htmlspecialchars($fullname); ?></div>
            <div class="student-id"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($reg_no); ?></div>
        </div>
        <div class="nav-menu">
            <a href="student_index.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="student_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="student_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="student_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="student_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="student_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Dashboard | IAA Helpdesk</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number"><?php echo $stats['open_count'] ?? 0; ?></div><div>Open Queries</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number"><?php echo $stats['progress_count'] ?? 0; ?></div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $stats['resolved_count'] ?? 0; ?></div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-tachometer-alt"></i><div class="stat-number"><?php echo $stats['total_count'] ?? 0; ?></div><div>Total Queries</div></div>
        </div>

        <!-- RECENT QUERIES -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Recent student queries</strong>
                <a href="student_submit-query.php" class="btn-primary"><i class="fas fa-plus"></i> New Query</a>
            </div>
            <table>
                <thead>
                    <tr><th>Ticket No</th><th>Subject</th><th>Department</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($recent_result) == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No queries yet. <a href="student_submit-query.php">Submit your first query</a>侧
                        </tr>
                    <?php else: ?>
                        <?php while($ticket = mysqli_fetch_assoc($recent_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['ticket_no']); ?>侧
                            <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?>侧
                            <td><?php echo isset($departments[$ticket['department_id']]) ? htmlspecialchars($departments[$ticket['department_id']]) : 'Unknown'; ?>侧
                            <td><span class="status-badge <?php echo $ticket['status'] == 'resolved' ? 'status-resolved' : ''; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span>侧
                            <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?>侧
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div class="widget-card">
            <div class="flex-between"><strong>📢 Announcement from IAA</strong></div>
            <p>✅ Exam results will be released on 15th June. Use Helpdesk for missing marks queries.<br>🛠️ E-learning portal maintenance on Saturday from 8pm to 10pm.</p>
        </div>
    </main>
</div>

<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            dateElement.innerText = new Date().toLocaleDateString('en-US', options);
        }
    }
    setCurrentDate();
</script>
</body>
</html>