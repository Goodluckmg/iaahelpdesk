<?php
session_start();

// 1. Angalia kama ameingia
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Angalia kama ana role ya admin (super_admin au admin)
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: student_dashboard.php");
    exit();
}

require_once 'config/database.php';

$logged_user_id = $_SESSION['student_id'];
$logged_role = $_SESSION['role'];
$is_super_admin = ($logged_role === 'super_admin');

// Get profile photo for sidebar
$photo_query = "SELECT profile_photo FROM students WHERE id = $logged_user_id";
$photo_result = mysqli_query($conn, $photo_query);
$admin_data = mysqli_fetch_assoc($photo_result);
$current_photo = $admin_data['profile_photo'] ?? null;

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
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal, .close-dept-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        .close-modal:hover, .close-dept-modal:hover { color: #c0392b; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        input, select { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #cbdbe6; outline: none; }
        input:focus, select:focus { border-color: #e74c3c; box-shadow: 0 0 0 2px rgba(231,76,60,0.1); }
        .btn-primary { background: #e74c3c; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; transition: 0.2s; }
        .btn-primary:hover { background: #c0392b; }
        .btn-danger { background: #c0392b; }
        .btn-danger:hover { background: #a93226; }
        .btn-sm { padding: 5px 12px; font-size: 0.75rem; }
        .message { padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; }
        .message-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-weight: 600; color: #0a2b38; }
        tr:hover { background: #f8fafc; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; }
        .page-title { font-size: 1.6rem; font-weight: 700; color: #0a2b38; }
        .date-badge { background: white; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; border: 1px solid #dee9f0; }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar">
    <?php if ($current_photo): ?>
        <img src="data:image/jpeg;base64,<?php echo $current_photo; ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
    <?php else: ?>
        <i class="fas fa-user-shield"></i>
    <?php endif; ?>
</div>
       
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
            <div class="user-role"><?php echo ($_SESSION['role'] == 'super_admin') ? '👑 Super Admin' : '⚙️ Admin'; ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['reg_no']); ?></div>
        </div>
        <div class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="admin_users_management.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-label">User Management</span></a>
            <a href="admin_tickets_view.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">All Tickets</span></a>
            <a href="admin_departments.php" class="nav-item active"><i class="fas fa-building"></i><span class="nav-label">Departments</span></a>
            <a href="admin_edit.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="admin_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span></a>
            <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">System Settings</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
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
                    <table id="deptsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Department Name</th>
                                <th>Head of Department</th>
                                <th>Email</th>
                                <th>Tickets</th>
                                <th>Actions</th>
                            </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo $dept['id']; ?>
                                <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong>
                                <td><?php echo htmlspecialchars($dept['head_of_department']); ?>
                                <td><a href="mailto:<?php echo htmlspecialchars($dept['email']); ?>"><?php echo htmlspecialchars($dept['email']); ?></a>
                                <td>
                                    <?php if($dept['ticket_count'] > 0): ?>
                                        <span class="status-badge" style="background:#e3f2fd; color:#1565c0;"><?php echo $dept['ticket_count']; ?> tickets</span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">0 tickets</span>
                                    <?php endif; ?>
                                
                                <td class="action-buttons">
                                    <a href="?edit=<?php echo $dept['id']; ?>" class="btn-primary btn-sm" style="text-decoration:none; display:inline-block;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $dept['id']; ?>" onclick="return confirm('Are you sure you want to delete this department? This action cannot be undone.')" class="btn-danger btn-sm" style="background:#c0392b; text-decoration:none; color:white; border-radius:5px; display:inline-block;">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
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
            <h3 id="deptModalTitle"><i class="fas fa-plus-circle"></i> Add Department</h3>
            <span class="close-dept-modal">&times;</span>
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
                <label>Department Email </label>
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
            <a href="admin_departments.php" class="close-modal" style="text-decoration:none;">&times;</a>
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
                <a href="admin_departments.php" class="btn-primary" style="background:#7f8c8d; text-decoration:none;">Cancel</a>
                <button type="submit" class="btn-primary">Update Department</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    // Add Department Modal
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
    
    // Close add modal when clicking outside
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>
</body>
</html>