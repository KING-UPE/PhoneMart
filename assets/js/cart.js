// assets/js/cart.js
document.addEventListener('DOMContentLoaded', function() {
    // Remove item confirmation
    document.querySelectorAll('.remove-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const itemName = this.closest('.showcase').querySelector('.showcase-title').textContent;
            
            showModal(
                'Remove Item',
                `Are you sure you want to remove ${itemName} from your cart?`,
                () => {
                    showCustomAlert(`${itemName} removed from cart`, 'success');
                    this.submit();
                },
                true
            );
        });
    });
    
    // Clear cart confirmation
    const clearForm = document.querySelector('form[action="cart.php?action=clear"]');
    if (clearForm) {
        clearForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            showModal(
                'Clear Cart',
                'Are you sure you want to remove all items from your cart?',
                () => {
                    showCustomAlert('Cart cleared successfully', 'success');
                    this.submit();
                },
                true
            );
        });
    }
    
    // Handle quantity buttons
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.quantity-form');
            const input = form.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if (this.classList.contains('decrease') && quantity > 1) {
                quantity--;
            } else if (this.classList.contains('increase')) {
                quantity++;
            }
            
            input.value = quantity;
            form.submit();
        });
    });
    
    // Handle direct input changes
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('.quantity-form');
            form.submit();
        });
    });
});

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