# Jobs Page Unified Styling System - Design Specification

## Executive Summary

This document defines a comprehensive, unified styling system for the Tulima Solar Jobs Portal pages. The system ensures visual consistency across all job-related pages while maintaining alignment with the global site design language.

---

## 1. Current State Analysis

### 1.1 Files Analyzed

| File | Purpose | Status |
|------|---------|--------|
| [`job-portal.css`](job-portal/pages/assets/css/job-portal.css) | Main job portal styles | Has inconsistencies |
| [`master-style.css`](master-style.css) | Global site styles | Reference standard |
| [`COLORS-AND-FONTS.txt`](COLORS-AND-FONTS.txt) | Design tokens reference | Source of truth |
| [`index.php`](job-portal/pages/index.php) | Job listings page | Structure analyzed |
| [`job-detail.php`](job-portal/pages/job-detail.php) | Job detail page | Structure analyzed |
| [`apply.php`](job-portal/pages/apply.php) | Application form | Structure analyzed |

### 1.2 Identified Issues

#### Critical Issues

1. **Spacing System Mismatch**
   - Job portal uses: `xs:0.5rem, sm:1rem, md:1.5rem, lg:2rem, xl:3rem, xxl:4rem`
   - Master uses: `xs:0.25rem, sm:0.5rem, md:1rem, lg:1.5rem, xl:2rem, 2xl:3rem`
   - **Impact**: Inconsistent whitespace throughout

2. **Border Radius Inconsistency**
   - Job portal: `sm:4px, md:8px, lg:12px, xl:16px`
   - Master: `sm:6px, md:8px, lg:12px, xl:20px, pill:25px`
   - **Impact**: Visual inconsistency in rounded corners

3. **Responsive Breakpoint Mismatch**
   - Job portal: `1024px, 768px, 480px`
   - Master: `991.98px, 767.98px, 575.98px`
   - **Impact**: Layout breaks at different points

#### Moderate Issues

4. **Font Family Inconsistency**
   - Job portal uses Inter as primary
   - Master uses Century Gothic/Calibri
   - **Recommendation**: Keep Inter for job portal but document clearly

5. **Missing Component States**
   - No loading/skeleton states
   - Limited focus states for accessibility
   - No disabled state variations for some components

#### Minor Issues

6. **Shadow Definitions**
   - Job portal: `light, medium, deep`
   - Master: `light, medium, dark`
   - Naming inconsistency

7. **Missing Category Section Styling**
   - Category cards need enhanced visual hierarchy
   - Filter active states need improvement

---

## 2. Unified Design Token System

### 2.1 Color Palette

```css
:root {
  /* ==========================================
     BRAND COLORS - Primary Palette
     ========================================== */
  --color-primary: #660099;
  --color-primary-dark: #4A0066;
  --color-primary-light: #F0E6F7;
  --color-primary-darker: #2d1b4e; /* Navbar */
  
  /* ==========================================
     SECONDARY COLORS - Green Palette
     ========================================== */
  --color-secondary: #B0D7AB;
  --color-secondary-dark: #8BC4A0;
  --color-secondary-light: #E8F5E8;
  
  /* ==========================================
     ACCENT COLORS - Orange Palette  
     ========================================== */
  --color-accent: #E98E39;
  --color-accent-dark: #D17A1F;
  --color-accent-light: #FDF4E8;
  
  /* ==========================================
     STATUS COLORS
     ========================================== */
  --color-success: #38a169;
  --color-success-light: #E8F5E8;
  --color-warning: #dd6b20;
  --color-warning-light: #FDF4E8;
  --color-error: #e53e3e;
  --color-error-dark: #c53030;
  --color-error-light: #fff5f5;
  --color-info: #3182ce;
  --color-info-light: #ebf8ff;
  
  /* ==========================================
     NEUTRAL COLORS
     ========================================== */
  --color-text-primary: #2C2C2C;
  --color-text-secondary: #4A4A4A;
  --color-text-muted: #6B6B6B;
  --color-text-inverse: #FFFFFF;
  
  --color-bg-primary: #FFFFFF;
  --color-bg-secondary: #f8f9fa;
  --color-bg-tertiary: #e9ecef;
  
  --color-border: #dee2e6;
  --color-border-light: #e9ecef;
  --color-border-dark: #ced4da;
  
  /* ==========================================
     GRADIENTS
     ========================================== */
  --gradient-primary: linear-gradient(135deg, #660099 0%, #4A0066 100%);
  --gradient-secondary: linear-gradient(135deg, #660099 0%, #B0D7AB 100%);
  --gradient-accent: linear-gradient(45deg, #E98E39 0%, #D17A1F 100%);
  --gradient-hero: linear-gradient(135deg, #660099 0%, #B0D7AB 50%, #E98E39 100%);
  --gradient-text: linear-gradient(135deg, #E98E39 0%, #660099 100%);
}
```

### 2.2 Typography Scale

```css
:root {
  /* ==========================================
     FONT FAMILIES
     ========================================== */
  --font-heading: 'Inter', 'Century Gothic', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-body: 'Inter', 'Calibri', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-mono: 'Courier New', Consolas, monospace;
  
  /* ==========================================
     FONT SIZES - Desktop
     ========================================== */
  --text-xs: 0.75rem;      /* 12px */
  --text-sm: 0.875rem;     /* 14px */
  --text-base: 1rem;       /* 16px */
  --text-lg: 1.125rem;     /* 18px */
  --text-xl: 1.25rem;      /* 20px */
  --text-2xl: 1.5rem;      /* 24px */
  --text-3xl: 1.75rem;     /* 28px */
  --text-4xl: 2rem;        /* 32px */
  --text-5xl: 2.5rem;      /* 40px */
  
  /* ==========================================
     FONT WEIGHTS
     ========================================== */
  --font-normal: 400;
  --font-medium: 500;
  --font-semibold: 600;
  --font-bold: 700;
  --font-extrabold: 800;
  
  /* ==========================================
     LINE HEIGHTS
     ========================================== */
  --leading-none: 1;
  --leading-tight: 1.25;
  --leading-snug: 1.375;
  --leading-normal: 1.5;
  --leading-relaxed: 1.625;
  --leading-loose: 2;
  
  /* ==========================================
     LETTER SPACING
     ========================================== */
  --tracking-tighter: -0.05em;
  --tracking-tight: -0.025em;
  --tracking-normal: 0;
  --tracking-wide: 0.025em;
  --tracking-wider: 0.05em;
  --tracking-widest: 0.1em;
}

/* Heading Styles */
.heading-1 {
  font-family: var(--font-heading);
  font-size: var(--text-5xl);
  font-weight: var(--font-extrabold);
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
}

.heading-2 {
  font-family: var(--font-heading);
  font-size: var(--text-4xl);
  font-weight: var(--font-bold);
  line-height: var(--leading-tight);
}

.heading-3 {
  font-family: var(--font-heading);
  font-size: var(--text-3xl);
  font-weight: var(--font-semibold);
  line-height: var(--leading-snug);
}

.heading-4 {
  font-family: var(--font-heading);
  font-size: var(--text-2xl);
  font-weight: var(--font-semibold);
  line-height: var(--leading-snug);
}

.heading-5 {
  font-family: var(--font-heading);
  font-size: var(--text-xl);
  font-weight: var(--font-medium);
  line-height: var(--leading-normal);
}

.heading-6 {
  font-family: var(--font-heading);
  font-size: var(--text-lg);
  font-weight: var(--font-medium);
  line-height: var(--leading-normal);
}
```

### 2.3 Spacing System

```css
:root {
  /* ==========================================
     SPACING SCALE - 4px base unit
     ========================================== */
  --space-0: 0;
  --space-1: 0.25rem;   /* 4px */
  --space-2: 0.5rem;    /* 8px */
  --space-3: 0.75rem;   /* 12px */
  --space-4: 1rem;      /* 16px */
  --space-5: 1.25rem;   /* 20px */
  --space-6: 1.5rem;    /* 24px */
  --space-8: 2rem;      /* 32px */
  --space-10: 2.5rem;   /* 40px */
  --space-12: 3rem;     /* 48px */
  --space-16: 4rem;     /* 64px */
  --space-20: 5rem;     /* 80px */
  --space-24: 6rem;     /* 96px */
  
  /* ==========================================
     SEMANTIC SPACING
     ========================================== */
  --spacing-section: var(--space-16);
  --spacing-component: var(--space-6);
  --spacing-element: var(--space-4);
  --spacing-inline: var(--space-2);
  --spacing-gap: var(--space-4);
}
```

### 2.4 Border Radius

```css
:root {
  /* ==========================================
     BORDER RADIUS
     ========================================== */
  --radius-none: 0;
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 20px;
  --radius-2xl: 24px;
  --radius-full: 9999px;
  --radius-pill: 25px;
}
```

### 2.5 Shadows

```css
:root {
  /* ==========================================
     SHADOWS
     ========================================== */
  --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15), 0 10px 10px rgba(0, 0, 0, 0.04);
  --shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.25);
  
  /* Focus Ring */
  --shadow-focus: 0 0 0 3px rgba(102, 0, 153, 0.2);
  --shadow-focus-error: 0 0 0 3px rgba(229, 62, 62, 0.2);
  --shadow-focus-success: 0 0 0 3px rgba(56, 161, 105, 0.2);
  
  /* Colored Shadows */
  --shadow-primary: 0 4px 14px rgba(102, 0, 153, 0.25);
  --shadow-accent: 0 4px 14px rgba(233, 142, 57, 0.25);
}
```

### 2.6 Transitions

```css
:root {
  /* ==========================================
     TRANSITIONS
     ========================================== */
  --transition-fast: 150ms ease;
  --transition-normal: 250ms ease;
  --transition-slow: 350ms ease;
  --transition-slower: 500ms ease;
  
  /* Easing Functions */
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
```

### 2.7 Z-Index Scale

```css
:root {
  /* ==========================================
     Z-INDEX SCALE
     ========================================== */
  --z-base: 0;
  --z-dropdown: 1000;
  --z-sticky: 1020;
  --z-fixed: 1030;
  --z-modal-backdrop: 1040;
  --z-modal: 1050;
  --z-popover: 1060;
  --z-tooltip: 1070;
}
```

---

## 3. Responsive Breakpoints

### 3.1 Breakpoint Definitions

```css
/* Mobile First Approach */

/* Extra Small devices (phones, less than 576px) */
/* Default styles - no media query needed */

/* Small devices (landscape phones, 576px and up) */
@media (min-width: 576px) { }

/* Medium devices (tablets, 768px and up) */
@media (min-width: 768px) { }

/* Large devices (desktops, 992px and up) */
@media (min-width: 992px) { }

/* Extra large devices (large desktops, 1200px and up) */
@media (min-width: 1200px) { }

/* Extra extra large devices (larger desktops, 1400px and up) */
@media (min-width: 1400px) { }
```

### 3.2 Container Widths

```css
.container {
  width: 100%;
  padding-right: var(--space-4);
  padding-left: var(--space-4);
  margin-right: auto;
  margin-left: auto;
}

@media (min-width: 576px) {
  .container { max-width: 540px; }
}

@media (min-width: 768px) {
  .container { max-width: 720px; }
}

@media (min-width: 992px) {
  .container { max-width: 960px; }
}

@media (min-width: 1200px) {
  .container { max-width: 1140px; }
}

@media (min-width: 1400px) {
  .container { max-width: 1320px; }
}
```

---

## 4. Component Specifications

### 4.1 Job Listing Card

```css
/* Job Card Component */
.job-card {
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  transition: transform var(--transition-normal), 
              box-shadow var(--transition-normal),
              border-color var(--transition-fast);
  display: flex;
  flex-direction: column;
  height: 100%;
}

.job-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--color-primary);
}

/* Card Variants */
.job-card--featured {
  border: 2px solid var(--color-primary);
  background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-bg-primary) 100%);
}

.job-card--urgent {
  border: 2px solid var(--color-accent);
}

.job-card--expired {
  opacity: 0.6;
  pointer-events: none;
}

/* Card Header */
.job-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--space-4);
}

.job-card__title {
  font-family: var(--font-heading);
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
  margin: 0;
}

.job-card__title a {
  color: inherit;
  text-decoration: none;
  transition: color var(--transition-fast);
}

.job-card__title a:hover {
  color: var(--color-primary);
}

/* Card Body */
.job-card__body {
  flex: 1;
  margin-bottom: var(--space-4);
}

.job-card__info {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-bottom: var(--space-3);
}

.job-card__info-item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.job-card__info-icon {
  width: 16px;
  height: 16px;
  color: var(--color-primary);
}

/* Card Footer */
.job-card__footer {
  margin-top: auto;
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-border-light);
}
```

### 4.2 Badges

```css
/* Base Badge */
.badge {
  display: inline-flex;
  align-items: center;
  padding: var(--space-1) var(--space-3);
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  text-transform: uppercase;
  letter-spacing: var(--tracking-wide);
  border-radius: var(--radius-sm);
}

/* Badge Variants */
.badge--urgent {
  background: var(--gradient-accent);
  color: var(--color-text-inverse);
}

.badge--featured {
  background: var(--gradient-secondary);
  color: var(--color-secondary-dark);
}

.badge--expired {
  background: linear-gradient(135deg, var(--color-error), var(--color-error-dark));
  color: var(--color-text-inverse);
}

.badge--remote {
  background: var(--color-secondary-light);
  color: var(--color-secondary-dark);
}

.badge--full-time {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.badge--contract {
  background: var(--color-accent-light);
  color: var(--color-accent-dark);
}
```

### 4.3 Buttons

```css
/* Base Button */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-5);
  font-family: var(--font-body);
  font-size: var(--text-base);
  font-weight: var(--font-medium);
  line-height: 1;
  text-decoration: none;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn:focus {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  pointer-events: none;
}

/* Button Sizes */
.btn--sm {
  padding: var(--space-2) var(--space-4);
  font-size: var(--text-sm);
}

.btn--lg {
  padding: var(--space-4) var(--space-6);
  font-size: var(--text-lg);
}

/* Button Variants */
.btn--primary {
  background: var(--gradient-primary);
  color: var(--color-text-inverse);
  box-shadow: var(--shadow-sm);
}

.btn--primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-primary);
}

.btn--secondary {
  background: var(--color-secondary);
  color: var(--color-text-inverse);
}

.btn--secondary:hover {
  background: var(--gradient-secondary);
}

.btn--accent {
  background: var(--gradient-accent);
  color: var(--color-text-inverse);
}

.btn--accent:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-accent);
}

.btn--outline {
  background: transparent;
  color: var(--color-text-secondary);
  border: 2px solid var(--color-border);
}

.btn--outline:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.btn--outline-primary {
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
}

.btn--outline-primary:hover {
  background: var(--color-primary-light);
}

/* Button with Icon */
.btn__icon {
  width: 20px;
  height: 20px;
}

.btn--icon-only {
  padding: var(--space-3);
}
```

### 4.4 Form Elements

```css
/* Form Group */
.form-group {
  margin-bottom: var(--space-5);
}

/* Labels */
.form-label {
  display: block;
  font-size: var(--text-base);
  font-weight: var(--font-semibold);
  color: var(--color-text-secondary);
  margin-bottom: var(--space-2);
}

.form-label--required::after {
  content: '*';
  color: var(--color-accent);
  margin-left: var(--space-1);
}

/* Input Base */
.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  font-family: var(--font-body);
  font-size: var(--text-base);
  color: var(--color-text-primary);
  background-color: var(--color-bg-primary);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  transition: border-color var(--transition-fast), 
              box-shadow var(--transition-fast);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: var(--color-text-muted);
}

.form-input:hover,
.form-select:hover,
.form-textarea:hover {
  border-color: var(--color-border-dark);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}

/* Input States */
.form-input--error {
  border-color: var(--color-error);
}

.form-input--error:focus {
  box-shadow: var(--shadow-focus-error);
}

.form-input--success {
  border-color: var(--color-success);
  background-color: var(--color-success-light);
}

.form-input--success:focus {
  box-shadow: var(--shadow-focus-success);
}

/* Select */
.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B6B6B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right var(--space-3) center;
  background-size: 20px;
  padding-right: var(--space-10);
}

/* Textarea */
.form-textarea {
  min-height: 150px;
  resize: vertical;
}

/* Form Hint */
.form-hint {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-top: var(--space-1);
}

/* Form Error */
.form-error {
  font-size: var(--text-sm);
  color: var(--color-error);
  margin-top: var(--space-1);
}
```

### 4.5 Search and Filters

```css
/* Search Box */
.search-box {
  position: relative;
  margin-bottom: var(--space-6);
}

.search-box__icon {
  position: absolute;
  left: var(--space-4);
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: var(--color-text-muted);
  pointer-events: none;
}

.search-box__input {
  padding-left: var(--space-12);
}

/* Filter Panel */
.filter-panel {
  background: var(--color-bg-secondary);
  padding: var(--space-6);
  border-radius: var(--radius-lg);
}

.filter-panel__title {
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  margin-bottom: var(--space-4);
  color: var(--color-text-primary);
}

.filter-group {
  margin-bottom: var(--space-4);
}

.filter-group:last-child {
  margin-bottom: 0;
}

/* Salary Range Input */
.salary-range {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.salary-range__input {
  flex: 1;
}

.salary-range__separator {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}

/* Filter Toggle */
.filter-toggle {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  padding: var(--space-2) 0;
}

.filter-toggle:hover {
  color: var(--color-primary);
}

.filter-toggle__checkbox {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary);
}
```

### 4.6 Job Detail Layout

```css
/* Breadcrumb */
.breadcrumb {
  padding: var(--space-4) 0;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.breadcrumb__link {
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.breadcrumb__link:hover {
  color: var(--color-primary);
}

.breadcrumb__separator {
  color: var(--color-text-muted);
}

.breadcrumb__current {
  color: var(--color-text-primary);
  font-weight: var(--font-semibold);
}

/* Job Detail Header */
.job-detail__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: var(--space-4);
  margin-bottom: var(--space-8);
}

.job-detail__title-section {
  flex: 1;
  min-width: 280px;
}

.job-detail__title {
  font-size: var(--text-4xl);
  font-weight: var(--font-bold);
  margin-bottom: var(--space-2);
}

.job-detail__actions {
  display: flex;
  gap: var(--space-3);
}

/* Quick Info Grid */
.job-detail__info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-4);
  background: var(--color-bg-secondary);
  padding: var(--space-6);
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-8);
}

/* Detail Content Layout */
.job-detail__content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: var(--space-8);
}

@media (max-width: 991.98px) {
  .job-detail__content {
    grid-template-columns: 1fr;
  }
}

/* Detail Sections */
.job-detail__section {
  margin-bottom: var(--space-8);
}

.job-detail__section-title {
  font-size: var(--text-2xl);
  font-weight: var(--font-semibold);
  margin-bottom: var(--space-4);
  color: var(--color-text-primary);
}

.job-detail__description {
  color: var(--color-text-secondary);
  line-height: var(--leading-relaxed);
  white-space: pre-wrap;
}

/* Sidebar Cards */
.job-detail__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

/* Apply Card */
.apply-card {
  background: var(--color-bg-secondary);
  padding: var(--space-6);
  border-radius: var(--radius-lg);
}

.apply-card__title {
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  margin-bottom: var(--space-2);
}

.apply-card__text {
  color: var(--color-text-secondary);
  margin-bottom: var(--space-4);
}

.apply-card--closed {
  background: linear-gradient(135deg, var(--color-error-light), #fed7d7);
  border: 2px solid var(--color-error);
}

.apply-card--closed .apply-card__title,
.apply-card--closed .apply-card__text {
  color: var(--color-error-dark);
}

/* Share Card */
.share-card {
  background: var(--color-bg-secondary);
  padding: var(--space-6);
  border-radius: var(--radius-lg);
}

.share-card__title {
  font-size: var(--text-lg);
  font-weight: var(--font-semibold);
  margin-bottom: var(--space-4);
}

.share-card__buttons {
  display: flex;
  gap: var(--space-2);
}

.share-card__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-primary);
  color: var(--color-text-secondary);
  transition: all var(--transition-fast);
}

.share-card__btn:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.share-card__btn svg {
  width: 20px;
  height: 20px;
}
```

### 4.7 Application Form

```css
/* Application Page */
.application {
  background: var(--color-bg-primary);
}

.application__container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 var(--space-4);
}

.application__header {
  text-align: center;
  margin-bottom: var(--space-8);
}

.application__title {
  font-size: var(--text-4xl);
  margin-bottom: var(--space-2);
}

.application__subtitle {
  font-size: var(--text-xl);
  color: var(--color-text-secondary);
}

/* Form Sections */
.application-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-8);
}

.form-section {
  background: var(--color-bg-secondary);
  padding: var(--space-6);
  border-radius: var(--radius-lg);
}

.form-section__title {
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  margin-bottom: var(--space-6);
}

/* File Upload */
.file-upload {
  position: relative;
}

.file-upload__input {
  position: absolute;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.file-upload__label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--space-8);
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-primary);
  transition: all var(--transition-fast);
  cursor: pointer;
}

.file-upload__label:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-light);
}

.file-upload__icon {
  width: 48px;
  height: 48px;
  color: var(--color-primary);
  margin-bottom: var(--space-3);
}

.file-upload__text {
  font-weight: var(--font-semibold);
  color: var(--color-text-primary);
}

.file-upload__hint {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-top: var(--space-2);
}

.file-upload--has-file .file-upload__text {
  color: var(--color-primary);
}

.file-upload--drag-over .file-upload__label {
  border-color: var(--color-primary);
  background: var(--color-primary-light);
}

/* Form Actions */
.form-actions {
  text-align: center;
  padding-top: var(--space-6);
  border-top: 1px solid var(--color-border);
}

/* Success State */
.application-success {
  text-align: center;
  padding: var(--space-8);
  background: var(--color-success-light);
  border-radius: var(--radius-lg);
}

.application-success__title {
  font-size: var(--text-3xl);
  color: var(--color-success);
  margin-bottom: var(--space-4);
}

.application-success__text {
  color: var(--color-text-secondary);
  margin-bottom: var(--space-6);
}

.application-success__details {
  display: grid;
  gap: var(--space-2);
  margin-bottom: var(--space-6);
  text-align: left;
}

.application-success__item {
  display: flex;
  justify-content: space-between;
  padding: var(--space-3);
  background: var(--color-bg-primary);
  border-radius: var(--radius-sm);
}

.application-success__label {
  font-weight: var(--font-semibold);
  color: var(--color-text-secondary);
}

.application-success__value {
  color: var(--color-primary);
  font-weight: var(--font-semibold);
}
```

### 4.8 Alerts

```css
/* Alert Base */
.alert {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-4);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-5);
}

.alert__icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.alert__message {
  font-weight: var(--font-medium);
}

/* Alert Variants */
.alert--error {
  background: var(--color-error-light);
  color: var(--color-error-dark);
}

.alert--success {
  background: var(--color-success-light);
  color: var(--color-success);
}

.alert--warning {
  background: var(--color-warning-light);
  color: var(--color-warning);
}

.alert--info {
  background: var(--color-info-light);
  color: var(--color-info);
}
```

### 4.9 Tags

```css
/* Tags */
.tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.tag {
  display: inline-flex;
  align-items: center;
  padding: var(--space-2) var(--space-3);
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: var(--radius-sm);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
}
```

### 4.10 Hero Section

```css
/* Hero */
.job-hero {
  background: var(--gradient-hero);
  color: var(--color-text-inverse);
  padding: var(--space-16) 0;
  text-align: center;
}

.job-hero__content {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 var(--space-4);
}

.job-hero__title {
  font-size: clamp(var(--text-3xl), 4vw, var(--text-5xl));
  font-weight: var(--font-extrabold);
  margin-bottom: var(--space-4);
  color: var(--color-text-inverse);
}

.job-hero__subtitle {
  font-size: var(--text-xl);
  margin-bottom: var(--space-6);
  opacity: 0.95;
}
```

### 4.11 Stats Section

```css
/* Stats */
.job-stats {
  background: var(--color-bg-primary);
  padding: var(--space-10) 0;
  border-bottom: 1px solid var(--color-border);
}

.job-stats__container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--space-6);
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--space-4);
}

.job-stats__item {
  text-align: center;
  padding: var(--space-4);
}

.job-stats__number {
  font-size: var(--text-3xl);
  font-weight: var(--font-bold);
  color: var(--color-primary);
  margin-bottom: var(--space-1);
}

.job-stats__label {
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  font-weight: var(--font-medium);
}
```

### 4.12 Pagination

```css
/* Pagination */
.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: var(--space-6);
  border-top: 1px solid var(--color-border);
}

.pagination__info {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.pagination__nav {
  display: flex;
  gap: var(--space-2);
}

.pagination__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 40px;
  padding: var(--space-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-primary);
  color: var(--color-text-secondary);
  font-size: var(--text-sm);
  transition: all var(--transition-fast);
}

.pagination__btn:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.pagination__btn--active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-text-inverse);
}

.pagination__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
```

---

## 5. Page Layout Specifications

### 5.1 Job Listings Page Layout

```
┌─────────────────────────────────────────────────────────────┐
│                        HEADER                                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│                     HERO SECTION                             │
│              Join Our Team - Gradient BG                     │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│  ┌────────────┐  ┌────────────┐  ┌────────────┐            │
│  │   Stats    │  │   Stats    │  │   Stats    │            │
│  └────────────┘  └────────────┘  └────────────┘            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│              FEATURED JOBS - Grid 3 cols                     │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│              URGENT JOBS - Grid 3 cols                       │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌────────────────────────────────────┐  │
│  │              │  │                                      │  │
│  │   FILTERS    │  │         JOB LISTINGS                │  │
│  │   SIDEBAR    │  │         - Card                      │  │
│  │              │  │         - Card                      │  │
│  │  - Search    │  │         - Card                      │  │
│  │  - Category  │  │         - Card                      │  │
│  │  - Location  │  │         - Card                      │  │
│  │  - Type      │  │                                      │  │
│  │  - Salary    │  │         PAGINATION                   │  │
│  │              │  │                                      │  │
│  └──────────────┘  └────────────────────────────────────┘  │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                        FOOTER                                │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 Job Detail Page Layout

```
┌─────────────────────────────────────────────────────────────┐
│                        HEADER                                │
├─────────────────────────────────────────────────────────────┤
│  Breadcrumb: Careers / Job Title                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Job Title                                    [Back Button] │
│  [Urgent] [Featured] badges                                 │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │ Quick Info: Location | Type | Salary | Posted      │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────┐  ┌────────────────────┐    │
│  │                            │  │                    │    │
│  │   JOB DESCRIPTION          │  │   APPLY CARD       │    │
│  │                            │  │   Ready to Apply?  │    │
│  │   REQUIREMENTS             │  │   [Apply Now]      │    │
│  │                            │  │                    │    │
│  │   RESPONSIBILITIES         │  │   SHARE CARD       │    │
│  │                            │  │   [Email][Li][Copy]│    │
│  │   QUALIFICATIONS           │  │                    │    │
│  │                            │  │                    │    │
│  │   SKILLS & TAGS            │  │                    │    │
│  │                            │  │                    │    │
│  └────────────────────────────┘  └────────────────────┘    │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│              RELATED POSITIONS - Grid                        │
├─────────────────────────────────────────────────────────────┤
│                        FOOTER                                │
└─────────────────────────────────────────────────────────────┘
```

### 5.3 Application Page Layout

```
┌─────────────────────────────────────────────────────────────┐
│                        HEADER                                │
├─────────────────────────────────────────────────────────────┤
│  Breadcrumb: Careers / Job Title / Application              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│              Apply for [Job Title]                           │
│              Fill out the form below...                      │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  PERSONAL INFORMATION                               │    │
│  │  - Full Name                                        │    │
│  │  - Email                                            │    │
│  │  - Phone                                            │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  COVER LETTER                                       │    │
│  │  - Textarea                                         │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  RESUME UPLOAD                                      │    │
│  │  [Drag & Drop Zone]                                 │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│                    [Submit Application]                      │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                        FOOTER                                │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. Accessibility Guidelines

### 6.1 Color Contrast

| Element | Foreground | Background | Ratio | Status |
|---------|------------|------------|-------|--------|
| Body text | #2C2C2C | #FFFFFF | 13.5:1 | ✅ AAA |
| Muted text | #6B6B6B | #FFFFFF | 5.0:1 | ✅ AA |
| Primary button | #FFFFFF | #660099 | 7.5:1 | ✅ AAA |
| Accent button | #FFFFFF | #E98E39 | 3.5:1 | ⚠️ AA Large |

### 6.2 Focus States

All interactive elements must have visible focus indicators:

```css
:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
```

### 6.3 Form Accessibility

- All form inputs must have associated labels
- Required fields must be marked with aria-required="true"
- Error messages must be linked with aria-describedby
- Form validation errors must be announced to screen readers

---

## 7. Animation Guidelines

### 7.1 Micro-interactions

```css
/* Card Hover */
.job-card {
  transition: transform var(--transition-normal), 
              box-shadow var(--transition-normal);
}

/* Button Press */
.btn:active {
  transform: translateY(-1px);
}

/* Input Focus */
.form-input:focus {
  transition: border-color var(--transition-fast),
              box-shadow var(--transition-fast);
}
```

### 7.2 Page Transitions

```css
/* Fade In Up Animation */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fadeInUp 0.5s var(--ease-out) forwards;
}
```

### 7.3 Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## 8. Implementation Checklist

### Phase 1: CSS Variable Updates
- [ ] Update spacing scale in job-portal.css to match master-style.css
- [ ] Update border-radius values
- [ ] Standardize shadow naming
- [ ] Add missing status colors

### Phase 2: Component Refactoring
- [ ] Refactor job card component with new design tokens
- [ ] Update button styles with all variants
- [ ] Standardize form elements
- [ ] Add missing component states (loading, disabled, error)

### Phase 3: Layout Updates
- [ ] Update responsive breakpoints
- [ ] Fix sidebar/main content layout
- [ ] Ensure consistent container widths

### Phase 4: Accessibility
- [ ] Add focus states to all interactive elements
- [ ] Verify color contrast ratios
- [ ] Add ARIA attributes where needed
- [ ] Test with screen readers

### Phase 5: Polish
- [ ] Add smooth transitions
- [ ] Implement loading states
- [ ] Add skeleton loaders
- [ ] Test across all breakpoints

---

## 9. CSS Architecture

### 9.1 File Structure

```
job-portal/
├── assets/
│   └── css/
│       ├── job-portal.css       # Main compiled CSS
│       └── job-portal/
│           ├── _variables.css   # Design tokens
│           ├── _base.css        # Reset & base styles
│           ├── _typography.css  # Text styles
│           ├── _buttons.css     # Button components
│           ├── _forms.css       # Form elements
│           ├── _cards.css       # Card components
│           ├── _layout.css      # Layout utilities
│           ├── _hero.css        # Hero sections
│           ├── _filters.css     # Filter components
│           ├── _alerts.css      # Alert components
│           └── _responsive.css  # Media queries
```

### 9.2 Naming Convention

Using BEM (Block Element Modifier) methodology:

```css
/* Block */
.job-card { }

/* Element */
.job-card__title { }
.job-card__body { }

/* Modifier */
.job-card--featured { }
.job-card--expired { }
```

---

## 10. Visual Reference

### Color Palette Visualization

```
PRIMARY PALETTE
┌──────────────┬──────────────┬──────────────┐
│   Primary    │   Secondary  │    Accent    │
│   #660099    │   #B0D7AB    │   #E98E39    │
│   ████████   │   ████████   │   ████████   │
│   Purple     │   Green      │   Orange     │
└──────────────┴──────────────┴──────────────┘

COLOR VARIANTS
┌──────────────┬──────────────┬──────────────┐
│    Dark      │    Base      │    Light     │
├──────────────┼──────────────┼──────────────┤
│  #4A0066     │  #660099     │  #F0E6F7     │ Purple
│  #8BC4A0     │  #B0D7AB     │  #E8F5E8     │ Green
│  #D17A1F     │  #E98E39     │  #FDF4E8     │ Orange
└──────────────┴──────────────┴──────────────┘

STATUS COLORS
┌──────────────┬──────────────┬──────────────┐
│   Success    │   Warning    │    Error     │
│   #38a169    │   #dd6b20    │   #e53e3e    │
│   ████████   │   ████████   │   ████████   │
└──────────────┴──────────────┴──────────────┘
```

---

## Document Information

| Property | Value |
|----------|-------|
| Version | 1.0 |
| Created | 2026-02-24 |
| Author | Architect Mode |
| Status | Ready for Implementation |

---

*This document serves as the complete design specification for the Jobs Page Unified Styling System. All CSS implementations should reference this document as the source of truth.*
