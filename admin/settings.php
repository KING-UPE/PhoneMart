<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Initialize message variables
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        // Handle maintenance mode
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $maintenance_message = $_POST['maintenance_message'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO setting (SettingKey, SettingValue) VALUES ('maintenance_mode', ?) 
                              ON DUPLICATE KEY UPDATE SettingValue = ?");
        $stmt->bind_param("ss", $maintenance_mode, $maintenance_mode);
        $stmt->execute();
        
        $stmt = $conn->prepare("INSERT INTO setting (SettingKey, SettingValue) VALUES ('maintenance_message', ?) 
                              ON DUPLICATE KEY UPDATE SettingValue = ?");
        $stmt->bind_param("ss", $maintenance_message, $maintenance_message);
        $stmt->execute();
        
        // Handle admin-only mode
        $admin_only = isset($_POST['admin_only']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO setting (SettingKey, SettingValue) VALUES ('admin_only_mode', ?) 
                              ON DUPLICATE KEY UPDATE SettingValue = ?");
        $stmt->bind_param("ss", $admin_only, $admin_only);
        $stmt->execute();
        
        // Handle clear cache
        if (isset($_POST['clear_cache'])) {
          try {
              // Example: Clear specific cache files, not entire directories
              $safeCacheFiles = [
                  '../cache/template_cache/',
                  '../cache/image_cache/'
              ];
              
              $clearedFiles = 0;
              foreach ($safeCacheFiles as $dir) {
                  if (file_exists($dir)) {
                      $files = glob($dir.'*'); // Get all files
                      foreach($files as $file) {
                          if(is_file($file)) {
                              unlink($file);
                              $clearedFiles++;
                          }
                      }
                  }
              }
              
              $message = "Successfully cleared $clearedFiles cache files";
              $message_type = "success";
          } catch (Exception $e) {
              $message = "Cache clearance partially failed: ".$e->getMessage();
              $message_type = "warning";
          }
        }
        
        // Handle reset demo data
        if (isset($_POST['reset_demo_data'])) {
            // Demo data reset implementation would go here
            $message = "Demo data reset (not implemented in production)";
            $message_type = "info";
        }
        
        $conn->commit();
        
        if (!isset($_POST['clear_cache']) && !isset($_POST['reset_demo_data'])) {
            $message = "Settings updated successfully";
            $message_type = "success";
        }
        
        // Refresh the page to show updated settings
        header("Location: settings.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error updating settings: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get current settings with proper error handling
$settings = [
    'maintenance_mode' => 0,
    'maintenance_message' => '',
    'admin_only_mode' => 0
];

try {
    $result = $conn->query("SELECT SettingKey, SettingValue FROM setting");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (isset($row['SettingKey']) && isset($row['SettingValue'])) {
                $settings[$row['SettingKey']] = $row['SettingValue'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching settings: " . $e->getMessage());
}

$maintenance_mode = (int)($settings['maintenance_mode'] ?? 0);
$maintenance_message = htmlspecialchars($settings['maintenance_message'] ?? '');
$admin_only_mode = (int)($settings['admin_only_mode'] ?? 0);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - Admin Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h3>Phone Mart Admin</h3>
      </div>
      <nav class="sidebar-menu">
        <ul>
          <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
          <li><a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a></li>
          <li><a href="categories.php"><i class="fas fa-list"></i> <span>Categories</span></a></li>
          <li><a href="brands.php"><i class="fas fa-tags"></i> <span>Brands</span></a></li>
          <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
          <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
          <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
          <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        </ul>
      </nav>
    </aside>
    
    <main class="main-content">
      <div class="header">
        <h1>Settings</h1>
      </div>
      
      <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>
      
      <div class="settings-card">
        <!-- Maintenance Mode -->
        <form method="POST" class="setting-item">
          <h3>Maintenance Mode</h3>
          <label class="switch">
            <input type="checkbox" name="maintenance_mode" id="maintenanceToggle" 
                  value="1" <?= $maintenance_mode ? 'checked' : '' ?>>
            <span class="slider"></span>
          </label>
          <p id="maintenanceStatus">Website is <?= $maintenance_mode ? 'UNDER MAINTENANCE' : 'LIVE' ?></p>
          <div id="maintenanceMessageBox" style="<?= $maintenance_mode ? 'display: block;' : 'display: none;' ?>">
            <textarea name="maintenance_message" id="maintenanceMessage" 
                     placeholder="Message for users..."><?= htmlspecialchars($maintenance_message) ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
        </form>

        <!-- Admin-Only Access -->
        <form method="POST" class="setting-item">
          <h3>Admin-Only Testing</h3>
          <label class="switch">
            <input type="checkbox" name="admin_only" id="adminOnlyToggle" 
                  value="1" <?= $admin_only_mode ? 'checked' : '' ?>>
            <span class="slider"></span>
          </label>
          <p>Only admins can access the site.</p>
          <button type="submit" class="btn btn-primary">Save</button>
        </form>

        <!-- Quick Actions -->
        <div class="setting-item">
          <h3>Quick Actions</h3>
          
          <!-- Safe Cache Clear Button -->
          <form method="POST" style="display: inline-block;">
            <input type="hidden" name="clear_cache" value="1">
            <button type="submit" class="btn btn-danger" id="clearCacheBtn">
              <i class="fas fa-broom"></i> Clear Cache
            </button>
          </form>

          <!-- Disabled Demo Data Reset with Warning -->
          <button class="btn btn-danger" disabled title="Disabled in production environment" 
                  style="display: inline-block; cursor: not-allowed;">
            <i class="fas fa-trash"></i> Reset Demo Data (Disabled)
          </button>
          
          <p class="text-muted" style="margin-top: 10px;">
            <small>Demo data reset is disabled in production for safety reasons.</small>
          </p>
        </div>
        
        <div class="setting-item">
          <h3>Session</h3>
          <a href="../includes/logout.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
      </div>
    </main>   
  </div>
  
  <script src="assets/js/settings.js"></script> 
</body>
</html>