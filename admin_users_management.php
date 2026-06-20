<?php
session_start();

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check admin role
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student_index.php");
    exit();
}

require_once 'config/database.php';

// ========== FIXED: Admin ID handling ==========
$logged_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$logged_user_id) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$is_super_admin = ($_SESSION['role'] === 'super_admin');

// Get profile photo - priority session first
$current_photo = null;
if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) {
    $current_photo = $_SESSION['profile_photo'];
} else {
    $photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
    $photo_result = mysqli_query($conn, $photo_query);
    if ($photo_result && mysqli_num_rows($photo_result) > 0) {
        $admin_data = mysqli_fetch_assoc($photo_result);
        $current_photo = $admin_data['profile_photo'] ?? null;
        $_SESSION['profile_photo'] = $current_photo;
    }
}

// ========== VALIDATE STUDENT REG NUMBER FORMAT ==========
function validateStudentRegNumber($reg_no) {
    return preg_match('/^[a-zA-Z]+\/\d{4}\/\d{4}$/', $reg_no) === 1;
}

// ========== VALIDATE STAFF REG NUMBER FORMAT ==========
function validateStaffRegNumber($reg_no) {
    return preg_match('/^[a-zA-Z]+\/\d{3,4}$/', $reg_no) === 1;
}

// ========== FORMAT REG NUMBER (UPPERCASE) ==========
function formatRegNumber($reg_no) {
    $parts = explode('/', $reg_no);
    $parts[0] = strtoupper($parts[0]);
    return implode('/', $parts);
}

// ========== DISPLAY ROLE NAME ==========
function getRoleDisplay($role) {
    $roles = [
        'student' => 'Student',
        'admin' => 'Admin',
        'super_admin' => 'Super Admin',
        'exam_officer' => 'Exam Officer',
        'ict' => 'ICT Staff',
        'finance' => 'Finance Staff',
    ];
    return $roles[$role] ?? ucfirst($role);
}

// ========== HANDLE ADD USER VIA AJAX ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $reg_no_raw = trim($_POST['reg_no']);
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $program = mysqli_real_escape_string($conn, trim($_POST['program']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = password_hash('password123', PASSWORD_DEFAULT);
    
    $errors = [];
    
    if ($role == 'student') {
        if (!validateStudentRegNumber($reg_no_raw)) {
            $errors[] = 'Invalid registration number format! Use: program/0001/2024 (e.g., odait/0001/2024)';
        }
    } else {
        if (!validateStaffRegNumber($reg_no_raw)) {
            $errors[] = 'Invalid registration number format! Use: dep/001 (e.g., admin/001, ict/001)';
        }
    }
    
    if (empty($errors)) {
        $reg_no = formatRegNumber($reg_no_raw);
    }
    
    if (!empty($reg_no)) {
        $check_reg = mysqli_query($conn, "SELECT id FROM students WHERE UPPER(reg_no) = UPPER('$reg_no')");
        if (mysqli_num_rows($check_reg) > 0) {
            $errors[] = 'Registration number already exists!';
        }
    }
    
    $check_email = mysqli_query($conn, "SELECT id FROM students WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = 'Email already registered!';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }
    
    $program_value = ($role == 'student') ? "'$program'" : "NULL";
    
    $insert = "INSERT INTO students (fullname, reg_no, email, phone, program, password, role, status) 
               VALUES ('$fullname', '$reg_no', '$email', '$phone', $program_value, '$password', '$role', 'active')";
    if (mysqli_query($conn, $insert)) {
        echo json_encode(['success' => true, 'message' => 'User added successfully! Reg No: ' . $reg_no . ' | Default password: password123']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
    exit();
}

// ========== HANDLE DELETE USER ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!$is_super_admin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized! Only Super Admin can delete users.']);
        exit();
    }
    
    $user_id = (int)$_POST['user_id'];
    $delete = "DELETE FROM students WHERE id = $user_id AND role != 'super_admin'";
    if (mysqli_query($conn, $delete)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}

// ========== HANDLE TOGGLE STATUS ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $user_id = (int)$_POST['user_id'];
    $current_status = mysqli_real_escape_string($conn, $_POST['current_status']);
    $new_status = ($current_status == 'active') ? 'inactive' : 'active';
    $update = "UPDATE students SET status = '$new_status' WHERE id = $user_id";
    if (mysqli_query($conn, $update)) {
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$users_query = "SELECT id, fullname, reg_no, email, phone, program, role, status, created_at 
                FROM students 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset";
$users_result = mysqli_query($conn, $users_query);

$total_query = "SELECT COUNT(*) as total FROM students";
$total_result = mysqli_query($conn, $total_query);
$total_users = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin | IAA Helpdesk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ============================================
           GLOBAL STYLES - RANGI ZAKO ZIMEBAKISHWA
           ============================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; }
        
        /* ===== SIDEBAR ===== */
        .sidebar { width: 280px; background: #0a1c2a; color: #e0edf5; display: flex; flex-direction: column; overflow-y: auto; }
        .profile-area { padding: 25px 20px; text-align: center; border-bottom: 1px solid #1a3a4f; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar i { font-size: 40px; color: white; }
        .welcome-text { font-size: 0.85rem; color: #94a3b8; }
        .user-name { font-size: 1.2rem; font-weight: 600; margin: 5px 0; }
        .user-role { font-size: 0.7rem; background: #2c7da0; display: inline-block; padding: 3px 12px; border-radius: 20px; }
        .user-id { font-size: 0.7rem; margin-top: 8px; color: #94a3b8; }
        .nav-menu { flex: 1; padding: 15px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; color: #cbdbe6; text-decoration: none; margin-bottom: 5px; }
        .nav-item:hover { background: #1a3a4f; color: white; }
        .nav-item.active { background: #2c7da0; color: white; }
        .logout-item { margin-top: auto; border-top: 1px solid #1a3a4f; padding-top: 15px; }
        
        /* ===== MAIN CONTENT ===== */
        .main-content { flex: 1; padding: 20px 25px; background: #f8fafc; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.6rem; color: #0a2b38; }
        .date-badge { background: white; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        
        /* ===== TABLE - FIX KWA EMAIL NDFU NA MAJINA YASIYOVUNJWA ===== */
        table {
            width: 100%;
            table-layout: fixed; /* Muhimu! */
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #e9eef3;
            font-size: 0.82rem;
            vertical-align: middle;
        }
        th {
            background: #f1f5f9;
            font-weight: 600;
            color: #0a1c2a;
        }
        
        /* Upana wa kila safu */
        table th:nth-child(1), table td:nth-child(1) { width: 5%; }   /* ID */
        table th:nth-child(2), table td:nth-child(2) { 
            width: 15%; 
            white-space: nowrap; /* MAJINA YASIYOVUNJWA - kama awali */
        }  /* Full Name */
        table th:nth-child(3), table td:nth-child(3) { width: 12%; }  /* Reg No */
        table th:nth-child(4), table td:nth-child(4) { width: 12%; }  /* Program */
        table th:nth-child(5), table td:nth-child(5) { 
            width: 18%; 
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 200px;
        }  /* EMAIL - inavunja maneno marefu */
        table th:nth-child(6), table td:nth-child(6) { width: 10%; }  /* Role */
        table th:nth-child(7), table td:nth-child(7) { width: 8%; }   /* Status */
        table th:nth-child(8), table td:nth-child(8) { width: 10%; }  /* Registered */
        table th:nth-child(9), table td:nth-child(9) { 
            width: 180px; 
            min-width: 160px;
            white-space: nowrap;
            text-align: center;
        }  /* Actions - upana wa kutosha */
        
        /* Buttons kwenye Actions - zisipandiane */
        table td:nth-child(9) .btn-primary,
        table td:nth-child(9) .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 12px;
            font-size: 0.7rem;
            border-radius: 20px;
            margin: 2px 4px;
            white-space: nowrap;
            min-width: 75px;
            cursor: default;
            border: none;
        }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        .status-active { background: #d9f0e5; color: #1d6f42; }
        .status-inactive { background: #fde8e8; color: #c0392b; }
        
        .btn-primary { background: #2c7da0; color: white; padding: 8px 18px; border-radius: 30px; text-decoration: none; font-size: 0.8rem; display: inline-block; border: none; }
        .btn-danger { background: #e74c3c; color: white; padding: 8px 18px; border-radius: 30px; text-decoration: none; font-size: 0.8rem; display: inline-block; border: none; }
        .btn-sm { padding: 4px 12px; font-size: 0.7rem; }
        
        .pagination { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .pagination a { padding: 8px 12px; background: #e2e8f0; border-radius: 8px; text-decoration: none; color: #0f172a; }
        .pagination a.active { background: #2c7da0; color: white; }
        
        .badge-program { background: #e8f0f5; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; display: inline-block; }
        .format-hint { color: #7f8c8d; font-size: 0.7rem; margin-top: 4px; }
        
        /* ===== MODAL STYLES ===== */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 550px; max-height: 85vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .close-modal:hover { color: #c0392b; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        .form-group label .required { color: #e74c3c; }
        input, select { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #cbdbe6; outline: none; }
        .program-field { display: block; }
        
        /* ===== TOAST ===== */
        .toast { position: fixed; bottom: 20px; right: 20px; background: #1d6f42; color: white; padding: 12px 20px; border-radius: 12px; z-index: 1001; display: none; }
        .toast.error { background: #c0392b; }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar span { display: none; } 
        }
        
        @media (max-width: 992px) {
            table th:nth-child(1), table td:nth-child(1) { width: 6%; }
            table th:nth-child(2), table td:nth-child(2) { width: 13%; white-space: nowrap; }
            table th:nth-child(3), table td:nth-child(3) { width: 10%; }
            table th:nth-child(4), table td:nth-child(4) { width: 10%; }
            table th:nth-child(5), table td:nth-child(5) { width: 16%; }
            table th:nth-child(6), table td:nth-child(6) { width: 8%; }
            table th:nth-child(7), table td:nth-child(7) { width: 8%; }
            table th:nth-child(8), table td:nth-child(8) { width: 10%; }
            table th:nth-child(9), table td:nth-child(9) { 
                width: 150px;
                min-width: 130px;
            }
            table td:nth-child(9) .btn-primary,
            table td:nth-child(9) .btn-danger {
                min-width: 60px;
                padding: 3px 8px;
                font-size: 0.65rem;
                margin: 1px 2px;
            }
        }
        
        @media (max-width: 768px) {
            table th:nth-child(2), table td:nth-child(2) { 
                white-space: normal; /* kwenye screen ndogo, majina yanaweza kuvunjika */
                width: 12%;
            }
            table th:nth-child(5), table td:nth-child(5) { 
                width: 14%;
                max-width: 120px;
            }
            table td:nth-child(9) .btn-primary,
            table td:nth-child(9) .btn-danger {
                min-width: 50px;
                padding: 2px 6px;
                font-size: 0.6rem;
                margin: 1px 1px;
            }
            table th:nth-child(9), table td:nth-child(9) {
                width: 120px;
                min-width: 100px;
            }
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
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></div>
            <div class="user-role"><?php echo $is_super_admin ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no'] ?? ''); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item active"><i class="fas fa-users"></i><span>User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span>Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">User Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📋 Registered Users</strong>
                <button class="btn-primary" id="showAddUserBtn"><i class="fas fa-plus"></i> Add New User</button>
            </div>
            
            <div style="overflow-x: auto;">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Reg No</th>
                            <th>Program</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                        <tr data-id="<?php echo $user['id']; ?>">
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['reg_no']); ?></td>
                            <td>
                                <?php if ($user['role'] == 'student' && !empty($user['program'])): ?>
                                    <span class="badge-program"><?php echo htmlspecialchars($user['program']); ?></span>
                                <?php elseif ($user['role'] == 'student'): ?>
                                    <span class="badge-program" style="background:#fef9e6;">Not set</span>
                                <?php else: ?>
                                    <span class="badge-program" style="background:#e3f2fd;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo getRoleDisplay($user['role']); ?></td>
                            <td><span class="status-badge <?php echo $user['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if (($is_super_admin || $_SESSION['role'] == 'admin') && $user['role'] != 'super_admin'): ?>
                                    <button class="btn-primary btn-sm toggle-status" data-id="<?php echo $user['id']; ?>" data-status="<?php echo $user['status']; ?>"><?php echo $user['status'] == 'active' ? 'Deactivate' : 'Activate'; ?></button>
                                <?php endif; ?>
                                
                                <?php if ($is_super_admin && $user['role'] != 'super_admin'): ?>
                                    <button class="btn-danger btn-sm delete-user" data-id="<?php echo $user['id']; ?>">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- ADD USER MODAL -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="addUserForm">
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="fullname" id="fullname" required placeholder="e.g., John Doe">
            </div>
            <div class="form-group">
                <label>Registration Number <span class="required">*</span></label>
                <input type="text" name="reg_no" id="reg_no" required placeholder="e.g., odait/0001/2024 or admin/001">
                <div class="format-hint">
                    <i class="fas fa-info-circle"></i> 
                    Student: <strong>program/0001/2024</strong> &nbsp;|&nbsp; 
                    Staff: <strong>dep/001</strong> (e.g., admin/001, ict/001)
                </div>
            </div>
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" id="email" required placeholder="student@iaa.ac.tz">
            </div>
            <div class="form-group">
                <label>Phone (Optional)</label>
                <input type="text" name="phone" id="phone" placeholder="e.g., 0712345678">
            </div>
            <div class="form-group">
                <label>Role <span class="required">*</span></label>
                <select name="role" id="role" required>
                    <option value="student">Student</option>
                    <?php if ($is_super_admin): ?>
                    <option value="exam_officer">Exam Officer</option>
                    <option value="ict">ICT Staff</option>
                    <option value="finance">Finance Staff</option>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group program-field" id="programField">
                <label>Program / Course <span class="required" id="programRequired">*</span></label>
                <select name="program" id="program">
                    <option value="">-- Select Program --</option>
                    <option value="BCS">Bachelor in Computer Science</option>
                    <option value="BA">Bachelor in Accountancy</option>
                    <option value="BBA">Bachelor in Business Administration</option>
                    <option value="BIT">Bachelor in Information Technology</option>
                    <option value="ODIT">Diploma in Computer Science</option>
                    <option value="ODA">Diploma in Accountancy</option>
                    <option value="BTCICT">Certificate in ICT</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Default Password</label>
                <input type="text" value="password123" disabled style="background:#f1f5f9;">
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
    // Modal elements
    const modal = document.getElementById('addUserModal');
    const showBtn = document.getElementById('showAddUserBtn');
    const closeBtn = document.querySelector('.close-modal');
    const cancelBtn = document.getElementById('cancelModalBtn');
    const form = document.getElementById('addUserForm');
    const toast = document.getElementById('toast');
    
    const roleSelect = document.getElementById('role');
    const programField = document.getElementById('programField');
    const programRequired = document.getElementById('programRequired');
    const programSelect = document.getElementById('program');

    function showToast(message, isError = false) {
        toast.textContent = message;
        toast.className = 'toast' + (isError ? ' error' : '');
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function toggleProgramField() {
        if (roleSelect.value === 'student') {
            programField.style.display = 'block';
            programRequired.style.display = 'inline';
            programSelect.setAttribute('required', 'required');
        } else {
            programField.style.display = 'none';
            programRequired.style.display = 'none';
            programSelect.removeAttribute('required');
            programSelect.value = '';
        }
    }

    if (showBtn) {
        showBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
            form.reset();
            toggleProgramField();
        });
    }

    function closeModal() {
        modal.style.display = 'none';
    }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    roleSelect.addEventListener('change', toggleProgramField);
    toggleProgramField();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (roleSelect.value === 'student' && !programSelect.value) {
            showToast('Please select a program for student', true);
            return;
        }
        
        const formData = new FormData(form);
        formData.append('action', 'add_user');
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Adding...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message);
                closeModal();
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast(result.message, true);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            showToast('Error adding user: ' + error.message, true);
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    });

    // Delete user
    document.querySelectorAll('.delete-user').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this user permanently? This action cannot be undone.')) return;
            
            const userId = btn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);
            
            btn.textContent = '...';
            btn.disabled = true;
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    showToast('User deleted successfully');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error deleting user: ' + result.message, true);
                    btn.textContent = 'Delete';
                    btn.disabled = false;
                }
            } catch (error) {
                showToast('Error deleting user', true);
                btn.textContent = 'Delete';
                btn.disabled = false;
            }
        });
    });

    // Toggle status
    document.querySelectorAll('.toggle-status').forEach(btn => {
        btn.addEventListener('click', async () => {
            const userId = btn.dataset.id;
            const currentStatus = btn.dataset.status;
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('user_id', userId);
            formData.append('current_status', currentStatus);
            
            btn.textContent = '...';
            btn.disabled = true;
            
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    showToast(`User ${result.new_status === 'active' ? 'activated' : 'deactivated'} successfully`);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error updating status', true);
                    btn.textContent = currentStatus === 'active' ? 'Deactivate' : 'Activate';
                    btn.disabled = false;
                }
            } catch (error) {
                showToast('Error updating status', true);
                btn.textContent = currentStatus === 'active' ? 'Deactivate' : 'Activate';
                btn.disabled = false;
            }
        });
    });
</script>
</body>
</html>