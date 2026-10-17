/**
 * Tulima Solar - Contact Page JavaScript
 * Form validation, character counter, and interactivity
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    // =========================================================================
    // DOM Elements
    // =========================================================================
    
    const contactForm = document.querySelector('.c-contact-form');
    const messageTextarea = document.getElementById('message');
    const charCounter = document.getElementById('char-count');
    const phoneInput = document.getElementById('phone');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const serviceSelect = document.getElementById('service_interest');
    
    // =========================================================================
    // Configuration
    // =========================================================================
    
    const config = {
        maxMessageLength: 5000,
        phonePattern: /^[\d\s\+\-\(\)]+$/,
        emailPattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        minNameLength: 2,
        maxNameLength: 255,
        debounceDelay: 300
    };
    
    // =========================================================================
    // Utility Functions
    // =========================================================================
    
    /**
     * Debounce function to limit execution rate
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    function sanitizeInput(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    /**
     * Format phone number as user types
     */
    function formatPhoneNumber(value) {
        // Remove all non-digit characters except + at the start
        const cleaned = value.replace(/[^\d+]/g, '');
        
        // Ensure only one + at the start
        const formatted = cleaned.replace(/(?!^)\+/g, '');
        
        return formatted;
    }
    
    /**
     * Show error for a field
     */
    function showFieldError(field, message) {
        const formGroup = field.closest('.c-form__group');
        if (!formGroup) return;
        
        // Remove existing error
        clearFieldError(field);
        
        // Add error class
        formGroup.classList.add('c-form__group--error');
        
        // Create error element
        const errorEl = document.createElement('span');
        errorEl.className = 'c-form__error';
        errorEl.setAttribute('role', 'alert');
        errorEl.textContent = message;
        
        // Insert after the field
        const textareaFooter = field.closest('.c-form__textarea-footer');
        if (textareaFooter) {
            textareaFooter.insertBefore(errorEl, textareaFooter.firstChild);
        } else {
            field.parentNode.insertBefore(errorEl, field.nextSibling);
        }
        
        // Update ARIA
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', field.id + '-error');
        errorEl.id = field.id + '-error';
    }
    
    /**
     * Clear error for a field
     */
    function clearFieldError(field) {
        const formGroup = field.closest('.c-form__group');
        if (!formGroup) return;
        
        // Remove error class
        formGroup.classList.remove('c-form__group--error');
        
        // Remove error message
        const existingError = formGroup.querySelector('.c-form__error');
        if (existingError) {
            existingError.remove();
        }
        
        // Update ARIA
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    }
    
    /**
     * Show success state for a field
     */
    function showFieldSuccess(field) {
        const formGroup = field.closest('.c-form__group');
        if (!formGroup) return;
        
        clearFieldError(field);
        formGroup.classList.add('c-form__group--success');
    }
    
    /**
     * Clear success state for a field
     */
    function clearFieldSuccess(field) {
        const formGroup = field.closest('.c-form__group');
        if (!formGroup) return;
        
        formGroup.classList.remove('c-form__group--success');
    }
    
    // =========================================================================
    // Validation Functions
    // =========================================================================
    
    /**
     * Validate name field
     */
    function validateName(field) {
        const value = field.value.trim();
        
        if (!value) {
            showFieldError(field, 'Please enter your full name.');
            return false;
        }
        
        if (value.length < config.minNameLength) {
            showFieldError(field, `Name must be at least ${config.minNameLength} characters.`);
            return false;
        }
        
        if (value.length > config.maxNameLength) {
            showFieldError(field, `Name must be less than ${config.maxNameLength} characters.`);
            return false;
        }
        
        clearFieldError(field);
        return true;
    }
    
    /**
     * Validate email field
     */
    function validateEmail(field) {
        const value = field.value.trim();
        
        if (!value) {
            showFieldError(field, 'Please enter your email address.');
            return false;
        }
        
        if (!config.emailPattern.test(value)) {
            showFieldError(field, 'Please enter a valid email address.');
            return false;
        }
        
        clearFieldError(field);
        return true;
    }
    
    /**
     * Validate phone field (optional)
     */
    function validatePhone(field) {
        const value = field.value.trim();
        
        // Phone is optional, so empty is valid
        if (!value) {
            clearFieldError(field);
            return true;
        }
        
        if (!config.phonePattern.test(value)) {
            showFieldError(field, 'Please enter a valid phone number.');
            return false;
        }
        
        // Check minimum length (at least 8 digits for a valid phone)
        const digitsOnly = value.replace(/\D/g, '');
        if (digitsOnly.length < 8) {
            showFieldError(field, 'Phone number must have at least 8 digits.');
            return false;
        }
        
        clearFieldError(field);
        return true;
    }
    
    /**
     * Validate message field
     */
    function validateMessage(field) {
        const value = field.value.trim();
        
        if (!value) {
            showFieldError(field, 'Please enter your message.');
            return false;
        }
        
        if (value.length > config.maxMessageLength) {
            showFieldError(field, `Message must be less than ${config.maxMessageLength} characters.`);
            return false;
        }
        
        clearFieldError(field);
        return true;
    }
    
    /**
     * Validate the entire form
     */
    function validateForm() {
        let isValid = true;
        
        // Validate required fields
        if (nameInput && !validateName(nameInput)) {
            isValid = false;
        }
        
        if (emailInput && !validateEmail(emailInput)) {
            isValid = false;
        }
        
        if (phoneInput && !validatePhone(phoneInput)) {
            isValid = false;
        }
        
        if (messageTextarea && !validateMessage(messageTextarea)) {
            isValid = false;
        }
        
        return isValid;
    }
    
    // =========================================================================
    // Character Counter
    // =========================================================================
    
    /**
     * Update character counter for message textarea
     */
    function updateCharCounter() {
        if (!messageTextarea || !charCounter) return;
        
        const currentLength = messageTextarea.value.length;
        charCounter.textContent = currentLength.toLocaleString();
        
        // Update counter color based on length
        const counterParent = charCounter.parentElement;
        if (currentLength > config.maxMessageLength) {
            counterParent.classList.add('text-error');
            counterParent.style.color = 'var(--color-error)';
        } else if (currentLength > config.maxMessageLength * 0.9) {
            counterParent.classList.remove('text-error');
            counterParent.style.color = 'var(--color-warning)';
        } else {
            counterParent.classList.remove('text-error');
            counterParent.style.color = '';
        }
    }
    
    // =========================================================================
    // Phone Number Formatting
    // =========================================================================
    
    /**
     * Handle phone input formatting
     */
    function handlePhoneInput(e) {
        const input = e.target;
        const cursorPosition = input.selectionStart;
        const originalLength = input.value.length;
        
        // Format the phone number
        input.value = formatPhoneNumber(input.value);
        
        // Adjust cursor position if formatting changed the length
        const newLength = input.value.length;
        const lengthDiff = originalLength - newLength;
        if (lengthDiff !== 0) {
            input.setSelectionRange(cursorPosition - lengthDiff, cursorPosition - lengthDiff);
        }
    }
    
    // =========================================================================
    // Form Submission
    // =========================================================================
    
    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        // Validate form before submission
        if (!validateForm()) {
            e.preventDefault();
            
            // Focus first error field
            const firstError = contactForm.querySelector('.c-form__group--error input, .c-form__group--error textarea, .c-form__group--error select');
            if (firstError) {
                firstError.focus();
                
                // Scroll to error
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
            
            return false;
        }
        
        // Show loading state
        if (contactForm) {
            contactForm.classList.add('is-submitting');
        }
        
        // Form will submit normally (non-AJAX)
        // For AJAX submission, uncomment the code below and add e.preventDefault()
        
        /*
        e.preventDefault();
        
        const formData = new FormData(contactForm);
        const submitBtn = contactForm.querySelector('[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Update button to loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="c-btn__loading">Sending...</span>';
        
        // Submit via AJAX
        fetch(contactForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showFormSuccess(data.message);
                contactForm.reset();
                updateCharCounter();
            } else {
                // Show errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = contactForm.querySelector(`[name="${field}"]`);
                        if (input) {
                            showFieldError(input, data.errors[field]);
                        }
                    });
                } else {
                    showFormError(data.message || 'An error occurred. Please try again.');
                }
            }
        })
        .catch(error => {
            showFormError('An error occurred. Please try again later.');
            console.error('Form submission error:', error);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            contactForm.classList.remove('is-submitting');
        });
        */
    }
    
    /**
     * Show form success message (for AJAX)
     */
    function showFormSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'c-alert c-alert--success';
        successDiv.setAttribute('role', 'alert');
        successDiv.innerHTML = `
            <div class="c-alert__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="c-alert__content">
                <h4 class="c-alert__title">Message Sent!</h4>
                <p class="c-alert__message">${sanitizeInput(message)}</p>
            </div>
        `;
        
        contactForm.parentNode.insertBefore(successDiv, contactForm);
        contactForm.style.display = 'none';
        
        // Scroll to success message
        successDiv.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
    
    /**
     * Show form error message (for AJAX)
     */
    function showFormError(message) {
        // Remove existing general errors
        const existingError = contactForm.querySelector('.c-alert--error');
        if (existingError) {
            existingError.remove();
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'c-alert c-alert--error';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.innerHTML = `
            <div class="c-alert__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <div class="c-alert__content">
                <p class="c-alert__message">${sanitizeInput(message)}</p>
            </div>
        `;
        
        contactForm.insertBefore(errorDiv, contactForm.firstChild);
        
        // Scroll to error
        errorDiv.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
    
    // =========================================================================
    // Event Listeners
    // =========================================================================
    
    /**
     * Initialize event listeners
     */
    function initEventListeners() {
        // Form submission
        if (contactForm) {
            contactForm.addEventListener('submit', handleFormSubmit);
        }
        
        // Character counter for message
        if (messageTextarea) {
            messageTextarea.addEventListener('input', debounce(updateCharCounter, 50));
            // Initialize counter
            updateCharCounter();
        }
        
        // Phone number formatting
        if (phoneInput) {
            phoneInput.addEventListener('input', handlePhoneInput);
        }
        
        // Real-time validation on blur
        if (nameInput) {
            nameInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateName(this);
                }
            });
            nameInput.addEventListener('input', function() {
                clearFieldError(this);
            });
        }
        
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateEmail(this);
                }
            });
            emailInput.addEventListener('input', function() {
                clearFieldError(this);
            });
        }
        
        if (phoneInput) {
            phoneInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validatePhone(this);
                }
            });
            phoneInput.addEventListener('input', function() {
                clearFieldError(this);
            });
        }
        
        if (messageTextarea) {
            messageTextarea.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateMessage(this);
                }
            });
            messageTextarea.addEventListener('input', function() {
                clearFieldError(this);
            });
        }
        
        // Service select change
        if (serviceSelect) {
            serviceSelect.addEventListener('change', function() {
                clearFieldError(this);
            });
        }
    }
    
    // =========================================================================
    // Accessibility Enhancements
    // =========================================================================
    
    /**
     * Add accessibility enhancements
     */
    function initAccessibility() {
        // Add required aria attributes to required fields
        const requiredFields = contactForm?.querySelectorAll('[required]');
        requiredFields?.forEach(field => {
            field.setAttribute('aria-required', 'true');
        });
        
        // Add live region for form errors
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.className = 'sr-only';
        liveRegion.id = 'form-status';
        document.body.appendChild(liveRegion);
    }
    
    // =========================================================================
    // Map Interaction (Optional Enhancement)
    // =========================================================================
    
    /**
     * Initialize map interactions
     */
    function initMapInteraction() {
        const mapContainer = document.querySelector('.c-map-container');
        const mapIframe = mapContainer?.querySelector('iframe');
        
        if (!mapContainer || !mapIframe) return;
        
        // Add loading state for map
        mapIframe.addEventListener('load', function() {
            mapContainer.classList.add('is-loaded');
        });
        
        // Handle map focus for accessibility
        mapIframe.addEventListener('focus', function() {
            mapContainer.classList.add('is-focused');
        });
        
        mapIframe.addEventListener('blur', function() {
            mapContainer.classList.remove('is-focused');
        });
    }
    
    // =========================================================================
    // Smooth Scroll for Anchor Links
    // =========================================================================
    
    /**
     * Initialize smooth scrolling for anchor links
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    
                    const navHeight = document.querySelector('.c-nav')?.offsetHeight || 0;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Update focus for accessibility
                    target.setAttribute('tabindex', '-1');
                    target.focus();
                }
            });
        });
    }
    
    // =========================================================================
    // Office Card Hover Effects
    // =========================================================================
    
    /**
     * Initialize office card interactions
     */
    function initOfficeCards() {
        const officeCards = document.querySelectorAll('.c-office-card');
        
        officeCards.forEach(card => {
            const mapLink = card.querySelector('.c-office-card__footer a');
            
            if (mapLink) {
                // Open map links in new window
                mapLink.addEventListener('click', function(e) {
                    // Let the default behavior handle it (target="_blank")
                });
            }
        });
    }
    
    // =========================================================================
    // Form Auto-Save (Optional)
    // =========================================================================
    
    /**
     * Save form data to localStorage
     */
    function saveFormData() {
        if (!contactForm) return;
        
        const formData = new FormData(contactForm);
        const data = {};
        
        formData.forEach((value, key) => {
            if (key !== 'csrf_token') {
                data[key] = value;
            }
        });
        
        try {
            localStorage.setItem('contactFormData', JSON.stringify(data));
        } catch (e) {
            // localStorage not available
        }
    }
    
    /**
     * Restore form data from localStorage
     */
    function restoreFormData() {
        if (!contactForm) return;
        
        try {
            const savedData = localStorage.getItem('contactFormData');
            if (!savedData) return;
            
            const data = JSON.parse(savedData);
            
            Object.keys(data).forEach(key => {
                const field = contactForm.querySelector(`[name="${key}"]`);
                if (field && field.type !== 'checkbox' && field.type !== 'radio') {
                    field.value = data[key];
                }
            });
            
            // Update character counter after restore
            updateCharCounter();
        } catch (e) {
            // localStorage not available or data corrupted
        }
    }
    
    /**
     * Clear saved form data
     */
    function clearSavedFormData() {
        try {
            localStorage.removeItem('contactFormData');
        } catch (e) {
            // localStorage not available
        }
    }
    
    // =========================================================================
    // Initialization
    // =========================================================================
    
    /**
     * Initialize all contact page functionality
     */
    function init() {
        // Check if we're on the contact page
        if (!contactForm) return;
        
        // Initialize all modules
        initEventListeners();
        initAccessibility();
        initMapInteraction();
        initSmoothScroll();
        initOfficeCards();
        
        // Restore form data (optional - uncomment if desired)
        // restoreFormData();
        
        // Auto-save form data (optional - uncomment if desired)
        // contactForm.addEventListener('input', debounce(saveFormData, 1000));
        
        // Clear saved data on successful submission
        // if (document.querySelector('.c-alert--success')) {
        //     clearSavedFormData();
        // }
        
        console.log('Contact page initialized');
    }
    
    // =========================================================================
    // DOM Ready
    // =========================================================================
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // =========================================================================
    // Expose public API (optional)
    // =========================================================================
    
    window.TulimaContact = {
        validateForm: validateForm,
        showFieldError: showFieldError,
        clearFieldError: clearFieldError,
        updateCharCounter: updateCharCounter
    };
    
})();
