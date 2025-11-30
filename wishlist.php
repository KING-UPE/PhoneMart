<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Please login to access your wishlist'];
    header("Location: login.php?redirect=wishlist.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $variantId = intval($_POST['variant_id']);
    
    try {
        // Get user's wishlist
        $wishlistQuery = "SELECT WishlistID FROM wishlist WHERE UserID = ?";
        $stmt = $conn->prepare($wishlistQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $wishlist = $stmt->get_result()->fetch_assoc();
        
        if ($wishlist) {
            $wishlistId = $wishlist['WishlistID'];
            
            // First check if item exists in wishlist
            $checkQuery = "SELECT wi.WishlistItemID, p.Name 
                          FROM wishlistitem wi
                          JOIN productvariant pv ON wi.VariantID = pv.VariantID
                          JOIN product p ON pv.ProductID = p.ProductID
                          WHERE wi.WishlistID = ? AND wi.VariantID = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("ii", $wishlistId, $variantId);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            
            if ($exists) {
                // Remove item from wishlist
                $deleteQuery = "DELETE FROM wishlistitem 
                               WHERE WishlistID = ? AND VariantID = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("ii", $wishlistId, $variantId);
                
                if ($stmt->execute()) {
                    $_SESSION['alert'] = [
                        'type' => 'success', 
                        'message' => $exists['Name'].' removed from wishlist'
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'error', 
                        'message' => 'Failed to remove item from wishlist'
                    ];
                }
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error', 
                    'message' => 'Item not found in wishlist'
                ];
            }
        } else {
            $_SESSION['alert'] = [
                'type' => 'error', 
                'message' => 'Wishlist not found'
            ];
        }
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'error', 
            'message' => 'Error: '.$e->getMessage()
        ];
    }
    
    // Redirect back to prevent form resubmission
    header("Location: wishlist.php");
    exit();
}


// Get user's wishlist items with product details
$wishlistItems = [];

$query = "SELECT 
    wi.WishlistItemID, wi.VariantID,
    pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice,
    p.ProductID, p.Name, p.ImagePath1,
    b.BrandName
FROM wishlist w
JOIN wishlistitem wi ON w.WishlistID = wi.WishlistID
JOIN productvariant pv ON wi.VariantID = pv.VariantID
JOIN product p ON pv.ProductID = p.ProductID
JOIN brand b ON p.BrandID = b.BrandID
WHERE w.UserID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $price = $row['DiscountedPrice'] ? $row['DiscountedPrice'] : $row['Price'];
    $wishlistItems[] = [
        'wishlist_item_id' => $row['WishlistItemID'],
        'variant_id' => $row['VariantID'],
        'name' => $row['Name'],
        'brand' => $row['BrandName'],
        'variant' => $row['Color'] . ' / ' . $row['Storage'],
        'price' => $price,
        'img' => $row['ImagePath1']
    ];
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
    <title>PHONE MART - Wishlist</title>
    <link rel="icon" href="assets/images/logo/favicon.ico">
    <link rel="stylesheet" href="assets/css/wishlist.css">
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
          <h2 class="title">Your Wishlist</h2>
          
          <?php if ($alert): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showCustomAlert('<?= addslashes($alert['message']) ?>', '<?= $alert['type'] ?>');
                });
            </script>
          <?php endif; ?>
          
          <div id="wishlist-page-items" style="min-height: 50vh;">
            <?php if (empty($wishlistItems)): ?>
                <p>Your wishlist is empty.</p>
            <?php else: ?>
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="showcase" data-id="<?= $item['variant_id'] ?>">
                        <div class="showcase-banner">
                            <img src="../<?= $item['img'] ?>" alt="<?= $item['name'] ?>" class="showcase-img">
                        </div>
                        <div class="showcase-content">
                            <form method="post" action="wishlist.php" class="remove-form">
                                <input type="hidden" name="variant_id" value="<?= $item['variant_id'] ?>">
                                <button type="submit" name="remove_item" class="remove-btn remove-wishlist-item">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </form>
                            <div class="showcase-header">
                                <div>
                                    <h4 class="showcase-title"><?= $item['name'] ?></h4>
                                    <p class="showcase-category"><?= $item['brand'] ?></p>
                                    <p class="showcase-variant"><?= $item['variant'] ?></p>
                                    <p class="price">LKR <?= number_format($item['price'], 2) ?></p>
                                </div>
                            </div>
                            
                            <div class="showcase-footer">
                                <?php if (isLoggedIn()): ?>
                                <button class="add-cart-btn" data-variant="<?= $item['variant_id'] ?>">Add to Cart</button>
                                <?php endif; ?>
                                <a href="product.php?id=<?= $item['variant_id'] ?>" class="view-btn">View Product</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
  <!-- Custom Alert Notification -->
  <div class="custom-alert hide">
      <i class="fas fa-info-circle"></i>
      <span class="alert-msg">Message here</span>
      <div class="close-btn">
          <i class="fas fa-times"></i>
      </div>
  </div>                                   
  <?php include 'includes/footer.php'; ?>

  <script src="assets/js/wishlist.js"></script>
  <script src="assets/js/index.js"></script>
  <script src="assets/js/custom-alert.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>  
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>