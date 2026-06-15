<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and has access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'exam_officer')) {
    header('Location: login.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Examinations Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .app-container {
            display: flex;
            height: 100vh;
            background: #f5f7fa;
        }

        /* ========== SIDEBAR STYLES ========== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0a2b38 0%, #0d3b4c 100%);
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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .avatar i {
            font-size: 40px;
            color: white;
        }

        .welcome-text {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .user-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 5px 0;
            color: white;
        }

        .user-role {
            font-size: 0.7rem;
            background: #667eea;
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
        }

        .user-id {
            font-size: 0.7rem;
            margin-top: 8px;
            color: #94a3b8;
        }

        .nav-menu {
            flex: 1;
            padding: 15px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 12px;
            color: #cbdbe6;
            text-decoration: none;
            margin-bottom: 5px;
            transition: 0.2s;
            cursor: pointer;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-item.active {
            background: #667eea;
            color: white;
        }

        .nav-item i {
            width: 20px;
        }

        .logout-item {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            padding: 20px 25px;
            overflow-y: auto;
            margin-left: 280px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 1.6rem;
            color: #0a2b38;
        }

        .date-badge {
            background: white;
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 0.8rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* ========== STATS CARDS ========== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card i {
            font-size: 35px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .stat-card div:last-child {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        /* ========== WIDGET CARD ========== */
        .widget-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-primary {
            background: #667eea;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            color: white;
            cursor: pointer;
            font-size: 0.8rem;
            transition: 0.2s;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        /* ========== TABLE STYLES ========== */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e2edf2;
            font-size: 0.85rem;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3e0;
            color: #f39c12;
        }

        .status-progress {
            background: #e3f2fd;
            color: #2196f3;
        }

        .status-resolved {
            background: #d9f0e5;
            color: #1d6f42;
        }

        /* ========== SEARCH & FILTER ========== */
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #f0f0f0;
            border: none;
            padding: 6px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: 0.2s;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
        }

        /* ========== MODAL STYLES ========== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 25px;
            width: 90%;
            max-width: 550px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2edf2;
        }

        .close-modal {
            cursor: pointer;
            font-size: 1.5rem;
            color: #7f8c8d;
        }

        .close-modal:hover {
            color: #c0392b;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid #cbdbe6;
            outline: none;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #667eea;
        }

        .evidence-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 10px;
            border: 1px dashed #667eea;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-graduation-cap"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($user['fullname'] ?? 'Dr. Sarah Examinations'); ?></div>
            <div class="user-role">📋 Examinations Officer</div>
            <div class="user-id"><?php echo htmlspecialchars($user['reg_no'] ?? 'STAFF/EXAM/001'); ?></div>
        </div>
        <div class="nav-menu">
            <a href="lecturers.php" class="nav-item active">
                <i class="fas fa-chart-pie"></i><span>Dashboard</span>
            </a>
            <a href="lec_pending.php" class="nav-item">
                <i class="fas fa-clock"></i><span>Student query</span>
            </a>
            <a href="lec_resolved.php" class="nav-item">
                <i class="fas fa-check-circle"></i><span>Resolved</span>
            </a>
            <a href="lec_timetable.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i><span>Exam Timetable</span>
            </a>
            <div class="logout-item">
                <a href="logout.php" class="nav-item" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Examinations Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row" id="statsContainer">
            <div class="stat-card">
                <i class="fas fa-gavel"></i>
                <div class="stat-number" id="pendingCount">0</div>
                <div>Pending Appeals</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner"></i>
                <div class="stat-number" id="reviewCount">0</div>
                <div>Under Review</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number" id="resolvedCount">0</div>
                <div>Resolved</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <div class="stat-number" id="totalCount">0</div>
                <div>Total Requests</div>
            </div>
        </div>

        <!-- SEARCH & FILTER -->
        <div class="widget-card">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search by student name, reg no...">
                <button class="btn-primary" id="searchBtn"><i class="fas fa-search"></i> Search</button>
            </div>
            <div class="filter-tabs" id="filterTabs">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="Grade Appeal">Grade Appeal</button>
                <button class="filter-btn" data-filter="Grade Mismatch">Grade Mismatch</button>
                <button class="filter-btn" data-filter="Exam Complaint">Exam Complaint</button>
                <button class="filter-btn" data-filter="Missing Result">Missing Result</button>
                <button class="filter-btn" data-filter="Other">Other</button>
            </div>
        </div>

        <!-- APPEALS TABLE -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Student Appeals & Complaints</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div style="overflow-x: auto;">
                <table id="appealsTable">
                    <thead>
                        <tr><th>ID</th><th>Student Name</th><th>Reg No</th><th>Course</th><th>Type</th><th>Current</th><th>Expected</th><th>Evidence</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody id="appealsBody">
                        <tr><td colspan="10" style="text-align:center;">Loading...</td><ei
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- MODAL FOR PROCESSING APPEAL -->
<div id="respondModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-gavel"></i> Process Student Appeal</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="respondForm">
            <input type="hidden" id="appealId">
            <div class="form-group">
                <label>Student Name:</label>
                <input type="text" id="studentNameDisplay" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Course:</label>
                <input type="text" id="courseDisplay" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Current Grade in System:</label>
                <input type="text" id="currentGradeDisplay" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Student's Claimed Grade:</label>
                <input type="text" id="claimedGradeDisplay" readonly style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Student's Reason:</label>
                <textarea id="studentReasonDisplay" rows="2" readonly style="background:#f5f5f5;"></textarea>
            </div>
            <div class="form-group" id="evidenceDiv" style="display:none;">
                <label>Evidence/Screenshot:</label>
                <div id="evidenceDisplay" class="evidence-box"></div>
            </div>
            <div class="form-group">
                <label>Your Response / Decision</label>
                <textarea id="responseMsg" rows="3" placeholder="Write your decision and response..." required></textarea>
            </div>
            <div class="form-group">
                <label>New Grade (if approved)</label>
                <select id="newGrade">
                    <option value="">-- No change --</option>
                    <option value="A">A (80-100)</option>
                    <option value="B+">B+ (70-74)</option>
                    <option value="B">B (65-69)</option>
                    <option value="C+">C+ (55-59)</option>
                    <option value="C">C (50-54)</option>
                    <option value="D">D (45-49)</option>
                    <option value="F">F (0-44)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Update Status</label>
                <select id="responseStatus">
                    <option value="Under Review">Under Review</option>
                    <option value="Approved">✅ Approved - Grade Changed</option>
                    <option value="Rejected">❌ Rejected - No Change</option>
                    <option value="Resolved">Resolved - Student Notified</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Submit Decision</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentFilter = "all";
    let currentSearch = "";

    function loadStats() {
        fetch('get_appeals.php?action=stats')
            .then(response => response.json())
            .then(data => {
                document.getElementById('pendingCount').innerText = data.pending || 0;
                document.getElementById('reviewCount').innerText = data.review || 0;
                document.getElementById('resolvedCount').innerText = data.resolved || 0;
                document.getElementById('totalCount').innerText = data.total || 0;
            })
            .catch(error => console.error('Error loading stats:', error));
    }

    function loadAppeals() {
        let url = 'get_appeals.php?action=list&filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(currentSearch);
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('appealsBody');
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;">No appeals found</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.map(a => `
                    <tr>
                        <td>#${a.id}</td>
                        <td>${escapeHtml(a.student_name)}</td>
                        <td>${escapeHtml(a.reg_no)}</td>
                        <td>${escapeHtml(a.course.substring(0, 30))}${a.course.length > 30 ? '...' : ''}</td>
                        <td><span class="status-badge status-pending">${escapeHtml(a.appeal_type)}</span></td>
                        <td><strong>${escapeHtml(a.current_grade || 'N/A')}</strong></td>
                        <td>${escapeHtml(a.expected_grade || 'N/A')}</td>
                        <td>${a.evidence_file ? '<i class="fas fa-paperclip" style="color:#27ae60;"></i> Yes' : '<span style="color:#999;">No</span>'}</td>
                        <td><span class="status-badge ${getStatusClass(a.status)}">${escapeHtml(a.status)}</span></td>
                        <td>${a.status !== 'Resolved' && a.status !== 'Approved' && a.status !== 'Rejected' ? 
                            `<button class="btn-primary respond-btn" data-id="${a.id}" style="padding:4px 12px;"><i class="fas fa-gavel"></i> Process</button>` : 
                            '<span style="color:#27ae60;">✓ Done</span>'}</td>
                    </tr>
                `).join('');
                
                document.querySelectorAll('.respond-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.id;
                        loadAppealDetails(id);
                    });
                });
            })
            .catch(error => console.error('Error loading appeals:', error));
    }

    function loadAppealDetails(id) {
        fetch('get_appeals.php?action=details&id=' + id)
            .then(response => response.json())
            .then(a => {
                document.getElementById('appealId').value = a.id;
                document.getElementById('studentNameDisplay').value = a.student_name;
                document.getElementById('courseDisplay').value = a.course;
                document.getElementById('currentGradeDisplay').value = a.current_grade || 'N/A';
                document.getElementById('claimedGradeDisplay').value = a.expected_grade || 'N/A';
                document.getElementById('studentReasonDisplay').value = a.reason;
                document.getElementById('evidenceDiv').style.display = a.evidence_file ? 'block' : 'none';
                if (a.evidence_file) {
                    document.getElementById('evidenceDisplay').innerHTML = `<i class="fas fa-image"></i> ${a.evidence_file}`;
                }
                document.getElementById('responseMsg').value = '';
                document.getElementById('newGrade').value = '';
                document.getElementById('responseStatus').value = 'Under Review';
                document.getElementById('respondModal').style.display = 'flex';
            })
            .catch(error => console.error('Error loading appeal details:', error));
    }

    function getStatusClass(status) {
        if (status === "Pending") return "status-pending";
        if (status === "Under Review") return "status-progress";
        return "status-resolved";
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        document.getElementById('currentDate').innerText = new Date().toLocaleDateString('en-US', options);
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            loadAppeals();
        });
    });

    document.getElementById('searchBtn').addEventListener('click', () => {
        currentSearch = document.getElementById('searchInput').value;
        loadAppeals();
    });

    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            currentSearch = e.target.value;
            loadAppeals();
        }
    });

    document.getElementById('refreshBtn').addEventListener('click', () => {
        loadStats();
        loadAppeals();
    });

    document.getElementById('logoutBtn').addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    });

    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => {
            document.getElementById('respondModal').style.display = 'none';
        });
    });

    document.getElementById('respondForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const response = document.getElementById('responseMsg').value;
        if (!response) {
            alert('Please write a response');
            return;
        }
        
        const formData = new FormData();
        formData.append('appeal_id', document.getElementById('appealId').value);
        formData.append('response', response);
        formData.append('new_grade', document.getElementById('newGrade').value);
        formData.append('status', document.getElementById('responseStatus').value);
        
        fetch('process_appeal.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                document.getElementById('respondModal').style.display = 'none';
                loadStats();
                loadAppeals();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });

    loadStats();
    loadAppeals();
    setCurrentDate();
</script>
</body>
</html>