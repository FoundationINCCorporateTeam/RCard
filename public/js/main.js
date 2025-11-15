// RCard Main JavaScript

const API_URL = '/api.php';
let isRegistering = false;

/**
 * Show login modal
 */
function showLoginModal() {
    document.getElementById('loginModal').classList.remove('hidden');
    document.getElementById('loginModal').classList.add('flex');
}

/**
 * Hide login modal
 */
function hideLoginModal() {
    document.getElementById('loginModal').classList.add('hidden');
    document.getElementById('loginModal').classList.remove('flex');
    document.getElementById('errorMessage').classList.add('hidden');
}

/**
 * Toggle between login and register
 */
function toggleRegister() {
    isRegistering = !isRegistering;
    const title = document.getElementById('modalTitle');
    const button = document.querySelector('#loginForm button');
    const toggleBtn = document.querySelector('#loginForm .text-purple-400');
    
    if (isRegistering) {
        title.textContent = 'Register';
        button.textContent = 'Register';
        button.setAttribute('onclick', 'handleRegister()');
        toggleBtn.textContent = 'Already have an account? Login';
    } else {
        title.textContent = 'Login';
        button.textContent = 'Login';
        button.setAttribute('onclick', 'handleLogin()');
        toggleBtn.textContent = "Don't have an account? Register";
    }
}

/**
 * Handle user login
 */
async function handleLogin() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        showError('Please enter username and password');
        return;
    }
    
    try {
        const response = await fetch(API_URL + '?action=auth_login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                username: username,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // Store user data
            localStorage.setItem('rcard_user', JSON.stringify(data.data.user));
            
            // Redirect to dashboard
            window.location.href = '/dashboard/home.php';
        } else {
            showError(data.error || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        showError('Network error. Please try again.');
    }
}

/**
 * Handle user registration
 */
async function handleRegister() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        showError('Please enter username and password');
        return;
    }
    
    if (username.length < 3 || username.length > 30) {
        showError('Username must be between 3 and 30 characters');
        return;
    }
    
    if (password.length < 6) {
        showError('Password must be at least 6 characters');
        return;
    }
    
    try {
        const response = await fetch(API_URL + '?action=auth_register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                username: username,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // Store user data
            localStorage.setItem('rcard_user', JSON.stringify(data.data.user));
            
            // Redirect to dashboard
            window.location.href = '/dashboard/home.php';
        } else {
            showError(data.error || 'Registration failed');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showError('Network error. Please try again.');
    }
}

/**
 * Show error message
 */
function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden');
}

/**
 * API call helper
 */
async function apiCall(action, data = {}) {
    const params = new URLSearchParams({ action, ...data });
    
    try {
        const response = await fetch(API_URL + '?' + params.toString(), {
            credentials: 'include'
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API call error:', error);
        return { status: 'error', error: 'Network error' };
    }
}

/**
 * API POST helper
 */
async function apiPost(action, data = {}) {
    data.action = action;
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data),
            credentials: 'include'
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API POST error:', error);
        return { status: 'error', error: 'Network error' };
    }
}

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'R$') {
    return currency + parseFloat(amount).toFixed(2);
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 alert alert-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Auto-focus on username field when modal opens
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('loginModal');
    const usernameField = document.getElementById('username');
    
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                if (!modal.classList.contains('hidden')) {
                    setTimeout(() => usernameField.focus(), 100);
                }
            }
        });
    });
    
    observer.observe(modal, { attributes: true });
});
