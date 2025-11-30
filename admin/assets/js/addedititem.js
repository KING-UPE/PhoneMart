document.addEventListener('DOMContentLoaded', function() {
    // Variant Management System
    const addVariantBtn = document.getElementById('addVariantBtn');
    const variantsContainer = document.getElementById('variantsContainer');
    const variantModal = document.getElementById('variantModal');
    const saveVariantBtn = document.getElementById('saveVariantBtn');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const modalSelectedColor = document.getElementById('modalSelectedColor');
    const modalSelectedStorage = document.getElementById('modalSelectedStorage');
    const variantPriceInput = document.getElementById('variantPrice');
    const variantDiscountedPriceInput = document.getElementById('variantDiscountedPrice');
    const variantQuantityInput = document.getElementById('variantQuantity');
    const colorError = document.getElementById('colorError');
    const storageError = document.getElementById('storageError');
    const priceError = document.getElementById('priceError');
    const discountError = document.getElementById('discountError');
    const quantityError = document.getElementById('quantityError');
    const modalTitle = document.getElementById('modalTitle');
    const modalVariantId = document.getElementById('modalVariantId');
    
    // Array to store all variants
    let productVariants = [];
    let editingVariantIndex = null;
    
    // Function to get color hex code
    function getColorHex(color) {
        const colors = {
            'blue': '#4285f4',
            'green': '#34a853',
            'black': '#000000',
            'gold': '#fbbc05',
            'red': '#ea4335'
        };
        return colors[color.toLowerCase()] || '#cccccc';
    }

    // Initialize variants if in edit mode
    if (document.getElementById('editMode').value === 'true') {
        // Get existing variants from hidden inputs
        const variantElements = document.querySelectorAll('.variant-card');
        variantElements.forEach(variantEl => {
            const variantId = variantEl.getAttribute('data-variantid');
            const color = variantEl.querySelector('input[name*="[color]"]').value;
            const storage = variantEl.querySelector('input[name*="[storage]"]').value;
            const price = variantEl.querySelector('input[name*="[price]"]').value;
            const discountedPrice = variantEl.querySelector('input[name*="[discountedPrice]"]').value;
            const quantity = variantEl.querySelector('input[name*="[quantity]"]').value;
            
            productVariants.push({
                id: variantId,
                color: color,
                storage: storage,
                price: price,
                discountedPrice: discountedPrice || null,
                quantity: quantity,
                colorHex: getColorHex(color)
            });
        });
    }

    // Open modal when Add Variant button is clicked
    addVariantBtn.addEventListener('click', function() {
        modalTitle.textContent = 'Add New Variant';
        modalVariantId.value = '';
        editingVariantIndex = null;
        variantModal.style.display = 'flex';
    });
  
    // Edit variant button click handler
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-variant') || e.target.closest('.edit-variant')) {
            const variantId = e.target.closest('.edit-variant').getAttribute('data-variantid');
            const variantIndex = productVariants.findIndex(v => v.id == variantId);
            
            if (variantIndex !== -1) {
                const variant = productVariants[variantIndex];
                modalTitle.textContent = 'Edit Variant';
                modalVariantId.value = variant.id;
                editingVariantIndex = variantIndex;
                
                // Set modal values
                document.querySelector(`.color-option[data-color="${variant.color}"]`).click();
                document.querySelector(`.storage-option[data-storage="${variant.storage}"]`).click();
                variantPriceInput.value = variant.price;
                variantDiscountedPriceInput.value = variant.discountedPrice || '';
                variantQuantityInput.value = variant.quantity;
                
                variantModal.style.display = 'flex';
            }
        }
    });
  
    // Close modal when X or Cancel is clicked
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            variantModal.style.display = 'none';
            resetVariantModal();
        });
    });
  
    // Close modal when clicking outside the modal content
    variantModal.addEventListener('click', function(e) {
        if (e.target === variantModal) {
            variantModal.style.display = 'none';
            resetVariantModal();
        }
    });
  
    // Color selection in modal
    const modalColorOptions = document.querySelectorAll('#variantModal .color-option');
    modalColorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all color options
            modalColorOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Update hidden input value
            modalSelectedColor.value = this.getAttribute('data-color');
            colorError.style.display = 'none';
        });
    });
  
    // Storage selection in modal
    const modalStorageOptions = document.querySelectorAll('#variantModal .storage-option');
    modalStorageOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all storage options
            modalStorageOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Update hidden input value
            modalSelectedStorage.value = this.getAttribute('data-storage');
            storageError.style.display = 'none';
        });
    });
  
    // Input validation event listeners
    variantPriceInput.addEventListener('input', function() {
        if (this.value && !isNaN(this.value) && parseFloat(this.value) > 0) {
            this.style.borderColor = '';
            priceError.style.display = 'none';
        }
    });
    
    variantQuantityInput.addEventListener('input', function() {
        if (this.value && !isNaN(this.value) && parseInt(this.value) > 0) {
            this.style.borderColor = '';
            quantityError.style.display = 'none';
        }
    });
    
    variantDiscountedPriceInput.addEventListener('input', function() {
        if (!this.value || (parseFloat(this.value) < parseFloat(variantPriceInput.value))) {
            this.style.borderColor = '';
            discountError.style.display = 'none';
        }
    });
    
    // Save variant
    saveVariantBtn.addEventListener('click', function() {
        // Get all required values
        const color = modalSelectedColor.value;
        const storage = modalSelectedStorage.value;
        const price = variantPriceInput.value;
        const quantity = variantQuantityInput.value;
        const discountedPrice = variantDiscountedPriceInput.value;
        const variantId = modalVariantId.value;

        // Validate all required fields
        let isValid = true;
        
        // Reset error messages
        colorError.style.display = 'none';
        storageError.style.display = 'none';
        priceError.style.display = 'none';
        quantityError.style.display = 'none';
        discountError.style.display = 'none';

        // Validate color selection
        if (!color) {
            colorError.style.display = 'block';
            isValid = false;
        }

        // Validate storage selection
        if (!storage) {
            storageError.style.display = 'block';
            isValid = false;
        }

        // Validate price
        if (!price || isNaN(price) || parseFloat(price) <= 0) {
            priceError.style.display = 'block';
            variantPriceInput.style.borderColor = 'red';
            isValid = false;
        }

        // Validate quantity
        if (!quantity || isNaN(quantity) || parseInt(quantity) <= 0) {
            quantityError.style.display = 'block';
            variantQuantityInput.style.borderColor = 'red';
            isValid = false;
        }

        // Validate discounted price if provided
        if (discountedPrice && (isNaN(discountedPrice) || parseFloat(discountedPrice) >= parseFloat(price))) {
            discountError.style.display = 'block';
            variantDiscountedPriceInput.style.borderColor = 'red';
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        // Create variant object
        const variant = {
            id: variantId,
            color: color,
            storage: storage,
            price: parseFloat(price),
            discountedPrice: discountedPrice ? parseFloat(discountedPrice) : null,
            quantity: parseInt(quantity),
            colorHex: getColorHex(color)
        };

        if (editingVariantIndex !== null) {
            // Update existing variant
            productVariants[editingVariantIndex] = variant;
        } else {
            // Add new variant
            productVariants.push(variant);
        }
        
        // Render the variants
        renderVariants();
        
        // Close modal and reset
        variantModal.style.display = 'none';
        resetVariantModal();
    });
  
    // Function to reset the variant modal
    function resetVariantModal() {
        modalSelectedColor.value = '';
        modalSelectedStorage.value = '';
        variantPriceInput.value = '';
        variantDiscountedPriceInput.value = '';
        variantQuantityInput.value = '';
        modalVariantId.value = '';
        editingVariantIndex = null;
        
        // Reset error states
        colorError.style.display = 'none';
        storageError.style.display = 'none';
        priceError.style.display = 'none';
        quantityError.style.display = 'none';
        discountError.style.display = 'none';
        
        variantPriceInput.style.borderColor = '';
        variantDiscountedPriceInput.style.borderColor = '';
        variantQuantityInput.style.borderColor = '';
        
        // Clear selections
        modalColorOptions.forEach(opt => opt.classList.remove('selected'));
        modalStorageOptions.forEach(opt => opt.classList.remove('selected'));
    }
  
    // Render all variants
    function renderVariants() {
        variantsContainer.innerHTML = '';
        
        productVariants.forEach((variant, index) => {
            const colorHex = variant.colorHex || '#cccccc';
            const storageText = variant.storage + 'GB';
            const priceDisplay = variant.discountedPrice ? 
                `<span class="original-price">LKR ${parseFloat(variant.price).toFixed(2)}</span>
                 <span class="discounted-price">LKR ${parseFloat(variant.discountedPrice).toFixed(2)}</span>` :
                `LKR ${parseFloat(variant.price).toFixed(2)}`;
            
            const variantCard = document.createElement('div');
            variantCard.className = 'variant-card';
            variantCard.setAttribute('data-variantid', variant.id || 'new-' + index);
            
            // Determine if this is an existing variant or new one
            const variantPrefix = variant.id ? 'variants' : 'newVariants';
            const variantKey = variant.id ? variant.id : index;
            
            variantCard.innerHTML = `
                <input type="hidden" name="${variantPrefix}[${variantKey}][color]" value="${variant.color}">
                <input type="hidden" name="${variantPrefix}[${variantKey}][storage]" value="${variant.storage}">
                <input type="hidden" name="${variantPrefix}[${variantKey}][price]" value="${variant.price}">
                <input type="hidden" name="${variantPrefix}[${variantKey}][discountedPrice]" value="${variant.discountedPrice || ''}">
                <input type="hidden" name="${variantPrefix}[${variantKey}][quantity]" value="${variant.quantity}">
                <input type="hidden" name="${variantPrefix}[${variantKey}][variantId]" value="${variant.id || ''}">
                <div class="variant-color" style="background-color: ${colorHex}"></div>
                <div class="variant-details">
                    <div>${variant.color.charAt(0).toUpperCase() + variant.color.slice(1)}</div>
                    <div>${storageText}</div>
                    <div class="price-display">${priceDisplay}</div>
                    <div>Qty: ${variant.quantity}</div>
                </div>
                <button type="button" class="edit-variant edit-btn" data-variantid="${variant.id || 'new-' + index}"><i class="fas fa-edit"></i></button>
                <button type="button" class="remove-variant" data-index="${index}">&times;</button>
            `;
            
            variantsContainer.appendChild(variantCard);
        });
        
        // Add event listeners to remove buttons
        document.querySelectorAll('.remove-variant').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                if (confirm('Are you sure you want to remove this variant?')) {
                    productVariants.splice(index, 1);
                    renderVariants();
                }
            });
        });
    }
  
    // Image Upload Handling
    const imageUpload1 = document.getElementById('imageUpload1');
    const imageUpload2 = document.getElementById('imageUpload2');
    const productImage1 = document.getElementById('productImage1');
    const productImage2 = document.getElementById('productImage2');
    const imagePreview1 = document.getElementById('imagePreview1');
    const imagePreview2 = document.getElementById('imagePreview2');
  
    // Click handlers for upload boxes
    imageUpload1.addEventListener('click', function() {
        productImage1.click();
    });
  
    imageUpload2.addEventListener('click', function() {
        productImage2.click();
    });
  
    // Preview image when file is selected
    productImage1.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview1.src = e.target.result;
                imagePreview1.style.display = 'block';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
  
    productImage2.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview2.src = e.target.result;
                imagePreview2.style.display = 'block';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
  
    // Form Validation
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const isEditMode = document.getElementById('editMode').value === 'true';
        const hasExistingImage1 = document.querySelector('input[name="existingImage1"]')?.value;
        
        // Check if at least one image is provided (either existing or new)
        const productImage1 = document.getElementById('productImage1');
        if (!isEditMode && (!productImage1.files || productImage1.files.length === 0)) {
            e.preventDefault();
            alert('Please upload at least one product image');
            return;
        }
        
        // Check if we have at least one variant
        if (productVariants.length === 0) {
            e.preventDefault();
            alert('Please add at least one product variant');
            return;
        }
    });
    
    // Delete Product Button
    const deleteProductBtn = document.getElementById('deleteProductBtn');
    if (deleteProductBtn) {
        deleteProductBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                const productId = document.getElementById('productId').value;
                window.location.href = `../includes/delete_product.php?id=${productId}`;
            }
        });
    }

    // Handle click events on dynamically added remove buttons
    variantsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-variant') || e.target.closest('.remove-variant')) {
            const button = e.target.classList.contains('remove-variant') ? e.target : e.target.closest('.remove-variant');
            const index = parseInt(button.getAttribute('data-index'));
            if (confirm('Are you sure you want to remove this variant?')) {
                productVariants.splice(index, 1);
                renderVariants();
            }
        }
    });
});