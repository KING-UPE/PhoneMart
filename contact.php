<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Get cart and wishlist counts if logged in
$cartCount = 0;
$wishlistCount = 0;

if (isLoggedIn()) {
    // Get cart count
    $cartQuery = "SELECT COUNT(*) as count FROM cart c 
                  JOIN cartitem ci ON c.CartID = ci.CartID 
                  WHERE c.UserID = ?";
    $stmt = $conn->prepare($cartQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $cartResult = $stmt->get_result();
    if ($cartResult) {
        $cartCount = $cartResult->fetch_assoc()['count'];
    }
    
    // Get wishlist count
    $wishlistQuery = "SELECT COUNT(*) as count FROM wishlist w 
                      JOIN wishlistitem wi ON w.WishlistID = wi.WishlistID 
                      WHERE w.UserID = ?";
    $stmt = $conn->prepare($wishlistQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $wishlistResult = $stmt->get_result();
    if ($wishlistResult) {
        $wishlistCount = $wishlistResult->fetch_assoc()['count'];
    }
}

// Handle form submission
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
}

// Check for session alert
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - PHONE MART</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/contact.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    <div class="product-container">
      <div class="container">
        <div class="product-box">
          <h2 class="title">Contact Us</h2>
          
          <div class="contact-content">
            <div class="contact-info">
              <div class="info-item">
                <ion-icon name="location-outline"></ion-icon>
                <h3>Our Store</h3>
                <p>123 Tech Street, Colombo 01, Sri Lanka</p>
              </div>
              
              <div class="info-item">
                <ion-icon name="call-outline"></ion-icon>
                <h3>Phone</h3>
                <p>+94 11 234 5678</p>
                <p>+94 77 123 4567 (Mobile)</p>
              </div>
              
              <div class="info-item">
                <ion-icon name="mail-outline"></ion-icon>
                <h3>Email</h3>
                <p>info@phonemart.lk</p>
                <p>support@phonemart.lk</p>
              </div>
              
              <div class="info-item">
                <ion-icon name="time-outline"></ion-icon>
                <h3>Opening Hours</h3>
                <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                <p>Saturday: 9:00 AM - 4:00 PM</p>
                <p>Sunday: Closed</p>
              </div>
            </div>
            
            <form class="contact-form" method="POST">
              <div class="form-group">
                <input type="text" name="name" placeholder="Your Name" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <input type="email" name="email" placeholder="Your Email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <textarea name="message" placeholder="Your Message" rows="5" required><?= 
                    htmlspecialchars($_POST['message'] ?? '') ?></textarea>
              </div>
              
              <button type="submit" class="btn btn-success">Send Message</button>
            </form>
          </div>
          
          <div class="contact-map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.798511757687!2d79.8527554153657!3d6.921682495003692!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2593cf65a1e9d%3A0xe13da4b400e2d38c!2sColombo!5e0!3m2!1sen!2slk!4v1620000000000!5m2!1sen!2slk" 
                    width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <!-- custom js link -->
  <script src="assets/js/contact.js"></script>
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