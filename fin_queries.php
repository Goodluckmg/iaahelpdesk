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

// Get profile photo
$photo_query = "SELECT profile_photo FROM students WHERE id = $officer_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond_query'])) {
    $ticket_id = mysqli_real_escape_string($conn, $_POST['ticket_id']);
    $response_msg = mysqli_real_escape_string($conn, $_POST['response_message']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    // Insert response
    $insert_response = "INSERT INTO responses (ticket_id, responder_id, responder_role, message) 
                        VALUES ('$ticket_id', '$officer_id', 'finance', '$response_msg')";
    mysqli_query($conn, $insert_response);
    
    // Update ticket status
    $update_ticket = "UPDATE tickets SET status = '$new_status' WHERE id = '$ticket_id'";
    mysqli_query($conn, $update_ticket);
    
    $success_message = "Response sent successfully!";
}

// Get filter parameters
$status_filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$query = "SELECT t.*, s.fullname as student_name, s.reg_no as student_reg, s.email as student_email, s.phone as student_phone
          FROM tickets t
          JOIN students s ON t.user_id = s.id
          WHERE t.department_id = 2";

if ($status_filter != 'all') {
    $query .= " AND t.status = '$status_filter'";
}
if (!empty($search)) {
    $query .= " AND (t.ticket_no LIKE '%$search%' OR s.fullname LIKE '%$search%' OR s.reg_no LIKE '%$search%' OR t.title LIKE '%$search%')";
}

$query .= " ORDER BY 
    CASE t.status 
        WHEN 'pending' THEN 1 
        WHEN 'open' THEN 2 
        WHEN 'in_progress' THEN 3 
        ELSE 4 
    END, 
    t.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance - Student Queries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0a2b38, #0d3b4c);
            color: white;
            padding: 20px;
            min-height: 100vh;
        }
        .profile-area {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
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
        .welcome-text {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        .user-name {
            font-weight: bold;
            margin: 5px 0;
        }
        .user-role {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        .user-id {
            font-size: 0.65rem;
            opacity: 0.6;
        }
        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .nav-item {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
        }
        .logout-item {
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
        }
        .nav-label {
            font-size: 0.9rem;
        }
        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-title {
            font-size: 1.5rem;
            color: #2c3e50;
        }
        .date-badge {
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-size: 0.85rem;
        }
        .widget-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-btn {
            background: #f0f0f0;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            color: #333;
        }
        .filter-btn.active {
            background: #f39c12;
            color: white;
        }
        .search-box {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 250px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2edf2;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .status-pending, .status-open {
            background: #fff3e0;
            color: #e67e22;
        }
        .status-in_progress {
            background: #e3f2fd;
            color: #2196f3;
        }
        .status-resolved {
            background: #d9f0e5;
            color: #1d6f42;
        }
        .btn-primary {
            background: #f39c12;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
        }
        .btn-primary:hover {
            background: #e67e22;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e2edf2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 20px;
        }
        .close-modal {
            cursor: pointer;
            font-size: 1.5rem;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .attachment-link {
            background: #e8f0f5;
            padding: 10px;
            border-radius: 10px;
            margin-top: 10px;
        }
        .message {
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message-success {
            background: #d9f0e5;
            color: #1d6f42;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            .nav-label, .welcome-text, .user-name, .user-role, .user-id {
                display: none;
            }
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
            <a href="finance.php" class="nav-item">
                <i class="fas fa-chart-pie"></i>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="fin_queries.php" class="nav-item active">
                <i class="fas fa-ticket-alt"></i>
                <span class="nav-label">Student Queries</span>
            </a>
            <a href="fin_students.php" class="nav-item">
                <i class="fas fa-user-check"></i>
                <span class="nav-label">Verification</span>
            </a>
            <a href="fin_reports.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span class="nav-label">Reports</span>
            </a>
            <a href="fin_edit.php" class="nav-item">
                <i class="fas fa-camera"></i>
                <span class="nav-label">Edit Photo</span>
            </a>
            <div class="logout-item">
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-label">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Student Finance Queries</h1>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i> 
                <span id="currentDate"></span>
            </div>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="message message-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-list"></i> All Student Queries</strong>
            </div>
            
            <div class="filters">
                <a href="?filter=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?filter=open" class="filter-btn <?php echo $status_filter == 'open' ? 'active' : ''; ?>">Open</a>
                <a href="?filter=in_progress" class="filter-btn <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                <a href="?filter=resolved" class="filter-btn <?php echo $status_filter == 'resolved' ? 'active' : ''; ?>">Resolved</a>
                <div style="flex:1"></div>
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="text" name="search" class="search-box" placeholder="Search by ticket, name, reg no..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="filter" value="<?php echo $status_filter; ?>">
                    <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <?php if(mysqli_num_rows($result) == 0): ?>
                <div style="text-align:center; padding:60px; color:#7f8c8d;">
                    <i class="fas fa-inbox" style="font-size:3rem;"></i>
                    <p>No finance queries found</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width:100%; min-width:600px;">
                        <thead>
                            <tr>
                                <th>Ticket No</th>
                                <th>Student</th>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['ticket_no']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['student_name']); ?><br>
                                    <small><?php echo htmlspecialchars($row['student_reg']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($row['title'], 0, 40)); ?></td>
                                <td>
                                    <span style="padding:2px 8px; border-radius:12px; font-size:0.7rem; background:<?php 
                                        echo $row['priority'] == 'high' ? '#ffe0e0' : ($row['priority'] == 'urgent' ? '#ffcccc' : '#e8f0f5'); 
                                    ?>">
                                        <?php echo ucfirst($row['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <button class="btn-primary respond-btn" 
                                            data-id="<?php echo $row['id']; ?>"
                                            data-ticket="<?php echo $row['ticket_no']; ?>"
                                            data-student="<?php echo htmlspecialchars($row['student_name']); ?>"
                                            data-reg="<?php echo $row['student_reg']; ?>"
                                            data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                            data-desc="<?php echo htmlspecialchars($row['description']); ?>"
                                            data-status="<?php echo $row['status']; ?>"
                                            data-hasdoc="<?php echo $row['has_document']; ?>"
                                            data-docname="<?php echo $row['document_name']; ?>"
                                            data-docpath="<?php echo $row['document_path']; ?>"
                                            style="padding:4px 12px; font-size:0.7rem;">
                                        <i class="fas fa-reply"></i> Respond
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- RESPOND MODAL -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-reply"></i> Respond to Student Query</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form method="POST" class="modal-body">
            <div class="form-group">
                <label>Ticket Number</label>
                <input type="text" id="ticketNo" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Student</label>
                <input type="text" id="studentName" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Registration Number</label>
                <input type="text" id="studentReg" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Query Title</label>
                <input type="text" id="queryTitle" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Original Description</label>
                <textarea id="queryDesc" rows="3" readonly style="background:#f5f5f5;"></textarea>
            </div>
            <div id="attachmentDiv" class="attachment-link" style="display:none;">
                <i class="fas fa-paperclip"></i> Attached Document: 
                <a href="#" id="attachmentLink" target="_blank">View Document</a>
            </div>
            <div class="form-group">
                <label>Your Response <span style="color:red">*</span></label>
                <textarea name="response_message" id="responseMsg" rows="4" required placeholder="Write your response to the student..."></textarea>
            </div>
            <div class="form-group">
                <label>Update Status</label>
                <select name="new_status" id="newStatus">
                    <option value="in_progress">In Progress - Being Reviewed</option>
                    <option value="resolved">Resolved - Issue Fixed</option>
                </select>
            </div>
            <input type="hidden" name="ticket_id" id="ticketId">
            <input type="hidden" name="respond_query" value="1">
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Send Response</button>
            </div>
        </form>
    </div>
</div>

<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options);
    }
    setCurrentDate();

    // Modal handling
    const modal = document.getElementById('respondModal');
    const closeModal = document.querySelector('.close-modal');
    const cancelBtn = document.getElementById('cancelModalBtn');

    document.querySelectorAll('.respond-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('ticketId').value = btn.dataset.id;
            document.getElementById('ticketNo').value = btn.dataset.ticket;
            document.getElementById('studentName').value = btn.dataset.student;
            document.getElementById('studentReg').value = btn.dataset.reg;
            document.getElementById('queryTitle').value = btn.dataset.title;
            document.getElementById('queryDesc').value = btn.dataset.desc;
            
            // Set current status
            const statusSelect = document.getElementById('newStatus');
            if (btn.dataset.status === 'resolved') {
                statusSelect.value = 'resolved';
                statusSelect.disabled = true;
            } else {
                statusSelect.disabled = false;
            }
            
            // Handle attachment
            const attachmentDiv = document.getElementById('attachmentDiv');
            if (btn.dataset.hasdoc === '1' && btn.dataset.docpath) {
                attachmentDiv.style.display = 'block';
                const link = document.getElementById('attachmentLink');
                link.href = btn.dataset.docpath;
                link.innerHTML = '<i class="fas fa-file"></i> ' + btn.dataset.docname;
            } else {
                attachmentDiv.style.display = 'none';
            }
            
            modal.style.display = 'flex';
        });
    });

    function closeModalFunc() {
        modal.style.display = 'none';
        document.getElementById('responseMsg').value = '';
    }

    closeModal.addEventListener('click', closeModalFunc);
    cancelBtn.addEventListener('click', closeModalFunc);
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModalFunc();
    });
</script>
</body>
</html>