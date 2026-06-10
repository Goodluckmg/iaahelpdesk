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

// Get ticket ID
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id == 0) {
    header("Location: admin_tickets_view.php");
    exit();
}

// Get admin ID for profile photo
$logged_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

// Get profile photo
$photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
$photo_result = mysqli_query($conn, $photo_query);
$current_photo = ($photo_result && mysqli_num_rows($photo_result) > 0) ? mysqli_fetch_assoc($photo_result)['profile_photo'] : null;

// Get ticket details with student info
$query = "SELECT t.*, s.fullname as student_name, s.reg_no, s.email as student_email, d.name as dept_name
          FROM tickets t
          JOIN students s ON t.user_id = s.id
          LEFT JOIN departments d ON t.department_id = d.id
          WHERE t.id = $ticket_id";
$result = mysqli_query($conn, $query);
$ticket = mysqli_fetch_assoc($result);

if (!$ticket) {
    header("Location: admin_tickets_view.php");
    exit();
}

// Get replies
$replies_query = "SELECT r.*, 
                  CASE WHEN r.user_type = 'student' THEN s.fullname ELSE 'Admin' END as user_name
                  FROM ticket_replies r
                  LEFT JOIN students s ON r.user_type = 'student' AND r.user_id = s.id
                  WHERE r.ticket_id = $ticket_id
                  ORDER BY r.created_at ASC";
$replies_result = mysqli_query($conn, $replies_query);

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['reply_message']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $insert = "INSERT INTO ticket_replies (ticket_id, user_id, user_type, message) 
               VALUES ($ticket_id, $logged_user_id, 'staff', '$message')";
    mysqli_query($conn, $insert);
    
    $update = "UPDATE tickets SET status = '$new_status', updated_at = NOW() WHERE id = $ticket_id";
    mysqli_query($conn, $update);
    
    $_SESSION['success'] = "Reply sent successfully!";
    header("Location: admin_view_ticket.php?id=$ticket_id");
    exit();
}

// Handle status update only
if (isset($_POST['update_status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $update = "UPDATE tickets SET status = '$new_status', updated_at = NOW() WHERE id = $ticket_id";
    mysqli_query($conn, $update);
    $_SESSION['success'] = "Ticket status updated!";
    header("Location: admin_view_ticket.php?id=$ticket_id");
    exit();
}

// Function to get file icon
function getFileIcon($filename) {
    if (empty($filename)) return 'fa-file-alt';
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word', 'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel',
        'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint',
        'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image', 'gif' => 'fa-file-image'
    ];
    return $icons[$ext] ?? 'fa-file-alt';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket #<?php echo $ticket['ticket_no']; ?> - Admin</title>
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
        .ticket-info { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        .status-open { background: #e0f0f5; color: #165a72; }
        .status-in_progress { background: #fff3e0; color: #b45f06; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        .status-closed { background: #e2e8f0; color: #475569; }
        .reply-item { background: #f8fafc; border-radius: 16px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #2c7da0; }
        .reply-item.staff { border-left-color: #e74c3c; background: #fff5f5; }
        .reply-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.8rem; color: #64748b; }
        .attachment-box { background: #f1f5f9; padding: 15px; border-radius: 12px; margin-top: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-primary { background: #2c7da0; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; }
        .btn-back { background: #7f8c8d; }
        textarea, select { width: 100%; padding: 10px; border-radius: 12px; border: 1px solid #cbd5e1; margin-top: 5px; }
        .message { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
        .message-success { background: #d9f0e5; color: #1d6f42; border-left: 4px solid #1d6f42; }
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
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Ticket #<?php echo htmlspecialchars($ticket['ticket_no']); ?></h1>
            <a href="admin_tickets_view.php" class="btn-primary btn-back"><i class="fas fa-arrow-left"></i> Back to Tickets</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Ticket Information -->
        <div class="widget-card">
            <h3><i class="fas fa-info-circle"></i> Ticket Information</h3>
            <div class="ticket-info">
                <p><strong>Student:</strong> <?php echo htmlspecialchars($ticket['student_name']); ?> (<?php echo $ticket['reg_no']; ?>)</p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['student_email']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($ticket['dept_name'] ?? 'General'); ?></p>
                <p><strong>Priority:</strong> <span class="status-badge status-<?php echo $ticket['priority']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></p>
                <p><strong>Status:</strong> <span class="status-badge status-<?php echo $ticket['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></p>
                <p><strong>Created:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></p>
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['title']); ?></p>
                <p><strong>Description:</strong></p>
                <div style="background:#f8fafc; padding: 15px; border-radius: 12px; margin-top: 5px;">
                    <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                </div>
                
                <!-- ATTACHMENT SECTION - VIEW DOCUMENT HERE -->
                <?php 
                $attachment_path = !empty($ticket['attachment']) ? $ticket['attachment'] : ($ticket['document_path'] ?? '');
                if (!empty($attachment_path) && file_exists($attachment_path)): 
                    $file_icon = getFileIcon($attachment_path);
                    $file_name = basename($attachment_path);
                ?>
                <div class="attachment-box">
                    <i class="fas <?php echo $file_icon; ?>" style="font-size: 24px; color: #e74c3c;"></i>
                    <div style="flex: 1;">
                        <strong>Attached Document:</strong><br>
                        <span style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($file_name); ?></span>
                    </div>
                    <a href="<?php echo htmlspecialchars($attachment_path); ?>" target="_blank" class="btn-primary" style="background: #27ae60;">
                        <i class="fas fa-eye"></i> View Document
                    </a>
                    <a href="<?php echo htmlspecialchars($attachment_path); ?>" download class="btn-primary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
                <?php elseif (!empty($attachment_path)): ?>
                <div class="attachment-box" style="background:#fde8e8;">
                    <i class="fas fa-exclamation-triangle" style="color: #c0392b;"></i>
                    <div>
                        <strong>Attachment not found!</strong><br>
                        <small>The file may have been moved or deleted.</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update Status Form -->
        <div class="widget-card">
            <h3><i class="fas fa-tasks"></i> Update Status</h3>
            <form method="POST">
                <select name="status" style="width: auto; display: inline-block; margin-right: 10px;">
                    <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
                <button type="submit" name="update_status" class="btn-primary">Update Status</button>
            </form>
        </div>

        <!-- Conversation History -->
        <div class="widget-card">
            <h3><i class="fas fa-comments"></i> Conversation History</h3>
            <?php if (mysqli_num_rows($replies_result) > 0): ?>
                <?php while ($reply = mysqli_fetch_assoc($replies_result)): ?>
                    <div class="reply-item <?php echo $reply['user_type']; ?>">
                        <div class="reply-header">
                            <strong><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($reply['user_name']); ?></strong>
                            <small><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></small>
                        </div>
                        <div><?php echo nl2br(htmlspecialchars($reply['message'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No replies yet.</p>
            <?php endif; ?>
        </div>

        <!-- Reply Form -->
        <div class="widget-card">
            <h3><i class="fas fa-reply"></i> Write a Reply</h3>
            <form method="POST">
                <textarea name="reply_message" rows="5" placeholder="Type your response here..." required></textarea>
                <select name="new_status" style="margin-top: 10px;">
                    <option value="in_progress">Mark as In Progress</option>
                    <option value="resolved">Mark as Resolved</option>
                    <option value="open">Keep Open</option>
                </select>
                <button type="submit" class="btn-primary" style="margin-top: 10px;"><i class="fas fa-paper-plane"></i> Send Reply</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>