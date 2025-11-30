const sign_in_btn = document.querySelector("#sign-in-btn");
const sign_up_btn = document.querySelector("#sign-up-btn");
const container = document.querySelector(".container");
const logoImg = document.getElementById("logo-img");

// Handle URL parameters to show appropriate form
const urlParams = new URLSearchParams(window.location.search);
const formParam = urlParams.get('form');

if (formParam === 'register') {
    container.classList.add("sign-up-mode");
    logoImg.src = "assets/images/logo/logo.svg";
}

sign_up_btn.addEventListener("click", () => {
    container.classList.add("sign-up-mode");
    logoImg.src = "assets/images/logo/logo.svg";
    
    // Clear URL parameters
    const url = new URL(window.location.href);
    url.searchParams.delete('error');
    url.searchParams.delete('form');
    window.history.replaceState({}, '', url);
});

sign_in_btn.addEventListener("click", () => {
    container.classList.remove("sign-up-mode");
    logoImg.src = "assets/images/logo/logo-light.svg";
    
    // Clear URL parameters
    const url = new URL(window.location.href);
    url.searchParams.delete('error');
    url.searchParams.delete('form');
    window.history.replaceState({}, '', url);
});

// Display error messages from URL parameters (except ban errors)
document.addEventListener('DOMContentLoaded', () => {
    const error = urlParams.get('error');
    if (error && error !== 'banned') {
        let errorMessage = '';
        let alertType = 'error';
        
        switch(error) {
            case 'empty_fields':
                errorMessage = 'Please fill all fields';
                break;
            case 'invalid_credentials':
                errorMessage = 'Invalid email or password';
                break;
            case 'password_mismatch':
                errorMessage = 'Passwords do not match';
                break;
            case 'password_length':
                errorMessage = 'Password must be at least 8 characters';
                break;
            case 'email_exists':
                errorMessage = 'Email already registered';
                break;
            case 'registration_failed':
                errorMessage = 'Registration failed. Please try again.';
                break;
            default:
                errorMessage = 'An error occurred';
        }
        
        showCustomAlert(errorMessage, alertType);
    }
});