<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $categoryName = trim($_POST['category_name']);
        if (!empty($categoryName)) {
            $stmt = $conn->prepare("INSERT INTO category (CategoryName) VALUES (?)");
            $stmt->bind_param("s", $categoryName);
            $stmt->execute();
            $_SESSION['message'] = "Category added successfully!";
        }
    } elseif (isset($_POST['update_category'])) {
        $categoryId = intval($_POST['category_id']);
        $categoryName = trim($_POST['category_name']);
        if (!empty($categoryName) && $categoryId > 0) {
            $stmt = $conn->prepare("UPDATE category SET CategoryName = ? WHERE CategoryID = ?");
            $stmt->bind_param("si", $categoryName, $categoryId);
            $stmt->execute();
            $_SESSION['message'] = "Category updated successfully!";
        }
    } elseif (isset($_POST['delete_category'])) {
        $categoryId = intval($_POST['category_id']);
        if ($categoryId > 0) {
            // First check if there are products using this category
            $stmt = $conn->prepare("SELECT COUNT(*) FROM product WHERE CategoryID = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            
            if ($count == 0) {
                $stmt = $conn->prepare("DELETE FROM category WHERE CategoryID = ?");
                $stmt->bind_param("i", $categoryId);
                $stmt->execute();
                $_SESSION['message'] = "Category deleted successfully!";
            } else {
                $_SESSION['error'] = "Cannot delete category - there are products associated with it!";
            }
        }
    }
    header("Location: categories.php");
    exit();
}

// Fetch all categories with product counts
$categories = $conn->query("
    SELECT c.CategoryID, c.CategoryName, COUNT(p.ProductID) as ProductCount 
    FROM category c
    LEFT JOIN product p ON c.CategoryID = p.CategoryID
    GROUP BY c.CategoryID
    ORDER BY c.CategoryName
");

// Add this new query to fetch all categories for the delete modal dropdown
$allCategories = $conn->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - Category Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/categories.css">
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
          <li><a href="categories.php" class="active"><i class="fas fa-list"></i> <span>Categories</span></a></li>
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
        <h1>Category Management</h1>
        <button id="addCategoryBtn" class="btn btn-primary add-btn">
          <i class="fas fa-plus"></i> Add Category
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
          <h2>All Categories</h2>
        </div>
        
        <div class="categories-grid" id="categoriesContainer">
          <?php if ($categories->num_rows > 0): ?>
            <?php while ($category = $categories->fetch_assoc()): ?>
              <div class="category-card">
                <div class="category-info">
                  <div class="category-name"><?php echo htmlspecialchars($category['CategoryName']); ?></div>
                  <div class="product-count"><?php echo $category['ProductCount']; ?> product(s)</div>
                </div>
                <div class="category-actions">
                  <button class="btn btn-sm btn-primary btn-edit" data-id="<?php echo $category['CategoryID']; ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger btn-delete" 
                          data-id="<?php echo $category['CategoryID']; ?>"
                          data-count="<?php echo $category['ProductCount']; ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="no-categories">No categories found. Add your first category!</div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <!-- Add Category Modal -->
  <div class="modal" id="addCategoryModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Add New Category</h4>
        <span class="close-modal">&times;</span>
      </div>
      <form id="addCategoryForm" method="POST">
        <div class="modal-body">
          <div class="form-group">
            <label for="categoryName">Category Name*</label>
            <input type="text" id="categoryName" name="category_name" class="form-control" placeholder="Enter category name" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" class="btn btn-primary" name="add_category">Save Category</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Category Modal -->
  <div class="modal" id="editCategoryModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Edit Category</h4>
        <span class="close-modal">&times;</span>
      </div>
      <form id="editCategoryForm" method="POST">
        <input type="hidden" name="category_id" id="editCategoryId">
        <div class="modal-body">
          <div class="form-group">
            <label for="editCategoryName">Category Name*</label>
            <input type="text" id="editCategoryName" name="category_name" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" class="btn btn-primary" name="update_category">Update Category</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteCategoryModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Confirm Deletion</h4>
        <span class="close-modal">&times;</span>
      </div>
      <form id="deleteCategoryForm" method="POST">
        <input type="hidden" name="category_id" id="deleteCategoryId">
        <input type="hidden" name="delete_category" value="1">
        <div class="modal-body">
          <p id="deleteWarningText">Are you sure you want to delete this category?</p>
          <div id="deleteOptions" class="delete-options" style="display: none;">
            <p><strong>This category contains <span id="productCount">0</span> product(s).</strong></p>
            <div class="form-group">
              <label>Choose action:</label>
              <div class="radio-group">
                <label>
                  <input type="radio" name="delete_action" value="prevent" checked>
                  Cancel deletion (keep category)
                </label>
                <label>
                  <input type="radio" name="delete_action" value="move">
                  Move products to another category:
                </label>
                <select name="new_category_id" class="form-control" style="margin-top: 5px; margin-bottom: 10px;" disabled>
                  <?php while($cat = $allCategories->fetch_assoc()): ?>
                    <option value="<?= $cat['CategoryID'] ?>"><?= htmlspecialchars($cat['CategoryName']) ?></option>
                  <?php endwhile; ?>
                </select>
                <label>
                  <input type="radio" name="delete_action" value="delete_all">
                  Delete all products in this category (irreversible)
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

  <script src="assets/js/categories.js"></script>
</body>
</html>