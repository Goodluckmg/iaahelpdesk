<?php
session_start();

// 1. Angalia kama ameingia
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Angalia kama ana role ya admin (super_admin au admin)
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student/student_index.php");
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

$logged_role = $_SESSION['role'];
$is_super_admin = ($logged_role === 'super_admin');

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
        $_SESSION['profile_photo'] = $current_photo; // Store in session
    }
}

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $head = mysqli_real_escape_string($conn, trim($_POST['head']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    // Validate input
    if (empty($name) || empty($head) || empty($email)) {
        $_SESSION['error'] = "All fields are required!";
    } else {
        // Check if department already exists
        $check = mysqli_query($conn, "SELECT id FROM departments WHERE name = '$name'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['error'] = "Department already exists!";
        } else {
            $insert = "INSERT INTO departments (name, head_of_department, email, status) VALUES ('$name', '$head', '$email', 'active')";
            if (mysqli_query($conn, $insert)) {
                $_SESSION['success'] = "Department added successfully!";
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        }
    }
    header("Location: admin_departments.php");
    exit();
}

// Handle Edit Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $head = mysqli_real_escape_string($conn, trim($_POST['head']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    if (empty($name) || empty($head) || empty($email)) {
        $_SESSION['error'] = "All fields are required!";
    } else {
        $update = "UPDATE departments SET name = '$name', head_of_department = '$head', email = '$email' WHERE id = $id";
        if (mysqli_query($conn, $update)) {
            $_SESSION['success'] = "Department updated successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    header("Location: admin_departments.php");
    exit();
}

// Handle Delete Department
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Check if department has tickets
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM tickets WHERE department_id = $id");
    $row = mysqli_fetch_assoc($check);
    if ($row['count'] > 0) {
        $_SESSION['error'] = "Cannot delete department that has tickets assigned!";
    } else {
        $delete = "DELETE FROM departments WHERE id = $id";
        if (mysqli_query($conn, $delete)) {
            $_SESSION['success'] = "Department deleted successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    header("Location: admin_departments.php");
    exit();
}

// Fetch all departments
$dept_query = "SELECT d.*, COUNT(t.id) as ticket_count 
               FROM departments d 
               LEFT JOIN tickets t ON d.id = t.department_id 
               GROUP BY d.id 
               ORDER BY d.id ASC";
$dept_result = mysqli_query($conn, $dept_query);
$departments = [];
while ($row = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $row;
}

// Fetch data for a single department (for edit modal)
$edit_dept = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM departments WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_dept = mysqli_fetch_assoc($edit_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Departments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; }
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
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 15px; border-radius: 10px; color: #cbdbe6; text-decoration: none; margin-bottom: 5px; transition: 0.2s; }
        .nav-item:hover { background: #1a3a4f; color: white; }
        .nav-item.active { background: #2c7da0; color: white; }
        .logout-item { margin-top: auto; border-top: 1px solid #1a3a4f; padding-top: 15px; }
        .main-content { flex: 1; padding: 20px 25px; background: #f8fafc; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.6rem; color: #0a2b38; }
        .date-badge { background: white; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal, .close-dept-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        input, select { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #cbdbe6; outline: none; }
        .btn-primary { background: #2c7da0; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #1a5a74; }
        .btn-danger { background: #c0392b; }
        .btn-danger:hover { background: #a93226; }
        .btn-sm { padding: 5px 12px; font-size: 0.75rem; }
        .message { padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; }
        .message-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-weight: 600; color: #0a2b38; }
        .status-badge { background: #e3f2fd; color: #1565c0; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
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
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span>User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item active"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span>Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Departments</h1>
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
                <strong>🏢 Departments</strong>
                <button class="btn-primary" id="addDeptBtn"><i class="fas fa-plus"></i> Add Department</button>
            </div>
            
            <?php if (empty($departments)): ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <i class="fas fa-building" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                    No departments found. Click "Add Department" to create one.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Department Name</th><th>Head of Department</th><th>Email</th><th>Tickets</th><th>Actions</th></thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo $dept['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($dept['head_of_department']); ?></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($dept['email']); ?>"><?php echo htmlspecialchars($dept['email']); ?></a></td>
                                <td>
                                    <?php if($dept['ticket_count'] > 0): ?>
                                        <span class="status-badge"><?php echo $dept['ticket_count']; ?> tickets</span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">0 tickets</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="?edit=<?php echo $dept['id']; ?>" class="btn-primary btn-sm">Edit</a>
                                    <a href="?delete=<?php echo $dept['id']; ?>" onclick="return confirm('Are you sure?')" class="btn-primary btn-sm" style="background:#c0392b;">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- ADD DEPARTMENT MODAL -->
<div id="deptModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Department</h3>
            <span class="close-dept-modal" style="cursor:pointer;">&times;</span>
        </div>
        <form id="deptForm" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Department Name *</label>
                <input type="text" name="name" id="deptName" placeholder="e.g., ICT Support, Finance Office" required>
            </div>
            <div class="form-group">
                <label>Head of Department *</label>
                <input type="text" name="head" id="deptHead" placeholder="e.g., Dr. John Mkono" required>
            </div>
            <div class="form-group">
                <label>Department Email *</label>
                <input type="email" name="email" id="deptEmail" placeholder="e.g., dept@iaa.ac.tz" required>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelDeptModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Save Department</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT DEPARTMENT MODAL -->
<?php if ($edit_dept): ?>
<div id="editDeptModal" class="modal" style="display: flex;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Department</h3>
            <a href="admin_departments.php" style="text-decoration:none; font-size:1.5rem;">&times;</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $edit_dept['id']; ?>">
            <div class="form-group">
                <label>Department Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_dept['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Head of Department *</label>
                <input type="text" name="head" value="<?php echo htmlspecialchars($edit_dept['head_of_department']); ?>" required>
            </div>
            <div class="form-group">
                <label>Department Email *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($edit_dept['email']); ?>" required>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <a href="admin_departments.php" class="btn-primary" style="background:#7f8c8d;">Cancel</a>
                <button type="submit" class="btn-primary">Update Department</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    const addBtn = document.getElementById('addDeptBtn');
    const modal = document.getElementById('deptModal');
    const closeModal = document.querySelectorAll('.close-dept-modal, #cancelDeptModalBtn');
    
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            document.getElementById('deptForm').reset();
            modal.style.display = 'flex';
        });
    }
    
    closeModal.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });
    
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>
</body>
</html>