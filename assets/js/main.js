/**
 * Tulima Solar - Main JavaScript
 * 
 * Global JavaScript functionality for the public website
 * 
 * @version 1.0.0
 */

(function() {
  'use strict';

  /**
   * DOM Ready handler
   */
  function domReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  /**
   * Initialize all modules when DOM is ready
   */
  domReady(function() {
    initSmoothScroll();
    initLazyLoading();
    initFormValidation();
    initAccessibilityEnhancements();
  });

  /**
   * Smooth scroll for anchor links
   */
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
      anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#' || href === '#main-content') return;

        const target = document.querySelector(href);
        if (target) {
          e.preventDefault();
          const navHeight = document.querySelector('.c-nav')?.offsetHeight || 70;
          const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;

          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });

          // Update URL without jumping
          history.pushState(null, null, href);
          
          // Focus target for accessibility
          target.setAttribute('tabindex', '-1');
          target.focus({ preventScroll: true });
        }
      });
    });
  }

  /**
   * Lazy loading for images
   */
  function initLazyLoading() {
    // Use native lazy loading if supported
    if ('loading' in HTMLImageElement.prototype) {
      document.querySelectorAll('img[data-src]').forEach(function(img) {
        img.src = img.dataset.src;
        img.loading = 'lazy';
      });
    } else {
      // Fallback for older browsers
      const lazyImages = document.querySelectorAll('img[data-src]');
      
      if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              const image = entry.target;
              image.src = image.dataset.src;
              image.removeAttribute('data-src');
              imageObserver.unobserve(image);
            }
          });
        }, {
          rootMargin: '50px 0px',
          threshold: 0.01
        });

        lazyImages.forEach(function(img) {
          imageObserver.observe(img);
        });
      } else {
        // Fallback: load all images immediately
        lazyImages.forEach(function(img) {
          img.src = img.dataset.src;
        });
      }
    }
  }

  /**
   * Basic form validation
   */
  function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(function(form) {
      form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        // Clear previous errors
        form.querySelectorAll('.c-form__error').forEach(function(el) {
          el.remove();
        });
        form.querySelectorAll('.c-form__group--error').forEach(function(el) {
          el.classList.remove('c-form__group--error');
        });
        
        // Validate each required field
        requiredFields.forEach(function(field) {
          if (!field.value.trim()) {
            isValid = false;
            showFieldError(field, 'This field is required');
          } else if (field.type === 'email' && !isValidEmail(field.value)) {
            isValid = false;
            showFieldError(field, 'Please enter a valid email address');
          }
        });
        
        if (!isValid) {
          e.preventDefault();
          
          // Focus first error field
          const firstError = form.querySelector('.c-form__group--error input, .c-form__group--error textarea');
          if (firstError) {
            firstError.focus();
          }
        }
      });
      
      // Real-time validation on blur
      form.querySelectorAll('input, textarea, select').forEach(function(field) {
        field.addEventListener('blur', function() {
          validateField(this);
        });
        
        // Clear error on input
        field.addEventListener('input', function() {
          const group = this.closest('.c-form__group');
          if (group && group.classList.contains('c-form__group--error')) {
            group.classList.remove('c-form__group--error');
            const error = group.querySelector('.c-form__error');
            if (error) error.remove();
          }
        });
      });
    });
  }

  /**
   * Validate a single field
   */
  function validateField(field) {
    const group = field.closest('.c-form__group');
    if (!group) return;
    
    // Clear previous error
    group.classList.remove('c-form__group--error');
    const existingError = group.querySelector('.c-form__error');
    if (existingError) existingError.remove();
    
    // Check validation
    if (field.required && !field.value.trim()) {
      showFieldError(field, 'This field is required');
      return false;
    }
    
    if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
      showFieldError(field, 'Please enter a valid email address');
      return false;
    }
    
    return true;
  }

  /**
   * Show field error message
   */
  function showFieldError(field, message) {
    const group = field.closest('.c-form__group');
    if (!group) return;
    
    group.classList.add('c-form__group--error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'c-form__error';
    errorDiv.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' + message;
    
    group.appendChild(errorDiv);
  }

  /**
   * Email validation helper
   */
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  /**
   * Accessibility enhancements
   */
  function initAccessibilityEnhancements() {
    // Add focus visible polyfill behavior
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Tab') {
        document.body.classList.add('using-keyboard');
      }
    });
    
    document.addEventListener('mousedown', function() {
      document.body.classList.remove('using-keyboard');
    });
    
    // Handle dropdown menus for keyboard users
    const dropdownItems = document.querySelectorAll('.c-nav__item');
    dropdownItems.forEach(function(item) {
      const link = item.querySelector('.c-nav__link');
      const dropdown = item.querySelector('.c-nav__dropdown');
      
      if (link && dropdown) {
        link.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            item.classList.toggle('is-open');
          }
          
          if (e.key === 'Escape') {
            item.classList.remove('is-open');
            link.focus();
          }
        });
        
        // Close dropdown when focus leaves
        item.addEventListener('focusout', function(e) {
          if (!item.contains(e.relatedTarget)) {
            item.classList.remove('is-open');
          }
        });
      }
    });
    
    // Announce page changes for screen readers (for SPA-like behavior)
    const mainContent = document.getElementById('main-content');
    if (mainContent) {
      mainContent.setAttribute('role', 'main');
      mainContent.setAttribute('aria-label', 'Main content');
    }
  }

  /**
   * Utility: Debounce function
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
   * Utility: Throttle function
   */
  function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  /**
   * Utility: Check if element is in viewport
   */
  function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
  }

  // Expose utilities globally
  window.TulimaSolar = {
    debounce: debounce,
    throttle: throttle,
    isInViewport: isInViewport,
    validateField: validateField
  };

})();
