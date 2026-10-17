/**
 * Tulima Solar - Services Page JavaScript
 * Handles FAQ accordion and other page interactions
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    // =========================================================================
    // FAQ ACCORDION
    // =========================================================================

    /**
     * Initialize FAQ accordion functionality
     */
    function initFaqAccordion() {
        const accordion = document.getElementById('faq-accordion');
        
        if (!accordion) {
            return;
        }

        const faqItems = accordion.querySelectorAll('.c-faq-item, .p-services-faq__item');

        faqItems.forEach(item => {
            const questionButton = item.querySelector('.c-faq-item__question, .p-services-faq__q, [data-faq-toggle]');
            const answer = item.querySelector('.c-faq-item__answer, .p-services-faq__a');

            if (!questionButton || !answer) {
                return;
            }

            questionButton.addEventListener('click', () => {
                const isExpanded = questionButton.getAttribute('aria-expanded') === 'true';

                // Close all other items (accordion behavior)
                faqItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        const otherButton = otherItem.querySelector('.c-faq-item__question, .p-services-faq__q, [data-faq-toggle]');
                        const otherAnswer = otherItem.querySelector('.c-faq-item__answer, .p-services-faq__a');
                        
                        if (otherButton && otherAnswer) {
                            otherItem.classList.remove('is-active', 'is-open');
                            otherButton.setAttribute('aria-expanded', 'false');
                            otherAnswer.hidden = true;
                        }
                    }
                });

                // Toggle current item
                if (isExpanded) {
                    item.classList.remove('is-active', 'is-open');
                    questionButton.setAttribute('aria-expanded', 'false');
                    answer.hidden = true;
                } else {
                    item.classList.add('is-active', 'is-open');
                    questionButton.setAttribute('aria-expanded', 'true');
                    answer.hidden = false;
                    if (typeof announceToScreenReader === 'function') {
                        announceToScreenReader('Expanded: ' + questionButton.textContent.trim());
                    }
                }
            });

            questionButton.addEventListener('keydown', (e) => {
                if (typeof handleAccordionKeyboard === 'function') {
                    handleAccordionKeyboard(e, faqItems, item);
                }
            });
        });
    }

    /**
     * Handle keyboard navigation for accordion
     * @param {KeyboardEvent} e - Keyboard event
     * @param {NodeList} allItems - All accordion items
     * @param {HTMLElement} currentItem - Current focused item
     */
    function handleAccordionKeyboard(e, allItems, currentItem) {
        const itemsArray = Array.from(allItems);
        const currentIndex = itemsArray.indexOf(currentItem);
        let targetIndex;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                targetIndex = (currentIndex + 1) % itemsArray.length;
                focusAccordionItem(itemsArray[targetIndex]);
                break;

            case 'ArrowUp':
                e.preventDefault();
                targetIndex = (currentIndex - 1 + itemsArray.length) % itemsArray.length;
                focusAccordionItem(itemsArray[targetIndex]);
                break;

            case 'Home':
                e.preventDefault();
                focusAccordionItem(itemsArray[0]);
                break;

            case 'End':
                e.preventDefault();
                focusAccordionItem(itemsArray[itemsArray.length - 1]);
                break;
        }
    }

    /**
     * Focus a specific accordion item
     * @param {HTMLElement} item - Accordion item to focus
     */
    function focusAccordionItem(item) {
        const button = item.querySelector('.c-faq-item__question, .p-services-faq__q, [data-faq-toggle]');
        if (button) {
            button.focus();
        }
    }

    // =========================================================================
    // SCREEN READER ANNOUNCEMENTS
    // =========================================================================

    /**
     * Announce message to screen readers
     * @param {string} message - Message to announce
     */
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'u-sr-only';
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    // =========================================================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // =========================================================================

    /**
     * Initialize smooth scrolling for anchor links within the page
     */
    function initSmoothScroll() {
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        const navHeight = document.querySelector('.c-nav')?.offsetHeight || 0;

        anchorLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const targetId = link.getAttribute('href');
                
                if (targetId === '#' || !targetId) {
                    return;
                }

                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    e.preventDefault();
                    
                    const targetPosition = targetElement.getBoundingClientRect().top + 
                                          window.pageYOffset - 
                                          navHeight - 
                                          20;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Update URL without jumping
                    history.pushState(null, null, targetId);

                    // Focus target for accessibility
                    targetElement.setAttribute('tabindex', '-1');
                    targetElement.focus();
                }
            });
        });
    }

    // =========================================================================
    // SERVICE CARD HOVER EFFECTS
    // =========================================================================

    /**
     * Initialize enhanced hover effects for service cards
     */
    function initServiceCardEffects() {
        const serviceCards = document.querySelectorAll('.c-service-card');

        serviceCards.forEach(card => {
            // Add ripple effect on click
            card.addEventListener('click', function(e) {
                const link = this.querySelector('.c-service-card__link');
                if (link && e.target !== link && !link.contains(e.target)) {
                    // Simulate link click when card is clicked
                    window.location.href = link.href;
                }
            });

            // Add cursor pointer style
            card.style.cursor = 'pointer';
        });
    }

    // =========================================================================
    // PROCESS STEPS ANIMATION
    // =========================================================================

    /**
     * Initialize intersection observer for process steps animation
     */
    function initProcessStepsAnimation() {
        const processSteps = document.querySelectorAll('.c-process-step');

        if (!processSteps.length) {
            return;
        }

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.2
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Add staggered animation delay
                    setTimeout(() => {
                        entry.target.classList.add('is-visible');
                    }, index * 100);
                    
                    // Unobserve after animation
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        processSteps.forEach(step => {
            step.classList.add('animate-on-scroll');
            observer.observe(step);
        });
    }

    // =========================================================================
    // LAZY LOADING FOR SERVICE CARD ICONS
    // =========================================================================

    /**
     * Initialize lazy loading for any images in service cards
     */
    function initLazyLoading() {
        const lazyImages = document.querySelectorAll('.c-service-card img[loading="lazy"]');
        
        if ('loading' in HTMLImageElement.prototype) {
            // Native lazy loading is supported
            return;
        }

        // Fallback for browsers that don't support native lazy loading
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    }

    // =========================================================================
    // CTA SECTION INTERACTIONS
    // =========================================================================

    /**
     * Initialize CTA section interactions
     */
    function initCtaInteractions() {
        const ctaSection = document.querySelector('.c-section--cta');
        
        if (!ctaSection) {
            return;
        }

        // Add parallax effect to CTA background
        if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const sectionTop = ctaSection.offsetTop;
                const sectionHeight = ctaSection.offsetHeight;
                
                if (scrolled > sectionTop - window.innerHeight && 
                    scrolled < sectionTop + sectionHeight) {
                    const parallaxOffset = (scrolled - sectionTop) * 0.3;
                    ctaSection.style.backgroundPositionY = `${parallaxOffset}px`;
                }
            });
        }
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    /**
     * Initialize all services page functionality
     */
    function init() {
        initFaqAccordion();
        initSmoothScroll();
        initServiceCardEffects();
        initProcessStepsAnimation();
        initLazyLoading();
        initCtaInteractions();

        // Log initialization
        console.log('Services page initialized');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // =========================================================================
    // CSS FOR ANIMATIONS (injected)
    // =========================================================================

    // Add animation styles
    const animationStyles = document.createElement('style');
    animationStyles.textContent = `
        /* Process steps scroll animation */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Screen reader only utility */
        .u-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            .animate-on-scroll {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    `;
    document.head.appendChild(animationStyles);

})();
