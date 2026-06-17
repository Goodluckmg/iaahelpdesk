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

require_once 'config/database.php';

$student_id = $_SESSION['student_id'];
$fullname = $_SESSION['fullname'];
$reg_no = $_SESSION['reg_no'];

// Get current profile photo
$photo_query = "SELECT profile_photo FROM students WHERE id = $student_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    
    if (in_array($file['type'], $allowed_types) && $file['size'] <= 1 * 1024 * 1024) {
        $image_data = base64_encode(file_get_contents($file['tmp_name']));
        $update_query = "UPDATE students SET profile_photo = '$image_data' WHERE id = $student_id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = "Profile photo updated successfully!";
            $current_photo = $image_data;
        } else {
            $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Invalid file! Use JPEG, PNG, JPG (max 1MB).";
    }
    header("Location: student_edit-photo.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Edit Profile Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ========== BASE STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; background: #f5f7fa; }
        
        /* ========== SIDEBAR - STATIC ========== */
        .sidebar { 
            width: 280px; 
            background: #0a2b38; /* RANGI MOJA - HAKUNA GRADIENT */
            color: #e0edf5; 
            display: flex; 
            flex-direction: column; 
            overflow-y: auto; 
            position: fixed; 
            height: 100vh; 
            left: 0; 
            top: 0; 
            z-index: 100;
        }
        .profile-area { 
            padding: 25px 20px; 
            text-align: center; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
            background: #2c7da0; /* RANGI MOJA - HAKUNA GRADIENT */
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
        
        .welcome-text { font-size: 0.85rem; color: #94a3b8; }
        .student-name { font-size: 1.1rem; font-weight: 600; margin: 5px 0; color: white; }
        .student-id { font-size: 0.7rem; margin-top: 8px; color: #94a3b8; }
        .student-id i { margin-right: 5px; }
        
        .nav-menu { flex: 1; padding: 15px; }
        .nav-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 12px 15px; 
            border-radius: 12px; 
            color: #cbdbe6; 
            text-decoration: none; 
            margin-bottom: 5px; 
            cursor: pointer; 
        }
        /* HAKUNA HOVER EFFECTS - zimeondolewa */
        .nav-item.active { 
            background: #2c7da0; 
            color: white; 
        }
        .nav-item.active i { color: white; }
        .nav-item i { width: 20px; color: #cbdbe6; }
        .nav-item.active i { color: white; }
        .nav-label { font-size: 0.9rem; }
        .logout-item { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        
        /* ========== MAIN CONTENT ========== */
        .main-content { flex: 1; padding: 20px 25px; overflow-y: auto; margin-left: 280px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.6rem; color: #0a2b38; }
        .date-badge { background: white; padding: 8px 18px; border-radius: 30px; font-size: 0.8rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        /* ========== WIDGET CARDS ========== */
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        
        /* ========== BUTTONS - COLOR #2c7da0 ========== */
        .btn-primary {
            background: #2c7da0;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            color: white;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover {
            background: #1f5a7a;
            color: white;
            text-decoration: none;
        }
        .btn-primary i { margin-right: 6px; }
        
        /* ========== EDIT PHOTO SPECIFIC ========== */
        .edit-photo-wrapper { max-width: 550px; margin: 0 auto; }
        .user-info-card { text-align: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 20px; border: 1px solid #e2edf2; }
        .welcome-badge { background: #e0f0f5; display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 0.75rem; color: #2c7da0; margin-bottom: 12px; }
        .user-fullname { font-size: 1.4rem; font-weight: 700; color: #0a2b38; margin-bottom: 5px; }
        .user-reg-number { font-size: 0.8rem; color: #7f8c8d; background: #f1f5f9; display: inline-block; padding: 5px 15px; border-radius: 30px; }
        .photo-preview-card { text-align: center; margin-bottom: 25px; padding: 20px; background: white; border-radius: 20px; border: 1px solid #e2edf2; }
        .photo-preview-label { color: #7f8c8d; font-size: 0.8rem; margin-bottom: 15px; display: block; }
        .photo-circle-preview { width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; overflow: hidden; border: 4px solid #2c7da0; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .photo-circle-preview img { width: 100%; height: 100%; object-fit: cover; }
        .upload-card { background: #f8fafc; border-radius: 20px; padding: 25px; margin-bottom: 25px; border: 1px dashed #cbd5e1; text-align: center; }
        .upload-icon { font-size: 48px; color: #2c7da0; margin-bottom: 15px; }
        .upload-requirements { font-size: 0.75rem; color: #7f8c8d; margin-bottom: 20px; }
        
        .custom-file-label { 
            background: #2c7da0; 
            color: white; 
            padding: 10px 24px; 
            border-radius: 30px; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
        }
        .custom-file-label:hover { background: #1f5a7a; }
        
        .save-photo-btn { 
            width: 100%; 
            background: #2c7da0; 
            border: none; 
            padding: 14px; 
            border-radius: 40px; 
            color: white; 
            font-size: 1rem; 
            font-weight: 600; 
            cursor: pointer; 
        }
        .save-photo-btn:hover { background: #1f5a7a; }
        
        .selected-file-name { font-size: 0.8rem; color: #2c7da0; margin-top: 10px; }
        
        /* ========== MESSAGE ========== */
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: none; align-items: center; gap: 10px; }
        .message.show { display: flex; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-label { display: none; }
            .sidebar .welcome-text, .sidebar .student-name, .sidebar .student-id { display: none; }
            .main-content { margin-left: 70px; padding: 15px; }
            .edit-photo-wrapper { max-width: 100%; }
        }
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
            <a href="student_index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="student_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="student_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="student_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="student_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="student_edit-photo.php" class="nav-item active"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Edit Profile Photo | IAA Helpdesk</h1>
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
                <div class="welcome-badge"><i class="fas fa-smile"></i> Student Profile</div>
                <div class="user-fullname"><?php echo htmlspecialchars($fullname); ?></div>
                <div class="user-reg-number"><?php echo htmlspecialchars($reg_no); ?></div>
            </div>

            <div class="photo-preview-card">
                <span class="photo-preview-label">Current Profile Photo</span>
                <div class="photo-circle-preview">
                    <?php if ($current_photo): ?>
                        <img src="data:image/jpeg;base64,<?php echo $current_photo; ?>" alt="Current Photo" id="previewImage">
                    <?php else: ?>
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150'%3E%3Crect width='150' height='150' fill='%23e2edf2'/%3E%3Ctext x='75' y='85' font-size='14' text-anchor='middle' fill='%237f8c8d' font-family='sans-serif'%3ENo Photo%3C/text%3E%3C/svg%3E" alt="No Photo" id="previewImage">
                    <?php endif; ?>
                </div>
            </div>

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

    // Logout confirmation
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>