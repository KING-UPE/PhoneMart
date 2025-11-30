<?php
require_once 'config.php';
require_once 'auth.php';
redirectIfNotAdmin();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    $returnPage = isset($_GET['return_page']) ? intval($_GET['return_page']) : 1;
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // First delete from promotion table (if exists)
        $stmt = $conn->prepare("DELETE pr FROM promotion pr 
                              JOIN productvariant pv ON pr.VariantID = pv.VariantID 
                              WHERE pv.ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        
        // Then delete cart items
        $stmt = $conn->prepare("DELETE ci FROM cartitem ci 
                              JOIN productvariant pv ON ci.VariantID = pv.VariantID 
                              WHERE pv.ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        
        // Then delete wishlist items
        $stmt = $conn->prepare("DELETE wi FROM wishlistitem wi 
                              JOIN productvariant pv ON wi.VariantID = pv.VariantID 
                              WHERE pv.ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        
        // Then delete variants
        $stmt = $conn->prepare("DELETE FROM productvariant WHERE ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        
        // Finally delete the product
        $stmt = $conn->prepare("DELETE FROM product WHERE ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = "Product deleted successfully!";
        header("Location: ../admin/products.php?success=1&page=$returnPage");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Deletion error: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
        header("Location: ../admin/products.php?error=1&page=$returnPage");
        exit();
    }
} else {
    $_SESSION['error'] = "No product ID specified";
    header("Location: ../admin/products.php?error=1");
    exit();
}