<?php
session_start();
require_once 'config/database.php';

// Function to verify password (supports MD5, plain text, and hashed)
function verifyUserPassword($input_password, $stored_password) {
    // Kwanza angalia kama ni hashed password (starts with $2y$)
    if (strpos($stored_password, '$2y$') === 0) {
        return password_verify($input_password, $stored_password);
    } 
    // Angalia kama ni MD5 (32 characters hexadecimal)
    else if (strlen($stored_password) == 32 && ctype_xdigit($stored_password)) {
        // Compare MD5 of input with stored MD5
        $input_md5 = md5($input_password);
        return $input_md5 === $stored_password;
    }
    else {
        // Ikiwa ni plain text password
        return $input_password === $stored_password;
    }
}

// Function to redirect user based on role
function redirectToDashboard($role, $user_id, $fullname, $username) {
    // Set common session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['fullname'] = $fullname;
    $_SESSION['role'] = $role;
    
    // Set role-specific session variables
    switch($role) {
        case 'student':
            $_SESSION['student_id'] = $user_id;
            $_SESSION['reg_no'] = $username;
            header("Location: student_index.php");
            break;
        case 'lecturer':
        case 'academic':
            $_SESSION['staff_id'] = $user_id;
            $_SESSION['staff_no'] = $username;
            header("Location: academic_dashboard.php");
            break;
        case 'ict':
            $_SESSION['staff_id'] = $user_id;
            $_SESSION['staff_no'] = $username;
            $_SESSION['staff_role'] = 'ict';
            header("Location: ict_dashboard.php");
            break;
        case 'finance':
            $_SESSION['staff_id'] = $user_id;
            $_SESSION['staff_no'] = $username;
            $_SESSION['staff_role'] = 'finance';
            header("Location: finance_dashboard.php");
            break;
        case 'admin':
        case 'super_admin':
            $_SESSION['admin_id'] = $user_id;
            $_SESSION['is_super_admin'] = ($role == 'super_admin');
            header("Location: admin_dashboard.php");
            break;
        default:
            session_destroy();
            $_SESSION['error'] = "Unknown user role. Please contact system administrator.";
            header("Location: login.php");
            break;
    }
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// Get and sanitize inputs
$username = mysqli_real_escape_string($conn, trim($_POST['username']));
$password = $_POST['password'];

// Validate inputs
if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Please enter both username/registration number and password.";
    header("Location: login.php");
    exit();
}

// ============================================
// STEP 1: Check in students table
// ============================================
$student_query = "SELECT id, reg_no, fullname, email, password, role, status FROM students WHERE (reg_no = '$username' OR email = '$username') AND status = 'active'";
$student_result = mysqli_query($conn, $student_query);

if (mysqli_num_rows($student_result) == 1) {
    $user = mysqli_fetch_assoc($student_result);
    
    // Debug - uncomment to see what's happening
    // echo "Stored password: " . $user['password'] . "<br>";
    // echo "MD5 of input: " . md5($password) . "<br>";
    
    if (verifyUserPassword($password, $user['password'])) {
        $role = !empty($user['role']) ? $user['role'] : 'student';
        redirectToDashboard($role, $user['id'], $user['fullname'], $user['reg_no']);
    } else {
        $_SESSION['error'] = "Invalid password. Please try again.";
        header("Location: login.php");
        exit();
    }
}

// ============================================
// STEP 2: Check in staff table
// ============================================
$staff_query = "SELECT id, staff_no, fullname, email, password, role, is_active as status FROM staff WHERE (staff_no = '$username' OR email = '$username') AND is_active = 1";
$staff_result = mysqli_query($conn, $staff_query);

if (mysqli_num_rows($staff_result) == 1) {
    $user = mysqli_fetch_assoc($staff_result);
    
    if (verifyUserPassword($password, $user['password'])) {
        $role = $user['role'];
        redirectToDashboard($role, $user['id'], $user['fullname'], $user['staff_no']);
    } else {
        $_SESSION['error'] = "Invalid password. Please try again.";
        header("Location: login.php");
        exit();
    }
}

// ============================================
// STEP 3: Check in admins table (if exists)
// ============================================
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
if (mysqli_num_rows($table_check) > 0) {
    $admin_query = "SELECT id, username, fullname, password, role, status FROM admins WHERE (username = '$username' OR email = '$username') AND status = 'active'";
    $admin_result = mysqli_query($conn, $admin_query);
    
    if (mysqli_num_rows($admin_result) == 1) {
        $user = mysqli_fetch_assoc($admin_result);
        
        if (verifyUserPassword($password, $user['password'])) {
            $role = $user['role'];
            redirectToDashboard($role, $user['id'], $user['fullname'], $user['username']);
        } else {
            $_SESSION['error'] = "Invalid password. Please try again.";
            header("Location: login.php");
            exit();
        }
    }
}

// ============================================
// If no user found
// ============================================
$_SESSION['error'] = "User not found. Please check your registration number, staff ID, or username.";
header("Location: login.php");
exit();
?>