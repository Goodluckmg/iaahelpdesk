<?php
session_start();
require_once('config/database.php');

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

// Handle form submission for processing query
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_query'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $response = mysqli_real_escape_string($conn, $_POST['response']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Update ticket status
    mysqli_query($conn, "UPDATE tickets SET status = '$status', updated_at = NOW() WHERE id = $ticket_id");
    
    // Save response
    $staff_id = $_SESSION['user_id'];
    mysqli_query($conn, "INSERT INTO ticket_replies (ticket_id, user_id, user_type, message) VALUES ($ticket_id, $staff_id, 'staff', '$response')");
    
    echo "<script>alert('Response sent successfully'); window.location.href='lec_pending.php';</script>";
    exit();
}

// Get filter and search
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query - Get Examination issues only
$where = "WHERE t.category = 'Examination issues'";
if ($filter !== 'all') {
    $where .= " AND t.status = '$filter'";
}
if ($search !== '') {
    $where .= " AND (s.fullname LIKE '%$search%' OR s.reg_no LIKE '%$search%' OR t.ticket_no LIKE '%$search%')";
}

$queries_query = "SELECT t.*, s.fullname as student_name, s.reg_no 
                  FROM tickets t 
                  JOIN students s ON t.user_id = s.id 
                  $where 
                  ORDER BY t.created_at DESC";
$queries_result = mysqli_query($conn, $queries_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Student Queries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #2c7da0; /* RANGI MOJA - HAKUNA GRADIENT */
        }
        .avatar i { font-size: 40px; color: white; }
        .welcome-text { font-size: 0.85rem; color: #94a3b8; }
        .user-name { font-size: 1.2rem; font-weight: 600; margin: 5px 0; color: white; }
        .user-role { font-size: 0.7rem; background: #2c7da0; display: inline-block; padding: 3px 12px; border-radius: 20px; }
        .user-id { font-size: 0.7rem; margin-top: 8px; color: #94a3b8; }
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
        
        /* ========== TABLE STYLES ========== */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #e2edf2; font-size: 0.85rem; }
        th { background: #f8fafc; font-weight: 600; color: #333; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .status-pending { background: #fff3e0; color: #f39c12; }
        .status-progress { background: #e3f2fd; color: #2196f3; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        
        /* ========== SEARCH & FILTER ========== */
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 25px; outline: none; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-btn { background: #f0f0f0; border: none; padding: 6px 16px; border-radius: 20px; cursor: pointer; font-size: 13px; }
        .filter-btn.active { background: #2c7da0; color: white; }
        
        /* ========== MODAL STYLES ========== */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 550px; max-height: 85vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .close-modal:hover { color: #c0392b; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        input, textarea, select { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #cbdbe6; outline: none; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        input:focus, textarea:focus, select:focus { border-color: #2c7da0; }
        
        .evidence-box { background: #f8fafc; border-radius: 12px; padding: 10px; border: 1px dashed #2c7da0; }
        .document-link { color: #2c7da0; text-decoration: none; cursor: pointer; }
        .document-link:hover { text-decoration: underline; }
        
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar span { display: none; } 
            .main-content { margin-left: 70px; } 
        }
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
            <a href="lec_pending.php" class="nav-item active"><i class="fas fa-clock"></i><span>Student query</span></a>
            <a href="lec_resolved.php" class="nav-item"><i class="fas fa-check-circle"></i><span>Resolved</span></a>
            <a href="lec_timetable.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Exam Timetable</span></a>
            <a href="lec_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Student Queries</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <form method="GET" action="" class="search-box" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="search" placeholder="🔍 Search by student name, reg no, ticket no..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Search</button>
                <a href="lec_pending.php" class="btn-primary" style="background:#2c7da0;"><i class="fas fa-sync-alt"></i> Refresh</a>
            </form>
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All Queries</a>
                <a href="?filter=open" class="filter-btn <?php echo $filter == 'open' ? 'active' : ''; ?>">Open</a>
                <a href="?filter=in_progress" class="filter-btn <?php echo $filter == 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                <a href="?filter=resolved" class="filter-btn <?php echo $filter == 'resolved' ? 'active' : ''; ?>">Resolved</a>
            </div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Student Queries (Examination Issues)</strong>
                <span style="color:#666; font-size:0.8rem;"><?php echo mysqli_num_rows($queries_result); ?> records</span>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>Ticket No</th><th>Student Name</th><th>Reg No</th><th>Title</th><th>Category</th><th>Priority</th><th>Document</th><th>Status</th><th>Submitted</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($queries_result)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['ticket_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['title'], 0, 40)); ?></td>
                            <td><span class="status-badge status-pending"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td><?php echo $row['priority']; ?></td>
                            <td>
                                <?php if ($row['has_document'] && $row['document_name']): ?>
                                    <a href="#" class="document-link view-doc-link" data-doc-path="<?php echo htmlspecialchars($row['document_path']); ?>" data-doc-name="<?php echo htmlspecialchars($row['document_name']); ?>"><i class="fas fa-paperclip"></i> <?php echo htmlspecialchars(substr($row['document_name'], 0, 20)); ?></a>
                                <?php else: ?>
                                    <span style="color:#999;"><i class="fas fa-times-circle"></i> No document</span>
                                <?php endif; ?>
                             </td>
                            <td><span class="status-badge <?php echo $row['status'] == 'open' ? 'status-pending' : ($row['status'] == 'in_progress' ? 'status-progress' : 'status-resolved'); ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            <td><button class="btn-primary respond-btn" 
                                data-id="<?php echo $row['id']; ?>" 
                                data-ticket="<?php echo htmlspecialchars($row['ticket_no']); ?>" 
                                data-student="<?php echo htmlspecialchars($row['student_name']); ?>" 
                                data-reg="<?php echo htmlspecialchars($row['reg_no']); ?>" 
                                data-title="<?php echo htmlspecialchars($row['title']); ?>" 
                                data-category="<?php echo htmlspecialchars($row['category']); ?>" 
                                data-desc="<?php echo htmlspecialchars($row['description']); ?>"
                                data-has-doc="<?php echo $row['has_document']; ?>"
                                data-doc-name="<?php echo htmlspecialchars($row['document_name']); ?>"
                                data-doc-path="<?php echo htmlspecialchars($row['document_path']); ?>"
                                style="padding:4px 12px;"><i class="fas fa-reply"></i> Respond</button></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($queries_result) == 0): ?>
                        <tr><td colspan="10" style="text-align:center;">No queries found. Make sure student submits with category "Examination issues"</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- MODAL FOR RESPONDING -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-reply"></i> Respond to Student Query</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="ticket_id" id="ticket_id">
            <div class="form-group">
                <label>Ticket No:</label>
                <input type="text" id="ticket_no" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Student Name:</label>
                <input type="text" id="student_name" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Registration No:</label>
                <input type="text" id="reg_no" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Query Title:</label>
                <input type="text" id="title" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Category:</label>
                <input type="text" id="category_display" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Student's Description:</label>
                <textarea id="description" rows="3" readonly style="background:#f5f5f5;"></textarea>
            </div>
            
            <!-- Document Display Section -->
            <div class="form-group" id="documentDiv" style="display:none;">
                <label><i class="fas fa-paperclip"></i> Attached Document:</label>
                <div class="evidence-box" style="background:#f8fafc; border-radius:12px; padding:10px; border:1px dashed #2c7da0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <span><i class="fas fa-file-pdf" style="color:#e74c3c;"></i> <span id="documentName"></span></span>
                    <button type="button" id="viewDocumentBtn" class="btn-primary" style="padding:4px 12px; font-size:0.7rem;"><i class="fas fa-download"></i> Download</button>
                </div>
            </div>
            
            <div class="form-group">
                <label>Your Response</label>
                <textarea name="response" rows="4" placeholder="Write your response to the student..." required></textarea>
            </div>
            <div class="form-group">
                <label>Update Status</label>
                <select name="status">
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" name="process_query" class="btn-primary">Submit Response</button>
            </div>
        </form>
    </div>
</div>

<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', options);
    }
    setCurrentDate();

    // View document from table link
    document.querySelectorAll('.view-doc-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const docPath = link.dataset.doc_path;
            const docName = link.dataset.doc_name;
            if (docPath) {
                window.open(docPath, '_blank');
            } else {
                alert('Document not found');
            }
        });
    });

    // Modal functionality
    const modal = document.getElementById('respondModal');
    const closeBtn = document.querySelector('.close-modal');
    const cancelBtn = document.getElementById('cancelModalBtn');

    document.querySelectorAll('.respond-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('ticket_id').value = btn.dataset.id;
            document.getElementById('ticket_no').value = btn.dataset.ticket;
            document.getElementById('student_name').value = btn.dataset.student;
            document.getElementById('reg_no').value = btn.dataset.reg;
            document.getElementById('title').value = btn.dataset.title;
            document.getElementById('category_display').value = btn.dataset.category;
            document.getElementById('description').value = btn.dataset.desc;
            
            // Handle document display in modal
            const hasDoc = btn.dataset.has_doc === '1';
            const docName = btn.dataset.doc_name || '';
            const docPath = btn.dataset.doc_path || '';
            
            if (hasDoc && docName) {
                document.getElementById('documentDiv').style.display = 'block';
                document.getElementById('documentName').innerText = docName;
                document.getElementById('viewDocumentBtn').onclick = () => {
                    if (docPath) {
                        window.open(docPath, '_blank');
                    } else {
                        alert('Document not found');
                    }
                };
            } else {
                document.getElementById('documentDiv').style.display = 'none';
            }
            
            modal.style.display = 'flex';
        });
    });

    function closeModal() { 
        modal.style.display = 'none'; 
    }
    
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // Logout confirmation
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>