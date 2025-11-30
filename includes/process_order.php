<?php
require_once 'config.php';
require_once 'auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only accept JSON requests
if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid content type']);
    exit();
}

// Get the request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

// Check the action
if (!isset($data['action'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Action is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($data['action']) {
        case 'cancel_order':
            if (!isset($data['order_id'])) {
                $response['message'] = 'Order ID is required';
                break;
            }
            
            $order_id = (int)$data['order_id'];
            
            // Verify order belongs to user and is pending
            $stmt = $conn->prepare("SELECT * FROM `order` WHERE OrderID = ? AND UserID = ? AND Status = 'Pending'");
            $stmt->bind_param("ii", $order_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $response['message'] = 'Order not found or cannot be cancelled';
                break;
            }
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // 1. Update order status to cancelled
                $update = $conn->prepare("UPDATE `order` SET Status = 'Cancelled' WHERE OrderID = ?");
                $update->bind_param("i", $order_id);
                
                if (!$update->execute()) {
                    throw new Exception("Failed to update order status");
                }
                
                // 2. Get all items in the order
                $items = $conn->prepare("SELECT VariantID, Quantity FROM orderitem WHERE OrderID = ?");
                $items->bind_param("i", $order_id);
                $items->execute();
                $items_result = $items->get_result();
                
                // 3. Restore stock for each item
                while ($item = $items_result->fetch_assoc()) {
                    $restore = $conn->prepare("UPDATE productvariant SET StockQuantity = StockQuantity + ? WHERE VariantID = ?");
                    $restore->bind_param("ii", $item['Quantity'], $item['VariantID']);
                    if (!$restore->execute()) {
                        throw new Exception("Failed to restore stock for variant ID: " . $item['VariantID']);
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                $response = [
                    'success' => true, 
                    'message' => 'Order cancelled successfully',
                    'order_id' => $order_id
                ];
                
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'Transaction failed: ' . $e->getMessage();
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>