document.addEventListener('DOMContentLoaded', function() {
  // DOM Elements
  const addBrandBtn = document.getElementById('addBrandBtn');
  const addBrandModal = document.getElementById('addBrandModal');
  const editBrandModal = document.getElementById('editBrandModal');
  const deleteBrandModal = document.getElementById('deleteBrandModal');
  const closeModalBtns = document.querySelectorAll('.close-modal');
  const brandNameInput = document.getElementById('brandName');
  const editBrandNameInput = document.getElementById('editBrandName');
  const editBrandIdInput = document.getElementById('editBrandId');
  const deleteBrandIdInput = document.getElementById('deleteBrandId');
  const brandsContainer = document.getElementById('brandsContainer');
  const deleteOptions = document.getElementById('deleteOptions');
  const productCountSpan = document.getElementById('productCount');
  const deleteWarningText = document.getElementById('deleteWarningText');

  // Open Add modal when Add Brand button is clicked
  addBrandBtn.addEventListener('click', function() {
    addBrandModal.style.display = 'flex';
    brandNameInput.focus();
  });

  // Handle Edit button clicks using event delegation
  brandsContainer.addEventListener('click', function(e) {
    if (e.target.closest('.btn-edit')) {
      const btn = e.target.closest('.btn-edit');
      const brandCard = btn.closest('.category-card');
      const brandName = brandCard.querySelector('.category-name').textContent;
      const brandId = btn.getAttribute('data-id');
      
      editBrandNameInput.value = brandName;
      editBrandIdInput.value = brandId;
      editBrandModal.style.display = 'flex';
      editBrandNameInput.focus();
    }
    
    // Handle Delete button clicks
    if (e.target.closest('.btn-delete')) {
      const btn = e.target.closest('.btn-delete');
      const brandId = btn.getAttribute('data-id');
      const productCount = parseInt(btn.getAttribute('data-count'));
      
      deleteBrandIdInput.value = brandId;
      
      // Show appropriate options based on product count
      if (productCount > 0) {
        deleteWarningText.textContent = 'This brand contains products. Choose how to proceed:';
        deleteOptions.style.display = 'block';
        productCountSpan.textContent = productCount;
      } else {
        deleteWarningText.textContent = 'Are you sure you want to delete this brand?';
        deleteOptions.style.display = 'none';
      }
      
      deleteBrandModal.style.display = 'flex';
    }
  });

  // Enable/disable brand dropdown based on radio selection
  document.querySelectorAll('input[name="delete_action"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const brandSelect = document.querySelector('select[name="new_brand_id"]');
      brandSelect.disabled = this.value !== 'move';
    });
  });

  // Close any modal when X or Cancel is clicked
  closeModalBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
      });
    });
  });

  // Close modal when clicking outside the modal content
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
  });

  // Form validation
  document.getElementById('addBrandForm').addEventListener('submit', function(e) {
    if (!brandNameInput.value.trim()) {
      e.preventDefault();
      alert('Please enter a brand name');
      brandNameInput.focus();
    }
  });

  document.getElementById('editBrandForm').addEventListener('submit', function(e) {
    if (!editBrandNameInput.value.trim()) {
      e.preventDefault();
      alert('Please enter a brand name');
      editBrandNameInput.focus();
    }
  });
});