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

require_once 'config/database.php';

// ========== ENSURE student_id IS IN SESSION ==========
if (!isset($_SESSION['student_id'])) {
    $reg_no = $_SESSION['reg_no'];
    $find_id_query = "SELECT id FROM students WHERE reg_no = '$reg_no'";
    $find_id_result = mysqli_query($conn, $find_id_query);
    if ($find_id_result && mysqli_num_rows($find_id_result) > 0) {
        $student_id_data = mysqli_fetch_assoc($find_id_result);
        $_SESSION['student_id'] = $student_id_data['id'];
    } else {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
}

$student_id = $_SESSION['student_id'];
$fullname = $_SESSION['fullname'];
$reg_no = $_SESSION['reg_no'];

// ========== GET PROFILE PHOTO ==========
$photo_query = "SELECT profile_photo FROM students WHERE id = $student_id";
$photo_result = mysqli_query($conn, $photo_query);
$student_data = mysqli_fetch_assoc($photo_result);
$current_photo = $student_data['profile_photo'] ?? null;

// ========== FIXED: Get opportunities that are NOT expired ==========
// Only show opportunities where deadline is today or in the future
$opp_query = "SELECT * FROM startup_opportunities 
              WHERE status = 'active' 
              AND deadline >= CURDATE()
              ORDER BY deadline ASC, created_at DESC";
$opp_result = mysqli_query($conn, $opp_query);

// Get ideas
$ideas_query = "SELECT * FROM startup_ideas WHERE user_id = '$student_id' ORDER BY created_at DESC";
$ideas_result = mysqli_query($conn, $ideas_query);

// Handle idea submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_idea'])) {
    $title = mysqli_real_escape_string($conn, $_POST['idea_title']);
    $category = mysqli_real_escape_string($conn, $_POST['idea_category']);
    $description = mysqli_real_escape_string($conn, $_POST['idea_description']);
    $resources = mysqli_real_escape_string($conn, $_POST['idea_resources']);
    
    $insert_query = "INSERT INTO startup_ideas (user_id, title, category, description, resources_needed, status) 
                     VALUES ('$student_id', '$title', '$category', '$description', '$resources', 'pending')";
    if (mysqli_query($conn, $insert_query)) {
        $success_message = "Your idea has been submitted successfully!";
        $ideas_result = mysqli_query($conn, $ideas_query);
    } else {
        $error_message = "Error submitting idea.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Startup Hub | Fursa, Mawazo & Innovation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/statup.css">
    <style>
        /* ========== BASE STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; background: #f5f7fa; }
        
        /* ========== SIDEBAR - STATIC ========== */
        .sidebar { 
            width: 280px; 
            background: #0a2b38; /* RANGI MOJA - HAKUNA GRADIENT */
            color: #e0edf5; 
            display: flex; 
            flex-direction: column; 
            overflow-y: auto; 
            position: fixed; 
            height: 100vh; 
            left: 0; 
            top: 0; 
            z-index: 100;
        }
        .profile-area { 
            padding: 25px 20px; 
            text-align: center; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
            background: #2c7da0; /* RANGI MOJA - HAKUNA GRADIENT */
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
        
        .welcome-text { font-size: 0.85rem; color: #94a3b8; }
        .student-name { font-size: 1.1rem; font-weight: 600; margin: 5px 0; color: white; }
        .student-id { font-size: 0.7rem; margin-top: 8px; color: #94a3b8; }
        .student-id i { margin-right: 5px; }
        
        .nav-menu { flex: 1; padding: 15px; }
        .nav-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 12px 15px; 
            border-radius: 12px; 
            color: #cbdbe6; 
            text-decoration: none; 
            margin-bottom: 5px; 
            cursor: pointer; 
        }
        /* HAKUNA HOVER EFFECTS - zimeondolewa */
        .nav-item.active { 
            background: #2c7da0; 
            color: white; 
        }
        .nav-item.active i { color: white; }
        .nav-item i { width: 20px; color: #cbdbe6; }
        .nav-item.active i { color: white; }
        .nav-label { font-size: 0.9rem; }
        .logout-item { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        
        /* ========== MAIN CONTENT ========== */
        .main-content { flex: 1; padding: 20px 25px; overflow-y: auto; margin-left: 280px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 1.6rem; color: #0a2b38; }
        .date-badge { background: white; padding: 8px 18px; border-radius: 30px; font-size: 0.8rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        /* ========== WIDGET CARDS ========== */
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        
        /* ========== BUTTONS - COLOR #2c7da0 ========== */
        .btn-primary {
            background: #2c7da0;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            color: white;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover {
            background: #1f5a7a;
            color: white;
            text-decoration: none;
        }
        .btn-primary i { margin-right: 6px; }
        
        /* ========== STARTUP TAB STYLES ========== */
        .tab-content { 
            display: none; 
            min-height: 550px;
        }
        .tab-content.active { 
            display: block; 
        }
        
        .startup-tabs { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px; 
            border-bottom: 1px solid #e2edf2; 
            padding-bottom: 10px; 
            flex-wrap: wrap; 
        }
        .tab-btn { 
            background: none; 
            border: none; 
            padding: 8px 20px; 
            cursor: pointer; 
            font-weight: 500; 
            color: #7f8c8d; 
            border-radius: 20px; 
        }
        .tab-btn.active { 
            background: #2c7da0; 
            color: white; 
        }
        
        .opportunity-card, .idea-card { 
            background: white; 
            border-radius: 16px; 
            padding: 18px; 
            margin-bottom: 15px; 
            border: 1px solid #e2edf2; 
        }
        .card-type { 
            background: #e0f0f5; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: 600; 
            display: inline-block; 
            color: #2c7da0;
        }
        .card-type.job { background: #d9f0e5; color: #1d6f42; }
        .card-type.training { background: #fff3e0; color: #b45f06; }
        
        /* ========== APPLY BUTTON - COLOR #2c7da0 ========== */
        .apply-btn { 
            background: #2c7da0; 
            color: white; 
            border: none; 
            padding: 5px 12px; 
            border-radius: 20px; 
            cursor: pointer; 
            font-size: 0.7rem; 
            text-decoration: none;
            display: inline-block;
        }
        .apply-btn:hover {
            background: #1f5a7a;
        }
        
        /* ========== IDEA FORM ========== */
        .idea-form { background: white; border-radius: 20px; padding: 25px; margin-bottom: 25px; border: 1px solid #e2edf2; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; 
            padding: 10px; 
            border-radius: 12px; 
            border: 1px solid #cbd5e1; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2c7da0; }
        
        /* ========== SUBMIT BUTTON - COLOR #2c7da0 ========== */
        .submit-btn { 
            background: #2c7da0; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 30px; 
            cursor: pointer; 
            font-weight: 600; 
        }
        .submit-btn:hover { background: #1f5a7a; }
        
        /* ========== MESSAGE ========== */
        .message { padding: 10px 14px; border-radius: 12px; margin-bottom: 20px; display: none; align-items: center; gap: 10px; }
        .message.show { display: flex; }
        .message-success { background: #d9f0e5; color: #1d6f42; }
        .message-error { background: #fde8e8; color: #c0392b; }
        
        .status-badge { background: #fff3e0; padding: 2px 8px; border-radius: 20px; display: inline-block; font-size: 0.75rem; }
        .status-badge.pending { background: #fff3e0; color: #e67e22; }
        .status-badge.approved { background: #d9f0e5; color: #1d6f42; }
        
        .deadline-urgent { color: #c0392b; font-weight: bold; }
        .deadline-warning { color: #e67e22; }
        .deadline-normal { color: #27ae60; }
        
        /* ========== ADVISORY SECTION ========== */
        .advisory-card { 
            background: #0a2b38; 
            border-radius:20px; 
            padding:25px; 
            color:white; 
        }
        .advisory-contact-btn {
            background: #2c7da0;
            border: none;
            padding: 5px 12px;
            border-radius: 20px;
            color: white;
            cursor: pointer;
        }
        .advisory-contact-btn:hover {
            background: #1f5a7a;
        }
        
        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #e2edf2; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2c7da0; border-radius: 10px; }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-label { display: none; }
            .sidebar .welcome-text, .sidebar .student-name, .sidebar .student-id { display: none; }
            .main-content { margin-left: 70px; padding: 15px; }
            .tab-content { min-height: 650px; }
        }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
                <?php if ($current_photo): ?>
                    <img src="data:image/jpeg;base64,<?php echo htmlspecialchars($current_photo); ?>" alt="Profile Photo">
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
            <a href="student_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="student_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="student_startup.php" class="nav-item active"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="student_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Startup Hub | Fursa, Mawazo & Innovation</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <?php if(isset($success_message)): ?>
            <div class="message message-success show"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
            <div class="message message-error show"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="startup-tabs">
            <button class="tab-btn active" data-tab="opportunities"><i class="fas fa-briefcase"></i> Fursa & Nafasi</button>
            <button class="tab-btn" data-tab="ideas"><i class="fas fa-lightbulb"></i> Mawazo ya Biashara</button>
            <button class="tab-btn" data-tab="advisory"><i class="fas fa-chalkboard-user"></i> Ushauri & Innovation</button>
        </div>

        <!-- Tab 1: Opportunities -->
        <div id="opportunitiesTab" class="tab-content active">
            <div style="margin-bottom: 20px;">
                <select id="filterCategory" style="padding:8px; border-radius:20px; border:1px solid #cbdbe6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    <option value="all">Fursa Zote</option>
                    <option value="job">Kazi / Short Term Jobs</option>
                    <option value="training">Mafunzo / Training</option>
                    <option value="internship">Internship / Field Attachment</option>
                </select>
            </div>
            <div id="opportunitiesList">
                <?php if(mysqli_num_rows($opp_result) == 0): ?>
                    <div style="text-align:center; padding:40px; color:#7f8c8d;">⚠️ Hakuna fursa kwa sasa. Check back later!</div>
                <?php else: ?>
                    <?php while($opp = mysqli_fetch_assoc($opp_result)): 
                        $deadline_date = $opp['deadline'];
                        $days_left = ceil((strtotime($deadline_date) - time()) / (60 * 60 * 24));
                        
                        if ($days_left <= 3) {
                            $deadline_class = 'deadline-urgent';
                            $deadline_icon = 'fa-exclamation-triangle';
                        } elseif ($days_left <= 7) {
                            $deadline_class = 'deadline-warning';
                            $deadline_icon = 'fa-clock';
                        } else {
                            $deadline_class = 'deadline-normal';
                            $deadline_icon = 'fa-calendar-check';
                        }
                        
                        $apply_link = !empty($opp['application_link']) ? $opp['application_link'] : '#';
                    ?>
                        <div class="opportunity-card">
                            <div style="display:flex; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                                <span class="card-type <?php echo $opp['type']; ?>">
                                    <i class="fas <?php echo $opp['type'] == 'job' ? 'fa-briefcase' : ($opp['type'] == 'training' ? 'fa-graduation-cap' : 'fa-star'); ?>"></i> 
                                    <?php echo ucfirst($opp['type']); ?>
                                </span>
                                <span style="font-size:0.7rem;" class="<?php echo $deadline_class; ?>">
                                    <i class="fas <?php echo $deadline_icon; ?>"></i> 
                                    Deadline: <?php echo date('d/m/Y', strtotime($deadline_date)); ?>
                                    (<?php echo $days_left; ?> days left)
                                </span>
                            </div>
                            <div style="font-weight:700; margin-bottom:8px;"><?php echo htmlspecialchars($opp['title']); ?></div>
                            <div style="font-size:0.8rem; color:#7f8c8d; margin-bottom:12px;"><?php echo htmlspecialchars($opp['description']); ?></div>
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($opp['contact_info']); ?></span>
                                <?php if($apply_link != '#'): ?>
                                    <a href="<?php echo htmlspecialchars($apply_link); ?>" target="_blank" class="apply-btn">
                                        <i class="fas fa-external-link-alt"></i> Apply / Register
                                    </a>
                                <?php else: ?>
                                    <button class="apply-btn" onclick="alert('Application link not available yet. Please check back later.')">
                                        <i class="fas fa-paper-plane"></i> Apply / Register
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 2: Submit Business Ideas -->
        <div id="ideasTab" class="tab-content">
            <div class="idea-form">
                <h3 style="margin-bottom:15px;"><i class="fas fa-lightbulb" style="color: #2c7da0;"></i> Wasilisha Wazo Lako la Biashara</h3>
                <form method="POST" action="">
                    <div class="form-group"><label>Jina la Wazo</label><input type="text" name="idea_title" required></div>
                    <div class="form-group"><label>Kategoria ya Biashara</label>
                        <select name="idea_category">
                            <option>Teknolojia / Tech</option>
                            <option>Kilimo / Agriculture</option>
                            <option>Biashara / Trade</option>
                            <option>Huduma / Services</option>
                            <option>Ubunifu / Creative</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Maelezo ya Wazo</label><textarea name="idea_description" rows="4" required></textarea></div>
                    <div class="form-group"><label>Unachohitaji kuanza</label><input type="text" name="idea_resources" placeholder="Mfano: Capital, Training, Mentor, Equipment"></div>
                    <button type="submit" name="submit_idea" class="submit-btn"><i class="fas fa-paper-plane"></i> Wasilisha Wazo</button>
                </form>
            </div>
            <h3><i class="fas fa-list" style="color: #2c7da0;"></i> Mawazo Yaliyowasilishwa</h3>
            <div id="ideasList">
                <?php if(mysqli_num_rows($ideas_result) == 0): ?>
                    <div style="text-align:center; padding:40px; color:#7f8c8d;">Bado hakuna mawazo yaliyowasilishwa. Kuwa wa kwanza!</div>
                <?php else: ?>
                    <?php while($idea = mysqli_fetch_assoc($ideas_result)): ?>
                        <div class="idea-card">
                            <div style="display:flex; justify-content:space-between;">
                                <span class="card-type"><i class="fas fa-lightbulb"></i> <?php echo htmlspecialchars($idea['category']); ?></span>
                                <span style="font-size:0.7rem;"><?php echo date('d/m/Y', strtotime($idea['created_at'])); ?></span>
                            </div>
                            <div style="font-weight:700; margin:10px 0;"><?php echo htmlspecialchars($idea['title']); ?></div>
                            <div style="font-size:0.8rem; color:#7f8c8d;"><?php echo htmlspecialchars($idea['description']); ?></div>
                            <div style="margin-top:10px;">
                                <span><i class="fas fa-tools"></i> Anahitaji: <?php echo htmlspecialchars($idea['resources_needed']); ?></span>
                                <span class="status-badge <?php echo $idea['status']; ?>"><?php echo ucfirst($idea['status']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 3: Advisory & Innovation -->
        <div id="advisoryTab" class="tab-content">
            <div class="advisory-card">
                <h3><i class="fas fa-chalkboard-user"></i> Startup Office - Innovation Hub</h3>
                <p style="margin-top:10px;">Tunakusaidia kuboresha mawazo yako, kuunganisha na wataalam, na kupata fursa za ufadhili.</p>
                <div style="display:flex; align-items:center; gap:15px; background:rgba(255,255,255,0.1); padding:15px; border-radius:15px; margin-top:15px; flex-wrap:wrap;">
                    <div style="width:50px; height:50px; background:#2c7da0; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fas fa-user-tie"></i></div>
                    <div><h4>Dr. Sarah Mushi</h4><p>Head of Innovation & Startup Office</p><p><i class="fas fa-envelope"></i> sarah.mushi@iaa.ac.tz</p></div>
                    <button onclick="alert('Email sent to Startup Office')" class="advisory-contact-btn">Wasiliana</button>
                </div>
            </div>
            <div style="background:white; border-radius:20px; padding:20px; margin-top:20px;">
                <h3><i class="fas fa-graduation-cap" style="color: #2c7da0;"></i> Innovation Resources</h3>
                <ul style="margin-top:15px; list-style:none;">
                    <li style="padding:10px; border-bottom:1px solid #e2edf2;"><i class="fas fa-file-alt" style="color: #2c7da0;"></i> <strong>Business Plan Template</strong> - Download template ya mpango wa biashara</li>
                    <li style="padding:10px; border-bottom:1px solid #e2edf2;"><i class="fas fa-video" style="color: #2c7da0;"></i> <strong>Video Tutorials</strong> - Jinsi ya kuanzisha biashara</li>
                    <li style="padding:10px;"><i class="fas fa-handshake" style="color: #2c7da0;"></i> <strong>Mentorship Program</strong> - Jiunge na wajasiriamali waliobobea</li>
                </ul>
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

    // Tab switching - No movement
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('active')) return;
            
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const tab = this.dataset.tab;
            const mainContent = document.querySelector('.main-content');
            const scrollPosition = mainContent.scrollTop;
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            if (tab === 'opportunities') {
                document.getElementById('opportunitiesTab').classList.add('active');
            }
            if (tab === 'ideas') {
                document.getElementById('ideasTab').classList.add('active');
            }
            if (tab === 'advisory') {
                document.getElementById('advisoryTab').classList.add('active');
            }
            
            setTimeout(() => {
                mainContent.scrollTop = scrollPosition;
            }, 10);
        });
    });

    // Filter opportunities
    const filterSelect = document.getElementById('filterCategory');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filter = this.value;
            const cards = document.querySelectorAll('#opportunitiesTab .opportunity-card');
            cards.forEach(card => {
                const typeElement = card.querySelector('.card-type');
                if (typeElement) {
                    const type = typeElement.textContent.trim().toLowerCase();
                    if (filter === 'all' || type.includes(filter)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    }

    // Logout confirmation
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>