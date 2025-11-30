document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filter form when any filter changes
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('input, select');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }

    // Status toggle functionality
    document.querySelectorAll('.status-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const messageId = this.getAttribute('data-id');
            const statusBadge = this.nextElementSibling;
            const row = this.closest('tr');
            const isChecked = this.checked;
            
            fetch('messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `toggle_status=1&message_id=${messageId}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update UI
                    statusBadge.textContent = data.new_status;
                    statusBadge.className = `status-badge ${data.status_class}`;
                    
                    // Update row styling
                    if (data.is_read) {
                        row.classList.remove('unread');
                    } else {
                        row.classList.add('unread');
                    }
                } else {
                    // Revert checkbox if error
                    this.checked = !isChecked;
                    console.error('Error updating status:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isChecked;
            });
        });
    });

    // View Message Modal
    const viewMessageBtns = document.querySelectorAll('.view-message');
    const messageModal = document.getElementById('messageModal');
    const messageSenderName = document.getElementById('messageSenderName');
    const messageSenderEmail = document.getElementById('messageSenderEmail');
    const messageDate = document.getElementById('messageDate');
    const messageStatus = document.getElementById('messageStatus');
    const messageText = document.getElementById('messageText');
    
    let currentMessageId = null;
    
    viewMessageBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const messageId = this.getAttribute('data-id');
            currentMessageId = messageId;
            
            // Fetch full message details and mark as read
            fetch('messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `view_message=1&message_id=${messageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update modal with message details
                    messageSenderName.textContent = data.message.name;
                    messageSenderEmail.textContent = data.message.email;
                    messageDate.textContent = new Date(data.message.created_at).toLocaleString();
                    messageText.textContent = data.message.message;
                    messageStatus.textContent = 'Read';
                    messageStatus.className = `status-badge ${data.status_class}`;
                    
                    // Update the row in the table
                    const row = document.querySelector(`tr[data-id="${messageId}"]`);
                    if (row) {
                        row.classList.remove('unread');
                        const checkbox = row.querySelector('.status-checkbox');
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Read';
                            statusBadge.className = `status-badge ${data.status_class}`;
                        }
                    }
                    
                    // Show the modal
                    messageModal.style.display = 'flex';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });

    // Delete Message functionality
    const deleteButtons = document.querySelectorAll('.delete-message');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            currentMessageId = this.getAttribute('data-id');
            if (deleteModal) {
                deleteModal.style.display = 'flex';
            }
        });
    });
    
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (currentMessageId) {
                const formData = new FormData();
                formData.append('delete_message', '1');
                formData.append('message_id', currentMessageId);
                
                fetch('messages.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(text => {
                    if (text) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    }

    // Delete from modal
    document.getElementById('deleteMessageBtn').addEventListener('click', function() {
        deleteModal.style.display = 'flex';
    });

    // Close modals when clicking X or outside
    const closeModalBtns = document.querySelectorAll('.close-modal');
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        });
    });
    
    const modals = [messageModal, deleteModal];
    modals.forEach(modal => {
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    });

    // Keyboard navigation for modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
        }
    });
});