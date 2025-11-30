// assets/js/profile.js
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const editProfileBtn = document.getElementById('editProfileBtn');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const passwordModal = document.getElementById('passwordModal');
    const closeModal = document.querySelector('.close');
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const realAvatarUpload = document.getElementById('realAvatarUpload');
    const avatarForm = document.getElementById('avatarForm');
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutLink = document.getElementById('logoutLink');
    
    // Edit Profile Toggle
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function() {
            editProfileBtn.style.display = 'none';
            saveProfileBtn.style.display = 'inline-block';
            cancelEditBtn.style.display = 'inline-block';
            changePasswordBtn.style.display = 'none';
            
            document.querySelectorAll('.profile-field p').forEach(p => p.style.display = 'none');
            document.querySelectorAll('.profile-field input, .profile-field textarea').forEach(input => {
                input.style.display = 'block';
            });
        });
    }
    
    // Cancel Edit
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            editProfileBtn.style.display = 'inline-block';
            saveProfileBtn.style.display = 'none';
            cancelEditBtn.style.display = 'none';
            changePasswordBtn.style.display = 'inline-block';
            
            document.querySelectorAll('.profile-field p').forEach(p => p.style.display = 'block');
            document.querySelectorAll('.profile-field input, .profile-field textarea').forEach(input => {
                input.style.display = 'none';
            });
        });
    }
    
    // Change Password Modal
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function() {
            passwordModal.style.display = 'block';
        });
    }
    
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            passwordModal.style.display = 'none';
        });
    }
    
    window.addEventListener('click', function(event) {
        if (event.target === passwordModal) {
            passwordModal.style.display = 'none';
        }
    });
    
    // Avatar Change
    if (changeAvatarBtn) {
        changeAvatarBtn.addEventListener('click', function() {
            realAvatarUpload.click();
        });
    }
    
    if (realAvatarUpload) {
        realAvatarUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Preview image
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('userAvatar').src = event.target.result;
                };
                reader.readAsDataURL(file);
                
                // Submit form
                avatarForm.submit();
            }
        });
    }
    
    // Logout Confirmation
    if (logoutBtn && logoutLink) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            showModal(
                'Confirm Logout',
                'Are you sure you want to logout?',
                () => {
                    window.location.href = logoutLink.href;
                },
                true
            );
        });
    }
    
    // Order Filtering
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active button
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Filter orders
                const orderCards = document.querySelectorAll('.order-card');
                orderCards.forEach(card => {
                    if (filter === 'all' || card.dataset.status === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }
});

function confirmCancelOrder(orderId) {
    showModal(
        'Confirm Cancellation',
        'Are you sure you want to cancel this order?',
        () => {
            cancelOrder(orderId);
        },
        true
    );
}

function cancelOrder(orderId) {
    fetch('includes/process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'cancel_order',
            order_id: orderId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCustomAlert('Order cancelled successfully!', 'success');
            // Reload the page to update the order status
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showCustomAlert(data.message || 'Failed to cancel order', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCustomAlert('An error occurred while cancelling order', 'error');
    });
}