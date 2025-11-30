<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Initialize variables
$product = null;
$isEditMode = false;
$pageTitle = "Add New Product";

// Check if we're in edit mode
if (isset($_GET['edit'])) {
    $isEditMode = true;
    $pageTitle = "Edit Product";
    
    // Get product ID from URL
    $productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($productId > 0) {
        // Fetch product from database
        $stmt = $conn->prepare("SELECT * FROM product WHERE ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product) {
            // Product not found, redirect back
            header("Location: products.php");
            exit();
        }
        
        // Fetch variants for this product
        $stmt = $conn->prepare("SELECT * FROM productvariant WHERE ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        // Invalid ID, redirect back
        header("Location: products.php");
        exit();
    }
}

// Fetch brands and categories for dropdowns
$brands = $conn->query("SELECT * FROM brand");
$categories = $conn->query("SELECT * FROM category");

// Function to get color hex code
function getColorHex($color) {
    $colors = [
        'blue' => '#4285f4',
        'green' => '#34a853',
        'black' => '#000000',
        'gold' => '#fbbc05',
        'red' => '#ea4335',
    ];
    return $colors[strtolower($color)] ?? '#cccccc';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - <?php echo $pageTitle; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/addedititem.css">
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
        <h1 id="pageTitle"><?php echo $pageTitle; ?></h1>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Product Information</h2>
        </div>
        <form id="productForm" action="../includes/save_product.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" id="editMode" name="editMode" value="<?php echo $isEditMode ? 'true' : 'false'; ?>">
          <input type="hidden" id="productId" name="productId" value="<?php echo $isEditMode ? $product['ProductID'] : ''; ?>">
          
          <div class="form-row">
            <div class="form-group">
              <label for="productName">Product Name*</label>
              <input type="text" id="productName" name="productName" class="form-control" 
                     placeholder="e.g. Samsung Galaxy S25 Ultra" required
                     value="<?php echo $isEditMode ? htmlspecialchars($product['Name']) : ''; ?>">
            </div>
            <div class="form-group">
              <label for="brand">Brand*</label>
              <select id="brand" name="brand" class="form-control" required>
                <option value="">Select Brand</option>
                <?php while ($brand = $brands->fetch_assoc()): ?>
                  <option value="<?php echo $brand['BrandID']; ?>"
                    <?php echo ($isEditMode && $product['BrandID'] == $brand['BrandID']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($brand['BrandName']); ?>
                  </option>
                <?php endwhile; ?>  
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="category">Category*</label>
              <select id="category" name="category" class="form-control" required>
                <option value="">Select Category</option>
                <?php while ($category = $categories->fetch_assoc()): ?>
                  <option value="<?php echo $category['CategoryID']; ?>"
                    <?php echo ($isEditMode && $product['CategoryID'] == $category['CategoryID']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['CategoryName']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="model">Model</label>
              <input type="text" id="model" name="model" class="form-control" 
                     placeholder="e.g. SM-S928B"
                     value="<?php echo $isEditMode ? htmlspecialchars($product['Model']) : ''; ?>">
            </div>
          </div>

          <div class="form-group">
            <label for="description">Description*</label>
            <textarea id="description" name="description" class="form-control" 
                      placeholder="Enter product description..." required><?php 
                echo $isEditMode ? htmlspecialchars($product['Description']) : ''; 
            ?></textarea>
          </div>

          <!-- Variant Section -->
          <div class="variant-section">
            <h3>
              Product Variants
              <button type="button" class="btn btn-sm btn-primary" id="addVariantBtn">
                <i class="fas fa-plus"></i> Add Variant
              </button>
            </h3>
            
            <div class="variants-container" id="variantsContainer">
              <?php if ($isEditMode && !empty($variants)): ?>
                <?php foreach ($variants as $variant): ?>
                  <div class="variant-card" data-variantid="<?php echo $variant['VariantID']; ?>">
                    <input type="hidden" name="variants[<?php echo $variant['VariantID']; ?>][color]" 
                           value="<?php echo htmlspecialchars($variant['Color']); ?>">
                    <input type="hidden" name="variants[<?php echo $variant['VariantID']; ?>][storage]" 
                           value="<?php echo htmlspecialchars($variant['Storage']); ?>">
                    <input type="hidden" name="variants[<?php echo $variant['VariantID']; ?>][price]" 
                           value="<?php echo htmlspecialchars($variant['Price']); ?>">
                    <input type="hidden" name="variants[<?php echo $variant['VariantID']; ?>][discountedPrice]" 
                           value="<?php echo htmlspecialchars($variant['DiscountedPrice'] ?? ''); ?>">
                    <input type="hidden" name="variants[<?php echo $variant['VariantID']; ?>][quantity]" 
                           value="<?php echo htmlspecialchars($variant['StockQuantity']); ?>">
                    <input type="hidden" name="variants[<?php echo $variant['VariantID']; ?>][variantId]" 
                           value="<?php echo $variant['VariantID']; ?>">
                    <div class="variant-color" style="background-color: <?php echo getColorHex($variant['Color']); ?>"></div>
                    <div class="variant-details">
                      <div><?php echo ucfirst($variant['Color']); ?></div>
                      <div><?php echo $variant['Storage']; ?>GB</div>
                      <div>
                          <?php if ($variant['DiscountedPrice']): ?>
                              <span class="original-price">LKR <?php echo number_format($variant['Price'], 0); ?></span>
                              <span class="discounted-price">LKR <?php echo number_format($variant['DiscountedPrice'], 0); ?></span>
                            <?php else: ?>
                              LKR <?php echo number_format($variant['Price'], 0); ?>
                            <?php endif; ?>
                          </div>
                      <div>Qty: <?php echo $variant['StockQuantity']; ?></div>
                    </div>
                    <button type="button" class="edit-variant edit-btn" data-variantid="<?php echo $variant['VariantID']; ?>"><i class="fas fa-edit"></i></button>
                    <button type="button" class="remove-variant" data-variantid="<?php echo $variant['VariantID']; ?>">&times;</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Variant Modal (hidden by default) -->
          <div class="modal" id="variantModal">
              <div class="modal-content">
                  <div class="modal-header">
                      <h4 id="modalTitle">Add New Variant</h4>
                      <button type="button" class="close-modal">&times;</button>
                  </div>
                  <div class="modal-body">
                      <input type="hidden" id="modalVariantId">
                      <div class="form-group">
                          <label>Color*</label>
                          <div class="color-options">
                              <div class="color-option" style="background-color: #4285f4;" data-color="blue" title="Blue"></div>
                              <div class="color-option" style="background-color: #34a853;" data-color="green" title="Green"></div>
                              <div class="color-option" style="background-color: #000000;" data-color="black" title="Black"></div>
                              <div class="color-option" style="background-color: #fbbc05;" data-color="gold" title="Gold"></div>
                              <div class="color-option" style="background-color: #ea4335;" data-color="red" title="Red"></div>
                          </div>
                          <input type="hidden" id="modalSelectedColor" name="modalSelectedColor">
                          <small class="error-message" id="colorError" style="color: red; display: none;">Please select a color</small>
                      </div>

                      <div class="form-group">
                          <label>Storage Options*</label>
                          <div class="storage-options">
                              <div class="storage-option" data-storage="64">64GB</div>
                              <div class="storage-option" data-storage="128">128GB</div>
                              <div class="storage-option" data-storage="256">256GB</div>
                              <div class="storage-option" data-storage="512">512GB</div>
                              <div class="storage-option" data-storage="1024">1TB</div>
                          </div>
                          <input type="hidden" id="modalSelectedStorage" name="modalSelectedStorage">
                          <small class="error-message" id="storageError" style="color: red; display: none;">Please select a storage option</small>
                      </div>

                      <div class="form-group">
                        <label for="variantPrice">Price (LKR)*</label>
                        <input type="number" id="variantPrice" name="variantPrice" class="form-control" placeholder="e.g. 475000" min="1" step="1">
                        <small class="error-message" id="priceError" style="color: red; display: none;">Please enter a valid price</small>
                    </div>
                    <div class="form-group">
                        <label for="variantDiscountedPrice">Discounted Price (LKR)</label>
                        <input type="number" id="variantDiscountedPrice" name="variantDiscountedPrice" class="form-control" 
                              placeholder="e.g. 429900" min="0" step="1">
                        <small class="error-message" id="discountError" style="color: red; display: none;">Discount must be less than price</small>
                    </div>
                      <div class="form-group">
                          <label for="variantQuantity">Quantity*</label>
                          <input type="number" id="variantQuantity" name="variantQuantity" class="form-control" 
                                placeholder="e.g. 50" min="1">
                          <small class="error-message" id="quantityError" style="color: red; display: none;">Please enter a valid quantity</small>
                      </div>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary close-modal btn-danger">Cancel</button>
                      <button type="button" class="btn btn-primary" id="saveVariantBtn">Save Variant</button>
                  </div>
              </div>
          </div>

          <!-- Image Upload -->
          <div class="form-group">
            <label>Product Images (Upload at least 1 image)</label>
            <div class="image-upload">
              <div class="upload-box" id="imageUpload1">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Click to upload</span>
                <?php if ($isEditMode && !empty($product['ImagePath1'])): ?>
                  <img src="<?php echo '../../' . $product['ImagePath1']; ?>" alt="Preview" id="imagePreview1">
                <?php else: ?>
                  <img src="" alt="Preview" id="imagePreview1">
                <?php endif; ?>
                <input type="file" id="productImage1" name="productImage1" accept="image/*" style="display: none;" <?php echo !$isEditMode ? 'required' : ''; ?>>
              </div>
              <div class="upload-box" id="imageUpload2">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Click to upload</span>
                <?php if ($isEditMode && !empty($product['ImagePath2'])): ?>
                  <img src="<?php echo '../../' . $product['ImagePath2']; ?>" alt="Preview" id="imagePreview2">
                <?php else: ?>
                  <img src="" alt="Preview" id="imagePreview2">
                <?php endif; ?>
                <input type="file" id="productImage2" name="productImage2" accept="image/*" style="display: none;">
              </div>
            </div>
          </div>
          <?php if ($isEditMode): ?>
              <input type="hidden" name="existingImage1" value="<?php echo !empty($product['ImagePath1']) ? $product['ImagePath1'] : ''; ?>">
              <input type="hidden" name="existingImage2" value="<?php echo !empty($product['ImagePath2']) ? $product['ImagePath2'] : ''; ?>">
          <?php endif; ?>
          <div class="form-group" style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <a href="products.php" class="btn btn-danger" style="margin-left: 10px;">Cancel</a>
            <?php if ($isEditMode): ?>
              <button type="button" class="btn btn-danger" id="deleteProductBtn" style="margin-left: 10px;">
                <i class="fas fa-trash"></i> Delete Product
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </main>
  </div>
  <script src="assets/js/addedititem.js"></script>
</body>
</html>