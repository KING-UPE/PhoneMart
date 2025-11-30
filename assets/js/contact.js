// mobile menu variables
const mobileMenuOpenBtn = document.querySelectorAll('[data-mobile-menu-open-btn]');
const mobileMenu = document.querySelectorAll('[data-mobile-menu]');
const mobileMenuCloseBtn = document.querySelectorAll('[data-mobile-menu-close-btn]');
const overlay = document.querySelector('[data-overlay]');

for (let i = 0; i < mobileMenuOpenBtn.length; i++) {
  // mobile menu function
  const mobileMenuCloseFunc = function () {
    mobileMenu[i].classList.remove('active');
    overlay.classList.remove('active');
    document.body.classList.remove('no-scroll');
  }

  mobileMenuOpenBtn[i].addEventListener('click', function () {
    mobileMenu[i].classList.add('active');
    overlay.classList.add('active');
    document.body.classList.add('no-scroll');
  });

  mobileMenuCloseBtn[i].addEventListener('click', mobileMenuCloseFunc);
  overlay.addEventListener('click', mobileMenuCloseFunc);
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

// Contact form handling
document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('.contact-form');
  
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Show loading state
      showCustomAlert('Massage Sent', 'success');
      
      // Submit the form
      const formData = new FormData(form);
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(html => {
        // Create a temporary element to parse the HTML
        const temp = document.createElement('div');
        temp.innerHTML = html;
        
        // Check if there's an alert script in the response
        const alertScript = temp.querySelector('script');
        if (alertScript) {
          // Execute the alert script
          eval(alertScript.textContent);
          
          // If success, reset the form
          if (alertScript.textContent.includes('success')) {
            form.reset();
          }
        } else {
          // Reload if something went wrong
          window.location.reload();
        }
      })
      .catch(error => {
        showCustomAlert('An error occurred. Please try again.', 'error');
      });
    });
  }
});