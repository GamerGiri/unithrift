// assets/js/main.js
// Main client-side script for UniThrift (Theme toggle, modals, responsive nav)

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initMobileNav();
    initModals();
    initPriceCalculators();
    initAlertDismissal();
});

/* ==========================================================================
   1. Theme Switcher (Dark & Light Mode with LocalStorage)
   ========================================================================== */
function initTheme() {
    const themeToggleBtn = document.getElementById('themeToggle');
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Check saved preference or fallback to system preference
    const savedTheme = localStorage.getItem('unithrift_theme');
    const currentTheme = savedTheme || (prefersDarkScheme.matches ? 'dark' : 'light');

    applyTheme(currentTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
            localStorage.setItem('unithrift_theme', newTheme);
        });
    }
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const themeToggleBtn = document.getElementById('themeToggle');
    if (themeToggleBtn) {
        themeToggleBtn.innerHTML = theme === 'dark' ? '☀️' : '🌙';
        themeToggleBtn.setAttribute('title', theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode');
    }
}

/* ==========================================================================
   2. Responsive Mobile Navigation
   ========================================================================== */
function initMobileNav() {
    const toggleBtn = document.getElementById('mobileNavToggle');
    const navLinks = document.getElementById('navLinks');

    if (toggleBtn && navLinks) {
        toggleBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            toggleBtn.innerHTML = navLinks.classList.contains('active') ? '✕' : '☰';
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!toggleBtn.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
                toggleBtn.innerHTML = '☰';
            }
        });
    }
}

/* ==========================================================================
   3. Modal Controllers
   ========================================================================== */
function initModals() {
    // Close button triggers
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) closeModal(modal.id);
        });
    });

    // Close when clicking on backdrop
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/* ==========================================================================
   4. Live Price & Discount Calculator
   ========================================================================== */
function initPriceCalculators() {
    const origInputs = document.querySelectorAll('.calc-orig-price');
    const sellInputs = document.querySelectorAll('.calc-sell-price');

    function updateDiscount(container) {
        const origInput = container.querySelector('.calc-orig-price');
        const sellInput = container.querySelector('.calc-sell-price');
        const badge = container.querySelector('.calc-discount-badge');

        if (origInput && sellInput && badge) {
            const orig = parseFloat(origInput.value) || 0;
            const sell = parseFloat(sellInput.value) || 0;

            if (orig > 0 && sell > 0 && sell < orig) {
                const discount = Math.round(((orig - sell) / orig) * 100);
                badge.textContent = `${discount}% OFF (Buyer saves ৳${(orig - sell).toLocaleString()})`;
                badge.style.display = 'inline-block';
            } else if (sell >= orig && orig > 0) {
                badge.textContent = `Warning: Resale price should be lower than original price`;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    document.querySelectorAll('form').forEach(form => {
        const orig = form.querySelector('.calc-orig-price');
        const sell = form.querySelector('.calc-sell-price');
        if (orig && sell) {
            orig.addEventListener('input', () => updateDiscount(form));
            sell.addEventListener('input', () => updateDiscount(form));
        }
    });
}

/* ==========================================================================
   5. Auto-dismiss Flash Alerts
   ========================================================================== */
function initAlertDismissal() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
}
