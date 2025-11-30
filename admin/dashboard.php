<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Get statistics data
$stats = [];
try {
    // Total Orders (excluding cancelled)
    $orderQuery = "SELECT COUNT(*) as total FROM `order` WHERE Status != 'cancelled'";
    $stmt = $conn->query($orderQuery);
    $stats['total_orders'] = $stmt->fetch_assoc()['total'];

    // Total Products
    $productQuery = "SELECT COUNT(*) as total FROM product";
    $stmt = $conn->query($productQuery);
    $stats['total_products'] = $stmt->fetch_assoc()['total'];

    // Total Customers (non-admin users)
    $userQuery = "SELECT COUNT(*) as total FROM user WHERE IsAdmin = 0";
    $stmt = $conn->query($userQuery);
    $stats['total_customers'] = $stmt->fetch_assoc()['total'];

    // Total Revenue (sum of completed orders)
    $revenueQuery = "SELECT SUM(TotalAmount) as total FROM `order` WHERE Status = 'delivered'";
    $stmt = $conn->query($revenueQuery);
    $stats['total_revenue'] = $stmt->fetch_assoc()['total'] ?? 0;

    // Recent Orders (5 most recent non-cancelled orders)
    $recentOrdersQuery = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, 
                         u.Username as CustomerName
                         FROM `order` o
                         JOIN user u ON o.UserID = u.UserID
                         WHERE o.Status != 'cancelled'
                         ORDER BY o.OrderDate DESC
                         LIMIT 5";
    $stmt = $conn->query($recentOrdersQuery);
    $stats['recent_orders'] = $stmt->fetch_all(MYSQLI_ASSOC);

    // Top Selling Products (based on order items)
    $topProductsQuery = "SELECT p.ProductID, p.Name, pv.Color, pv.Storage, 
                        SUM(oi.Quantity) as total_sold, 
                        AVG(oi.UnitPrice) as avg_price
                        FROM orderitem oi
                        JOIN productvariant pv ON oi.VariantID = pv.VariantID
                        JOIN product p ON pv.ProductID = p.ProductID
                        JOIN `order` o ON oi.OrderID = o.OrderID
                        WHERE o.Status = 'delivered'
                        GROUP BY p.ProductID, p.Name, pv.Color, pv.Storage
                        ORDER BY total_sold DESC
                        LIMIT 5";
    $stmt = $conn->query($topProductsQuery);
    $stats['top_products'] = $stmt->fetch_all(MYSQLI_ASSOC);

    // Get sales data for the default period (6 months)
    $chartData = getSalesData(6);
    $revenueData = getRevenueByCategory();

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Fallback to dummy data if there's an error
    $stats = [
        'total_orders' => 1254,
        'total_products' => 356,
        'total_customers' => 1842,
        'total_revenue' => 12500000,
        'recent_orders' => [],
        'top_products' => []
    ];
    $chartData = [
        'sales' => [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'revenue' => [8000000, 6000000, 10000000, 7000000, 9000000, 12000000],
            'categories' => [
                'Phones' => [5000000, 4000000, 7000000, 4500000, 6000000, 8000000],
                'Tablets' => [2000000, 1000000, 2000000, 1500000, 2000000, 3000000],
                'Accessories' => [1000000, 1000000, 1000000, 1000000, 1000000, 1000000]
            ]
        ]
    ];
    $revenueData = [
        'labels' => ['Phones', 'Tablets', 'Accessories', 'Others'],
        'data' => [65, 15, 12, 8]
    ];
}

// Handle AJAX request for sales data
if (isset($_GET['get_sales_data'])) {
    header('Content-Type: application/json');
    $months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
    try {
        $data = getSalesData($months);
        echo json_encode([
            'success' => true,
            'labels' => $data['sales']['labels'],
            'revenue' => $data['sales']['revenue'],
            'categories' => $data['sales']['categories']
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Format numbers for display
function formatNumber($number) {
    if ($number >= 1000000) {
        return 'LKR ' . number_format($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return 'LKR ' . number_format($number / 1000, 1) . 'K';
    }
    return 'LKR ' . number_format($number);
}

function formatCount($number) {
    if ($number >= 1000000) {
        return number_format($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return number_format($number / 1000, 1) . 'K';
    }
    return number_format($number);
}

// Function to get sales data for a specific period
function getSalesData($months) {
    global $conn;
    
    $data = ['sales' => ['labels' => [], 'revenue' => [], 'categories' => []]];
    
    // Get revenue by month
    $salesQuery = "SELECT 
                  DATE_FORMAT(OrderDate, '%b') as month,
                  SUM(TotalAmount) as total_amount
                  FROM `order`
                  WHERE OrderDate >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                  AND Status = 'delivered'
                  GROUP BY DATE_FORMAT(OrderDate, '%Y-%m'), DATE_FORMAT(OrderDate, '%b')
                  ORDER BY DATE_FORMAT(OrderDate, '%Y-%m')";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param("i", $months);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data['sales']['labels'][] = $row['month'];
        $data['sales']['revenue'][] = $row['total_amount'];
    }
    
    // Get revenue by category for each month
    $categoryQuery = "SELECT 
                     c.CategoryName,
                     DATE_FORMAT(o.OrderDate, '%b') as month,
                     SUM(oi.Quantity * oi.UnitPrice) as revenue
                     FROM orderitem oi
                     JOIN productvariant pv ON oi.VariantID = pv.VariantID
                     JOIN product p ON pv.ProductID = p.ProductID
                     JOIN category c ON p.CategoryID = c.CategoryID
                     JOIN `order` o ON oi.OrderID = o.OrderID
                     WHERE o.OrderDate >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                     AND o.Status = 'delivered'
                     GROUP BY c.CategoryName, DATE_FORMAT(o.OrderDate, '%Y-%m'), DATE_FORMAT(o.OrderDate, '%b')
                     ORDER BY DATE_FORMAT(o.OrderDate, '%Y-%m'), revenue DESC";
    $stmt = $conn->prepare($categoryQuery);
    $stmt->bind_param("i", $months);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($categories[$row['CategoryName']])) {
            $categories[$row['CategoryName']] = array_fill(0, count($data['sales']['labels']), 0);
        }
        $monthIndex = array_search($row['month'], $data['sales']['labels']);
        if ($monthIndex !== false) {
            $categories[$row['CategoryName']][$monthIndex] = $row['revenue'];
        }
    }
    
    $data['sales']['categories'] = $categories;
    
    return $data;
}

// Function to get revenue by category (top 3 + others)
function getRevenueByCategory() {
    global $conn;
    
    $data = ['labels' => [], 'data' => []];
    
    // Get total revenue by category
    $query = "SELECT 
              c.CategoryName,
              SUM(oi.Quantity * oi.UnitPrice) as revenue
              FROM orderitem oi
              JOIN productvariant pv ON oi.VariantID = pv.VariantID
              JOIN product p ON pv.ProductID = p.ProductID
              JOIN category c ON p.CategoryID = c.CategoryID
              JOIN `order` o ON oi.OrderID = o.OrderID
              WHERE o.Status = 'delivered'
              GROUP BY c.CategoryName
              ORDER BY revenue DESC";
    $result = $conn->query($query);
    
    $categories = [];
    $totalRevenue = 0;
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'name' => $row['CategoryName'],
            'revenue' => $row['revenue']
        ];
        $totalRevenue += $row['revenue'];
    }
    
    // Get top 3 categories and group the rest as "Others"
    $topCategories = array_slice($categories, 0, 3);
    $otherRevenue = 0;
    
    foreach ($topCategories as $category) {
        $data['labels'][] = $category['name'];
        $data['data'][] = $category['revenue'];
    }
    
    // Calculate "Others" if there are more than 3 categories
    if (count($categories) > 3) {
        $otherCategories = array_slice($categories, 3);
        foreach ($otherCategories as $category) {
            $otherRevenue += $category['revenue'];
        }
        if ($otherRevenue > 0) {
            $data['labels'][] = 'Others';
            $data['data'][] = $otherRevenue;
        }
    }
    
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/dashboard.css">

  <style>
    .table-controls {
        padding: 8px 12px;
        border: 1px solid var(--light-gray);
        border-radius: var(--border-radius);
        font-size: 0.9rem;
        background-color: var(--white);
        min-width: 150px;
      }
  </style>

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
          <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
          <li><a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a></li>
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
        <h1>Dashboard Overview</h1>
      </div>

      <!-- Stats Cards -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(67, 97, 238, 0.1);">
            <i class="fas fa-shopping-cart" style="color: var(--primary);"></i>
          </div>
          <div class="stat-info">
            <h3><?= formatCount($stats['total_orders']) ?></h3>
            <p>Total Orders</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(76, 201, 240, 0.1);">
            <i class="fas fa-box" style="color: var(--success);"></i>
          </div>
          <div class="stat-info">
            <h3><?= formatCount($stats['total_products']) ?></h3>
            <p>Total Products</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(248, 150, 30, 0.1);">
            <i class="fas fa-users" style="color: var(--warning);"></i>
          </div>
          <div class="stat-info">
            <h3><?= formatCount($stats['total_customers']) ?></h3>
            <p>Total Customers</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(255, 44, 79, 0.1);">
            <i class="fas fa-dollar-sign" style="color: var(--danger);"></i>
          </div>
          <div class="stat-info">
            <h3><?= formatNumber($stats['total_revenue']) ?></h3>
            <p>Total Revenue</p>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row">
        <div class="chart-card">
          <div class="card-header table-controls">
            <h2>Sales Overview</h2>
            <select class="form-control-sm" id="salesPeriod">
              <option value="3">Last 3 Months</option>
              <option value="6" selected>Last 6 Months</option>
              <option value="12">Last 12 Months</option>
            </select>
          </div>
          <div class="chart-container">
            <canvas id="salesChart"></canvas>
          </div>
        </div>

        <div class="chart-card">
          <div class="card-header">
            <h2>Revenue Sources</h2>
          </div>
          <div class="chart-container">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Orders & Top Products -->
      <div class="row">
        <div class="table-card">
          <div class="card-header">
            <h2>Recent Orders</h2>
            <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
          </div>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($stats['recent_orders'] as $order): ?>
                <tr>
                  <td>#ORD-<?= $order['OrderID'] ?></td>
                  <td><?= htmlspecialchars($order['CustomerName']) ?></td>
                  <td><?= date('d M Y', strtotime($order['OrderDate'])) ?></td>
                  <td><?= formatNumber($order['TotalAmount']) ?></td>
                  <td>
                    <span class="status-badge status-<?= strtolower($order['Status']) ?>">
                      <?= ucfirst($order['Status']) ?>
                    </span>
                  </td>
                  <td>
                    <a href="orders.php" class="btn btn-sm btn-primary">View</a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stats['recent_orders'])): ?>
                <tr>
                  <td colspan="6" class="text-center">No recent orders found</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="products-card">
          <div class="card-header">
            <h2>Top Selling Products</h2>
          </div>
          <div class="top-products-list">
            <?php foreach ($stats['top_products'] as $index => $product): ?>
            <div class="top-product-item">
              <div class="product-rank"><?= $index + 1 ?></div>
              <img src="<?= getProductImage($product['ProductID']) ?>" alt="<?= htmlspecialchars($product['Name']) ?>">
              <div class="product-info">
                <h4><?= htmlspecialchars($product['Name']) ?></h4>
                <p><?= htmlspecialchars($product['Storage']) ?> - <?= htmlspecialchars($product['Color']) ?></p>
                <div class="product-stats">
                  <span><i class="fas fa-shopping-cart"></i> <?= $product['total_sold'] ?> sold</span>
                  <span><?= formatNumber($product['avg_price']) ?></span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($stats['top_products'])): ?>
            <div class="top-product-item">
              <div class="text-center">No top products found</div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Chart.js for dashboard charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Pass PHP data to JavaScript
    const chartData = {
      sales: {
        labels: <?= json_encode($chartData['sales']['labels']) ?>,
        revenue: <?= json_encode($chartData['sales']['revenue']) ?>,
        categories: <?= json_encode($chartData['sales']['categories']) ?>
      },
      revenue: {
        labels: <?= json_encode($revenueData['labels']) ?>,
        data: <?= json_encode($revenueData['data']) ?>
      }
    };
  </script>
  <script src="assets/js/dashboard.js"></script>
  <script src="assets/js/orders.js"></script>
</body>
</html>

<?php
// Helper function to get product image
function getProductImage($productId) {
    // In a real implementation, you would query the database for the image path
    // This is a simplified version
    return "assets/images/products/default-product.jpg";
}
?>