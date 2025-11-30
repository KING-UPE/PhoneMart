<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Pagination settings
$itemsPerPage = 8; // Number of products per page
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Check for success message
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        echo '<script>alert("Product saved successfully!");</script>';
    } elseif ($_GET['success'] == 2) {
        echo '<script>alert("Product deleted successfully!");</script>';
    } elseif ($_GET['success'] == 3) {
        echo '<script>alert("Offer added successfully!");</script>';
    } elseif ($_GET['success'] == 4) {
        echo '<script>alert("Error: ' . htmlspecialchars($_GET['message']) . '");</script>';
    }
}

// Fetch total number of products for pagination
$totalProductsQuery = "SELECT COUNT(*) as total FROM product";
$totalProductsResult = $conn->query($totalProductsQuery);
$totalProducts = $totalProductsResult->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $itemsPerPage);

// Fetch products with pagination
$productsQuery = "SELECT p.*, b.BrandName, c.CategoryName, 
                 MIN(pv.DiscountedPrice) AS min_price,
                 SUM(pv.StockQuantity) AS total_stock
                 FROM product p
                 JOIN brand b ON p.BrandID = b.BrandID
                 JOIN category c ON p.CategoryID = c.CategoryID
                 LEFT JOIN productvariant pv ON p.ProductID = pv.ProductID
                 GROUP BY p.ProductID
                 ORDER BY p.Name ASC
                 LIMIT $itemsPerPage OFFSET $offset";
$productsResult = $conn->query($productsQuery);

// Fetch top selling products (unchanged)
$topProductsQuery = "SELECT p.ProductID, p.Name, b.BrandName, 
                    SUM(oi.Quantity) AS total_sold, 
                    p.ImagePath1
                    FROM orderitem oi
                    JOIN productvariant pv ON oi.VariantID = pv.VariantID
                    JOIN product p ON pv.ProductID = p.ProductID
                    JOIN brand b ON p.BrandID = b.BrandID
                    GROUP BY p.ProductID
                    ORDER BY total_sold DESC
                    LIMIT 5";
$topProductsResult = $conn->query($topProductsQuery);

// Fetch all brands and categories for filters
$brands = $conn->query("SELECT * FROM brand ORDER BY BrandName");
$categories = $conn->query("SELECT * FROM category ORDER BY CategoryName");

// Fetch product variants for the offer dropdown
$variantsQuery = "SELECT pv.VariantID, p.Name, pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice
                 FROM productvariant pv
                 JOIN product p ON pv.ProductID = p.ProductID
                 ORDER BY p.Name, pv.Color, pv.Storage";
$variantsResult = $conn->query($variantsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - Product Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/products.css">
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h3>Phone Mart Admin</h3>
      </div>
      <nav class="sidebar-menu">
        <ul>
          <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
          <li><a href="products.php" class="active"><i class="fas fa-box"></i> <span>Products</span></a></li>
          <li><a href="categories.php"><i class="fas fa-list"></i> <span>Categories</span></a></li>
          <li><a href="brands.php"><i class="fas fa-tags"></i> <span>Brands</span></a></li>
          <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
          <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
          <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
          <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h1>Product Management</h1>
        <div class="header-actions">
          <a href="addeditItem.php" class="btn btn-primary add-product-btn">
            <i class="fas fa-plus"></i> <span>Add Product</span>
          </a>
          <button class="btn add-offer-btn" id="openOfferModalBtn">
            <i class="fas fa-tag"></i> <span>Add Offer</span>
          </button>
        </div>
      </div>

      <div class="content-grid">
        <!-- Main Products Table -->
        <div class="table-card">
          <div class="card-header">
            <h2>All Products</h2>
            <div class="table-controls">
              <select class="form-control-sm" id="brandFilter">
                <option value="">All Brands</option>
                <?php while ($brand = $brands->fetch_assoc()): ?>
                  <option value="<?= htmlspecialchars($brand['BrandName']) ?>">
                    <?= htmlspecialchars($brand['BrandName']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <select class="form-control-sm" id="categoryFilter">
                <option value="">All Categories</option>
                <?php while ($category = $categories->fetch_assoc()): ?>
                  <option value="<?= htmlspecialchars($category['CategoryName']) ?>">
                    <?= htmlspecialchars($category['CategoryName']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          <div class="table-responsive">
            <table class="products-table" id="productsTable">
              <thead>
                <tr>
                  <th class="product-col">Product</th>
                  <th class="brand-col">Brand</th>
                  <th class="category-col">Category</th>
                  <th class="price-col">Price (LKR)</th>
                  <th class="stock-col">Stock</th>
                  <th class="status-col">Status</th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($productsResult->num_rows > 0): ?>
                  <?php while ($product = $productsResult->fetch_assoc()): 
                    $status = ($product['total_stock'] > 10) ? 'In Stock' : 
                             (($product['total_stock'] > 0) ? 'Low Stock' : 'Out of Stock');
                    $statusClass = ($product['total_stock'] > 10) ? 'status-active' : 
                                  (($product['total_stock'] > 0) ? 'status-warning' : 'status-inactive');
                  ?>
                    <tr>
                      <td>
                        <div class="product-cell">
                          <?php if ($product['ImagePath1']): ?>
                            <img src="../../<?= htmlspecialchars($product['ImagePath1']) ?>" alt="<?= htmlspecialchars($product['Name']) ?>" class="product-thumbnail">
                          <?php endif; ?>
                          <div class="product-info">
                            <h4><?= htmlspecialchars($product['Name']) ?></h4>
                            <p><?= htmlspecialchars($product['Model'] ?? '') ?></p>
                          </div>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($product['BrandName']) ?></td>
                      <td><?= htmlspecialchars($product['CategoryName']) ?></td>
                      <td><?= number_format($product['min_price'], 0) ?></td>
                      <td><?= $product['total_stock'] ?? 0 ?></td>
                      <td><span class="status-badge <?= $statusClass ?>"><?= $status ?></span></td>
                      <td>
                        <div class="action-btns">
                          <button onclick="location.href='addeditItem.php?edit=true&id=<?= $product['ProductID'] ?>'" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-sm btn-danger delete-product" data-id="<?= $product['ProductID'] ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center">No products found. <a href="addeditItem.php">Add your first product</a></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <br/>
          <div class="table-footer">
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=1" class="btn btn-sm" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?= $currentPage - 1 ?>" class="btn btn-sm" title="Previous">
                          <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="btn btn-sm disabled" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="btn btn-sm disabled" title="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                <?php endif; ?>

                <?php 
                // Show limited page numbers with ellipsis
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if ($startPage > 1): ?>
                    <span class="btn btn-sm disabled">...</span>
                <?php endif;
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i == $currentPage ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor;
                
                if ($endPage < $totalPages): ?>
                    <span class="btn btn-sm disabled">...</span>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>" class="btn btn-sm" title="Next">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="?page=<?= $totalPages ?>" class="btn btn-sm" title="Last Page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="btn btn-sm disabled" title="Next">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                    <span class="btn btn-sm disabled" title="Last Page">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="pagination-info">
                Showing <?= ($offset + 1) ?> to <?= min($offset + $itemsPerPage, $totalProducts) ?> of <?= $totalProducts ?> products
            </div>
        </div>
        </div>
        <br/><br/>
        <!-- Top Selling Products Sidebar -->
        <div class="top-products-card">
          <div class="card-header">
            <h2>Top Selling Products</h2>
          </div>
          <div class="top-products-list">
            <?php if ($topProductsResult->num_rows > 0): ?>
              <?php $rank = 1; ?>
              <?php while ($topProduct = $topProductsResult->fetch_assoc()): ?>
                <div class="top-product-item">
                  <div class="product-rank"><?= $rank++ ?></div>
                  <?php if ($topProduct['ImagePath1']): ?>
                    <img src="../../<?= htmlspecialchars($topProduct['ImagePath1']) ?>" alt="<?= htmlspecialchars($topProduct['Name']) ?>">
                  <?php else: ?>
                    <div class="no-image-placeholder">
                      <i class="fas fa-box-open"></i>
                    </div>
                  <?php endif; ?>
                  <div class="product-info">
                    <h4><?= htmlspecialchars($topProduct['Name']) ?></h4>
                    <p><?= htmlspecialchars($topProduct['BrandName']) ?></p>
                    <div class="product-stats">
                      <span><i class="fas fa-shopping-cart"></i> <?= $topProduct['total_sold'] ?> sold</span>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-chart-line"></i>
                <p>No sales data available yet</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Offer Modal -->
  <div class="modal" id="offerModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Add New Offer</h4>
      </div>
      <div class="modal-body">
        <form id="offerForm" action="../includes/save_offer.php" method="POST">
          <div class="form-group">
            <label for="offerVariantId">Select Product Variant</label>
            <select id="offerVariantId" name="variant_id" class="form-control" required>
              <option value="">Select a product variant</option>
              <?php 
              if ($variantsResult && $variantsResult->num_rows > 0) {
                while ($variant = $variantsResult->fetch_assoc()) {
                  echo '<option value="' . $variant['VariantID'] . '">' . 
                       htmlspecialchars($variant['Name']) . ' - ' . 
                       htmlspecialchars($variant['Color']) . ' - ' . 
                       htmlspecialchars($variant['Storage']) . ' - LKR ' . 
                       number_format($variant['Price'], 0) . '</option>';
                }
              }
              ?>
            </select>
          </div>
          <div class="form-group">
            <label for="offerDiscount">Discount Percentage</label>
            <div class="input-group">
              <input type="number" id="offerDiscount" name="discount" class="form-control" placeholder="1-100" min="1" max="100" required>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="form-group">
            <label for="offerEndDate">End Date</label>
            <input type="date" id="offerEndDate" name="end_date" class="form-control" required>
          </div>
          <!-- Include current page so we can return to it after saving -->
          <input type="hidden" name="return_page" value="<?= $currentPage ?>">
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Offer</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal">
    <div class="modal-content small">
      <div class="modal-header">
        <h4>Confirm Deletion</h4>
        <button type="button" class="close-modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this product? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
      </div>
    </div>
  </div>

  <script src="assets/js/products.js"></script>
</body>
</html>