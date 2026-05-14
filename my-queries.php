<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | My Queries</title>
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
            <a href="index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="my-queries.php" class="nav-item active"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="index.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
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
                <button id="refreshBtn" class="btn-primary"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div id="queriesListContainer"></div>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    function renderMyQueries() {
        const allTickets = getAllTickets();
        const container = document.getElementById('queriesListContainer');
        
        if (allTickets.length === 0) {
            container.innerHTML = '<div class="ticket-item">No submitted queries yet. <a href="submit-query.html">Submit your first query</a></div>';
            return;
        }
        
        container.innerHTML = allTickets.map(t => `
            <div class="ticket-item">
                <div style="display:flex; justify-content:space-between;">
                    <strong>#${t.id} - ${escapeHtml(t.title)}</strong>
                    <span class="status-badge ${t.status === 'Resolved' ? 'status-resolved' : ''}">${t.status}</span>
                </div>
                <div style="margin: 8px 0;">
                    <small><i class="fas fa-building"></i> ${t.department} | 🔥 ${t.priority} | 📅 ${t.date}</small>
                </div>
                <p>${escapeHtml(t.description.substring(0, 120))}${t.description.length > 120 ? '...' : ''}</p>
                ${t.status !== 'Resolved' ? 
                    `<button class="resolve-btn" data-id="${t.id}" style="background:#1a6e4b; border:none; color:white; padding:5px 14px; border-radius:30px; margin-top:8px; cursor:pointer;">✓ Mark as Resolved</button>` : 
                    (t.rating ? 
                        `<div><i class="fas fa-star" style="color:#f5b042;"></i> Rated: ${t.rating}/5</div>` : 
                        `<div class="rating-container" data-id="${t.id}" style="margin-top:8px;">
                            ⭐ Rate resolution: 
                            <span class="star" data-rate="1" style="cursor:pointer;">★</span>
                            <span class="star" data-rate="2" style="cursor:pointer;">★</span>
                            <span class="star" data-rate="3" style="cursor:pointer;">★</span>
                            <span class="star" data-rate="4" style="cursor:pointer;">★</span>
                            <span class="star" data-rate="5" style="cursor:pointer;">★</span>
                        </div>`
                    )
                }
            </div>
        `).join('');

        document.querySelectorAll('.resolve-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                updateTicketStatus(parseInt(btn.dataset.id), 'Resolved');
                showMessage('✅ Ticket marked as resolved!');
                renderMyQueries();
            });
        });

        document.querySelectorAll('.rating-container').forEach(container => {
            const ticketId = parseInt(container.dataset.id);
            container.querySelectorAll('.star').forEach(star => {
                star.addEventListener('click', () => {
                    addRating(ticketId, parseInt(star.dataset.rate));
                    showMessage(`⭐ Rated! Thanks for your feedback.`);
                    renderMyQueries();
                });
            });
        });
    }

    loadFromLocalStorage();
    initDemoData();
    setCurrentDate();
    document.getElementById('userName').innerText = appData.currentUser.name;
    document.getElementById('studentId').innerText = appData.currentUser.studentId;
    renderMyQueries();

    document.getElementById('refreshBtn').addEventListener('click', () => renderMyQueries());
    
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.clear();
        window.location.href = 'index.html';
    });
</script>
</body>
</html>