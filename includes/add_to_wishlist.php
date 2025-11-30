<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Only allow logged-in users
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please login to manage your wishlist'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

try {
    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);
    $variantId = isset($data['variant_id']) ? intval($data['variant_id']) : 0;

    // Validate variant ID
    if ($variantId <= 0) {
        throw new Exception('Invalid product variant');
    }

    // Verify variant exists
    $variantQuery = "SELECT VariantID FROM productvariant WHERE VariantID = ?";
    $stmt = $conn->prepare($variantQuery);
    $stmt->bind_param("i", $variantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Product variant not found');
    }

    // Get or create user's wishlist
    $wishlistQuery = "SELECT WishlistID FROM wishlist WHERE UserID = ?";
    $stmt = $conn->prepare($wishlistQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $wishlist = $stmt->get_result()->fetch_assoc();
    
    if (!$wishlist) {
        $insertWishlist = "INSERT INTO wishlist (UserID) VALUES (?)";
        $stmt = $conn->prepare($insertWishlist);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $wishlistId = $conn->insert_id;
    } else {
        $wishlistId = $wishlist['WishlistID'];
    }

    // Check if item exists in wishlist
    $checkQuery = "SELECT WishlistItemID FROM wishlistitem WHERE WishlistID = ? AND VariantID = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $wishlistId, $variantId);
    $stmt->execute();
    $existingItem = $stmt->get_result()->fetch_assoc();

    if ($existingItem) {
        // Remove from wishlist
        $deleteQuery = "DELETE FROM wishlistitem WHERE WishlistItemID = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $existingItem['WishlistItemID']);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add to wishlist
        $insertQuery = "INSERT INTO wishlistitem (WishlistID, VariantID) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ii", $wishlistId, $variantId);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'action' => 'added']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>