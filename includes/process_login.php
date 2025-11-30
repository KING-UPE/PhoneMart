<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Invalid request method'];
    header('Location: ../login.php');
    exit();
}

$email = $conn->real_escape_string($_POST['email']);
$password = $_POST['password'];
$remember = isset($_POST['remember']) ? true : false;

// Validate input
if (empty($email) || empty($password)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Please fill all fields'];
    header('Location: ../login.php');
    exit();
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT UserID, Username, PasswordHash, IsAdmin, AvatarPath FROM user WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Invalid email or password'];
    header('Location: ../login.php');
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['PasswordHash'])) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Invalid email or password'];
    header('Location: ../login.php');
    exit();
}

// Ban check
$banCheck = $conn->prepare("SELECT BanReason, UnbanDate FROM ban WHERE UserID = ? AND IsActive = 1 AND UnbanDate > NOW()");
$banCheck->bind_param("i", $user['UserID']);
$banCheck->execute();
$banResult = $banCheck->get_result();

if ($banResult->num_rows > 0) {
    $ban = $banResult->fetch_assoc();
    header('Location: ../login.php?error=banned&reason=' . urlencode($ban['BanReason']) . '&until=' . urlencode($ban['UnbanDate']));
    exit();
}

// Set session variables
$_SESSION['user_id'] = $user['UserID'];
$_SESSION['username'] = $user['Username'];
$_SESSION['logged_in'] = true;
$_SESSION['is_admin'] = (bool)$user['IsAdmin'];
$_SESSION['avatar_path'] = $user['AvatarPath'];

// Remember me functionality
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 86400 * 30); // 30 days
    
    $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user['UserID'], $token, $expiry);
    $stmt->execute();
    
    setcookie('remember_token', $token, time() + 86400 * 30, '/', '', true, true);
}

// Redirect based on admin status
if ($_SESSION['is_admin']) {
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Welcome back, admin!'];
    header('Location: ../admin/dashboard.php');
} else {
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Logged in successfully!'];
    header('Location: ../index.php');
}
exit();
?>