<?php
require_once 'config.php';

// Delete remember token if exists
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    setcookie('remember_token', '', time() - 3600, '/');
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../login.php');
exit();
?>