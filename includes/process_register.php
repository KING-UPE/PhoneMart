<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Invalid request method'];
    header('Location: ../login.php?form=register');
    exit();
}

// Get form data
$username = $conn->real_escape_string($_POST['username']);
$email = $conn->real_escape_string($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$address = $conn->real_escape_string($_POST['address']);
$phone = $conn->real_escape_string($_POST['phone']);

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($address) || empty($phone)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Please fill all fields'];
    header('Location: ../login.php?form=register');
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Passwords do not match'];
    header('Location: ../login.php?form=register');
    exit();
}

if (strlen($password) < 8) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Password must be at least 8 characters'];
    header('Location: ../login.php?form=register');
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT UserID FROM user WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Email already registered'];
    header('Location: ../login.php?form=register');
    exit();
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO user (Username, Email, PasswordHash, PhoneNumber, Address, CreatedAt) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssss", $username, $email, $password_hash, $phone, $address);

if ($stmt->execute()) {
    // Get the new user ID
    $user_id = $stmt->insert_id;
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['logged_in'] = true;
    
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Registration successful!'];
    header('Location: ../index.php');
    exit();
} else {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Registration failed. Please try again.'];
    header('Location: ../login.php?form=register');
    exit();
}
?>