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

$admin_id = $_SESSION['student_id'];
$admin_reg_no = $_SESSION['reg_no'];
$admin_name = $_SESSION['fullname'];

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    
    if (in_array($file['type'], $allowed_types) && $file['size'] <= 1 * 1024 * 1024) {
        // Convert image to base64 for storage (or save to file)
        $image_data = base64_encode(file_get_contents($file['tmp_name']));
        $update_query = "UPDATE students SET profile_photo = '$image_data' WHERE id = $admin_id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = "Profile photo updated successfully!";
            // Refresh session data
            $_SESSION['profile_photo'] = $image_data;
        } else {
            $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Invalid file! Use JPEG, PNG, JPG (max 1MB).";
    }
    header("Location: admin_edit.php");
    exit();
}

// Get current profile photo from database
$photo_query = "SELECT profile_photo FROM students WHERE id = $admin_id";
$photo_result = mysqli_query($conn, $photo_query);
$admin_data = mysqli_fetch_assoc($photo_result);
$current_photo = $admin_data['profile_photo'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Admin - Edit Profile Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .edit-photo-wrapper { max-width: 550px; margin: 0 auto; }
        .user-info-card { text-align: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 20px; border: 1px solid #e2edf2; }
        .welcome-badge { background: #e0f0f5; display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 0.75rem; color: #2c7da0; margin-bottom: 12px; }
        .user-fullname { font-size: 1.4rem; font-weight: 700; color: #0a2b38; margin-bottom: 5px; }
        .user-reg-number { font-size: 0.8rem; color: #7f8c8d; background: #f1f5f9; display: inline-block; padding: 5px 15px; border-radius: 30px; }
        .photo-preview-card { text-align: center; margin-bottom: 25px; padding: 20px; background: white; border-radius: 20px; border: 1px solid #e2edf2; }
        .photo-preview-label { color: #7f8c8d; font-size: 0.8rem; margin-bottom: 15px; display: block; }
        .photo-circle-preview { width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; overflow: hidden; border: 4px solid #e74c3c; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .photo-circle-preview img { width: 100%; height: 100%; object-fit: cover; }
        .isms-info { text-align: center; margin-bottom: 20px; padding: 10px; background: #fef9e6; border-radius: 12px; font-size: 0.75rem; color: #b45f06; border-left: 3px solid #f39c12; }
        .upload-card { background: #f8fafc; border-radius: 20px; padding: 25px; margin-bottom: 25px; border: 1px dashed #cbd5e1; text-align: center; }
        .upload-icon { font-size: 48px; color: #e74c3c; margin-bottom: 15px; }
        .upload-requirements { font-size: 0.75rem; color: #7f8c8d; margin-bottom: 20px; }
        .file-input-area { margin-bottom: 10px; }
        .custom-file-label { background: #e74c3c; color: white; padding: 10px 24px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .custom-file-label:hover { background: #c0392b; transform: translateY(-2px); }
        .selected-file-name { font-size: 0.75rem; color: #e74c3c; margin-top: 10px; }
        .error-alert { background: #fde8e8; color: #c0392b; padding: 12px 15px; border-radius: 12px; font-size: 0.8rem; margin-bottom: 20px; display: none; align-items: center; gap: 10px; border-left: 3px solid #c0392b; }
        .error-alert.show { display: flex; }
        .save-photo-btn { width: 100%; background: linear-gradient(135deg, #e74c3c, #c0392b); border: none; padding: 14px; border-radius: 40px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .save-photo-btn:hover { background: linear-gradient(135deg, #c0392b, #a93226); transform: translateY(-2px); }
        .photo-notification { position: fixed; bottom: 20px; right: 20px; background: #1d6f42; color: white; padding: 12px 20px; border-radius: 12px; font-size: 0.85rem; z-index: 9999; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideInRight 0.3s ease; }
        .photo-notification.error { background: #c0392b; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: none; align-items: center; gap: 10px; }
        .message.show { display: flex; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; }
        @media (max-width: 550px) { .photo-circle-preview { width: 120px; height: 120px; } .user-fullname { font-size: 1.2rem; } }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-shield"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
            <div class="user-role"><?php echo ($_SESSION['role'] == 'super_admin') ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($admin_reg_no); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
            <a href="admin_edit.php" class="nav-item active"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Edit Profile Photo | Admin</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="edit-photo-wrapper">
            <div class="user-info-card">
                <div class="welcome-badge"><i class="fas fa-smile"></i> Admin Profile</div>
                <div class="user-fullname"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="user-reg-number"><?php echo htmlspecialchars($admin_reg_no); ?></div>
            </div>

            <div class="photo-preview-card">
                <div class="photo-preview-label"><i class="fas fa-image"></i> Current Profile Photo</div>
                <div class="photo-circle-preview">
                    <?php if ($current_photo): ?>
                        <img src="data:image/jpeg;base64,<?php echo $current_photo; ?>" alt="Profile Photo" id="previewImage">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?background=e74c3c&color=fff&size=150&name=<?php echo urlencode($admin_name); ?>" alt="Profile Photo" id="previewImage">
                    <?php endif; ?>
                </div>
            </div>

            <div class="isms-info"><i class="fas fa-shield-alt"></i> Required format PNG, JPG & JPEG Only (size less than 1 MB)</div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="upload-card">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="upload-requirements"><i class="fas fa-info-circle"></i> Required format PNG, JPEG & JPG Only (size less than 1 MB)</div>
                    <div class="file-input-area">
                        <label class="custom-file-label" id="fileLabel">
                            <i class="fas fa-folder-open"></i> Choose File
                        </label>
                        <input type="file" name="profile_photo" id="photoFile" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                    </div>
                    <div class="selected-file-name" id="fileNameDisplay">No file chosen</div>
                </div>
                <button type="submit" class="save-photo-btn"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </main>
</div>

<script>
    const photoInput = document.getElementById('photoFile');
    const fileLabel = document.getElementById('fileLabel');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const previewImage = document.getElementById('previewImage');

    fileLabel.addEventListener('click', () => photoInput.click());

    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            fileNameDisplay.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function(ev) {
                previewImage.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            fileNameDisplay.textContent = 'No file chosen';
        }
    });
</script>
</body>
</html>