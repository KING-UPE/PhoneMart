<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Function to check if item is in wishlist
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

// Get products from database
$products = [];
$featuredProducts = [];
$newArrivals = [];
$trendingProducts = [];

try {
    // Get all products with their variants
    $productQuery = "SELECT 
        p.ProductID, p.Name, p.Description, p.ImagePath1, p.ImagePath2, p.CreatedAt,
        b.BrandName, c.CategoryName,
        pv.VariantID, pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice, pv.StockQuantity,
        pr.DiscountPercent, pr.OfferEndDate,
        COUNT(oi.OrderItemID) as order_count
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    JOIN category c ON p.CategoryID = c.CategoryID
    JOIN productvariant pv ON p.ProductID = pv.ProductID
    LEFT JOIN promotion pr ON pv.VariantID = pr.VariantID
    LEFT JOIN orderitem oi ON pv.VariantID = oi.VariantID
    GROUP BY p.ProductID, pv.VariantID
    ORDER BY p.CreatedAt DESC";

    $result = $conn->query($productQuery);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $productId = $row['ProductID'];
            
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $row['ProductID'],
                    'name' => $row['Name'],
                    'brand' => $row['BrandName'],
                    'category' => $row['CategoryName'],
                    'description' => $row['Description'],
                    'image1' => $row['ImagePath1'],
                    'image2' => $row['ImagePath2'],
                    'created_at' => $row['CreatedAt'],
                    'order_count' => $row['order_count'],
                    'variants' => []
                ];
            }
            
            $products[$productId]['variants'][] = [
                'id' => $row['VariantID'],
                'color' => $row['Color'],
                'storage' => $row['Storage'],
                'price' => $row['Price'],
                'discounted_price' => $row['DiscountedPrice'],
                'stock' => $row['StockQuantity'],
                'discount_percent' => $row['DiscountPercent'],
                'offer_end' => $row['OfferEndDate']
            ];
        }

        // Prepare different product views
        $newArrivals = array_slice($products, 0, 4);
        
        $productsForTrending = $products;
        usort($productsForTrending, function($a, $b) {
            return $b['order_count'] <=> $a['order_count'];
        });
        $trendingProducts = array_slice($productsForTrending, 0, 4);

        $featuredProducts = array_slice($products, 0, 6);
    }
    
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
}

// Get categories for sidebar
$categories = [];
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

// Get deals of the month
$dealsOfTheMonth = [];
$currentDate = date('Y-m-d H:i:s');

$dealQuery = "SELECT 
    p.ProductID, p.Name, p.Description, p.ImagePath1, p.ImagePath2, 
    b.BrandName, c.CategoryName,
    pv.VariantID, pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice,
    pr.DiscountPercent, pr.OfferEndDate
FROM product p
JOIN brand b ON p.BrandID = b.BrandID
JOIN category c ON p.CategoryID = c.CategoryID
JOIN productvariant pv ON p.ProductID = pv.ProductID
JOIN promotion pr ON pv.VariantID = pr.VariantID
WHERE pr.OfferEndDate > ?
ORDER BY pr.OfferEndDate ASC
LIMIT 2";

$stmt = $conn->prepare($dealQuery);
$stmt->bind_param("s", $currentDate);
$stmt->execute();
$dealResult = $stmt->get_result();

if ($dealResult) {
    while ($row = $dealResult->fetch_assoc()) {
        $productId = $row['ProductID'];
        
        if (!isset($dealsOfTheMonth[$productId])) {
            $dealsOfTheMonth[$productId] = [
                'id' => $row['ProductID'],
                'name' => $row['Name'],
                'brand' => $row['BrandName'],
                'category' => $row['CategoryName'],
                'description' => $row['Description'],
                'image1' => $row['ImagePath1'],
                'image2' => $row['ImagePath2'],
                'variants' => []
            ];
        }
        
        $dealsOfTheMonth[$productId]['variants'][] = [
            'id' => $row['VariantID'],
            'color' => $row['Color'],
            'storage' => $row['Storage'],
            'price' => $row['Price'],
            'discounted_price' => $row['DiscountedPrice'],
            'discount_percent' => $row['DiscountPercent'],
            'offer_end' => $row['OfferEndDate']
        ];
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
  <title>PHONE MART</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/index.css">
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
    <!-- BANNER SECTION -->
    <div class="banner">
      <div class="container">
        <div class="slider-container has-scrollbar">
          <div class="slider-item">
            <img src="./assets/images/banner-1.png" alt="iphone-16" class="banner-img">
            <div class="banner-content">
              <p class="banner-subtitle">Introducing New</p>
              <h2 class="banner-title">iPhone 16</h2>
              <a href="#" class="banner-btn">Shop now</a>
            </div>
          </div>

          <div class="slider-item">
            <img src="./assets/images/banner-2.png" alt="samsung-s25-ultra" class="banner-img">
            <div class="banner-content">
              <p class="banner-subtitle">New Galaxy AI With</p>
              <h2 class="banner-title">Samsung S25</h2>
              <a href="#" class="banner-btn">Shop now</a>
            </div>
          </div>
        </div>  
      </div>
    </div>

    <!-- PRODUCT SECTION -->
    <div class="product-container">
      <div class="container">
        <!-- SIDEBAR -->
        <div class="sidebar has-scrollbar" data-mobile-menu>
          <div class="sidebar-category">
            <div class="sidebar-top">
              <h2 class="sidebar-title">Category</h2>
              <button class="sidebar-close-btn" data-mobile-menu-close-btn>
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>
            <ul class="sidebar-menu-category-list">
              <?php foreach ($categories as $category): ?>
              <li class="sidebar-menu-category">
                <button class="sidebar-accordion-menu" data-accordion-btn>
                  <div class="menu-title-flex">
                    <img src="./assets/images/icons/<?= strtolower($category['CategoryName']) ?>.svg" 
                        alt="<?= htmlspecialchars($category['CategoryName']) ?>" 
                        class="menu-title-img" width="20" height="20">
                    <p class="menu-title"><?= htmlspecialchars($category['CategoryName']) ?></p>
                  </div>
                  <div>
                    <ion-icon name="add-outline" class="add-icon"></ion-icon>
                    <ion-icon name="remove-outline" class="remove-icon"></ion-icon>
                  </div>
                </button>

                <ul class="sidebar-submenu-category-list" data-accordion>
                  <?php 
                  // Get brands for this category
                  $brandsQuery = "SELECT b.BrandID, b.BrandName, 
                                  COUNT(p.ProductID) as product_count
                                  FROM brand b
                                    JOIN product p ON b.BrandID = p.BrandID
                                  WHERE p.CategoryID = ?
                                  GROUP BY b.BrandID, b.BrandName
                                  ORDER BY b.BrandName";
                  $stmt = $conn->prepare($brandsQuery);
                  $stmt->bind_param("i", $category['CategoryID']);
                  $stmt->execute();
                  $brands = $stmt->get_result();
                  
                  if ($brands && $brands->num_rows > 0) {
                      while ($brand = $brands->fetch_assoc()) {
                          echo '<li class="sidebar-submenu-category">
                                  <a href="search.php?category='.$category['CategoryID'].'&brand='.$brand['BrandID'].'" class="sidebar-submenu-title">
                                    <p class="product-name">'.htmlspecialchars($brand['BrandName']).'</p>
                                    <data value="'.$brand['product_count'].'" class="stock" title="Products">'.$brand['product_count'].'</data>
                                  </a>
                                </li>';
                      } 
                  } else {
                      echo '<li class="sidebar-submenu-category">
                              <p class="product-name">No brands found</p>
                            </li>';
                  }
                  ?>
                </ul>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <!-- PRODUCT CONTENT -->
        <div class="product-box">
          <!-- NEW ARRIVALS -->
          <div class="product-minimal">
            <div class="product-showcase">
              <h2 class="title">New Arrivals</h2>
              <div class="showcase-wrapper has-scrollbar">
                <div class="showcase-container">
                  <?php foreach ($newArrivals as $product): 
                    $firstVariant = $product['variants'][0];
                  ?>
                  <div class="showcase">
                    <a href="product.php?id=<?= $firstVariant['id'] ?>" class="showcase-img-box">
                      <img src="../<?= htmlspecialchars($product['image1']) ?>" 
                          alt="<?= htmlspecialchars($product['name']) ?>" 
                          width="70" class="showcase-img">
                    </a>
                    <div class="showcase-content">
                      <a href="product.php?id=<?= $firstVariant['id'] ?>">
                        <h4 class="showcase-title"><?= htmlspecialchars($product['name']) ?></h4>
                      </a>
                      <a href="#" class="showcase-category"><?= htmlspecialchars($product['category']) ?></a>
                      <div class="price-box">
                        <p class="price">LKR <?= number_format($firstVariant['discounted_price'] ?? $firstVariant['price'], 0) ?></p>
                        <?php if ($firstVariant['discounted_price']): ?>
                        <del>LKR <?= number_format($firstVariant['price'], 2) ?></del>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- TRENDING PRODUCTS -->
            <div class="product-showcase">
              <h2 class="title">Trending</h2>
              <div class="showcase-wrapper has-scrollbar">
                <div class="showcase-container">
                  <?php foreach ($trendingProducts as $product): 
                    $firstVariant = $product['variants'][0];
                  ?>
                  <div class="showcase">
                    <a href="product.php?id=<?= $firstVariant['id'] ?>" class="showcase-img-box">
                      <img src="../<?= htmlspecialchars($product['image1']) ?>" 
                           alt="<?= htmlspecialchars($product['name']) ?>" 
                           width="70" class="showcase-img">
                    </a>
                    <div class="showcase-content">
                      <a href="product.php?id=<?= $firstVariant['id'] ?>">
                        <h4 class="showcase-title"><?= htmlspecialchars($product['name']) ?></h4>
                      </a>
                      <a href="#" class="showcase-category"><?= htmlspecialchars($product['category']) ?></a>
                      <div class="price-box">
                        <p class="price">LKR <?= number_format($firstVariant['discounted_price'] ?? $firstVariant['price'], 0) ?></p>
                        <?php if ($firstVariant['discounted_price']): ?>
                        <del>LKR <?= number_format($firstVariant['price'], 2) ?></del>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- DEAL OF THE MONTH -->
          <div class="product-featured">
              <h2 class="title">Deal of the Month</h2>
              <div class="showcase-wrapper has-scrollbar">
                  <?php foreach ($dealsOfTheMonth as $product): 
                      $firstVariant = $product['variants'][0];
                      $hasDiscount = isset($firstVariant['discount_percent']);
                  ?>
                  <div class="showcase-container">
                      <div class="showcase">
                          <div class="showcase-banner">
                              <img src="../<?= htmlspecialchars($product['image1']) ?>" 
                                  alt="<?= htmlspecialchars($product['name']) ?>" 
                                  class="showcase-img">
                          </div>
                          <div class="showcase-content">
                              <a href="product.php?id=<?= $firstVariant['id'] ?>">
                                  <h3 class="showcase-title"><?= htmlspecialchars($product['name']) ?></h3>
                              </a>
                              <p class="showcase-desc"><?= htmlspecialchars($product['description']) ?></p>
                              <div class="price-box">
                                  <p class="price">LKR <?= number_format($firstVariant['discounted_price'] ?? $firstVariant['price'], 2) ?></p>
                                  <?php if ($firstVariant['discounted_price']): ?>
                                  <del>LKR <?= number_format($firstVariant['price'], 2) ?></del>
                                  <?php endif; ?>
                              </div>
                              
                              <?php if (isLoggedIn()): ?>
                              <button class="add-cart-btn" data-variant="<?= $firstVariant['id'] ?>">add to cart</button>
                              <?php else: ?>
                              <a href="login.php" class="add-cart-btn">Login to Purchase</a>
                              <?php endif; ?>

                              <div class="countdown-box">
                                  <p class="countdown-desc">Hurry Up! Offer ends in:</p>
                                  <div class="countdown" data-end="<?= $firstVariant['offer_end'] ?>">
                                      <div class="countdown-content">
                                          <p class="display-number days">00</p>
                                          <p class="display-text">Days</p>
                                      </div>
                                      <div class="countdown-content">
                                          <p class="display-number hours">00</p>
                                          <p class="display-text">Hours</p>
                                      </div>
                                      <div class="countdown-content">
                                          <p class="display-number minutes">00</p>
                                          <p class="display-text">Min</p>
                                      </div>
                                      <div class="countdown-content">
                                          <p class="display-number seconds">00</p>
                                          <p class="display-text">Sec</p>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <?php endforeach; ?>
              </div>
          </div>

          <!-- FEATURED PRODUCTS GRID -->
          <div class="product-main">
            <h2 class="title">Featured Products</h2>
            <div class="product-grid">
              <?php foreach ($featuredProducts as $product): 
                $firstVariant = $product['variants'][0];
                $hasDiscount = isset($firstVariant['discounted_price']);
                $isInWishlist = isLoggedIn() ? isInWishlist($firstVariant['id'], $_SESSION['user_id']) : false;
              ?>
              <div class="showcase">
                <div class="showcase-banner">
                  <img src="../<?= htmlspecialchars($product['image1']) ?>" 
                       alt="<?= htmlspecialchars($product['name']) ?>" 
                       width="300" class="product-img default">
                  <img src="../<?= htmlspecialchars($product['image2'] ?? $product['image1']) ?>" 
                       alt="<?= htmlspecialchars($product['name']) ?>" 
                       width="300" class="product-img hover">

                  <?php if ($hasDiscount): ?>
                  <p class="showcase-badge"><?= round(100 - ($firstVariant['discounted_price'] / $firstVariant['price'] * 100)) ?>% off</p>
                  <?php endif; ?>

                  <div class="showcase-actions">
                    <?php if (isLoggedIn()): ?>
                    <button class="btn-action" data-variant="<?= $firstVariant['id'] ?>" 
                            aria-label="<?= $isInWishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                      <ion-icon name="<?= $isInWishlist ? 'heart' : 'heart-outline' ?>"></ion-icon>
                    </button>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="showcase-content">
                  <a href="#" class="showcase-category"><?= htmlspecialchars($product['category']) ?></a>
                  <a href="product.php?id=<?= $firstVariant['id'] ?>">
                    <h3 class="showcase-title"><?= htmlspecialchars($product['name']) ?></h3>
                  </a>
                  <div class="price-box">
                    <p class="price">LKR <?= number_format($firstVariant['discounted_price'] ?? $firstVariant['price'], 2) ?></p>
                    <?php if ($hasDiscount): ?>
                    <del>LKR <?= number_format($firstVariant['price'], 0) ?></del>
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

    <!-- SERVICES SECTION -->
    <div>
      <div class="container">
        <div class="service-box">
          <div class="service">
            <h2 class="title">Our Services</h2>
            <div class="service-container">
              <a href="#" class="service-item">
                <div class="service-icon">
                  <ion-icon name="boat-outline"></ion-icon>
                </div>
                <div class="service-content">
                  <h3 class="service-title">Islandwide Delivery</h3>
                  <p class="service-desc">No Delivery Chargers</p>
                </div>
              </a>

              <a href="#" class="service-item">
                <div class="service-icon">
                  <ion-icon name="rocket-outline"></ion-icon>
                </div>
                <div class="service-content">
                  <h3 class="service-title">Next Day delivery</h3>
                  <p class="service-desc">Only For Pre Orders</p>
                </div>
              </a>

              <a href="#" class="service-item">
                <div class="service-icon">
                  <ion-icon name="call-outline"></ion-icon>
                </div>
                <div class="service-content">
                  <h3 class="service-title">24h Online Support</h3>
                  <p class="service-desc">Hotline +94 7X XXX XXXX</p>
                </div>
              </a>

              <a href="#" class="service-item">
                <div class="service-icon">
                  <ion-icon name="arrow-undo-outline"></ion-icon>
                </div>
                <div class="service-content">
                  <h3 class="service-title">Return Policy</h3>
                  <p class="service-desc">Easy & Free Return</p>
                </div>
              </a>

              <a href="#" class="service-item">
                <div class="service-icon">
                  <ion-icon name="ticket-outline"></ion-icon>
                </div>
                <div class="service-content">
                  <h3 class="service-title">10% money back</h3>
                  <p class="service-desc">For Pre Orders</p>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <!-- custom js link -->
  <script src="assets/js/product.js"></script>
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