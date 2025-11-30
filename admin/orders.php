<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Initialize variables
$orders = [];
$completedOrders = [];
$error = '';

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get all ongoing orders with pagination
try {
    // Count total ongoing orders
    $totalQuery = "SELECT COUNT(DISTINCT o.OrderID) as total 
                  FROM `order` o
                  JOIN user u ON o.UserID = u.UserID
                  WHERE o.Status NOT IN ('delivered', 'cancelled')";
    
    $totalStmt = $conn->prepare($totalQuery);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalOrders = $totalResult->fetch_assoc()['total'];
    $totalPages = ceil($totalOrders / $itemsPerPage);

    // Get paginated orders with item count
    $orderQuery = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, 
                  u.Username as CustomerName,
                  (SELECT COUNT(*) FROM orderitem WHERE OrderID = o.OrderID) as ItemCount
                  FROM `order` o
                  JOIN user u ON o.UserID = u.UserID
                  WHERE o.Status NOT IN ('delivered', 'cancelled')
                  ORDER BY o.OrderDate DESC
                  LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("ii", $itemsPerPage, $offset);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching orders: " . $e->getMessage();
    error_log($error);
}

// Get completed orders (for modal) with pagination
try {
    $completedItemsPerPage = 10;
    $completedCurrentPage = isset($_GET['completed_page']) ? max(1, intval($_GET['completed_page'])) : 1;
    $completedOffset = ($completedCurrentPage - 1) * $completedItemsPerPage;

    // Count total completed orders
    $completedTotalQuery = "SELECT COUNT(DISTINCT o.OrderID) as total 
                          FROM `order` o
                          JOIN user u ON o.UserID = u.UserID
                          WHERE o.Status IN ('delivered', 'cancelled')";
    
    $completedTotalStmt = $conn->prepare($completedTotalQuery);
    $completedTotalStmt->execute();
    $completedTotalResult = $completedTotalStmt->get_result();
    $completedTotalOrders = $completedTotalResult->fetch_assoc()['total'];
    $completedTotalPages = ceil($completedTotalOrders / $completedItemsPerPage);

    // Get paginated completed orders
    $completedQuery = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, 
                      u.Username as CustomerName,
                      (SELECT COUNT(*) FROM orderitem WHERE OrderID = o.OrderID) as ItemCount,
                      MAX(CASE WHEN o.Status = 'delivered' THEN o.OrderDate ELSE NULL END) as CompletedDate
                      FROM `order` o
                      JOIN user u ON o.UserID = u.UserID
                      WHERE o.Status IN ('delivered', 'cancelled')
                      GROUP BY o.OrderID
                      ORDER BY o.OrderDate DESC
                      LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($completedQuery);
    $stmt->bind_param("ii", $completedItemsPerPage, $completedOffset);
    $stmt->execute();
    $completedOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching completed orders: " . $e->getMessage();
    error_log($error);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_POST['order_id'])) {
            throw new Exception("Order ID is required");
        }
        if (!isset($_POST['new_status'])) {
            throw new Exception("Status is required");
        }

        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['new_status'];
        
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $allowedStatuses)) {
            throw new Exception("Invalid status value");
        }

        $conn->begin_transaction();
        
        // Update order status
        $updateQuery = "UPDATE `order` SET Status = ? WHERE OrderID = ?";
        $stmt = $conn->prepare($updateQuery);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $conn->error);
        }
        
        $stmt->bind_param("si", $newStatus, $orderId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update order: " . $stmt->error);
        }
        
        if ($newStatus === 'cancelled') {
            $restoreQuery = "UPDATE productvariant pv
                           JOIN orderitem oi ON pv.VariantID = oi.VariantID
                           SET pv.StockQuantity = pv.StockQuantity + oi.Quantity
                           WHERE oi.OrderID = ?";
            $stmt = $conn->prepare($restoreQuery);
            if ($stmt === false) {
                throw new Exception("Failed to prepare stock restore query: " . $conn->error);
            }
            
            $stmt->bind_param("i", $orderId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to restore stock: " . $stmt->error);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'new_status' => $newStatus
        ]);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Handle order details request
if (isset($_GET['get_order_details'])) {
    $orderId = $_GET['order_id'];
    
    try {
        // Get order info
        $orderQuery = "SELECT o.*, u.Username, u.Email, u.PhoneNumber, u.Address 
                      FROM `order` o
                      JOIN user u ON o.UserID = u.UserID
                      WHERE o.OrderID = ?";
        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            throw new Exception("Order not found");
        }
        
        // Get order items
        $itemsQuery = "SELECT oi.*, p.Name as ProductName, pv.Color, pv.Storage, 
                      pv.Price as OriginalPrice, pv.DiscountedPrice
                      FROM orderitem oi
                      JOIN productvariant pv ON oi.VariantID = pv.VariantID
                      JOIN product p ON pv.ProductID = p.ProductID
                      WHERE oi.OrderID = ?";
        $stmt = $conn->prepare($itemsQuery);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate subtotal
        $subtotal = array_reduce($items, function($carry, $item) {
            return $carry + ($item['UnitPrice'] * $item['Quantity']);
        }, 0);
        
        // Prepare response
        $response = [
            'order' => $order,
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $order['TotalAmount'] - $subtotal
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Handle alerts
$alert = null;
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - Order Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/orders.css">
  <link rel="stylesheet" href="assets/css/messages.css">
</head>
<body>
  <div class="admin-container">

    <!-- Slide Bar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h3>Phone Mart Admin</h3>
      </div>
      <nav class="sidebar-menu">
        <ul>
          <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
          <li><a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a></li>
          <li><a href="categories.php"><i class="fas fa-list"></i> <span>Categories</span></a></li>
          <li><a href="brands.php"><i class="fas fa-tags"></i> <span>Brands</span></a></li>
          <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
          <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
          <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
          <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        </ul>
      </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h1>Order Management</h1>
        <div class="header-actions">
          <button class="btn btn-success" id="viewCompletedOrdersBtn">
            <i class="fas fa-check-circle"></i> <span>Completed Orders</span>
          </button>
        </div>
      </div>

      <?php if ($alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>">
          <?= htmlspecialchars($alert['message']) ?>
        </div>
      <?php endif; ?>

      <div class="content-grid">
        <!-- Main Orders Table -->
        <div class="table-card">
          <div class="card-header">
            <h2>Ongoing Orders</h2>
            <div class="table-controls">
              <div class="date-filter">
                <input type="date" id="orderDateFilter" class="form-control-sm date">
                <select class="form-control-sm" id="orderStatusFilter">
                  <option value="">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="processing">Processing</option>
                  <option value="shipped">Shipped</option>
                </select>
              </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="products-table" id="ordersTable">
              <thead>
                <tr>
                  <th class="order-col">Order</th>
                  <th class="customer-col">Customer</th>
                  <th class="items-col">Items</th>
                  <th class="date-col">Order Date</th>
                  <th class="amount-col">Amount</th>
                  <th class="status-col">Status</th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): 
                  $orderDate = date('Y-m-d', strtotime($order['OrderDate']));
                  $statusClass = strtolower($order['Status']);
                ?>
                <tr>
                  <td>
                    <div class="order-cell">
                      <div class="order-info">
                        <h4>#ORD-<?= $order['OrderID'] ?></h4>
                        <p><?= $order['DeliveryType'] ?? 'Standard Delivery' ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="customer-col"><?= htmlspecialchars($order['CustomerName']) ?></td>
                  <td class="items-col"><?= $order['ItemCount'] ?> item<?= $order['ItemCount'] > 1 ? 's' : '' ?></td>
                  <td><?= $orderDate ?></td>
                  <td>LKR <?= number_format($order['TotalAmount'], 2) ?></td>
                  <td>
                    <select class="status-select" data-order-id="<?= $order['OrderID'] ?>" disabled>
                      <option value="pending" <?= $order['Status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="processing" <?= $order['Status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                      <option value="shipped" <?= $order['Status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                      <option value="delivered" <?= $order['Status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                      <option value="cancelled" <?= $order['Status'] === 'cancelled' ? 'selected' : '' ?>>Cancel</option>
                    </select>
                  </td>
                  <td>
                    <div class="action-btns">
                      <button class="btn btn-sm btn-primary edit-status-btn" data-order-id="<?= $order['OrderID'] ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-info view-order-btn" data-order-id="<?= $order['OrderID'] ?>">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                  <td colspan="7" class="text-center">No ongoing orders found</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="table-footer">
            <div class="pagination">
              <?php if ($currentPage > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-sm" title="First Page">
                  <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="btn btn-sm" title="Previous">
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
              $startPage = max(1, $currentPage - 2);
              $endPage = min($totalPages, $currentPage + 2);
              
              if ($startPage > 1): ?>
                <span class="btn btn-sm disabled">...</span>
              <?php endif;
              
              for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="btn btn-sm <?= $i == $currentPage ? 'active' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor;
              
              if ($endPage < $totalPages): ?>
                <span class="btn btn-sm disabled">...</span>
              <?php endif; ?>

              <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="btn btn-sm" title="Next">
                  <i class="fas fa-chevron-right"></i>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="btn btn-sm" title="Last Page">
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
              Showing <?= ($offset + 1) ?> to <?= min($offset + $itemsPerPage, $totalOrders) ?> of <?= $totalOrders ?> orders
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Completed Orders Modal -->
  <div class="modal" id="completedOrdersModal">
    <div class="modal-content large">
      <div class="modal-header">
        <h4>Completed Orders</h4>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="products-table">
            <thead>
              <tr>
                <th class="order-col">Order</th>
                <th class="customer-col">Customer</th>
                <th class="items-col">Items</th>
                <th class="date-col">Order Date</th>
                <th class="completed-col">Completed Date</th>
                <th class="amount-col">Amount</th>
                <th class="status-col">Status</th>
                <th class="actions-col">View</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($completedOrders as $order): 
                $orderDate = date('Y-m-d', strtotime($order['OrderDate']));
                $completedDate = $order['CompletedDate'] ? date('Y-m-d', strtotime($order['CompletedDate'])) : 'N/A';
                $statusClass = strtolower($order['Status']);
              ?>
              <tr>
                <td>
                  <div class="order-cell">
                    <div class="order-info">
                      <h4>#ORD-<?= $order['OrderID'] ?></h4>
                      <p><?= $order['DeliveryType'] ?? 'Standard Delivery' ?></p>
                    </div>
                  </div>
                </td>
                <td class="customer-col"><?= htmlspecialchars($order['CustomerName']) ?></td>
                <td class="items-col"><?= $order['ItemCount'] ?> item<?= $order['ItemCount'] > 1 ? 's' : '' ?></td>
                <td><?= $orderDate ?></td>
                <td><?= $completedDate ?></td>
                <td>LKR <?= number_format($order['TotalAmount'], 2) ?></td>
                <td><span class="status-badge status-<?= $statusClass ?>"><?= ucfirst($order['Status']) ?></span></td>
                <td>
                  <button class="btn btn-sm btn-info view-order-btn" data-order-id="<?= $order['OrderID'] ?>">
                    <i class="fas fa-eye"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($completedOrders)): ?>
              <tr>
                <td colspan="8" class="text-center">No completed orders found</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <div class="pagination">
          <?php if ($completedCurrentPage > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['completed_page' => 1])) ?>" class="btn btn-sm" title="First Page">
              <i class="fas fa-angle-double-left"></i>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['completed_page' => $completedCurrentPage - 1])) ?>" class="btn btn-sm" title="Previous">
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
          $completedStartPage = max(1, $completedCurrentPage - 2);
          $completedEndPage = min($completedTotalPages, $completedCurrentPage + 2);
          
          if ($completedStartPage > 1): ?>
            <span class="btn btn-sm disabled">...</span>
          <?php endif;
          
          for ($i = $completedStartPage; $i <= $completedEndPage; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['completed_page' => $i])) ?>" 
               class="btn btn-sm <?= $i == $completedCurrentPage ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor;
          
          if ($completedEndPage < $completedTotalPages): ?>
            <span class="btn btn-sm disabled">...</span>
          <?php endif; ?>

          <?php if ($completedCurrentPage < $completedTotalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['completed_page' => $completedCurrentPage + 1])) ?>" class="btn btn-sm" title="Next">
              <i class="fas fa-chevron-right"></i>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['completed_page' => $completedTotalPages])) ?>" class="btn btn-sm" title="Last Page">
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
          Showing <?= ($completedOffset + 1) ?> to <?= min($completedOffset + $completedItemsPerPage, $completedTotalOrders) ?> of <?= $completedTotalOrders ?> orders
        </div>
        
        <button type="button" class="btn btn-secondary btn-close">Close</button>
      </div>
    </div>
  </div>

  <!-- Order Details Modal -->
  <div class="modal" id="orderDetailsModal">
    <div class="modal-content large">
      <div class="modal-header">
        <h4>Order Details - <span id="orderDetailsId"></span></h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h5>Customer Information</h5>
            <div class="customer-info">
              <p><strong>Name:</strong> <span id="customerName"></span></p>
              <p><strong>Email:</strong> <span id="customerEmail"></span></p>
              <p><strong>Phone:</strong> <span id="customerPhone"></span></p>
              <p><strong>Address:</strong> <span id="customerAddress"></span></p>
            </div>
            
            <h5>Order Summary</h5>
            <div class="order-summary">
              <p><strong>Order Date:</strong> <span id="orderDate"></span></p>
              <p><strong>Status:</strong> <span id="orderStatus"></span></p>
              <p><strong>Payment Method:</strong> <span id="paymentMethod"></span></p>
              <p><strong>Delivery Type:</strong> <span id="deliveryType"></span></p>
            </div>
          </div>
          <div class="col-md-6">
            <h5>Order Items</h5>
            <div class="order-items" id="orderItems">
              <!-- Items will be populated here -->
            </div>
            
            <div class="order-totals">
              <p><strong>Subtotal:</strong> <span id="orderSubtotal"></span></p>
              <p><strong>Shipping:</strong> <span id="orderShipping"></span></p>
              <p><strong>Total:</strong> <span id="orderTotal"></span></p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-close">Close</button>
        <button type="button" class="btn btn-primary" id="printOrderBtn">
          <i class="fas fa-print"></i> Print Order
        </button>
      </div>
    </div>
  </div>

  <script src="assets/js/orders.js"></script>
</body>
</html>