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

// Handle clear all data
if (isset($_GET['clear_data'])) {
    // Clear all tables except students and system_settings
    mysqli_query($conn, "DELETE FROM tickets");
    mysqli_query($conn, "DELETE FROM responses");
    mysqli_query($conn, "DELETE FROM ratings");
    mysqli_query($conn, "DELETE FROM notifications");
    mysqli_query($conn, "DELETE FROM startup_opportunities");
    mysqli_query($conn, "DELETE FROM startup_ideas");
    mysqli_query($conn, "DELETE FROM courses");
    mysqli_query($conn, "DELETE FROM system_logs");
    
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
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: none; align-items: center; gap: 10px; }
        .message.show { display: flex; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; }
        hr { margin: 20px 0; border: none; border-top: 1px solid #e2edf2; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-shield"></i></div>
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
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item active"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">System Settings</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
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
            <hr>
            <a href="?clear_data=1" onclick="return confirm('WARNING: This will delete ALL tickets, responses, ratings, notifications, and logs! Are you sure?')" class="btn-danger btn-primary" style="background:#c0392b; text-decoration:none; display:inline-block;">⚠️ Clear All System Data</a>
        </div>
    </main>
</div>
</body>
</html>