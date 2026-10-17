-- What We Offer page: key-value content and section items (services, products, why-us, financing)
-- Mirrors live page https://test.tulimasolar.com/what-we-offer/
USE tulima_solar;

-- Key-value content for hero, section titles, CTAs
CREATE TABLE IF NOT EXISTS what_we_offer_page_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services section items (Professional Farm Survey, Expert System Design, etc.)
CREATE TABLE IF NOT EXISTS what_we_offer_services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    icon_class VARCHAR(100) DEFAULT NULL COMMENT 'Font Awesome class, e.g. fas fa-search',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product categories (Solar Water Pumps, Agricultural Processing Machines, Solar Panel Systems)
CREATE TABLE IF NOT EXISTS what_we_offer_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    bullet_points TEXT DEFAULT NULL COMMENT 'One bullet per line',
    primary_cta_label VARCHAR(255) DEFAULT NULL,
    primary_cta_url VARCHAR(500) DEFAULT NULL,
    secondary_cta_label VARCHAR(255) DEFAULT NULL,
    secondary_cta_url VARCHAR(500) DEFAULT NULL,
    icon_class VARCHAR(100) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Why Choose Us benefits (Quality Assurance, Expert Team, Local Presence, Quick Service)
CREATE TABLE IF NOT EXISTS what_we_offer_benefits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    icon_class VARCHAR(100) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Financing options (22-Month Financing, Mobile Money Payments, Equipment as Collateral)
CREATE TABLE IF NOT EXISTS what_we_offer_financing (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    icon_class VARCHAR(100) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed what_we_offer_page_content from live https://test.tulimasolar.com/what-we-offer/
INSERT INTO what_we_offer_page_content (`key`, value) VALUES
('hero_heading', 'What We Offer – Professional Solar Solutions'),
('hero_subtitle', 'Comprehensive solar-powered solutions designed for modern African farming. From consultation to installation, we provide end-to-end professional services that transform agricultural productivity.'),
('hero_primary_text', 'Get Consultation'),
('hero_primary_url', '/contact'),
('hero_secondary_text', 'View Products'),
('hero_secondary_url', '/products'),
('services_heading', 'Our Services'),
('services_subtitle', 'Professional End-to-End Solutions'),
('services_intro', 'From consultation to installation and ongoing support, we provide comprehensive services tailored to your specific farming needs.'),
('products_heading', 'Product Categories'),
('products_subtitle', 'Professional Product Range'),
('products_intro', 'Comprehensive range of solar-powered agricultural equipment designed for maximum efficiency and reliability.'),
('why_heading', 'Why Choose Tulima Solar?'),
('why_subtitle', 'We''re committed to providing the best professional solar solutions for African farmers with unmatched quality and service.'),
('why_stat_value', '500+'),
('why_stat_label', 'Happy Farmers'),
('financing_heading', 'Flexible Professional Financing'),
('financing_subtitle', 'Making solar power accessible to all farmers with professional financing solutions.'),
('cta_heading', 'Ready to Transform Your Farm with Solar?'),
('cta_subtitle', 'Join thousands of successful farmers across East Africa who have already revolutionized their agricultural operations with our professional solar solutions. Get your free consultation and personalized quote today.'),
('cta_feature_1', 'Free Consultation'),
('cta_feature_2', 'Custom Solar Solutions'),
('cta_feature_3', 'Installation Support'),
('cta_feature_4', 'Ongoing Maintenance'),
('cta_primary_text', 'Get Started Today'),
('cta_primary_url', '/contact'),
('cta_secondary_text', 'Calculate Savings'),
('cta_secondary_url', '/products#calculator')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Seed services (professional end-to-end solutions)
INSERT INTO what_we_offer_services (title, description, icon_class, sort_order, is_active) VALUES
('Professional Farm Survey', 'Our certified experts conduct comprehensive assessments using advanced technology to analyze your farm''s water needs, terrain characteristics, and crop requirements with professional precision.', 'fas fa-search', 10, 1),
('Expert System Design', 'Custom-engineered solar water pumping systems optimized for your specific farm conditions, designed by professional engineers with decades of experience in solar technology.', 'fas fa-cogs', 20, 1),
('Professional Installation', 'Expert installation by certified technicians following international standards, ensuring optimal performance, system longevity, and professional quality assurance.', 'fas fa-tools', 30, 1),
('Professional Training', 'Comprehensive training programs covering system operation, maintenance protocols, and troubleshooting procedures delivered by certified professional instructors.', 'fas fa-graduation-cap', 40, 1),
('24/7 Professional Support', 'Round-the-clock customer support and technical assistance through our dedicated professional service team, ensuring minimal downtime and maximum productivity.', 'fas fa-headset', 50, 1),
('Comprehensive Warranty', 'Extended warranty coverage with regular professional maintenance services, ensuring optimal performance and providing peace of mind for your investment.', 'fas fa-shield-alt', 60, 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), icon_class = VALUES(icon_class), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

-- Seed product categories
INSERT INTO what_we_offer_products (name, description, bullet_points, primary_cta_label, primary_cta_url, secondary_cta_label, secondary_cta_url, icon_class, sort_order, is_active) VALUES
('Solar Water Pumps', 'Efficient solar-powered water pumps for irrigation, livestock, and aquaculture applications with professional engineering.', 'Complete Systems\nSubmersible Pumps\nSurface Pumps', 'Contact', '/contact', 'Learn More', '/solar-water-pumps', 'fas fa-water', 10, 1),
('Agricultural Processing Machines', 'Solar-powered machines for post-harvest processing and value addition with professional quality standards.', 'Processing Units\nOil Presses\nGrain Mills', NULL, NULL, 'Learn More', '/agricultural-processing-machines', 'fas fa-industry', 20, 1),
('Solar Panel Systems', 'Complete solar panel arrays and mounting systems for optimal energy production with professional installation.', 'Control Systems\nMounting Structures\nSolar Panels', NULL, NULL, 'Learn More', '/products', 'fas fa-solar-panel', 30, 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), bullet_points = VALUES(bullet_points), primary_cta_label = VALUES(primary_cta_label), primary_cta_url = VALUES(primary_cta_url), secondary_cta_label = VALUES(secondary_cta_label), secondary_cta_url = VALUES(secondary_cta_url), icon_class = VALUES(icon_class), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

-- Seed Why Choose Us benefits
INSERT INTO what_we_offer_benefits (title, description, icon_class, sort_order, is_active) VALUES
('Quality Assurance', 'All our products meet international quality standards and come with comprehensive professional warranties.', 'fas fa-award', 10, 1),
('Expert Team', 'Experienced professionals with deep knowledge of solar technology and African agriculture.', 'fas fa-users-cog', 20, 1),
('Local Presence', 'Regional offices across Uganda with local teams who understand your specific professional needs.', 'fas fa-map-marker-alt', 30, 1),
('Quick Service', 'Fast response times and efficient professional service delivery to minimize downtime on your farm.', 'fas fa-clock', 40, 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), icon_class = VALUES(icon_class), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

-- Seed financing options
INSERT INTO what_we_offer_financing (title, description, icon_class, sort_order, is_active) VALUES
('22-Month Financing', 'Spread the cost over 22 months with affordable monthly payments and professional terms.', 'fas fa-calendar-alt', 10, 1),
('Mobile Money Payments', 'Convenient mobile money payments with no additional charges for professional convenience.', 'fas fa-mobile-alt', 20, 1),
('Equipment as Collateral', 'The solar water pump serves as collateral, making professional financing accessible to all.', 'fas fa-balance-scale', 30, 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), icon_class = VALUES(icon_class), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

-- Optional: ensure pages/menu entry exists for what-we-offer (used by navigation builder)
INSERT INTO pages (slug, parent_id, title, meta_description, meta_title, sort_order, is_active, show_in_footer, template_type, custom_template, layout_file, requires_auth, hero_title, hero_subtitle, hero_cta_text, hero_cta_url, hero_cta_secondary_text, hero_cta_secondary_url, hero_video_path)
SELECT
  'what-we-offer' AS slug,
  NULL AS parent_id,
  'What We Offer' AS title,
  'What Tulima Solar offers – professional solar-powered solutions for modern African farming.' AS meta_description,
  'What We Offer | Tulima Solar' AS meta_title,
  3 AS sort_order,
  1 AS is_active,
  1 AS show_in_footer,
  'fullwidth' AS template_type,
  NULL AS custom_template,
  'default' AS layout_file,
  0 AS requires_auth,
  'What We Offer' AS hero_title,
  'Professional solar-powered solutions for African farmers.' AS hero_subtitle,
  'Get Consultation' AS hero_cta_text,
  'index.php?page=contact' AS hero_cta_url,
  'View Products' AS hero_cta_secondary_text,
  'index.php?page=products' AS hero_cta_secondary_url,
  NULL AS hero_video_path
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'what-we-offer');


