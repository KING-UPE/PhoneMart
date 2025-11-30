<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getSetting($key, $default = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT SettingValue FROM setting WHERE SettingKey = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && ($row = $result->fetch_assoc())) {
            return $row['SettingValue'];
        }
        return $default;
    } catch (Exception $e) {
        error_log("Error getting setting $key: " . $e->getMessage());
        return $default;
    }
}

function checkMaintenanceMode() {
    // Get current script name
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    
    // Pages that should be accessible during maintenance
    $allowed_pages = ['login.php', 'process_login.php', 'process_register.php'];
    
    // If current page is in allowed list, skip maintenance check
    if (in_array($current_page, $allowed_pages)) {
        return;
    }
    
    $maintenanceMode = (bool)getSetting('maintenance_mode', false);
    $adminOnlyMode = (bool)getSetting('admin_only_mode', false);
    
    // If either mode is active and user is not admin, handle accordingly
    if (($maintenanceMode || $adminOnlyMode) && !isAdmin()) {
        // Clean any existing output
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Get maintenance message
        $message = $maintenanceMode 
            ? getSetting('maintenance_message', 'Website is currently under maintenance. Please check back later.')
            : 'Website is currently in admin-only mode. Only administrators can access the site.';
        
        // Show maintenance page
        header('Content-Type: text/html; charset=UTF-8');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance Mode</title>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Poppins', sans-serif;
                    background-color: #f8f9fa;
                    color: #333;
                    text-align: center;
                    padding: 50px 20px;
                    line-height: 1.6;
                }
                .maintenance-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #dc3545;
                    margin-bottom: 20px;
                }
                p {
                    margin-bottom: 30px;
                    font-size: 18px;
                }
                .admin-login {
                    margin-top: 30px;
                }
                .admin-login a {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #007bff;
                    color: white;
                    text-decoration: none;
                    font-weight: 500;
                    border-radius: 5px;
                    transition: background-color 0.3s;
                }
                .admin-login a:hover {
                    background-color: #0056b3;
                }
                .logo {
                    max-width: 200px;
                    margin-bottom: 30px;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <img src="assets/images/logo/logo.svg" alt="Logo" class="logo">
                <h1><?= $maintenanceMode ? 'Under Maintenance' : 'Admin-Only Mode' ?></h1>
                <p><?= htmlspecialchars($message) ?></p>
                <p>We appreciate your patience and understanding.</p>
                
                <div class="admin-login">
                    <a href="/PHONE_MART/login.php">Login Page</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

function redirectLoggedInUser() {
    if (isLoggedIn()) {
        // Check maintenance/admin mode first
        checkMaintenanceMode();
        
        if (isAdmin()) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    // Also check maintenance mode for logged-in users
    checkMaintenanceMode();
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Remember Me check (if session not already active)
if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    try {
        // Clean up expired tokens first
        $conn->query("DELETE FROM auth_tokens WHERE expires_at <= NOW()");

        $stmt = $conn->prepare("SELECT user_id FROM auth_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Fetch full user data
            $stmt = $conn->prepare("SELECT UserID, Username, IsAdmin, AvatarPath FROM user WHERE UserID = ?");
            $stmt->bind_param("i", $row['user_id']);
            $stmt->execute();
            $userResult = $stmt->get_result();
            
            if ($userResult && $userResult->num_rows === 1) {
                $user = $userResult->fetch_assoc();

                // Rebuild session
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['logged_in'] = true;
                $_SESSION['is_admin'] = (bool)$user['IsAdmin'];
                $_SESSION['avatar_path'] = $user['AvatarPath'];
                
                // Optionally: Update token expiry
                $newExpiry = date('Y-m-d H:i:s', time() + 86400 * 30);
                $updateStmt = $conn->prepare("UPDATE auth_tokens SET expires_at = ? WHERE token = ?");
                $updateStmt->bind_param("ss", $newExpiry, $token);
                $updateStmt->execute();
                
                // Refresh cookie
                setcookie('remember_token', $token, time() + 86400 * 30, '/', '', true, true);
                
                // Check maintenance mode after auto-login
                checkMaintenanceMode();
            }
        } else {
            // Invalid token — expire cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    } catch (Exception $e) {
        error_log("Remember me token error: " . $e->getMessage());
    }
}

// Check maintenance mode on every page load
checkMaintenanceMode();

?>