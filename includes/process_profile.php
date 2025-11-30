<?php
require_once 'config.php';
require_once 'auth.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php?error=invalid_request');
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_profile':
        handleProfileUpdate($conn, $user_id);
        break;
    case 'change_password':
        handlePasswordChange($conn, $user_id);
        break;
    case 'update_avatar':
        handleAvatarUpload($conn, $user_id);
        break;
    default:
        header('Location: ../profile.php?error=invalid_action');
        exit();
}


function handleProfileUpdate($conn, $user_id) {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $address = $conn->real_escape_string($_POST['address'] ?? '');

    // Basic validation
    if (empty($username) || empty($email)) {
        header('Location: ../profile.php?error=empty_fields');
        exit();
    }

    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT UserID FROM user WHERE Email = ? AND UserID != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header('Location: ../profile.php?error=email_taken');
        exit();
    }

    // Update profile
    $stmt = $conn->prepare("UPDATE user SET Username = ?, Email = ?, PhoneNumber = ?, Address = ? WHERE UserID = ?");
    $stmt->bind_param("ssssi", $username, $email, $phone, $address, $user_id);

    if ($stmt->execute()) {
        // Update session username if changed
        $_SESSION['username'] = $username;
        header('Location: ../profile.php?success=profile_updated');
    } else {
        header('Location: ../profile.php?error=update_failed');
    }
    exit();
}

function handlePasswordChange($conn, $user_id) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($current_password)) {
        header('Location: ../profile.php?error=current_password_empty');
        exit();
    }

    if ($new_password !== $confirm_password) {
        header('Location: ../profile.php?error=password_mismatch');
        exit();
    }

    if (strlen($new_password) < 8) {
        header('Location: ../profile.php?error=password_too_short');
        exit();
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT PasswordHash FROM user WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!password_verify($current_password, $user['PasswordHash'])) {
        header('Location: ../profile.php?error=invalid_current_password');
        exit();
    }

    // Update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user SET PasswordHash = ? WHERE UserID = ?");
    $stmt->bind_param("si", $new_password_hash, $user_id);

    if ($stmt->execute()) {
        header('Location: ../profile.php?success=password_changed');
    } else {
        header('Location: ../profile.php?error=password_change_failed');
    }
    exit();
}

function handleAvatarUpload($conn, $user_id) {
    // Check if file was uploaded properly
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        header('Location: ../profile.php?error=upload_error');
        exit();
    }

    $file = $_FILES['avatar'];

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        header('Location: ../profile.php?error=invalid_file_type');
        exit();
    }

    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        header('Location: ../profile.php?error=file_too_large');
        exit();
    }

    // Create uploads directory if not exists
    $upload_dir = '../uploads/users';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Get file extension based on MIME type
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    $extension = $extensions[$mime_type] ?? pathinfo($file['name'], PATHINFO_EXTENSION);

    // Generate unique filename
    $filename = "avatar_{$user_id}_" . time() . ".{$extension}";
    $destination = "{$upload_dir}/{$filename}";

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Delete old avatar if exists
        $stmt = $conn->prepare("SELECT AvatarPath FROM user WHERE UserID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!empty($user['AvatarPath']) && file_exists("{$upload_dir}/{$user['AvatarPath']}")) {
            unlink("{$upload_dir}/{$user['AvatarPath']}");
        }

        // Update database
        $stmt = $conn->prepare("UPDATE user SET AvatarPath = ? WHERE UserID = ?");
        $stmt->bind_param("si", $filename, $user_id);

        if ($stmt->execute()) {
            // Update session
            $_SESSION['avatar_path'] = $filename;
            header('Location: ../profile.php?success=avatar_updated');
        } else {
            // Delete the uploaded file if DB update failed
            unlink($destination);
            header('Location: ../profile.php?error=avatar_update_failed');
        }
    } else {
        header('Location: ../profile.php?error=upload_failed');
    }
    exit();
}
?>