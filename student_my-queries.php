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

// Handle AJAX requests for resolving ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'resolve_ticket') {
        $ticket_id = intval($_POST['ticket_id']);
        
        $check_query = "SELECT id FROM tickets WHERE id = $ticket_id AND user_id = $student_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $update_query = "UPDATE tickets SET status = 'resolved', resolved_at = NOW() WHERE id = $ticket_id";
            if (mysqli_query($conn, $update_query)) {
                echo json_encode(['success' => true, 'message' => 'Ticket marked as resolved successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'rate_ticket') {
        $ticket_id = intval($_POST['ticket_id']);
        $rating = intval($_POST['rating']);
        
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid rating']);
            exit();
        }
        
        $check_query = "SELECT id FROM tickets WHERE id = $ticket_id AND user_id = $student_id AND status = 'resolved'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $check_rating = "SELECT id FROM ratings WHERE ticket_id = $ticket_id AND user_id = $student_id";
            $rating_result = mysqli_query($conn, $check_rating);
            
            if (mysqli_num_rows($rating_result) > 0) {
                echo json_encode(['success' => false, 'message' => 'You have already rated this ticket']);
            } else {
                $insert_query = "INSERT INTO ratings (ticket_id, user_id, rating) VALUES ($ticket_id, $student_id, $rating)";
                if (mysqli_query($conn, $insert_query)) {
                    echo json_encode(['success' => true, 'message' => 'Thank you for your rating!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket not found or not resolved']);
        }
        exit();
    }
}

// Get all tickets for this student
$tickets_query = "SELECT t.*, d.name as department_name 
                  FROM tickets t 
                  LEFT JOIN departments d ON t.department_id = d.id 
                  WHERE t.user_id = '$student_id' 
                  ORDER BY t.created_at DESC";
$tickets_result = mysqli_query($conn, $tickets_query);

// Pre-fetch ratings for resolved tickets
$rated_tickets = [];
$ratings_query = "SELECT ticket_id, rating FROM ratings WHERE user_id = $student_id";
$ratings_result = mysqli_query($conn, $ratings_query);
while ($row = mysqli_fetch_assoc($ratings_result)) {
    $rated_tickets[$row['ticket_id']] = $row['rating'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | My Queries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .toast-success { background: #27ae60; }
        .toast-error { background: #e74c3c; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .document-viewer-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .document-viewer-content {
            max-width: 90%;
            max-height: 90%;
            background: white;
            border-radius: 16px;
            padding: 20px;
            position: relative;
        }
        .close-doc-viewer {
            position: absolute;
            top: 10px;
            right: 20px;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            cursor: pointer;
        }
        .doc-image { max-width: 100%; max-height: 80vh; }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="student-name"><?php echo htmlspecialchars($fullname); ?></div>
            <div class="student-id"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($reg_no); ?></div>
        </div>
        <div class="nav-menu">
            <a href="student_index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="student_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="student_my-queries.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
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
            <h1 class="page-title">My Queries & Tracking</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>
        <div class="widget-card">
            <div class="flex-between">
                <strong>📌 All my submitted tickets</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div id="queriesListContainer">
                <?php if(mysqli_num_rows($tickets_result) == 0): ?>
                    <div class="ticket-item" style="text-align:center;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                        No submitted queries yet. 
                        <a href="student_submit-query.php">Submit your first query</a>
                    </div>
                <?php else: ?>
                    <?php while($ticket = mysqli_fetch_assoc($tickets_result)): ?>
                        <?php $has_rating = isset($rated_tickets[$ticket['id']]); ?>
                        <?php $rating_value = $has_rating ? $rated_tickets[$ticket['id']] : null; ?>
                        <div class="ticket-item" data-ticket-id="<?php echo $ticket['id']; ?>">
                            <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
                                <strong>#<?php echo htmlspecialchars($ticket['ticket_no']); ?> - <?php echo htmlspecialchars($ticket['title']); ?></strong>
                                <span class="status-badge <?php echo $ticket['status'] == 'resolved' ? 'status-resolved' : ''; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                </span>
                            </div>
                            <div style="margin: 8px 0;">
                                <small><i class="fas fa-building"></i> <?php echo htmlspecialchars($ticket['department_name'] ?? 'Unknown'); ?> | 
                                🔥 <?php echo ucfirst($ticket['priority']); ?> | 
                                📅 <?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></small>
                            </div>
                            <p><?php echo htmlspecialchars(substr($ticket['description'], 0, 150)); ?><?php echo strlen($ticket['description']) > 150 ? '...' : ''; ?></p>
                            
                            <?php if($ticket['has_document'] && $ticket['document_path']): ?>
                                <div style="margin-top:10px; padding:8px; background:#e8f0f5; border-radius:10px;">
                                    <i class="fas fa-paperclip"></i> Attached: <?php echo htmlspecialchars($ticket['document_name']); ?>
                                    <button class="view-doc-btn" data-doc-path="<?php echo htmlspecialchars($ticket['document_path']); ?>" data-doc-name="<?php echo htmlspecialchars($ticket['document_name']); ?>" style="background:#2c7da0; color:white; border:none; padding:3px 12px; border-radius:15px; margin-left:10px; cursor:pointer;">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($ticket['status'] != 'resolved'): ?>
                                <button class="resolve-btn" data-id="<?php echo $ticket['id']; ?>" style="background:#1a6e4b; border:none; color:white; padding:5px 14px; border-radius:30px; margin-top:8px; cursor:pointer;">
                                    <i class="fas fa-check"></i> Mark as Resolved
                                </button>
                            <?php else: ?>
                                <?php if($has_rating): ?>
                                    <div style="margin-top:8px;">
                                        <i class="fas fa-star" style="color:#f5b042;"></i> Rated: <?php echo $rating_value; ?>/5
                                    </div>
                                <?php else: ?>
                                    <div class="rating-container" data-id="<?php echo $ticket['id']; ?>" style="margin-top:8px;">
                                        <span style="font-size:0.8rem;">⭐ Rate resolution:</span>
                                        <span class="star" data-rate="1" style="cursor:pointer; font-size:1.2rem;">★</span>
                                        <span class="star" data-rate="2" style="cursor:pointer; font-size:1.2rem;">★</span>
                                        <span class="star" data-rate="3" style: "cursor:pointer; font-size:1.2rem;">★</span>
                                        <span class="star" data-rate="4" style="cursor:pointer; font-size:1.2rem;">★</span>
                                        <span class="star" data-rate="5" style="cursor:pointer; font-size:1.2rem;">★</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Document Viewer Modal -->
<div id="docViewerModal" class="document-viewer-modal">
    <div class="document-viewer-content">
        <button class="close-doc-viewer" onclick="closeDocViewer()">✕ Close</button>
        <div id="docViewerBody" style="text-align: center;">
            <div class="loading-spinner"></div>
            <p>Loading document...</p>
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

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `toast-notification toast-${type}`;
        let icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
        notification.innerHTML = `${icon} ${message}`;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Resolve ticket
    document.querySelectorAll('.resolve-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const ticketId = this.dataset.id;
            if (!confirm('Mark this ticket as resolved?')) return;
            
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="loading-spinner" style="width:14px; height:14px;"></span> Processing...';
            this.disabled = true;
            
            try {
                const formData = new URLSearchParams();
                formData.append('action', 'resolve_ticket');
                formData.append('ticket_id', ticketId);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            } catch (error) {
                showNotification('Network error. Please try again.', 'error');
                this.innerHTML = originalHtml;
                this.disabled = false;
            }
        });
    });

    // Rating functionality
    document.querySelectorAll('.rating-container').forEach(container => {
        const ticketId = container.dataset.id;
        container.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', async function() {
                const rating = this.dataset.rate;
                
                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'rate_ticket');
                    formData.append('ticket_id', ticketId);
                    formData.append('rating', rating);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                } catch (error) {
                    showNotification('Network error. Please try again.', 'error');
                }
            });
        });
    });

    // Document viewer
    const docModal = document.getElementById('docViewerModal');
    
    window.viewDocument = function(docPath, docName) {
        const modalBody = document.getElementById('docViewerBody');
        modalBody.innerHTML = '<div class="loading-spinner"></div><p>Loading document...</p>';
        docModal.style.display = 'flex';
        
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
                modalBody.innerHTML = `<p>Unable to preview. <a href="${docPath}" download="${docName}">Download</a></p>`;
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
            modalBody.innerHTML = `<p>Preview not available. <a href="${docPath}" download="${docName}">Download</a></p>`;
        }
    };
    
    window.closeDocViewer = function() {
        docModal.style.display = 'none';
    };
    
    document.querySelectorAll('.view-doc-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const docPath = btn.dataset.docPath;
            const docName = btn.dataset.docName;
            viewDocument(docPath, docName);
        });
    });
    
    docModal.addEventListener('click', function(e) {
        if (e.target === docModal) closeDocViewer();
    });

    document.getElementById('refreshBtn')?.addEventListener('click', () => {
        window.location.reload();
    });
</script>
</body>
</html>