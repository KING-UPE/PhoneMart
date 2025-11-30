<?php
require_once 'includes/auth.php';
redirectLoggedInUser();

$banError = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'banned') {
        $banReason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : 'Violation of terms';
        $unbanDate = isset($_GET['until']) ? htmlspecialchars($_GET['until']) : 'a future date';
        
        $banError = '
        <div class="ban-error-container">
            <div class="ban-error-header">
                <i class="fas fa-ban"></i>
                <h3>Account Suspended</h3>
            </div>
            <div class="ban-error-content">
                <div class="ban-error-detail">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Reason: '.$banReason.'</span>
                </div>
                <div class="ban-error-detail">
                    <i class="fas fa-clock"></i>
                    <span>Ban expires: '.$unbanDate.'</span>
                </div>
                <div class="ban-countdown">
                    Time remaining: <span id="banCountdown"></span>
                </div>
            </div>
        </div>
        <script>
            if (document.getElementById("banCountdown")) {
                const unbanDate = new Date("'.$unbanDate.'").getTime();
                
                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = unbanDate - now;
                    
                    if (distance < 0) {
                        document.getElementById("banCountdown").innerHTML = "Ban expired - Refresh page";
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    
                    document.getElementById("banCountdown").innerHTML = 
                        `${days}d ${hours}h ${minutes}m`;
                }
                
                updateCountdown();
                setInterval(updateCountdown, 60000);
            }
        </script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" href="assets/images/logo/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/login.css" />
    <title>PHONE MART</title>
  </head>
  <body>
    <!-- Custom Alert Notification -->
    <div class="custom-alert hide">
        <i class="fas fa-info-circle"></i>
        <span class="alert-msg">Message here</span>
        <div class="close-btn">
            <i class="fas fa-times"></i>
        </div>
    </div>
    
    <header class="logo-header">
      <img src="assets/images/logo/logo-light.svg" alt="Phone Shop Logo" class="logo" id="logo-img" />
    </header>      
    <div class="container">
      <div class="forms-container">
        <div class="signin-signup">
          <form action="includes/process_login.php" method="POST" class="sign-in-form">
            <h2 class="title">Sign in</h2>
            <?php echo $banError; ?>
            <div class="input-field">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="input-field">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            <input type="submit" value="Login" class="btn solid" />
          </form>
          
          <form action="includes/process_register.php" method="POST" class="sign-up-form">
            <h2 class="title">Sign up</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" name="username" placeholder="Username" required />
            </div>
            <div class="input-field">
              <i class="fas fa-home"></i>
              <input type="text" name="address" placeholder="Address" required />
            </div>
            <div class="input-field">
              <i class="fas fa-envelope"></i>
              <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="input-field">
              <i class="fas fa-phone"></i>
              <input type="tel" name="phone" placeholder="Phone Number" required />
            </div>            
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" placeholder="Password" required />
            </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" name="confirm_password" placeholder="Confirm Password" required />
            </div>
            <input type="submit" class="btn" value="Sign up" />
          </form>
        </div>
      </div>

      <div class="panels-container">
        <div class="panel left-panel">
          <div class="content">
            <h3>New here?</h3>
            <p>Join our mobile world! Sign up to explore the latest smartphones, accessories, and exclusive deals tailored just for you.</p>
            <button class="btn transparent" id="sign-up-btn">Sign up</button>
          </div>
          <img src="assets/images/log.svg" class="image" alt="" />
        </div>
        <div class="panel right-panel">
          <div class="content">
            <h3>Already with us?</h3>
            <p>Welcome back! Log in to track your orders, manage your profile, and continue shopping your favorite devices.</p>
            <button class="btn transparent" id="sign-in-btn">Sign in</button>
          </div>
          <img src="assets/images/register.svg" class="image" alt="" />
        </div>
      </div>
    </div>
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
    <script src="assets/js/login.js"></script>
    <script src="assets/js/custom-alert.js"></script>
    <?php if (isset($_SESSION['alert'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showCustomAlert('<?= addslashes($_SESSION['alert']['message']) ?>', '<?= $_SESSION['alert']['type'] ?>');
        });
    </script>
    <?php unset($_SESSION['alert']); endif; ?>
  </body>
</html>