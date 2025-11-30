<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header("Location: login.php?redirect=cart.php");
    exit();
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    $cartItemId = $_POST['cart_item_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    
    try {
        // Get or create user's cart
        $userId = $_SESSION['user_id'];
        $cartQuery = "SELECT CartID FROM cart WHERE UserID = ?";
        $stmt = $conn->prepare($cartQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $cart = $stmt->get_result()->fetch_assoc();
        
        if (!$cart) {
            $insertCart = "INSERT INTO cart (UserID) VALUES (?)";
            $stmt = $conn->prepare($insertCart);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $cartId = $conn->insert_id;
        } else {
            $cartId = $cart['CartID'];
        }

        switch ($action) {
            case 'remove':
                if ($cartItemId > 0) {
                    // Verify the cart item belongs to the user
                    $verifyQuery = "SELECT CartItemID FROM cartitem WHERE CartItemID = ? AND CartID = ?";
                    $stmt = $conn->prepare($verifyQuery);
                    $stmt->bind_param("ii", $cartItemId, $cartId);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        // Delete the item
                        $deleteQuery = "DELETE FROM cartitem WHERE CartItemID = ?";
                        $stmt = $conn->prepare($deleteQuery);
                        $stmt->bind_param("i", $cartItemId);
                        $stmt->execute();
                        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Item removed from cart'];
                    } else {
                        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Item not found in cart'];
                    }
                }
                break;
                
            case 'clear':
                // Delete all cart items
                $deleteQuery = "DELETE FROM cartitem WHERE CartID = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $cartId);
                $stmt->execute();
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Cart cleared successfully'];
                break;
                
            case 'update':
                if ($cartItemId > 0 && $quantity > 0) {
                    // Verify the cart item belongs to the user and get stock
                    $verifyQuery = "SELECT pv.StockQuantity, p.Name
                                    FROM cartitem ci
                                    JOIN productvariant pv ON ci.VariantID = pv.VariantID
                                    JOIN product p ON pv.ProductID = p.ProductID
                                    WHERE ci.CartItemID = ? AND ci.CartID = ?";
                    $stmt = $conn->prepare($verifyQuery);
                    $stmt->bind_param("ii", $cartItemId, $cartId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $variant = $result->fetch_assoc();
                        
                        // Check stock
                        if ($quantity <= $variant['StockQuantity']) {
                            // Update quantity
                            $updateQuery = "UPDATE cartitem SET Quantity = ? WHERE CartItemID = ?";
                            $stmt = $conn->prepare($updateQuery);
                            $stmt->bind_param("ii", $quantity, $cartItemId);
                            $stmt->execute();
                            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Quantity updated successfully'];
                        } else {
                            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Only '.$variant['StockQuantity'].' items available for '.$variant['Name']];
                        }
                    } else {
                        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Item not found in cart'];
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error: '.$e->getMessage()];
    }
    
    // Redirect back to cart to prevent form resubmission
    header("Location: cart.php");
    exit();
}

// Get cart items with product details for display
$userId = $_SESSION['user_id'];
$cartQuery = "SELECT CartID FROM cart WHERE UserID = ?";
$stmt = $conn->prepare($cartQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();

$cartItems = [];
$totalPrice = 0;

if ($cart) {
    $cartId = $cart['CartID'];
    
    $itemsQuery = "SELECT 
        ci.CartItemID, ci.Quantity,
        pv.VariantID, pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice,
        p.ProductID, p.Name, p.ImagePath1
    FROM cartitem ci
    JOIN productvariant pv ON ci.VariantID = pv.VariantID
    JOIN product p ON pv.ProductID = p.ProductID
    WHERE ci.CartID = ?";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $price = $row['DiscountedPrice'] ? $row['DiscountedPrice'] : $row['Price'];
        $cartItems[] = [
            'cart_item_id' => $row['CartItemID'],
            'variant_id' => $row['VariantID'],
            'name' => $row['Name'],
            'varient' => $row['Color'] . ' / ' . $row['Storage'],
            'price' => $price,
            'quantity' => $row['Quantity'],
            'img' => $row['ImagePath1']
        ];
        $totalPrice += $price * $row['Quantity'];
    }
}

// Display alert if exists
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHONE MART - Shopping Cart</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/cart.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="overlay" data-overlay></div>
  
  <?php include 'includes/header.php'; ?>

  <main>  
    <div class="product-container">
      <div class="container">
        <div class="product-box">
          <h2 class="title">Your Shopping Cart</h2>
          
          <?php if ($alert): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showCustomAlert('<?= addslashes($alert['message']) ?>', '<?= $alert['type'] ?>');
                });
            </script>
          <?php endif; ?>
          
          <div id="cart-page-items" style="min-height: 50vh;">
            <?php if (empty($cartItems)): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                    <div class="showcase" data-id="<?= $item['variant_id'] ?>" data-cart-item-id="<?= $item['cart_item_id'] ?>">
                        <div class="showcase-banner">
                            <img src="../<?= $item['img'] ?>" alt="<?= $item['name'] ?>" class="showcase-img">
                        </div>
                        <div class="showcase-content">
                            <form method="post" action="cart.php?action=remove" class="remove-form">
                                <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                <button type="submit" class="remove-btn remove-cart-item">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </form>
                            
                            <div class="showcase-header">
                                <div>
                                    <h4 class="showcase-title"><?= $item['name'] ?></h4>
                                    <p class="showcase-category"><?= $item['varient'] ?></p>
                                    <p class="price">LKR <?= number_format(($item['price'] * $item['quantity']), 2) ?></p>
                                </div>
                            </div>
                            
                            <div class="showcase-footer">
                                <form method="post" action="cart.php?action=update" class="quantity-form">
                                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                    <div class="quantity-container">
                                        <button type="button" class="quantity-btn decrease">-</button>
                                        <input type="number" class="quantity-input" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['max_quantity'] ?? 10 ?>">
                                        <button type="button" class="quantity-btn increase">+</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <div class="cart-summary">
            <div class="total-price">Total: LKR <?= number_format($totalPrice, 2) ?></div>
            <?php if (!empty($cartItems)): ?>
                <div class="cart-actions">
                    <form method="post" action="cart.php?action=clear">
                        <button type="submit" class="btn btn-danger" id="clear-cart">Clear Cart</button>
                    </form>
                    <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>
  <script src="assets/js/cart.js"></script>
  <script src="assets/js/index.js"></script>
  <script src="assets/js/custom-alert.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>  
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  
  <script>
  // Handle quantity buttons
  document.querySelectorAll('.quantity-btn').forEach(button => {
      button.addEventListener('click', function() {
          const form = this.closest('.quantity-form');
          const input = form.querySelector('.quantity-input');
          let quantity = parseInt(input.value);
          
          if (this.classList.contains('decrease') && quantity > 1) {
              quantity--;
          } else if (this.classList.contains('increase')) {
              quantity++;
          }
          
          input.value = quantity;
          form.submit();
      });
  });
  
  // Handle direct input changes
  document.querySelectorAll('.quantity-input').forEach(input => {
      input.addEventListener('change', function() {
          const form = this.closest('.quantity-form');
          form.submit();
      });
  });
  </script>
</body>
</html>