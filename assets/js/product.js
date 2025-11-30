document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu functionality
    const mobileMenuOpenBtn = document.querySelectorAll('[data-mobile-menu-open-btn]');
    const mobileMenu = document.querySelectorAll('[data-mobile-menu]');
    const mobileMenuCloseBtn = document.querySelectorAll('[data-mobile-menu-close-btn]');
    const overlay = document.querySelector('[data-overlay]');

    for (let i = 0; i < mobileMenuOpenBtn.length; i++) {
        mobileMenuOpenBtn[i].addEventListener('click', function () {
            mobileMenu[i].classList.add('active');
            overlay.classList.add('active');
            document.body.classList.add('no-scroll');
        });

        mobileMenuCloseBtn[i].addEventListener('click', function() {
            mobileMenu[i].classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('no-scroll');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            mobileMenu.forEach(menu => menu.classList.remove('active'));
            this.classList.remove('active');
            document.body.classList.remove('no-scroll');
        });
    }

    // Color and storage selection
    const colorOptions = document.querySelectorAll('.color-option');
    const storageOptions = document.querySelectorAll('.selecter');

    function updateVariantSelection() {
        const selectedColor = document.querySelector('.color-option.active')?.dataset.color;
        const selectedStorage = document.querySelector('.selecter.active')?.dataset.storage;
        
        if (!selectedColor || !selectedStorage) return;

        if (typeof window.productVariants !== 'undefined') {
            const variant = window.productVariants.find(v => 
                v.Color === selectedColor && v.Storage === selectedStorage
            );

            if (variant) {
                // Update price and other details
                const priceElement = document.querySelector('.current-price');
                const originalPriceElement = document.querySelector('.original-price');
                
                if (variant.DiscountedPrice) {
                    priceElement.textContent = 'LKR ' + Math.round(variant.DiscountedPrice).toLocaleString();
                    if (originalPriceElement) {
                        originalPriceElement.textContent = 'LKR ' + Math.round(variant.Price).toLocaleString();
                        originalPriceElement.style.display = 'block';
                    }
                } else {
                    priceElement.textContent = 'LKR ' + Math.round(variant.Price).toLocaleString();
                    if (originalPriceElement) {
                        originalPriceElement.style.display = 'none';
                    }
                }

                // Update stock info
                const stockInfo = document.querySelector('.stock-info');
                if (stockInfo) {
                    stockInfo.innerHTML = variant.StockQuantity > 0 
                        ? `<p class="in-stock">In Stock: ${variant.StockQuantity}</p>`
                        : '<p class="out-of-stock">Out of Stock</p>';
                }

                // Update buttons
                const addToCartBtn = document.querySelector('.add-to-cart');
                if (addToCartBtn) {
                    addToCartBtn.dataset.variant = variant.VariantID;
                    if (variant.StockQuantity > 0) {
                        addToCartBtn.classList.remove('disabled');
                        addToCartBtn.disabled = false;
                        addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to cart';
                    } else {
                        addToCartBtn.classList.add('disabled');
                        addToCartBtn.disabled = true;
                        addToCartBtn.innerHTML = 'Out of Stock';
                    }
                }

                const wishlistBtn = document.querySelector('.add-to-wishlist');
                if (wishlistBtn) {
                    wishlistBtn.dataset.variant = variant.VariantID;
                }
            }
        }
    }

    colorOptions.forEach(color => {
        color.addEventListener('click', function() {
            colorOptions.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            updateVariantSelection();
        });
    });

    storageOptions.forEach(option => {
        option.addEventListener('click', function() {
            storageOptions.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            updateVariantSelection();
        });
    });

    // Event delegation for all wishlist buttons
    document.addEventListener('click', function(e) {
        // Handle wishlist buttons
        const wishlistBtn = e.target.closest('.btn-action, .add-to-wishlist');
        if (wishlistBtn) {
            e.preventDefault();
            handleWishlistButtonClick(wishlistBtn);
        }

        // Handle add to cart buttons
        const cartBtn = e.target.closest('.add-cart-btn, .add-to-cart');
        if (cartBtn) {
            e.preventDefault();
            handleAddToCart(cartBtn);
        }
    });

    function handleWishlistButtonClick(btn) {
        const variantId = btn.dataset.variant;
        const icon = btn.querySelector('i, ion-icon');
        const isInWishlist = icon.classList.contains('fas') || icon.name === 'heart';
        const productName = btn.closest('.showcase') ? 
            btn.closest('.showcase').querySelector('.showcase-title').textContent :
            document.querySelector('.product-title').textContent;
        
        fetch('includes/add_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ variant_id: variantId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Toggle heart icon
                if (icon.tagName === 'ION-ICON') {
                    icon.name = icon.name === 'heart-outline' ? 'heart' : 'heart-outline';
                    btn.setAttribute('aria-label', 
                        icon.name === 'heart' ? 'Remove from wishlist' : 'Add to wishlist');
                } else {
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    btn.setAttribute('aria-label', 
                        icon.classList.contains('fas') ? 'Remove from wishlist' : 'Add to wishlist');
                }
                
                // Update wishlist count in header
                const wishlistCount = document.querySelector('.action-btn .count');
                if (wishlistCount) {
                    const currentCount = parseInt(wishlistCount.textContent);
                    wishlistCount.textContent = data.action === 'added' ? currentCount + 1 : currentCount - 1;
                }
                
                // Show appropriate message
                showCustomAlert(data.action === 'added' 
                    ? `${productName} added to wishlist!` 
                    : `${productName} removed from wishlist!`, 
                    'success');
            } else {
                showCustomAlert('Error: ' + (data.message || 'Failed to update wishlist'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCustomAlert('An error occurred while updating wishlist', 'error');
        });
    }

    function handleAddToCart(btn) {
        const variantId = btn.dataset.variant;
        const productName = btn.closest('.showcase-content')?.querySelector('.showcase-title')?.textContent || 
                          document.querySelector('.product-title')?.textContent || 'Product';
        
        fetch('includes/cart_api.php?action=add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ variant_id: variantId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showCustomAlert(`${productName} added to cart!`, 'success');
                // Update cart count in header
                document.querySelectorAll('.action-btn .count').forEach(el => {
                    el.textContent = parseInt(el.textContent) + 1;
                });
            } else {
                showCustomAlert('Error: ' + (data.message || 'Failed to add to cart'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCustomAlert('An error occurred while adding to cart', 'error');
        });
    }
});