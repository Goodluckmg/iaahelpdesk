<?php
session_start();

// 1. Angalia kama ameingia
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Angalia kama ana role ya admin (super_admin au admin)
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student_dashboard.php");
    exit();
}

require_once 'config/database.php';

// ========== ADD THIS LINE - FIXES THE ERROR ==========
$logged_user_id = $_SESSION['student_id'];
// =====================================================

// Handle AJAX request for updating status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $ticket_id = intval($_POST['ticket_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $allowed = ['Open', 'In Progress', 'Resolved'];
    if (in_array($new_status, $allowed)) {
        $db_status = strtolower(str_replace(' ', '_', $new_status));
        $update = "UPDATE tickets SET status = '$db_status' WHERE id = $ticket_id";
        if (mysqli_query($conn, $update)) {
            echo 'success';
        } else {
            echo 'error';
        }
    } else {
        echo 'invalid';
    }
    exit();
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$status_condition = '';

if ($filter !== 'all') {
    $filter_db = strtolower(str_replace(' ', '_', $filter));
    $status_condition = "WHERE t.status = '$filter_db'";
}

// Get profile photo - NOW $logged_user_id is defined
$photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
$photo_result = mysqli_query($conn, $photo_query);
$admin_data = mysqli_fetch_assoc($photo_result);
$current_photo = $admin_data['profile_photo'] ?? null;

// Fetch tickets with student, department names, and document info
$query = "
    SELECT t.id, t.ticket_no, t.title, t.priority, t.status, t.created_at,
           t.has_document, t.document_name, t.document_path, t.document_type,
           s.fullname AS student_name,
           d.name AS department_name
    FROM tickets t
    LEFT JOIN students s ON t.user_id = s.id
    LEFT JOIN departments d ON t.department_id = d.id
    $status_condition
    ORDER BY t.created_at DESC
";
$result = mysqli_query($conn, $query);
$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['display_status'] = ucwords(str_replace('_', ' ', $row['status']));
    $tickets[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | All Tickets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; display: inline-block; }
        .status-urgent { background: #fde8e8; color: #c0392b; }
        .status-resolved { background: #d9f0e5; color: #1d6f42; }
        .status-progress { background: #fff3e0; color: #b45f06; }
        .btn-primary { background: #1e5a74; border: none; padding: 6px 12px; border-radius: 30px; color: white; cursor: pointer; font-size: 0.75rem; }
        .btn-primary:hover { background: #0f4057; }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 12px 10px; text-align: left; }
        th { background: #f8fafc; font-weight: 600; color: #0a2b38; }
        tr:hover { background: #f8fafc; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; }
        .page-title { font-size: 1.6rem; font-weight: 700; color: #0a2b38; }
        .date-badge { background: white; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; border: 1px solid #dee9f0; }
        .document-badge { background: #e8f0f5; color: #2c7da0; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        
        /* Document Modal */
        .doc-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; align-items: center; justify-content: center; }
        .doc-modal-content { max-width: 90%; max-height: 90%; background: white; border-radius: 16px; overflow: auto; padding: 20px; position: relative; }
        .doc-modal-close { position: absolute; top: 10px; right: 20px; background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 30px; cursor: pointer; font-size: 0.8rem; }
        .doc-modal-close:hover { background: #c0392b; }
        .doc-image { max-width: 100%; max-height: 80vh; display: block; margin: 0 auto; }
        .doc-iframe { width: 100%; height: 80vh; border: none; }
        .doc-download { display: inline-block; margin-top: 15px; padding: 8px 16px; background: #2c7da0; color: white; border-radius: 30px; text-decoration: none; font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
                <?php if ($current_photo): ?>
                    <img src="data:image/jpeg;base64,<?php echo $current_photo; ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
            <div class="user-role"><?php echo ($_SESSION['role'] == 'super_admin') ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no']); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">All Tickets</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>🎫 All System Tickets</strong>
                <div>
                    <select id="filterStatus" style="width:150px; margin-right:10px; padding:6px; border-radius:20px; border:1px solid #cbdbe6;">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Open" <?php echo $filter == 'Open' ? 'selected' : ''; ?>>Open</option>
                        <option value="In Progress" <?php echo $filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Resolved" <?php echo $filter == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                    <button class="btn-primary" id="refreshTicketsBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>
            
            <?php if (empty($tickets)): ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <i class="fas fa-ticket-alt" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                    No tickets found.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table id="allTicketsTable">
                        <thead>
                            <tr>
                                <th>Ticket No</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Department</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Document</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($t['ticket_no']); ?></strong>侧
                                <td><?php echo htmlspecialchars($t['student_name'] ?? 'Unknown'); ?>侧
                                <td><?php echo htmlspecialchars(substr($t['title'], 0, 50)); ?>侧
                                <td><?php echo htmlspecialchars($t['department_name'] ?? 'Unassigned'); ?>侧
                                <td>
                                    <span class="status-badge <?php echo ($t['priority'] == 'urgent') ? 'status-urgent' : ''; ?>">
                                        <?php echo ucfirst($t['priority']); ?>
                                    </span>
                                侧
                                <td>
                                    <select class="status-select" data-id="<?php echo $t['id']; ?>" style="padding:4px 8px; border-radius:12px; border:1px solid #cbdbe6;">
                                        <option value="Open" <?php echo ($t['display_status'] == 'Open') ? 'selected' : ''; ?>>Open</option>
                                        <option value="In Progress" <?php echo ($t['display_status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Resolved" <?php echo ($t['display_status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                                    </select>
                                侧
                                <td>
                                    <?php if($t['has_document'] && $t['document_path']): ?>
                                        <button class="btn-primary btn-sm view-doc-btn" 
                                                data-doc-path="<?php echo htmlspecialchars($t['document_path']); ?>" 
                                                data-doc-name="<?php echo htmlspecialchars($t['document_name']); ?>"
                                                style="background:#2c7da0;">
                                            <i class="fas fa-paperclip"></i> View
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:0.7rem;">No document</span>
                                    <?php endif; ?>
                                侧
                                <td><?php echo date('d/m/Y', strtotime($t['created_at'])); ?>侧
                                <td>
                                    <button class="btn-primary btn-sm view-ticket" data-id="<?php echo $t['id']; ?>" data-title="<?php echo htmlspecialchars($t['title']); ?>">
                                        <i class="fas fa-info-circle"></i> Details
                                    </button>
                                侧
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Document Viewer Modal -->
<div id="docViewerModal" class="doc-modal">
    <div class="doc-modal-content">
        <button class="doc-modal-close" onclick="closeDocViewer()">✕ Close</button>
        <div id="docViewerBody" style="text-align: center;">
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-pulse" style="font-size: 40px; color: #2c7da0;"></i>
                <p>Loading document...</p>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Details Modal -->
<div id="ticketModal" class="doc-modal">
    <div class="doc-modal-content" style="max-width: 600px;">
        <button class="doc-modal-close" onclick="closeTicketModal()">✕ Close</button>
        <div id="ticketDetailsBody"></div>
    </div>
</div>

<script>
    // Set current date
    document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });

    // Filter change
    document.getElementById('filterStatus').addEventListener('change', function() {
        window.location.href = 'admin_tickets_view.php?filter=' + encodeURIComponent(this.value);
    });

    // Refresh button
    document.getElementById('refreshTicketsBtn').addEventListener('click', function() {
        window.location.reload();
    });

    // Update ticket status via AJAX
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            let ticketId = this.dataset.id;
            let newStatus = this.value;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update_status&ticket_id=' + ticketId + '&status=' + encodeURIComponent(newStatus)
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'success') {
                    alert('✅ Status updated successfully!');
                    window.location.reload();
                } else {
                    alert('❌ Error updating status. Please try again.');
                }
            })
            .catch(err => {
                alert('Network error: ' + err);
            });
        });
    });

    // Document Viewer Function
    function viewDocument(docPath, docName) {
        const modal = document.getElementById('docViewerModal');
        const modalBody = document.getElementById('docViewerBody');
        
        modalBody.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-pulse" style="font-size: 40px;"></i><p>Loading document...</p></div>';
        modal.style.display = 'flex';
        
        const extension = docPath.split('.').pop().toLowerCase();
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (imageExtensions.includes(extension)) {
            const img = new Image();
            img.onload = () => {
                modalBody.innerHTML = '';
                modalBody.appendChild(img);
                img.className = 'doc-image';
            };
            img.onerror = () => {
                modalBody.innerHTML = `<p>Unable to preview image. <a href="${docPath}" download="${docName}" class="doc-download">Download ${docName}</a></p>`;
            };
            img.src = docPath;
        } else if (extension === 'pdf') {
            const iframe = document.createElement('iframe');
            iframe.src = docPath;
            iframe.style.width = '100%';
            iframe.style.height = '80vh';
            iframe.style.border = 'none';
            modalBody.innerHTML = '';
            modalBody.appendChild(iframe);
        } else {
            modalBody.innerHTML = `<p>Preview not available for this file type.</p>
                                   <a href="${docPath}" download="${docName}" class="doc-download"><i class="fas fa-download"></i> Download ${docName}</a>`;
        }
    }
    
    function closeDocViewer() {
        document.getElementById('docViewerModal').style.display = 'none';
    }
    
    // View Ticket Details
    function viewTicketDetails(ticketId, title) {
        const modal = document.getElementById('ticketModal');
        const modalBody = document.getElementById('ticketDetailsBody');
        
        modalBody.innerHTML = `
            <div style="padding: 10px;">
                <h3><i class="fas fa-ticket-alt"></i> Ticket #${ticketId}</h3>
                <p><strong>Title:</strong> ${title}</p>
                <p><strong>Status:</strong> <span class="status-badge">${document.querySelector(`.status-select[data-id="${ticketId}"]`)?.value || 'Unknown'}</span></p>
                <hr>
                <p><i class="fas fa-info-circle"></i> Full ticket details will be displayed here.</p>
                <p>You can expand this feature to show:</p>
                <ul>
                    <li>Full description</li>
                    <li>Response history</li>
                    <li>Attached documents</li>
                    <li>Student contact info</li>
                </ul>
            </div>
        `;
        modal.style.display = 'flex';
    }
    
    function closeTicketModal() {
        document.getElementById('ticketModal').style.display = 'none';
    }
    
    // Attach document view listeners
    document.querySelectorAll('.view-doc-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const docPath = this.dataset.docPath;
            const docName = this.dataset.docName;
            viewDocument(docPath, docName);
        });
    });
    
    // Attach ticket view listeners
    document.querySelectorAll('.view-ticket').forEach(btn => {
        btn.addEventListener('click', function() {
            const ticketId = this.dataset.id;
            const title = this.dataset.title;
            viewTicketDetails(ticketId, title);
        });
    });
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const docModal = document.getElementById('docViewerModal');
        const ticketModal = document.getElementById('ticketModal');
        if (event.target === docModal) closeDocViewer();
        if (event.target === ticketModal) closeTicketModal();
    }
</script>
</body>
</html>