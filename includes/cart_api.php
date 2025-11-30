<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Only allow logged-in users
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage your cart']);
    exit();
}

$userId = $_SESSION['user_id'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // Get or create user's cart
    $cartQuery = "SELECT CartID FROM cart WHERE UserID = ?";
    $stmt = $conn->prepare($cartQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart && $requestMethod === 'POST' && $action !== 'clear') {
        $insertCart = "INSERT INTO cart (UserID) VALUES (?)";
        $stmt = $conn->prepare($insertCart);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $cartId = $conn->insert_id;
    } else {
        $cartId = $cart['CartID'] ?? null;
    }

    switch ($action) {
        case 'add':
            if ($requestMethod === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $variantId = isset($data['variant_id']) ? intval($data['variant_id']) : 0;

                // Validate variant
                $variantQuery = "SELECT * FROM productvariant WHERE VariantID = ? AND StockQuantity > 0";
                $stmt = $conn->prepare($variantQuery);
                $stmt->bind_param("i", $variantId);
                $stmt->execute();
                $variant = $stmt->get_result()->fetch_assoc();
                
                if (!$variant) {
                    throw new Exception('Product variant not available');
                }

                // Check if item exists in cart
                $cartItemQuery = "SELECT * FROM cartitem WHERE CartID = ? AND VariantID = ?";
                $stmt = $conn->prepare($cartItemQuery);
                $stmt->bind_param("ii", $cartId, $variantId);
                $stmt->execute();
                $cartItem = $stmt->get_result()->fetch_assoc();
                
                if ($cartItem) {
                    // Update quantity (don't exceed stock)
                    $newQuantity = $cartItem['Quantity'] + 1;
                    if ($newQuantity > $variant['StockQuantity']) {
                        throw new Exception('Cannot add more than available stock');
                    }
                    
                    $updateQty = "UPDATE cartitem SET Quantity = ? WHERE CartItemID = ?";
                    $stmt = $conn->prepare($updateQty);
                    $stmt->bind_param("ii", $newQuantity, $cartItem['CartItemID']);
                    $stmt->execute();
                } else {
                    // Add new item
                    $insertItem = "INSERT INTO cartitem (CartID, VariantID, Quantity) VALUES (?, ?, 1)";
                    $stmt = $conn->prepare($insertItem);
                    $stmt->bind_param("ii", $cartId, $variantId);
                    $stmt->execute();
                }
                
                echo json_encode(['success' => true]);
            }
            break;

        case 'update':
            if ($requestMethod === 'POST') {
                $cartItemId = isset($_POST['cart_item_id']) ? intval($_POST['cart_item_id']) : 0;
                $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

                if ($cartItemId <= 0 || $quantity <= 0) {
                    throw new Exception('Invalid request');
                }

                // Verify the cart item belongs to the user and get price/stock
                $verifyQuery = "SELECT pv.Price, pv.DiscountedPrice, pv.StockQuantity
                                FROM cartitem ci
                                JOIN productvariant pv ON ci.VariantID = pv.VariantID
                                WHERE ci.CartItemID = ? AND ci.CartID = ?";
                $stmt = $conn->prepare($verifyQuery);
                $stmt->bind_param("ii", $cartItemId, $cartId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception('Cart item not found');
                }

                $variant = $result->fetch_assoc();
                
                // Check stock
                if ($quantity > $variant['StockQuantity']) {
                    throw new Exception('Cannot add more than available stock');
                }

                // Update quantity
                $updateQuery = "UPDATE cartitem SET Quantity = ? WHERE CartItemID = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("ii", $quantity, $cartItemId);
                $stmt->execute();

                // Get updated price and total
                $price = $variant['DiscountedPrice'] ? $variant['DiscountedPrice'] : $variant['Price'];
                
                $totalQuery = "SELECT SUM(ci.Quantity * IFNULL(pv.DiscountedPrice, pv.Price)) as total
                               FROM cartitem ci
                               JOIN productvariant pv ON ci.VariantID = pv.VariantID
                               WHERE ci.CartID = ?";
                $stmt = $conn->prepare($totalQuery);
                $stmt->bind_param("i", $cartId);
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

                echo json_encode([
                    'success' => true,
                    'price' => $price,
                    'total' => $total
                ]);
            }
            break;

        case 'remove':
            if ($requestMethod === 'POST') {
                $cartItemId = isset($_POST['cart_item_id']) ? intval($_POST['cart_item_id']) : 0;

                if ($cartItemId <= 0) {
                    throw new Exception('Invalid cart item');
                }

                // Verify the cart item belongs to the user
                $verifyQuery = "SELECT CartItemID FROM cartitem WHERE CartItemID = ? AND CartID = ?";
                $stmt = $conn->prepare($verifyQuery);
                $stmt->bind_param("ii", $cartItemId, $cartId);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Cart item not found');
                }

                // Delete the item
                $deleteQuery = "DELETE FROM cartitem WHERE CartItemID = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $cartItemId);
                $stmt->execute();

                echo json_encode(['success' => true]);
            }
            break;

        case 'clear':
            if ($requestMethod === 'POST') {
                // Delete all cart items
                $deleteQuery = "DELETE FROM cartitem WHERE CartID = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $cartId);
                $stmt->execute();

                echo json_encode(['success' => true]);
            }
            break;

        case 'get':
            default:
                // Get cart items with product details
                $itemsQuery = "SELECT 
                    ci.CartItemID, ci.Quantity,
                    pv.VariantID, pv.Color, pv.Storage, pv.Price, pv.DiscountedPrice,
                    p.ProductID, p.Name, p.ImagePath1
                FROM cartitem ci
                JOIN productvariant pv ON ci.VariantID = pv.VariantID
                JOIN product p ON pv.ProductID = p.ProductID
                WHERE ci.CartID = ?";
                
                $stmt = $conn->prepare($itemsQuery);
                $stmt->bind_param("i", $cartId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $cartItems = [];
                $totalPrice = 0;
                
                while ($row = $result->fetch_assoc()) {
                    $price = $row['DiscountedPrice'] ? $row['DiscountedPrice'] : $row['Price'];
                    $cartItems[] = [
                        'cart_item_id' => $row['CartItemID'],
                        'variant_id' => $row['VariantID'],
                        'name' => $row['Name'],
                        'varient' => $row['Color'] . ' / ' . $row['Storage'],
                        'price' => $price,
                        'quantity' => $row['Quantity'],
                        'img' => $row['ImagePath1']
                    ];
                    $totalPrice += $price * $row['Quantity'];
                }
                
                echo json_encode([
                    'success' => true,
                    'items' => $cartItems,
                    'total' => $totalPrice
                ]);
                break;
    }

} catch (Exception $e) {
    error_log("Cart API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>