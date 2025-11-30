<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

// Initialize variables
$userId = $_SESSION['user_id'];
$cartItems = [];
$totalPrice = 0;
$user = [];
$error = '';

// Get user details with error handling
try {
    $userQuery = "SELECT Username, Email, PhoneNumber, Address FROM user WHERE UserID = ?";
    $stmt = $conn->prepare($userQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare user query: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        throw new Exception("User not found");
    }
} catch (Exception $e) {
    $error = "Error fetching user details: " . $e->getMessage();
    $_SESSION['alert'] = ['type' => 'error', 'message' => $error];
    header("Location: cart.php");
    exit();
}

// Get cart items with error handling
try {
    // First get the cart ID
    $cartQuery = "SELECT CartID FROM cart WHERE UserID = ?";
    $stmt = $conn->prepare($cartQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare cart query: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();

    if ($cart) {
        $cartId = $cart['CartID'];
        
        // Then get all items in the cart
        $itemsQuery = "SELECT 
            ci.CartItemID, ci.Quantity,
            pv.VariantID, pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice, pv.StockQuantity,
            p.ProductID, p.Name, p.ImagePath1
        FROM cartitem ci
        JOIN productvariant pv ON ci.VariantID = pv.VariantID
        JOIN product p ON pv.ProductID = p.ProductID
        WHERE ci.CartID = ?";
        
        $stmt = $conn->prepare($itemsQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare items query: " . $conn->error);
        }
        $stmt->bind_param("i", $cartId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Check stock availability
            if ($row['StockQuantity'] < $row['Quantity']) {
                throw new Exception('Only '.$row['StockQuantity'].' items available for '.$row['Name']);
            }
            
            $price = $row['DiscountedPrice'] ? $row['DiscountedPrice'] : $row['Price'];
            $cartItems[] = [
                'variant_id' => $row['VariantID'],
                'name' => $row['Name'],
                'variant' => $row['Color'] . ' / ' . $row['Storage'],
                'price' => $price,
                'quantity' => $row['Quantity'],
                'img' => $row['ImagePath1'],
                'stock' => $row['StockQuantity']
            ];
            $totalPrice += $price * $row['Quantity'];
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    $_SESSION['alert'] = ['type' => 'error', 'message' => $error];
    header("Location: cart.php");
    exit();
}

// Handle order submission with transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $shippingAddress = $_POST['shipping_address'] ?? $user['Address'];
    
    // Validate inputs
    if (empty($cartItems)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Your cart is empty'];
        header("Location: cart.php");
        exit();
    }
    
    if (empty($paymentMethod)) {
      $_SESSION['alert'] = [
          'type' => 'error',
          'message' => 'Please select a payment method'
      ];
      header("Location: checkout.php");
      exit();
    }
    
    try {
        $conn->begin_transaction();
        
        // 1. Create the order record
        $orderQuery = "INSERT INTO `order` (UserID, OrderDate, TotalAmount, Status, PaymentMethod, ShippingAddress) 
                      VALUES (?, NOW(), ?, 'Pending', ?, ?)";
        $stmt = $conn->prepare($orderQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare order query: " . $conn->error);
        }
        $stmt->bind_param("idss", $userId, $totalPrice, $paymentMethod, $shippingAddress);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $stmt->error);
        }
        $orderId = $conn->insert_id;
        
        // 2. Add all order items
        foreach ($cartItems as $item) {
            $orderItemQuery = "INSERT INTO orderitem (OrderID, VariantID, Quantity, UnitPrice) 
                             VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($orderItemQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare order item query: " . $conn->error);
            }
            $stmt->bind_param("iiid", $orderId, $item['variant_id'], $item['quantity'], $item['price']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to add order item: " . $stmt->error);
            }
            
            // 3. Update stock quantities
            $updateStockQuery = "UPDATE productvariant SET StockQuantity = StockQuantity - ? WHERE VariantID = ?";
            $stmt = $conn->prepare($updateStockQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare stock update query: " . $conn->error);
            }
            $stmt->bind_param("ii", $item['quantity'], $item['variant_id']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update stock: " . $stmt->error);
            }
        }
        
        // 4. Clear the cart
        $clearCartQuery = "DELETE FROM cartitem WHERE CartID = ?";
        $stmt = $conn->prepare($clearCartQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare cart clear query: " . $conn->error);
        }
        $stmt->bind_param("i", $cartId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to clear cart: " . $stmt->error);
        }
        
        // Commit the transaction
        $conn->commit();

        // Store all necessary data in session
        $_SESSION['order_id'] = $orderId;
        $_SESSION['print_data'] = [
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
            'user' => $user,
            'paymentMethod' => $paymentMethod,
            'shippingAddress' => $shippingAddress
        ];

        header("Location: checkout.php?success=order_placed");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error placing order: '.$e->getMessage()];
        header("Location: checkout.php");
        exit();
    }
}

// Handle success message
$alert = null;
if (isset($_GET['success']) && $_GET['success'] === 'order_placed') {
    $alert = [
        'type' => 'success',
        'message' => 'Order placed successfully!'
    ];
    
    // Load print data from session
    if (isset($_SESSION['print_data'])) {
        $cartItems = $_SESSION['print_data']['cartItems'];
        $totalPrice = $_SESSION['print_data']['totalPrice'];
        $user = $_SESSION['print_data']['user'];
        $paymentMethod = $_SESSION['print_data']['paymentMethod'];
        $shippingAddress = $_SESSION['print_data']['shippingAddress'];
    }
    
    // Show order confirmation modal
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showOrderConfirmation(); });</script>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHONE MART - Checkout</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/checkout.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="overlay" data-overlay></div>
  
  <?php include 'includes/header.php'; ?>
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
          <h2 class="title">Checkout</h2>
          
          <?php if ($alert): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showCustomAlert('<?= addslashes($alert['message']) ?>', '<?= $alert['type'] ?>');
                });
            </script>
          <?php endif; ?>
          
          <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
              <p>Your cart is empty. <a href="search.php">Continue shopping</a></p>
            </div>
          <?php else: ?>
            <form method="post" action="checkout.php" id="checkout-form">
              <div class="checkout-container">
                <div class="checkout-section">
                  <h3>Shipping Information</h3>
                  <div class="address-form">
                    <div class="form-group">
                      <label>Name</label>
                      <p><?= htmlspecialchars($user['Username']) ?></p>
                    </div>
                    <div class="form-group">
                      <label>Email</label>
                      <p><?= htmlspecialchars($user['Email']) ?></p>
                    </div>
                    <div class="form-group">
                      <label>Phone</label>
                      <p><?= htmlspecialchars($user['PhoneNumber'] ?? 'Not provided') ?></p>
                    </div>
                    <div class="form-group">
                      <label for="shipping_address">Shipping Address</label>
                      <textarea name="shipping_address" id="shipping_address" required><?= htmlspecialchars($user['Address'] ?? '') ?></textarea>
                    </div>
                  </div>
                  
                  <h3>Payment Method</h3>
                  <div class="payment-methods">
                    <label class="payment-method">
                      <input type="radio" name="payment_method" value="cash_on_delivery" required>
                      <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                      <div class="details">
                        <h4>Cash on Delivery</h4>
                        <p>Pay with cash when your order is delivered</p>
                      </div>
                    </label>
                    
                    <label class="payment-method">
                      <input type="radio" name="payment_method" value="credit_card">
                      <div class="icon"><i class="far fa-credit-card"></i></div>
                      <div class="details">
                        <h4>Credit/Debit Card</h4>
                        <p>Pay securely with your credit or debit card</p>
                      </div>
                    </label>
                    
                    <label class="payment-method">
                      <input type="radio" name="payment_method" value="bank_transfer">
                      <div class="icon"><i class="fas fa-university"></i></div>
                      <div class="details">
                        <h4>Bank Transfer</h4>
                        <p>Transfer money directly from your bank account</p>
                      </div>
                    </label>
                  </div>
                </div>
                
                <div class="checkout-section">
                  <h3>Order Summary</h3>
                  <div class="order-summary">
                    <?php foreach ($cartItems as $item): ?>
                      <div class="order-summary-item">
                        <span class="phn_name"><?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['variant']) ?>) x <?= $item['quantity'] ?></span>
                        <span class="phn_price">LKR <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                      </div>
                    <?php endforeach; ?>
                    
                    <div class="order-summary-item-total order-summary-total">
                      <span class="total">Total</span>
                      <span class="total">LKR <?= number_format($totalPrice, 2) ?></span>
                    </div>
                  </div>
                  
                  <button type="submit" name="place_order" class="place-order-btn">
                    Place Order
                  </button>
                </div>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <!-- Order Confirmation Modal -->
  <div class="confirmation-modal" id="order-confirmation-modal">
    <div class="confirmation-content">
      <div class="confirmation-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <h3 class="confirmation-title">Order Confirmed!</h3>
      <p class="confirmation-message">Thank you for your purchase. Your order has been placed successfully.</p>
      
      <div class="confirmation-actions">
        <a href="profile.php" class="confirmation-btn confirmation-btn-primary">
          View Order in Profile
        </a>
        <a href="index.php" class="confirmation-btn confirmation-btn-secondary">
          Continue Shopping
        </a>
        <button onclick="printOrder()" class="confirmation-btn confirmation-btn-light print-btn no-print">
          <i class="fas fa-print"></i> Print Receipt
        </button>
      </div>
    </div>
  </div>
  <!-- Printable Order Content -->
  <div id="print-content" style="display: none;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
          <h2 style="text-align: center; margin-bottom: 20px;">PHONE MART - Order Receipt</h2>
          
          <div style="margin-bottom: 30px;">
              <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Order #<?= $_SESSION['order_id'] ?? 'N/A' ?></h3>
              <p><strong>Date:</strong> <?= date('F j, Y H:i:s') ?></p>
              <p><strong>Status:</strong> Pending</p>
          </div>
          
          <div style="margin-bottom: 30px;">
              <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Customer Information</h3>
              <p><strong>Name:</strong> <?= htmlspecialchars($user['Username'] ?? 'N/A') ?></p>
              <p><strong>Email:</strong> <?= htmlspecialchars($user['Email'] ?? 'N/A') ?></p>
              <p><strong>Phone:</strong> <?= htmlspecialchars($user['PhoneNumber'] ?? 'N/A') ?></p>
              <p><strong>Shipping Address:</strong> <?= htmlspecialchars($shippingAddress ?? 'N/A') ?></p>
          </div>
          
          <div style="margin-bottom: 30px;">
              <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Order Items</h3>
              <table style="width: 100%; border-collapse: collapse;">
                  <thead>
                      <tr style="border-bottom: 1px solid #ddd;">
                          <th style="text-align: left; padding: 8px;">Item</th>
                          <th style="text-align: right; padding: 8px;">Price</th>
                          <th style="text-align: right; padding: 8px;">Qty</th>
                          <th style="text-align: right; padding: 8px;">Total</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach ($cartItems as $item): ?>
                      <tr style="border-bottom: 1px solid #eee;">
                          <td style="padding: 8px;"><?= htmlspecialchars($item['name']) ?><br><small><?= htmlspecialchars($item['variant']) ?></small></td>
                          <td style="text-align: right; padding: 8px;">LKR <?= number_format($item['price'], 2) ?></td>
                          <td style="text-align: right; padding: 8px;"><?= $item['quantity'] ?></td>
                          <td style="text-align: right; padding: 8px;">LKR <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                      <tr>
                          <td colspan="3" style="text-align: right; padding: 8px; font-weight: bold;">Total:</td>
                          <td style="text-align: right; padding: 8px; font-weight: bold;">LKR <?= number_format($totalPrice, 2) ?></td>
                      </tr>
                  </tfoot>
              </table>
          </div>
          
          <div>
              <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">Payment Information</h3>
              <p><strong>Payment Method:</strong> 
                  <?php 
                  if (!empty($paymentMethod)) {
                      switch ($paymentMethod) {
                          case 'cash_on_delivery': echo 'Cash on Delivery'; break;
                          case 'credit_card': echo 'Credit/Debit Card'; break;
                          case 'bank_transfer': echo 'Bank Transfer'; break;
                          default: echo 'N/A';
                      }
                  } else {
                      echo 'N/A';
                  }
                  ?>
              </p>
          </div>
      </div>
  </div>

  <?php include 'includes/footer.php'; ?>
    <script src="assets/js/index.js"></script>
  <script src="assets/js/custom-alert.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  
  <script>
  // Payment method selection
  document.querySelectorAll('.payment-method').forEach(method => {
      method.addEventListener('click', function() {
          const radio = this.querySelector('input[type="radio"]');
          radio.checked = true;
          
          // Update UI
          document.querySelectorAll('.payment-method').forEach(m => {
              m.classList.remove('selected');
          });
          this.classList.add('selected');
      });
  });
  
  // Show order confirmation modal
  function showOrderConfirmation() {
      document.getElementById('order-confirmation-modal').classList.add('active');
  }
  
  // Close modal when clicking outside
  document.getElementById('order-confirmation-modal').addEventListener('click', function(e) {
      if (e.target === this) {
          this.classList.remove('active');
      }
  });
  
  // Print order function
 function printOrder() {
    // Get the print content HTML
    const printContent = document.getElementById('print-content').innerHTML;

    // Create a new window
    const printWindow = window.open('', '_blank');

    // Write the complete HTML document to the new window
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Order Receipt</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                }
                /* Add any other styles needed for your print layout */
                @media print {
                    .print-button {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);

    // Close the document stream to load the content
    printWindow.document.close();

    // Automatically trigger the print dialog
    printWindow.print();
}


    // Form validation
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        const shippingAddress = document.getElementById('shipping_address').value.trim();
        
        let isValid = true;
        let errorMessage = '';
        
        if (!paymentMethod) {
            isValid = false;
            errorMessage = 'Please select a payment method';
        } else if (!shippingAddress) {
            isValid = false;
            errorMessage = 'Please enter a shipping address';
        }
        
        if (!isValid) {
            e.preventDefault();
            showCustomAlert(errorMessage, 'error');
            
            // Scroll to the top to show the alert
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    });
  </script>
</body>
</html>