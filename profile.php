<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT Username, Email, PhoneNumber, Address FROM user WHERE UserID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: index.php');
    exit();
}

// Fetch user orders with product details
$orders = [];
$orderQuery = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status 
               FROM `order` o
               WHERE o.UserID = ?
               ORDER BY o.OrderDate DESC";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

while ($order = $orders_result->fetch_assoc()) {
    // Get order items for each order
    $itemsQuery = "SELECT oi.*, p.Name as ProductName, pv.Color, pv.Storage 
                   FROM orderitem oi
                   JOIN productvariant pv ON oi.VariantID = pv.VariantID
                   JOIN product p ON pv.ProductID = p.ProductID
                   WHERE oi.OrderID = ?";
    $stmt_items = $conn->prepare($itemsQuery);
    $stmt_items->bind_param("i", $order['OrderID']);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    
    $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
    $orders[] = $order;
}

// Handle alert messages from URL parameters
$alert = null;
if (isset($_GET['success'])) {
    $alert = [
        'message' => getSuccessMessage($_GET['success']),
        'type' => 'success'
    ];
} elseif (isset($_GET['error'])) {
    $alert = [
        'message' => getErrorMessage($_GET['error']),
        'type' => 'error'
    ];
}

function getSuccessMessage($code) {
    $messages = [
        'profile_updated' => 'Profile updated successfully!',
        'password_changed' => 'Password changed successfully!',
        'avatar_updated' => 'Avatar updated successfully!',
        'order_cancelled' => 'Order cancelled successfully!'
    ];
    return $messages[$code] ?? 'Operation completed successfully!';
}

function getErrorMessage($code) {
    $messages = [
        'empty_fields' => 'Please fill all required fields!',
        'email_taken' => 'Email is already taken by another user!',
        'update_failed' => 'Failed to update profile. Please try again.',
        'current_password_empty' => 'Please enter your current password!',
        'password_mismatch' => 'New passwords do not match!',
        'password_too_short' => 'Password must be at least 8 characters!',
        'invalid_current_password' => 'Current password is incorrect!',
        'password_change_failed' => 'Failed to change password. Please try again.',
        'upload_error' => 'Error uploading file. Please try again.',
        'invalid_file_type' => 'Only JPG, PNG, and GIF images are allowed!',
        'file_too_large' => 'Image size must be less than 2MB!',
        'upload_failed' => 'Failed to upload image. Please try again.',
        'avatar_update_failed' => 'Failed to update avatar. Please try again.',
        'invalid_action' => 'Invalid action requested.',
        'cancel_failed' => 'Failed to cancel order. Please try again.',
        'order_not_found' => 'Order not found.'
    ];
    return $messages[$code] ?? 'An error occurred. Please try again.';
}

// Set default avatar if not set or file doesn't exist
$avatar_path = 'assets/images/default-avatar.png'; // Default fallback
if (isset($_SESSION['avatar_path']) && !empty($_SESSION['avatar_path'])) {
    $user_avatar = 'uploads/users/' . $_SESSION['avatar_path'];
    if (file_exists($user_avatar)) {
        $avatar_path = $user_avatar;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - PHONE MART</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/profile.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body>
  <div class="overlay" data-overlay></div>
  
  <?php include 'includes/header.php'; ?>

  <!-- Custom Alert Notification -->
  <div class="custom-alert hide">
      <i class="fas fa-info-circle"></i>
      <span class="alert-msg">Message here</span>
      <div class="close-btn">
          <i class="fas fa-times"></i>
      </div>
  </div>

  <main>
    <div class="container">
      <div class="profile-container">
        <div class="profile-header">
          <h2 class="title">My Profile</h2>
        </div>
        
        <div class="profile-content">
          <div class="profile-avatar">
            <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="User Avatar" id="userAvatar" onerror="this.src='uploads/users/user.png'">
            <button class="btn btn-primary" id="changeAvatarBtn">Change Photo</button>
            <form id="avatarForm" action="includes/process_profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
              <input type="hidden" name="action" value="update_avatar">
              <input type="file" name="avatar" id="realAvatarUpload" accept="image/*">
            </form>
          </div>
          
          <div class="profile-details">
            <form id="profileForm" action="includes/process_profile.php" method="POST">
              <input type="hidden" name="action" value="update_profile">
              
              <div class="profile-field">
                <label>Username</label>
                <p id="usernameDisplay"><?php echo htmlspecialchars($user['Username']); ?></p>
                <input type="text" name="username" id="usernameInput" class="form-control" value="<?php echo htmlspecialchars($user['Username']); ?>" style="display: none;" required minlength="3" maxlength="50">
                <div class="field-error" id="usernameError" style="display: none;"></div>
              </div>
              
              <div class="profile-field">
                <label>Email</label>
                <p id="emailDisplay"><?php echo htmlspecialchars($user['Email']); ?></p>
                <input type="email" name="email" id="emailInput" class="form-control" value="<?php echo htmlspecialchars($user['Email']); ?>" style="display: none;" required>
                <div class="field-error" id="emailError" style="display: none;"></div>
              </div>
              
              <div class="profile-field">
                <label>Phone Number</label>
                <p id="phoneDisplay"><?php echo htmlspecialchars($user['PhoneNumber'] ?? 'Not set'); ?></p>
                <input type="tel" name="phone" id="phoneInput" class="form-control" value="<?php echo htmlspecialchars($user['PhoneNumber'] ?? ''); ?>" style="display: none;" pattern="[0-9\-\+\s\(\)]{10,15}">
                <div class="field-error" id="phoneError" style="display: none;"></div>
              </div>
              
              <div class="profile-field">
                <label>Address</label>
                <p id="addressDisplay"><?php echo htmlspecialchars($user['Address'] ?? 'Not set'); ?></p>
                <textarea name="address" id="addressInput" class="form-control" style="display: none;" maxlength="255"><?php echo htmlspecialchars($user['Address'] ?? ''); ?></textarea>
                <div class="field-error" id="addressError" style="display: none;"></div>
              </div>
              
              <div class="profile-actions">
                <!-- First Row -->
                <div class="top-actions">
                  <button type="button" id="editProfileBtn" class="btn btn-primary">Edit Profile</button>
                  <button type="submit" id="saveProfileBtn" class="btn btn-primary" style="display: none;">Save Changes</button>
                  <button type="button" id="cancelEditBtn" class="btn btn-outline" style="display: none;">Cancel</button>
                  <button type="button" id="changePasswordBtn" class="btn btn-outline">Change Password</button>
                </div>

                <!-- Second Row -->
                <div class="bottom-actions">
                    <button type="button" id="logoutBtn" class="btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                    <a href="includes/logout.php" id="logoutLink" style="display: none;"></a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Orders Section -->
      <div class="orders-container">
        <div class="orders-header">
          <h2 class="title">My Orders</h2>
        </div>
        
        <?php if (!empty($orders)): ?>
          <div class="orders-filters">
            <button class="filter-btn active" data-filter="all">All Orders</button>
            <button class="filter-btn" data-filter="pending">Pending</button>
            <button class="filter-btn" data-filter="shipped">Shipped</button>
            <button class="filter-btn" data-filter="delivered">Delivered</button>
            <button class="filter-btn" data-filter="cancelled">Cancelled</button>
          </div>

          <div class="orders-list">
            <?php foreach ($orders as $order): 
              $statusClass = strtolower($order['Status']);
              $orderDate = date('M d, Y', strtotime($order['OrderDate']));
            ?>
            <div class="order-card <?= $statusClass ?>" data-status="<?= $statusClass ?>">
              <div class="order-header">
                <div class="order-meta">
                  <span class="order-id">Order #<?= $order['OrderID'] ?></span>
                  <span class="order-date"><?= $orderDate ?></span>
                </div>
                <div class="order-status <?= $statusClass ?>">
                  <?= htmlspecialchars($order['Status']) ?>
                </div>
              </div>
              
              <div class="order-body">
                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                  <div class="order-item-details">
                    <div class="order-item-name"><?= htmlspecialchars($item['ProductName']) ?></div>
                    <div class="order-item-variant">
                      <?= htmlspecialchars($item['Color']) ?>, <?= htmlspecialchars($item['Storage']) ?>
                    </div>
                    <div class="order-item-price">LKR <?= number_format($item['UnitPrice'], 2) ?></div>
                    <div class="order-item-quantity">Qty: <?= $item['Quantity'] ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
                
                <div class="order-summary">
                  <span><?= count($order['items']) ?> item<?= count($order['items']) > 1 ? 's' : '' ?></span>
                  <span class="order-total">LKR <?= number_format($order['TotalAmount'], 2) ?></span>
                </div>
                
                <div class="order-actions">
                  <a href="includes/order_details.php?id=<?= urlencode($order['OrderID']) ?>" class="btn-view">
                    Print Order
                  </a>
                  <?php if ($order['Status'] === 'Pending'): ?>
                  <button class="btn-cancel order-cancel" onclick="confirmCancelOrder(<?= $order['OrderID'] ?>)">
                    Cancel Order
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="no-orders">
            <img src="assets/images/icons/empty-order.svg" alt="No orders" onerror="this.style.display='none'">
            <p>You haven't placed any orders yet</p>
            <a href="search.php" class="btn-shop">Start Shopping</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="passwordModal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Change Password</h2>
        <form id="passwordForm" action="includes/process_profile.php" method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input type="password" name="current_password" id="currentPassword" class="form-control" required>
            <div class="field-error" id="currentPasswordError" style="display: none;"></div>
          </div>
          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="8">
            <small class="form-text">Password must be at least 8 characters long</small>
            <div class="field-error" id="newPasswordError" style="display: none;"></div>
          </div>
          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
            <div class="field-error" id="confirmPasswordError" style="display: none;"></div>
          </div>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
      </div>
    </div>

    <!-- Order Cancel Confirmation Modal -->
    <div class="modal" id="cancelOrderModal" style="display: none;">
      <div class="modal-content">
        <span class="close" onclick="closeCancelOrderModal()">&times;</span>
        <h2>Cancel Order</h2>
        <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
        <div class="modal-actions">
          <button class="btn btn-outline" onclick="closeCancelOrderModal()">No, Keep Order</button>
          <button class="btn btn-danger" id="confirmCancelBtn">Yes, Cancel Order</button>
        </div>
      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <script>
    // Profile form validation and editing
    document.getElementById('editProfileBtn').addEventListener('click', function() {
        // Show input fields, hide display paragraphs
        document.getElementById('usernameDisplay').style.display = 'none';
        document.getElementById('usernameInput').style.display = 'block';
        document.getElementById('emailDisplay').style.display = 'none';
        document.getElementById('emailInput').style.display = 'block';
        document.getElementById('phoneDisplay').style.display = 'none';
        document.getElementById('phoneInput').style.display = 'block';
        document.getElementById('addressDisplay').style.display = 'none';
        document.getElementById('addressInput').style.display = 'block';
        
        // Show/Hide buttons
        this.style.display = 'none';
        document.getElementById('saveProfileBtn').style.display = 'inline-block';
        document.getElementById('cancelEditBtn').style.display = 'inline-block';
    });

    document.getElementById('cancelEditBtn').addEventListener('click', function() {
        // Hide input fields, show display paragraphs
        document.getElementById('usernameDisplay').style.display = 'block';
        document.getElementById('usernameInput').style.display = 'none';
        document.getElementById('emailDisplay').style.display = 'block';
        document.getElementById('emailInput').style.display = 'none';
        document.getElementById('phoneDisplay').style.display = 'block';
        document.getElementById('phoneInput').style.display = 'none';
        document.getElementById('addressDisplay').style.display = 'block';
        document.getElementById('addressInput').style.display = 'none';
        
        // Reset form values
        document.getElementById('profileForm').reset();
        
        // Show/Hide buttons
        document.getElementById('editProfileBtn').style.display = 'inline-block';
        document.getElementById('saveProfileBtn').style.display = 'none';
        this.style.display = 'none';
        
        // Clear errors
        clearFieldErrors();
    });

    // Avatar upload handling
    document.getElementById('changeAvatarBtn').addEventListener('click', function() {
        document.getElementById('realAvatarUpload').click();
    });

    document.getElementById('realAvatarUpload').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            // Validate file
            const file = this.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            const maxSize = 2 * 1024 * 1024; // 2MB

            if (!validTypes.includes(file.type)) {
                showCustomAlert('Only JPG, PNG, and GIF images are allowed!', 'error');
                this.value = '';
                return;
            }

            if (file.size > maxSize) {
                showCustomAlert('Image size must be less than 2MB!', 'error');
                this.value = '';
                return;
            }

            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('userAvatar').src = e.target.result;
            };
            reader.readAsDataURL(file);

            // Auto-submit form
            document.getElementById('avatarForm').submit();
        }
    });

    // Password modal handling
    const passwordModal = document.getElementById('passwordModal');
    document.getElementById('changePasswordBtn').addEventListener('click', function() {
        passwordModal.style.display = 'block';
    });

    document.querySelector('.close').addEventListener('click', function() {
        passwordModal.style.display = 'none';
        document.getElementById('passwordForm').reset();
        clearFieldErrors();
    });

    // Password confirmation validation
    document.getElementById('confirmPassword').addEventListener('input', function() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = this.value;
        const errorDiv = document.getElementById('confirmPasswordError');
        
        if (confirmPassword && newPassword !== confirmPassword) {
            errorDiv.textContent = 'Passwords do not match';
            errorDiv.style.display = 'block';
        } else {
            errorDiv.style.display = 'none';
        }
    });

    // Order filtering
    const filterButtons = document.querySelectorAll('.filter-btn');
    const orderCards = document.querySelectorAll('.order-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter orders
            orderCards.forEach(card => {
                const status = card.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Order cancellation
    let orderToCancel = null;

    function confirmCancelOrder(orderId) {
        orderToCancel = orderId;
        document.getElementById('cancelOrderModal').style.display = 'block';
    }

    function closeCancelOrderModal() {
        document.getElementById('cancelOrderModal').style.display = 'none';
        orderToCancel = null;
    }

    document.getElementById('confirmCancelBtn').addEventListener('click', function() {
        if (orderToCancel) {
            // Create form to cancel order
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'includes/process_profile.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'cancel_order';
            
            const orderInput = document.createElement('input');
            orderInput.type = 'hidden';
            orderInput.name = 'order_id';
            orderInput.value = orderToCancel;
            
            form.appendChild(actionInput);
            form.appendChild(orderInput);
            document.body.appendChild(form);
            form.submit();
        }
    });

    // Logout confirmation
    document.getElementById('logoutBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'includes/logout.php';
        }
    });

    // Form validation helper
    function clearFieldErrors() {
        const errorDivs = document.querySelectorAll('.field-error');
        errorDivs.forEach(div => {
            div.style.display = 'none';
            div.textContent = '';
        });
    }

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === passwordModal) {
            passwordModal.style.display = 'none';
            document.getElementById('passwordForm').reset();
            clearFieldErrors();
        }
        if (event.target === document.getElementById('cancelOrderModal')) {
            closeCancelOrderModal();
        }
    });
  </script>

  <!-- custom js link -->
  <script src="assets/js/profile.js"></script>
  <script src="assets/js/index.js"></script>
  <script src="assets/js/custom-alert.js"></script>

  <!-- icon js link -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <?php if ($alert): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showCustomAlert('<?= addslashes($alert['message']) ?>', '<?= $alert['type'] ?>');
        });
    </script>
  <?php endif; ?>
</body>
</html>