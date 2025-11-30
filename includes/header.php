<?php
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
// At the top of header.php
if (!isset($categories)) {
    try {
        global $conn; // Make sure connection is available
        $categoryQuery = "SELECT c.CategoryID, c.CategoryName, 
                         COUNT(p.ProductID) as product_count 
                         FROM category c
                         LEFT JOIN product p ON c.CategoryID = p.CategoryID
                         GROUP BY c.CategoryID, c.CategoryName";
        $categoryResult = $conn->query($categoryQuery);
        $categories = $categoryResult ? $categoryResult->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        error_log("Error fetching categories in header: " . $e->getMessage());
        $categories = [];
    }
}

// Determine current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header>
  <div class="header-main">
    <div class="container">
      <a href="index.php" class="header-logo">
        <img src="./assets/images/logo/logo.svg" alt="Phone Mart logo" width="120" height="36">
      </a>

      <div class="header-search-container">
        <form action="search.php" method="GET">
          <input  type="search" name="search" class="search-field" placeholder="Enter your product name..."value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
          <button type="submit" class="search-btn">
            <ion-icon name="search-outline"></ion-icon>
          </button>
        </form>
      </div>

      <div class="header-user-actions">
        <?php if (isLoggedIn()): ?>
          <a href="profile.php" class="action-btn">
            <ion-icon name="person-outline"></ion-icon>
          </a>
        <?php else: ?>
          <a href="login.php" class="action-btn">
            <ion-icon name="person-outline"></ion-icon>
          </a>
        <?php endif; ?>

        <a href="wishlist.php" class="action-btn">
          <ion-icon name="heart-outline"></ion-icon>
          <span class="count"><?= $wishlistCount ?></span>
        </a>

        <a href="cart.php" class="action-btn">
          <ion-icon name="bag-handle-outline"></ion-icon>
          <span class="count"><?= $cartCount ?></span>
        </a>
      </div>
    </div>
  </div>

  <nav class="desktop-navigation-menu">
    <div class="container">
      <ul class="desktop-menu-category-list">
        <li class="menu-category">
          <a href="index.php" class="menu-title">Home</a>
        </li>
        
        <li class="menu-category">
          <a href="#" class="menu-title">Categories</a>
          <div class="dropdown-panel">
            <?php 
            // Group categories into columns
            $chunkedCategories = array_chunk($categories, ceil(count($categories) / 4));
            foreach ($chunkedCategories as $categoryGroup): 
            ?>
            <ul class="dropdown-panel-list">
              <?php foreach ($categoryGroup as $category): ?>
              <li class="menu-title">
                <a href="search.php?category=<?= $category['CategoryID'] ?>"><?= htmlspecialchars($category['CategoryName']) ?></a>
              </li>
              <?php 
              // Get top 5 brands for this category
              $topbrandsQuery = "SELECT b.BrandID, b.BrandName 
                              FROM brand b
                              JOIN product p ON b.BrandID = p.BrandID
                              WHERE p.CategoryID = ?
                              GROUP BY b.BrandID, b.BrandName
                              LIMIT 5";
              $stmt = $conn->prepare($topbrandsQuery);
              $stmt->bind_param("i", $category['CategoryID']);
              $stmt->execute();
              $topbrands = $stmt->get_result();
              
              if ($topbrands && $topbrands->num_rows > 0) {
                  while ($topbrand = $topbrands->fetch_assoc()) {
                      echo '<li class="panel-list-item">
                              <a href="search.php?category='.$category['CategoryID'].'&brand='.$topbrand['BrandID'].'">'.htmlspecialchars($topbrand['BrandName']).'</a>
                            </li>';
                  }
              }
              ?>
              <?php endforeach; ?>
            </ul>
            <?php endforeach; ?>
          </div>
        </li>

        <?php foreach ($categories as $category): ?>
        <li class="menu-category">
          <a href="search.php?category=<?= $category['CategoryID'] ?>" class="menu-title"><?= htmlspecialchars($category['CategoryName']) ?></a>
          <ul class="dropdown-list">
            <?php 
            // Get top 6 brands for this category
            $topbrandsQuery = "SELECT b.BrandID, b.BrandName 
                            FROM brand b
                            JOIN product p ON b.BrandID = p.BrandID
                            WHERE p.CategoryID = ?
                            GROUP BY b.BrandID, b.BrandName
                            LIMIT 6";
            $stmt = $conn->prepare($topbrandsQuery);
            $stmt->bind_param("i", $category['CategoryID']);
            $stmt->execute();
            $topbrands = $stmt->get_result();
            
            if ($topbrands && $topbrands->num_rows > 0) {
                while ($topbrand = $topbrands->fetch_assoc()) {
                    echo '<li class="dropdown-item">
                            <a href="search.php?category='.$category['CategoryID'].'&brand='.$topbrand['BrandID'].'">'.htmlspecialchars($topbrand['BrandName']).'</a>
                          </li>';
                }
            } else {
                echo '<li class="dropdown-item"><a href="#">No brands found</a></li>';
            }
            ?>
          </ul>
        </li>
        <?php endforeach; ?>

        <li class="menu-category">
          <a href="contact.php" class="menu-title">Contact</a>
        </li>
        <li class="menu-category">
          <a href="about.php" class="menu-title">About</a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- Mobile Bottom Navigation -->
  <div class="mobile-bottom-navigation">
    <button class="action-btn" data-mobile-menu-open-btn data-target="#main-mobile-menu">
      <ion-icon name="menu-outline"></ion-icon>
    </button>

    <a href="cart.php" class="action-btn">
      <ion-icon name="bag-handle-outline"></ion-icon>
      <span class="count"><?= $cartCount ?></span>
    </a>

    <?php if (isLoggedIn()): ?>
      <a href="profile.php" class="action-btn">
        <ion-icon name="person-outline"></ion-icon>
      </a>
    <?php else: ?>
      <a href="login.php" class="action-btn">
        <ion-icon name="person-outline"></ion-icon>
      </a>
    <?php endif; ?>

    <a href="wishlist.php" class="action-btn">
      <ion-icon name="heart-outline"></ion-icon>
      <span class="count"><?= $wishlistCount ?></span>
    </a>

    <?php if ($currentPage === 'index.php'): ?>
      <button class="action-btn" data-mobile-menu-open-btn data-target="#categories-mobile-menu">
        <ion-icon name="grid-outline"></ion-icon>
      </button>
    <?php elseif ($currentPage === 'search.php'): ?>
      <button class="action-btn" data-mobile-menu-open-btn data-target="#filters-mobile-menu">
        <ion-icon name="filter-outline"></ion-icon>
      </button>
    <?php else: ?>
      <a href="search.php" class="action-btn">
        <ion-icon name="search-outline"></ion-icon>
      </a>
    <?php endif; ?>
  </div>

  <!-- Main Mobile Menu -->
  <nav class="mobile-navigation-menu has-scrollbar" id="main-mobile-menu" data-mobile-menu>
    <div class="menu-top">
      <h2 class="menu-title">Menu</h2>
      <button class="menu-close-btn" data-mobile-menu-close-btn>
        <ion-icon name="close-outline"></ion-icon>
      </button>
    </div>

    <ul class="mobile-menu-category-list">
      <li class="menu-category">
        <a href="index.php" class="menu-title">Home</a>
      </li>
      
      <?php if ($currentPage === 'index.php'): ?>
        <li class="menu-category">
          <button class="accordion-menu" data-accordion-btn>
            <p class="menu-title">Categories</p>
            <div>
              <ion-icon name="add-outline" class="add-icon"></ion-icon>
              <ion-icon name="remove-outline" class="remove-icon"></ion-icon>
            </div>
          </button>
          
          <ul class="submenu-category-list">
            <?php foreach ($categories as $category): ?>
              <li class="submenu-category">
                <a href="search.php?category=<?= $category['CategoryID'] ?>" class="submenu-title">
                  <?= htmlspecialchars($category['CategoryName']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endif; ?>
      
      <li class="menu-category">
        <a href="contact.php" class="menu-title">Contact</a>
      </li>
      <li class="menu-category">
        <a href="about.php" class="menu-title">About</a>
      </li>
    </ul>
  </nav>

  <!-- Categories Mobile Menu (only for index page) -->
  <?php if ($currentPage === 'index.php'): ?>
    <nav class="mobile-navigation-menu has-scrollbar" id="categories-mobile-menu" data-mobile-menu>
      <div class="menu-top">
        <h2 class="menu-title">Categories</h2>
        <button class="menu-close-btn" data-mobile-menu-close-btn>
          <ion-icon name="close-outline"></ion-icon>
        </button>
      </div>

      <ul class="mobile-menu-category-list">
        <?php foreach ($categories as $category): ?>
        <li class="menu-category">
          <button class="accordion-menu" data-accordion-btn>
            <p class="menu-title"><?= htmlspecialchars($category['CategoryName']) ?></p>
            <div>
              <ion-icon name="add-outline" class="add-icon"></ion-icon>
              <ion-icon name="remove-outline" class="remove-icon"></ion-icon>
            </div>
          </button>
          
          <ul class="submenu-category-list">
            <?php
            $menubrandsQuery = "SELECT b.BrandID, b.BrandName 
                            FROM brand b
                            JOIN product p ON b.BrandID = p.BrandID
                            WHERE p.CategoryID = ?
                            GROUP BY b.BrandID
                            LIMIT 6";
            $stmt = $conn->prepare($menubrandsQuery);
            $stmt->bind_param("i", $category['CategoryID']);
            $stmt->execute();
            $menubrands = $stmt->get_result();
            
            while ($menubrand = $menubrands->fetch_assoc()): ?>
            <li class="submenu-category">
              <a href="search.php?category=<?= $category['CategoryID'] ?>&brand=<?= $menubrand['BrandID'] ?>" 
                 class="submenu-title">
                <?= htmlspecialchars($menubrand['BrandName']) ?>
              </a>
            </li>
            <?php endwhile; ?>
          </ul>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  <?php endif; ?>

  <!-- Filters Mobile Menu (only for search page) -->
  <?php if ($currentPage === 'search.php'): ?>
    <nav class="mobile-navigation-menu has-scrollbar" id="filters-mobile-menu" data-mobile-menu>
      <div class="menu-top">
        <h2 class="menu-title">Filters</h2>
        <button class="menu-close-btn" data-mobile-menu-close-btn>
          <ion-icon name="close-outline"></ion-icon>
        </button>
      </div>

      <div class="sidebar-category">
        <!-- Price Range Filter -->
        <div class="filter-section">
          <h3 class="filter-title">Price Range</h3>
          <div style="margin-left: 15px;">
            <div class="values">
              <span id="range1" style="margin-left: 5px;">LKR <?= number_format($minPrice ?? 0, 0) ?></span>
              <span id="range2">LKR <?= number_format($maxPrice ?? $maxPriceValue, 0) ?></span>
            </div>
            <div class="slider-container">
              <input type="range" min="0" max="<?= $maxPriceValue ?>" value="<?= $minPrice ?? 0 ?>" id="slider-1" oninput="slideOne()">
              <input type="range" min="0" max="<?= $maxPriceValue ?>" value="<?= $maxPrice ?? $maxPriceValue ?>" id="slider-2" oninput="slideTwo()">
              <div class="slider-track-1"></div>
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
    </nav>
  <?php endif; ?>

  <!-- Overlay -->
  <div class="overlay" data-overlay></div>
</header>