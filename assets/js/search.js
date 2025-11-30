// --- Fixed and Optimized search.js ---

const mobileMenuOpenBtn = document.querySelectorAll('[data-mobile-menu-open-btn]');
const mobileMenu = document.querySelectorAll('[data-mobile-menu]');
const mobileMenuCloseBtn = document.querySelectorAll('[data-mobile-menu-close-btn]');
const overlay = document.querySelector('[data-overlay]');

// Mobile menu
for (let i = 0; i < mobileMenuOpenBtn.length; i++) {
  mobileMenuOpenBtn[i].addEventListener('click', function () {
    const target = this.dataset.target;
    if (target) {
      document.querySelector(target).classList.add('active');
      overlay.classList.add('active');
      document.body.classList.add('no-scroll');
    }
  });

  if (mobileMenuCloseBtn[i]) {
    mobileMenuCloseBtn[i].addEventListener('click', function() {
      mobileMenu[i].classList.remove('active');
      overlay.classList.remove('active');
      document.body.classList.remove('no-scroll');
    });
  }
}

if (overlay) {
  overlay.addEventListener('click', function() {
    document.querySelectorAll('[data-mobile-menu]').forEach(menu => menu.classList.remove('active'));
    this.classList.remove('active');
    document.body.classList.remove('no-scroll');
  });
}

// Accordion
const accordionBtn = document.querySelectorAll('[data-accordion-btn]');
const accordion = document.querySelectorAll('[data-accordion]');

for (let i = 0; i < accordionBtn.length; i++) {
  accordionBtn[i].addEventListener('click', function () {
    const clickedBtn = this.nextElementSibling.classList.contains('active');
    for (let i = 0; i < accordion.length; i++) {
      if (clickedBtn) break;
      if (accordion[i].classList.contains('active')) {
        accordion[i].classList.remove('active');
        accordionBtn[i].classList.remove('active');
      }
    }
    this.nextElementSibling.classList.toggle('active');
    this.classList.toggle('active');
  });
}

// Price range
let sliderOne = document.getElementById("slider-1");
let sliderTwo = document.getElementById("slider-2");
let displayValOne = document.getElementById("range1");
let displayValTwo = document.getElementById("range2");
let sliderTrack = document.querySelector(".slider-track-1");
let sliderThree = document.getElementById("slider-3");
let sliderFour = document.getElementById("slider-4");
let displayValThree = document.getElementById("range3");
let displayValFour = document.getElementById("range4");
let sliderTrackTwo = document.querySelector(".slider-track-2");
let minGap = 0;
let minValue = parseInt(sliderOne?.min || 0 && sliderThree?.min || 0);
let maxValue = parseInt(sliderOne?.max || 500000 && sliderThree?.max || 500000);

function syncCheckboxes(name, value, isChecked) {
  document.querySelectorAll(`input[name="${name}"][value="${value}"]`).forEach(checkbox => {
    checkbox.checked = isChecked;
  });
}

function initializeSlidersFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  const minPrice = urlParams.get('min_price');
  const maxPrice = urlParams.get('max_price');
  if (sliderOne && sliderTwo) {
    sliderOne.value = minPrice || sliderOne.min;
    sliderTwo.value = maxPrice || sliderTwo.max;
    displayValOne.textContent = "LKR " + parseInt(sliderOne.value).toLocaleString();
    displayValTwo.textContent = "LKR " + parseInt(sliderTwo.value).toLocaleString();
    fillColor();
  }
  if (sliderThree && sliderFour) {
    sliderThree.value = minPrice || sliderThree.min;
    sliderFour.value = maxPrice || sliderFour.max;
    displayValThree.textContent = "LKR " + parseInt(sliderThree.value).toLocaleString();
    displayValFour.textContent = "LKR " + parseInt(sliderFour.value).toLocaleString();
    fillColorTwo();
  }
}

function slideOne() {
  if (!sliderOne || !sliderTwo) return;
  if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
    sliderOne.value = parseInt(sliderTwo.value) - minGap;
  }
  displayValOne.textContent = "LKR " + parseInt(sliderOne.value).toLocaleString();
  fillColor();
}

function slideTwo() {
  if (!sliderOne || !sliderTwo) return;
  if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
    sliderTwo.value = parseInt(sliderOne.value) + minGap;
  }
  displayValTwo.textContent = "LKR " + parseInt(sliderTwo.value).toLocaleString();
  fillColor();
}

function slideThree() {
  if (!sliderThree || !sliderFour) return;
  if (parseInt(sliderFour.value) - parseInt(sliderThree.value) <= minGap) {
    sliderThree.value = parseInt(sliderFour.value) - minGap;
  }
  displayValThree.textContent = "LKR " + parseInt(sliderThree.value).toLocaleString();
  fillColorTwo();
}

function slideFour() {
  if (!sliderOne || !sliderTwo) return;
  if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
    sliderTwo.value = parseInt(sliderOne.value) + minGap;
  }
  displayValFour.textContent = "LKR " + parseInt(sliderFour.value).toLocaleString();
  fillColorTwo();
}

function fillColor() {
  if (!sliderOne || !sliderTwo || !sliderTrack) return;
  let percent1 = ((sliderOne.value - minValue) / (maxValue - minValue)) * 100;
  let percent2 = ((sliderTwo.value - minValue) / (maxValue - minValue)) * 100;
  sliderTrack.style.background = `linear-gradient(to right, #dadae5 ${percent1}%, #3264fe ${percent1}%, #3264fe ${percent2}%, #dadae5 ${percent2}%)`;
}

//if not working change this
function fillColorTwo() {
  if (!sliderThree || !sliderFour || !sliderTrackTwo) return;
  let percent3 = ((sliderThree.value - minValue) / (maxValue - minValue)) * 100;
  let percent4 = ((sliderFour.value - minValue) / (maxValue - minValue)) * 100;
  sliderTrackTwo.style.background = `linear-gradient(to right, #dadae5 ${percent3}%, #3264fe ${percent3}%, #3264fe ${percent4}%, #dadae5 ${percent4}%)`;
}

function initializeCheckboxesFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  const categoryParam = urlParams.get('category');
  if (categoryParam) {
    const selectedCategories = categoryParam.split(',');
    document.querySelectorAll('input[name="category"]').forEach(checkbox => {
      checkbox.checked = selectedCategories.includes(checkbox.value);
    });
  }
  const brandParam = urlParams.get('brand');
  if (brandParam) {
    const selectedBrands = brandParam.split(',');
    document.querySelectorAll('input[name="brand"]').forEach(checkbox => {
      checkbox.checked = selectedBrands.includes(checkbox.value);
    });
  }
}

function applyFilters() {
  const url = new URL(window.location.href);
  const searchParams = new URLSearchParams();
  const searchQuery = url.searchParams.get('search');
  if (searchQuery) searchParams.set('search', searchQuery);

  // Check the page width
  const pageWidth = window.innerWidth;

  if (pageWidth < 1024) {
    // Run the sliderOne and sliderTwo logic
    if (sliderOne && sliderTwo) {
      const minPrice = sliderOne.value;
      const maxPrice = sliderTwo.value;
      if (minPrice > 0 || maxPrice < maxValue) {
        searchParams.set('min_price', minPrice);
        searchParams.set('max_price', maxPrice);
      }
    }
  } else {
    // Run the sliderThree and sliderFour logic
    if (sliderThree && sliderFour) {
      const minPrice = sliderThree.value;
      const maxPrice = sliderFour.value;
      if (minPrice > 0 || maxPrice < maxValue) {
        searchParams.set('min_price', minPrice);
        searchParams.set('max_price', maxPrice);
      }
    }
  }

  // Handle category filters
  const categoryCheckboxes = document.querySelectorAll('input[name="category"]:checked');
  const categories = Array.from(categoryCheckboxes).map(cb => cb.value);
  if (categories.length > 0) searchParams.set('category', categories.join(','));

  // Handle brand filters
  const brandCheckboxes = document.querySelectorAll('input[name="brand"]:checked');
  const brands = Array.from(brandCheckboxes).map(cb => cb.value);
  if (brands.length > 0) searchParams.set('brand', brands.join(','));

  // Update the URL with new search parameters
  window.location.search = searchParams.toString();
}


function clearFilters() {
  const url = new URL(window.location.href);
  const searchParams = new URLSearchParams();
  const searchQuery = url.searchParams.get('search');
  if (searchQuery) searchParams.set('search', searchQuery);
  document.querySelectorAll('input[name="category"], input[name="brand"]').forEach(cb => cb.checked = false);
  if (sliderOne && sliderTwo) {
    sliderOne.value = sliderOne.min;
    sliderTwo.value = sliderTwo.max;
    slideOne();
    slideTwo();
  }
  if (sliderThree && sliderFour) {
    sliderThree.value = sliderThree.min;
    sliderFour.value = sliderFour.max;
    slideThree();
    slideFour();
  }
  window.location.search = searchParams.toString();
}

document.addEventListener('DOMContentLoaded', function() {
  initializeSlidersFromURL();
  initializeCheckboxesFromURL();

  if (sliderOne && sliderTwo) {
    sliderOne.addEventListener('input', slideOne);
    sliderTwo.addEventListener('input', slideTwo);
    sliderOne.addEventListener('change', () => setTimeout(applyFilters, 100));
    sliderTwo.addEventListener('change', () => setTimeout(applyFilters, 100));
  }
  if (sliderThree && sliderFour) {
    sliderThree.addEventListener('input', slideThree);
    sliderFour.addEventListener('input', slideFour);
    sliderThree.addEventListener('change', () => setTimeout(applyFilters, 100));
    sliderFour.addEventListener('change', () => setTimeout(applyFilters, 100));
  }

  document.querySelectorAll('input[name="category"], input[name="brand"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      setTimeout(() => applyFilters(), 100);
      syncCheckboxes(this.name, this.value, this.checked);
    });
  });

  document.querySelector('.clear-filters-btn')?.addEventListener('click', clearFilters);

    initWishlistButtons();
    displaySearchedKeyword();
});

// Wishlist functionality
function initWishlistButtons() {
    document.querySelectorAll('.btn-action[data-variant]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const variantId = this.dataset.variant;
            const isInWishlist = this.querySelector('ion-icon').getAttribute('name') === 'heart';
            const action = isInWishlist ? 'remove' : 'add';
            
            fetch(`/PHONE_MART/includes/add_to_wishlist.php?action=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ variant_id: variantId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = btn.querySelector('ion-icon');
                    const newIconName = isInWishlist ? 'heart-outline' : 'heart';
                    const message = isInWishlist ? 'Removed from wishlist' : 'Added to wishlist';
                    
                    icon.setAttribute('name', newIconName);
                    btn.setAttribute('aria-label', isInWishlist ? 'Add to wishlist' : 'Remove from wishlist');
                    
                    showCustomAlert(message, 'success');
                } else {
                    showCustomAlert(data.message || 'Error updating wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCustomAlert('An error occurred', 'error');
            });
        });
    });
}



// Display searched keyword in search box
function displaySearchedKeyword() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('search');
    
    if (searchQuery) {
        const searchInput = document.querySelector('.header-search-container .search-field');
        if (searchInput) {
            searchInput.value = decodeURIComponent(searchQuery);
        }
    }
}
