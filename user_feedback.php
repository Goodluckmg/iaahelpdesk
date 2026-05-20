<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="student-name" id="userName">Goodluck</div>
            <div class="student-id"><i class="fas fa-id-card"></i> <span id="studentId">BCS-01-0131-2023</span></div>
        </div>
        <div class="nav-menu">
              <a href="user_index.php" class="nav-item active"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="user_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="user_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="user_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="user_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="user_edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="user_startup.php" class="nav-item active"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="user_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="index.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Feedback & Rating System</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>⭐ Rate IAA Helpdesk Service</strong></div>
            <p>Your feedback helps IAA improve response quality.</p>
            <div id="resolvedTicketsList" style="margin-bottom: 20px;"></div>
            
            <div class="form-group">
                <label>General feedback / suggestions</label>
                <textarea id="generalFeedback" rows="3" placeholder="Share your experience..."></textarea>
            </div>
            <button id="submitFeedbackBtn" class="btn-primary">Submit Feedback</button>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    function renderFeedback() {
        const resolved = getResolvedTickets();
        const container = document.getElementById('resolvedTicketsList');
        
        if (resolved.length === 0) {
            container.innerHTML = '<p>No resolved tickets yet. After your queries are resolved, you can rate them here.</p>';
            return;
        }
        
        container.innerHTML = resolved.map(t => `
            <div class="ticket-item">
                <strong>#${t.id} - ${escapeHtml(t.title)}</strong><br>
                <small>Department: ${t.department} | Resolved: ${t.date}</small>
                ${t.rating ? 
                    `<div style="margin-top: 8px;"><i class="fas fa-star" style="color:#f5b042;"></i> Your rating: ${t.rating}/5</div>` : 
                    `<div class="fb-rating" data-id="${t.id}" style="margin-top: 10px;">
                        Rate this resolution: 
                        <span class="fb-star" data-rate="1" style="cursor:pointer;">★</span>
                        <span class="fb-star" data-rate="2" style="cursor:pointer;">★</span>
                        <span class="fb-star" data-rate="3" style="cursor:pointer;">★</span>
                        <span class="fb-star" data-rate="4" style="cursor:pointer;">★</span>
                        <span class="fb-star" data-rate="5" style="cursor:pointer;">★</span>
                    </div>`
                }
            </div>
        `).join('');

        document.querySelectorAll('.fb-rating').forEach(container => {
            const ticketId = parseInt(container.dataset.id);
            container.querySelectorAll('.fb-star').forEach(star => {
                star.addEventListener('click', () => {
                    addRating(ticketId, parseInt(star.dataset.rate));
                    showMessage('⭐ Rating saved! Thank you for your feedback.');
                    renderFeedback();
                });
            });
        });
    }

    loadFromLocalStorage();
    initDemoData();
    setCurrentDate();
    document.getElementById('userName').innerText = appData.currentUser.name;
    document.getElementById('studentId').innerText = appData.currentUser.studentId;
    renderFeedback();

    document.getElementById('submitFeedbackBtn').addEventListener('click', () => {
        const feedback = document.getElementById('generalFeedback').value;
        if (feedback) {
            showMessage('Thank you for your valuable feedback!');
            document.getElementById('generalFeedback').value = '';
        } else {
            showMessage('Please write your feedback before submitting.');
        }
    });

    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.clear();
        window.location.href = 'index.php';
    });
</script>
</body>
</html>