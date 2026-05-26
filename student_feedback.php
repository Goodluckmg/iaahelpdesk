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

// Get resolved tickets that haven't been rated
$resolved_tickets_query = "SELECT t.*, d.name as department_name 
                           FROM tickets t 
                           LEFT JOIN departments d ON t.department_id = d.id 
                           LEFT JOIN feedback f ON t.id = f.ticket_id AND f.user_id = '$student_id'
                           WHERE t.user_id = '$student_id' AND t.status = 'resolved' AND f.id IS NULL
                           ORDER BY t.resolved_at DESC";
$resolved_result = mysqli_query($conn, $resolved_tickets_query);

// Handle rating submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate_ticket') {
    header('Content-Type: application/json');
    
    $ticket_id = intval($_POST['ticket_id']);
    $rating = intval($_POST['rating']);
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit();
    }
    
    $check_query = "SELECT id FROM tickets WHERE id = $ticket_id AND user_id = $student_id AND status = 'resolved'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $check_rating = "SELECT id FROM feedback WHERE ticket_id = $ticket_id AND user_id = $student_id";
        $rating_result = mysqli_query($conn, $check_rating);
        
        if (mysqli_num_rows($rating_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already rated this ticket']);
        } else {
            $insert_query = "INSERT INTO feedback (ticket_id, user_id, rating) VALUES ($ticket_id, $student_id, $rating)";
            if (mysqli_query($conn, $insert_query)) {
                echo json_encode(['success' => true, 'message' => 'Thank you for your rating!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ticket not found or not resolved']);
    }
    exit();
}

// Handle general feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_text = mysqli_real_escape_string($conn, $_POST['general_feedback']);
    
    if (!empty($feedback_text)) {
        $insert_query = "INSERT INTO feedback (user_id, feedback_text) VALUES ('$student_id', '$feedback_text')";
        if (mysqli_query($conn, $insert_query)) {
            $success_message = "Thank you for your valuable feedback!";
        } else {
            $error_message = "Error saving feedback: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Please write your feedback before submitting.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Feedback</title>
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
        .rating-star {
            cursor: pointer;
            font-size: 1.5rem;
            color: #e2e8f0;
            transition: color 0.2s;
        }
        .rating-star:hover,
        .rating-star.active {
            color: #f5b042;
        }
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: none; align-items: center; gap: 10px; }
        .message.show { display: flex; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; }
        
        /* Avatar styles */
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
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <!-- SIDEBAR AVATAR - SAHIHI (HAKUNA DIV MBILI) -->
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
            <a href="student_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="student_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="student_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="student_feedback.php" class="nav-item active"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="student_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Feedback & Rating System</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="message message-success show"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if(isset($error_message)): ?>
            <div class="message message-error show"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between"><strong>⭐ Rate IAA Helpdesk Service</strong></div>
            <p>Your feedback helps IAA improve response quality.</p>
            
            <div id="resolvedTicketsList" style="margin-bottom: 20px;">
                <?php if(mysqli_num_rows($resolved_result) == 0): ?>
                    <p>No resolved tickets yet. After your queries are resolved, you can rate them here.</p>
                <?php else: ?>
                    <?php while($ticket = mysqli_fetch_assoc($resolved_result)): ?>
                        <div class="ticket-item" data-ticket-id="<?php echo $ticket['id']; ?>">
                            <strong>#<?php echo $ticket['ticket_no']; ?> - <?php echo htmlspecialchars($ticket['title']); ?></strong><br>
                            <small>Department: <?php echo htmlspecialchars($ticket['department_name']); ?> | Resolved: <?php echo date('d/m/Y', strtotime($ticket['resolved_at'])); ?></small>
                            <div class="rating-box" style="margin-top: 10px;">
                                <span class="rating-star" data-rate="1">★</span>
                                <span class="rating-star" data-rate="2">★</span>
                                <span class="rating-star" data-rate="3">★</span>
                                <span class="rating-star" data-rate="4">★</span>
                                <span class="rating-star" data-rate="5">★</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>General feedback / suggestions</label>
                    <textarea name="general_feedback" rows="3" placeholder="Share your experience with IAA Helpdesk..."></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="btn-primary">Submit Feedback</button>
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

    // Rating functionality (AJAX)
    document.querySelectorAll('.rating-box').forEach(box => {
        const ticketDiv = box.closest('.ticket-item');
        if (ticketDiv) {
            const ticketId = ticketDiv.dataset.ticketId;
            const stars = box.querySelectorAll('.rating-star');
            
            stars.forEach(star => {
                star.addEventListener('click', async function() {
                    const rating = this.dataset.rate;
                    
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                    
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
                            stars.forEach(s => s.style.pointerEvents = 'none');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    } catch (error) {
                        showNotification('Network error. Please try again.', 'error');
                    }
                });
            });
        }
    });
</script>
</body>
</html>