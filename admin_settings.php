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
// Create settings table if not exists
$create_table = "
CREATE TABLE IF NOT EXISTS system_settings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
mysqli_query($conn, $create_table);

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $sys_name = mysqli_real_escape_string($conn, $_POST['sys_name']);
    $sys_email = mysqli_real_escape_string($conn, $_POST['sys_email']);
    $response_time = intval($_POST['response_time']);
    $maintenance_mode = mysqli_real_escape_string($conn, $_POST['maintenance_mode']);
    
    // Insert or update settings
    $settings = [
        'system_name' => $sys_name,
        'system_email' => $sys_email,
        'default_response_time' => $response_time,
        'maintenance_mode' => $maintenance_mode
    ];
    
    foreach ($settings as $key => $value) {
        $check = mysqli_query($conn, "SELECT id FROM system_settings WHERE setting_key = '$key'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE system_settings SET setting_value = '$value' WHERE setting_key = '$key'");
        } else {
            mysqli_query($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('$key', '$value')");
        }
    }
    
    $_SESSION['success'] = "Settings saved successfully!";
    header("Location: admin_settings.php");
    exit();
}

// ========== FIXED: Clear all data (only existing tables) ==========
if (isset($_GET['clear_data'])) {
    // Clear only tables that exist in your database
    mysqli_query($conn, "DELETE FROM tickets");
    mysqli_query($conn, "DELETE FROM ticket_replies");
    mysqli_query($conn, "DELETE FROM responses");
    mysqli_query($conn, "DELETE FROM ratings");
    mysqli_query($conn, "DELETE FROM feedback");
    mysqli_query($conn, "DELETE FROM startup_opportunities");
    mysqli_query($conn, "DELETE FROM startup_ideas");
    mysqli_query($conn, "DELETE FROM knowledge_base");
    
    // Reset AUTO_INCREMENT values
    mysqli_query($conn, "ALTER TABLE tickets AUTO_INCREMENT = 1");
    mysqli_query($conn, "ALTER TABLE ticket_replies AUTO_INCREMENT = 1");
    mysqli_query($conn, "ALTER TABLE responses AUTO_INCREMENT = 1");
    mysqli_query($conn, "ALTER TABLE startup_opportunities AUTO_INCREMENT = 1");
    mysqli_query($conn, "ALTER TABLE startup_ideas AUTO_INCREMENT = 1");
    
    $_SESSION['success'] = "All system data cleared successfully!";
    header("Location: admin_settings.php");
    exit();
}

// Load current settings
$settings = [];
$result = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$system_name = $settings['system_name'] ?? 'IAA Student Helpdesk';
$system_email = $settings['system_email'] ?? 'helpdesk@iaa.ac.tz';
$response_time = $settings['default_response_time'] ?? '24';
$maintenance_mode = $settings['maintenance_mode'] ?? 'off';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | System Settings</title>
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
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        input, select { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #cbdbe6; outline: none; }
        .btn-primary { background: #2c7da0; border: none; padding: 10px 20px; border-radius: 30px; color: white; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; }
        .btn-danger { background: #c0392b; }
        hr { margin: 20px 0; border: none; border-top: 1px solid #e2edf2; }
        .message { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: #d9f0e5; color: #1d6f42; border-left: 4px solid #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; border-left: 4px solid #c0392b; }
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
            <a href="admin_settings.php" class="nav-item active"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">System Settings</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between"><strong>⚙️ System Settings</strong></div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>System Name</label>
                    <input type="text" name="sys_name" value="<?php echo htmlspecialchars($system_name); ?>" required>
                </div>
                <div class="form-group">
                    <label>System Email</label>
                    <input type="email" name="sys_email" value="<?php echo htmlspecialchars($system_email); ?>" required>
                </div>
                <div class="form-group">
                    <label>Default Response Time (hours)</label>
                    <input type="number" name="response_time" value="<?php echo $response_time; ?>" required>
                </div>
                <div class="form-group">
                    <label>Maintenance Mode</label>
                    <select name="maintenance_mode">
                        <option value="off" <?php echo $maintenance_mode == 'off' ? 'selected' : ''; ?>>Off</option>
                        <option value="on" <?php echo $maintenance_mode == 'on' ? 'selected' : ''; ?>>On</option>
                    </select>
                </div>
                <button type="submit" name="save_settings" class="btn-primary">Save Settings</button>
            </form>
            
            <?php if ($_SESSION['role'] == 'super_admin'): ?>
            <hr>
            <a href="?clear_data=1" onclick="return confirm('WARNING: This will delete ALL tickets, replies, responses, ratings, feedback, and startup data! Are you sure?')" class="btn-primary btn-danger" style="background:#c0392b;">⚠️ Clear All System Data</a>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>