<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check if user has student role
if ($_SESSION['role'] !== 'student') {
    header("Location: ../" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

require_once 'config/database.php';

// ========== ENSURE student_id IS IN SESSION ==========
// If student_id is not in session, find it using reg_no
if (!isset($_SESSION['student_id'])) {
    $reg_no = $_SESSION['reg_no'];
    $find_id_query = "SELECT id FROM students WHERE reg_no = '$reg_no'";
    $find_id_result = mysqli_query($conn, $find_id_query);
    if ($find_id_result && mysqli_num_rows($find_id_result) > 0) {
        $student_id_data = mysqli_fetch_assoc($find_id_result);
        $_SESSION['student_id'] = $student_id_data['id'];
    } else {
        // If student not found, logout
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
}

$student_id = $_SESSION['student_id'];
$fullname = $_SESSION['fullname'];
$reg_no = $_SESSION['reg_no'];

// ========== GET PROFILE PHOTO USING student_id ==========
$photo_query = "SELECT profile_photo FROM students WHERE id = $student_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;
// ========================================================

// Get current user data
$user_query = "SELECT * FROM students WHERE id = '$student_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $new_name = mysqli_real_escape_string($conn, $_POST['fullname']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $default_dept = mysqli_real_escape_string($conn, $_POST['default_dept']);
    $notif_pref = mysqli_real_escape_string($conn, $_POST['notif_pref']);
    
    $update_query = "UPDATE students SET fullname = '$new_name', email = '$new_email', phone = '$new_phone' WHERE id = '$student_id'";
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['fullname'] = $new_name;
        $success_message = "Settings saved successfully!";
        // Refresh user data
        $user['fullname'] = $new_name;
        $user['email'] = $new_email;
        $user['phone'] = $new_phone;
    } else {
        $error_message = "Error saving settings.";
    }
}

// Handle clear data
if (isset($_GET['clear_data'])) {
    // Delete all tickets for this user
    $delete_tickets = "DELETE FROM tickets WHERE user_id = '$student_id'";
    mysqli_query($conn, $delete_tickets);
    $success_message = "All your data has been cleared!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .message { 
            padding: 10px 14px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            display: none; 
            align-items: center; 
            gap: 10px; 
        }
        .message.show { 
            display: flex; 
        }
        .message-success { 
            background: #d9f0e5; 
            color: #1d6f42; 
        }
        .message-error { 
            background: #fde8e8; 
            color: #c0392b; 
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .btn-primary {
            background: #2c7da0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
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
            <a href="student_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item active"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Settings & Preferences</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="message message-success show"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if(isset($error_message)): ?>
            <div class="message message-error show"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between"><strong>⚙️ Account Settings</strong></div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address (for notifications)</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number (SMS notifications)</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>Default Department for queries</label>
                    <select name="default_dept">
                        <option>ICT Support</option>
                        <option>Examination & Records</option>
                        <option>Finance Office</option>
                        <option>Academic Registry</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notification Preference</label>
                    <select name="notif_pref">
                        <option>Email & SMS</option>
                        <option>Email only</option>
                        <option>SMS only</option>
                        <option>None</option>
                    </select>
                </div>
                <button type="submit" name="save_settings" class="btn-primary">Save Changes</button>
            </form>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>🔒 Privacy & Data</strong></div>
            <p>Your data is stored in our secure database. You can clear your ticket history at any time.</p>
            <a href="?clear_data=1" onclick="return confirm('Are you sure? This will delete ALL your tickets and data!')" class="btn-primary" style="background:#c0392b; margin-top:10px; display:inline-block;">Clear All My Data</a>
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
</script>
</body>
</html>