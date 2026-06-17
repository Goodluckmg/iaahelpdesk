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

// ========== GET ALL ANNOUNCEMENTS (ALL DEPARTMENTS) ==========
$active_announcements = [];

// 1. Get announcements from main announcements table
$announcements_query = "SELECT 
    a.*,
    'general' as source,
    'Information Center' as department_name
    FROM announcements a
    WHERE a.is_active = 1 
    AND (a.target_audience = 'all' OR a.target_audience = 'students' OR a.target_audience = 'All Students' OR a.target_audience = 'All Students & Staff')
    AND (a.start_date IS NULL OR a.start_date <= NOW())
    AND (a.end_date IS NULL OR a.end_date >= NOW())
    ORDER BY 
        CASE WHEN a.type = 'maintenance' THEN 1
             WHEN a.type = 'warning' THEN 2
             ELSE 3 END,
        a.created_at DESC
    LIMIT 10";

$announcements_result = mysqli_query($conn, $announcements_query);
while ($row = mysqli_fetch_assoc($announcements_result)) {
    $active_announcements[] = $row;
}

// 2. Get from exam_announcements (Examinations Department)
$exam_announcements_query = "SELECT 
    e.*,
    'exam' as source,
    'Examinations Department' as department_name,
    e.priority as type
    FROM exam_announcements e
    WHERE (e.target_audience = 'All Students & Staff' OR e.target_audience = 'All Students')
    ORDER BY 
        CASE WHEN e.priority = 'High' THEN 1
             WHEN e.priority = 'Medium' THEN 2
             ELSE 3 END,
        e.created_at DESC
    LIMIT 5";

$exam_result = mysqli_query($conn, $exam_announcements_query);
while ($row = mysqli_fetch_assoc($exam_result)) {
    $active_announcements[] = $row;
}

// Sort all announcements by priority/type and date
usort($active_announcements, function($a, $b) {
    $priority_a = $a['type'] ?? ($a['priority'] ?? 'Medium');
    $priority_b = $b['type'] ?? ($b['priority'] ?? 'Medium');
    
    $order = ['maintenance' => 1, 'High' => 1, 'warning' => 2, 'Medium' => 2, 'info' => 3, 'Low' => 3, 'success' => 4];
    $pa = $order[strtolower($priority_a)] ?? 3;
    $pb = $order[strtolower($priority_b)] ?? 3;
    
    if ($pa != $pb) return $pa - $pb;
    
    $time_a = strtotime($a['created_at']);
    $time_b = strtotime($b['created_at']);
    return $time_b - $time_a;
});
// =============================================================

// ========== GET ALL DOCUMENTS ==========
$documents_query = "SELECT * FROM exam_documents 
                    WHERE category IN ('almanac', 'test_timetable', 'exam_timetable', 'class_timetable')
                    ORDER BY created_at DESC";
$documents_result = mysqli_query($conn, $documents_query);
$all_documents = [];
while ($row = mysqli_fetch_assoc($documents_result)) {
    $all_documents[] = $row;
}

// Group documents by category (only get latest per category)
$latest_documents = [];
foreach ($all_documents as $doc) {
    $cat = $doc['category'];
    if (!isset($latest_documents[$cat]) || strtotime($doc['created_at']) > strtotime($latest_documents[$cat]['created_at'])) {
        $latest_documents[$cat] = $doc;
    }
}
// =================================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Dashboard</title>
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
            /* HAKUNA transition au animation */
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
            /* HAKUNA transition */ 
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
        
        /* ========== STATS CARDS ========== */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card i { font-size: 30px; color: #2c7da0; margin-bottom: 8px; }
        .stat-number { font-size: 28px; font-weight: 700; color: #333; }
        .stat-card div:last-child { font-size: 13px; color: #666; margin-top: 5px; }
        
        /* ========== TABLE STYLES ========== */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #e2edf2; font-size: 0.85rem; }
        th { background: #f8fafc; font-weight: 600; color: #333; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        
        /* ========== ANNOUNCEMENT STYLES ========== */
        .announcement-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            position: relative;
        }
        .announcement-high { border-left: 4px solid #2c7da0; }
        .announcement-maintenance { border-left: 4px solid #2c7da0; }
        .announcement-medium { border-left: 4px solid #2c7da0; }
        .announcement-warning { border-left: 4px solid #2c7da0; }
        .announcement-low { border-left: 4px solid #2c7da0; }
        .announcement-info { border-left: 4px solid #2c7da0; }
        .announcement-success { border-left: 4px solid #2c7da0; }
        
        .announcement-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .announcement-title strong { font-size: 0.9rem; color: #0a2b38; }
        .announcement-date { font-size: 0.7rem; color: #64748b; margin-left: auto; }
        .announcement-message { font-size: 0.85rem; color: #334155; line-height: 1.5; }
        
        .badge-type {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .badge-high { background: #fde8e8; color: #2c7da0; }
        .badge-maintenance { background: #fde8e8; color: #2c7da0; }
        .badge-medium { background: #fff3e0; color: #2c7da0; }
        .badge-warning { background: #fff3e0; color: #2c7da0; }
        .badge-low { background: #d9f0e5; color: #2c7da0; }
        .badge-info { background: #e0f0f5; color: #2c7da0; }
        .badge-success { background: #d9f0e5; color: #2c7da0; }
        
        .announcement-dept {
            font-size: 0.65rem;
            color: #2c7da0;
            background: #e8ecf8;
            padding: 2px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        .announcement-doc-link {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e2edf2;
        }
        .announcement-doc-link a { color: #2c7da0; text-decoration: none; font-size: 0.75rem; }
        .announcement-doc-link a:hover { text-decoration: underline; }
        .announcement-doc-link i { margin-right: 4px; }
        
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
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-label { display: none; }
            .sidebar .welcome-text, .sidebar .student-name, .sidebar .student-id { display: none; }
            .main-content { margin-left: 70px; padding: 15px; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) { .stats-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="app-container">
    <!-- ========== SIDEBAR - STATIC ========== -->
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

    <!-- ========== MAIN CONTENT ========== -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Dashboard | IAA Helpdesk</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- ============================================================ -->
        <!-- ========== ACADEMIC DOCUMENTS - ACCORDION STYLE ========== -->
        <!-- ============================================================ -->
        <div class="widget-card" style="padding: 0; overflow: hidden;">
            
            <!-- ===== HEADER (Inabonyezwa kupanua) ===== -->
            <div onclick="toggleDocuments()" style="
                padding: 14px 22px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
                background: white;
                border-bottom: 1px solid #e2edf2;
                user-select: none;
            ">
                
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-graduation-cap" style="color: #2c7da0; font-size: 16px;"></i>
                    <span style="font-size: 0.95rem; font-weight: 600; color: #0a2b38;">Academic Documents</span>
                    <span style="font-size: 0.6rem; background: #e8ecf8; color: #2c7da0; padding: 2px 12px; border-radius: 20px;">
                        <?php echo count(array_filter($latest_documents)); ?> available
                    </span>
                </div>
                
                <!-- Chevron -->
                <div id="docToggleIcon" style="color: #94a3b8; font-size: 14px; transition: transform 0.3s;">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            
            <!-- ===== BODY (Inapanuka/Inajificha) ===== -->
            <div id="documentsBody" style="
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.4s ease;
                background: #fafbfc;
            ">
                <div style="padding: 8px 22px 14px 22px;">
                    
                    <!-- ===== Class Timetable ===== -->
                    <?php 
                    $doc = $latest_documents['class_timetable'] ?? null;
                    $has_doc = $doc !== null;
                    $action_text = $has_doc ? 'Click to View' : 'Not uploaded yet';
                    $action_icon = $has_doc ? 'fa-eye' : 'fa-clock';
                    $file_path = $has_doc ? htmlspecialchars($doc['file_path']) : '#';
                    $color = '#2c7da0';
                    ?>
                    <a href="<?php echo $file_path; ?>" 
                       <?php echo $has_doc ? 'target="_blank"' : 'onclick="return false;"'; ?>
                       style="
                           display: flex;
                           align-items: center;
                           justify-content: space-between;
                           padding: 10px 0;
                           text-decoration: none;
                           border-bottom: 1px solid #f0f0f0;
                           cursor: <?php echo $has_doc ? 'pointer' : 'default'; ?>;
                           opacity: <?php echo $has_doc ? '1' : '0.5'; ?>;
                       ">
                        
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-calendar-check" style="color: <?php echo $color; ?>; font-size: 14px; width: 18px;"></i>
                            <span style="font-weight: 500; color: #0a2b38; font-size: 0.9rem;">Class Timetable</span>
                            <span style="color: #94a3b8; font-size: 0.8rem;">—</span>
                            <span style="color: #2c7da0; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;">
                                <i class="fas <?php echo $action_icon; ?>" style="font-size: 11px;"></i>
                                <?php echo $action_text; ?>
                            </span>
                        </div>
                        
                        <i class="fas fa-chevron-right" style="color: #d0d0d0; font-size: 12px;"></i>
                    </a>
                    
                    <!-- ===== Exam Timetable ===== -->
                    <?php 
                    $doc = $latest_documents['exam_timetable'] ?? null;
                    $has_doc = $doc !== null;
                    $action_text = $has_doc ? 'Click to Download' : 'Not uploaded yet';
                    $action_icon = $has_doc ? 'fa-download' : 'fa-clock';
                    $file_path = $has_doc ? htmlspecialchars($doc['file_path']) : '#';
                    $color = '#2c7da0';
                    ?>
                    <a href="<?php echo $file_path; ?>" 
                       <?php echo $has_doc ? 'target="_blank"' : 'onclick="return false;"'; ?>
                       style="
                           display: flex;
                           align-items: center;
                           justify-content: space-between;
                           padding: 10px 0;
                           text-decoration: none;
                           border-bottom: 1px solid #f0f0f0;
                           cursor: <?php echo $has_doc ? 'pointer' : 'default'; ?>;
                           opacity: <?php echo $has_doc ? '1' : '0.5'; ?>;
                       ">
                        
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-file-alt" style="color: <?php echo $color; ?>; font-size: 14px; width: 18px;"></i>
                            <span style="font-weight: 500; color: #0a2b38; font-size: 0.9rem;">Exam Timetable</span>
                            <span style="color: #94a3b8; font-size: 0.8rem;">—</span>
                            <span style="color: #2c7da0; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;">
                                <i class="fas <?php echo $action_icon; ?>" style="font-size: 11px;"></i>
                                <?php echo $action_text; ?>
                            </span>
                        </div>
                        
                        <i class="fas fa-chevron-right" style="color: #d0d0d0; font-size: 12px;"></i>
                    </a>
                    
                    <!-- ===== Test Timetable ===== -->
                    <?php 
                    $doc = $latest_documents['test_timetable'] ?? null;
                    $has_doc = $doc !== null;
                    $action_text = $has_doc ? 'Click to View' : 'Not uploaded yet';
                    $action_icon = $has_doc ? 'fa-eye' : 'fa-clock';
                    $file_path = $has_doc ? htmlspecialchars($doc['file_path']) : '#';
                    $color = '#2c7da0';
                    ?>
                    <a href="<?php echo $file_path; ?>" 
                       <?php echo $has_doc ? 'target="_blank"' : 'onclick="return false;"'; ?>
                       style="
                           display: flex;
                           align-items: center;
                           justify-content: space-between;
                           padding: 10px 0;
                           text-decoration: none;
                           border-bottom: 1px solid #f0f0f0;
                           cursor: <?php echo $has_doc ? 'pointer' : 'default'; ?>;
                           opacity: <?php echo $has_doc ? '1' : '0.5'; ?>;
                       ">
                        
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-pen-alt" style="color: <?php echo $color; ?>; font-size: 14px; width: 18px;"></i>
                            <span style="font-weight: 500; color: #0a2b38; font-size: 0.9rem;">Test Timetable</span>
                            <span style="color: #94a3b8; font-size: 0.8rem;">—</span>
                            <span style="color: #2c7da0; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;">
                                <i class="fas <?php echo $action_icon; ?>" style="font-size: 11px;"></i>
                                <?php echo $action_text; ?>
                            </span>
                        </div>
                        
                        <i class="fas fa-chevron-right" style="color: #d0d0d0; font-size: 12px;"></i>
                    </a>
                    
                    <!-- ===== Almanac ===== -->
                    <?php 
                    $doc = $latest_documents['almanac'] ?? null;
                    $has_doc = $doc !== null;
                    $action_text = $has_doc ? 'Click to View' : 'Not uploaded yet';
                    $action_icon = $has_doc ? 'fa-eye' : 'fa-clock';
                    $file_path = $has_doc ? htmlspecialchars($doc['file_path']) : '#';
                    $color = '#2c7da0';
                    ?>
                    <a href="<?php echo $file_path; ?>" 
                       <?php echo $has_doc ? 'target="_blank"' : 'onclick="return false;"'; ?>
                       style="
                           display: flex;
                           align-items: center;
                           justify-content: space-between;
                           padding: 10px 0;
                           text-decoration: none;
                           cursor: <?php echo $has_doc ? 'pointer' : 'default'; ?>;
                           opacity: <?php echo $has_doc ? '1' : '0.5'; ?>;
                       ">
                        
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-calendar-alt" style="color: <?php echo $color; ?>; font-size: 14px; width: 18px;"></i>
                            <span style="font-weight: 500; color: #0a2b38; font-size: 0.9rem;">Almanac</span>
                            <span style="color: #94a3b8; font-size: 0.8rem;">—</span>
                            <span style="color: #2c7da0; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;">
                                <i class="fas <?php echo $action_icon; ?>" style="font-size: 11px;"></i>
                                <?php echo $action_text; ?>
                            </span>
                        </div>
                        
                        <i class="fas fa-chevron-right" style="color: #d0d0d0; font-size: 12px;"></i>
                    </a>
                    
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ========== STATS CARDS ========== -->
        <!-- ============================================================ -->
        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number"><?php echo $stats['open_count'] ?? 0; ?></div><div>Open Queries</div></div>
            <div class="stat-card"><i class="fas fa-spinner"></i><div class="stat-number"><?php echo $stats['progress_count'] ?? 0; ?></div><div>In Progress</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number"><?php echo $stats['resolved_count'] ?? 0; ?></div><div>Resolved</div></div>
            <div class="stat-card"><i class="fas fa-tachometer-alt"></i><div class="stat-number"><?php echo $stats['total_count'] ?? 0; ?></div><div>Total Queries</div></div>
        </div>

        <!-- ============================================================ -->
        <!-- ========== RECENT QUERIES ========== -->
        <!-- ============================================================ -->
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
                                <td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;">
                                    <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                    No queries yet. 
                                    <a href="student_submit-query.php" style="color: #2c7da0; text-decoration: none; font-weight: 600;">Submit your first query</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while($ticket = mysqli_fetch_assoc($recent_result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ticket['ticket_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?></td>
                                <td><?php echo isset($departments[$ticket['department_id']]) ? htmlspecialchars($departments[$ticket['department_id']]) : 'Unknown'; ?></td>
                                <td><span class="status-badge <?php echo $ticket['status'] == 'resolved' ? 'status-resolved' : ''; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ========== ANNOUNCEMENTS ========== -->
        <!-- ============================================================ -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>📢 Announcements</strong>
                <span style="font-size:0.7rem; color:#94a3b8;">
                    <i class="fas fa-info-circle"></i> From all departments
                </span>
            </div>
            
            <?php if (!empty($active_announcements)): ?>
                <?php foreach ($active_announcements as $ann): 
                    $type = $ann['type'] ?? ($ann['priority'] ?? 'info');
                    $priority = $ann['priority'] ?? ($ann['type'] ?? 'Medium');
                    
                    $ann_class = 'announcement-' . strtolower($type);
                    $badge_class = 'badge-' . strtolower($type);
                    
                    if (isset($ann['source']) && $ann['source'] == 'exam') {
                        $badge_class = 'badge-' . strtolower($ann['priority']);
                        $ann_class = 'announcement-' . strtolower($ann['priority']);
                    }
                    
                    $dept_name = $ann['department_name'] ?? 'Information Center';
                    $title = $ann['title'] ?? '';
                    $message = $ann['message'] ?? '';
                    $created_at = $ann['created_at'] ?? '';
                    $document_path = $ann['document_path'] ?? null;
                    $document_name = $ann['document_name'] ?? null;
                ?>
                <div class="announcement-item <?php echo $ann_class; ?>">
                    <div class="announcement-title">
                        <strong><?php echo htmlspecialchars($title); ?></strong>
                        <span class="badge-type <?php echo $badge_class; ?>">
                            <?php echo ucfirst(strtolower($type)); ?>
                        </span>
                        <span class="announcement-dept">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($dept_name); ?>
                        </span>
                        <span class="announcement-date">
                            <?php echo date('d/m/Y H:i', strtotime($created_at)); ?>
                        </span>
                    </div>
                    <div class="announcement-message">
                        <?php echo nl2br(htmlspecialchars($message)); ?>
                    </div>
                    <?php if ($document_path && $document_name): 
                        $ext = strtolower(pathinfo($document_name, PATHINFO_EXTENSION));
                        $view_files = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                        $is_viewable = in_array($ext, $view_files);
                        $target = $is_viewable ? 'target="_blank"' : 'download';
                    ?>
                        <div class="announcement-doc-link">
                            <i class="fas fa-paperclip"></i> 
                            <a href="<?php echo htmlspecialchars($document_path); ?>" <?php echo $target; ?>>
                                <?php echo htmlspecialchars($document_name); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:30px; color:#94a3b8;">
                    <i class="fas fa-bullhorn" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                    No announcements available
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
    // ========== SET CURRENT DATE ==========
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            dateElement.innerText = new Date().toLocaleDateString('en-US', options);
        }
    }
    setCurrentDate();

    // ========== TOGGLE DOCUMENTS ACCORDION ==========
    function toggleDocuments() {
        const body = document.getElementById('documentsBody');
        const icon = document.getElementById('docToggleIcon');
        
        if (body.style.maxHeight === '0px' || body.style.maxHeight === '') {
            body.style.maxHeight = '500px';
            icon.style.transform = 'rotate(180deg)';
        } else {
            body.style.maxHeight = '0px';
            icon.style.transform = 'rotate(0deg)';
        }
    }

    // ========== LOGOUT CONFIRMATION ==========
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>