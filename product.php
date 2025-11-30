<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Get product ID from URL - make sure it's a variant ID
$variantId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$product = [];
$variants = [];
$relatedProducts = [];
$categories = [];

try {
    // First get categories for header
    $categoryQuery = "SELECT c.CategoryID, c.CategoryName, 
                     COUNT(p.ProductID) as product_count 
                     FROM category c
                     LEFT JOIN product p ON c.CategoryID = p.CategoryID
                     GROUP BY c.CategoryID, c.CategoryName";
    $categoryResult = $conn->query($categoryQuery);
    if ($categoryResult) {
        while ($row = $categoryResult->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    // Get product details based on variant ID
    $productQuery = "SELECT 
        p.ProductID, p.Name, p.Description, p.ImagePath1, p.ImagePath2, p.Model, p.CreatedAt,
        b.BrandName, c.CategoryName, c.CategoryID
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    JOIN category c ON p.CategoryID = c.CategoryID
    JOIN productvariant v ON p.ProductID = v.ProductID
    WHERE v.VariantID = ?";
    
    $stmt = $conn->prepare($productQuery);
    $stmt->bind_param("i", $variantId);
    $stmt->execute();
    $productResult = $stmt->get_result();
    
    if ($productResult && $productResult->num_rows > 0) {
        $product = $productResult->fetch_assoc();
        
        // Get all variants for this product
        $variantQuery = "SELECT 
            v.VariantID, v.Color, v.Storage, v.Price, v.DiscountedPrice, v.StockQuantity,
            pr.DiscountPercent, pr.OfferEndDate
        FROM productvariant v
        LEFT JOIN promotion pr ON v.VariantID = pr.VariantID
        WHERE v.ProductID = ?";
        
        $stmt = $conn->prepare($variantQuery);
        $stmt->bind_param("i", $product['ProductID']);
        $stmt->execute();
        $variantResult = $stmt->get_result();
        
        if ($variantResult) {
            while ($row = $variantResult->fetch_assoc()) {
                $variants[] = $row;
            }
            
            // Get first variant as default if current variant not found
            $defaultVariant = $variants[0] ?? null;
            
            // Get related products (same category, different products)
            $relatedQuery = "SELECT 
                p.ProductID, p.Name, p.ImagePath1, p.ImagePath2,
                b.BrandName, c.CategoryName,
                v.VariantID, v.Price, v.DiscountedPrice
            FROM product p
            JOIN brand b ON p.BrandID = b.BrandID
            JOIN category c ON p.CategoryID = c.CategoryID
            JOIN productvariant v ON p.ProductID = v.ProductID
            WHERE p.CategoryID = ? AND p.ProductID != ?
            GROUP BY p.ProductID
            ORDER BY RAND()
            LIMIT 6";
            
            $stmt = $conn->prepare($relatedQuery);
            $stmt->bind_param("ii", $product['CategoryID'], $product['ProductID']);
            $stmt->execute();
            $relatedResult = $stmt->get_result();
            
            if ($relatedResult) {
                while ($row = $relatedResult->fetch_assoc()) {
                    $relatedProducts[] = $row;
                }
            }
        }
    } else {
        // Product not found, redirect to homepage
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Product not found'];
        header("Location: index.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching product: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error loading product details'];
    header("Location: index.php");
    exit();
}

// Get cart and wishlist counts for header
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

function isInWishlist($variantId, $userId) {
    global $conn;
    if (!$userId) return false;
    
    $query = "SELECT wi.WishlistItemID 
              FROM wishlist w
              JOIN wishlistitem wi ON w.WishlistID = wi.WishlistID
              WHERE w.UserID = ? AND wi.VariantID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $variantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
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
  <title><?= htmlspecialchars($product['Name'] ?? 'Product') ?> | PHONE MART</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/product.css">
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
          <?php if ($alert): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showCustomAlert('<?= addslashes($alert['message']) ?>', '<?= $alert['type'] ?>');
                });
            </script>
          <?php endif; ?>
          
          <div class="product-container">
            <div class="product-card">
              <div class="phone-display">
                <img src="../<?= htmlspecialchars($product['ImagePath1']) ?>" alt="<?= htmlspecialchars($product['Name']) ?>" class="phone-image">
              </div>
              <div class="product-details">
                <div class="product-header">
                  <div>
                    <h1 class="product-title"><?= htmlspecialchars($product['Name']) ?></h1>
                    <?php if (strtotime($product['CreatedAt']) > strtotime('-30 days')): ?>
                    <span class="badge-new">new</span>
                    <?php endif; ?>
                  </div>
                  <h3 class="category"><?= htmlspecialchars($product['CategoryName']) ?></h3>
                </div>
                
                <div class="specs">
                  <h3 class="section-title">Key Features</h3>
                  <p class="spec-text"><?= htmlspecialchars($product['Description']) ?></p>
                  <p class="spec-text">Model: <?= htmlspecialchars($product['Model']) ?></p>
                  <p class="spec-text">Brand: <?= htmlspecialchars($product['BrandName']) ?></p>
                </div>
                
                <?php if (!empty($variants)): ?>
                <div class="color-options">
                  <h3 class="section-title">Color</h3>
                  <div class="colors">
                    <?php 
                    // Get unique colors
                    $colors = array_unique(array_column($variants, 'Color'));
                    foreach ($colors as $color): 
                    ?>
                    <span class="color-option <?= $color === $defaultVariant['Color'] ? 'active' : '' ?>" 
                          data-color="<?= htmlspecialchars($color) ?>" 
                          style="background-color: <?= htmlspecialchars($color) ?>"></span>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="selecter-options">
                  <h3 class="section-title">Storage</h3>
                  <div class="selecters">
                    <?php 
                    // Get unique storage options for the default color
                    $storageOptions = array_filter($variants, function($v) use ($defaultVariant) {
                        return $v['Color'] === $defaultVariant['Color'];
                    });
                    foreach ($storageOptions as $option): 
                    ?>
                    <span class="selecter <?= $option['Storage'] === $defaultVariant['Storage'] ? 'active' : '' ?>" 
                          data-variant="<?= $option['VariantID'] ?>"
                          data-storage="<?= htmlspecialchars($option['Storage']) ?>">
                      <?= htmlspecialchars($option['Storage']) ?>
                    </span>
                    <?php endforeach; ?>
                  </div>
                </div>
                <?php endif; ?>

                <div class="purchase-section">
                  <h3 class="section-title">Price</h3>
                  <div class="price-line">
                    <?php if ($defaultVariant['DiscountedPrice']): ?>
                    <div class="current-price">LKR <?= number_format($defaultVariant['DiscountedPrice'], 0) ?></div>
                    <div class="original-price">LKR <?= number_format($defaultVariant['Price'], 0) ?></div>
                    <?php else: ?>
                    <div class="current-price">LKR <?= number_format($defaultVariant['Price'], 0) ?></div>
                    <?php endif; ?>
                  </div>
                  
                  <div class="stock-info">
                    <?php if ($defaultVariant['StockQuantity'] > 0): ?>
                    <p class="in-stock">In Stock: <?= $defaultVariant['StockQuantity'] ?></p>
                    <?php else: ?>
                    <p class="out-of-stock">Out of Stock</p>
                    <?php endif; ?>
                  </div>
                  
                  <div class="action-buttons">
                    <?php if (isLoggedIn()): ?>
                      <?php if ($defaultVariant['StockQuantity'] > 0): ?>
                      <a href="#" class="buy-btn add-to-cart" data-variant="<?= $defaultVariant['VariantID'] ?>">
                        <i class="fas fa-shopping-cart"></i> Add to cart
                      </a>
                      <?php else: ?>
                      <button class="buy-btn disabled" disabled>Out of Stock</button>
                      <?php endif; ?>
                      <button class="wishlist-btn add-to-wishlist" data-variant="<?= $defaultVariant['VariantID'] ?>">
                        <i class="<?= isInWishlist($defaultVariant['VariantID'], $_SESSION['user_id'] ?? 0) ? 'fas' : 'far' ?> fa-heart"></i>
                      </button>
                    <?php else: ?>
                      <a href="login.php" class="buy-btn">
                        <i class="fas fa-shopping-cart"></i> Login to Purchase
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Related Products -->
          <div class="product-main">
            <h2 class="title">You May Also Like</h2>
            <div class="product-grid">
              <?php foreach ($relatedProducts as $related): 
                $hasDiscount = isset($related['DiscountedPrice']) && $related['DiscountedPrice'] > 0;
                $isInWishlist = isLoggedIn() ? isInWishlist($related['VariantID'], $_SESSION['user_id'] ?? 0) : false;
              ?>
              <div class="showcase">
                <div class="showcase-banner">
                  <img src="../<?= htmlspecialchars($related['ImagePath1']) ?>" 
                      alt="<?= htmlspecialchars($related['Name']) ?>" 
                      width="300" class="product-img default">
                  <img src="../<?= htmlspecialchars($related['ImagePath2'] ?? $related['ImagePath1']) ?>" 
                      alt="<?= htmlspecialchars($related['Name']) ?>" 
                      width="300" class="product-img hover">

                  <?php if ($hasDiscount): ?>
                  <p class="showcase-badge">
                    <?= round(100 - ($related['DiscountedPrice'] / $related['Price'] * 100)) ?>% off
                  </p>
                  <?php endif; ?>

                  <div class="showcase-actions">
                    <?php if (isLoggedIn()): ?>
                    <button class="btn-action" data-variant="<?= $related['VariantID'] ?>" 
                            aria-label="<?= $isInWishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                      <ion-icon name="<?= $isInWishlist ? 'heart' : 'heart-outline' ?>"></ion-icon>
                    </button>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="showcase-content">
                  <a href="#" class="showcase-category"><?= htmlspecialchars($related['CategoryName']) ?></a>
                  <a href="product.php?id=<?= $related['VariantID'] ?>">
                    <h3 class="showcase-title"><?= htmlspecialchars($related['Name']) ?></h3>
                  </a>
                  <div class="price-box">
                    <p class="price">LKR <?= number_format($related['DiscountedPrice'] ?? $related['Price'], 0) ?></p>
                    <?php if ($hasDiscount): ?>
                    <del>LKR <?= number_format($related['Price'], 2) ?></del>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
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

  <script src="assets/js/product.js"></script>
  <script src="assets/js/custom-alert.js"></script>
  
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <script>
    // Make variants available to JavaScript
    const productVariants = <?= json_encode($variants) ?>;
    
    // Handle color selection
    document.querySelectorAll('.color-option').forEach(color => {
      color.addEventListener('click', function() {
        // Remove active class from all colors
        document.querySelectorAll('.color-option').forEach(c => c.classList.remove('active'));
        // Add active class to clicked color
        this.classList.add('active');
        
        const selectedColor = this.dataset.color;
        
        // Filter storage options for this color
        const storageContainer = document.querySelector('.selecters');
        storageContainer.innerHTML = '';
        
        // Get all variants for this color
        const colorVariants = productVariants.filter(v => v.Color === selectedColor);
        
        // Add storage options
        colorVariants.forEach(variant => {
          const storageOption = document.createElement('span');
          storageOption.className = 'selecter';
          storageOption.textContent = variant.Storage;
          storageOption.dataset.variant = variant.VariantID;
          storageOption.dataset.storage = variant.Storage;
          
          // Make first one active by default
          if (storageContainer.children.length === 0) {
            storageOption.classList.add('active');
            updateVariantDetails(variant);
          }
          
          storageOption.addEventListener('click', function() {
            document.querySelectorAll('.selecter').forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            updateVariantDetails(variant);
          });
          
          storageContainer.appendChild(storageOption);
        });
      });
    });
    
    // Update all details based on selected variant
    function updateVariantDetails(variant) {
        // Update price display
        const priceElement = document.querySelector('.current-price');
        const originalPriceElement = document.querySelector('.original-price');
        
        if (variant.DiscountedPrice) {
            priceElement.textContent = 'LKR ' + Math.round(variant.DiscountedPrice).toLocaleString('en-US');
            if (originalPriceElement) {
                originalPriceElement.textContent = 'LKR ' + Math.round(variant.Price).toLocaleString('en-US');
                originalPriceElement.style.display = 'block';
            }
        } else {
            priceElement.textContent = 'LKR ' + Math.round(variant.Price).toLocaleString('en-US');
            if (originalPriceElement) {
                originalPriceElement.style.display = 'none';
            }
        }
        
        // Update stock info
        const stockInfo = document.querySelector('.stock-info');
        if (stockInfo) {
            stockInfo.innerHTML = variant.StockQuantity > 0 
                ? `<p class="in-stock">In Stock: ${variant.StockQuantity}</p>`
                : '<p class="out-of-stock">Out of Stock</p>';
        }
        
        // Update add to cart button
        const addToCartBtn = document.querySelector('.add-to-cart');
        if (addToCartBtn) {
            addToCartBtn.dataset.variant = variant.VariantID;
            if (variant.StockQuantity > 0) {
                addToCartBtn.classList.remove('disabled');
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to cart';
            } else {
                addToCartBtn.classList.add('disabled');
                addToCartBtn.disabled = true;
                addToCartBtn.innerHTML = 'Out of Stock';
            }
        }
        
        // Update wishlist button
        const wishlistBtn = document.querySelector('.add-to-wishlist');
        if (wishlistBtn) {
            wishlistBtn.dataset.variant = variant.VariantID;
        }
    }
  </script>
</body>
</html>