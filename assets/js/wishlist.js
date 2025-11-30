// assets/js/wishlist.js
function showModal(title, message, confirmCallback, showCancel = true) {
    const modal = `
        <div class="modal-overlay active">
            <div class="modal-container">
                <h3 class="modal-title">${title}</h3>
                <p class="modal-message">${message}</p>
                <div class="modal-actions">
                    ${showCancel ? `<button class="modal-btn modal-btn-cancel">Cancel</button>` : ''}
                    <button class="modal-btn modal-btn-confirm delete">OK</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
    
    document.querySelector('.modal-btn-confirm').addEventListener('click', function() {
        document.querySelector('.modal-overlay').remove();
        confirmCallback();
    });
    
    const cancelBtn = document.querySelector('.modal-btn-cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            document.querySelector('.modal-overlay').remove();
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    document.querySelectorAll('.add-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const variantId = this.dataset.variant;
            const itemName = this.closest('.showcase').querySelector('.showcase-title').textContent;
            
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
                    showCustomAlert(`${itemName} added to cart!`, 'success');
                    // Update cart count in header
                    document.querySelectorAll('.action-btn .count').forEach(el => {
                        el.textContent = parseInt(el.textContent) + 1;
                    });
                } else {
                    showCustomAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCustomAlert('An error occurred while adding to cart', 'error');
            });
        });
    });
    
    // Remove item confirmation
    document.querySelectorAll('.remove-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const itemName = this.closest('.showcase').querySelector('.showcase-title').textContent;
            
            showModal(
                'Remove Item',
                `Are you sure you want to remove ${itemName} from your wishlist?`,
                () => {
                    // Create a new form submission
                    const hiddenForm = document.createElement('form');
                    hiddenForm.method = 'POST';
                    hiddenForm.action = form.action;
                    
                    // Add the variant_id input
                    const variantInput = document.createElement('input');
                    variantInput.type = 'hidden';
                    variantInput.name = 'variant_id';
                    variantInput.value = form.querySelector('input[name="variant_id"]').value;
                    hiddenForm.appendChild(variantInput);
                    
                    // Add the remove_item input
                    const removeInput = document.createElement('input');
                    removeInput.type = 'hidden';
                    removeInput.name = 'remove_item';
                    removeInput.value = '1';
                    hiddenForm.appendChild(removeInput);
                    
                    // Submit the form
                    document.body.appendChild(hiddenForm);
                    hiddenForm.submit();
                },
                true
            );
        });
    });
});