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

// Handle verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_payment'])) {
    $ticket_id = mysqli_real_escape_string($conn, $_POST['ticket_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $transaction_ref = mysqli_real_escape_string($conn, $_POST['transaction_ref']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    $update_query = "UPDATE tickets SET status = 'resolved', 
                     response = CONCAT(IFNULL(response, ''), '\n\nPayment Verified: Amount $amount, Ref: $transaction_ref, Notes: $notes')
                     WHERE id = '$ticket_id'";
    mysqli_query($conn, $update_query);
    
    $success_message = "Payment verified successfully!";
}

// Get pending payment queries
$query = "SELECT t.*, s.fullname as student_name, s.reg_no as student_reg 
          FROM tickets t
          JOIN students s ON t.user_id = s.id
          WHERE t.department_id = 2 
          AND (t.title LIKE '%payment%' OR t.title LIKE '%fee%' OR t.category = 'Fee-related query')
          AND t.status != 'resolved'
          ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Student Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0a2b38, #0d3b4c);
            color: white;
            padding: 20px;
            min-height: 100vh;
        }
        .profile-area { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
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
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar i { font-size: 35px; color: white; }
        .welcome-text { font-size: 0.75rem; opacity: 0.8; }
        .user-name { font-weight: bold; margin: 5px 0; }
        .user-role { font-size: 0.7rem; opacity: 0.7; }
        .user-id { font-size: 0.65rem; opacity: 0.6; }
        .nav-menu { display: flex; flex-direction: column; gap: 5px; }
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
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); }
        .logout-item { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        .nav-label { font-size: 0.9rem; }
        .main-content { flex: 1; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.5rem; color: #2c3e50; }
        .date-badge { background: white; padding: 8px 16px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); font-size: 0.85rem; }
        .widget-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .student-card {
            background: #f9fdfe;
            border-left: 3px solid #f39c12;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .student-info h4 { margin-bottom: 5px; color: #0a2b38; }
        .student-info p { font-size: 0.75rem; color: #7f8c8d; }
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
        .status-pending { background: #fff3e0; color: #e67e22; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
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
        .btn-primary:hover { background: #e67e22; }
        .btn-verify { background: #27ae60; color: white; border: none; padding: 6px 16px; border-radius: 20px; cursor: pointer; font-size: 0.7rem; }
        .btn-verify:hover { background: #1e8449; }
        .search-input { padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; width: 250px; }
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
            max-width: 500px;
            width: 90%;
        }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #e2edf2; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .close-modal { cursor: pointer; font-size: 1.5rem; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; }
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .nav-label, .welcome-text, .user-name, .user-role, .user-id { display: none; }
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
            <a href="fin_queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">Student Queries</span></a>
            <a href="fin_students.php" class="nav-item active"><i class="fas fa-user-check"></i><span class="nav-label">Verification</span></a>
            <a href="fin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <a href="fin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Student Payment Verification</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="message message-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between">
                <strong>👨‍🎓 Pending Payment Verifications</strong>
            </div>
            <div style="margin-bottom: 20px;">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name or registration number...">
            </div>
            <div id="studentsList">
                <?php if(mysqli_num_rows($result) == 0): ?>
                    <div style="text-align:center; padding:40px; color:#7f8c8d;">No pending verifications</div>
                <?php else: ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <div class="student-card">
                            <div class="student-info">
                                <h4><?php echo htmlspecialchars($row['student_name']); ?></h4>
                                <p><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($row['student_reg']); ?></p>
                                <p><i class="fas fa-ticket-alt"></i> Ticket: <?php echo htmlspecialchars($row['ticket_no']); ?></p>
                                <p><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>...</p>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                                <button class="btn-verify verify-btn" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                        data-reg="<?php echo $row['student_reg']; ?>"
                                        data-ticket="<?php echo $row['ticket_no']; ?>"
                                        style="margin-left:10px;">
                                    <i class="fas fa-check"></i> Verify Payment
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- VERIFY MODAL -->
<div id="verifyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle"></i> Verify Student Payment</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form method="POST" class="modal-body">
            <div class="form-group">
                <label>Student Name</label>
                <input type="text" id="verifyStudentName" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Registration Number</label>
                <input type="text" id="verifyStudentReg" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Payment Amount (TSh)</label>
                <input type="number" id="verifyAmount" placeholder="Enter amount paid" required>
            </div>
            <div class="form-group">
                <label>Transaction Reference</label>
                <input type="text" id="verifyTransaction" placeholder="Enter transaction reference" required>
            </div>
            <div class="form-group">
                <label>Verification Notes</label>
                <textarea id="verifyNotes" rows="3" placeholder="Add verification notes..."></textarea>
            </div>
            <input type="hidden" name="ticket_id" id="verifyTicketId">
            <input type="hidden" name="verify_payment" value="1">
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn-primary" id="cancelVerifyBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Confirm & Verify</button>
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

    const modal = document.getElementById('verifyModal');
    const closeModal = document.querySelector('.close-modal');
    const cancelBtn = document.getElementById('cancelVerifyBtn');

    document.querySelectorAll('.verify-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('verifyStudentName').value = btn.dataset.name;
            document.getElementById('verifyStudentReg').value = btn.dataset.reg;
            document.getElementById('verifyTicketId').value = btn.dataset.id;
            modal.style.display = 'flex';
        });
    });

    function closeModalFunc() {
        modal.style.display = 'none';
        document.getElementById('verifyAmount').value = '';
        document.getElementById('verifyTransaction').value = '';
        document.getElementById('verifyNotes').value = '';
    }

    closeModal?.addEventListener('click', closeModalFunc);
    cancelBtn?.addEventListener('click', closeModalFunc);
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModalFunc();
    });

    // Search functionality
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.student-card');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(searchTerm) ? 'flex' : 'none';
        });
    });
</script>
</body>
</html>