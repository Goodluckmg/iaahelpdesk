<?php
header('Content-Type: application/json');
require_once 'config/database.php';
session_start();

// Check if user is logged in and is exam officer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lecturer', 'academic', 'exam', 'exam_officer'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'stats') {
    // Get statistics for dashboard
    $stats = [];
    
    $query = "SELECT COUNT(*) as count FROM exam_appeals WHERE status = 'Pending'";
    $result = mysqli_query($conn, $query);
    $stats['pending'] = mysqli_fetch_assoc($result)['count'];
    
    $query = "SELECT COUNT(*) as count FROM exam_appeals WHERE status = 'Under Review'";
    $result = mysqli_query($conn, $query);
    $stats['review'] = mysqli_fetch_assoc($result)['count'];
    
    $query = "SELECT COUNT(*) as count FROM exam_appeals WHERE status IN ('Resolved', 'Approved', 'Rejected')";
    $result = mysqli_query($conn, $query);
    $stats['resolved'] = mysqli_fetch_assoc($result)['count'];
    
    $query = "SELECT COUNT(*) as count FROM exam_appeals";
    $result = mysqli_query($conn, $query);
    $stats['total'] = mysqli_fetch_assoc($result)['count'];
    
    echo json_encode($stats);
    
} elseif ($action === 'list') {
    // Get all appeals (for dashboard)
    $filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'all';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    
    $where = "";
    if ($filter !== 'all') {
        $where .= " AND appeal_type = '$filter'";
    }
    if ($search !== '') {
        $where .= " AND (student_name LIKE '%$search%' OR reg_no LIKE '%$search%')";
    }
    
    $query = "SELECT * FROM exam_appeals WHERE 1=1 $where ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    
    $appeals = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appeals[] = $row;
    }
    
    echo json_encode($appeals);
    
} elseif ($action === 'pending') {
    // Get pending and under review appeals only (for lec_pending.php)
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    
    $where = "WHERE status IN ('Pending', 'Under Review')";
    if ($search !== '') {
        $where .= " AND (student_name LIKE '%$search%' OR reg_no LIKE '%$search%')";
    }
    
    $query = "SELECT * FROM exam_appeals $where ORDER BY 
              CASE WHEN status = 'Pending' THEN 1 ELSE 2 END, id DESC";
    $result = mysqli_query($conn, $query);
    
    $appeals = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appeals[] = $row;
    }
    
    echo json_encode($appeals);
    
} elseif ($action === 'resolved') {
    // Get resolved, approved, rejected appeals (for lec_resolved.php)
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    
    $where = "WHERE status IN ('Resolved', 'Approved', 'Rejected')";
    if ($search !== '') {
        $where .= " AND (student_name LIKE '%$search%' OR reg_no LIKE '%$search%')";
    }
    
    $query = "SELECT * FROM exam_appeals $where ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    
    $appeals = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appeals[] = $row;
    }
    
    echo json_encode($appeals);
    
} elseif ($action === 'details') {
    // Get single appeal details for modal
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $query = "SELECT * FROM exam_appeals WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $appeal = mysqli_fetch_assoc($result);
    
    echo json_encode($appeal);
}
?>