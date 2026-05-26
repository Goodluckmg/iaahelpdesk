<?php
session_start();

// 1. Angalia kama ameingia
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Angalia kama ana role ya admin
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student_dashboard.php");
    exit();
}

require_once 'config/database.php';

$logged_user_id = $_SESSION['student_id'];
$logged_role = $_SESSION['role'];

// Determine if user is SUPER ADMIN (from database default) or REGULAR ADMIN
// SUPER ADMIN ni yule ambaye admin_type = 'super_admin' au role = 'super_admin'
$is_super_admin = ($logged_role === 'super_admin');

// Get current user data to check admin_type
$user_check = mysqli_query($conn, "SELECT admin_type FROM students WHERE id = $logged_user_id");
$user_data = mysqli_fetch_assoc($user_check);
$is_super_admin = $is_super_admin || ($user_data['admin_type'] === 'super_admin');

// Handle Delete (Super Admin only)
if (isset($_GET['delete']) && $is_super_admin) {
    $delete_id = intval($_GET['delete']);
    // Kamwe usifute super admin mwenyewe
    $check_query = "SELECT role, reg_no FROM students WHERE id = $delete_id";
    $check_res = mysqli_query($conn, $check_query);
    if ($check_res && mysqli_num_rows($check_res) > 0) {
        $row = mysqli_fetch_assoc($check_res);
        if ($row['role'] !== 'super_admin' && $row['reg_no'] !== 'ADMIN/001') {
            mysqli_query($conn, "DELETE FROM students WHERE id = $delete_id");
            $_SESSION['success'] = "User deleted successfully.";
        } else {
            $_SESSION['error'] = "Cannot delete a super admin user.";
        }
    }
    header("Location: admin_users_management.php");
    exit();
}

// Handle Add/Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $reg_no   = mysqli_real_escape_string($conn, $_POST['reg_no']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'] ?? '';

    // REGULAR ADMIN cannot assign admin roles
    $allow_admin_assignment = $is_super_admin;

    if (!$allow_admin_assignment && ($role === 'admin' || $role === 'super_admin')) {
        $_SESSION['error'] = "You do not have permission to assign admin roles.";
        header("Location: admin_users_management.php");
        exit();
    }

    if ($action === 'add') {
        // Check if reg_no or email already exists
        $check = mysqli_query($conn, "SELECT id FROM students WHERE reg_no = '$reg_no' OR email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['error'] = "Registration number or email already exists.";
        } else {
            $hashed_pass = md5($password ?: '12345');
            // If adding a regular admin (not super admin), set admin_type = 'regular'
            $admin_type = ($role === 'admin' && $is_super_admin) ? 'regular' : 'NULL';
            $insert = "INSERT INTO students (fullname, reg_no, email, phone, password, role, admin_type, status, created_by)
                       VALUES ('$fullname', '$reg_no', '$email', NULL, '$hashed_pass', '$role', " . ($admin_type !== 'NULL' ? "'$admin_type'" : "NULL") . ", 'active', $logged_user_id)";
            if (mysqli_query($conn, $insert)) {
                $_SESSION['success'] = "User added successfully.";
            } else {
                $_SESSION['error'] = "Database error: " . mysqli_error($conn);
            }
        }
    } elseif ($action === 'edit' && $user_id > 0) {
        $target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM students WHERE id = $user_id"));
        if (!$target) {
            $_SESSION['error'] = "User not found.";
        } else {
            // Cannot modify super admin if not super admin yourself
            if ($target['role'] === 'super_admin' && !$is_super_admin) {
                $_SESSION['error'] = "You cannot modify a super admin user.";
                header("Location: admin_users_management.php");
                exit();
            }
            // Only super admin can assign admin roles
            if (($role === 'admin' || $role === 'super_admin') && !$is_super_admin) {
                $_SESSION['error'] = "Only super admin can assign admin roles.";
                header("Location: admin_users_management.php");
                exit();
            }
            $admin_type_update = ($role === 'admin' && $is_super_admin) ? "admin_type = 'regular'" : "admin_type = NULL";
            $pass_update = "";
            if (!empty($password)) {
                $hashed = md5($password);
                $pass_update = ", password = '$hashed'";
            }
            $update = "UPDATE students SET 
                        fullname = '$fullname',
                        reg_no = '$reg_no',
                        email = '$email',
                        role = '$role',
                        $admin_type_update
                        $pass_update
                       WHERE id = $user_id";
            if (mysqli_query($conn, $update)) {
                $_SESSION['success'] = "User updated successfully.";
            } else {
                $_SESSION['error'] = "Update error: " . mysqli_error($conn);
            }
        }
    }
    header("Location: admin_users_management.php");
    exit();
}

// Fetch all users
$users_query = "SELECT id, fullname, reg_no, email, role, admin_type, status, created_at, created_by FROM students ORDER BY id ASC";
$users_result = mysqli_query($conn, $users_query);
$users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .role-super-admin { background: #f39c12; color: #fff; }
        .role-admin { background: #2c7da0; color: #fff; }
        .role-student { background: #27ae60; color: #fff; }
        .role-lecturer { background: #8e44ad; color: #fff; }
        .role-finance { background: #e67e22; color: #fff; }
        .role-ict { background: #3498db; color: #fff; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-icon { padding: 5px 10px; font-size: 0.7rem; }
        .disabled-btn { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .message { padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .message-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; border-radius: 12px; border: 1px solid #cbdbe6; }
        .btn-primary { background: #e74c3c; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; }
        .btn-primary:hover { background: #c0392b; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-shield"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
            <div class="user-role"><?php echo $is_super_admin ? '👑 Super Admin' : '⚙️ Regular Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no']); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item active"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">User Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message message-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message message-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="flex-between">
                <strong>👥 System Users</strong>
                <?php if ($is_super_admin): ?>
                    <button class="btn-primary" id="addUserBtn"><i class="fas fa-plus"></i> Add New User</button>
                <?php else: ?>
                    <button class="btn-primary" disabled style="background:#7f8c8d; cursor:not-allowed;"><i class="fas fa-plus"></i> Add User (Super Admin only)</button>
                <?php endif; ?>
            </div>
            <div style="overflow-x: auto;">
                <table id="usersTable">
                    <thead>
                        <tr><th>ID</th><th>Full Name</th><th>Reg No</th><th>Role</th><th>Admin Type</th><th>Email</th><th>Status</th><th>Actions</th>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $is_super_admin_user = ($user['role'] === 'super_admin');
                            $is_self = ($user['id'] == $_SESSION['student_id']);
                            $can_edit = ($is_super_admin || (!$is_super_admin_user && !$is_self));
                            $can_delete = ($is_super_admin && !$is_super_admin_user && !$is_self);
                        ?>
                        <tr>
                            <td><?php echo $user['id']; ?>侧
                            <td><?php echo htmlspecialchars($user['fullname']); ?><?php echo $is_super_admin_user ? ' 👑' : ''; ?>侧
                            <td><?php echo htmlspecialchars($user['reg_no']); ?>侧
                            <td>
                                <?php
                                if ($user['role'] === 'super_admin') echo '<span class="role-badge role-super-admin">Super Admin</span>';
                                elseif ($user['role'] === 'admin') echo '<span class="role-badge role-admin">Admin</span>';
                                elseif ($user['role'] === 'student') echo '<span class="role-badge role-student">Student</span>';
                                elseif ($user['role'] === 'lecturer') echo '<span class="role-badge role-lecturer">Lecturer</span>';
                                elseif ($user['role'] === 'finance') echo '<span class="role-badge role-finance">Finance</span>';
                                elseif ($user['role'] === 'ict') echo '<span class="role-badge role-ict">ICT</span>';
                                else echo ucfirst($user['role']);
                                ?>
                            侧
                            <td>
                                <?php 
                                if ($user['role'] === 'admin') {
                                    echo ($user['admin_type'] === 'regular') ? 'Regular Admin' : 'Admin';
                                } elseif ($user['role'] === 'super_admin') {
                                    echo '👑 Founder';
                                } else {
                                    echo '-';
                                }
                                ?>
                            侧
                            <td><?php echo htmlspecialchars($user['email']); ?>侧
                            <td><span class="status-badge status-resolved">Active</span>侧
                            <td class="action-buttons">
                                <?php if ($can_edit): ?>
                                    <button class="btn-primary btn-icon edit-user" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['fullname']); ?>" data-reg="<?php echo htmlspecialchars($user['reg_no']); ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-role="<?php echo $user['role']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                <?php else: ?>
                                    <button class="btn-primary btn-icon disabled-btn" disabled><i class="fas fa-edit"></i> Edit</button>
                                <?php endif; ?>
                                <?php if ($can_delete): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')" class="btn-danger btn-icon" style="background:#c0392b; padding:5px 10px; border-radius:5px; text-decoration:none; color:white;"><i class="fas fa-trash"></i> Delete</a>
                                <?php else: ?>
                                    <button class="btn-danger btn-icon disabled-btn" disabled><i class="fas fa-trash"></i> Delete</button>
                                <?php endif; ?>
                            侧
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ADD/EDIT USER MODAL -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">Add New User</h3><span class="close-modal">&times;</span></div>
        <form id="userForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="user_id" id="editUserId" value="0">
            <div class="form-group"><label>Full Name</label><input type="text" id="userFullName" name="fullname" required></div>
            <div class="form-group"><label>Registration Number</label><input type="text" id="userRegNo" name="reg_no" required></div>
            <div class="form-group"><label>Email</label><input type="email" id="userEmail" name="email" required></div>
            <div class="form-group"><label>Role</label>
                <select id="userRole" name="role">
                    <option value="student">Student</option>
                    <option value="lecturer">Lecturer</option>
                    <?php if ($is_super_admin): ?>
                        <option value="admin">Admin (Regular)</option>
                    <?php endif; ?>
                    <option value="finance">Finance</option>
                    <option value="ict">ICT Support</option>
                </select>
            </div>
            <div class="form-group"><label>Password</label><input type="password" id="userPassword" name="password" placeholder="Leave blank for default (12345)"></div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('userModal');
    const addBtn = document.getElementById('addUserBtn');
    const closeModal = document.querySelectorAll('.close-modal, #cancelModalBtn');
    
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            document.getElementById('modalTitle').innerText = 'Add New User';
            document.getElementById('formAction').value = 'add';
            document.getElementById('editUserId').value = '0';
            document.getElementById('userForm').reset();
            modal.style.display = 'flex';
        });
    }
    
    closeModal.forEach(btn => btn.addEventListener('click', () => modal.style.display = 'none'));
    
    // Edit user
    document.querySelectorAll('.edit-user').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const reg = btn.dataset.reg;
            const email = btn.dataset.email;
            const role = btn.dataset.role;
            document.getElementById('modalTitle').innerText = 'Edit User';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('editUserId').value = id;
            document.getElementById('userFullName').value = name;
            document.getElementById('userRegNo').value = reg;
            document.getElementById('userEmail').value = email;
            document.getElementById('userRole').value = role;
            document.getElementById('userPassword').value = '';
            modal.style.display = 'flex';
        });
    });
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === modal) modal.style.display = 'none';
    }
</script>
</body>
</html>