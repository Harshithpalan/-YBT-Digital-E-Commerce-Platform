function addToCart(productId) {
    fetch('api/cart.php?action=add&id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateCartCount(data.cart_count);
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error adding product to cart', 'error');
        });
}

function updateCartQuantity(productId, quantity) {
    fetch('api/cart.php?action=update&id=' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'quantity=' + quantity
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error updating cart', 'error');
        });
}

function removeFromCart(productId) {
    if (confirm('Are you sure you want to remove this item?')) {
        fetch('api/cart.php?action=remove&id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateCartCount(data.cart_count);
                    location.reload();
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error removing item', 'error');
            });
    }
}

function clearCart() {
    if (confirm('Are you sure you want to clear your cart?')) {
        fetch('api/cart.php?action=clear')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateCartCount(0);
                    location.reload();
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error clearing cart', 'error');
            });
    }
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
    });
}

function showToast(message, type = 'info') {
    // Create toast element if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show`;
    toast.style.cssText = `
        margin-bottom: 10px;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;

    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    toastContainer.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Add animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// Dark Mode Toggle Logic
const themeToggle = document.getElementById('theme-toggle');
const themeToggleMobile = document.getElementById('theme-toggle-mobile');
const body = document.body;

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-mdb-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    document.documentElement.setAttribute('data-mdb-theme', newTheme);
    localStorage.setItem('theme', newTheme);

    // Update icons
    const icons = document.querySelectorAll('#theme-toggle i, #theme-toggle-mobile i');
    icons.forEach(icon => {
        icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
}

if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
if (themeToggleMobile) themeToggleMobile.addEventListener('click', toggleTheme);

// Check for saved theme and sync icons
function syncTheme() {
    const currentTheme = document.documentElement.getAttribute('data-mdb-theme') || 'light';
    const icons = document.querySelectorAll('#theme-toggle i, #theme-toggle-mobile i');
    icons.forEach(icon => {
        icon.className = currentTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
}

// Call sync on load
syncTheme();

// Initialize MDB inputs
document.querySelectorAll('.form-outline').forEach((formOutline) => {
    if (typeof mdb !== 'undefined' && mdb.Input) {
        new mdb.Input(formOutline).init();
    }
});
