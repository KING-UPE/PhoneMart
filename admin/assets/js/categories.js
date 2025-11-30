document.addEventListener('DOMContentLoaded', function() {
  // DOM Elements
  const addCategoryBtn = document.getElementById('addCategoryBtn');
  const addCategoryModal = document.getElementById('addCategoryModal');
  const editCategoryModal = document.getElementById('editCategoryModal');
  const deleteCategoryModal = document.getElementById('deleteCategoryModal');
  const closeModalBtns = document.querySelectorAll('.close-modal');
  const categoryNameInput = document.getElementById('categoryName');
  const editCategoryNameInput = document.getElementById('editCategoryName');
  const editCategoryIdInput = document.getElementById('editCategoryId');
  const deleteCategoryIdInput = document.getElementById('deleteCategoryId');
  const categoriesContainer = document.getElementById('categoriesContainer');
  const deleteOptions = document.getElementById('deleteOptions');
  const productCountSpan = document.getElementById('productCount');
  const deleteWarningText = document.getElementById('deleteWarningText');

  // Open Add modal when Add Category button is clicked
  addCategoryBtn.addEventListener('click', function() {
    addCategoryModal.style.display = 'flex';
    categoryNameInput.focus();
  });

  // Handle Edit button clicks using event delegation
  categoriesContainer.addEventListener('click', function(e) {
    if (e.target.closest('.btn-edit')) {
      const btn = e.target.closest('.btn-edit');
      const categoryCard = btn.closest('.category-card');
      const categoryName = categoryCard.querySelector('.category-name').textContent;
      const categoryId = btn.getAttribute('data-id');
      
      editCategoryNameInput.value = categoryName;
      editCategoryIdInput.value = categoryId;
      editCategoryModal.style.display = 'flex';
      editCategoryNameInput.focus();
    }
    
    // Handle Delete button clicks
    if (e.target.closest('.btn-delete')) {
      const btn = e.target.closest('.btn-delete');
      const categoryId = btn.getAttribute('data-id');
      const productCount = parseInt(btn.getAttribute('data-count'));
      
      deleteCategoryIdInput.value = categoryId;
      
      // Show appropriate options based on product count
      if (productCount > 0) {
        deleteWarningText.textContent = 'This category contains products. Choose how to proceed:';
        deleteOptions.style.display = 'block';
        productCountSpan.textContent = productCount;
      } else {
        deleteWarningText.textContent = 'Are you sure you want to delete this category?';
        deleteOptions.style.display = 'none';
      }
      
      deleteCategoryModal.style.display = 'flex';
    }
  });

  // Enable/disable category dropdown based on radio selection
  document.querySelectorAll('input[name="delete_action"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const categorySelect = document.querySelector('select[name="new_category_id"]');
      categorySelect.disabled = this.value !== 'move';
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
  document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    if (!categoryNameInput.value.trim()) {
      e.preventDefault();
      alert('Please enter a category name');
      categoryNameInput.focus();
    }
  });

  document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
    if (!editCategoryNameInput.value.trim()) {
      e.preventDefault();
      alert('Please enter a category name');
      editCategoryNameInput.focus();
    }
  });
});