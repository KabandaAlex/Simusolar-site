/**
 * Tulima Solar - Home Page JavaScript
 * Page-specific functionality for the home page
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    // =========================================================================
    // VIDEO BACKGROUND CONTROL
    // =========================================================================

    const heroVideo = document.getElementById('hero-video');
    const videoControl = document.getElementById('video-control');

    if (heroVideo && videoControl) {
        const iconPause = videoControl.querySelector('.icon-pause');
        const iconPlay = videoControl.querySelector('.icon-play');

        // Toggle video play/pause
        videoControl.addEventListener('click', function() {
            if (heroVideo.paused) {
                heroVideo.play();
                videoControl.classList.remove('is-paused');
                videoControl.setAttribute('aria-label', 'Pause video');
            } else {
                heroVideo.pause();
                videoControl.classList.add('is-paused');
                videoControl.setAttribute('aria-label', 'Play video');
            }
        });

        // Pause video on mobile to save bandwidth
        if (window.innerWidth < 768) {
            heroVideo.pause();
            videoControl.classList.add('is-paused');
        }

        // Handle video loading errors
        heroVideo.addEventListener('error', function() {
            console.log('Video failed to load, showing fallback image');
        });
    }

    // =========================================================================
    // TESTIMONIALS SLIDER
    // =========================================================================

    const testimonialsSlider = document.getElementById('testimonials-slider');

    if (testimonialsSlider) {
        const testimonials = testimonialsSlider.querySelectorAll('.c-testimonial');
        const prevBtn = testimonialsSlider.querySelector('.c-testimonials__prev');
        const nextBtn = testimonialsSlider.querySelector('.c-testimonials__next');
        const dots = testimonialsSlider.querySelectorAll('.c-testimonials__dot');
        let currentIndex = 0;
        let autoplayInterval = null;

        function showTestimonial(index) {
            // Wrap around
            if (index < 0) {
                index = testimonials.length - 1;
            } else if (index >= testimonials.length) {
                index = 0;
            }

            // Update testimonials
            testimonials.forEach((testimonial, i) => {
                testimonial.classList.toggle('is-active', i === index);
            });

            // Update dots
            dots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === index);
            });

            currentIndex = index;
        }

        function nextTestimonial() {
            showTestimonial(currentIndex + 1);
        }

        function prevTestimonial() {
            showTestimonial(currentIndex - 1);
        }

        function startAutoplay() {
            stopAutoplay();
            autoplayInterval = setInterval(nextTestimonial, 6000);
        }

        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                autoplayInterval = null;
            }
        }

        // Event listeners
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevTestimonial();
                startAutoplay(); // Reset autoplay timer
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextTestimonial();
                startAutoplay(); // Reset autoplay timer
            });
        }

        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', function() {
                showTestimonial(index);
                startAutoplay(); // Reset autoplay timer
            });
        });

        // Keyboard navigation
        testimonialsSlider.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                prevTestimonial();
                startAutoplay();
            } else if (e.key === 'ArrowRight') {
                nextTestimonial();
                startAutoplay();
            }
        });

        // Touch/swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        testimonialsSlider.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        testimonialsSlider.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    nextTestimonial();
                } else {
                    prevTestimonial();
                }
                startAutoplay();
            }
        }

        // Pause autoplay on hover/focus
        testimonialsSlider.addEventListener('mouseenter', stopAutoplay);
        testimonialsSlider.addEventListener('mouseleave', startAutoplay);
        testimonialsSlider.addEventListener('focusin', stopAutoplay);
        testimonialsSlider.addEventListener('focusout', startAutoplay);

        // Start autoplay
        if (testimonials.length > 1) {
            startAutoplay();
        }
    }

    // =========================================================================
    // ANIMATED COUNTERS (impact section numbers count up when section is in view)
    // =========================================================================

    const impactSection = document.getElementById('impact');
    const impactValues = document.querySelectorAll('.c-impact-card__value[data-count]');
    const statNumbers = document.querySelectorAll('.c-stat__number[data-count]');

    const duration = 2000;
    const easing = t => t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;

    function animateOneCounter(element) {
        const raw = element.getAttribute('data-count');
        const target = parseInt(raw, 10);
        if (raw === '' || raw === null || isNaN(target) || target < 0) return;

        const suffix = element.getAttribute('data-suffix') || '';

        function formatDisplay(num) {
            return Math.floor(num).toLocaleString() + suffix;
        }

        const startTime = performance.now();

        function update(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = easing(progress);
            const current = target * eased;
            element.textContent = formatDisplay(current);
            if (progress < 1) requestAnimationFrame(update);
            else element.textContent = formatDisplay(target);
        }

        requestAnimationFrame(update);
    }

    function runImpactCounters() {
        impactValues.forEach(el => {
            if (!el.hasAttribute('data-animated')) {
                el.setAttribute('data-animated', '1');
                animateOneCounter(el);
            }
        });
    }

    // When impact section is in view, run all impact counters together
    if (impactSection && impactValues.length > 0) {
        const impactObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    runImpactCounters();
                    impactObserver.unobserve(entry.target);
                }
            });
        }, { root: null, rootMargin: '0px 0px -80px 0px', threshold: 0.15 });

        impactObserver.observe(impactSection);
    }

    // Legacy .c-stat__number (e.g. About/Projects): animate when each enters view
    if (statNumbers.length > 0) {
        const statObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateOneCounter(entry.target);
                    statObserver.unobserve(entry.target);
                }
            });
        }, { root: null, rootMargin: '0px', threshold: 0.3 });

        statNumbers.forEach(el => statObserver.observe(el));
    }

    // =========================================================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // =========================================================================

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                const navHeight = document.querySelector('.c-nav')?.offsetHeight || 0;
                const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;

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

    // =========================================================================
    // SCROLL-TRIGGERED ANIMATIONS
    // =========================================================================

    const animatedSections = document.querySelectorAll('.c-section');

    if (animatedSections.length > 0 && 'IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    sectionObserver.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -100px 0px',
            threshold: 0.1
        });

        animatedSections.forEach(section => {
            section.style.animationPlayState = 'paused';
            sectionObserver.observe(section);
        });
    }

    // =========================================================================
    // LAZY LOAD PARTNER LOGOS
    // =========================================================================

    const partnerLogos = document.querySelectorAll('.c-partner__logo[loading="lazy"]');

    if (partnerLogos.length > 0 && 'IntersectionObserver' in window) {
        const logoObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    logoObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px'
        });

        partnerLogos.forEach(img => {
            if (img.dataset.src) {
                logoObserver.observe(img);
            }
        });
    }

    // =========================================================================
    // PARALLAX EFFECT FOR HERO (Optional Enhancement)
    // =========================================================================

    const hero = document.querySelector('.c-hero--home');

    if (hero && window.innerWidth > 768) {
        const heroBackground = hero.querySelector('.c-hero__background');
        
        window.addEventListener('scroll', function() {
            const scrolled = window.scrollY;
            const rate = scrolled * 0.3;
            
            if (heroBackground && scrolled < hero.offsetHeight) {
                heroBackground.style.transform = `translateY(${rate}px)`;
            }
        }, { passive: true });
    }

    // =========================================================================
    // ACCESSIBILITY ENHANCEMENTS
    // =========================================================================

    // Add ARIA labels to interactive elements that might be missing them
    document.querySelectorAll('.c-card__link').forEach(link => {
        if (!link.getAttribute('aria-label')) {
            const title = link.closest('.c-card')?.querySelector('.c-card__title')?.textContent;
            if (title) {
                link.setAttribute('aria-label', `Learn more about ${title}`);
            }
        }
    });

    // =========================================================================
    // PERFORMANCE: Debounce resize handler
    // =========================================================================

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

    // Handle window resize
    window.addEventListener('resize', debounce(function() {
        // Re-check video on resize
        if (heroVideo && videoControl) {
            if (window.innerWidth < 768 && !heroVideo.paused) {
                heroVideo.pause();
                videoControl.classList.add('is-paused');
            }
        }
    }, 250));

})();
