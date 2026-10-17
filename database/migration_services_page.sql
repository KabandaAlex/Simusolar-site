-- Services page: key-value content (hero, intros, section headings, CTA) and steps/benefits
-- Mirrors live page https://test.tulimasolar.com/services/
USE tulima_solar;

CREATE TABLE IF NOT EXISTS services_page_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services_page_steps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    step_number INT NOT NULL DEFAULT 1,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services_page_benefits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    icon_class VARCHAR(100) DEFAULT NULL COMMENT 'Font Awesome icon class',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed services_page_content from live https://test.tulimasolar.com/services/
INSERT INTO services_page_content (`key`, value) VALUES
('hero_heading', 'Professional Solar Solutions That Transform Agriculture'),
('hero_subtitle', 'From expert consultation to ongoing support, we deliver sustainable solutions that power your agricultural success with unmatched quality and reliability.'),
('hero_primary_text', 'Get Consultation'),
('hero_primary_url', '/contact'),
('hero_secondary_text', 'Explore Solutions'),
('hero_secondary_url', '/products'),
('intro_text', 'From consultation to installation and ongoing support, we provide comprehensive solar-powered agricultural solutions tailored to your specific farming needs.'),
('services_heading', 'End-to-End Professional Solutions'),
('services_subtitle', 'We deliver comprehensive solar-powered agricultural solutions with professional expertise, ensuring every step of your journey is handled with precision and care.'),
('process_heading', 'Professional Service Process'),
('process_subtitle', 'Our proven professional methodology ensures every project is delivered with excellence, from initial consultation to ongoing support and maintenance.'),
('why_heading', 'Why Choose Our Professional Services?'),
('why_subtitle', 'We\'re committed to delivering exceptional service experiences with unmatched quality, expertise, and professional standards that exceed expectations.'),
('cta_heading', 'Ready to Transform Your Farm? Experience Professional Excellence'),
('cta_subtitle', 'Contact our professional team today for a comprehensive consultation and discover how our expert services can revolutionize your agricultural operations with cutting-edge solar technology and unwavering support.'),
('cta_primary_text', 'Get Consultation'),
('cta_primary_url', '/contact'),
('cta_secondary_text', 'View Products'),
('cta_secondary_url', '/products')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Steps (Professional Service Process)
INSERT INTO services_page_steps (step_number, title, description, sort_order, is_active) VALUES
(1, 'Professional Consultation', 'Comprehensive initial consultation with our expert team to understand your farming requirements and assess solar potential with professional analysis.', 10, 1),
(2, 'Expert Farm Survey', 'Detailed on-site assessment using advanced technology to analyze your farm\'s topography, water sources, and energy requirements with professional precision.', 20, 1),
(3, 'Professional System Design', 'Custom engineering of optimal solar water pumping systems tailored to your specific farm conditions, designed by certified professional engineers.', 30, 1),
(4, 'Expert Installation & Support', 'Professional installation by certified technicians followed by comprehensive training and ongoing professional support services.', 40, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), step_number = VALUES(step_number), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

-- Why Choose Us benefits
INSERT INTO services_page_benefits (title, description, icon_class, sort_order, is_active) VALUES
('Quality Guaranteed', 'All our services meet and exceed international quality standards with comprehensive professional warranties and quality assurance protocols.', 'fas fa-award', 10, 1),
('Rapid Response', 'Quick response times and efficient professional service delivery to minimize downtime and ensure continuous agricultural operations.', 'fas fa-clock', 20, 1),
('Expert Professional Team', 'Highly experienced professionals with deep expertise in solar technology, agricultural systems, and sustainable energy solutions.', 'fas fa-users-cog', 30, 1),
('Local Professional Presence', 'Strategic regional offices across Uganda with local professional teams who understand your specific agricultural and business requirements.', 'fas fa-map-marker-alt', 40, 1),
('Flexible Professional Financing', 'Customized payment solutions and flexible financing options designed to make professional solar solutions accessible to all farmers.', 'fas fa-hand-holding-usd', 50, 1),
('Sustainable Professional Solutions', 'Environmentally responsible solutions that protect our planet while enhancing agricultural productivity with professional sustainability standards.', 'fas fa-leaf', 60, 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), icon_class = VALUES(icon_class), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

