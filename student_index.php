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

// ========== GET PROFILE PHOTO ==========
$photo_query = "SELECT profile_photo FROM students WHERE id = $student_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;
// =======================================

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

// ========== GET ACTIVE ANNOUNCEMENTS ==========
$announcements_query = "SELECT * FROM announcements 
                        WHERE is_active = 1 
                        AND (target_audience = 'all' OR target_audience = 'students')
                        AND (start_date IS NULL OR start_date <= NOW())
                        AND (end_date IS NULL OR end_date >= NOW())
                        ORDER BY 
                            CASE WHEN type = 'maintenance' THEN 1
                                 WHEN type = 'warning' THEN 2
                                 ELSE 3 END,
                            created_at DESC
                        LIMIT 5";
$announcements_result = mysqli_query($conn, $announcements_query);
$active_announcements = [];
while ($row = mysqli_fetch_assoc($announcements_result)) {
    $active_announcements[] = $row;
}
// ==============================================
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
    <style>
        /* Additional style for avatar with image */
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
        
        /* Announcement styles */
        .announcement-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
        }
        .announcement-maintenance {
            border-left: 4px solid #c0392b;
        }
        .announcement-warning {
            border-left: 4px solid #e67e22;
        }
        .announcement-info {
            border-left: 4px solid #2c7da0;
        }
        .announcement-success {
            border-left: 4px solid #27ae60;
        }
        .announcement-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .announcement-title strong {
            font-size: 0.9rem;
            color: #0a2b38;
        }
        .announcement-date {
            font-size: 0.7rem;
            color: #64748b;
            margin-left: auto;
        }
        .announcement-message {
            font-size: 0.85rem;
            color: #334155;
            line-height: 1.5;
        }
        .badge-type {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .badge-maintenance { background: #fde8e8; color: #c0392b; }
        .badge-warning { background: #fff3e0; color: #e67e22; }
        .badge-info { background: #e0f0f5; color: #2c7da0; }
        .badge-success { background: #d9f0e5; color: #27ae60; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
                <?php if ($current_photo): ?>
                    <img src="data:image/jpeg;base64,<?php echo $current_photo; ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-user-graduate"></i>
                <?php endif; ?>
            </div>
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
            <div style="overflow-x: auto;">
                <table style="min-width: 500px;">
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
                                <td><?php echo htmlspecialchars($ticket['ticket_no']); ?>
                                <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?>
                                <td><?php echo isset($departments[$ticket['department_id']]) ? htmlspecialchars($departments[$ticket['department_id']]) : 'Unknown'; ?>
                                <td><span class="status-badge <?php echo $ticket['status'] == 'resolved' ? 'status-resolved' : ''; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span>
                                <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ANNOUNCEMENTS SECTION - ONLY THIS ADDED -->
        <?php if (!empty($active_announcements)): ?>
        <div class="widget-card">
            <div class="flex-between">
                <strong>📢 Announcements</strong>
            </div>
            <?php foreach ($active_announcements as $ann): 
                $ann_class = 'announcement-' . $ann['type'];
                $badge_class = 'badge-' . $ann['type'];
                $badge_text = $ann['type'] == 'maintenance' ? 'Maintenance' : ($ann['type'] == 'warning' ? 'Warning' : ($ann['type'] == 'success' ? 'Success' : 'Info'));
            ?>
            <div class="announcement-item <?php echo $ann_class; ?>">
                <div class="announcement-title">
                    <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
                    <span class="badge-type <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                    <span class="announcement-date">
                        <?php echo date('d/m/Y', strtotime($ann['created_at'])); ?>
                    </span>
                </div>
                <div class="announcement-message">
                    <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <!-- END ANNOUNCEMENTS SECTION -->

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