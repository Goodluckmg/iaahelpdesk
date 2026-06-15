<?php
header('Content-Type: application/json');
require_once 'config/database.php';
session_start();

// Check if user is logged in and is exam officer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lecturer', 'academic', 'exam', 'exam_officer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$appeal_id = isset($_POST['appeal_id']) ? intval($_POST['appeal_id']) : 0;
$response = isset($_POST['response']) ? mysqli_real_escape_string($conn, $_POST['response']) : '';
$new_grade = isset($_POST['new_grade']) ? mysqli_real_escape_string($conn, $_POST['new_grade']) : '';
$status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';

if (!$appeal_id || !$response) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Update the appeal
$query = "UPDATE exam_appeals SET 
            status = '$status',
            staff_response = '$response',
            new_grade = " . ($new_grade ? "'$new_grade'" : "NULL") . ",
            responded_by = " . $_SESSION['user_id'] . ",
            responded_at = NOW()
          WHERE id = $appeal_id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Appeal processed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>