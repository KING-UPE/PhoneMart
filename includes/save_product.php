<?php
require_once 'config.php';
require_once 'auth.php';
redirectIfNotAdmin();

function handleFileUpload($fieldName, $productId, $imageNumber) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate a unique filename
        $fileExt = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExt, $allowedExtensions)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            return null;
        }
        
        $fileName = "product_" . uniqid() . "_{$imageNumber}.{$fileExt}";
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $filePath)) {
            return "/uploads/products/" . $fileName;
        } else {
            $_SESSION['error'] = "Failed to upload file.";
            return null;
        }
    }
    return null;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $isEditMode = $_POST['editMode'] === 'true';
        $productId = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
        
        // Validate required fields
        if (empty($_POST['productName']) || empty($_POST['brand']) || empty($_POST['category']) || empty($_POST['description'])) {
            throw new Exception("All required fields must be filled.");
        }
        
        // Validate at least one variant exists
        if (empty($_POST['variants']) && empty($_POST['newVariants'])) {
            throw new Exception("At least one product variant is required.");
        }

        // Sanitize inputs
        $name = trim($_POST['productName']);
        $brandId = intval($_POST['brand']);
        $categoryId = intval($_POST['category']);
        $model = isset($_POST['model']) ? trim($_POST['model']) : null;
        $description = trim($_POST['description']);
        
        // Handle file uploads
        $imagePath1 = handleFileUpload('productImage1', $productId, 1);
        $imagePath2 = handleFileUpload('productImage2', $productId, 2);
        
        // For edit mode, use existing image if no new image was uploaded
        if ($isEditMode && !$imagePath1 && isset($_POST['existingImage1'])) {
            $imagePath1 = $_POST['existingImage1'];
        }
        
        // For new products, require image upload
        if (!$isEditMode && !$imagePath1) {
            throw new Exception("At least one product image is required.");
        }

        if ($isEditMode && $productId > 0) {
            // Update existing product
            $stmt = $conn->prepare("UPDATE product SET 
                Name = ?, BrandID = ?, CategoryID = ?, Model = ?, Description = ?, 
                ImagePath1 = ?, ImagePath2 = ?
                WHERE ProductID = ?");
            $stmt->bind_param("siissssi", $name, $brandId, $categoryId, $model, $description, 
                $imagePath1, $imagePath2, $productId);
            $stmt->execute();
            
            // Handle existing variants
            if (isset($_POST['variants'])) {
                foreach ($_POST['variants'] as $variantId => $variantData) {
                    $color = $conn->real_escape_string($variantData['color']);
                    $storage = $conn->real_escape_string($variantData['storage']);
                    $price = floatval($variantData['price']);
                    $discountedPrice = isset($variantData['discountedPrice']) ? floatval($variantData['discountedPrice']) : null;
                    $quantity = intval($variantData['quantity']);
                    
                    $stmt = $conn->prepare("UPDATE productvariant SET 
                        Color = ?, Storage = ?, Price = ?, DiscountedPrice = ?, StockQuantity = ?
                        WHERE VariantID = ?");
                    $stmt->bind_param("ssddii", $color, $storage, $price, $discountedPrice, $quantity, $variantId);
                    $stmt->execute();
                }
            }

            // Handle new variants
            if (isset($_POST['newVariants'])) {
                foreach ($_POST['newVariants'] as $newVariant) {
                    $color = $conn->real_escape_string($newVariant['color']);
                    $storage = $conn->real_escape_string($newVariant['storage']);
                    $price = floatval($newVariant['price']);
                    $discountedPrice = isset($newVariant['discountedPrice']) ? floatval($newVariant['discountedPrice']) : null;
                    $quantity = intval($newVariant['quantity']);
                    
                    $stmt = $conn->prepare("INSERT INTO productvariant 
                        (ProductID, Color, Storage, Price, DiscountedPrice, StockQuantity) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issddi", $productId, $color, $storage, $price, $discountedPrice, $quantity);
                    $stmt->execute();
                }
            }
            
            $_SESSION['message'] = "Product updated successfully!";
        } else {
            // Create new product
            $stmt = $conn->prepare("INSERT INTO product 
                (Name, BrandID, CategoryID, Model, Description, ImagePath1, ImagePath2) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siissss", $name, $brandId, $categoryId, $model, $description, $imagePath1, $imagePath2);
            $stmt->execute();
            $productId = $conn->insert_id;
            
            // Add variants
            if (isset($_POST['variants'])) {
                foreach ($_POST['variants'] as $variantId => $variant) {
                    $color = $conn->real_escape_string($variant['color']);
                    $storage = $conn->real_escape_string($variant['storage']);
                    $price = floatval($variant['price']);
                    $discountedPrice = isset($variant['discountedPrice']) ? floatval($variant['discountedPrice']) : null;
                    $quantity = intval($variant['quantity']);
                    
                    $stmt = $conn->prepare("INSERT INTO productvariant 
                        (ProductID, Color, Storage, Price, DiscountedPrice, StockQuantity) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issddi", $productId, $color, $storage, $price, $discountedPrice, $quantity);
                    $stmt->execute();
                }
            }
            
            // Add new variants
            if (isset($_POST['newVariants'])) {
                foreach ($_POST['newVariants'] as $newVariant) {
                    $color = $conn->real_escape_string($newVariant['color']);
                    $storage = $conn->real_escape_string($newVariant['storage']);
                    $price = floatval($newVariant['price']);
                    $discountedPrice = isset($newVariant['discountedPrice']) ? floatval($newVariant['discountedPrice']) : null;
                    $quantity = intval($newVariant['quantity']);
                    
                    $stmt = $conn->prepare("INSERT INTO productvariant 
                        (ProductID, Color, Storage, Price, DiscountedPrice, StockQuantity) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issddi", $productId, $color, $storage, $price, $discountedPrice, $quantity);
                    $stmt->execute();
                }
            }
            
            $_SESSION['message'] = "Product created successfully!";
        }
        
        header("Location: ../admin/products.php?success=1");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}   