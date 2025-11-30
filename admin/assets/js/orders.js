document.addEventListener('DOMContentLoaded', function() {
    // Modal Management
    const showModal = (modalId) => {
        document.getElementById(modalId).style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    const hideModal = (modalId) => {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    };

    // Show completed orders modal
    document.getElementById('viewCompletedOrdersBtn')?.addEventListener('click', () => {
        showModal('completedOrdersModal');
    });

    // Close modals
    document.querySelectorAll('.btn-close').forEach(button => {
        button.addEventListener('click', function() {
            hideModal(this.closest('.modal').id);
        });
    });

    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal(this.id);
            }
        });
    });

    // Edit/Save status button functionality
    document.querySelectorAll('.edit-status-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const orderId = this.getAttribute('data-order-id');
            const statusSelect = document.querySelector(`.status-select[data-order-id="${orderId}"]`);
            const isEditing = statusSelect.disabled;
            
            if (isEditing) {
                // Enable editing
                statusSelect.disabled = false;
                statusSelect.classList.add('editable');
                this.innerHTML = '<i class="fas fa-save"></i>';
                this.classList.remove('btn-primary');
                this.classList.add('btn-success');
            } else {
                // Get the new status
                const newStatus = statusSelect.value;
                
                try {
                    // Show loading state
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    this.disabled = true;
                    
                    // Send update request
                    const formData = new FormData();
                    formData.append('update_status', '1');
                    formData.append('order_id', orderId);
                    formData.append('new_status', newStatus);
                    
                    const response = await fetch('orders.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        throw new Error(text || 'Invalid server response');
                    }
                    
                    const result = await response.json();
                    
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to update status');
                    }
                    
                    // Update UI
                    statusSelect.disabled = true;
                    statusSelect.classList.remove('editable');
                    this.innerHTML = '<i class="fas fa-edit"></i>';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-primary');
                    
                    // Show success message
                    showAlert(result.message, 'success');
                    
                    // If status is delivered or cancelled, reload the page
                    if (newStatus === 'delivered' || newStatus === 'cancelled') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } catch (error) {
                    console.error('Error updating status:', error);
                    showAlert(`Error: ${error.message}`, 'error');
                    
                    // Reset to previous status
                    statusSelect.value = statusSelect.dataset.previousValue || 'pending';
                    
                    // Reset button state
                    this.innerHTML = '<i class="fas fa-save"></i>';
                } finally {
                    this.disabled = false;
                }
            }
        });
    });

    // View order details handler (for both main table and completed orders modal)
    document.querySelectorAll('.view-order-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const orderId = this.getAttribute('data-order-id');
            
            try {
                // Show loading state
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                // Fetch order details
                const response = await fetch(`orders.php?get_order_details=1&order_id=${orderId}`);
                const orderData = await response.json();
                
                if (orderData.error) {
                    throw new Error(orderData.error);
                }
                
                // Populate the modal
                populateOrderDetails(orderData);
                showModal('orderDetailsModal');
            } catch (error) {
                console.error('Error fetching order details:', error);
                showAlert(`Error: ${error.message}`, 'error');
            } finally {
                this.innerHTML = '<i class="fas fa-eye"></i>';
                this.disabled = false;
            }
        });
    });

    // Print button functionality
    document.getElementById('printOrderBtn')?.addEventListener('click', function() {
        const printContent = document.getElementById('orderDetailsModal').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
    });

    // Date filter functionality
    document.getElementById('orderDateFilter')?.addEventListener('change', function() {
        const selectedDate = this.value;
        filterOrders(selectedDate, document.getElementById('orderStatusFilter').value);
    });

    // Status filter functionality
    document.getElementById('orderStatusFilter')?.addEventListener('change', function() {
        const selectedStatus = this.value;
        filterOrders(document.getElementById('orderDateFilter').value, selectedStatus);
    });

    // Helper function to filter orders
    function filterOrders(date, status) {
        const rows = document.querySelectorAll('#ordersTable tbody tr');
        
        rows.forEach(row => {
            const rowDate = row.querySelector('td:nth-child(4)').textContent;
            const rowStatus = row.querySelector('.status-select').value;
            
            const dateMatch = !date || rowDate === date;
            const statusMatch = !status || rowStatus === status;
            
            row.style.display = dateMatch && statusMatch ? '' : 'none';
        });
    }

    // Function to populate order details
    function populateOrderDetails(orderData) {
        const order = orderData.order;
        const items = orderData.items;
        
        // Format dates
        const orderDate = new Date(order.OrderDate).toLocaleDateString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        
        // Populate customer info
        document.getElementById('orderDetailsId').textContent = `#ORD-${order.OrderID}`;
        document.getElementById('customerName').textContent = order.Username;
        document.getElementById('customerEmail').textContent = order.Email;
        document.getElementById('customerPhone').textContent = order.PhoneNumber || 'N/A';
        document.getElementById('customerAddress').textContent = order.Address || 'N/A';
        
        // Populate order summary
        document.getElementById('orderDate').textContent = orderDate;
        document.getElementById('orderStatus').textContent = order.Status.charAt(0).toUpperCase() + order.Status.slice(1);
        document.getElementById('paymentMethod').textContent = order.PaymentMethod || 'N/A';
        document.getElementById('deliveryType').textContent = order.DeliveryType || 'Standard Delivery';
        
        // Populate order items
        const itemsContainer = document.getElementById('orderItems');
        itemsContainer.innerHTML = '';
        
        items.forEach(item => {
            const discountPercentage = item.OriginalPrice > 0 
                ? Math.round((1 - (item.UnitPrice / item.OriginalPrice)) * 100)
                : 0;
            
            const itemElement = document.createElement('div');
            itemElement.className = 'order-item';
            itemElement.innerHTML = `
                <div class="order-item-header">
                    <h5>${item.ProductName}</h5>
                    <span class="price">LKR ${item.UnitPrice.toLocaleString()}</span>
                </div>
                <div class="order-item-details">
                    <p><strong>Variant:</strong> ${item.Color}, ${item.Storage}</p>
                    <p><strong>Quantity:</strong> ${item.Quantity}</p>
                    ${discountPercentage > 0 ? `<p><strong>Discount:</strong> ${discountPercentage}% off</p>` : ''}
                </div>
                <div class="order-item-total">
                    <strong>Total:</strong> LKR ${(item.UnitPrice * item.Quantity).toLocaleString()}
                </div>
            `;
            itemsContainer.appendChild(itemElement);
        });
        
        // Populate totals
        document.getElementById('orderSubtotal').textContent = `LKR ${orderData.subtotal.toLocaleString()}`;
        document.getElementById('orderShipping').textContent = `LKR ${orderData.shipping.toLocaleString()}`;
        document.getElementById('orderTotal').textContent = `LKR ${order.TotalAmount.toLocaleString()}`;
    }

    // Helper function to show alerts
    function showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        document.querySelector('.main-content').prepend(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
});