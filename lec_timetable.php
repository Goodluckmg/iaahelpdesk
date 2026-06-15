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

// Handle announcement with document upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $target_audience = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $author = $_SESSION['fullname'] ?? 'Examinations Officer';
    
    $has_document = 0;
    $doc_name = null;
    $doc_path = null;
    
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] == 0) {
        $file = $_FILES['doc_file'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        
        if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
            $doc_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $doc_path = $upload_dir . $doc_name;
            move_uploaded_file($file['tmp_name'], $doc_path);
            $has_document = 1;
        }
    }
    
    $query = "INSERT INTO exam_announcements (title, message, priority, target_audience, author, has_document, document_name, document_path) 
              VALUES ('$title', '$message', '$priority', '$target_audience', '$author', '$has_document', '$doc_name', '$doc_path')";
    mysqli_query($conn, $query);
    echo "<script>alert('Announcement posted successfully!'); window.location.href='lec_timetable.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Examinations Department - Documents & Announcements</title>
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
        .upload-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .upload-card { background: #f8fafc; border-radius: 16px; padding: 20px; text-align: center; transition: 0.2s; border: 1px solid #e2edf2; }
        .upload-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .upload-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #667eea20, #764ba220); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
        .upload-icon i { font-size: 28px; color: #667eea; }
        .upload-card h3 { color: #0a2b38; margin-bottom: 5px; }
        .upload-card p { font-size: 0.75rem; color: #64748b; margin-bottom: 15px; }
        .upload-area { border: 2px dashed #cbdbe6; border-radius: 12px; padding: 15px; margin-top: 10px; cursor: pointer; transition: 0.2s; background: white; }
        .upload-area:hover { border-color: #667eea; background: #f1f5f9; }
        .file-info { background: #e3f2fd; padding: 8px 12px; border-radius: 10px; margin-top: 10px; font-size: 0.75rem; display: none; }
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
        .doc-actions button { background: none; border: none; cursor: pointer; margin-left: 10px; color: #94a3b8; transition: 0.2s; }
        .doc-actions button:hover { color: #e74c3c; }
        .announcement-list { max-height: 350px; overflow-y: auto; }
        .announcement-item { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 12px; border-left: 4px solid #667eea; transition: 0.2s; }
        .announcement-item:hover { background: #f1f5f9; }
        .announcement-title { font-weight: 700; color: #0a2b38; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .announcement-meta { font-size: 0.7rem; color: #94a3b8; margin-bottom: 8px; display: flex; gap: 15px; flex-wrap: wrap; }
        .announcement-meta i { margin-right: 3px; }
        .announcement-message { font-size: 0.85rem; color: #334155; line-height: 1.4; margin-bottom: 10px; }
        .announcement-badge { background: #667eea; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.6rem; }
        .announcement-badge.high { background: #e74c3c; }
        .announcement-badge.medium { background: #f39c12; }
        .announcement-badge.low { background: #27ae60; }
        .delete-announcement { color: #e74c3c; cursor: pointer; font-size: 0.8rem; opacity: 0.6; transition: 0.2s; }
        .delete-announcement:hover { opacity: 1; }
        .announcement-doc { margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2edf2; }
        .doc-link { color: #2c7da0; text-decoration: none; font-size: 0.75rem; }
        .doc-link:hover { text-decoration: underline; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 550px; max-height: 85vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        input, textarea, select { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #cbdbe6; outline: none; }
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
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar span { display: none; } .main-content { margin-left: 70px; } .upload-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="app-container">
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

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Academic Documents & Announcements</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- UPLOAD DOCUMENTS SECTION -->
        <div class="widget-card">
            <div class="flex-between"><strong><i class="fas fa-upload"></i> Upload Academic Documents</strong><span style="font-size:0.7rem; color:#666;">Supported: PDF, Excel, Word, Image</span></div>
            <div class="upload-grid">
                <div class="upload-card"><div class="upload-icon"><i class="fas fa-calendar-alt"></i></div><h3>Almanac</h3><p>Academic Calendar, Important Dates, Semester Schedule</p><div class="upload-area" data-type="almanac"><i class="fas fa-cloud-upload-alt"></i><p>Click to upload Almanac</p><input type="file" class="file-input" accept=".pdf,.xlsx,.xls,.doc,.docx" style="display:none;"></div><div class="file-info"></div></div>
                <div class="upload-card"><div class="upload-icon"><i class="fas fa-pen-alt"></i></div><h3>Test Timetable</h3><p>Continuous Assessment Tests, Quizzes Schedule</p><div class="upload-area" data-type="test_timetable"><i class="fas fa-cloud-upload-alt"></i><p>Click to upload Test Timetable</p><input type="file" class="file-input" accept=".pdf,.xlsx,.xls" style="display:none;"></div><div class="file-info"></div></div>
                <div class="upload-card"><div class="upload-icon"><i class="fas fa-file-alt"></i></div><h3>Exam Timetable</h3><p>Final Examination Schedule</p><div class="upload-area" data-type="exam_timetable"><i class="fas fa-cloud-upload-alt"></i><p>Click to upload Exam Timetable</p><input type="file" class="file-input" accept=".pdf,.xlsx,.xls" style="display:none;"></div><div class="file-info"></div></div>
            </div>
        </div>

        <!-- AVAILABLE DOCUMENTS SECTION -->
        <div class="widget-card">
            <div class="flex-between"><strong><i class="fas fa-folder-open"></i> Available Documents</strong><button class="btn-primary btn-outline" id="refreshDocsBtn"><i class="fas fa-sync-alt"></i> Refresh</button></div>
            <div class="documents-list" id="documentsList"><div style="text-align:center; padding:20px;">Loading documents...</div></div>
        </div>

        <!-- ANNOUNCEMENTS SECTION -->
        <div class="widget-card">
            <div class="flex-between"><strong><i class="fas fa-bullhorn"></i> Announcements</strong><button class="btn-primary" id="newAnnouncementBtn"><i class="fas fa-plus"></i> New Announcement</button></div>
            <div class="announcement-list" id="announcementList"><div style="text-align:center; padding:20px;">Loading announcements...</div></div>
        </div>
    </main>
</div>

<!-- MODAL FOR NEW ANNOUNCEMENT -->
<div id="announcementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-bullhorn"></i> New Announcement</h3><span class="close-modal">&times;</span></div>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group"><label>Announcement Title</label><input type="text" name="title" placeholder="e.g., Mid-Semester Examinations Schedule" required></div>
            <div class="form-group"><label>Message / Details</label><textarea name="message" rows="4" placeholder="Write detailed announcement here..." required></textarea></div>
            <div class="form-group"><label>Priority Level</label><div class="priority-select"><label class="priority-option"><input type="radio" name="priority" value="High" checked> <span style="color:#e74c3c;">🔴 High</span></label><label class="priority-option"><input type="radio" name="priority" value="Medium"> <span style="color:#f39c12;">🟡 Medium</span></label><label class="priority-option"><input type="radio" name="priority" value="Low"> <span style="color:#27ae60;">🟢 Low</span></label></div></div>
            <div class="form-group"><label>Target Audience</label><select name="target_audience"><option value="All Students & Staff">All Students & Staff</option><option value="All Students">All Students Only</option><option value="Staff Only">Staff Only</option><option value="ICT Department">ICT Department</option><option value="Finance Department">Finance Department</option></select></div>
            <div class="document-upload-section"><div class="document-header"><i class="fas fa-paperclip"></i><h4>Attach Document <span class="optional-badge">Optional</span></h4></div><div class="file-input-group"><label class="document-file-label"><i class="fas fa-upload"></i> Choose File<input type="file" name="doc_file" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.png" style="display: none;" onchange="updateFileName(this)"></label><span class="selected-file-name" id="selectedFileName">No file chosen</span><button type="button" class="remove-file-btn" onclick="removeFile()">Remove</button></div><p style="font-size:0.7rem; color:#7f8c8d; margin-top:10px;"><i class="fas fa-info-circle"></i> Max 5MB. Allowed: PDF, Excel, Word, Image</p></div>
            <div style="display:flex; gap:10px; justify-content:flex-end;"><button type="button" class="btn-primary" id="cancelAnnouncementBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" name="post_announcement" class="btn-primary">Post Announcement</button></div>
        </form>
    </div>
</div>

<script>
    function loadDocuments() {
        fetch('get_documents.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('documentsList');
                if (data.length === 0) { container.innerHTML = '<div style="text-align:center; padding:20px; color:#94a3b8;">No documents uploaded yet</div>'; return; }
                let html = '';
                const categories = ['almanac', 'test_timetable', 'exam_timetable'];
                const categoryNames = { almanac: 'Almanac', test_timetable: 'Test Timetable', exam_timetable: 'Exam Timetable' };
                const categoryIcons = { almanac: 'fas fa-calendar-alt', test_timetable: 'fas fa-pen-alt', exam_timetable: 'fas fa-file-alt' };
                for (const cat of categories) {
                    const docs = data.filter(d => d.category === cat);
                    if (docs.length > 0) {
                        html += `<div class="doc-category"><h4><i class="${categoryIcons[cat]}" style="margin-right:8px;"></i> ${categoryNames[cat]}</h4>${docs.map(doc => `<div class="doc-item"><div class="doc-info"><i class="fas fa-file-pdf"></i><div class="doc-details"><span class="doc-name">${escapeHtml(doc.file_name)}</span><span class="doc-meta">Uploaded: ${doc.created_at} | By: ${escapeHtml(doc.uploaded_by)}</span></div></div><div class="doc-actions"><button class="view-doc" data-id="${doc.id}"><i class="fas fa-download"></i></button><button class="delete-doc" data-id="${doc.id}"><i class="fas fa-trash-alt"></i></button></div></div>`).join('')}</div>`;
                    }
                }
                container.innerHTML = html || '<div style="text-align:center; padding:20px; color:#94a3b8;">No documents uploaded yet</div>';
                document.querySelectorAll('.view-doc').forEach(btn => btn.addEventListener('click', () => window.open('download_document.php?id=' + btn.dataset.id, '_blank')));
                document.querySelectorAll('.delete-doc').forEach(btn => btn.addEventListener('click', () => { if(confirm('Delete?')) fetch('delete_document.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+btn.dataset.id}).then(()=>loadDocuments()); }));
            });
    }
    
    function loadAnnouncements() {
        fetch('get_announcements.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('announcementList');
                if (data.length === 0) { container.innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8;">No announcements yet. Click "New Announcement" to post.</div>'; return; }
                container.innerHTML = data.map(a => `<div class="announcement-item"><div class="announcement-title"><span><i class="fas fa-bullhorn"></i> ${escapeHtml(a.title)}</span><span class="delete-announcement" data-id="${a.id}"><i class="fas fa-trash-alt"></i></span></div><div class="announcement-meta"><span><i class="far fa-calendar-alt"></i> ${a.created_at}</span><span><i class="fas fa-user"></i> ${escapeHtml(a.author)}</span><span><i class="fas fa-users"></i> ${escapeHtml(a.target_audience)}</span><span class="announcement-badge ${a.priority === 'High' ? 'high' : (a.priority === 'Medium' ? 'medium' : 'low')}">${a.priority} Priority</span></div><div class="announcement-message">${escapeHtml(a.message)}</div>${a.has_document ? `<div class="announcement-doc"><i class="fas fa-paperclip"></i> <a href="#" class="doc-link view-announcement-doc" data-path="${escapeHtml(a.document_path)}" data-name="${escapeHtml(a.document_name)}">${escapeHtml(a.document_name)}</a></div>` : ''}</div>`).join('');
                document.querySelectorAll('.delete-announcement').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); if(confirm('Delete?')) fetch('delete_announcement.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+btn.dataset.id}).then(()=>loadAnnouncements()); }));
                document.querySelectorAll('.view-announcement-doc').forEach(link => link.addEventListener('click', (e) => { e.preventDefault(); window.open(link.dataset.path, '_blank'); }));
            });
    }
    
    function setupUploadCards() {
        document.querySelectorAll('.upload-area').forEach(area => {
            const fileInput = area.querySelector('.file-input');
            const fileInfo = area.querySelector('.file-info');
            const type = area.dataset.type;
            area.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    fileInfo.innerHTML = `<i class="fas fa-check-circle" style="color:#27ae60;"></i> ${file.name} (${fileSize} MB)<button class="confirm-upload" style="margin-left:10px; background:#27ae60; border:none; padding:2px 10px; border-radius:15px; color:white; cursor:pointer;">Confirm</button><button class="cancel-upload" style="margin-left:5px; background:#e74c3c; border:none; padding:2px 10px; border-radius:15px; color:white; cursor:pointer;">Cancel</button>`;
                    fileInfo.style.display = 'block';
                    fileInfo.querySelector('.confirm-upload').onclick = () => { const formData = new FormData(); formData.append('category', type); formData.append('file', file); fetch('upload_document.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => { if(data.success){ alert(data.message); fileInfo.style.display='none'; fileInput.value=''; loadDocuments(); } else alert('Error: '+data.message); }); };
                    fileInfo.querySelector('.cancel-upload').onclick = () => { fileInfo.style.display='none'; fileInput.value=''; };
                }
            });
        });
    }
    
    function setCurrentDate() { document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }); }
    function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, m => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;' }[m])); }
    function updateFileName(input) { document.getElementById('selectedFileName').innerText = input.files[0]?.name || 'No file chosen'; }
    function removeFile() { const input = document.querySelector('input[name="doc_file"]'); if(input) input.value = ''; document.getElementById('selectedFileName').innerText = 'No file chosen'; }
    
    document.getElementById('refreshDocsBtn')?.addEventListener('click', () => loadDocuments());
    const modal = document.getElementById('announcementModal');
    document.getElementById('newAnnouncementBtn')?.addEventListener('click', () => { document.querySelector('form').reset(); removeFile(); modal.style.display = 'flex'; });
    document.querySelectorAll('.close-modal, #cancelAnnouncementBtn').forEach(el => el.addEventListener('click', () => modal.style.display = 'none'));
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); if(confirm('Logout?')) window.location.href='logout.php'; });
    
    loadDocuments(); loadAnnouncements(); setupUploadCards(); setCurrentDate();
</script>
</body>
</html>