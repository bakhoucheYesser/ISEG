import './bootstrap.js';

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Import CSS - Tailwind will be processed via PostCSS
import './styles/app.css';

// Enhanced application initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('ISEG Student Management System - Application loaded');

    // Initialize application features
    initializeLoadingSpinner();
    initializeFlashMessages();
    initializeFormValidation();
    initializeTooltips();

    // Stimulus application debug info
    if (window.Stimulus) {
        console.log('Stimulus controllers loaded:', window.Stimulus.router.modules.length);
    }
});

// Loading spinner management
function initializeLoadingSpinner() {
    const spinner = document.getElementById('loading-spinner');

    if (!spinner) return;

    // Show spinner on form submissions
    document.addEventListener('submit', function(e) {
        // Don't show spinner for search forms or quick actions
        if (!e.target.classList.contains('no-spinner')) {
            spinner.classList.remove('hidden');
        }
    });

    // Show spinner on navigation
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (link && !link.hasAttribute('data-no-spinner') && !link.href.includes('#')) {
            setTimeout(() => {
                if (spinner) spinner.classList.remove('hidden');
            }, 100);
        }
    });

    // Hide spinner when page loads
    window.addEventListener('load', function() {
        if (spinner) spinner.classList.add('hidden');
    });

    // Turbo integration
    document.addEventListener('turbo:submit-start', function() {
        if (spinner) spinner.classList.remove('hidden');
    });

    document.addEventListener('turbo:submit-end', function() {
        if (spinner) spinner.classList.add('hidden');
    });

    document.addEventListener('turbo:visit', function() {
        if (spinner) spinner.classList.remove('hidden');
    });

    document.addEventListener('turbo:load', function() {
        if (spinner) spinner.classList.add('hidden');
    });
}

// Flash messages auto-hide
function initializeFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-messages .alert');

    flashMessages.forEach(function(message) {
        // Add close button
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '&times;';
        closeButton.className = 'ml-auto text-xl leading-none font-semibold hover:opacity-70';
        closeButton.onclick = function() {
            hideMessage(message);
        };

        message.appendChild(closeButton);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            hideMessage(message);
        }, 5000);
    });

    function hideMessage(message) {
        message.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        message.style.opacity = '0';
        message.style.transform = 'translateY(-10px)';
        setTimeout(function() {
            if (message.parentNode) {
                message.remove();
            }
        }, 300);
    }
}

// Enhanced form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                validateField(input);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

    inputs.forEach(function(input) {
        if (!validateField(input)) {
            isValid = false;
        }
    });

    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';

    // Required validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'Ce champ est obligatoire';
    }

    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Veuillez saisir un email valide';
        }
    }

    // CIN validation
    if (field.name === 'cin' && value) {
        const cinRegex = /^[0-9]{8}$/;
        if (!cinRegex.test(value)) {
            isValid = false;
            message = 'Le CIN doit contenir 8 chiffres';
        }
    }

    // Phone validation
    if (field.type === 'tel' && value) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            message = 'Numéro de téléphone invalide';
        }
    }

    // Show/hide error message
    showFieldError(field, isValid ? '' : message);

    return isValid;
}

function showFieldError(field, message) {
    // Remove existing error
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }

    // Add error styling
    if (message) {
        field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        field.classList.remove('border-gray-300');

        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    } else {
        field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        field.classList.add('border-gray-300');
    }
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');

    tooltipElements.forEach(function(element) {
        element.addEventListener('mouseenter', function() {
            showTooltip(element, element.getAttribute('data-tooltip'));
        });

        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

function showTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.id = 'tooltip';
    tooltip.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg pointer-events-none';
    tooltip.textContent = text;

    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

function hideTooltip() {
    const tooltip = document.getElementById('tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Utility functions
window.ISEG = {
    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('fr-TN', {
            style: 'currency',
            currency: 'TND'
        }).format(amount);
    },

    // Format date
    formatDate: function(date) {
        return new Intl.DateTimeFormat('fr-FR').format(new Date(date));
    },

    // Confirm action
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // Show notification
    notify: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-20 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm animate-fade-in alert-${type}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="flex-1">${message}</span>
                <button class="ml-4 text-xl leading-none hover:opacity-70" onclick="this.parentNode.parentNode.remove()">&times;</button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
};

// Global error handling
window.addEventListener('error', function(e) {
    console.error('Application error:', e.error);

    // Show user-friendly error message
    if (window.ISEG) {
        window.ISEG.notify('Une erreur inattendue s\'est produite. Veuillez réessayer.', 'error');
    }
});

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const timing = performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            console.log('Page load time:', loadTime + 'ms');
        }, 0);
    });
}
