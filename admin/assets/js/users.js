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

    // Show banned users modal
    const viewBannedUsersBtn = document.getElementById('viewBannedUsersBtn');
    if (viewBannedUsersBtn) {
        viewBannedUsersBtn.addEventListener('click', function() {
            document.getElementById('bannedUsersModal').style.display = 'flex';
        });
    }

    // Ban user button
    document.querySelectorAll('.ban-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            document.getElementById('banUserId').value = userId;
            document.getElementById('banUserModal').style.display = 'flex';
        });
    });

    // Unban user button (in main table)
    document.querySelectorAll('.unban-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to unban this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = 'csrf_token';
                csrf.value = document.querySelector('input[name="csrf_token"]').value;
                
                const userId = document.createElement('input');
                userId.type = 'hidden';
                userId.name = 'user_id';
                userId.value = this.getAttribute('data-user-id');
                
                const unban = document.createElement('input');
                unban.type = 'hidden';
                unban.name = 'unban_user';
                unban.value = '1';
                
                form.appendChild(csrf);
                form.appendChild(userId);
                form.appendChild(unban);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Delete user button
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserModal').style.display = 'flex';
        });
    });

    // Close modals
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        });
    });
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
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