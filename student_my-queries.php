<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check if user has student role
if ($_SESSION['role'] !== 'student') {
    header("Location: ../" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

require_once '../config/database.php';

$student_id = $_SESSION['student_id'];
$fullname = $_SESSION['fullname'];
$reg_no = $_SESSION['reg_no'];

// Get all tickets for this student
$tickets_query = "SELECT t.*, d.name as department_name 
                  FROM tickets t 
                  LEFT JOIN departments d ON t.department_id = d.id 
                  WHERE t.user_id = '$student_id' 
                  ORDER BY t.created_at DESC";
$tickets_result = mysqli_query($conn, $tickets_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | My Queries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
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
            <div class="logout-item"><a href="../logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
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
                <a href="student_my-queries.php" class="btn-primary"><i class="fas fa-sync-alt"></i> Refresh</a>
            </div>
            <div id="queriesListContainer">
                <?php if(mysqli_num_rows($tickets_result) == 0): ?>
                    <div class="ticket-item">No submitted queries yet. <a href="student_submit-query.php">Submit your first query</a></div>
                <?php else: ?>
                    <?php while($ticket = mysqli_fetch_assoc($tickets_result)): ?>
                        <div class="ticket-item">
                            <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
                                <strong>#<?php echo $ticket['ticket_no']; ?> - <?php echo htmlspecialchars($ticket['title']); ?></strong>
                                <span class="status-badge <?php echo $ticket['status'] == 'resolved' ? 'status-resolved' : ''; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                </span>
                            </div>
                            <div style="margin: 8px 0;">
                                <small><i class="fas fa-building"></i> <?php echo htmlspecialchars($ticket['department_name']); ?> | 
                                🔥 <?php echo ucfirst($ticket['priority']); ?> | 
                                📅 <?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></small>
                            </div>
                            <p><?php echo htmlspecialchars(substr($ticket['description'], 0, 120)); ?><?php echo strlen($ticket['description']) > 120 ? '...' : ''; ?></p>
                            <?php if($ticket['has_document'] && $ticket['document_path']): ?>
                                <div style="margin-top:10px; padding:8px; background:#e8f0f5; border-radius:10px;">
                                    <i class="fas fa-paperclip"></i> Attached: <?php echo htmlspecialchars($ticket['document_name']); ?>
                                    <a href="#" onclick="alert('Document viewer would open here')" style="margin-left:10px;">View</a>
                                </div>
                            <?php endif; ?>
                            <?php if($ticket['status'] != 'resolved'): ?>
                                <button class="resolve-btn" data-id="<?php echo $ticket['id']; ?>" style="background:#1a6e4b; border:none; color:white; padding:5px 14px; border-radius:30px; margin-top:8px; cursor:pointer;">✓ Mark as Resolved</button>
                            <?php else: ?>
                                <?php 
                                $rating_query = "SELECT rating FROM ratings WHERE ticket_id = '{$ticket['id']}' AND user_id = '$student_id'";
                                $rating_result = mysqli_query($conn, $rating_query);
                                $has_rating = mysqli_num_rows($rating_result) > 0;
                                $rating_value = $has_rating ? mysqli_fetch_assoc($rating_result)['rating'] : null;
                                ?>
                                <?php if($has_rating): ?>
                                    <div style="margin-top:8px;"><i class="fas fa-star" style="color:#f5b042;"></i> Rated: <?php echo $rating_value; ?>/5</div>
                                <?php else: ?>
                                    <div class="rating-container" data-id="<?php echo $ticket['id']; ?>" style="margin-top:8px;">
                                        ⭐ Rate resolution: 
                                        <span class="star" data-rate="1" style="cursor:pointer;">★</span>
                                        <span class="star" data-rate="2" style="cursor:pointer;">★</span>
                                        <span class="star" data-rate="3" style="cursor:pointer;">★</span>
                                        <span class="star" data-rate="4" style="cursor:pointer;">★</span>
                                        <span class="star" data-rate="5" style="cursor:pointer;">★</span>
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

<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options);
    }
    setCurrentDate();

    // Resolve ticket functionality
    document.querySelectorAll('.resolve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if(confirm('Mark this ticket as resolved?')) {
                const ticketId = this.dataset.id;
                window.location.href = 'resolve_ticket.php?id=' + ticketId;
            }
        });
    });

    // Rating functionality
    document.querySelectorAll('.rating-container').forEach(container => {
        const ticketId = container.dataset.id;
        container.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rate;
                window.location.href = 'rate_ticket.php?id=' + ticketId + '&rating=' + rating;
            });
        });
    });
</script>
</body>
</html>