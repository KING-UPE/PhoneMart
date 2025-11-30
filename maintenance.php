<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Get maintenance message
$maintenanceMessage = 'Website is currently under maintenance. Please check back later.';
try {
    $result = $conn->query("SELECT SettingValue FROM setting WHERE SettingKey = 'maintenance_message'");
    if ($result && $row = $result->fetch_assoc()) {
        $maintenanceMessage = $row['SettingValue'];
    }
} catch (Exception $e) {
    error_log("Error fetching maintenance message: " . $e->getMessage());
}
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
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
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
        <h1>Under Maintenance</h1>
        <p><?php echo htmlspecialchars($maintenanceMessage); ?></p>
        <p>We're performing some maintenance tasks and will be back shortly.</p>
        <p>Thank you for your patience.</p>
        
        <?php if (!isset($_SESSION['logged_in'])): ?>
        <div class="admin-login">
            <a href="login.php">Admin Login</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>