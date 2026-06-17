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

// Include database connection
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

// Get knowledge base articles from database
$kb_query = "SELECT * FROM knowledge_base WHERE status = 'published' ORDER BY views DESC";
$kb_result = mysqli_query($conn, $kb_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Knowledge Base</title>
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
        
        /* ========== KB LIST ========== */
        .kb-list {
            list-style: none;
            padding: 0;
        }
        .kb-list li {
            padding: 12px 16px;
            margin-bottom: 8px;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 3px solid #2c7da0;
            font-size: 0.9rem;
        }
        .kb-list li i {
            color: #2c7da0;
            margin-right: 10px;
        }
        .kb-list li strong {
            color: #0a2b38;
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-label { display: none; }
            .sidebar .welcome-text, .sidebar .student-name, .sidebar .student-id { display: none; }
            .main-content { margin-left: 70px; padding: 15px; }
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
            <a href="student_knowledge-base.php" class="nav-item active"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="student_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="student_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Knowledge Base & FAQs</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>
        <div class="widget-card">
            <div class="flex-between"><strong>📖 IAA Knowledge Base</strong></div>
            <ul class="kb-list">
                <?php if(mysqli_num_rows($kb_result) > 0): ?>
                    <?php while($kb = mysqli_fetch_assoc($kb_result)): ?>
                        <li><i class="fas fa-check-circle"></i> <strong><?php echo htmlspecialchars($kb['title']); ?></strong> – <?php echo htmlspecialchars($kb['content']); ?></li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li><i class="fas fa-check-circle"></i> <strong>How to check missing marks?</strong> – Contact Examination department via helpdesk and attach registration proof.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Portal login failure?</strong> – Reset your password using student email or submit an ICT support ticket.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Course registration errors:</strong> – Ensure all fees are paid; contact Academic Registry.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Fee payment confirmation:</strong> – Upload payment slip to Finance Office through helpdesk.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Request for transcript:</strong> – Processed within 7 working days.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Library book renewal:</strong> – Can be done online via library portal.</li>
                <?php endif; ?>
            </ul>
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2edf2;">
            <p><i class="fas fa-headset" style="color: #2c7da0;"></i> Still have questions? <a href="student_submit-query.php" style="color: #2c7da0; text-decoration: none; font-weight: 600;">Submit a query</a> for personalized support.</p>
            <div style="margin-top: 20px; background: #e8f0f5; padding: 15px; border-radius: 20px;">
                <i class="fas fa-phone-alt" style="color: #2c7da0;"></i> <strong>ICT Support Hotline:</strong> +255 712 345 678<br>
                <i class="fas fa-envelope" style="color: #2c7da0;"></i> <strong>Email:</strong> helpdesk@iaa.ac.tz
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

    // Logout confirmation
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>