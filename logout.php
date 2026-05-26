<?php
// ============================================
// LOGOUT SCRIPT
// ============================================

session_start();

// Log the logout activity (if user was logged in)
if (isset($_SESSION['student_id']) && isset($_SESSION['logged_in'])) {
    // Optional: You can log logout activity to database
    // include 'config/database.php';
    // $student_id = $_SESSION['student_id'];
    // $ip_address = $_SERVER['REMOTE_ADDR'];
    // $user_agent = $_SERVER['HTTP_USER_AGENT'];
    // $log_query = "INSERT INTO system_logs (student_id, action, description, ip_address, user_agent) 
    //               VALUES ('$student_id', 'LOGOUT', 'User logged out', '$ip_address', '$user_agent')";
    // mysqli_query($conn, $log_query);
}

// Destroy all session data
session_unset();
session_destroy();

// Clear any remember me cookies if they exist
if (isset($_COOKIE['remember_reg_no'])) {
    setcookie('remember_reg_no', '', time() - 3600, "/");
}
if (isset($_COOKIE['remember_role'])) {
    setcookie('remember_role', '', time() - 3600, "/");
}

// Redirect to login page
header("Location: login.php");
exit();
?>