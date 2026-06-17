<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is exam officer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lecturer', 'academic', 'exam', 'exam_officer'])) {
    header('Location: login.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Create upload directory if not exists
$upload_dir = 'uploads/exam_documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ========== HANDLE ANNOUNCEMENT POST ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $target_audience = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $author = $_SESSION['fullname'] ?? 'Examinations Officer';
    
    $doc_name = null;
    $doc_path = null;
    
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] == 0) {
        $file = $_FILES['doc_file'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        
        if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $doc_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $doc_path = $upload_dir . $doc_name;
            move_uploaded_file($file['tmp_name'], $doc_path);
        }
    }
    
    $query = "INSERT INTO exam_announcements (title, message, priority, target_audience, author, document_name, document_path) 
              VALUES ('$title', '$message', '$priority', '$target_audience', '$author', '$doc_name', '$doc_path')";
    mysqli_query($conn, $query);
    echo "<script>alert('Announcement posted successfully!'); window.location.href='lec_timetable.php';</script>";
    exit();
}

// ========== HANDLE DOCUMENT UPLOAD ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_document'])) {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $uploaded_by = $_SESSION['fullname'] ?? 'Examinations Officer';
    
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] == 0) {
        $file = $_FILES['doc_file'];
        $allowed = ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg', 'image/png', 'image/jpg'];
        
        if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $query = "INSERT INTO exam_documents (category, file_name, file_path, uploaded_by) 
                          VALUES ('$category', '$file_name', '$file_path', '$uploaded_by')";
                mysqli_query($conn, $query);
                echo "<script>alert('Document uploaded successfully!'); window.location.href='lec_timetable.php';</script>";
                exit();
            }
        }
    }
    echo "<script>alert('Upload failed. Please try again.'); window.location.href='lec_timetable.php';</script>";
    exit();
}

// ========== HANDLE TIMETABLE UPLOAD ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_timetable'])) {
    $uploaded_by = $_SESSION['fullname'] ?? 'Examinations Officer';
    $category = 'class_timetable';
    
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] == 0) {
        $file = $_FILES['doc_file'];
        $allowed = ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg', 'image/png', 'image/jpg'];
        
        if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $query = "INSERT INTO exam_documents (category, file_name, file_path, uploaded_by) 
                          VALUES ('$category', '$file_name', '$file_path', '$uploaded_by')";
                mysqli_query($conn, $query);
                echo "<script>alert('Timetable uploaded successfully!'); window.location.href='lec_timetable.php';</script>";
                exit();
            }
        }
    }
    echo "<script>alert('Upload failed. Please try again.'); window.location.href='lec_timetable.php';</script>";
    exit();
}

// ========== HANDLE DOCUMENT DELETE ==========
if (isset($_GET['delete_doc'])) {
    $id = intval($_GET['delete_doc']);
    $query = "SELECT file_path FROM exam_documents WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $doc = mysqli_fetch_assoc($result);
    
    if ($doc && file_exists($doc['file_path'])) {
        unlink($doc['file_path']);
    }
    mysqli_query($conn, "DELETE FROM exam_documents WHERE id = $id");
    header('Location: lec_timetable.php');
    exit();
}

// ========== HANDLE TIMETABLE DELETE ==========
if (isset($_GET['delete_timetable'])) {
    $id = intval($_GET['delete_timetable']);
    $query = "SELECT file_path FROM exam_documents WHERE id = $id AND category = 'class_timetable'";
    $result = mysqli_query($conn, $query);
    $doc = mysqli_fetch_assoc($result);
    
    if ($doc && file_exists($doc['file_path'])) {
        unlink($doc['file_path']);
    }
    mysqli_query($conn, "DELETE FROM exam_documents WHERE id = $id AND category = 'class_timetable'");
    header('Location: lec_timetable.php');
    exit();
}

// ========== HANDLE ANNOUNCEMENT DELETE ==========
if (isset($_GET['delete_announcement'])) {
    $id = intval($_GET['delete_announcement']);
    $query = "SELECT document_path FROM exam_announcements WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $ann = mysqli_fetch_assoc($result);
    
    if ($ann && $ann['document_path'] && file_exists($ann['document_path'])) {
        unlink($ann['document_path']);
    }
    mysqli_query($conn, "DELETE FROM exam_announcements WHERE id = $id");
    header('Location: lec_timetable.php');
    exit();
}

// ========== FETCH DATA ==========
// Fetch documents
$docs_result = mysqli_query($conn, "SELECT * FROM exam_documents ORDER BY created_at DESC");
$documents = [];
while ($row = mysqli_fetch_assoc($docs_result)) {
    $documents[] = $row;
}

// Fetch announcements
$ann_result = mysqli_query($conn, "SELECT * FROM exam_announcements ORDER BY created_at DESC");
$announcements = [];
while ($row = mysqli_fetch_assoc($ann_result)) {
    $announcements[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Examinations Department</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; background: #f5f7fa; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #0a2b38 0%, #0d3b4c 100%); color: #e0edf5; display: flex; flex-direction: column; overflow-y: auto; position: fixed; height: 100vh; left: 0; top: 0; z-index: 100; }
        .profile-area { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea, #764ba2); }
        .avatar i { font-size: 40px; color: white; }
        .welcome-text { font-size: 0.85rem; color: #94a3b8; }
        .user-name { font-size: 1.2rem; font-weight: 600; margin: 5px 0; color: white; }
        .user-role { font-size: 0.7rem; background: #667eea; display: inline-block; padding: 3px 12px; border-radius: 20px; }
        .user-id { font-size: 0.7rem; margin-top: 8px; color: #94a3b8; }
        .nav-menu { flex: 1; padding: 15px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; color: #cbdbe6; text-decoration: none; margin-bottom: 5px; transition: 0.2s; cursor: pointer; }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: #667eea; color: white; }
        .nav-item i { width: 20px; }
        .logout-item { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        .main-content { flex: 1; padding: 20px 25px; overflow-y: auto; margin-left: 280px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.6rem; color: #0a2b38; }
        .date-badge { background: white; padding: 8px 18px; border-radius: 30px; font-size: 0.8rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .btn-primary { background: #667eea; border: none; padding: 8px 20px; border-radius: 25px; color: white; cursor: pointer; font-size: 0.8rem; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #5a67d8; transform: translateY(-2px); }
        .btn-outline { background: transparent; border: 1px solid #667eea; color: #667eea; }
        .btn-outline:hover { background: #667eea; color: white; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #1e8449; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #d68910; }
        .upload-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .upload-card { background: #f8fafc; border-radius: 16px; padding: 20px; text-align: center; transition: 0.2s; border: 1px solid #e2edf2; }
        .upload-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .upload-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #667eea20, #764ba220); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
        .upload-icon i { font-size: 28px; color: #667eea; }
        .upload-card h3 { color: #0a2b38; margin-bottom: 5px; }
        .upload-card p { font-size: 0.75rem; color: #64748b; margin-bottom: 15px; }
        .upload-form { border: 2px dashed #cbdbe6; border-radius: 12px; padding: 15px; margin-top: 10px; background: white; }
        .upload-form:hover { border-color: #667eea; background: #f1f5f9; }
        .upload-form input[type="file"] { display: none; }
        .upload-form label { cursor: pointer; display: block; padding: 10px; }
        .upload-form select { width: 100%; padding: 6px; border-radius: 6px; border: 1px solid #ddd; font-size: 0.75rem; margin-bottom: 8px; }
        .upload-form button { margin-top: 10px; }
        .documents-list { margin-top: 20px; }
        .doc-category { margin-bottom: 20px; }
        .doc-category h4 { color: #0a2b38; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid #667eea; display: inline-block; }
        .doc-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8fafc; border-radius: 12px; margin-bottom: 8px; transition: 0.2s; }
        .doc-item:hover { background: #f1f5f9; }
        .doc-info { display: flex; align-items: center; gap: 12px; }
        .doc-info i { font-size: 24px; color: #e74c3c; }
        .doc-details { display: flex; flex-direction: column; }
        .doc-name { font-weight: 600; color: #0a2b38; }
        .doc-meta { font-size: 0.7rem; color: #94a3b8; }
        .doc-actions a { margin-left: 8px; padding: 4px 10px; border-radius: 15px; font-size: 0.7rem; text-decoration: none; color: white; display: inline-block; }
        .doc-actions .view-doc { background: #2c7da0; }
        .doc-actions .view-doc:hover { background: #1a5a7a; }
        .doc-actions .del-doc { background: #e74c3c; }
        .doc-actions .del-doc:hover { background: #c0392b; }
        .announcement-list { max-height: 400px; overflow-y: auto; }
        .announcement-item { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 12px; border-left: 4px solid #667eea; transition: 0.2s; position: relative; }
        .announcement-item:hover { background: #f1f5f9; }
        .announcement-title { font-weight: 700; color: #0a2b38; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .announcement-meta { font-size: 0.7rem; color: #94a3b8; margin-bottom: 8px; display: flex; gap: 15px; flex-wrap: wrap; }
        .announcement-meta i { margin-right: 3px; }
        .announcement-message { font-size: 0.85rem; color: #334155; line-height: 1.4; margin-bottom: 10px; }
        .announcement-badge { background: #667eea; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.6rem; }
        .announcement-badge.high { background: #e74c3c; }
        .announcement-badge.medium { background: #f39c12; }
        .announcement-badge.low { background: #27ae60; }
        .announcement-doc { margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2edf2; }
        .announcement-doc a { color: #2c7da0; text-decoration: none; font-size: 0.75rem; }
        .announcement-doc a:hover { text-decoration: underline; }
        .del-ann { color: #e74c3c; cursor: pointer; font-size: 0.8rem; opacity: 0.6; transition: 0.2s; }
        .del-ann:hover { opacity: 1; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 550px; max-height: 85vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .close-modal:hover { color: #c0392b; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        input, textarea, select { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #cbdbe6; outline: none; }
        input:focus, textarea:focus, select:focus { border-color: #667eea; }
        .priority-select { display: flex; gap: 15px; margin-top: 5px; }
        .priority-option { display: flex; align-items: center; gap: 5px; cursor: pointer; }
        .priority-option input { width: auto; }
        .document-upload-section { background: #f8fafc; border-radius: 16px; padding: 15px; margin-bottom: 20px; border: 1px dashed #cbd5e1; }
        .document-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .document-header i { font-size: 20px; color: #2c7da0; }
        .optional-badge { background: #e0f0f5; color: #2c7da0; font-size: 0.6rem; padding: 2px 8px; border-radius: 20px; margin-left: 8px; }
        .file-input-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .document-file-label { background: #2c7da0; color: white; padding: 8px 16px; border-radius: 30px; font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .selected-file-name { font-size: 0.7rem; color: #2c7da0; }
        .remove-file-btn { background: #c0392b; color: white; border: none; padding: 5px 12px; border-radius: 20px; font-size: 0.65rem; cursor: pointer; }
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar span { display: none; } 
            .main-content { margin-left: 70px; } 
            .upload-grid { grid-template-columns: 1fr; } 
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-graduation-cap"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($user['fullname'] ?? 'Dr. Sarah Examinations'); ?></div>
            <div class="user-role">📋 Examinations Officer</div>
            <div class="user-id"><?php echo htmlspecialchars($user['reg_no'] ?? 'STAFF/EXAM/001'); ?></div>
        </div>
        <div class="nav-menu">
            <a href="lecturers.php" class="nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="lec_pending.php" class="nav-item"><i class="fas fa-clock"></i><span>Student query</span></a>
            <a href="lec_resolved.php" class="nav-item"><i class="fas fa-check-circle"></i><span>Resolved</span></a>
            <a href="lec_timetable.php" class="nav-item active"><i class="fas fa-calendar-alt"></i><span>Exam Timetable</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Academic Documents & Announcements</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- ============================================================ -->
        <!-- ========== UPLOAD DOCUMENTS SECTION ========== -->
        <!-- ============================================================ -->
        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-upload"></i> Upload Academic Documents</strong>
                <span style="font-size:0.7rem; color:#666;">Supported: PDF, Excel, Word, Image (Max 5MB)</span>
            </div>
            <div class="upload-grid">
                <!-- Almanac -->
                <div class="upload-card">
                    <div class="upload-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h3>Almanac</h3>
                    <p>Academic Calendar, Important Dates, Semester Schedule</p>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="category" value="almanac">
                        <label><i class="fas fa-cloud-upload-alt"></i> Click to choose file
                            <input type="file" name="doc_file" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.png" required>
                        </label>
                        <button type="submit" name="upload_document" class="btn-primary btn-success" style="font-size:0.7rem; padding:5px 15px;">Upload</button>
                    </form>
                </div>

                <!-- Test Timetable -->
                <div class="upload-card">
                    <div class="upload-icon"><i class="fas fa-pen-alt"></i></div>
                    <h3>Test Timetable</h3>
                    <p>Continuous Assessment Tests, Quizzes Schedule</p>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="category" value="test_timetable">
                        <label><i class="fas fa-cloud-upload-alt"></i> Click to choose file
                            <input type="file" name="doc_file" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.png" required>
                        </label>
                        <button type="submit" name="upload_document" class="btn-primary btn-success" style="font-size:0.7rem; padding:5px 15px;">Upload</button>
                    </form>
                </div>

                <!-- Exam Timetable -->
                <div class="upload-card">
                    <div class="upload-icon"><i class="fas fa-file-alt"></i></div>
                    <h3>Exam Timetable</h3>
                    <p>Final Examination Schedule</p>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="category" value="exam_timetable">
                        <label><i class="fas fa-cloud-upload-alt"></i> Click to choose file
                            <input type="file" name="doc_file" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.png" required>
                        </label>
                        <button type="submit" name="upload_document" class="btn-primary btn-success" style="font-size:0.7rem; padding:5px 15px;">Upload</button>
                    </form>
                </div>

                <!-- Class Timetable -->
                <div class="upload-card">
                    <div class="upload-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3>Class Timetable</h3>
                    <p>Class Schedule for Current Semester</p>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="category" value="class_timetable">
                        <label><i class="fas fa-cloud-upload-alt"></i> Click to choose file
                            <input type="file" name="doc_file" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.png" required>
                        </label>
                        <button type="submit" name="upload_timetable" class="btn-primary btn-success" style="font-size:0.7rem; padding:5px 15px;">Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ========== AVAILABLE DOCUMENTS ========== -->
        <!-- ============================================================ -->
        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-folder-open"></i> Available Documents</strong>
                <a href="lec_timetable.php" class="btn-primary btn-outline" style="font-size:0.7rem;"><i class="fas fa-sync-alt"></i> Refresh</a>
            </div>
            <div class="documents-list">
                <?php
                $categories = [
                    'almanac' => 'Almanac', 
                    'test_timetable' => 'Test Timetable', 
                    'exam_timetable' => 'Exam Timetable',
                    'class_timetable' => 'Class Timetable'
                ];
                $icons = [
                    'almanac' => 'fas fa-calendar-alt', 
                    'test_timetable' => 'fas fa-pen-alt', 
                    'exam_timetable' => 'fas fa-file-alt',
                    'class_timetable' => 'fas fa-calendar-check'
                ];
                $file_icons = ['pdf' => 'fa-file-pdf', 'xlsx' => 'fa-file-excel', 'xls' => 'fa-file-excel', 
                              'doc' => 'fa-file-word', 'docx' => 'fa-file-word', 'jpg' => 'fa-file-image', 
                              'png' => 'fa-file-image', 'jpeg' => 'fa-file-image'];
                
                $has_docs = false;
                foreach ($categories as $cat => $label) {
                    $docs = array_filter($documents, function($d) use ($cat) { return $d['category'] == $cat; });
                    if (!empty($docs)) {
                        $has_docs = true;
                        echo '<div class="doc-category"><h4><i class="' . $icons[$cat] . '" style="margin-right:8px;"></i> ' . $label . '</h4>';
                        foreach ($docs as $doc) {
                            $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                            $icon = $file_icons[$ext] ?? 'fa-file';
                            echo '<div class="doc-item">
                                    <div class="doc-info">
                                        <i class="fas ' . $icon . '"></i>
                                        <div class="doc-details">
                                            <span class="doc-name">' . htmlspecialchars($doc['file_name']) . '</span>
                                            <span class="doc-meta">Uploaded: ' . date('d/m/Y H:i', strtotime($doc['created_at'])) . ' | By: ' . htmlspecialchars($doc['uploaded_by']) . '</span>
                                        </div>
                                    </div>
                                    <div class="doc-actions">';
                            if ($cat == 'class_timetable') {
                                echo '<a href="' . htmlspecialchars($doc['file_path']) . '" class="view-doc" target="_blank"><i class="fas fa-download"></i> View</a>
                                      <a href="?delete_timetable=' . $doc['id'] . '" class="del-doc" onclick="return confirm(\'Delete this timetable?\')"><i class="fas fa-trash-alt"></i> Delete</a>';
                            } else {
                                echo '<a href="' . htmlspecialchars($doc['file_path']) . '" class="view-doc" target="_blank"><i class="fas fa-download"></i> View</a>
                                      <a href="?delete_doc=' . $doc['id'] . '" class="del-doc" onclick="return confirm(\'Delete this document?\')"><i class="fas fa-trash-alt"></i> Delete</a>';
                            }
                            echo '    </div>
                                </div>';
                        }
                        echo '</div>';
                    }
                }
                if (!$has_docs) {
                    echo '<div style="text-align:center; padding:20px; color:#94a3b8;">No documents uploaded yet</div>';
                }
                ?>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ========== ANNOUNCEMENTS ========== -->
        <!-- ============================================================ -->
        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-bullhorn"></i> Announcements</strong>
                <button class="btn-primary" id="newAnnouncementBtn"><i class="fas fa-plus"></i> New Announcement</button>
            </div>
            <div class="announcement-list">
                <?php if (empty($announcements)): ?>
                    <div style="text-align:center; padding:30px; color:#94a3b8;">No announcements yet. Click "New Announcement" to post.</div>
                <?php else: ?>
                    <?php foreach ($announcements as $ann): ?>
                        <div class="announcement-item">
                            <div class="announcement-title">
                                <span><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($ann['title']); ?></span>
                                <span class="del-ann" onclick="if(confirm('Delete this announcement?')) window.location.href='?delete_announcement=<?php echo $ann['id']; ?>'"><i class="fas fa-trash-alt"></i></span>
                            </div>
                            <div class="announcement-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($ann['created_at'])); ?></span>
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($ann['author']); ?></span>
                                <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($ann['target_audience']); ?></span>
                                <span class="announcement-badge <?php echo strtolower($ann['priority']); ?>"><?php echo $ann['priority']; ?> Priority</span>
                            </div>
                            <div class="announcement-message"><?php echo nl2br(htmlspecialchars($ann['message'])); ?></div>
                            <?php if ($ann['document_path']): ?>
                                <div class="announcement-doc"><i class="fas fa-paperclip"></i> <a href="<?php echo htmlspecialchars($ann['document_path']); ?>" target="_blank"><?php echo htmlspecialchars($ann['document_name']); ?></a></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- ============================================================ -->
<!-- ========== MODAL: NEW ANNOUNCEMENT ========== -->
<!-- ============================================================ -->
<div id="announcementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-bullhorn"></i> New Announcement</h3>
            <span class="close-modal" onclick="closeAnnouncementModal()">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Announcement Title</label>
                <input type="text" name="title" placeholder="e.g., Mid-Semester Examinations Schedule" required>
            </div>
            <div class="form-group">
                <label>Message / Details</label>
                <textarea name="message" rows="4" placeholder="Write detailed announcement here..." required></textarea>
            </div>
            <div class="form-group">
                <label>Priority Level</label>
                <div class="priority-select">
                    <label class="priority-option"><input type="radio" name="priority" value="High" checked> <span style="color:#e74c3c;">🔴 High</span></label>
                    <label class="priority-option"><input type="radio" name="priority" value="Medium"> <span style="color:#f39c12;">🟡 Medium</span></label>
                    <label class="priority-option"><input type="radio" name="priority" value="Low"> <span style="color:#27ae60;">🟢 Low</span></label>
                </div>
            </div>
            <div class="form-group">
                <label>Target Audience</label>
                <select name="target_audience">
                    <option value="All Students & Staff">All Students & Staff</option>
                    <option value="All Students">All Students Only</option>
                    <option value="Staff Only">Staff Only</option>
                    <option value="ICT Department">ICT Department</option>
                    <option value="Finance Department">Finance Department</option>
                </select>
            </div>
            <div class="document-upload-section">
                <div class="document-header">
                    <i class="fas fa-paperclip"></i>
                    <h4>Attach Document <span class="optional-badge">Optional</span></h4>
                </div>
                <div class="file-input-group">
                    <label class="document-file-label">
                        <i class="fas fa-upload"></i> Choose File
                        <input type="file" name="doc_file" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.png" onchange="updateFileName(this)">
                    </label>
                    <span class="selected-file-name" id="selectedFileName">No file chosen</span>
                    <button type="button" class="remove-file-btn" onclick="removeFile()">Remove</button>
                </div>
                <p style="font-size:0.7rem; color:#7f8c8d; margin-top:10px;">
                    <i class="fas fa-info-circle"></i> Max 5MB. Allowed: PDF, Excel, Word, Image
                </p>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" onclick="closeAnnouncementModal()" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" name="post_announcement" class="btn-primary">Post Announcement</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ========== SET CURRENT DATE ==========
    function setCurrentDate() {
        document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { 
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' 
        });
    }
    setCurrentDate();

    // ========== ANNOUNCEMENT MODAL ==========
    function openAnnouncementModal() {
        document.getElementById('announcementModal').style.display = 'flex';
    }
    function closeAnnouncementModal() {
        document.getElementById('announcementModal').style.display = 'none';
    }
    document.getElementById('newAnnouncementBtn')?.addEventListener('click', openAnnouncementModal);
    document.querySelectorAll('.close-modal').forEach(el => el.addEventListener('click', closeAnnouncementModal));
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeAnnouncementModal();
        }
    });

    // ========== FILE UPLOAD HANDLERS ==========
    function updateFileName(input) {
        document.getElementById('selectedFileName').innerText = input.files[0]?.name || 'No file chosen';
    }
    function removeFile() {
        const input = document.querySelector('input[name="doc_file"]');
        if(input) input.value = '';
        document.getElementById('selectedFileName').innerText = 'No file chosen';
    }

    // ========== LOGOUT ==========
    document.querySelector('.logout-item a')?.addEventListener('click', (e) => {
        if(!confirm('Are you sure you want to logout?')) e.preventDefault();
    });
</script>
</body>
</html>