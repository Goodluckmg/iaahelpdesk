<?php
session_start();

// 1. Angalia kama ameingia
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// 2. Angalia kama ana role ya ICT
if ($_SESSION['role'] !== 'ict') {
    header("Location: ../" . $_SESSION['role'] . "_dashboard.php");
    exit();
}

require_once 'config/database.php';

// ========== ICT STAFF ID handling ==========
$staff_id = $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? null;

if (!$staff_id) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$staff_reg_no = $_SESSION['staff_no'] ?? '';
$staff_name = $_SESSION['fullname'] ?? 'ICT Staff';

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    
    if ($file['error'] == 0) {
        if (in_array($file['type'], $allowed_types) && $file['size'] <= 2 * 1024 * 1024) { // 2MB max
            // Convert image to base64 for storage
            $image_data = base64_encode(file_get_contents($file['tmp_name']));
            
            // Check if staff is in students table or staff table
            $update_query = "UPDATE students SET profile_photo = '$image_data' WHERE id = $staff_id";
            if (!mysqli_query($conn, $update_query)) {
                // Try staff table
                $update_query = "UPDATE staff SET profile_photo = '$image_data' WHERE id = $staff_id";
                mysqli_query($conn, $update_query);
            }
            
            // Update session with new photo
            $_SESSION['profile_photo'] = $image_data;
            $_SESSION['success'] = "Profile photo updated successfully!";
        } else {
            $_SESSION['error'] = "Invalid file! Use JPEG, PNG, JPG (max 2MB).";
        }
    } else {
        $_SESSION['error'] = "Please select a file to upload.";
    }
    header("Location: ict_photo.php");
    exit();
}

// Get current profile photo (priority: session first, then database)
if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) {
    $current_photo = $_SESSION['profile_photo'];
} else {
    // Try students table first
    $photo_query = "SELECT profile_photo FROM students WHERE id = $staff_id";
    $photo_result = mysqli_query($conn, $photo_query);
    
    if ($photo_result && mysqli_num_rows($photo_result) > 0) {
        $staff_data = mysqli_fetch_assoc($photo_result);
        $current_photo = $staff_data['profile_photo'] ?? null;
    } else {
        // Try staff table
        $photo_query = "SELECT profile_photo FROM staff WHERE id = $staff_id";
        $photo_result = mysqli_query($conn, $photo_query);
        if ($photo_result && mysqli_num_rows($photo_result) > 0) {
            $staff_data = mysqli_fetch_assoc($photo_result);
            $current_photo = $staff_data['profile_photo'] ?? null;
        } else {
            $current_photo = null;
        }
    }
    // Store in session for next time
    $_SESSION['profile_photo'] = $current_photo;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | ICT - Edit Profile Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; }
        .sidebar { width: 280px; background: #0a1c2a; color: #e0edf5; display: flex; flex-direction: column; overflow-y: auto; }
        .profile-area { padding: 25px 20px; text-align: center; border-bottom: 1px solid #1a3a4f; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: linear-gradient(135deg, #2c7da0, #1f5068); }
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
        .edit-photo-wrapper { max-width: 550px; margin: 0 auto; }
        .user-info-card { text-align: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 20px; border: 1px solid #e2edf2; }
        .welcome-badge { background: #e0f0f5; display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 0.75rem; color: #2c7da0; margin-bottom: 12px; }
        .user-fullname { font-size: 1.4rem; font-weight: 700; color: #0a2b38; margin-bottom: 5px; }
        .user-reg-number { font-size: 0.8rem; color: #7f8c8d; background: #f1f5f9; display: inline-block; padding: 5px 15px; border-radius: 30px; }
        .photo-preview-card { text-align: center; margin-bottom: 25px; padding: 20px; background: white; border-radius: 20px; border: 1px solid #e2edf2; }
        .photo-circle-preview { width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; overflow: hidden; border: 4px solid #2c7da0; box-shadow: 0 5px 15px rgba(0,0,0,0.1); background: #f1f5f9; display: flex; align-items: center; justify-content: center; }
        .photo-circle-preview img { width: 100%; height: 100%; object-fit: cover; }
        .photo-circle-preview i { font-size: 60px; color: #cbd5e1; }
        .upload-card { background: #f8fafc; border-radius: 20px; padding: 25px; margin-bottom: 25px; border: 1px dashed #cbd5e1; text-align: center; }
        .upload-icon { font-size: 48px; color: #2c7da0; margin-bottom: 15px; }
        .upload-requirements { font-size: 0.75rem; color: #7f8c8d; margin-bottom: 20px; }
        .file-input-area { margin-bottom: 10px; }
        .custom-file-label { background: #2c7da0; color: white; padding: 10px 24px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .custom-file-label:hover { background: #1f5068; transform: translateY(-2px); }
        .selected-file-name { font-size: 0.75rem; color: #2c7da0; margin-top: 10px; }
        .save-photo-btn { width: 100%; background: linear-gradient(135deg, #2c7da0, #1f5068); border: none; padding: 14px; border-radius: 40px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .save-photo-btn:hover { background: linear-gradient(135deg, #1f5068, #0f3a4f); transform: translateY(-2px); }
        .message { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: #d9f0e5; color: #1d6f42; border-left: 4px solid #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; border-left: 4px solid #c0392b; }
        @media (max-width: 550px) { .photo-circle-preview { width: 120px; height: 120px; } .user-fullname { font-size: 1.2rem; } .sidebar { width: 70px; } .sidebar span { display: none; } }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
                <?php 
                // Get photo from session first (after upload it will be here)
                $display_photo = null;
                if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) {
                    $display_photo = $_SESSION['profile_photo'];
                } elseif (isset($current_photo) && !empty($current_photo)) {
                    $display_photo = $current_photo;
                }
                ?>
                <?php if ($display_photo): ?>
                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($display_photo); ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-laptop-code"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($staff_name); ?></div>
            <div class="user-role">💻 ICT Support</div>
            <div class="user-id"><?php echo htmlspecialchars($staff_reg_no); ?></div>
        </div>
        <div class="nav-menu">
            <a href="ict.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="ict_tickets.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item"><i class="fas fa-server"></i><span class="nav-label">System Status</span></a>
            <a href="ict_maintenance.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="ict_photo.php" class="nav-item active"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="ict_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Edit Profile Photo</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="edit-photo-wrapper">
            <div class="user-info-card">
                <div class="welcome-badge"><i class="fas fa-smile"></i> ICT Staff Profile</div>
                <div class="user-fullname"><?php echo htmlspecialchars($staff_name); ?></div>
                <div class="user-reg-number"><?php echo htmlspecialchars($staff_reg_no); ?></div>
            </div>

            <div class="photo-preview-card">
                <div class="photo-circle-preview" id="photoPreview">
                    <?php if ($display_photo): ?>
                        <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($display_photo); ?>" alt="Current Photo" id="previewImage">
                    <?php else: ?>
                        <i class="fas fa-laptop-code"></i>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="upload-card">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="upload-requirements">
                        <i class="fas fa-info-circle"></i> Required format: JPG, JPEG, PNG (max 2MB)
                    </div>
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
    const previewContainer = document.getElementById('photoPreview');

    if (fileLabel) {
        fileLabel.addEventListener('click', () => photoInput.click());
    }

    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    previewContainer.innerHTML = `<img src="${ev.target.result}" alt="Preview" id="previewImage" style="width:100%; height:100%; object-fit:cover;">`;
                };
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = 'No file chosen';
            }
        });
    }
</script>
</body>
</html>