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


// Get search query and filters from GET parameters
$searchQuery = $_GET['search'] ?? '';
$categoryIds = isset($_GET['category']) ? explode(',', $_GET['category']) : [];
$brandIds = isset($_GET['brand']) ? explode(',', $_GET['brand']) : [];
$minPrice = $_GET['min_price'] ?? null;
$maxPrice = $_GET['max_price'] ?? null;

// Initialize products array
$products = [];

try {
    // Get max price in the shop
    $maxPriceQuery = "SELECT MAX(Price) as max_price FROM productvariant";
    $maxPriceResult = $conn->query($maxPriceQuery);
    $maxPriceValue = $maxPriceResult->fetch_assoc()['max_price'] ?? 500000;

    // Base query
    $query = "SELECT 
        p.ProductID, p.Name, p.Description, p.ImagePath1, p.ImagePath2, p.CreatedAt,
        b.BrandID, b.BrandName, 
        c.CategoryID, c.CategoryName,
        pv.VariantID, pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice, pv.StockQuantity,
        pr.DiscountPercent, pr.OfferEndDate,
        COUNT(oi.OrderItemID) as order_count
    FROM product p
    JOIN brand b ON p.BrandID = b.BrandID
    JOIN category c ON p.CategoryID = c.CategoryID
    JOIN productvariant pv ON p.ProductID = pv.ProductID
    LEFT JOIN promotion pr ON pv.VariantID = pr.VariantID
    LEFT JOIN orderitem oi ON pv.VariantID = oi.VariantID";
    
    // Conditions array
    $conditions = [];
    $params = [];
    $types = '';
    
    // Add search condition
    if (!empty($searchQuery)) {
        $conditions[] = "(p.Name LIKE ? OR p.Description LIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
        $types .= 'ss';
    }
    
    // Add category filter
    if (!empty($categoryIds)) {
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $conditions[] = "p.CategoryID IN ($placeholders)";
        $params = array_merge($params, $categoryIds);
        $types .= str_repeat('i', count($categoryIds));
    }
    
    // Add brand filter
    if (!empty($brandIds)) {
        $placeholders = implode(',', array_fill(0, count($brandIds), '?'));
        $conditions[] = "p.BrandID IN ($placeholders)";
        $params = array_merge($params, $brandIds);
        $types .= str_repeat('i', count($brandIds));
    }
    
    // Add price range filter
    if ($minPrice !== null && $maxPrice !== null) {
        $conditions[] = "(pv.DiscountedPrice IS NOT NULL AND pv.DiscountedPrice BETWEEN ? AND ? OR 
                         pv.DiscountedPrice IS NULL AND pv.Price BETWEEN ? AND ?)";
        $params[] = $minPrice;
        $params[] = $maxPrice;
        $params[] = $minPrice;
        $params[] = $maxPrice;
        $types .= 'dddd';
    }
    
    // Combine conditions
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Group and order
    $query .= " GROUP BY p.ProductID, pv.VariantID
               ORDER BY p.CreatedAt DESC";
    
    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Process results
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $productId = $row['ProductID'];
            
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $row['ProductID'],
                    'name' => $row['Name'],
                    'brand_id' => $row['BrandID'],
                    'brand' => $row['BrandName'],
                    'category_id' => $row['CategoryID'],
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

// Get all brands for filters
$brands = [];
$brandQuery = "SELECT b.BrandID, b.BrandName, 
              COUNT(p.ProductID) as product_count
              FROM brand b
              LEFT JOIN product p ON b.BrandID = p.BrandID
              GROUP BY b.BrandID, b.BrandName
              ORDER BY b.BrandName";
$brandResult = $conn->query($brandQuery);
if ($brandResult) {
    while ($row = $brandResult->fetch_assoc()) {
        $brands[] = $row;
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
  <title>Search - PHONE MART</title>
  <link rel="icon" href="assets/images/logo/favicon.ico">
  <link rel="stylesheet" href="assets/css/search.css">
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
        <!-- SIDEBAR -->
        <div class="sidebar has-scrollbar" data-mobile-menu>
          <div class="sidebar-category">
            <div class="sidebar-top">
              <h2 class="sidebar-title">Filters</h2>
              <button class="sidebar-close-btn" data-mobile-menu-close-btn>
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>
            
            <!-- Price Range Filter -->
            <div class="filter-section">
              <h3 class="filter-title">Price Range</h3>
              <div style="margin-left: 15px;">
                <div class="values">
                  <span id="range3" style="margin-left: 5px;">LKR <?= number_format($minPrice ?? 0, 0) ?></span>
                  <span id="range4">LKR <?= number_format($maxPrice ?? $maxPriceValue, 0) ?></span>
                </div>
                <div class="slider-container">
                  <input type="range" min="0" max="<?= $maxPriceValue ?>" value="<?= $minPrice ?? 0 ?>" id="slider-3" oninput="slideThree()">
                  <input type="range" min="0" max="<?= $maxPriceValue ?>" value="<?= $maxPrice ?? $maxPriceValue ?>" id="slider-4" oninput="slideFour()">
                  <div class="slider-track-2"></div>
                </div>
              </div>
            </div>
            
            <!-- Categories Filter -->
            <div class="filter-section">
              <h3 class="filter-title">Categories</h3>
              <div class="filter-options">
                <?php foreach ($categories as $category): ?>
                <label>
                  <?= htmlspecialchars($category['CategoryName']) ?> (<?= $category['product_count'] ?? 0 ?>)
                  <input type="checkbox" name="category" value="<?= $category['CategoryID'] ?>"
                    <?= in_array($category['CategoryID'], $categoryIds) ? 'checked' : '' ?>>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            
            <!-- Brands Filter -->
            <div class="filter-section">
              <h3 class="filter-title">Brands</h3>
              <div class="filter-options">
                <?php foreach ($brands as $brand): ?>
                <label>
                  <?= htmlspecialchars($brand['BrandName']) ?> (<?= $brand['product_count'] ?? 0 ?>)
                  <input type="checkbox" name="brand" value="<?= $brand['BrandID'] ?>"
                    <?= in_array($brand['BrandID'], $brandIds) ? 'checked' : '' ?>>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            
            <!-- Clear Filters Button -->
            <div class="filter-section">
              <button class="clear-filters-btn" onclick="clearFilters()">Clear Filters</button>
            </div>
          </div>
        </div>

        <!-- PRODUCT CONTENT -->
        <div class="product-box">
          <div class="product-main">
            <h2 class="title">Search Results</h2>
            
            <?php if (empty($products)): ?>
              <p>No products found matching your criteria.</p>
            <?php else: ?>
              <div class="product-grid">
                <?php foreach ($products as $product): 
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
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <!-- custom js link -->
  <script src="assets/js/search.js"></script>
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

  <script>
    
    // Initialize slider values from URL parameters
  document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const minPrice = urlParams.get('min_price') || 0;
      const maxPrice = urlParams.get('max_price') || <?= $maxPriceValue ?>;
      
      const slider1 = document.getElementById('slider-1');
      const slider2 = document.getElementById('slider-2');
      const range1 = document.getElementById('range1');
      const range2 = document.getElementById('range2');
      
      if (slider1 && slider2 && range1 && range2) {
          slider1.value = minPrice;
          slider2.value = maxPrice;
          range1.textContent = 'LKR ' + parseInt(minPrice).toLocaleString();
          range2.textContent = 'LKR ' + parseInt(maxPrice).toLocaleString();
          
          // Initialize slider track color
          const minValue = parseInt(slider1.min || 0);
          const maxValue = parseInt(slider1.max || <?= $maxPriceValue ?>);
          const percent1 = ((slider1.value - minValue) / (maxValue - minValue)) * 100;
          const percent2 = ((slider2.value - minValue) / (maxValue - minValue)) * 100;
          const sliderTrack = document.querySelector('.slider-track');
          if (sliderTrack) {
              sliderTrack.style.background = `linear-gradient(to right, #dadae5 ${percent1}%, #3264fe ${percent1}%, #3264fe ${percent2}%, #dadae5 ${percent2}%)`;
          }
      }
      
      // Initialize checkboxes from URL parameters
      const categoryParam = urlParams.get('category');
      if (categoryParam) {
          const selectedCategories = categoryParam.split(',');
          document.querySelectorAll('input[name="category"]').forEach(checkbox => {
              checkbox.checked = selectedCategories.includes(checkbox.value);
          });
      }
      
      const brandParam = urlParams.get('brand');
      if (brandParam) {
          const selectedBrands = brandParam.split(',');
          document.querySelectorAll('input[name="brand"]').forEach(checkbox => {
              checkbox.checked = selectedBrands.includes(checkbox.value);
          });
      }
  });
  </script>
</body>
</html>