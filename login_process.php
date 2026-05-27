<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_no = mysqli_real_escape_string($conn, trim($_POST['reg_no']));
    $password = trim($_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if (empty($reg_no) || empty($password) || empty($role)) {
        $_SESSION['error'] = "Please fill all fields";
        header("Location: login.php");
        exit();
    }
    
    $query = "SELECT * FROM students WHERE reg_no = '$reg_no' AND status = 'active'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $student = mysqli_fetch_assoc($result);
        
        if ($student['password'] === md5($password)) {
            if ($student['role'] !== $role) {
                $_SESSION['error'] = "Invalid role selected for this account";
                header("Location: login.php");
                exit();
            }
            
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['fullname'] = $student['fullname'];
            $_SESSION['reg_no'] = $student['reg_no'];
            $_SESSION['email'] = $student['email'];
            $_SESSION['role'] = $student['role'];
            $_SESSION['logged_in'] = true;
            
// Badilisha kulingana na muundo wako
if ($student['role'] == 'student') {
    header("Location: student_index.php");
    exit();
} elseif ($student['role'] == 'super_admin' || $student['role'] == 'admin') {
    header("Location: admin_dashboard.php");
    exit();
} elseif ($student['role'] == 'finance') {
    header("Location: finance.php");  // Au finance.php ikiwa iko kwenye root
    exit();
} elseif ($student['role'] == 'ict') {
    header("Location: ict_dashboard.php");
    exit();
} elseif ($student['role'] == 'lecturer') {
    header("Location: lecturer_dashboard.php");
    exit();
} else {
    $_SESSION['error'] = "Role not supported yet";
    header("Location: login.php");
    exit();
}
        } else {
            $_SESSION['error'] = "Invalid password";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Registration number not found or inactive";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>