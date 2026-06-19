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

// ========== Get officer ID ==========
$officer_id = $_SESSION['user_id'] ?? $_SESSION['staff_id'] ?? $_SESSION['student_id'] ?? 0;
$fullname = $_SESSION['fullname'] ?? 'Finance Officer';
$reg_no = $_SESSION['reg_no'] ?? $_SESSION['staff_no'] ?? 'FIN/2024/001';

// If officer_id is 0, try to get from staff table
if ($officer_id == 0) {
    $find_finance = mysqli_query($conn, "SELECT id, fullname, staff_no FROM staff WHERE role = 'finance' LIMIT 1");
    if ($find_finance && mysqli_num_rows($find_finance) > 0) {
        $finance_data = mysqli_fetch_assoc($find_finance);
        $officer_id = $finance_data['id'];
        $fullname = $finance_data['fullname'];
        $reg_no = $finance_data['staff_no'];
        $_SESSION['user_id'] = $officer_id;
        $_SESSION['staff_id'] = $officer_id;
    }
}

// ========== Ensure finance officer exists in students table ==========
if ($officer_id > 0) {
    $check_student = mysqli_query($conn, "SELECT id FROM students WHERE id = $officer_id");
    if (mysqli_num_rows($check_student) == 0) {
        $staff_query = "SELECT * FROM staff WHERE id = $officer_id OR staff_no = '$reg_no'";
        $staff_result = mysqli_query($conn, $staff_query);
        if ($staff_result && mysqli_num_rows($staff_result) > 0) {
            $staff_data = mysqli_fetch_assoc($staff_result);
            $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
            $insert_staff = "INSERT INTO students (fullname, reg_no, email, password, role, status) 
                             VALUES ('{$staff_data['fullname']}', '{$staff_data['staff_no']}', '{$staff_data['email']}', '$hashed_password', 'finance', 'active')";
            mysqli_query($conn, $insert_staff);
            $officer_id = mysqli_insert_id($conn);
            $_SESSION['user_id'] = $officer_id;
            $_SESSION['student_id'] = $officer_id;
        }
    }
}

// Get profile photo
$current_photo = null;
$photo_query = "SELECT profile_photo FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $photo_query);
mysqli_stmt_bind_param($stmt, "i", $officer_id);
mysqli_stmt_execute($stmt);
$photo_result = mysqli_stmt_get_result($stmt);
if ($photo_result && mysqli_num_rows($photo_result) > 0) {
    $student_data = mysqli_fetch_assoc($photo_result);
    $current_photo = $student_data['profile_photo'] ?? null;
}
mysqli_stmt_close($stmt);

// ========== Handle payment verification (AJAX) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment']) && $_POST['verify_payment'] == '1') {
    header('Content-Type: application/json');
    
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $issue_date = isset($_POST['issue_date']) ? $_POST['issue_date'] : date('Y-m-d');
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+30 days'));
    
    if ($ticket_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
        exit();
    }
    
    // Check if ticket exists and payment not already verified
    $check_query = "SELECT id, payment_verified FROM tickets WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $ticket_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($check_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit();
    }
    $ticket_data = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($stmt);
    
    if ($ticket_data['payment_verified'] == 1) {
        echo json_encode(['success' => false, 'message' => 'Payment already verified for this ticket']);
        exit();
    }
    
    // Generate unique badge code
    $badge_code = 'MTIHANI-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    
    // Update ticket using prepared statement
    $update_query = "UPDATE tickets SET 
                        payment_verified = 1, 
                        badge_code = ?,
                        badge_issue_date = ?,
                        badge_expiry_date = ?,
                        verified_by = ?,
                        verified_at = NOW(),
                        status = 'resolved'
                    WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sssii", $badge_code, $issue_date, $expiry_date, $officer_id, $ticket_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $formatted_issue = date('d/m/Y', strtotime($issue_date));
        $formatted_expiry = date('d/m/Y', strtotime($expiry_date));
        
        $response_msg = "✅ PAYMENT VERIFIED! Your payment has been confirmed.\n\n";
        $response_msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $response_msg .= "📋 EXAMINATION BADGE DETAILS\n";
        $response_msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $response_msg .= "🔹 Badge Code: $badge_code\n";
        $response_msg .= "📅 Issue Date: $formatted_issue\n";
        $response_msg .= "⏰ Expiry Date: $formatted_expiry\n";
        $response_msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $response_msg .= "🔹 To download your badge: Go to 'My Queries' and click 'Download Badge' button.\n";
        $response_msg .= "⚠️ Note: This badge expires on $formatted_expiry.";
        
        // Insert response using prepared statement
        $insert_response = "INSERT INTO responses (ticket_id, responder_id, responder_role, message) VALUES (?, ?, 'finance', ?)";
        $stmt2 = mysqli_prepare($conn, $insert_response);
        mysqli_stmt_bind_param($stmt2, "iis", $ticket_id, $officer_id, $response_msg);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        
        echo json_encode([
            'success' => true, 
            'badge_code' => $badge_code,
            'issue_date' => $formatted_issue,
            'expiry_date' => $formatted_expiry
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);
    exit();
}

// ========== Handle standard response submission ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_query']) && $_POST['respond_query'] == '1') {
    $ticket_id = intval($_POST['ticket_id']);
    $response_msg = mysqli_real_escape_string($conn, $_POST['response_message']);
    $new_status = isset($_POST['new_status']) ? mysqli_real_escape_string($conn, $_POST['new_status']) : 'in_progress';
    
    if (empty($response_msg)) {
        $error_message = "Please enter a response message.";
    } else {
        $insert_response = "INSERT INTO responses (ticket_id, responder_id, responder_role, message) VALUES (?, ?, 'finance', ?)";
        $stmt = mysqli_prepare($conn, $insert_response);
        mysqli_stmt_bind_param($stmt, "iis", $ticket_id, $officer_id, $response_msg);
        if (mysqli_stmt_execute($stmt)) {
            $update_ticket = "UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt2 = mysqli_prepare($conn, $update_ticket);
            mysqli_stmt_bind_param($stmt2, "si", $new_status, $ticket_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
            $success_message = "Response sent successfully!";
        } else {
            $error_message = "Database error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ========== Get filter and search parameters ==========
$status_filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// ========== Pagination ==========
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Build base query for counting
$count_query = "SELECT COUNT(*) as total
                FROM tickets t
                JOIN students s ON t.user_id = s.id
                WHERE t.department_id = 2";
if ($status_filter != 'all') {
    $count_query .= " AND t.status = '$status_filter'";
}
if (!empty($search)) {
    $count_query .= " AND (t.ticket_no LIKE '%$search%' OR s.fullname LIKE '%$search%' OR s.reg_no LIKE '%$search%' OR t.title LIKE '%$search%')";
}
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// Build main query with pagination
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
    t.created_at DESC
    LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

$active_page = 'queries';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance - Student Queries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
       
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
        .nav-item.active { background: #2c7da0; }
        .nav-item:hover { background: transparent; }
        .logout-item { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        .nav-label { font-size: 0.9rem; }

        .main-content { flex: 1; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.5rem; color: #0b2b4a; }
        .date-badge { background: white; padding: 8px 16px; border-radius: 20px; box-shadow: 0 1px 3px #2c7da0; font-size: 0.85rem; }
        .widget-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px#2c7da0; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .flex-between strong { color:#2c7da0; }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-btn {
            background: #f0f0f0; border: none; padding: 8px 20px; border-radius: 20px;
            text-decoration: none; color:#2c7da0; font-size: 0.85rem;
        }
        .filter-btn.active { background: #2c7da0; color: white; }
        .search-box { padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; width: 250px; }
        .btn-primary {
            background: #2c7da0; color: white; border: none; padding: 8px 16px; border-radius: 20px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.8rem;
        }
        .btn-primary:hover { background: #0b2b4a; }
        .btn-verify { background:#2c7da0; }
        .btn-verify:hover { background: #1d6f42; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2edf2; }
        th { background: #f8fafc; font-weight: 600; color: #0b2b4a; }
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
        .status-pending, .status-open { background: #fff3e0; color: #e67e22; }
        .status-in_progress { background: #e3f2fd; color: #1a5e9c; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
        .pagination a, .pagination span {
            padding: 6px 14px; border-radius: 20px; background: #f0f0f0; color: #333;
            text-decoration: none; font-size: 0.85rem;
        }
        .pagination .active { background: #0b2b4a; color: white; }
        .pagination .disabled { background: #e2e8f0; color: #94a3b8; pointer-events: none; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 16px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #e2edf2; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .close-modal { cursor: default; font-size: 1.5rem; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group label .required { color: red; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; margin-bottom: 0; }
        .attachment-link { background: #e8f0f5; padding: 10px; border-radius: 10px; margin-top: 10px; }
        .badge-settings { background: #e8f0f5; padding: 15px; border-radius: 10px; margin: 15px 0; border-left: 3px solid #1d6f42; }
        .badge-settings h4 { margin-bottom: 10px; color: #1d6f42; }
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .nav-label, .welcome-text, .user-name, .user-role, .user-id { display: none; }
            .form-row { flex-direction: column; gap: 10px; }
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
            <a href="fin_queries.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
            <a href="fin_students.php" class="nav-item"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <a href="fin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Student Finance Queries</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="message message-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if(isset($error_message)): ?>
            <div class="message message-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between">
                <strong><i class="fas fa-list"></i> All Student Queries</strong>
            </div>
            
            <div class="filters">
                <a href="?filter=all&page=1" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=pending&page=1" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?filter=open&page=1" class="filter-btn <?php echo $status_filter == 'open' ? 'active' : ''; ?>">Open</a>
                <a href="?filter=in_progress&page=1" class="filter-btn <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                <a href="?filter=resolved&page=1" class="filter-btn <?php echo $status_filter == 'resolved' ? 'active' : ''; ?>">Resolved</a>
                <div style="flex:1"></div>
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="text" name="search" class="search-box" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="filter" value="<?php echo $status_filter; ?>">
                    <input type="hidden" name="page" value="<?php echo $page; ?>">
                    <button type="submit" class="btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <?php if(mysqli_num_rows($result) == 0): ?>
                <div style="text-align:center; padding:60px; color:#94a3b8;">
                    <i class="fas fa-inbox" style="font-size:3rem;"></i>
                    <p>No finance queries found</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width:100%; min-width:600px;">
                        <thead><tr><th>Ticket No</th><th>Student</th><th>Title</th><th>Priority</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['ticket_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?><br><small><?php echo htmlspecialchars($row['student_reg']); ?></small></td>
                                <td><?php echo htmlspecialchars(substr($row['title'], 0, 40)); ?></td>
                                <td><span style="padding:2px 8px; border-radius:12px; font-size:0.7rem; background:<?php echo $row['priority'] == 'high' ? '#ffe0e0' : ($row['priority'] == 'urgent' ? '#ffcccc' : '#e8f0f5'); ?>"><?php echo ucfirst($row['priority']); ?></span></td>
                                <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <button class="btn-primary respond-btn" data-id="<?php echo $row['id']; ?>" data-ticket="<?php echo $row['ticket_no']; ?>" data-student="<?php echo htmlspecialchars($row['student_name']); ?>" data-reg="<?php echo $row['student_reg']; ?>" data-title="<?php echo htmlspecialchars($row['title']); ?>" data-desc="<?php echo htmlspecialchars($row['description']); ?>" data-status="<?php echo $row['status']; ?>" data-hasdoc="<?php echo $row['has_document']; ?>" data-docname="<?php echo $row['document_name']; ?>" data-docpath="<?php echo $row['document_path']; ?>" data-payment_verified="<?php echo $row['payment_verified']; ?>" style="padding:4px 12px; font-size:0.7rem;">
                                        <i class="fas fa-reply"></i> Respond
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?filter=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?filter=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?filter=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page+1; ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- RESPOND MODAL -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-reply"></i> Respond to Student Query</h3>
            <span class="close-modal" id="closeModalBtn">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group"><label>Ticket Number</label><input type="text" id="ticketNo" readonly style="background:#f5f5f5;"></div>
            <div class="form-group"><label>Student</label><input type="text" id="studentName" readonly style="background:#f5f5f5;"></div>
            <div class="form-group"><label>Registration Number</label><input type="text" id="studentReg" readonly style="background:#f5f5f5;"></div>
            <div class="form-group"><label>Query Title</label><input type="text" id="queryTitle" readonly style="background:#f5f5f5;"></div>
            <div class="form-group"><label>Original Description</label><textarea id="queryDesc" rows="3" readonly style="background:#f5f5f5;"></textarea></div>
            <div id="attachmentDiv" class="attachment-link" style="display:none;">
                <i class="fas fa-paperclip"></i> Attached Document: <a href="#" id="attachmentLink" target="_blank">View Document</a>
            </div>
            
            <div class="badge-settings">
                <h4><i class="fas fa-certificate"></i> Examination Badge Settings</h4>
                <p style="font-size: 0.75rem; margin-bottom: 10px; color: #1d6f42;">
                    <i class="fas fa-info-circle"></i> System will automatically generate the badge code.
                </p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Issue Date <span class="required">*</span></label>
                        <input type="date" id="issueDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date <span class="required">*</span></label>
                        <input type="date" id="expiryDate" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Your Response <span class="required">*</span></label>
                <textarea id="responseMsg" rows="4" placeholder="Write your response..."></textarea>
            </div>
            <div class="form-group">
                <label>Update Status</label>
                <select id="newStatus">
                    <option value="in_progress">In Progress - Being Reviewed</option>
                    <option value="resolved">Resolved - Issue Fixed</option>
                </select>
            </div>
            <input type="hidden" id="ticketId">
            
            <div style="display:flex; gap:10px; margin-top:20px; flex-wrap:wrap;">
                <button type="button" class="btn-primary" id="verifyPaymentBtn" style="background:#1d6f42;">
                    <i class="fas fa-check-circle"></i> ✅ Verify Payment & Issue Badge
                </button>
                <button type="button" class="btn-primary" id="sendResponseBtn" style="background:#0b2b4a;">
                    <i class="fas fa-paper-plane"></i> Send Response Only
                </button>
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options);
    }
    setCurrentDate();

    const modal = document.getElementById('respondModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const sendResponseBtn = document.getElementById('sendResponseBtn');

    let currentTicket = {};

    document.querySelectorAll('.respond-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTicket = {
                id: btn.dataset.id,
                no: btn.dataset.ticket,
                student: btn.dataset.student,
                reg: btn.dataset.reg,
                title: btn.dataset.title,
                desc: btn.dataset.desc,
                status: btn.dataset.status,
                hasdoc: btn.dataset.hasdoc,
                docname: btn.dataset.docname,
                docpath: btn.dataset.docpath,
                payment_verified: btn.dataset.payment_verified
            };
            
            document.getElementById('ticketId').value = currentTicket.id;
            document.getElementById('ticketNo').value = currentTicket.no;
            document.getElementById('studentName').value = currentTicket.student;
            document.getElementById('studentReg').value = currentTicket.reg;
            document.getElementById('queryTitle').value = currentTicket.title;
            document.getElementById('queryDesc').value = currentTicket.desc;
            
            const verifyBtn = document.getElementById('verifyPaymentBtn');
            if (currentTicket.payment_verified == '1') {
                verifyBtn.disabled = true;
                verifyBtn.style.opacity = '0.5';
                verifyBtn.style.cursor = 'not-allowed';
            } else {
                verifyBtn.disabled = false;
                verifyBtn.style.opacity = '1';
                verifyBtn.style.cursor = 'default';
            }
            
            const today = new Date().toISOString().split('T')[0];
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + 30);
            document.getElementById('issueDate').value = today;
            document.getElementById('expiryDate').value = expiryDate.toISOString().split('T')[0];
            
            const statusSelect = document.getElementById('newStatus');
            if (currentTicket.status === 'resolved') {
                statusSelect.value = 'resolved';
                statusSelect.disabled = true;
            } else {
                statusSelect.value = 'in_progress';
                statusSelect.disabled = false;
            }
            
            const attachmentDiv = document.getElementById('attachmentDiv');
            if (currentTicket.hasdoc === '1' && currentTicket.docpath) {
                attachmentDiv.style.display = 'block';
                document.getElementById('attachmentLink').href = currentTicket.docpath;
                document.getElementById('attachmentLink').innerHTML = '<i class="fas fa-file"></i> ' + currentTicket.docname;
            } else {
                attachmentDiv.style.display = 'none';
            }
            modal.style.display = 'flex';
        });
    });

    function closeModalFunc() {
        modal.style.display = 'none';
        document.getElementById('responseMsg').value = '';
        document.getElementById('ticketId').value = '';
    }

    // Send Response Only
    sendResponseBtn?.addEventListener('click', async function() {
        const ticketId = document.getElementById('ticketId').value;
        const responseMsg = document.getElementById('responseMsg').value;
        const newStatus = document.getElementById('newStatus').value;
        
        if (!ticketId) { alert('No ticket selected'); return; }
        if (!responseMsg.trim()) { alert('Please enter a response message'); return; }
        
        const formData = new FormData();
        formData.append('respond_query', '1');
        formData.append('ticket_id', ticketId);
        formData.append('response_message', responseMsg);
        formData.append('new_status', newStatus);
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;
        
        try {
            const response = await fetch(window.location.pathname, { method: 'POST', body: formData });
            const text = await response.text();
            if (text.includes('successfully')) {
                alert('Response sent successfully!');
                closeModalFunc();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert('Error sending response');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        } catch (error) {
            alert('Network error: ' + error.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });

    // Verify Payment Button
    document.getElementById('verifyPaymentBtn')?.addEventListener('click', async function() {
        const ticketId = document.getElementById('ticketId').value;
        const ticketNo = document.getElementById('ticketNo').value;
        const issueDate = document.getElementById('issueDate').value;
        const expiryDate = document.getElementById('expiryDate').value;
        
        if (!ticketId) { alert('No ticket selected'); return; }
        if (!issueDate) { alert('Please select issue date'); return; }
        if (!expiryDate) { alert('Please select expiry date'); return; }
        if (new Date(expiryDate) <= new Date(issueDate)) {
            alert('Expiry date must be after issue date');
            return;
        }
        if (!confirm(`Confirm payment verification for ticket ${ticketNo}?\n\n📅 Issue Date: ${issueDate}\n⏰ Expiry Date: ${expiryDate}`)) {
            return;
        }
        
        const verifyBtn = this;
        const originalHtml = verifyBtn.innerHTML;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        verifyBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('verify_payment', '1');
            formData.append('ticket_id', ticketId);
            formData.append('issue_date', issueDate);
            formData.append('expiry_date', expiryDate);
            
            const response = await fetch(window.location.pathname, { method: 'POST', body: formData });
            const text = await response.text();
            let result;
            try { result = JSON.parse(text); } catch(e) { throw new Error('Invalid server response'); }
            
            if (result.success) {
                alert(`✅ PAYMENT VERIFIED!\n\nBadge Code: ${result.badge_code}\nIssue Date: ${result.issue_date}\nExpiry Date: ${result.expiry_date}`);
                closeModalFunc();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert('Error: ' + (result.message || 'Unknown error'));
                verifyBtn.innerHTML = originalHtml;
                verifyBtn.disabled = false;
            }
        } catch (error) {
            alert('Network error: ' + error.message);
            verifyBtn.innerHTML = originalHtml;
            verifyBtn.disabled = false;
        }
    });

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModalFunc);
    if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeModalFunc);
    window.addEventListener('click', (e) => { if (e.target === modal) closeModalFunc(); });
</script>
</body>
</html>