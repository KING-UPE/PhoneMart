function showCustomAlert(message, type = 'info') {
    // Create alert element if it doesn't exist
    let alert = document.querySelector('.custom-alert');
    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'custom-alert hide';
        alert.innerHTML = `
            <i class="fas fa-info-circle"></i>
            <span class="alert-msg">${message}</span>
            <div class="close-btn">
                <i class="fas fa-times"></i>
            </div>
        `;
        document.body.appendChild(alert);
    }

    const icon = alert.querySelector('i');
    const msg = alert.querySelector('.alert-msg');
    
    // Set message
    msg.textContent = message;
    
    // Set icon based on type
    if (type === 'error') {
        icon.className = 'fas fa-exclamation-circle';
        alert.style.backgroundColor = '#ffebee';
        alert.style.borderLeft = '4px solid #f44336';
    } else if (type === 'success') {
        icon.className = 'fas fa-check-circle';
        alert.style.backgroundColor = '#e8f5e9';
        alert.style.borderLeft = '4px solid #4caf50';
    } else {
        icon.className = 'fas fa-info-circle';
        alert.style.backgroundColor = '#e3f2fd';
        alert.style.borderLeft = '4px solid #2196f3';
    }
    
    // Set type (changes color)
    alert.className = 'custom-alert'; // Reset classes
    alert.classList.add(type ? `alert-${type}` : '');
    
    // Show alert
    alert.classList.add('show');
    alert.classList.add('showAlert');
    alert.classList.remove('hide');
    
    // Auto-hide after 5 seconds
    const timeout = setTimeout(() => {
        alert.classList.remove('show');
        alert.classList.add('hide');
    }, 5000);
    
    // Close button functionality
    const closeBtn = alert.querySelector('.close-btn');
    closeBtn.onclick = function() {
        clearTimeout(timeout);
        alert.classList.remove('show');
        alert.classList.add('hide');
    };
}

// Override default alert
window.alert = function(message) {
    showCustomAlert(message);
};