document.addEventListener('DOMContentLoaded', function() {
  // Delete Product Functionality
  const deleteButtons = document.querySelectorAll('.delete-product');
  const deleteModal = document.getElementById('deleteModal');
  const confirmDeleteBtn = document.getElementById('confirmDelete');
  
  let productIdToDelete = null;
  
  deleteButtons.forEach(button => {
    button.addEventListener('click', function() {
      productIdToDelete = this.getAttribute('data-id');
      
      // Get current page number to return to after deletion
      const urlParams = new URLSearchParams(window.location.search);
      const currentPage = urlParams.get('page') || 1;
      
      // Store current page in the delete button
      this.setAttribute('data-current-page', currentPage);
      
      deleteModal.style.display = 'flex';
    });
  });
    
  
  deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) {
      deleteModal.style.display = 'none';
    }
  });
  
  // Confirm delete action - now preserves pagination
  confirmDeleteBtn.addEventListener('click', function() {
    if (productIdToDelete) {
      // Get the button that triggered the delete
      const deleteButton = document.querySelector(`.delete-product[data-id="${productIdToDelete}"]`);
      const currentPage = deleteButton.getAttribute('data-current-page') || 1;
      
      // Redirect with page parameter
      window.location.href = `../includes/delete_product.php?id=${productIdToDelete}&return_page=${currentPage}`;
    }
  });

  // Enhanced Filter Functionality
  const brandFilter = document.querySelector('.table-controls select:first-child');
  const categoryFilter = document.querySelector('.table-controls select:last-child');

  function applyFilters() {
      const brandValue = brandFilter.value;
      const categoryValue = categoryFilter.value;
      const rows = document.querySelectorAll('tbody tr');
      
      // Show loading indicator
      document.getElementById('productsTable').classList.add('loading');
      
      setTimeout(() => {
          rows.forEach(row => {
              const rowBrand = row.querySelector('td:nth-child(2)').textContent.trim();
              const rowCategory = row.querySelector('td:nth-child(3)').textContent.trim();
              
              // Brand filter logic - matches if "All Brands" or specific brand
              const brandMatch = brandValue === '' || brandValue === 'All Brands' || rowBrand === brandValue;
              
              // Category filter logic - matches if "All Categories" or specific category
              const categoryMatch = categoryValue === '' || categoryValue === 'All Categories' || rowCategory === categoryValue;
              
              // Show row if both filters match (AND logic)
              if (brandMatch && categoryMatch) {
                  row.style.display = '';
              } else {
                  row.style.display = 'none';
              }
          });
          
          // Hide loading indicator
          document.getElementById('productsTable').classList.remove('loading');
          
          // Update the "Showing X-Y of Z products" text
          updateProductCount();
      }, 100); // Small delay for better UX
  }

  // Update the product count display
  function updateProductCount() {
      const visibleRows = document.querySelectorAll('tbody tr:not([style*="display: none"])');
      const totalProducts = document.querySelectorAll('tbody tr').length;
      const paginationInfo = document.querySelector('.pagination-info');
      
      if (paginationInfo) {
          paginationInfo.textContent = `Showing ${visibleRows.length} of ${totalProducts} products`;
      }
  }

  // Add event listeners
  brandFilter.addEventListener('change', function() {
      // Reset to first page when filtering
      const url = new URL(window.location.href);
      url.searchParams.set('page', '1');
      window.history.pushState({}, '', url);
      applyFilters();
  });
  
  categoryFilter.addEventListener('change', function() {
      // Reset to first page when filtering
      const url = new URL(window.location.href);
      url.searchParams.set('page', '1');
      window.history.pushState({}, '', url);
      applyFilters();
  });

  // Initialize filters from URL parameters if present
  function initializeFiltersFromURL() {
      const urlParams = new URLSearchParams(window.location.search);
      const brandParam = urlParams.get('brand');
      const categoryParam = urlParams.get('category');
      
      if (brandParam) {
          brandFilter.value = brandParam;
      }
      if (categoryParam) {
          categoryFilter.value = categoryParam;
      }
      
      applyFilters();
  }

  // Call the initialization function
  initializeFiltersFromURL();

  // Offer Modal Functionality
  const offerModal = document.getElementById('offerModal');
  const openOfferModalBtn = document.getElementById('openOfferModalBtn');
  
  // Form validation for offer form
  const offerForm = document.getElementById('offerForm');
  
  if (offerForm) {
    offerForm.addEventListener('submit', function(e) {
      const variantId = document.getElementById('offerVariantId').value;
      const discount = document.getElementById('offerDiscount').value;
      const endDate = document.getElementById('offerEndDate').value;
      
      // Validate inputs
      if (!variantId || !discount || !endDate) {
        alert('Please fill all fields');
        e.preventDefault();
        return false;
      }
      
      if (discount < 1 || discount > 100) {
        alert('Discount must be between 1% and 100%');
        e.preventDefault();
        return false;
      }
      
      const today = new Date();
      const selectedDate = new Date(endDate);
      
      if (selectedDate < today) {
        alert('End date must be in the future');
        e.preventDefault();
        return false;
      }
      
      // If everything is valid, form will submit normally
      return true;
    });
  }
  
  // Open Offer Modal
  if (openOfferModalBtn) {
    openOfferModalBtn.addEventListener('click', function() {
      // Set minimum date to today
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('offerEndDate').min = today;
      
      // Clear previous values
      document.getElementById('offerForm').reset();
      
      offerModal.style.display = 'flex';
    });
  }

  // Close modal when clicking X, Cancel, or outside
  const closeModalBtns = document.querySelectorAll('.close-modal');
  closeModalBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
      });
    });
  });
  
  // Close when clicking outside modal
  if (offerModal) {
    offerModal.addEventListener('click', function(e) {
      if (e.target === offerModal) {
        offerModal.style.display = 'none';
      }
    });
  }
});