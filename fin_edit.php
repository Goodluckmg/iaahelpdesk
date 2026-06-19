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

require_once 'config/database.php';

$officer_id = $_SESSION['student_id'] ?? 0;
$fullname = $_SESSION['fullname'] ?? 'Finance Officer';
$reg_no = $_SESSION['reg_no'] ?? 'FIN/2024/001';

// Get current profile photo with prepared statement
$photo_query = "SELECT profile_photo FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $photo_query);
mysqli_stmt_bind_param($stmt, "i", $officer_id);
mysqli_stmt_execute($stmt);
$photo_result = mysqli_stmt_get_result($stmt);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;
mysqli_stmt_close($stmt);

// Handle photo upload with prepared statement
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    
    if ($file['error'] == 0) {
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, JPEG allowed.";
        } elseif ($file['size'] > 1 * 1024 * 1024) {
            $error = "File too large. Max 1MB allowed.";
        } else {
            $image_data = base64_encode(file_get_contents($file['tmp_name']));
            $update_query = "UPDATE students SET profile_photo = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $image_data, $officer_id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Profile photo updated successfully!";
                $current_photo = $image_data;
            } else {
                $error = "Error updating photo: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "No file selected or upload error.";
    }
}

$active_page = 'edit';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance - Edit Profile Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* =========================================
           FINANCE EDIT PHOTO STYLES - TULI KABISA
           ========================================= */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f9;
            min-height: 100vh;
        }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: #0a1c2a;
            color: white;
            padding: 20px;
            min-height: 100vh;
            flex-shrink: 0;
        }
        .profile-area { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
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
        .nav-item.active { background:#2c7da0; }
        .nav-item:hover { background: transparent; }
        .logout-item { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        .nav-label { font-size: 0.9rem; }

        .main-content { flex: 1; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.5rem; color: #0b2b4a; }
        .date-badge { background: white; padding: 8px 16px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); font-size: 0.85rem; }
        .widget-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .flex-between strong { color:#2c7da0; }
        .edit-photo-wrapper { max-width: 500px; margin: 0 auto; }
        .photo-circle-preview {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #2c7da0;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-circle-preview img { width: 100%; height: 100%; object-fit: cover; }
        .photo-circle-preview i { font-size: 60px; color: #cbd5e1; }
        .upload-card { text-align: center; padding: 20px; background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1; }
        .upload-icon { font-size: 48px; color: #2c7da0; margin-bottom: 15px; }
        .custom-file-label {
            background: #2c7da0;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            cursor: default;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .save-photo-btn {
            width: 100%;
            background: #2c7da0;
            border: none;
            padding: 12px;
            border-radius: 30px;
            color: white;
            font-weight: 600;
            cursor: default;
            margin-top: 20px;
        }
        .save-photo-btn:hover { background: #0b2b4a; }
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .nav-label, .welcome-text, .user-name, .user-role, .user-id { display: none; }
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
            <a href="finance.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
            <a href="fin_students.php" class="nav-item"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <a href="fin_edit.php" class="nav-item active"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Edit Profile Photo</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <?php if($message): ?>
            <div class="message message-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="message message-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="edit-photo-wrapper">
                <div class="photo-circle-preview">
                    <?php if ($current_photo): ?>
                        <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($current_photo); ?>" alt="Profile Photo" id="previewImage">
                    <?php else: ?>
                        <i class="fas fa-user-circle" id="previewIcon"></i>
                    <?php endif; ?>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-card">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="upload-requirements" style="margin-bottom: 15px; color:#64748b; font-size:0.85rem;">PNG, JPEG, JPG only (Max 1MB)</div>
                    <div class="file-input-area">
                        <label class="custom-file-label" id="fileLabel">
                            <i class="fas fa-folder-open"></i> Choose File
                        </label>
                        <input type="file" name="profile_photo" id="photoFile" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                    </div>
                    <div class="selected-file-name" id="fileNameDisplay" style="margin-top:10px; color:#1a5e9c;">No file chosen</div>
                    <button type="submit" class="save-photo-btn"><i class="fas fa-save"></i> Update Photo</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options);
    }
    setCurrentDate();

    const fileInput = document.getElementById('photoFile');
    const fileLabel = document.getElementById('fileLabel');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const previewImage = document.getElementById('previewImage');
    const previewIcon = document.getElementById('previewIcon');

    fileLabel?.addEventListener('click', () => fileInput?.click());

    fileInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            fileNameDisplay.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewImage) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                }
                if (previewIcon) previewIcon.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            fileNameDisplay.textContent = 'No file chosen';
        }
    });
</script>
</body>
</html>