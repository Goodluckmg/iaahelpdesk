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

// Get resolved tickets with responses that haven't been rated
$resolved_tickets_query = "SELECT t.*, d.name as department_name, 
                                  r.message as response_message, r.created_at as response_date,
                                  u.fullname as responder_name, u.role as responder_role
                           FROM tickets t 
                           LEFT JOIN departments d ON t.department_id = d.id 
                           LEFT JOIN responses r ON t.id = r.ticket_id
                           LEFT JOIN students u ON r.responder_id = u.id
                           LEFT JOIN feedback f ON t.id = f.ticket_id AND f.user_id = '$student_id'
                           WHERE t.user_id = '$student_id' AND t.status = 'resolved' AND f.id IS NULL
                           ORDER BY t.resolved_at DESC";
$resolved_result = mysqli_query($conn, $resolved_tickets_query);

// Get already rated tickets (history)
$rated_tickets_query = "SELECT t.*, d.name as department_name, f.rating, f.created_at as feedback_date,
                               r.message as response_message
                        FROM tickets t 
                        LEFT JOIN departments d ON t.department_id = d.id 
                        LEFT JOIN feedback f ON t.id = f.ticket_id AND f.user_id = '$student_id'
                        LEFT JOIN responses r ON t.id = r.ticket_id
                        WHERE t.user_id = '$student_id' AND t.status = 'resolved' AND f.id IS NOT NULL
                        ORDER BY f.created_at DESC LIMIT 5";
$rated_result = mysqli_query($conn, $rated_tickets_query);

// Handle rating submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate_ticket') {
    header('Content-Type: application/json');
    
    $ticket_id = intval($_POST['ticket_id']);
    $rating = intval($_POST['rating']);
    $feedback_text = isset($_POST['feedback_text']) ? mysqli_real_escape_string($conn, $_POST['feedback_text']) : '';
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Tafadhali chagua nyota 1 hadi 5']);
        exit();
    }
    
    $check_query = "SELECT id FROM tickets WHERE id = $ticket_id AND user_id = $student_id AND status = 'resolved'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $check_rating = "SELECT id FROM feedback WHERE ticket_id = $ticket_id AND user_id = $student_id";
        $rating_result = mysqli_query($conn, $check_rating);
        
        if (mysqli_num_rows($rating_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Umeshakadiria tiketi hii tayari']);
        } else {
            $insert_query = "INSERT INTO feedback (ticket_id, user_id, rating, feedback_text) 
                            VALUES ($ticket_id, $student_id, $rating, '$feedback_text')";
            if (mysqli_query($conn, $insert_query)) {
                echo json_encode(['success' => true, 'message' => 'Asante kwa ukadiriaji wako!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hitilafu ya database: ' . mysqli_error($conn)]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tiketi haijapatikana au haijatatuliwa']);
    }
    exit();
}

// Handle general feedback submission (FIXED - removed department_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_text = mysqli_real_escape_string($conn, $_POST['general_feedback']);
    
    if (!empty($feedback_text)) {
        $insert_query = "INSERT INTO feedback (user_id, feedback_text) VALUES ('$student_id', '$feedback_text')";
        if (mysqli_query($conn, $insert_query)) {
            $success_message = "Asante kwa maoni yako!";
        } else {
            $error_message = "Hitilafu: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Tafadhali andika maoni yako kabla ya kutuma.";
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
        
        .ticket-card {
            background: #f9fdfe;
            border-left: 3px solid #f39c12;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .response-box {
            background: #e8f0f5;
            border-radius: 10px;
            padding: 12px;
            margin: 10px 0;
            border-left: 3px solid #27ae60;
        }
        .rating-box {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2edf2;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .btn-primary {
            background: #f39c12;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        .rated-star {
            color: #f5b042;
            font-size: 1rem;
        }
        .rated-star.empty {
            color: #e2e8f0;
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

        <!-- SECTION 1: Pending Ratings -->
        <div class="widget-card">
            <div class="flex-between"><strong>⭐ Rate Resolved Queries</strong></div>
            <p>Tafadhali kagua majibu yaliyotolewa na ukadiria huduma uliyopokea.</p>
            
            <div id="resolvedTicketsList">
                <?php if(mysqli_num_rows($resolved_result) == 0): ?>
                    <div style="text-align:center; padding:30px; color:#7f8c8d;">
                        <i class="fas fa-check-circle" style="font-size:2rem;"></i>
                        <p>Hakuna tiketi mpya zilizotatuliwa. Baada ya maswali yako kujibiwa, utaweza kuyakadiria hapa.</p>
                    </div>
                <?php else: ?>
                    <?php while($ticket = mysqli_fetch_assoc($resolved_result)): ?>
                        <div class="ticket-card" data-ticket-id="<?php echo $ticket['id']; ?>">
                            <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                                <strong><i class="fas fa-ticket-alt"></i> #<?php echo $ticket['ticket_no']; ?></strong>
                                <small><i class="fas fa-building"></i> <?php echo htmlspecialchars($ticket['department_name']); ?></small>
                            </div>
                            <div style="margin-top: 5px;">
                                <strong>Swali lako:</strong> <?php echo htmlspecialchars($ticket['title']); ?>
                            </div>
                            
                            <?php if(!empty($ticket['response_message'])): ?>
                                <div class="response-box">
                                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                                        <strong><i class="fas fa-reply-all"></i> Jibu kutoka <?php echo htmlspecialchars($ticket['responder_role'] ?? 'Finance Officer'); ?>:</strong>
                                        <small><?php echo date('d/m/Y H:i', strtotime($ticket['response_date'])); ?></small>
                                    </div>
                                    <p style="margin-top: 8px;"><?php echo nl2br(htmlspecialchars($ticket['response_message'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="rating-box">
                                <label style="display: block; margin-bottom: 8px;"><i class="fas fa-star"></i> Kadiria huduma:</label>
                                <div class="stars-container">
                                    <span class="rating-star" data-rate="1">★</span>
                                    <span class="rating-star" data-rate="2">★</span>
                                    <span class="rating-star" data-rate="3">★</span>
                                    <span class="rating-star" data-rate="4">★</span>
                                    <span class="rating-star" data-rate="5">★</span>
                                </div>
                                <textarea class="rating-feedback" placeholder="Maoni yako kuhusu jibu hili (si lazima)" rows="2" style="width:100%; margin-top:10px; padding:8px; border:1px solid #ddd; border-radius:8px;"></textarea>
                                <button class="submit-rating-btn" data-id="<?php echo $ticket['id']; ?>" style="margin-top:10px; background:#27ae60; color:white; border:none; padding:5px 15px; border-radius:20px; cursor:pointer;">
                                    <i class="fas fa-paper-plane"></i> Tuma Ukadiriaji
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 2: Rating History -->
        <?php if(mysqli_num_rows($rated_result) > 0): ?>
        <div class="widget-card">
            <div class="flex-between"><strong>📋 History ya Ukadiriaji Wako</strong></div>
            <?php while($rated = mysqli_fetch_assoc($rated_result)): ?>
                <div class="ticket-card" style="background:#f8f9fa;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong>#<?php echo $rated['ticket_no']; ?></strong>
                        <small><?php echo date('d/m/Y', strtotime($rated['feedback_date'])); ?></small>
                    </div>
                    <div style="margin: 8px 0;">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $rated['rating'] ? 'rated-star' : 'rated-star empty'; ?>" style="color: <?php echo $i <= $rated['rating'] ? '#f5b042' : '#e2e8f0'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <small><i class="fas fa-building"></i> <?php echo htmlspecialchars($rated['department_name']); ?></small>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- SECTION 3: General Feedback (FIXED - removed department dropdown) -->
        <div class="widget-card">
            <div class="flex-between"><strong>💬 Maoni ya Jumla</strong></div>
            <p>Shiriki maoni yako kuhusu mfumo wa IAA Helpdesk kwa ujumla.</p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Maoni yako <span style="color:red">*</span></label>
                    <textarea name="general_feedback" rows="4" placeholder="Tueleze uzoefu wako na mfumo wa IAA Helpdesk..." required></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="btn-primary"><i class="fas fa-paper-plane"></i> Wasilisha Maoni</button>
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

    // Rating functionality
    document.querySelectorAll('.ticket-card').forEach(card => {
        const ticketId = card.dataset.ticketId;
        const stars = card.querySelectorAll('.rating-star');
        const submitBtn = card.querySelector('.submit-rating-btn');
        const feedbackTextarea = card.querySelector('.rating-feedback');
        let selectedRating = 0;
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rate);
                stars.forEach((s, index) => {
                    if (index < selectedRating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        if (submitBtn) {
            submitBtn.addEventListener('click', async function() {
                if (selectedRating === 0) {
                    showNotification('Tafadhali chagua ukadiriaji (nyota 1-5) kwanza', 'error');
                    return;
                }
                
                const feedbackText = feedbackTextarea ? feedbackTextarea.value : '';
                
                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'rate_ticket');
                    formData.append('ticket_id', ticketId);
                    formData.append('rating', selectedRating);
                    formData.append('feedback_text', feedbackText);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        card.style.opacity = '0.5';
                        const btns = card.querySelectorAll('button, .rating-star');
                        btns.forEach(btn => btn.style.pointerEvents = 'none');
                        setTimeout(() => {
                            card.remove();
                        }, 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                } catch (error) {
                    showNotification('Hitilafu ya mtandao. Tafadhali jaribu tena.', 'error');
                }
            });
        }
    });
</script>
</body>
</html>