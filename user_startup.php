<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Startup Hub | Fursa, Mawazo & Innovation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/statup.css">
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
            <a href="user_index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="user_submit-query.php" class="nav-item"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="user_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="user_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="user_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="user_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="user_startup.php" class="nav-item active"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar"><h1 class="page-title">Startup Hub | Fursa, Mawazo & Innovation</h1><div class="date-badge notification-badge" id="notifyBtn"><i class="fas fa-bell"></i><span class="badge-count" id="notificationCount">3</span></div></div>
        <div class="startup-tabs"><button class="tab-btn active" data-tab="opportunities"><i class="fas fa-briefcase"></i> Fursa & Nafasi</button><button class="tab-btn" data-tab="ideas"><i class="fas fa-lightbulb"></i> Mawazo ya Biashara</button><button class="tab-btn" data-tab="advisory"><i class="fas fa-chalkboard-user"></i> Ushauri & Innovation</button></div>
        <div id="opportunitiesTab" class="tab-content"><div style="margin-bottom:20px;"><select id="filterCategory" style="padding:8px; border-radius:20px; border:1px solid #cbdbe6;"><option value="all">Fursa Zote</option><option value="job">Kazi / Short Term Jobs</option><option value="training">Mafunzo / Training</option><option value="internship">Internship / Field Attachment</option></select></div><div id="opportunitiesList"></div></div>
        <div id="ideasTab" class="tab-content" style="display:none;"><div class="idea-form"><h3 style="margin-bottom:15px;"><i class="fas fa-lightbulb"></i> Wasilisha Wazo Lako la Biashara</h3><div class="form-group"><label>Jina la Wazo</label><input type="text" id="ideaTitle" placeholder="Mfano: Platform ya kuuza mboga mtandaoni"></div><div class="form-group"><label>Kategoria ya Biashara</label><select id="ideaCategory"><option>Teknolojia / Tech</option><option>Kilimo / Agriculture</option><option>Biashara / Trade</option><option>Huduma / Services</option><option>Ubunifu / Creative</option></select></div><div class="form-group"><label>Maelezo ya Wazo</label><textarea id="ideaDesc" rows="4" placeholder="Elezea wazo lako kwa kina..."></textarea></div><div class="form-group"><label>Unachohitaji kuanza</label><input type="text" id="ideaNeed" placeholder="Mfano: Capital, Training, Mentor, Equipment"></div><button class="submit-btn" id="submitIdeaBtn"><i class="fas fa-paper-plane"></i> Wasilisha Wazo</button></div><h3><i class="fas fa-list"></i> Mawazo Yaliyowasilishwa</h3><div id="ideasList"></div></div>
        <div id="advisoryTab" class="tab-content" style="display:none;"><div class="advisory-section"><h3><i class="fas fa-chalkboard-user"></i> Startup Office - Innovation Hub</h3><p style="margin-top:10px;">Tunakusaidia kuboresha mawazo yako, kuunganisha na wataalam, na kupata fursa za ufadhili.</p><div class="advisor-card"><div class="advisor-icon"><i class="fas fa-user-tie"></i></div><div class="advisor-info"><h4>Dr. Sarah Mushi</h4><p>Head of Innovation & Startup Office</p><p><i class="fas fa-envelope"></i> sarah.mushi@iaa.ac.tz</p></div><button class="contact-advisor" onclick="alert('Barua pepe imetumwa kwa Startup Office')"><i class="fas fa-envelope"></i> Wasiliana</button></div><div class="advisor-card"><div class="advisor-icon"><i class="fas fa-chart-line"></i></div><div class="advisor-info"><h4>Mr. John Peter</h4><p>Business Development Advisor</p><p><i class="fas fa-phone"></i> +255 712 345 678</p></div><button class="contact-advisor" onclick="alert('Umeomba ushauri. Tutawasiliana nawe soon!')"><i class="fas fa-calendar-alt"></i> Omba Ushauri</button></div></div><div style="background:white; border-radius:20px; padding:20px; margin-top:20px;"><h3><i class="fas fa-graduation-cap"></i> Innovation Resources</h3><ul style="margin-top:15px; list-style:none;"><li style="padding:10px; border-bottom:1px solid #e2edf2;"><i class="fas fa-file-alt"></i> <strong>Business Plan Template</strong> - Download template ya mpango wa biashara</li><li style="padding:10px; border-bottom:1px solid #e2edf2;"><i class="fas fa-video"></i> <strong>Video Tutorials</strong> - Jinsi ya kuanzisha biashara</li><li style="padding:10px; border-bottom:1px solid #e2edf2;"><i class="fas fa-handshake"></i> <strong>Mentorship Program</strong> - Jiunge na wajasiriamali waliobobea</li><li style="padding:10px;"><i class="fas fa-dollar-sign"></i> <strong>Grant Opportunities</strong> - Fursa za ufadhili kwa wanafunzi</li></ul></div></div>
    </main>
</div>

<div id="notificationModal" class="modal"><div class="modal-content"><h3 style="margin-bottom:15px;"><i class="fas fa-bell"></i> Notifications</h3><div id="notificationsList"></div><button class="submit-btn" style="margin-top:15px; width:100%;" onclick="closeModal()">Close</button></div></div>

<script>
    let opportunities = [{ id:1, title:"Digital Marketing Training", type:"training", category:"training", description:"Mafunzo ya masaa 40 kuhusu Digital Marketing, SEO na Social Media.", deadline:"2024-07-15", contact:"info@iaa.ac.tz" },{ id:2, title:"Data Entry Clerk", type:"job", category:"job", description:"Nafasi ya kazi ya muda (3 months) kwenye ofisi ya IT. Stipend available.", deadline:"2024-06-20", contact:"hr@iaa.ac.tz" },{ id:3, title:"Freelance Writer", type:"job", category:"job", description:"Tunatafuta waandishi wa makala kwa ajili ya blog ya chuo.", deadline:"2024-06-25", contact:"media@iaa.ac.tz" },{ id:4, title:"Entrepreneurship Workshop", type:"training", category:"training", description:"Workshop ya siku 2 kuhusu kuanzisha biashara na kupata capital.", deadline:"2024-07-10", contact:"startup@iaa.ac.tz" },{ id:5, title:"Web Development Intern", type:"internship", category:"internship", description:"Internship ya miezi 6 kwa wanafunzi wa Computer Science.", deadline:"2024-06-30", contact:"ict@iaa.ac.tz" }];
    let ideas = [{ id:1, title:"Mobile App for Food Delivery", category:"Teknolojia", description:"App ya kuagiza chakula kwa students", need:"Capital na Developer", status:"Pending", date:"2024-06-01" },{ id:2, title:"Organic Farming Project", category:"Kilimo", description:"Kulima mboga na matunda kwa kutumia greenhouse", need:"Land and Equipment", status:"Under Review", date:"2024-05-28" }];
    let notifications = [{ id:1, title:"New Job Opportunity!", message:"Data Entry Clerk position available. Apply by June 20th.", read:false, date:"2024-06-10" },{ id:2, title:"Training Alert", message:"Digital Marketing Training starts July 1st. Register now!", read:false, date:"2024-06-09" },{ id:3, title:"Startup Office Hours", message:"Office hours every Tuesday 2-4pm. Book your slot!", read:false, date:"2024-06-08" }];
    let nextIdeaId = 3;
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    let currentUserName = loggedUser.name || 'Goodluck V Vincent';
    let currentUserReg = loggedUser.regNo || 'BCS-01-0131-2023';

    function setCurrentDate() { const options = { weekday:'short', year:'numeric', month:'short', day:'numeric' }; const dateElement = document.getElementById('currentDate'); if(dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options); }
    function loadData() { renderOpportunities('all'); renderIdeas(); }
    function saveOpportunities() { localStorage.setItem('startup_opportunities', JSON.stringify(opportunities)); }
    function saveIdeas() { localStorage.setItem('startup_ideas', JSON.stringify(ideas)); }
    function renderOpportunities(filter) { let filtered = filter !== 'all' ? opportunities.filter(o => o.category === filter) : opportunities; const container = document.getElementById('opportunitiesList'); if(filtered.length === 0) { container.innerHTML = '<div style="text-align:center; padding:40px;">⚠️ Hakuna fursa kwa sasa. Check back later!</div>'; return; } container.innerHTML = filtered.map(opp => `<div class="opportunity-card"><div class="card-header"><span class="card-type ${opp.type}"><i class="fas ${opp.type === 'job' ? 'fa-briefcase' : opp.type === 'training' ? 'fa-graduation-cap' : 'fa-star'}"></i> ${opp.type === 'job' ? 'Kazi' : opp.type === 'training' ? 'Mafunzo' : 'Nafasi'}</span><span style="font-size:0.7rem;"><i class="fas fa-calendar"></i> Deadline: ${opp.deadline}</span></div><div class="card-title">${opp.title}</div><div class="card-desc">${opp.description}</div><div class="card-footer"><span><i class="fas fa-envelope"></i> ${opp.contact}</span><button class="apply-btn" onclick="applyOpportunity('${opp.title}')"><i class="fas fa-paper-plane"></i> Apply / Register</button></div></div>`).join(''); }
    function renderIdeas() { const container = document.getElementById('ideasList'); if(ideas.length === 0) { container.innerHTML = '<div style="text-align:center; padding:40px;">Bado hakuna mawazo yaliyowasilishwa. Kuwa wa kwanza!</div>'; return; } container.innerHTML = ideas.map(idea => `<div class="idea-card"><div class="card-header"><span class="card-type"><i class="fas fa-lightbulb"></i> ${idea.category}</span><span style="font-size:0.7rem;">${idea.date}</span></div><div class="card-title">${idea.title}</div><div class="card-desc">${idea.description}</div><div class="card-footer"><span><i class="fas fa-tools"></i> Anahitaji: ${idea.need}</span><span class="status-badge">${idea.status}</span></div></div>`).join(''); }
    function submitIdea() { const title = document.getElementById('ideaTitle').value, category = document.getElementById('ideaCategory').value, description = document.getElementById('ideaDesc').value, need = document.getElementById('ideaNeed').value; if(!title || !description) { alert('Tafadhali jaza sehemu zote muhimu'); return; } const newIdea = { id: nextIdeaId++, title, category, description, need, status: 'Pending Review', date: new Date().toLocaleDateString('en-GB') }; ideas.push(newIdea); saveIdeas(); renderIdeas(); document.getElementById('ideaTitle').value = ''; document.getElementById('ideaDesc').value = ''; document.getElementById('ideaNeed').value = ''; alert('Wazo lako limewasilishwa kwa mafanikio! Tutakujulisha baada ya ukaguzi.'); addNotification('Idea Submitted', `Wazo lako "${title}" limepokelewa. Tunaukagua.`); }
    function applyOpportunity(title) { alert(`✅ Umefanikiwa kuomba nafasi ya "${title}". Utapewa taarifa zaidi kwa email yako.`); addNotification('Application Submitted', `Umeomba nafasi ya "${title}". Subiri majibu.`); }
    function addNotification(title, message) { notifications.unshift({ id: notifications.length+1, title, message, read: false, date: new Date().toLocaleDateString('en-GB') }); updateNotificationBadge(); }
    function updateNotificationBadge() { document.getElementById('notificationCount').innerText = notifications.filter(n => !n.read).length; }
    function showNotifications() { const container = document.getElementById('notificationsList'); if(notifications.length === 0) { container.innerHTML = '<p>Hakuna taarifa mpya</p>'; } else { container.innerHTML = notifications.map(n => `<div class="notification-item ${!n.read ? 'unread' : ''}" onclick="markAsRead(${n.id})"><strong>${n.title}</strong><br><small>${n.message}</small><br><small style="color:#94a3b8;">${n.date}</small></div>`).join(''); } document.getElementById('notificationModal').style.display = 'flex'; }
    function markAsRead(id) { const notif = notifications.find(n => n.id === id); if(notif) notif.read = true; updateNotificationBadge(); showNotifications(); }
    function closeModal() { document.getElementById('notificationModal').style.display = 'none'; }
    document.querySelectorAll('.tab-btn').forEach(btn => btn.addEventListener('click', () => { document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); const tab = btn.dataset.tab; document.getElementById('opportunitiesTab').style.display = tab === 'opportunities' ? 'block' : 'none'; document.getElementById('ideasTab').style.display = tab === 'ideas' ? 'block' : 'none'; document.getElementById('advisoryTab').style.display = tab === 'advisory' ? 'block' : 'none'; if(tab === 'opportunities') renderOpportunities(document.getElementById('filterCategory').value); }));
    document.getElementById('filterCategory')?.addEventListener('change', (e) => renderOpportunities(e.target.value));
    document.getElementById('submitIdeaBtn')?.addEventListener('click', submitIdea);
    document.getElementById('notifyBtn')?.addEventListener('click', showNotifications);
    document.getElementById('userName').innerText = currentUserName;
    document.getElementById('studentId').innerText = currentUserReg;
    setCurrentDate();
    loadData();
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); sessionStorage.clear(); localStorage.clear(); window.location.href = 'login.html'; });
</script>
</body>
</html>