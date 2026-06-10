<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check if user has ICT role
if ($_SESSION['role'] !== 'ict') {
    header("Location: ../" . $_SESSION['role'] . "_dashboard.php");
    exit();
}

require_once 'config/database.php';

// Get ICT staff info
$staff_id = $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? null;
$staff_name = $_SESSION['fullname'] ?? 'ICT Support';
$staff_no = $_SESSION['staff_no'] ?? $_SESSION['reg_no'] ?? 'ICT/001';

// Get profile photo
$current_photo = null;
if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) {
    $current_photo = $_SESSION['profile_photo'];
} else {
    $photo_query = "SELECT profile_photo FROM students WHERE id = $staff_id";
    $photo_result = mysqli_query($conn, $photo_query);
    if ($photo_result && mysqli_num_rows($photo_result) > 0) {
        $staff_data = mysqli_fetch_assoc($photo_result);
        $current_photo = $staff_data['profile_photo'] ?? null;
    } else {
        $photo_query = "SELECT profile_photo FROM staff WHERE id = $staff_id";
        $photo_result = mysqli_query($conn, $photo_query);
        if ($photo_result && mysqli_num_rows($photo_result) > 0) {
            $staff_data = mysqli_fetch_assoc($photo_result);
            $current_photo = $staff_data['profile_photo'] ?? null;
        }
    }
    $_SESSION['profile_photo'] = $current_photo;
}

// Create system_status table if not exists with all columns
$create_table = "
CREATE TABLE IF NOT EXISTS system_status (
    id INT(11) NOT NULL AUTO_INCREMENT,
    system_name VARCHAR(100) NOT NULL,
    system_type VARCHAR(50) DEFAULT NULL,
    status ENUM('Online', 'Offline', 'Maintenance') DEFAULT 'Online',
    uptime DECIMAL(5,2) DEFAULT 99.9,
    last_check DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INT(11) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
mysqli_query($conn, $create_table);

// Check and add missing columns for existing tables
$check_uptime = mysqli_query($conn, "SHOW COLUMNS FROM system_status LIKE 'uptime'");
if (mysqli_num_rows($check_uptime) == 0) {
    mysqli_query($conn, "ALTER TABLE system_status ADD COLUMN uptime DECIMAL(5,2) DEFAULT 99.9 AFTER status");
}

$check_last_check = mysqli_query($conn, "SHOW COLUMNS FROM system_status LIKE 'last_check'");
if (mysqli_num_rows($check_last_check) == 0) {
    mysqli_query($conn, "ALTER TABLE system_status ADD COLUMN last_check DATETIME DEFAULT CURRENT_TIMESTAMP AFTER uptime");
}

$check_updated_by = mysqli_query($conn, "SHOW COLUMNS FROM system_status LIKE 'updated_by'");
if (mysqli_num_rows($check_updated_by) == 0) {
    mysqli_query($conn, "ALTER TABLE system_status ADD COLUMN updated_by INT(11) DEFAULT NULL AFTER last_check");
}

// Insert sample data if table is empty
$check_empty = mysqli_query($conn, "SELECT COUNT(*) as count FROM system_status");
$empty_result = mysqli_fetch_assoc($check_empty);
if ($empty_result['count'] == 0) {
    $insert_sample = "INSERT INTO system_status (system_name, system_type, status, uptime) VALUES
        ('Student Portal', 'Web Application', 'Online', 99.9),
        ('E-Learning Platform', 'Web Application', 'Online', 98.5),
        ('Library System', 'Web Application', 'Online', 99.8),
        ('Email Server', 'Mail Server', 'Online', 99.9),
        ('Finance System', 'Web Application', 'Maintenance', 95.0),
        ('Database Server', 'Database', 'Online', 99.99)";
    mysqli_query($conn, $insert_sample);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Add system
    if ($_POST['action'] === 'add_system') {
        $system_name = mysqli_real_escape_string($conn, $_POST['name']);
        $system_type = mysqli_real_escape_string($conn, $_POST['type']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $uptime = floatval($_POST['uptime']);
        
        $insert = "INSERT INTO system_status (system_name, system_type, status, uptime, updated_by, last_check) 
                   VALUES ('$system_name', '$system_type', '$status', '$uptime', '$staff_id', NOW())";
        
        if (mysqli_query($conn, $insert)) {
            echo json_encode(['success' => true, 'message' => 'System added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit();
    }
    
    // Update system
    if ($_POST['action'] === 'update_system') {
        $id = (int)$_POST['id'];
        $system_name = mysqli_real_escape_string($conn, $_POST['name']);
        $system_type = mysqli_real_escape_string($conn, $_POST['type']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $uptime = floatval($_POST['uptime']);
        
        $update = "UPDATE system_status SET 
                    system_name = '$system_name',
                    system_type = '$system_type',
                    status = '$status',
                    uptime = '$uptime',
                    updated_by = '$staff_id',
                    last_check = NOW()
                  WHERE id = $id";
        
        if (mysqli_query($conn, $update)) {
            echo json_encode(['success' => true, 'message' => 'System updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit();
    }
    
    // Delete system
    if ($_POST['action'] === 'delete_system') {
        $id = (int)$_POST['id'];
        $delete = "DELETE FROM system_status WHERE id = $id";
        
        if (mysqli_query($conn, $delete)) {
            echo json_encode(['success' => true, 'message' => 'System deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit();
    }
}

// Get systems
$systems_query = "SELECT * FROM system_status ORDER BY 
                  CASE WHEN status = 'Offline' THEN 1
                       WHEN status = 'Maintenance' THEN 2
                       ELSE 3 END,
                  system_name ASC";
$systems_result = mysqli_query($conn, $systems_query);
$systems = [];
while ($row = mysqli_fetch_assoc($systems_result)) {
    $systems[] = $row;
}

// Calculate statistics
$stats_query = "SELECT 
                    COUNT(CASE WHEN status = 'Online' THEN 1 END) as online,
                    COUNT(CASE WHEN status = 'Offline' THEN 1 END) as offline,
                    COUNT(CASE WHEN status = 'Maintenance' THEN 1 END) as maintenance
                FROM system_status";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Calculate average uptime
$avg_query = "SELECT AVG(uptime) as avg_uptime FROM system_status";
$avg_result = mysqli_query($conn, $avg_query);
$avg_uptime = round(mysqli_fetch_assoc($avg_result)['avg_uptime'] ?? 0, 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | System Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .app-container { display: flex; height: 100vh; }
        .sidebar { width: 280px; background: #0a1c2a; color: #e0edf5; display: flex; flex-direction: column; overflow-y: auto; }
        .profile-area { padding: 25px 20px; text-align: center; border-bottom: 1px solid #1a3a4f; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: linear-gradient(135deg, #2c7da0, #1f5068); }
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
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 20px; padding: 18px; border-left: 4px solid #2c7da0; text-align: center; }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: #2c7da0; margin-top: 5px; }
        .widget-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2edf2; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .system-item { background: #f8fafc; border-radius: 16px; padding: 18px; margin-bottom: 15px; border: 1px solid #e2edf2; transition: 0.2s; border-left: 4px solid #2c7da0; }
        .system-name { font-weight: 600; font-size: 1rem; margin-bottom: 8px; color: #0a2b38; }
        .system-meta { font-size: 0.75rem; color: #64748b; margin-bottom: 8px; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .status-Online { background: #d9f0e5; color: #1d6f42; }
        .status-Offline { background: #fde8e8; color: #c0392b; }
        .status-Maintenance { background: #fff3e0; color: #b45f06; }
        .btn-primary { background: #2c7da0; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; transition: 0.2s; }
        .btn-primary:hover { background: #1f5068; }
        .btn-danger { background: #c0392b; border: none; padding: 8px 18px; border-radius: 30px; color: white; cursor: pointer; text-decoration: none; font-size: 0.8rem; display: inline-block; }
        input, select { padding: 8px 12px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none; width: 100%; margin-top: 4px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; font-size: 0.85rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2edf2; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #7f8c8d; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar span { display: none; } }
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
                    <i class="fas fa-laptop-code"></i>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name"><?php echo htmlspecialchars($staff_name); ?></div>
            <div class="user-role">💻 ICT Support</div>
            <div class="user-id"><?php echo htmlspecialchars($staff_no); ?></div>
        </div>
        <div class="nav-menu">
            <a href="ict.php" class="nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="ict_tickets.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>Support Tickets</span></a>
            <a href="ict_systems.php" class="nav-item active"><i class="fas fa-server"></i><span>System Status</span></a>
             <a href="ict_maintenance.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="ict_photo.php" class="nav-item"><i class="fas fa-camera"></i><span>Edit Photo</span></a> 
            <a href="ict_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Reports</span></a>
            <div class="logout-item"><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">System Status Monitoring</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('D, M j, Y'); ?></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-check-circle" style="color:#27ae60;"></i><div class="stat-number"><?php echo $stats['online'] ?? 0; ?></div><div>Systems Online</div></div>
            <div class="stat-card"><i class="fas fa-times-circle" style="color:#c0392b;"></i><div class="stat-number"><?php echo $stats['offline'] ?? 0; ?></div><div>Systems Offline</div></div>
            <div class="stat-card"><i class="fas fa-tools" style="color:#e67e22;"></i><div class="stat-number"><?php echo $stats['maintenance'] ?? 0; ?></div><div>Maintenance</div></div>
            <div class="stat-card"><i class="fas fa-percent"></i><div class="stat-number"><?php echo $avg_uptime; ?>%</div><div>Avg Uptime</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>🖧 System Status Dashboard</strong>
                <button class="btn-primary" id="addSystemBtn"><i class="fas fa-plus"></i> Add System</button>
            </div>
            <div id="systemsList">
                <?php if (empty($systems)): ?>
                    <div style="text-align:center; padding:40px;">No systems registered. Click "Add System" to start.</div>
                <?php else: ?>
                    <?php foreach ($systems as $sys): 
                        $status_class = 'status-' . $sys['status'];
                        $border_color = $sys['status'] == 'Online' ? '#27ae60' : ($sys['status'] == 'Offline' ? '#c0392b' : '#e67e22');
                    ?>
                        <div class="system-item" style="border-left-color: <?php echo $border_color; ?>;" data-id="<?php echo $sys['id']; ?>">
                            <div class="flex-between">
                                <div class="system-name"><i class="fas fa-server"></i> <?php echo htmlspecialchars($sys['system_name']); ?></div>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $sys['status']; ?></span>
                            </div>
                            <div class="system-meta">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($sys['system_type'] ?? 'General'); ?> | 
                                <i class="fas fa-chart-line"></i> Uptime: <?php echo $sys['uptime']; ?>% | 
                                <i class="fas fa-calendar"></i> Last Check: <?php echo date('d/m/Y H:i', strtotime($sys['last_check'])); ?>
                            </div>
                            <div style="margin-top: 12px;">
                                <button class="btn-primary edit-system" data-id="<?php echo $sys['id']; ?>" style="padding:4px 12px;"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-danger delete-system" data-id="<?php echo $sys['id']; ?>" style="padding:4px 12px;"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- MODAL FOR ADD/EDIT SYSTEM -->
<div id="systemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New System</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="systemForm">
            <input type="hidden" id="editId" value="">
            <div class="form-group">
                <label>System Name *</label>
                <input type="text" id="systemName" required>
            </div>
            <div class="form-group">
                <label>System Type</label>
                <input type="text" id="systemType" placeholder="e.g., Web App, Database, Network">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select id="systemStatus">
                    <option value="Online">🟢 Online</option>
                    <option value="Offline">🔴 Offline</option>
                    <option value="Maintenance">🟡 Maintenance</option>
                </select>
            </div>
            <div class="form-group">
                <label>Uptime (%)</label>
                <input type="number" id="systemUptime" step="0.1" value="99.9" required>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button>
                <button type="submit" class="btn-primary">Save System</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentEditId = null;
    const modal = document.getElementById('systemModal');
    
    function showToast(message, isError = false) {
        alert(message);
    }
    
    async function saveSystem(data, isEdit = false) {
        const formData = new FormData();
        formData.append('action', isEdit ? 'update_system' : 'add_system');
        formData.append('name', data.name);
        formData.append('type', data.type);
        formData.append('status', data.status);
        formData.append('uptime', data.uptime);
        if (isEdit) formData.append('id', data.id);
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                window.location.reload();
            } else {
                showToast(result.message, true);
            }
        } catch (error) {
            showToast('Error saving system', true);
        }
    }
    
    async function deleteSystem(id) {
        if (!confirm('Are you sure you want to delete this system?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete_system');
        formData.append('id', id);
        
        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showToast(result.message);
                window.location.reload();
            } else {
                showToast(result.message, true);
            }
        } catch (error) {
            showToast('Error deleting system', true);
        }
    }
    
    function editSystem(id, name, type, status, uptime) {
        currentEditId = id;
        document.getElementById('modalTitle').innerText = 'Edit System';
        document.getElementById('editId').value = id;
        document.getElementById('systemName').value = name;
        document.getElementById('systemType').value = type;
        document.getElementById('systemStatus').value = status;
        document.getElementById('systemUptime').value = uptime;
        modal.style.display = 'flex';
    }
    
    // Modal handlers
    document.getElementById('addSystemBtn').addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('modalTitle').innerText = 'Add New System';
        document.getElementById('editId').value = '';
        document.getElementById('systemForm').reset();
        document.getElementById('systemUptime').value = '99.9';
        modal.style.display = 'flex';
    });
    
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => modal.style.display = 'none');
    });
    
    window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    
    document.getElementById('systemForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const data = {
            name: document.getElementById('systemName').value,
            type: document.getElementById('systemType').value,
            status: document.getElementById('systemStatus').value,
            uptime: document.getElementById('systemUptime').value
        };
        
        const editId = document.getElementById('editId').value;
        if (editId) {
            data.id = editId;
            saveSystem(data, true);
        } else {
            saveSystem(data, false);
        }
    });
    
    // Attach edit and delete events to buttons
    document.querySelectorAll('.edit-system').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.system-item');
            const id = btn.dataset.id;
            const nameElem = item.querySelector('.system-name');
            let name = nameElem.innerText;
            name = name.replace('🔧 ', '').replace('🖧 ', '').replace('📡 ', '').replace('🖥️ ', '').replace('<i class="fas fa-server"></i>', '').trim();
            const metaText = item.querySelector('.system-meta').innerText;
            let typeMatch = metaText.match(/Type: (.*?) \|/);
            let type = typeMatch ? typeMatch[1] : '';
            const statusElem = item.querySelector('.status-badge');
            const status = statusElem ? statusElem.innerText : 'Online';
            let uptimeMatch = metaText.match(/Uptime: ([\d.]+)%/);
            let uptime = uptimeMatch ? uptimeMatch[1] : '99.9';
            editSystem(id, name, type, status, uptime);
        });
    });
    
    document.querySelectorAll('.delete-system').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            deleteSystem(id);
        });
    });
</script>
</body>
</html>