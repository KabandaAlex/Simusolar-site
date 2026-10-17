/**
 * Tulima Solar - Projects Page JavaScript
 * Handles project filtering and interactions
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    // =========================================================================
    // Configuration
    // =========================================================================
    
    const CONFIG = {
        animationDuration: 400,
        scrollOffset: 100,
        debounceDelay: 150
    };

    // =========================================================================
    // DOM Elements
    // =========================================================================

    const elements = {
        filterButtons: document.querySelectorAll('.c-filter-btn'),
        projectsGrid: document.getElementById('projects-grid'),
        projectCards: document.querySelectorAll('.c-project-card'),
        loadMoreBtn: document.getElementById('load-more-btn'),
        loadMoreContainer: document.getElementById('load-more-container')
    };

    // =========================================================================
    // State
    // =========================================================================

    const state = {
        currentFilter: 'all',
        visibleProjects: 8,
        totalProjects: elements.projectCards.length
    };

    // =========================================================================
    // Filter Functionality
    // =========================================================================

    /**
     * Initialize filter buttons
     */
    function initFilters() {
        if (!elements.filterButtons.length) return;

        elements.filterButtons.forEach(button => {
            button.addEventListener('click', handleFilterClick);
        });
    }

    /**
     * Handle filter button click
     * @param {Event} event - Click event
     */
    function handleFilterClick(event) {
        const button = event.currentTarget;
        const filter = button.dataset.filter;

        // Update active state
        updateActiveFilter(button);

        // Apply filter
        filterProjects(filter);
    }

    /**
     * Update active filter button
     * @param {HTMLElement} activeButton - Button to set as active
     */
    function updateActiveFilter(activeButton) {
        elements.filterButtons.forEach(btn => {
            btn.classList.remove('is-active');
            btn.setAttribute('aria-selected', 'false');
        });

        activeButton.classList.add('is-active');
        activeButton.setAttribute('aria-selected', 'true');
    }

    /**
     * Filter projects by category
     * @param {string} category - Category to filter by
     */
    function filterProjects(category) {
        state.currentFilter = category;

        // First, fade out all cards that don't match
        elements.projectCards.forEach(card => {
            const cardCategory = card.dataset.category;
            const shouldShow = category === 'all' || cardCategory === category;

            if (!shouldShow) {
                card.classList.add('filtering-out');
            }
        });

        // After fade out animation, hide cards and show matching ones
        setTimeout(() => {
            elements.projectCards.forEach(card => {
                const cardCategory = card.dataset.category;
                const shouldShow = category === 'all' || cardCategory === category;

                if (shouldShow) {
                    card.classList.remove('hidden', 'filtering-out');
                    card.classList.add('filtering-in');
                    
                    // Remove animation class after it completes
                    setTimeout(() => {
                        card.classList.remove('filtering-in');
                    }, CONFIG.animationDuration);
                } else {
                    card.classList.add('hidden');
                    card.classList.remove('filtering-out');
                }
            });

            // Update load more button visibility
            updateLoadMoreVisibility();
        }, CONFIG.animationDuration);

        // Announce filter change to screen readers
        announceFilterChange(category);
    }

    /**
     * Announce filter change to screen readers
     * @param {string} category - Current filter category
     */
    function announceFilterChange(category) {
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.className = 'sr-only';
        announcement.textContent = `Showing ${category === 'all' ? 'all' : category} projects`;
        
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            announcement.remove();
        }, 1000);
    }

    // =========================================================================
    // Load More Functionality
    // =========================================================================

    /**
     * Initialize load more button
     */
    function initLoadMore() {
        if (!elements.loadMoreBtn) return;

        elements.loadMoreBtn.addEventListener('click', handleLoadMore);
        
        // Initial visibility check
        updateLoadMoreVisibility();
    }

    /**
     * Handle load more button click
     */
    function handleLoadMore() {
        // In a real implementation, this would load more projects from an API
        // For now, we'll just show a message
        const btn = elements.loadMoreBtn;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="c-btn__icon c-btn__icon--left c-btn__icon--spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
            </svg>
            Loading...
        `;

        // Simulate loading delay
        setTimeout(() => {
            // In production, this would load more projects
            // For demo, just show a message
            btn.innerHTML = `
                <svg class="c-btn__icon c-btn__icon--left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                All Projects Loaded
            `;
            btn.disabled = true;
            btn.classList.add('c-btn--disabled');
        }, 1500);
    }

    /**
     * Update load more button visibility based on filtered results
     */
    function updateLoadMoreVisibility() {
        if (!elements.loadMoreContainer) return;

        const visibleCards = document.querySelectorAll('.c-project-card:not(.hidden)');
        const hasMore = visibleCards.length < state.totalProjects;

        elements.loadMoreContainer.style.display = hasMore ? 'flex' : 'none';
    }

    // =========================================================================
    // Project Card Interactions
    // =========================================================================

    /**
     * Initialize project card interactions
     */
    function initProjectCards() {
        elements.projectCards.forEach(card => {
            // Add keyboard navigation
            card.setAttribute('tabindex', '0');
            
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const link = card.querySelector('.c-project-card__link');
                    if (link) {
                        link.click();
                    }
                }
            });

            // Add click handler for the whole card
            card.addEventListener('click', (e) => {
                // Don't trigger if clicking on the link itself
                if (e.target.closest('.c-project-card__link')) return;
                
                const link = card.querySelector('.c-project-card__link');
                if (link) {
                    link.click();
                }
            });
        });
    }

    // =========================================================================
    // Smooth Scroll for Project Links
    // =========================================================================

    /**
     * Initialize smooth scroll for project detail links
     */
    function initSmoothScroll() {
        document.querySelectorAll('.c-project-card__link').forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                
                // Only handle hash links
                if (!href || !href.startsWith('#')) return;
                
                e.preventDefault();
                
                const target = document.querySelector(href);
                if (target) {
                    const navHeight = document.querySelector('.c-nav')?.offsetHeight || 0;
                    const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    // =========================================================================
    // Image Lazy Loading Enhancement
    // =========================================================================

    /**
     * Initialize image lazy loading with fade-in effect
     */
    function initLazyLoading() {
        const images = document.querySelectorAll('.c-project-card__image img');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.classList.add('is-loaded');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });

            images.forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // =========================================================================
    // Statistics Counter Animation
    // =========================================================================

    /**
     * Initialize counter animation for statistics
     */
    function initCounterAnimation() {
        const stats = document.querySelectorAll('.c-impact-stat__value');
        
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.5
            });

            stats.forEach(stat => {
                observer.observe(stat);
            });
        }
    }

    /**
     * Animate counter from 0 to target value
     * @param {HTMLElement} element - Element containing the value
     */
    function animateCounter(element) {
        const text = element.textContent;
        const hasPlus = text.includes('+');
        const numericPart = text.replace(/[^0-9.]/g, '');
        const prefix = text.match(/^[^0-9]*/)[0] || '';
        const suffix = text.match(/[^0-9]*$/)[0].replace(/\+/, '') || '';
        
        const target = parseFloat(numericPart);
        const duration = 2000;
        const startTime = performance.now();
        
        // Don't animate non-numeric values
        if (isNaN(target)) return;

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (ease-out)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            
            const current = target * easeOut;
            
            // Format the number
            let displayValue;
            if (target >= 1000) {
                displayValue = Math.round(current).toLocaleString();
            } else if (Number.isInteger(target)) {
                displayValue = Math.round(current);
            } else {
                displayValue = current.toFixed(1);
            }
            
            element.textContent = prefix + displayValue + suffix + (hasPlus ? '+' : '');
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }

    // =========================================================================
    // Keyboard Navigation
    // =========================================================================

    /**
     * Initialize keyboard navigation for filter buttons
     */
    function initKeyboardNavigation() {
        const filterContainer = document.querySelector('.c-projects-filter');
        if (!filterContainer) return;

        filterContainer.addEventListener('keydown', (e) => {
            const buttons = Array.from(elements.filterButtons);
            const currentIndex = buttons.findIndex(btn => btn === document.activeElement);

            let newIndex;

            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    newIndex = currentIndex > 0 ? currentIndex - 1 : buttons.length - 1;
                    break;
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    newIndex = currentIndex < buttons.length - 1 ? currentIndex + 1 : 0;
                    break;
                case 'Home':
                    e.preventDefault();
                    newIndex = 0;
                    break;
                case 'End':
                    e.preventDefault();
                    newIndex = buttons.length - 1;
                    break;
                default:
                    return;
            }

            buttons[newIndex].focus();
        });
    }

    // =========================================================================
    // URL State Management
    // =========================================================================

    /**
     * Initialize URL state management for filters
     */
    function initUrlState() {
        // Check URL for filter parameter on load
        const urlParams = new URLSearchParams(window.location.search);
        const filterParam = urlParams.get('category');

        if (filterParam) {
            const validCategories = ['all', 'residential', 'commercial', 'agricultural', 'industrial'];
            if (validCategories.includes(filterParam)) {
                const button = document.querySelector(`[data-filter="${filterParam}"]`);
                if (button) {
                    button.click();
                }
            }
        }

        // Update URL when filter changes
        if (history.replaceState) {
            const originalFilter = state.currentFilter;
            
            // Watch for filter changes
            const observer = new MutationObserver(() => {
                const activeButton = document.querySelector('.c-filter-btn.is-active');
                if (activeButton) {
                    const newFilter = activeButton.dataset.filter;
                    if (newFilter !== originalFilter) {
                        updateUrl(newFilter);
                    }
                }
            });

            elements.filterButtons.forEach(btn => {
                observer.observe(btn, { attributes: true, attributeFilter: ['class'] });
            });
        }
    }

    /**
     * Update URL with current filter
     * @param {string} filter - Current filter value
     */
    function updateUrl(filter) {
        if (!history.replaceState) return;

        const url = new URL(window.location.href);
        
        if (filter === 'all') {
            url.searchParams.delete('category');
        } else {
            url.searchParams.set('category', filter);
        }

        history.replaceState(null, '', url.toString());
    }

    // =========================================================================
    // Initialization
    // =========================================================================

    /**
     * Initialize all projects page functionality
     */
    function init() {
        initFilters();
        initLoadMore();
        initProjectCards();
        initSmoothScroll();
        initLazyLoading();
        initCounterAnimation();
        initKeyboardNavigation();
        initUrlState();

        // Add loaded class to body for CSS transitions
        document.body.classList.add('projects-page-loaded');

        console.log('Projects page initialized');
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

    window.TulimaProjects = {
        filter: filterProjects,
        getCurrentFilter: () => state.currentFilter
    };

})();
