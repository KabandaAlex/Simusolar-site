# Home Page Rebuild Plan

## Overview

This document outlines the complete rebuild plan for the Tulima Solar home page (`pages/home.php`) to match the live version at https://test.tulimasolar.com/

## Current State Analysis

### Existing Implementation
The current [`pages/home.php`](../pages/home.php) contains:
- 5 basic sections: Hero, Impact Statistics, Solutions/Services, Partners, Testimonials, CTA
- Uses API endpoint [`api/homepage.php`](../api/homepage.php) for data fetching
- Inline CSS styles mixed with component classes

### Target State
The live site has **11 sections** as defined in [`database/seed_home_tulima.sql`](../database/seed_home_tulima.sql):

| Order | Section Key | Section Name |
|-------|-------------|--------------|
| 1 | hero | Hero Section |
| 2 | featured_products | Featured Products |
| 3 | transform_farm | Transform Your Farm |
| 4 | how_it_works | How It Works |
| 5 | impact_numbers | Impact Numbers |
| 6 | solar_solutions | Comprehensive Solar Solutions |
| 7 | applications | Applications |
| 8 | testimonials | Testimonials |
| 9 | partners | Partners |
| 10 | office_locations | Office Locations |
| 11 | cta_final | Final CTA |

---

## Section Details

### 1. Hero Section

**Data Source:** `pages` table (hero_* columns) + `site_settings` table

**Database Fields:**
```sql
SELECT hero_title, hero_subtitle, hero_cta_text, hero_cta_url, 
       hero_cta_secondary_text, hero_cta_secondary_url, hero_video_path
FROM pages WHERE slug = 'home'
```

**Settings:**
- `hero_title`: "Solar Productive Systems"
- `hero_subtitle`: "Empowering economies across East Africa with cutting edge solar powered productive equipment."
- `hero_cta_text`: "Get Started Today"
- `hero_cta_url`: "index.php?page=contact"

**HTML Structure:**
```html
<section class="c-hero" aria-label="Welcome to Tulima Solar">
    <div class="c-hero__background">
        <!-- Video or Image -->
        <video class="c-hero__video" autoplay muted loop playsinline>...</video>
        <img src="..." class="c-hero__image" />
        <div class="c-hero__overlay"></div>
        <div class="c-hero__particles"></div>
    </div>
    <div class="c-hero__content">
        <h1 class="c-hero__title"><?= e($heroTitle) ?></h1>
        <p class="c-hero__subtitle"><?= e($heroSubtitle) ?></p>
        <div class="c-hero__actions">
            <a href="<?= $heroCtaUrl ?>" class="c-btn c-btn--primary c-btn--lg"><?= $heroCtaText ?></a>
            <?php if ($heroCtaSecondaryText): ?>
            <a href="<?= $heroCtaSecondaryUrl ?>" class="c-btn c-btn--outline c-btn--lg"><?= $heroCtaSecondaryText ?></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="c-hero__scroll">
        <span>Scroll to explore</span>
        <svg>...</svg>
    </div>
</section>
```

**CSS Classes:** `.c-hero`, `.c-hero__background`, `.c-hero__video`, `.c-hero__image`, `.c-hero__overlay`, `.c-hero__content`, `.c-hero__title`, `.c-hero__subtitle`, `.c-hero__actions`, `.c-hero__scroll`

**JavaScript Dependencies:**
- None required (CSS animations handle visual effects)

---

### 2. Featured Products Section

**Data Source:** `products` table (featured products)

**Database Query:**
```sql
SELECT id, title, slug, short_description, image_path, featured
FROM products 
WHERE is_active = 1 AND featured = 1
ORDER BY sort_order
LIMIT 6
```

**Section Config:** `page_sections` table with section_key = 'featured_products'

**HTML Structure:**
```html
<section class="c-section c-section--products" aria-label="Featured Products">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title"><?= e($section['title']) ?></h2>
            <p class="c-section__subtitle"><?= e($section['content']) ?></p>
        </header>
        <div class="c-products__grid">
            <?php foreach ($featuredProducts as $product): ?>
            <article class="c-product-card">
                <div class="c-product-card__image">
                    <img src="<?= e($product['image_path']) ?>" alt="<?= e($product['title']) ?>" loading="lazy">
                </div>
                <div class="c-product-card__content">
                    <h3 class="c-product-card__title"><?= e($product['title']) ?></h3>
                    <p class="c-product-card__description"><?= e($product['short_description']) ?></p>
                    <a href="index.php?page=products&slug=<?= e($product['slug']) ?>" class="c-product-card__link">
                        Learn More <svg>...</svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="c-section__action">
            <a href="index.php?page=products" class="c-btn c-btn--primary">View All Products</a>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--products`, `.c-products__grid`, `.c-product-card`, `.c-product-card__image`, `.c-product-card__content`, `.c-product-card__title`, `.c-product-card__description`, `.c-product-card__link`

---

### 3. Transform Your Farm Section

**Data Source:** `page_sections` + `page_section_items` tables

**Database Query:**
```sql
-- Get section
SELECT * FROM page_sections 
WHERE page_id = (SELECT id FROM pages WHERE slug = 'home') 
AND section_key = 'transform_farm';

-- Get benefit cards
SELECT * FROM page_section_items 
WHERE section_id = ? 
ORDER BY sort_order;
```

**Expected Items:**
1. Significant Cost Savings
2. Environmental Sustainability
3. Unmatched Reliability
4. Increased Productivity

**HTML Structure:**
```html
<section class="c-section c-section--benefits" aria-label="Transform Your Farm">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title"><?= e($section['title']) ?></h2>
            <p class="c-section__subtitle"><?= e($section['content']) ?></p>
        </header>
        <div class="c-benefits__grid">
            <?php foreach ($benefits as $benefit): ?>
            <div class="c-benefit-card">
                <div class="c-benefit-card__icon">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                </div>
                <h3 class="c-benefit-card__title"><?= e($benefit['title']) ?></h3>
                <p class="c-benefit-card__description"><?= e($benefit['content']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--benefits`, `.c-benefits__grid`, `.c-benefit-card`, `.c-benefit-card__icon`, `.c-benefit-card__title`, `.c-benefit-card__description`

---

### 4. How It Works Section

**Data Source:** `page_sections` + `page_section_items` tables

**Database Query:**
```sql
SELECT * FROM page_section_items 
WHERE section_id = (SELECT id FROM page_sections WHERE section_key = 'how_it_works') 
ORDER BY sort_order;
```

**Expected Steps:**
1. Free Consultation (Site Assessment, Energy Analysis, Custom Quote)
2. System Design (Technical Design, Component Selection, Performance Guarantee)
3. Professional Installation (Expert Installation, Quality Testing, Safety Compliance)
4. Ongoing Support (8am-5pm Mon-Fri, Online Support, Regular Maintenance)

**HTML Structure:**
```html
<section class="c-section c-section--steps" aria-label="How It Works">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title"><?= e($section['title']) ?></h2>
            <p class="c-section__subtitle"><?= e($section['content']) ?></p>
        </header>
        <div class="c-steps__grid">
            <?php foreach ($steps as $index => $step): ?>
            <div class="c-step-card">
                <div class="c-step-card__number"><?= $index + 1 ?></div>
                <h3 class="c-step-card__title"><?= e($step['title']) ?></h3>
                <p class="c-step-card__description"><?= e($step['content']) ?></p>
                <?php if ($step['content_extra']): ?>
                <ul class="c-step-card__features">
                    <?php foreach (explode("\n", $step['content_extra']) as $feature): ?>
                    <li><?= e(trim($feature)) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--steps`, `.c-steps__grid`, `.c-step-card`, `.c-step-card__number`, `.c-step-card__title`, `.c-step-card__description`, `.c-step-card__features`

---

### 5. Impact Numbers Section

**Data Source:** `impact_numbers` table (preferred) OR `page_section_items` table

**Database Query:**
```sql
-- Primary: Dedicated table
SELECT label, value, sublabel, icon FROM impact_numbers 
WHERE is_active = 1 
ORDER BY sort_order;

-- Fallback: Page section items
SELECT title, content, content_extra FROM page_section_items 
WHERE section_id = (SELECT id FROM page_sections WHERE section_key = 'impact_numbers') 
ORDER BY sort_order;
```

**Expected Stats:**
1. Farmers Empowered - "0" - Lives transformed through solar power
2. Acres Irrigated - "0" - Land brought to life sustainably
3. Income Increase - "0%" - Average boost in farmer earnings
4. Regional Offices - "0" - Serving communities across Africa

**HTML Structure:**
```html
<section class="c-section c-section--impact" aria-label="Our Impact">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title">Our Impact in Numbers</h2>
            <p class="c-section__subtitle">Since 2019, we've made a significant difference</p>
        </header>
        <div class="c-impact__grid">
            <?php foreach ($impactStats as $stat): ?>
            <div class="c-impact-item">
                <?php if ($stat['icon']): ?>
                <div class="c-impact-item__icon">
                    <?= impact_icon_svg($stat['icon']) ?>
                </div>
                <?php endif; ?>
                <div class="c-impact-item__number" data-count="<?= e($stat['numeric_value']) ?>">
                    <?= e($stat['value']) ?>
                </div>
                <div class="c-impact-item__label"><?= e($stat['label']) ?></div>
                <div class="c-impact-item__description"><?= e($stat['sublabel']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--impact`, `.c-impact__grid`, `.c-impact-item`, `.c-impact-item__icon`, `.c-impact-item__number`, `.c-impact-item__label`, `.c-impact-item__description`

**JavaScript Dependencies:**
- Count-up animation on scroll (optional enhancement)

---

### 6. Comprehensive Solar Solutions Section

**Data Source:** `page_sections` + `page_section_items` tables

**Database Query:**
```sql
SELECT * FROM page_section_items 
WHERE section_id = (SELECT id FROM page_sections WHERE section_key = 'solar_solutions') 
ORDER BY sort_order;
```

**Expected Solutions:**
1. Solar Water Pumps (High efficiency pumps, Smart monitoring, Low maintenance)
2. Solar Panels (Premium quality, Weather resistant, 25-year warranty)
3. Control Systems (Remote monitoring, Mobile app control, Performance analytics)
4. Processing Equipment (Grinding machines, Drying systems, Milling equipment)

**HTML Structure:**
```html
<section class="c-section c-section--solutions" aria-label="Solar Solutions">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title"><?= e($section['title']) ?></h2>
            <p class="c-section__subtitle"><?= e($section['content']) ?></p>
        </header>
        <div class="c-solutions__grid">
            <?php foreach ($solutions as $solution): ?>
            <article class="c-solution-card">
                <div class="c-solution-card__icon">
                    <i class="fas fa-solar-panel" aria-hidden="true"></i>
                </div>
                <h3 class="c-solution-card__title"><?= e($solution['title']) ?></h3>
                <p class="c-solution-card__description"><?= e($solution['content']) ?></p>
                <?php if ($solution['content_extra']): ?>
                <ul class="c-solution-card__features">
                    <?php foreach (explode("\n", $solution['content_extra']) as $feature): ?>
                    <li><?= e(trim($feature)) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--solutions`, `.c-solutions__grid`, `.c-solution-card`, `.c-solution-card__icon`, `.c-solution-card__title`, `.c-solution-card__description`, `.c-solution-card__features`

---

### 7. Applications Section

**Data Source:** `page_sections` table (content_extra field with delimited data)

**Database Query:**
```sql
SELECT * FROM page_sections 
WHERE section_key = 'applications';
```

**Expected Applications:**
1. Crop Irrigation (Drip irrigation, Sprinkler systems, Furrow irrigation, Greenhouse irrigation)
2. Livestock Watering (Cattle watering, Poultry water supply, Fish farming, Automatic waterers)
3. Domestic Use (Household water supply, Community water projects, School water systems, Emergency water supply)

**HTML Structure:**
```html
<section class="c-section c-section--applications" aria-label="Applications">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title"><?= e($section['title']) ?></h2>
            <p class="c-section__subtitle"><?= e($section['content']) ?></p>
        </header>
        <div class="c-applications__grid">
            <?php foreach ($applications as $app): ?>
            <div class="c-application-card">
                <h3 class="c-application-card__title"><?= e($app['title']) ?></h3>
                <ul class="c-application-card__list">
                    <?php foreach ($app['items'] as $item): ?>
                    <li><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--applications`, `.c-applications__grid`, `.c-application-card`, `.c-application-card__title`, `.c-application-card__list`

---

### 8. Testimonials Section

**Data Source:** `testimonials` table

**Database Query:**
```sql
SELECT quote, author, role, image_path FROM testimonials 
WHERE is_active = 1 
ORDER BY sort_order 
LIMIT 6;
```

**HTML Structure:**
```html
<section class="c-section c-section--testimonials" aria-label="Testimonials">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title">What Our Clients Say</h2>
            <p class="c-section__subtitle">Real stories from satisfied customers</p>
        </header>
        <div class="c-testimonials__grid">
            <?php foreach ($testimonials as $testimonial): ?>
            <article class="c-testimonial-card">
                <div class="c-testimonial-card__rating">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <svg class="c-testimonial-card__star" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <?php endfor; ?>
                </div>
                <blockquote class="c-testimonial-card__quote">
                    "<?= e($testimonial['quote']) ?>"
                </blockquote>
                <div class="c-testimonial-card__author">
                    <?php if ($testimonial['image_path']): ?>
                    <img src="<?= e($testimonial['image_path']) ?>" alt="<?= e($testimonial['author']) ?>" class="c-testimonial-card__avatar">
                    <?php endif; ?>
                    <div class="c-testimonial-card__info">
                        <cite class="c-testimonial-card__name"><?= e($testimonial['author']) ?></cite>
                        <span class="c-testimonial-card__role"><?= e($testimonial['role']) ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--testimonials`, `.c-testimonials__grid`, `.c-testimonial-card`, `.c-testimonial-card__rating`, `.c-testimonial-card__star`, `.c-testimonial-card__quote`, `.c-testimonial-card__author`, `.c-testimonial-card__avatar`, `.c-testimonial-card__info`, `.c-testimonial-card__name`, `.c-testimonial-card__role`

---

### 9. Partners Section

**Data Source:** `partners` table

**Database Query:**
```sql
SELECT name, image_path, url FROM partners 
WHERE is_active = 1 
ORDER BY sort_order;
```

**HTML Structure:**
```html
<section class="c-section c-section--partners" aria-label="Our Partners">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title">Our Partners</h2>
            <p class="c-section__subtitle">Trusted by leading organizations across East Africa</p>
        </header>
        <div class="c-partners__grid">
            <?php foreach ($partners as $partner): ?>
            <div class="c-partner-logo">
                <?php if ($partner['url']): ?>
                <a href="<?= e($partner['url']) ?>" target="_blank" rel="noopener noreferrer">
                <?php endif; ?>
                <?php if ($partner['image_path']): ?>
                <img src="<?= e($partner['image_path']) ?>" alt="<?= e($partner['name']) ?>" class="c-partner-logo__image" loading="lazy">
                <?php else: ?>
                <span class="c-partner-logo__name"><?= e($partner['name']) ?></span>
                <?php endif; ?>
                <?php if ($partner['url']): ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--partners`, `.c-partners__grid`, `.c-partner-logo`, `.c-partner-logo__image`, `.c-partner-logo__name`

---

### 10. Office Locations Section

**Data Source:** `office_locations` table

**Database Query:**
```sql
SELECT name, address, phone, email, region_or_city, map_url, latitude, longitude 
FROM office_locations 
WHERE is_active = 1 
ORDER BY sort_order;
```

**HTML Structure:**
```html
<section class="c-section c-section--locations" aria-label="Office Locations">
    <div class="c-section__container">
        <header class="c-section__header">
            <h2 class="c-section__title">Our Office Locations</h2>
            <p class="c-section__subtitle">Find our branches and get directions</p>
        </header>
        <div class="c-locations__grid">
            <?php foreach ($offices as $office): ?>
            <div class="c-location-card">
                <div class="c-location-card__icon">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                </div>
                <h3 class="c-location-card__name"><?= e($office['name']) ?></h3>
                <?php if ($office['region_or_city']): ?>
                <p class="c-location-card__region"><?= e($office['region_or_city']) ?></p>
                <?php endif; ?>
                <?php if ($office['address']): ?>
                <address class="c-location-card__address"><?= e($office['address']) ?></address>
                <?php endif; ?>
                <?php if ($office['phone']): ?>
                <p class="c-location-card__phone">
                    <a href="tel:<?= e($office['phone']) ?>"><?= e($office['phone']) ?></a>
                </p>
                <?php endif; ?>
                <?php if ($office['email']): ?>
                <p class="c-location-card__email">
                    <a href="mailto:<?= e($office['email']) ?>"><?= e($office['email']) ?></a>
                </p>
                <?php endif; ?>
                <?php if ($office['map_url']): ?>
                <a href="<?= e($office['map_url']) ?>" target="_blank" rel="noopener noreferrer" class="c-location-card__map-link">
                    Get Directions <i class="fas fa-external-link-alt"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--locations`, `.c-locations__grid`, `.c-location-card`, `.c-location-card__icon`, `.c-location-card__name`, `.c-location-card__region`, `.c-location-card__address`, `.c-location-card__phone`, `.c-location-card__email`, `.c-location-card__map-link`

---

### 11. Final CTA Section

**Data Source:** `page_sections` table with section_key = 'cta_final'

**Database Query:**
```sql
SELECT * FROM page_sections 
WHERE page_id = (SELECT id FROM pages WHERE slug = 'home') 
AND section_key = 'cta_final';
```

**HTML Structure:**
```html
<section class="c-section c-section--cta" aria-label="Call to Action">
    <div class="c-section__container">
        <div class="c-cta">
            <div class="c-cta__content">
                <h2 class="c-cta__title"><?= e($ctaSection['title']) ?></h2>
                <p class="c-cta__description"><?= e($ctaSection['content']) ?></p>
            </div>
            <div class="c-cta__actions">
                <a href="index.php?page=contact" class="c-btn c-btn--secondary c-btn--lg">
                    Get Started Today
                </a>
                <a href="tel:<?= e($phone) ?>" class="c-btn c-btn--outline c-btn--lg">
                    <i class="fas fa-phone"></i> Call Now
                </a>
            </div>
        </div>
    </div>
</section>
```

**CSS Classes:** `.c-section--cta`, `.c-cta`, `.c-cta__content`, `.c-cta__title`, `.c-cta__description`, `.c-cta__actions`

---

## Database Tables Summary

| Table | Purpose | Used In Section(s) |
|-------|---------|-------------------|
| `pages` | Page metadata and hero content | Hero |
| `page_sections` | Section configuration | All sections |
| `page_section_items` | Individual items within sections | Transform Farm, How It Works, Impact, Solutions, Applications |
| `products` | Product catalog | Featured Products |
| `impact_numbers` | Impact statistics | Impact Numbers |
| `testimonials` | Customer testimonials | Testimonials |
| `partners` | Partner logos | Partners |
| `office_locations` | Office locations | Office Locations |
| `site_settings` | Global settings | Hero (video/image paths) |

---

## Helper Functions Required

The following helper functions from [`includes/functions.php`](../includes/functions.php) should be used:

```php
// Already exists
e($string)                    // HTML escape
getSetting($pdo, $key)        // Get site setting
getImpactNumbers($pdo)        // Get impact stats
getPartners($pdo)             // Get partners
getOfficeLocations($pdo)      // Get office locations
getTestimonials($pdo, $limit) // Get testimonials
getPageSections($pdo, $pageId) // Get page sections
getSectionItems($pdo, $sectionId) // Get section items
parse_impact_value($value)    // Parse impact numbers
impact_icon_svg($icon)        // Get SVG icon markup
```

---

## CSS Architecture

All CSS should use the existing BEM naming convention with the `c-` prefix for components. The main CSS file is at [`assets/css/pages/_home.css`](../assets/css/pages/_home.css).

### Key CSS Files:
- [`assets/css/pages/_home.css`](../assets/css/pages/_home.css) - Homepage specific styles
- [`assets/css/components/_hero.css`](../assets/css/components/_hero.css) - Hero component
- [`assets/css/components/_cards.css`](../assets/css/components/_cards.css) - Card components
- [`assets/css/components/_sections.css`](../assets/css/components/_sections.css) - Section layouts
- [`assets/css/components/_buttons.css`](../assets/css/components/_buttons.css) - Button styles

### CSS Variables to Use:
```css
--color-primary          // Primary brand color
--color-secondary        // Secondary brand color
--color-accent           // Accent color
--gradient-primary       // Primary gradient
--space-*                // Spacing scale
--font-size-*            // Typography scale
--radius-*               // Border radius scale
--shadow-*               // Box shadow scale
--transition-*           // Transition timing
```

---

## JavaScript Dependencies

### Required:
- None (all sections work with pure CSS)

### Optional Enhancements:
- Count-up animation for impact numbers (IntersectionObserver)
- Smooth scroll for hero scroll indicator
- Carousel/slider for partners section (if many partners)

---

## Implementation Order

1. **Phase 1: Data Layer**
   - Verify all database tables have correct data
   - Run [`database/seed_home_tulima.sql`](../database/seed_home_tulima.sql) if needed

2. **Phase 2: PHP Template**
   - Create new [`pages/home.php`](../pages/home.php) with all 11 sections
   - Use helper functions for data retrieval
   - Implement proper error handling for empty sections

3. **Phase 3: CSS Styling**
   - Verify all CSS classes exist in [`assets/css/pages/_home.css`](../assets/css/pages/_home.css)
   - Add any missing component styles
   - Test responsive breakpoints

4. **Phase 4: Testing**
   - Test with empty database tables (graceful degradation)
   - Test with sample data
   - Cross-browser testing
   - Mobile responsiveness testing

---

## File Structure

```
pages/
├── home.php              # Main homepage template (REBUILD)

includes/
├── functions.php         # Helper functions (existing)
├── header.php            # Site header (existing)
└── footer.php            # Site footer (existing)

api/
└── homepage.php          # Homepage API (existing - may need updates)

assets/css/
├── pages/
│   └── _home.css         # Homepage styles (may need additions)
└── components/
    ├── _hero.css         # Hero component (existing)
    ├── _cards.css        # Card components (existing)
    ├── _sections.css     # Section layouts (existing)
    └── _buttons.css      # Button styles (existing)

database/
├── seed_home_tulima.sql  # Home page seed data
├── seed_testimonials.sql # Testimonials seed data
└── seed_impact_partners_offices.sql # Impact/Partners/Offices seed data
```

---

## Gaps and Issues Identified

1. **Missing CSS Classes:** Some sections may need additional CSS classes not currently defined in `_home.css`
   - `.c-benefit-card` and related classes for Transform Farm section
   - `.c-step-card` and related classes for How It Works section
   - `.c-application-card` and related classes for Applications section
   - `.c-location-card` and related classes for Office Locations section

2. **Data Parsing:** The `content_extra` field in `page_section_items` uses custom delimited formats that need consistent parsing logic

3. **API Endpoint:** The current [`api/homepage.php`](../api/homepage.php) may need updates to support all 11 sections

4. **Image Dependencies:** Several sections require images that may need to be uploaded via the media library

---

## Acceptance Criteria

- [ ] All 11 sections render correctly
- [ ] Each section gracefully handles empty data
- [ ] Responsive design works on mobile, tablet, and desktop
- [ ] CSS follows BEM naming convention
- [ ] All database queries use prepared statements
- [ ] HTML is accessible (proper ARIA labels, semantic elements)
- [ ] Page loads in under 3 seconds
- [ ] No JavaScript errors in console
