<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_brand'])) {
        $brandName = trim($_POST['brand_name']);
        if (!empty($brandName)) {
            $stmt = $conn->prepare("INSERT INTO brand (BrandName) VALUES (?)");
            $stmt->bind_param("s", $brandName);
            $stmt->execute();
            $_SESSION['message'] = "Brand added successfully!";
        }
    } elseif (isset($_POST['update_brand'])) {
        $brandId = intval($_POST['brand_id']);
        $brandName = trim($_POST['brand_name']);
        if (!empty($brandName) && $brandId > 0) {
            $stmt = $conn->prepare("UPDATE brand SET BrandName = ? WHERE BrandID = ?");
            $stmt->bind_param("si", $brandName, $brandId);
            $stmt->execute();
            $_SESSION['message'] = "Brand updated successfully!";
        }
    } elseif (isset($_POST['delete_brand'])) {
        $brandId = intval($_POST['brand_id']);
        if ($brandId > 0) {
            // First check if there are products using this brand
            $stmt = $conn->prepare("SELECT COUNT(*) FROM product WHERE BrandID = ?");
            $stmt->bind_param("i", $brandId);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            
            if ($count == 0) {
                $stmt = $conn->prepare("DELETE FROM brand WHERE BrandID = ?");
                $stmt->bind_param("i", $brandId);
                $stmt->execute();
                $_SESSION['message'] = "Brand deleted successfully!";
            } else {
                if (isset($_POST['delete_action'])) {
                    switch ($_POST['delete_action']) {
                        case 'move':
                            $newBrandId = intval($_POST['new_brand_id']);
                            if ($newBrandId > 0 && $newBrandId != $brandId) {
                                // Move products to new brand
                                $stmt = $conn->prepare("UPDATE product SET BrandID = ? WHERE BrandID = ?");
                                $stmt->bind_param("ii", $newBrandId, $brandId);
                                $stmt->execute();
                                
                                // Then delete the brand
                                $stmt = $conn->prepare("DELETE FROM brand WHERE BrandID = ?");
                                $stmt->bind_param("i", $brandId);
                                $stmt->execute();
                                $_SESSION['message'] = "Brand deleted and products moved successfully!";
                            }
                            break;
                        case 'delete_all':
                            // First delete all products with this brand
                            $stmt = $conn->prepare("DELETE FROM product WHERE BrandID = ?");
                            $stmt->bind_param("i", $brandId);
                            $stmt->execute();
                            
                            // Then delete the brand
                            $stmt = $conn->prepare("DELETE FROM brand WHERE BrandID = ?");
                            $stmt->bind_param("i", $brandId);
                            $stmt->execute();
                            $_SESSION['message'] = "Brand and all associated products deleted successfully!";
                            break;
                        default:
                            $_SESSION['error'] = "Deletion cancelled - there are products associated with this brand!";
                            break;
                    }
                } else {
                    $_SESSION['error'] = "Cannot delete brand - there are products associated with it!";
                }
            }
        }
    }
    header("Location: brands.php");
    exit();
}

// Fetch all brands with product counts
$brands = $conn->query("
    SELECT b.BrandID, b.BrandName, COUNT(p.ProductID) as ProductCount 
    FROM brand b
    LEFT JOIN product p ON b.BrandID = p.BrandID
    GROUP BY b.BrandID
    ORDER BY b.BrandName
");

// Fetch all brands for the delete modal dropdown (excluding current brand)
$allBrands = $conn->query("SELECT BrandID, BrandName FROM brand ORDER BY BrandName");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - Brand Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/brands.css">
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
          <li><a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a></li>
          <li><a href="categories.php"><i class="fas fa-list"></i> <span>Categories</span></a></li>
          <li><a href="brands.php" class="active"><i class="fas fa-tags"></i> <span>Brands</span></a></li>
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
        <h1>Brand Management</h1>
        <button id="addBrandBtn" class="btn btn-primary add-btn">
          <i class="fas fa-plus"></i> Add Brand
        </button>
      </div>

      <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success">
          <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h2>All Brands</h2>
        </div>
        
        <div class="categories-grid" id="brandsContainer">
          <?php if ($brands->num_rows > 0): ?>
            <?php while ($brand = $brands->fetch_assoc()): ?>
              <div class="category-card">
                <div class="category-info">
                  <div class="category-name"><?php echo htmlspecialchars($brand['BrandName']); ?></div>
                  <div class="product-count"><?php echo $brand['ProductCount']; ?> product(s)</div>
                </div>
                <div class="category-actions">
                  <button class="btn btn-sm btn-primary btn-edit" data-id="<?php echo $brand['BrandID']; ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger btn-delete" 
                          data-id="<?php echo $brand['BrandID']; ?>"
                          data-count="<?php echo $brand['ProductCount']; ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="no-brands">No brands found. Add your first brand!</div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <!-- Add Brand Modal -->
  <div class="modal" id="addBrandModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Add New Brand</h4>
        <span class="close-modal">&times;</span>
      </div>
      <form id="addBrandForm" method="POST">
        <div class="modal-body">
          <div class="form-group">
            <label for="brandName">Brand Name*</label>
            <input type="text" id="brandName" name="brand_name" class="form-control" placeholder="Enter brand name" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" class="btn btn-primary" name="add_brand">Save Brand</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Brand Modal -->
  <div class="modal" id="editBrandModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Edit Brand</h4>
        <span class="close-modal">&times;</span>
      </div>
      <form id="editBrandForm" method="POST">
        <input type="hidden" name="brand_id" id="editBrandId">
        <div class="modal-body">
          <div class="form-group">
            <label for="editBrandName">Brand Name*</label>
            <input type="text" id="editBrandName" name="brand_name" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" class="btn btn-primary" name="update_brand">Update Brand</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteBrandModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Confirm Deletion</h4>
        <span class="close-modal">&times;</span>
      </div>
      <form id="deleteBrandForm" method="POST">
        <input type="hidden" name="brand_id" id="deleteBrandId">
        <input type="hidden" name="delete_brand" value="1">
        <div class="modal-body">
          <p id="deleteWarningText">Are you sure you want to delete this brand?</p>
          <div id="deleteOptions" class="delete-options" style="display: none;">
            <p><strong>This brand contains <span id="productCount">0</span> product(s).</strong></p>
            <div class="form-group">
              <label>Choose action:</label>
              <div class="radio-group">
                <label>
                  <input type="radio" name="delete_action" value="prevent" checked>
                  Cancel deletion (keep brand)
                </label>
                <label>
                  <input type="radio" name="delete_action" value="move">
                  Move products to another brand:
                </label>
                <select name="new_brand_id" class="form-control" style="margin-top: 5px; margin-bottom: 10px;" disabled>
                  <?php while($brand = $allBrands->fetch_assoc()): ?>
                    <option value="<?= $brand['BrandID'] ?>"><?= htmlspecialchars($brand['BrandName']) ?></option>
                  <?php endwhile; ?>
                </select>
                <label>
                  <input type="radio" name="delete_action" value="delete_all">
                  Delete all products in this brand (irreversible)
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Confirm Action</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/brands.js"></script>
</body>
</html>