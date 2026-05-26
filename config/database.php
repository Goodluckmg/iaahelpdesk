<?php
// ============================================
// DATABASE CONNECTION
// ============================================

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'iaa_helpdesk';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>