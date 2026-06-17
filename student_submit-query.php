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

// ========== GET PROFILE PHOTO ==========
$photo_query = "SELECT profile_photo FROM students WHERE id = $student_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;
// =======================================

// Get departments for dropdown
$dept_query = "SELECT id, name FROM departments WHERE status = 'active'";
$dept_result = mysqli_query($conn, $dept_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_query'])) {
    $title = mysqli_real_escape_string($conn, $_POST['qTitle']);
    $category = mysqli_real_escape_string($conn, $_POST['qCategory']);
    $department_id = mysqli_real_escape_string($conn, $_POST['qDept']);
    $priority = mysqli_real_escape_string($conn, $_POST['qPriority']);
    $description = mysqli_real_escape_string($conn, $_POST['qDesc']);
    
    // Generate ticket number
    $ticket_no = 'TK-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Handle document upload
    $has_document = 0;
    $document_name = null;
    $document_path = null;
    $document_type = null;
    
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $file = $_FILES['document_file'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        
        if (in_array($file['type'], $allowed) && $file['size'] <= 2 * 1024 * 1024) {
            $upload_dir = 'uploads/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $document_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $document_path = 'uploads/' . $document_name;
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $document_name)) {
                $has_document = 1;
                $document_type = $file['type'];
            } else {
                $error_message = "Failed to upload document. Please check folder permissions.";
            }
        } else {
            $error_message = "Invalid file type or file too large. Max 2MB, allowed: JPG, PNG, PDF.";
        }
    }
    
    $insert_query = "INSERT INTO tickets (ticket_no, user_id, title, category, department_id, priority, description, has_document, document_name, document_path, document_type, status) 
                     VALUES ('$ticket_no', '$student_id', '$title', '$category', '$department_id', '$priority', '$description', '$has_document', '$document_name', '$document_path', '$document_type', 'open')";
    
    if (mysqli_query($conn, $insert_query)) {
        header("Location: student_my-queries.php?success=1");
        exit();
    } else {
        $error_message = "Error submitting query: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Submit Query</title>
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
        
        /* ========== FORM STYLES ========== */
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; 
            padding: 10px 12px; 
            border-radius: 12px; 
            border: 1px solid #cbdbe6; 
            outline: none; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2c7da0; }
        
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
        
        /* ========== DOCUMENT UPLOAD ========== */
        .document-upload-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px dashed #cbd5e1;
        }
        .document-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .document-header i { font-size: 20px; color: #2c7da0; }
        .optional-badge { background: #e0f0f5; color: #2c7da0; font-size: 0.6rem; padding: 2px 8px; border-radius: 20px; margin-left: 8px; }
        .file-input-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .document-file-label { background: #2c7da0; color: white; padding: 8px 16px; border-radius: 30px; font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .document-file-label:hover { background: #1f5a7a; }
        .selected-file-name { font-size: 0.7rem; color: #2c7da0; }
        .remove-file-btn { background: #2c7da0; color: white; border: none; padding: 5px 12px; border-radius: 20px; font-size: 0.65rem; cursor: pointer; }
        .remove-file-btn:hover { background: #1f5a7a; }
        .document-note { font-size:0.7rem; color:#7f8c8d; margin-top:10px; }
        
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
            <a href="student_submit-query.php" class="nav-item active"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="student_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="student_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="student_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="student_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Submit New Query</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <?php if(isset($error_message)): ?>
            <div class="message message-error show"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between"><strong>📝 Submit a new query – IAA Helpdesk</strong></div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Query Title *</label>
                    <input type="text" name="qTitle" placeholder="e.g., Missing examination CSC 101, Fee payment not reflected" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="qCategory" required>
                        <option>Examination issues</option>
                        <option>Fee-related query</option>
                        <option>Portal login problem</option>
                        <option>Course registration error</option>
                        <option>Academic documents</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="qDept" required>
                        <?php while($dept = mysqli_fetch_assoc($dept_result)): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="qPriority" required>
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                        <option>Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea rows="5" name="qDesc" placeholder="Provide full details..." required></textarea>
                </div>

                <!-- DOCUMENT UPLOAD SECTION (OPTIONAL) -->
                <div class="document-upload-section">
                    <div class="document-header">
                        <i class="fas fa-paperclip"></i>
                        <h4>Supporting Document <span class="optional-badge">Optional</span></h4>
                    </div>
                    <div class="file-input-group">
                        <label class="document-file-label">
                            <i class="fas fa-upload"></i> Choose File
                            <input type="file" name="document_file" accept="image/jpeg,image/png,image/jpg,application/pdf" style="display: none;" onchange="updateFileName(this)">
                        </label>
                        <span class="selected-file-name" id="selectedFileName">No file chosen</span>
                        <button type="button" class="remove-file-btn" onclick="removeFile()">Remove</button>
                    </div>
                    <p class="document-note">
                        <i class="fas fa-info-circle"></i> You can upload a supporting document (payment receipt, letter, screenshot). Max 2MB.
                    </p>
                </div>

                <div style="display:flex; gap:12px; justify-content:end;">
                    <a href="student_index.php" class="btn-primary" style="background:#7f8c8d;">Cancel</a>
                    <button type="submit" name="submit_query" class="btn-primary">Submit Query</button>
                </div>
            </form>
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

    function updateFileName(input) {
        const fileName = input.files[0]?.name || 'No file chosen';
        document.getElementById('selectedFileName').textContent = fileName;
    }

    function removeFile() {
        const fileInput = document.querySelector('input[name="document_file"]');
        fileInput.value = '';
        document.getElementById('selectedFileName').textContent = 'No file chosen';
    }

    // Logout confirmation
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>