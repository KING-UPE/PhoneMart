const mobileMenuOpenBtn = document.querySelectorAll('[data-mobile-menu-open-btn]');
const mobileMenu = document.querySelectorAll('[data-mobile-menu]');
const mobileMenuCloseBtn = document.querySelectorAll('[data-mobile-menu-close-btn]');
const overlay = document.querySelector('[data-overlay]');

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

// accordion variables
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

// Countdown timer for deals
function initCountdownTimers() {
    document.querySelectorAll('.countdown').forEach(timer => {
        const endDate = new Date(timer.dataset.end).getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                timer.innerHTML = '<p>Offer expired</p>';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            timer.querySelector('.days').textContent = days.toString().padStart(2, '0');
            timer.querySelector('.hours').textContent = hours.toString().padStart(2, '0');
            timer.querySelector('.minutes').textContent = minutes.toString().padStart(2, '0');
            timer.querySelector('.seconds').textContent = seconds.toString().padStart(2, '0');
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
}

// Add to cart functionality (only for index page)
function initAddToCartButtons() {
    document.querySelectorAll('.add-cart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const variantId = this.dataset.variant;
            const productName = this.closest('.showcase-content')?.querySelector('.showcase-title')?.textContent || 'Product';
            
            fetch('includes/cart_api.php?action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ variant_id: variantId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showCustomAlert(`${productName} added to cart!`, 'success');
                    // Update cart count in header
                    document.querySelectorAll('.action-btn .count').forEach(el => {
                        el.textContent = parseInt(el.textContent) + 1;
                    });
                } else {
                    showCustomAlert('Error: ' + (data.message || 'Failed to add to cart'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCustomAlert('An error occurred while adding to cart', 'error');
            });
        });
    });
}

// Initialize only what's needed for index page
document.addEventListener('DOMContentLoaded', function() {
    initCountdownTimers();
    initAddToCartButtons();
});