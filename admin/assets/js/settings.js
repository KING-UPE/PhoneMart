document.addEventListener('DOMContentLoaded', function() {
    // Initialize toggles based on current state
    const maintenanceToggle = document.getElementById('maintenanceToggle');
    const adminOnlyToggle = document.getElementById('adminOnlyToggle');
    
    // Maintenance Mode Toggle
    if (maintenanceToggle) {
        const maintenanceStatus = document.getElementById('maintenanceStatus');
        const maintenanceMessageBox = document.getElementById('maintenanceMessageBox');
        
        // Set initial state
        maintenanceStatus.textContent = maintenanceToggle.checked ? 
            "Website is UNDER MAINTENANCE" : "Website is LIVE";
        maintenanceMessageBox.style.display = maintenanceToggle.checked ? "block" : "none";
        
        // Add change event listener
        maintenanceToggle.addEventListener('change', function() {
            const isMaintenance = this.checked;
            maintenanceStatus.textContent = 
                isMaintenance ? "Website is UNDER MAINTENANCE" : "Website is LIVE";
            maintenanceMessageBox.style.display = 
                isMaintenance ? "block" : "none";
        });
    }

    // Admin-Only Mode Toggle
    if (adminOnlyToggle) {
        // Initial state is set by PHP, no need for additional JS here
        adminOnlyToggle.addEventListener('change', function() {
            console.log("Admin-only mode:", this.checked);
        });
    }

    // Clear Cache Button (keep as is)
    const clearCacheBtn = document.getElementById('clearCacheBtn');
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', function(e) {
            if (!confirm("Are you sure you want to clear all cache?")) {
                e.preventDefault();
            }
        });
    }

// Remove the resetDemoDataBtn code completely since we're disabling it

    // Reset Demo Data Button
    const resetDemoDataBtn = document.getElementById('resetDemoDataBtn');
    if (resetDemoDataBtn) {
        resetDemoDataBtn.addEventListener('click', function(e) {
            if (!confirm("WARNING: This will delete all demo orders and products. Continue?")) {
                e.preventDefault();
            }
        });
    }
});