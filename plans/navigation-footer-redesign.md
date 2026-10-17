# Navigation and Footer Redesign - Architectural Design Document

## Executive Summary

This document outlines the comprehensive redesign of the navigation menu and footer sections for the Tulima Solar website. The design focuses on creating a modern, professional layout that aligns with brand guidelines while improving accessibility, responsiveness, and user experience.

---

## 1. Current State Analysis

### 1.1 Header Analysis

**Current Structure:**
- Custom navbar with transparent-to-solid scroll effect
- Logo (white and dark variants)
- Navigation menu with dropdowns
- CTA button ("Calculator")
- Mobile hamburger menu
- Bootstrap 5 integration

**Navigation Links:**
- Home
- Services (dropdown: What We Offer, Our Services)
- Products (dropdown: All Products, Solar Water Pumps, Surface Pumps, Submersible Pumps, Processing Machines)
- Who We Are
- Our Impact
- Careers
- Blog
- Contact

**Issues Identified:**
- Complex CSS with excessive !important overrides
- Inconsistent class naming
- Limited accessibility features
- Mobile menu could be more user-friendly
- No ARIA labels for screen readers
- Limited keyboard navigation support

### 1.2 Footer Analysis

**Current Structure:**
- 4-column layout (Company Info, Quick Links, Products, Contact Info)
- Social media links
- Copyright and legal links
- Back to top button
- Bootstrap grid system

**Issues Identified:**
- Limited sections (could benefit from more comprehensive information)
- No newsletter subscription
- Limited brand storytelling
- Could benefit from more visual hierarchy
- Missing additional resources section

### 1.3 Brand Guidelines Review

**Color Palette:**
- Primary Purple: #660099
- Secondary Green: #B0D7AB
- Accent Orange: #E98E39
- Dark Purple: #4A0066
- Light Purple: #F0E6F7
- Text Primary: #2C2C2C
- Text Secondary: #4A4A4A
- Text Muted: #6B6B6B

**Typography:**
- Font Family: Inter (Google Fonts)
- Weights: 300, 400, 500, 600, 700, 800, 900
- Heading Font: Century Gothic / Inter
- Body Font: Calibri / Inter

**Font Sizes:**
- h1: 2.5rem
- h2: 2rem
- h3: 1.75rem
- h4: 1.5rem
- h5: 1.25rem
- h6: 1.1rem
- Body: 1rem
- Small: 0.875rem

---

## 2. Navigation Redesign

### 2.1 HTML Structure

```html
<!-- Semantic HTML5 Navigation -->
<nav class="main-navigation" role="navigation" aria-label="Main navigation">
    <!-- Top Bar: Contact Info & Social Links -->
    <div class="nav-top-bar">
        <div class="container">
            <div class="nav-top-content">
                <div class="contact-info">
                    <a href="tel:+256800388883" class="contact-link">
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <span>+256 800 388 883</span>
                    </a>
                    <a href="mailto:info@tulimasolar.com" class="contact-link">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <span>info@tulimasolar.com</span>
                    </a>
                </div>
                <div class="social-links">
                    <a href="https://www.facebook.com/TulimaSolarUganda" class="social-link" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/TulimaSolarUg" class="social-link" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://ug.linkedin.com/in/tulima-solar-1817941a2" class="social-link" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="https://www.instagram.com/tulimasolarugltd" class="social-link" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.youtube.com/channel/UCmiAeg3RNof682wBGdeCHIw" class="social-link" aria-label="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <div class="nav-main-bar">
        <div class="container">
            <div class="nav-main-content">
                <!-- Logo -->
                <a href="<?php echo BASE_PATH; ?>/" class="nav-logo" aria-label="Tulima Solar Home">
                    <img src="<?php echo BASE_PATH; ?>/static/images/white_logo.png" alt="Tulima Solar Logo" class="nav-logo-light" height="45">
                    <img src="<?php echo BASE_PATH; ?>/static/images/colored_logo.png" alt="Tulima Solar Logo" class="nav-logo-dark" height="45">
                </a>

                <!-- Desktop Navigation -->
                <ul class="nav-menu nav-menu-desktop">
                    <li class="nav-item">
                        <a href="<?php echo BASE_PATH; ?>/" class="nav-link <?php echo $currentSlug === 'home' ? 'active' : ''; ?>" aria-current="<?php echo $currentSlug === 'home' ? 'page' : 'false'; ?>">
                            Home
                        </a>
                    </li>
                    
                    <li class="nav-item has-dropdown">
                        <button class="nav-link nav-link-toggle" aria-expanded="false" aria-haspopup="true">
                            Services
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li role="none">
                                <a href="<?php echo BASE_PATH; ?>/index.php?page=what-we-offer" class="dropdown-item" role="menuitem">
                                    What We Offer
                                </a>
                            </li>
                            <li role="none">
                                <a href="<?php echo BASE_PATH; ?>/index.php?page=services" class="dropdown-item" role="menuitem">
                                    Our Services
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item has-dropdown">
                        <button class="nav-link nav-link-toggle" aria-expanded="false" aria-haspopup="true">
                            Products
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li role="none">
                                <a href="<?php echo BASE_PATH; ?>/index.php?page=products" class="dropdown-item" role="menuitem">
                                    All Products
                                </a>
                            </li>
                            <li role="none">
                                <a href="<?php echo BASE_PATH; ?>/index.php?page=solar-pumps" class="dropdown-item" role="menuitem">
                                    Solar Water Pumps
                                </a>
                            </li>
                            <li role="none">
                                <a href="<?php echo BASE_PATH; ?>/index.php?page=surface-pumps" class="dropdown-item" role="menuitem">
                                    Surface Pumps
                                </a>
                            </li>
                            <li role="none">
                                <a href="<?php echo BASE_PATH; ?>/index.php?page=submersible-pumps" class="dropdown-item" role="menuitem">
                                    Submersible Pumps
                                </a>
                            </li>
                            <li role="none">
                                <a href="<?php echo BASE_PATH; ?>/index.php?page=processing-machines" class="dropdown-item" role="menuitem">
                                    Processing Machines
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?php echo BASE_PATH; ?>/index.php?page=about" class="nav-link <?php echo $currentSlug === 'about' || $currentSlug === 'who-we-are' ? 'active' : ''; ?>" aria-current="<?php echo ($currentSlug === 'about' || $currentSlug === 'who-we-are') ? 'page' : 'false'; ?>">
                            Who We Are
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?php echo BASE_PATH; ?>/index.php?page=impact" class="nav-link <?php echo $currentSlug === 'impact' ? 'active' : ''; ?>" aria-current="<?php echo $currentSlug === 'impact' ? 'page' : 'false'; ?>">
                            Our Impact
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?php echo BASE_PATH; ?>/index.php?page=career" class="nav-link <?php echo $currentSlug === 'career' ? 'active' : ''; ?>" aria-current="<?php echo $currentSlug === 'career' ? 'page' : 'false'; ?>">
                            Careers
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?php echo BASE_PATH; ?>/blog/index.php" class="nav-link <?php echo $currentSlug === 'blog' ? 'active' : ''; ?>" aria-current="<?php echo $currentSlug === 'blog' ? 'page' : 'false'; ?>">
                            Blog
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?php echo BASE_PATH; ?>/index.php?page=contact" class="nav-link <?php echo $currentSlug === 'contact' ? 'active' : ''; ?>" aria-current="<?php echo $currentSlug === 'contact' ? 'page' : 'false'; ?>">
                            Contact
                        </a>
                    </li>
                </ul>

                <!-- CTA Button -->
                <a href="<?php echo BASE_PATH; ?>/index.php?page=products#calculator" class="btn btn-primary btn-cta">
                    <span class="btn-text">Get Calculator</span>
                    <i class="fas fa-calculator" aria-hidden="true"></i>
                </a>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" type="button" aria-label="Toggle mobile menu" aria-expanded="false">
                    <span class="hamburger"></span>
                    <span class="hamburger"></span>
                    <span class="hamburger"></span>
                </button>
            </div>
        </div>
    </div>
</nav>
```

### 2.2 CSS Architecture

```css
/* ========================================
 * NAVIGATION - CSS VARIABLES
 * ======================================== */
:root {
    /* Colors */
    --nav-bg-transparent: transparent;
    --nav-bg-solid: rgba(255, 255, 255, 0.98);
    --nav-bg-blur: rgba(255, 255, 255, 0.9);
    
    --nav-text-white: #FFFFFF;
    --nav-text-dark: #2C2C2C;
    --nav-text-hover: #660099;
    --nav-text-active: #4A0066;
    
    --nav-primary: #660099;
    --nav-primary-hover: #4d0073;
    --nav-primary-light: #F0E6F7;
    
    --nav-border: rgba(255, 255, 255, 0.2);
    --nav-border-dark: rgba(0, 0, 0, 0.1);
    
    /* Typography */
    --nav-font: 'Inter', sans-serif;
    --nav-font-size: 1rem;
    --nav-font-weight: 500;
    
    /* Spacing */
    --nav-spacing-xs: 0.25rem;
    --nav-spacing-sm: 0.5rem;
    --nav-spacing-md: 1rem;
    --nav-spacing-lg: 1.5rem;
    --nav-spacing-xl: 2rem;
    
    /* Transitions */
    --nav-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    
    /* Shadows */
    --nav-shadow-light: 0 2px 8px rgba(0, 0, 0, 0.1);
    --nav-shadow-medium: 0 8px 32px rgba(0, 0, 0, 0.15);
    --nav-shadow-dropdown: 0 8px 32px rgba(0, 0, 0, 0.12);
    
    /* Border Radius */
    --nav-radius-sm: 6px;
    --nav-radius-md: 8px;
    --nav-radius-lg: 12px;
    --nav-radius-pill: 25px;
    
    /* Z-Index */
    --nav-z-top: 1000;
    --nav-z-dropdown: 1050;
    --nav-z-modal: 1100;
}

/* ========================================
 * NAVIGATION - RESET & BASE STYLES
 * ======================================== */
.main-navigation {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: var(--nav-z-top);
    transition: var(--nav-transition);
}

/* ========================================
 * NAVIGATION - TOP BAR
 * ======================================== */
.nav-top-bar {
    background: var(--nav-primary);
    color: var(--nav-text-white);
    font-size: 0.875rem;
    padding: 0.5rem 0;
}

.nav-top-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--nav-spacing-lg);
}

.contact-info {
    display: flex;
    gap: var(--nav-spacing-lg);
}

.contact-link {
    display: flex;
    align-items: center;
    gap: var(--nav-spacing-xs);
    color: var(--nav-text-white);
    transition: var(--nav-transition);
}

.contact-link:hover {
    color: var(--nav-text-white);
    opacity: 0.9;
}

.contact-link i {
    font-size: 0.9rem;
}

.social-links {
    display: flex;
    gap: var(--nav-spacing-sm);
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: var(--nav-text-white);
    transition: var(--nav-transition);
}

.social-link:hover {
    background: var(--nav-text-white);
    color: var(--nav-primary);
}

/* ========================================
 * NAVIGATION - MAIN BAR
 * ======================================== */
.nav-main-bar {
    background: var(--nav-bg-transparent);
    backdrop-filter: blur(0px);
    -webkit-backdrop-filter: blur(0px);
    transition: var(--nav-transition);
}

.nav-main-bar.scrolled {
    background: var(--nav-bg-solid);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: var(--nav-shadow-medium);
    border-bottom: 1px solid var(--nav-border-dark);
}

.nav-main-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--nav-spacing-xl);
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 3rem;
}

/* ========================================
 * NAVIGATION - LOGO
 * ======================================== */
.nav-logo {
    display: flex;
    align-items: center;
    gap: var(--nav-spacing-sm);
    text-decoration: none;
    flex-shrink: 0;
    transition: var(--nav-transition);
}

.nav-logo img {
    height: 45px;
    transition: var(--nav-transition);
}

.nav-main-bar.scrolled .nav-logo img {
    height: 40px;
}

/* ========================================
 * NAVIGATION - DESKTOP MENU
 * ======================================== */
.nav-menu {
    display: flex;
    align-items: center;
    gap: var(--nav-spacing-lg);
    flex: 1;
    justify-content: center;
}

.nav-menu-desktop {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: var(--nav-spacing-lg);
}

.nav-item {
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: var(--nav-spacing-xs);
    color: var(--nav-text-white);
    text-decoration: none;
    font-family: var(--nav-font);
    font-size: var(--nav-font-size);
    font-weight: var(--nav-font-weight);
    padding: 0.5rem 1rem;
    border-radius: var(--nav-radius-md);
    transition: var(--nav-transition);
}

.nav-main-bar.scrolled .nav-link {
    color: var(--nav-text-dark);
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
}

.nav-main-bar.scrolled .nav-link:hover {
    background: var(--nav-primary-light);
    color: var(--nav-text-hover);
}

.nav-link.active {
    color: var(--nav-text-white);
    font-weight: 600;
}

.nav-main-bar.scrolled .nav-link.active {
    color: var(--nav-text-active);
}

/* Dropdown Toggle */
.nav-link-toggle {
    cursor: pointer;
    border: none;
    background: none;
    padding: 0;
}

.nav-link-toggle:hover {
    background: none;
}

.nav-link-toggle:focus {
    outline: 2px solid var(--nav-primary);
    outline-offset: 2px;
}

.nav-link-toggle .fa-chevron-down {
    margin-left: var(--nav-spacing-xs);
    font-size: 0.75rem;
    transition: transform 0.2s ease;
}

.nav-link-toggle[aria-expanded="true"] .fa-chevron-down {
    transform: rotate(180deg);
}

/* ========================================
 * NAVIGATION - DROPDOWN MENU
 * ======================================== */
.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--nav-bg-solid);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: var(--nav-shadow-dropdown);
    border: 1px solid var(--nav-border-dark);
    border-radius: var(--nav-radius-lg);
    padding: var(--nav-spacing-sm) 0;
    min-width: 220px;
    margin-top: var(--nav-spacing-sm);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--nav-transition);
}

.nav-item:hover .dropdown-menu,
.dropdown-menu[aria-expanded="true"] {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu .dropdown-item {
    display: block;
    width: 100%;
    padding: 0.75rem 1.5rem;
    color: var(--nav-text-dark);
    text-decoration: none;
    font-family: var(--nav-font);
    font-size: 0.95rem;
    font-weight: 500;
    transition: var(--nav-transition);
}

.dropdown-menu .dropdown-item:hover,
.dropdown-menu .dropdown-item:focus {
    background: var(--nav-primary);
    color: var(--nav-text-white);
    transform: translateX(5px);
}

/* ========================================
 * NAVIGATION - CTA BUTTON
 * ======================================== */
.btn-cta {
    display: inline-flex;
    align-items: center;
    gap: var(--nav-spacing-xs);
    background: var(--nav-primary);
    color: var(--nav-text-white);
    font-family: var(--nav-font);
    font-size: 0.9375rem;
    font-weight: 600;
    padding: 0.625rem 1.5rem;
    border-radius: var(--nav-radius-lg);
    text-decoration: none;
    transition: var(--nav-transition);
    box-shadow: var(--nav-shadow-light);
    border: none;
    cursor: pointer;
    white-space: nowrap;
}

.btn-cta:hover {
    background: var(--nav-primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 0, 153, 0.4);
}

.btn-cta:focus {
    outline: 2px solid var(--nav-primary);
    outline-offset: 2px;
}

.btn-cta i {
    font-size: 1rem;
}

/* ========================================
 * NAVIGATION - MOBILE MENU TOGGLE
 * ======================================== */
.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: transparent;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    gap: 4px;
}

.mobile-menu-toggle:focus {
    outline: 2px solid var(--nav-primary);
    outline-offset: 2px;
}

.hamburger {
    width: 25px;
    height: 3px;
    background: var(--nav-text-white);
    border-radius: 2px;
    transition: var(--nav-transition);
}

.nav-main-bar.scrolled .hamburger {
    background: var(--nav-text-dark);
}

.mobile-menu-toggle:hover .hamburger {
    background: rgba(255, 255, 255, 0.9);
}

.nav-main-bar.scrolled .mobile-menu-toggle:hover .hamburger {
    background: var(--nav-text-dark);
}

/* ========================================
 * NAVIGATION - MOBILE MENU
 * ======================================== */
@media (max-width: 1200px) {
    .nav-main-content {
        padding: 1rem 2rem;
    }
}

@media (max-width: 991.98px) {
    .nav-top-bar {
        display: none;
    }

    .nav-main-bar {
        background: var(--nav-bg-solid);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: var(--nav-shadow-medium);
    }

    .nav-main-content {
        padding: 1rem;
        gap: 1rem;
    }

    .nav-menu {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--nav-bg-solid);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        padding: 4rem 1rem;
        z-index: 999;
        overflow-y: auto;
    }

    .nav-menu.show {
        display: flex;
    }

    .nav-menu-desktop {
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
    }

    .nav-item {
        width: 100%;
        text-align: center;
    }

    .nav-link {
        display: block;
        padding: 1rem 1.5rem;
        border-radius: var(--nav-radius-md);
        font-size: 1.125rem;
        justify-content: center;
    }

    .nav-link-toggle {
        justify-content: center;
    }

    .dropdown-menu {
        position: static;
        box-shadow: none;
        border: none;
        border-radius: 0;
        padding: 0;
        margin: 0;
        background: transparent;
        opacity: 1;
        visibility: visible;
        transform: none;
        display: none;
    }

    .dropdown-menu.show {
        display: block;
    }

    .dropdown-menu .dropdown-item {
        padding-left: 3rem;
        color: var(--nav-text-secondary);
    }

    .dropdown-menu .dropdown-item:hover {
        background: transparent;
        color: var(--nav-primary);
        transform: none;
    }

    .btn-cta {
        display: none;
    }

    .mobile-menu-toggle {
        display: flex;
    }

    /* Overlay for mobile menu */
    .nav-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 998;
    }

    .nav-overlay.show {
        display: block;
    }
}

@media (max-width: 768px) {
    .nav-main-content {
        padding: 0.875rem 1rem;
    }

    .nav-logo img {
        height: 40px;
    }

    .nav-main-bar.scrolled .nav-logo img {
        height: 35px;
    }

    .mobile-menu-toggle {
        width: 40px;
        height: 40px;
    }

    .hamburger {
        width: 20px;
    }
}

@media (max-width: 576px) {
    .nav-main-content {
        padding: 0.75rem 0.75rem;
    }

    .nav-logo img {
        height: 35px;
    }

    .nav-main-bar.scrolled .nav-logo img {
        height: 30px;
    }

    .mobile-menu-toggle {
        width: 36px;
        height: 36px;
    }

    .hamburger {
        width: 18px;
    }
}

/* ========================================
 * NAVIGATION - ACCESSIBILITY
 * ======================================== */
/* Focus styles for keyboard navigation */
.nav-link:focus,
.nav-link-toggle:focus,
.btn-cta:focus,
.mobile-menu-toggle:focus {
    outline: 2px solid var(--nav-primary);
    outline-offset: 2px;
}

/* Skip link for accessibility */
.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: var(--nav-primary);
    color: var(--nav-text-white);
    padding: 8px 16px;
    z-index: 10000;
    transition: top 0.3s;
}

.skip-link:focus {
    top: 0;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

### 2.3 JavaScript Functionality

```javascript
/**
 * Navigation JavaScript
 * Handles mobile menu toggle, dropdown interactions, and scroll effects
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.nav-menu');
    const navOverlay = document.querySelector('.nav-overlay');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('show');
            
            if (navOverlay) {
                navOverlay.classList.toggle('show');
            }
            
            // Prevent body scroll when menu is open
            document.body.style.overflow = isExpanded ? '' : 'hidden';
        });
    }
    
    // Close mobile menu when clicking overlay
    if (navOverlay) {
        navOverlay.addEventListener('click', function() {
            if (mobileToggle) {
                mobileToggle.click();
            }
        });
    }
    
    // Close mobile menu when clicking a link
    const mobileLinks = mobileMenu.querySelectorAll('.nav-link');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (mobileToggle) {
                mobileToggle.click();
            }
        });
    });
    
    // Dropdown toggle for mobile
    const dropdownToggles = document.querySelectorAll('.nav-link-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                dropdown.classList.toggle('show');
            }
        });
    });
    
    // Scroll effect for navbar
    const navMainBar = document.querySelector('.nav-main-bar');
    
    if (navMainBar) {
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    if (window.scrollY > 50) {
                        navMainBar.classList.add('scrolled');
                    } else {
                        navMainBar.classList.remove('scrolled');
                    }
                    ticking = false;
                });
                ticking = true;
            }
        });
    }
    
    // Handle keyboard navigation for dropdowns
    const dropdownItems = document.querySelectorAll('.dropdown-menu .dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.closest('.dropdown-menu').classList.remove('show');
                this.closest('.nav-item').querySelector('.nav-link-toggle')?.setAttribute('aria-expanded', 'false');
            }
        });
    });
});
```

---

## 3. Footer Redesign

### 3.1 HTML Structure

```html
<!-- Modern Professional Footer -->
<footer class="modern-footer" role="contentinfo">
    <!-- Background -->
    <div class="footer-background">
        <div class="footer-overlay"></div>
    </div>
    
    <div class="container">
        <!-- Main Footer Content -->
        <div class="footer-main">
            <div class="row g-5">
                
                <!-- Brand Section -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <div class="brand-logo">
                            <img src="<?php echo BASE_PATH; ?>/static/images/white_logo.png" alt="<?php echo e($siteName); ?>" class="footer-logo" height="50">
                        </div>
                        <h3 class="brand-name">Tulima Solar</h3>
                        <p class="brand-description">
                            Empowering African farmers with sustainable solar solutions since 2019. We provide professional, reliable, and cost-effective solar-powered equipment for modern agriculture.
                        </p>
                        
                        <!-- Newsletter Signup -->
                        <div class="newsletter-signup">
                            <h5 class="newsletter-title">Stay Updated</h5>
                            <form class="newsletter-form" novalidate>
                                <div class="input-group">
                                    <input type="email" class="form-control newsletter-input" placeholder="Enter your email" aria-label="Email address" required>
                                    <button class="btn btn-primary newsletter-submit" type="submit" aria-label="Subscribe">
                                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <small class="newsletter-hint">Subscribe to our newsletter for updates and offers.</small>
                            </form>
                        </div>
                        
                        <!-- Social Links -->
                        <div class="social-links">
                            <h6 class="social-title">Follow Us</h6>
                            <div class="social-icons">
                                <a href="https://www.facebook.com/TulimaSolarUganda" class="social-icon" aria-label="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/TulimaSolarUg" class="social-icon" aria-label="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://ug.linkedin.com/in/tulima-solar-1817941a2" class="social-icon" aria-label="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="https://www.instagram.com/tulimasolarugltd" class="social-icon" aria-label="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="https://www.youtube.com/channel/UCmiAeg3RNof682wBGdeCHIw" class="social-icon" aria-label="YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-section">
                        <h5 class="section-title">Quick Links</h5>
                        <ul class="footer-links">
                            <li><a href="<?php echo BASE_PATH; ?>/" class="footer-link">Home</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=about" class="footer-link">About Us</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=services" class="footer-link">Services</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=products" class="footer-link">Products</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=impact" class="footer-link">Our Impact</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=contact" class="footer-link">Contact</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/blog/index.php" class="footer-link">Blog</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Products -->
                <div class="col-lg-3 col-md-6">
                    <div class="footer-section">
                        <h5 class="section-title">Our Products</h5>
                        <ul class="footer-links">
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=solar-pumps" class="footer-link">Solar Water Pumps</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=surface-pumps" class="footer-link">Surface Pumps</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=submersible-pumps" class="footer-link">Submersible Pumps</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=processing-machines" class="footer-link">Processing Machines</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=services" class="footer-link">Services</a></li>
                            <li><a href="<?php echo BASE_PATH; ?>/index.php?page=calculator" class="footer-link">Solar Calculator</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="col-lg-3 col-md-6">
                    <div class="footer-section">
                        <h5 class="section-title">Contact Us</h5>
                        <div class="contact-info">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                </div>
                                <div class="contact-content">
                                    <h6 class="contact-label">Address</h6>
                                    <p class="contact-text">Plot 483, Kaduyu road, Ntinda - Kiwatule</p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone" aria-hidden="true"></i>
                                </div>
                                <div class="contact-content">
                                    <h6 class="contact-label">Phone</h6>
                                    <a href="tel:+256800388883" class="contact-text">+256 800 388 883</a>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope" aria-hidden="true"></i>
                                </div>
                                <div class="contact-content">
                                    <h6 class="contact-label">Email</h6>
                                    <a href="mailto:info@tulimasolar.com" class="contact-text">info@tulimasolar.com</a>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-clock" aria-hidden="true"></i>
                                </div>
                                <div class="contact-content">
                                    <h6 class="contact-label">Business Hours</h6>
                                    <p class="contact-text">Mon - Fri: 8:00 AM - 6:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">&copy; <?php echo date('Y'); ?> <?php echo e($siteName); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6">
                    <div class="footer-legal">
                        <a href="<?php echo BASE_PATH; ?>/index.php?page=privacy" class="legal-link" aria-label="Privacy Policy">Privacy Policy</a>
                        <span class="legal-separator">|</span>
                        <a href="<?php echo BASE_PATH; ?>/index.php?page=terms" class="legal-link" aria-label="Terms & Conditions">Terms & Conditions</a>
                        <span class="legal-separator">|</span>
                        <a href="<?php echo BASE_PATH; ?>/index.php?page=sitemap" class="legal-link" aria-label="Sitemap">Sitemap</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back to Top Button -->
        <button class="back-to-top" type="button" aria-label="Back to top">
            <i class="fas fa-arrow-up" aria-hidden="true"></i>
        </button>
    </div>
</footer>
```

### 3.2 CSS Architecture

```css
/* ========================================
 * FOOTER - CSS VARIABLES
 * ======================================== */
:root {
    /* Colors */
    --footer-bg: #1a1a2e;
    --footer-bg-secondary: #16213e;
    --footer-text-primary: #ffffff;
    --footer-text-secondary: #a0a0a0;
    --footer-text-muted: #6b6b6b;
    --footer-primary: #660099;
    --footer-primary-hover: #4d0073;
    --footer-border: rgba(255, 255, 255, 0.1);
    --footer-border-light: rgba(255, 255, 255, 0.05);
    
    /* Gradients */
    --footer-gradient: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    
    /* Typography */
    --footer-font: 'Inter', sans-serif;
    --footer-font-size: 0.9375rem;
    --footer-heading-size: 1.125rem;
    --footer-heading-weight: 600;
    
    /* Spacing */
    --footer-spacing-xs: 0.25rem;
    --footer-spacing-sm: 0.5rem;
    --footer-spacing-md: 1rem;
    --footer-spacing-lg: 1.5rem;
    --footer-spacing-xl: 2rem;
    --footer-spacing-2xl: 3rem;
    
    /* Border Radius */
    --footer-radius-sm: 4px;
    --footer-radius-md: 8px;
    --footer-radius-lg: 12px;
    --footer-radius-pill: 50px;
    
    /* Transitions */
    --footer-transition: all 0.3s ease;
    
    /* Shadows */
    --footer-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

/* ========================================
 * FOOTER - BASE STYLES
 * ======================================== */
.modern-footer {
    background: var(--footer-gradient);
    color: var(--footer-text-primary);
    position: relative;
    overflow: hidden;
    padding-top: var(--footer-spacing-2xl);
    padding-bottom: var(--footer-spacing-2xl);
}

/* Background Effects */
.footer-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 0;
}

.footer-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 50%, rgba(102, 0, 153, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(176, 215, 171, 0.1) 0%, transparent 50%);
}

/* ========================================
 * FOOTER - BRAND SECTION
 * ======================================== */
.footer-brand {
    display: flex;
    flex-direction: column;
    gap: var(--footer-spacing-lg);
}

.brand-logo {
    display: flex;
    align-items: center;
}

.footer-logo {
    height: 50px;
    transition: var(--footer-transition);
}

.footer-logo:hover {
    transform: scale(1.05);
}

.brand-name {
    font-family: var(--footer-font);
    font-size: var(--footer-heading-size);
    font-weight: var(--footer-heading-weight);
    color: var(--footer-text-primary);
    margin-bottom: var(--footer-spacing-sm);
}

.brand-description {
    font-size: var(--footer-font-size);
    line-height: 1.7;
    color: var(--footer-text-secondary);
    margin-bottom: var(--footer-spacing-lg);
}

/* Newsletter Signup */
.newsletter-signup {
    background: rgba(255, 255, 255, 0.05);
    padding: var(--footer-spacing-lg);
    border-radius: var(--footer-radius-lg);
    border: 1px solid var(--footer-border-light);
}

.newsletter-title {
    font-family: var(--footer-font);
    font-size: var(--footer-heading-size);
    font-weight: var(--footer-heading-weight);
    color: var(--footer-text-primary);
    margin-bottom: var(--footer-spacing-md);
}

.newsletter-form {
    display: flex;
    gap: var(--footer-spacing-sm);
}

.newsletter-input {
    flex: 1;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid var(--footer-border);
    border-radius: var(--footer-radius-md);
    padding: 0.75rem 1rem;
    color: var(--footer-text-primary);
    font-family: var(--footer-font);
    font-size: var(--footer-font-size);
    transition: var(--footer-transition);
}

.newsletter-input::placeholder {
    color: var(--footer-text-muted);
}

.newsletter-input:focus {
    outline: none;
    border-color: var(--footer-primary);
    background: rgba(255, 255, 255, 0.15);
}

.newsletter-submit {
    background: var(--footer-primary);
    color: var(--footer-text-primary);
    border: none;
    border-radius: var(--footer-radius-md);
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: var(--footer-transition);
}

.newsletter-submit:hover {
    background: var(--footer-primary-hover);
    transform: translateY(-2px);
}

.newsletter-submit:focus {
    outline: 2px solid var(--footer-primary);
    outline-offset: 2px;
}

.newsletter-hint {
    display: block;
    font-size: 0.75rem;
    color: var(--footer-text-muted);
    margin-top: var(--footer-spacing-sm);
}

/* Social Links */
.social-links {
    margin-top: var(--footer-spacing-lg);
}

.social-title {
    font-family: var(--footer-font);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--footer-text-secondary);
    margin-bottom: var(--footer-spacing-md);
}

.social-icons {
    display: flex;
    gap: var(--footer-spacing-sm);
}

.social-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: var(--footer-text-primary);
    transition: var(--footer-transition);
}

.social-icon:hover {
    background: var(--footer-primary);
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(102, 0, 153, 0.4);
}

/* ========================================
 * FOOTER - SECTIONS
 * ======================================== */
.footer-section {
    display: flex;
    flex-direction: column;
    gap: var(--footer-spacing-md);
}

.section-title {
    font-family: var(--footer-font);
    font-size: var(--footer-heading-size);
    font-weight: var(--footer-heading-weight);
    color: var(--footer-text-primary);
    margin-bottom: var(--footer-spacing-sm);
}

.footer-links {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--footer-spacing-sm);
}

.footer-link {
    display: block;
    color: var(--footer-text-secondary);
    text-decoration: none;
    font-size: var(--footer-font-size);
    transition: var(--footer-transition);
    padding: 0.5rem 0;
}

.footer-link:hover {
    color: var(--footer-primary);
    transform: translateX(5px);
}

/* ========================================
 * FOOTER - CONTACT INFO
 * ======================================== */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: var(--footer-spacing-lg);
}

.contact-item {
    display: flex;
    gap: var(--footer-spacing-md);
    align-items: flex-start;
}

.contact-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--footer-radius-md);
    color: var(--footer-primary);
    flex-shrink: 0;
    transition: var(--footer-transition);
}

.contact-item:hover .contact-icon {
    background: rgba(102, 0, 153, 0.2);
    transform: translateY(-2px);
}

.contact-content {
    display: flex;
    flex-direction: column;
    gap: var(--footer-spacing-xs);
}

.contact-label {
    font-family: var(--footer-font);
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--footer-text-secondary);
}

.contact-text {
    font-size: var(--footer-font-size);
    color: var(--footer-text-secondary);
    text-decoration: none;
    transition: var(--footer-transition);
}

.contact-text:hover {
    color: var(--footer-text-primary);
}

/* ========================================
 * FOOTER - BOTTOM BAR
 * ======================================== */
.footer-bottom {
    margin-top: var(--footer-spacing-2xl);
    padding-top: var(--footer-spacing-xl);
    border-top: 1px solid var(--footer-border);
}

.copyright {
    font-family: var(--footer-font);
    font-size: 0.875rem;
    color: var(--footer-text-muted);
    margin: 0;
}

.footer-legal {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--footer-spacing-md);
    flex-wrap: wrap;
}

.legal-link {
    color: var(--footer-text-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: var(--footer-transition);
}

.legal-link:hover {
    color: var(--footer-text-primary);
}

.legal-separator {
    color: var(--footer-border-light);
    font-size: 0.875rem;
}

/* ========================================
 * FOOTER - BACK TO TOP
 * ======================================== */
.back-to-top {
    position: absolute;
    top: -60px;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: var(--footer-primary);
    color: var(--footer-text-primary);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    box-shadow: var(--footer-shadow);
    transition: var(--footer-transition);
    opacity: 0;
    visibility: hidden;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    background: var(--footer-primary-hover);
    transform: translateY(-5px);
}

.back-to-top:focus {
    outline: 2px solid var(--footer-primary);
    outline-offset: 2px;
}

/* ========================================
 * FOOTER - RESPONSIVE DESIGN
 * ======================================== */
@media (max-width: 991.98px) {
    .footer-brand {
        order: 1;
    }
    
    .footer-section {
        order: 2;
    }
    
    .back-to-top {
        right: 1rem;
    }
}

@media (max-width: 768px) {
    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: var(--footer-spacing-md);
    }
    
    .footer-legal {
        justify-content: center;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .newsletter-submit {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .footer-main .row > div {
        width: 100%;
    }
    
    .back-to-top {
        right: 0.75rem;
    }
}

/* ========================================
 * FOOTER - ACCESSIBILITY
 * ======================================== */
/* Focus styles */
.footer-link:focus,
.legal-link:focus,
.newsletter-submit:focus,
.back-to-top:focus {
    outline: 2px solid var(--footer-primary);
    outline-offset: 2px;
}

/* Skip link */
.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: var(--footer-primary);
    color: var(--footer-text-primary);
    padding: 8px 16px;
    z-index: 10000;
    transition: top 0.3s;
}

.skip-link:focus {
    top: 0;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

### 3.3 JavaScript Functionality

```javascript
/**
 * Footer JavaScript
 * Handles newsletter signup, back to top button, and smooth scrolling
 */

document.addEventListener('DOMContentLoaded', function() {
    // Back to Top Button
    const backToTop = document.querySelector('.back-to-top');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Newsletter Signup
    const newsletterForms = document.querySelectorAll('.newsletter-form');
    
    newsletterForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const input = this.querySelector('.newsletter-input');
            const email = input.value.trim();
            const hint = this.querySelector('.newsletter-hint');
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && emailRegex.test(email)) {
                // Simulate API call
                hint.textContent = 'Subscribing...';
                hint.style.color = 'var(--footer-primary)';
                
                setTimeout(() => {
                    hint.textContent = 'Thank you for subscribing!';
                    hint.style.color = 'var(--secondary-green)';
                    input.value = '';
                    
                    setTimeout(() => {
                        hint.textContent = 'Subscribe to our newsletter for updates and offers.';
                        hint.style.color = 'var(--footer-text-muted)';
                    }, 3000);
                }, 1000);
            } else {
                hint.textContent = 'Please enter a valid email address.';
                hint.style.color = '#E98E39';
                
                setTimeout(() => {
                    hint.textContent = 'Subscribe to our newsletter for updates and offers.';
                    hint.style.color = 'var(--footer-text-muted)';
                }, 3000);
            }
        });
    });
    
    // Smooth scroll for footer links
    const footerLinks = document.querySelectorAll('.footer-link, .legal-link');
    
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
});
```

---

## 4. Responsive Design Strategy

### 4.1 Breakpoints

```css
/* Breakpoint Strategy */
/* 
   Mobile First Approach
   - Default styles are for mobile devices
   - Media queries progressively enhance for larger screens
*/

/* Mobile - Base Styles */
@media (max-width: 576px) {
    /* Smallest mobile devices */
}

/* Tablet */
@media (min-width: 577px) and (max-width: 768px) {
    /* Medium tablets */
}

/* Desktop */
@media (min-width: 769px) and (max-width: 991.98px) {
    /* Standard desktops */
}

/* Large Desktop */
@media (min-width: 992px) and (max-width: 1200px) {
    /* Large screens and laptops */
}

/* Extra Large Desktop */
@media (min-width: 1201px) {
    /* Ultra-wide screens */
}
```

### 4.2 Mobile Menu Strategy

**Hamburger Menu:**
- Hidden on desktop (desktop menu always visible)
- Visible on mobile/tablet
- Full-screen overlay with blur effect
- Keyboard accessible (Escape to close)
- Smooth animations

**Dropdowns on Mobile:**
- Accordion-style dropdowns
- Click to toggle
- Proper ARIA attributes
- Smooth transitions

### 4.3 Touch Optimization

```css
/* Touch-friendly targets */
.nav-link,
.nav-link-toggle,
.btn-cta,
.mobile-menu-toggle,
.social-icon,
.contact-item {
    min-height: 44px;
    min-width: 44px;
}

/* Prevent zoom on double tap */
* {
    touch-action: manipulation;
}
```

---

## 5. Accessibility Features

### 5.1 Semantic HTML

- [`<nav>`](includes/header-test-site.php:875) for navigation
- [`<footer>`](includes/footer-test-site.php:4) for footer content
- [`<main>`](includes/header-test-site.php:951) for main content
- Proper heading hierarchy (h1-h6)
- ARIA labels and roles
- Focus indicators

### 5.2 Keyboard Navigation

- Tab navigation through all interactive elements
- Enter/Space to activate buttons and links
- Escape to close dropdowns and mobile menus
- Visible focus states
- Skip link for keyboard users

### 5.3 Screen Reader Support

```html
<!-- ARIA Attributes -->
<nav role="navigation" aria-label="Main navigation">
<button aria-expanded="false" aria-haspopup="true">
<a href="#" aria-current="page">
```

### 5.4 Color Contrast

- All text meets WCAG AA standards (4.5:1 minimum)
- Focus states have high contrast
- Disabled states are clearly visible

---

## 6. Performance Considerations

### 6.1 CSS Optimization

- Use CSS variables for maintainability
- Group related styles
- Avoid excessive nesting
- Use efficient selectors

### 6.2 JavaScript Optimization

- Defer non-critical JavaScript
- Use event delegation
- Avoid inline event handlers
- Optimize animations with `transform` and `opacity`

### 6.3 Image Optimization

- Use SVG logos for fast loading
- Lazy load images if needed
- Provide appropriate alt text

---

## 7. Implementation Steps

### Phase 1: Header Redesign
1. Create new navigation HTML structure
2. Add navigation CSS variables and styles
3. Implement JavaScript for mobile menu and scroll effects
4. Test on all breakpoints
5. Verify accessibility features

### Phase 2: Footer Redesign
1. Create new footer HTML structure
2. Add footer CSS variables and styles
3. Implement JavaScript for newsletter and back to top
4. Test on all breakpoints
5. Verify accessibility features

### Phase 3: Integration
1. Update [`includes/header-test-site.php`](includes/header-test-site.php)
2. Update [`includes/footer-test-site.php`](includes/footer-test-site.php)
3. Remove old navigation and footer code
4. Test end-to-end functionality
5. Cross-browser testing

---

## 8. Testing Checklist

### 8.1 Functionality
- [ ] All navigation links work correctly
- [ ] Dropdowns open/close properly
- [ ] Mobile menu toggles correctly
- [ ] CTA button links to calculator
- [ ] Newsletter signup validates email
- [ ] Back to top button works
- [ ] Scroll effect works on navbar

### 8.2 Responsive Design
- [ ] Mobile menu appears on small screens
- [ ] Desktop menu hides on mobile
- [ ] All sections stack correctly on mobile
- [ ] Footer layout adapts to screen size
- [ ] Navigation fits within viewport

### 8.3 Accessibility
- [ ] Keyboard navigation works
- [ ] Screen reader announces elements
- [ ] Focus indicators are visible
- [ ] Color contrast meets WCAG AA
- [ ] Skip link works
- [ ] ARIA attributes are correct

### 8.4 Cross-Browser
- [ ] Works in Chrome
- [ ] Works in Firefox
- [ ] Works in Safari
- [ ] Works in Edge
- [ ] Works on iOS Safari
- [ ] Works on Android Chrome

---

## 9. Browser Support

- Chrome: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions
- Edge: Latest 2 versions
- iOS Safari: Latest 2 versions
- Android Chrome: Latest 2 versions

---

## 10. Maintenance Notes

### 10.1 Updating Navigation Links

When adding or removing navigation links:
1. Update the HTML structure in [`includes/header-test-site.php`](includes/header-test-site.php)
2. Update the PHP conditionals for active states
3. Test all links work correctly
4. Update mobile menu if needed

### 10.2 Updating Footer Content

When updating footer content:
1. Edit the HTML in [`includes/footer-test-site.php`](includes/footer-test-site.php)
2. Update contact information
3. Update social media links
4. Update legal links
5. Test all links work correctly

---

## 11. Conclusion

This comprehensive design document provides a complete blueprint for redesigning the navigation and footer sections of the Tulima Solar website. The design focuses on:

- **Modern, professional appearance** aligned with brand guidelines
- **Excellent accessibility** with full keyboard and screen reader support
- **Responsive design** that works seamlessly across all devices
- **Clean, maintainable code** following best practices
- **Performance optimization** for fast loading

The implementation should follow the phased approach outlined in Section 7 to ensure a smooth transition and thorough testing at each step.
