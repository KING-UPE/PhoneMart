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
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - PHONE MART</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/about.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body>
  <div class="overlay" data-overlay></div>
  
  <?php include 'includes/header.php'; ?>

  <main>
    <div class="product-container">
      <div class="container">
        <div class="product-box">
          <h2 class="title">About Phone Mart</h2>
          
          <div class="about-content">
            <div class="about-section">
              <h3>Our Story</h3>
              <p>Founded in 2015, Phone Mart has grown from a small tech shop to Sri Lanka's leading mobile device retailer. We started with a simple mission: to provide high-quality smartphones at affordable prices with exceptional customer service.</p>
            </div>
            <div class="why-choose-us">
              <h3>Why Choose Us?</h3>
              <div class="service-grid">
                <div class="service-item">
                  <div class="service-icon">
                    <ion-icon name="boat-outline"></ion-icon>
                  </div>
                  <div class="service-content">
                    <h3 class="service-title">Islandwide Delivery</h3>
                    <p class="service-desc">No Delivery Charges</p>
                  </div>
                </div>
                
                <div class="service-item">
                  <div class="service-icon">
                    <ion-icon name="rocket-outline"></ion-icon>
                  </div>
                  <div class="service-content">
                    <h3 class="service-title">Next Day Delivery</h3>
                    <p class="service-desc">Only For Pre Orders</p>
                  </div>
                </div>
                
                <div class="service-item">
                  <div class="service-icon">
                    <ion-icon name="call-outline"></ion-icon>
                  </div>
                  <div class="service-content">
                    <h3 class="service-title">24h Online Support</h3>
                    <p class="service-desc">Hotline +94 7X XXX XXXX</p>
                  </div>
                </div>
                
                <div class="service-item">
                  <div class="service-icon">
                    <ion-icon name="arrow-undo-outline"></ion-icon>
                  </div>
                  <div class="service-content">
                    <h3 class="service-title">Return Policy</h3>
                    <p class="service-desc">Easy & Free Return</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="about-section">
              <h3>Our Team</h3>
              <div class="team-grid">
                <div class="team-member">
                  <img src="assets/images/owner.png" alt="CEO">
                  <h4>Upendra Uddimantha</h4>
                  <p>Founder & CEO</p>
                </div>
                
                <div class="team-member">
                  <img src="assets/images/owner.png" alt="CTO">
                  <h4>Upendra Uddimantha</h4>
                  <p>Chief Technology Officer</p>
                </div>
              </div>
            </div>
            
            <div class="about-section">
              <h3>Our Values</h3>
              <ul class="values-list">
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Customer Satisfaction Above All</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Transparency in Pricing</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> After-Sales Support</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Continuous Innovation</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <!-- custom js link -->
  <script src="assets/js/about.js"></script>

  <!-- icon js link -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>
</html>