<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Submit Query</title>
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
            
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="index.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Submit New Query</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📝 Submit a new query – IAA Helpdesk</strong></div>
            <form id="queryForm">
                <div class="form-group">
                    <label>Query Title *</label>
                    <input type="text" id="qTitle" placeholder="e.g., Missing examination CSC 101" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="qCategory">
                        <option>Examination issues</option>
                        <option>Fee-related query</option>
                        <option>Portal login problem</option>
                        <option>Course registration error</option>
                        <option>Academic documents</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select id="qDept">
                        <option>Examination & Records</option>
                        <option>Finance Office</option>
                        <option>ICT Support</option>
                        <option>Academic Registry</option>
                        <option>Dean of Students</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select id="qPriority">
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                        <option>Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea rows="5" id="qDesc" placeholder="Provide full details..."></textarea>
                </div>
                <div style="display:flex; gap:12px; justify-content:end;">
                    <a href="index.php" class="btn-primary" style="background:#7f8c8d;">Cancel</a>
                    <button type="submit" class="btn-primary">Submit Query</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    loadFromLocalStorage();
    initDemoData();
    setCurrentDate();
    document.getElementById('userName').innerText = appData.currentUser.name;
    document.getElementById('studentId').innerText = appData.currentUser.studentId;

    document.getElementById('queryForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const title = document.getElementById('qTitle').value;
        const category = document.getElementById('qCategory').value;
        const department = document.getElementById('qDept').value;
        const priority = document.getElementById('qPriority').value;
        const description = document.getElementById('qDesc').value;

        if (!title || !description) {
            showMessage('Please fill title and description');
            return;
        }

        addTicket(title, category, department, priority, description);
        showMessage('✅ Query submitted successfully!');
        window.location.href = 'my-queries.html';
    });

    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.clear();
        window.location.href = 'index.php';
    });
</script>
</body>
</html>