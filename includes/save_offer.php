<?php
// Include necessary files
require_once 'config.php';
require_once 'auth.php';

// Make sure user is logged in and is an admin
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
    $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $return_page = isset($_POST['return_page']) ? (int)$_POST['return_page'] : 1;
    
    // Basic validation
    if ($variant_id <= 0 || $discount <= 0 || $discount > 100 || empty($end_date)) {
        header("Location: ../admin/products.php?page=$return_page&success=4&message=Invalid input data");
        exit();
    }
    
    // Validate end date is in the future
    $today = date('Y-m-d');
    if ($end_date <= $today) {
        header("Location: ../admin/products.php?page=$return_page&success=4&message=End date must be in the future");
        exit();
    }
    
    // Check if variant exists
    $variantQuery = "SELECT * FROM productvariant WHERE VariantID = ?";
    $stmt = $conn->prepare($variantQuery);
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../admin/products.php?page=$return_page&success=4&message=Product variant not found");
        exit();
    }
    
    // Get variant info for updating discounted price
    $variant = $result->fetch_assoc();
    $original_price = $variant['Price'];
    $discounted_price = $original_price * (1 - ($discount / 100));
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Check if an offer already exists for this variant
        $checkQuery = "SELECT * FROM promotion WHERE VariantID = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("i", $variant_id);
        $stmt->execute();
        $existingOffer = $stmt->get_result();
        
        if ($existingOffer->num_rows > 0) {
            // Update existing promotion
            $updatePromoQuery = "UPDATE promotion 
                                SET DiscountPercent = ?, 
                                    OfferEndDate = ? 
                                WHERE VariantID = ?";
            $stmt = $conn->prepare($updatePromoQuery);
            $stmt->bind_param("dsi", $discount, $end_date, $variant_id);
            $stmt->execute();
        } else {
            // Insert new promotion
            $insertPromoQuery = "INSERT INTO promotion 
                                (VariantID, DiscountPercent, OfferEndDate) 
                                VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertPromoQuery);
            $stmt->bind_param("ids", $variant_id, $discount, $end_date);
            $stmt->execute();
        }
        
        // Update the discounted price in the product variant table
        $updateVariantQuery = "UPDATE productvariant 
                              SET DiscountedPrice = ? 
                              WHERE VariantID = ?";
        $stmt = $conn->prepare($updateVariantQuery);
        $stmt->bind_param("di", $discounted_price, $variant_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect back to products page with success message
        header("Location: ../admin/products.php?page=$return_page&success=3");
        exit();
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        
        // Redirect with error message
        $error_message = urlencode($e->getMessage());
        header("Location: ../admin/products.php?page=$return_page&success=4&message=$error_message");
        exit();
    }
} else {
    // If not POST request, redirect to products page
    header("Location: ../admin/products.php");
    exit();
}
?>