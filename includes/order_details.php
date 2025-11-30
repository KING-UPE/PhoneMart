<?php
require_once 'config.php';
require_once 'auth.php';
redirectIfNotLoggedIn();

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: profile.php?error=order_not_found');
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch order details
$orderQuery = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, 
               u.Username, u.Email, u.PhoneNumber, u.Address
               FROM `order` o
               JOIN user u ON o.UserID = u.UserID
               WHERE o.OrderID = ? AND o.UserID = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header('Location: profile.php?error=order_not_found');
    exit();
}

// Fetch order items
$itemsQuery = "SELECT oi.*, p.Name as ProductName, pv.Color, pv.Storage 
               FROM orderitem oi
               JOIN productvariant pv ON oi.VariantID = pv.VariantID
               JOIN product p ON pv.ProductID = p.ProductID
               WHERE oi.OrderID = ?";
$stmt_items = $conn->prepare($itemsQuery);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order #<?= $order['OrderID'] ?> - PHONE MART</title>
  <style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        color: #333;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .print-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #eee;
        padding-bottom: 20px;
    }
    .print-header h1 {
        margin: 0;
        color: #2c3e50;
    }
    .order-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    .order-details, .customer-info {
        width: 48%;
    }
    .order-items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .order-items th, .order-items td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .order-items th {
        background-color: #f2f2f2;
    }
    .order-total {
        text-align: right;
        font-weight: bold;
        font-size: 1.2em;
    }
    .print-actions {
        text-align: center;
        margin-top: 30px;
    }
    .btn-print {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 4px;
    }
    @media print {
        .print-actions {
            display: none;
        }
        body {
            padding: 0;
        }
    }
  </style>
</head>
<body>
  <div class="print-header">
    <h1>PHONE MART</h1>
    <p>Order Invoice</p>
  </div>

  <div class="order-info">
    <div class="order-details">
      <h3>Order Information</h3>
      <p><strong>Order #:</strong> <?= $order['OrderID'] ?></p>
      <p><strong>Date:</strong> <?= date('F j, Y', strtotime($order['OrderDate'])) ?></p>
      <p><strong>Status:</strong> <?= $order['Status'] ?></p>
    </div>
    
    <div class="customer-info">
      <h3>Customer Information</h3>
      <p><strong>Name:</strong> <?= htmlspecialchars($order['Username']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($order['Email']) ?></p>
      <?php if (!empty($order['PhoneNumber'])): ?>
      <p><strong>Phone:</strong> <?= htmlspecialchars($order['PhoneNumber']) ?></p>
      <?php endif; ?>
      <?php if (!empty($order['Address'])): ?>
      <p><strong>Address:</strong> <?= htmlspecialchars($order['Address']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <table class="order-items">
    <thead>
      <tr>
        <th>Product</th>
        <th>Variant</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['ProductName']) ?></td>
        <td><?= htmlspecialchars($item['Color']) ?>, <?= htmlspecialchars($item['Storage']) ?></td>
        <td>LKR <?= number_format($item['UnitPrice'], 2) ?></td>
        <td><?= $item['Quantity'] ?></td>
        <td>LKR <?= number_format($item['UnitPrice'] * $item['Quantity'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="order-total">
    <p><strong>Total Amount:</strong> LKR <?= number_format($order['TotalAmount'], 2) ?></p>
  </div>

  <div class="print-actions">
    <button class="btn-print" onclick="window.print()">Print Invoice</button>
    <button class="btn-print" onclick="window.close()" style="background-color: #95a5a6;">Close</button>
  </div>

  <script>
    // Auto-print when page loads (optional)
    window.onload = function() {
      // Uncomment the line below if you want the print dialog to open automatically
      window.print();
    };
  </script>
</body>
</html>