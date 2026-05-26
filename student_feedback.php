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

// Get resolved tickets that haven't been rated
$resolved_tickets_query = "SELECT t.*, d.name as department_name 
                           FROM tickets t 
                           LEFT JOIN departments d ON t.department_id = d.id 
                           LEFT JOIN ratings r ON t.id = r.ticket_id AND r.user_id = '$student_id'
                           WHERE t.user_id = '$student_id' AND t.status = 'resolved' AND r.id IS NULL
                           ORDER BY t.resolved_at DESC";
$resolved_result = mysqli_query($conn, $resolved_tickets_query);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $feedback = mysqli_real_escape_string($conn, $_POST['general_feedback']);
    $insert_query = "INSERT INTO feedback (user_id, feedback, created_at) VALUES ('$student_id', '$feedback', NOW())";
    mysqli_query($conn, $insert_query);
    $success_message = "Thank you for your feedback!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Feedback</title>
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
            <a href="student_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="student_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="student_feedback.php" class="nav-item active"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="student_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="../logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
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

        <div class="widget-card">
            <div class="flex-between"><strong>⭐ Rate IAA Helpdesk Service</strong></div>
            <p>Your feedback helps IAA improve response quality.</p>
            <div id="resolvedTicketsList" style="margin-bottom: 20px;">
                <?php if(mysqli_num_rows($resolved_result) == 0): ?>
                    <p>No resolved tickets yet. After your queries are resolved, you can rate them here.</p>
                <?php else: ?>
                    <?php while($ticket = mysqli_fetch_assoc($resolved_result)): ?>
                        <div class="ticket-item">
                            <strong>#<?php echo $ticket['ticket_no']; ?> - <?php echo htmlspecialchars($ticket['title']); ?></strong><br>
                            <small>Department: <?php echo htmlspecialchars($ticket['department_name']); ?> | Resolved: <?php echo date('d/m/Y', strtotime($ticket['resolved_at'])); ?></small>
                            <div class="fb-rating" data-id="<?php echo $ticket['id']; ?>" style="margin-top: 10px;">
                                Rate this resolution: 
                                <span class="fb-star" data-rate="1" style="cursor:pointer;">★</span>
                                <span class="fb-star" data-rate="2" style="cursor:pointer;">★</span>
                                <span class="fb-star" data-rate="3" style="cursor:pointer;">★</span>
                                <span class="fb-star" data-rate="4" style="cursor:pointer;">★</span>
                                <span class="fb-star" data-rate="5" style="cursor:pointer;">★</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>General feedback / suggestions</label>
                    <textarea name="general_feedback" rows="3" placeholder="Share your experience..."></textarea>
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

    // Rating functionality
    document.querySelectorAll('.fb-rating').forEach(container => {
        const ticketId = container.dataset.id;
        container.querySelectorAll('.fb-star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rate;
                window.location.href = 'rate_ticket.php?id=' + ticketId + '&rating=' + rating;
            });
        });
    });
</script>
</body>
</html>