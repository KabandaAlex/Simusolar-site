-- =============================================================================
-- Tulima Solar CMS - Complete Database Installation Script
-- =============================================================================
-- Version: 1.0
-- Date: 2026-02-23
-- Description: Fresh installation script for Tulima Solar CMS database
-- 
-- This script creates ALL required tables, indexes, and seed data for a
-- complete Tulima Solar CMS installation.
--
-- Usage:
--   mysql -u root -p < install-complete.sql
--
-- Or run in MySQL client:
--   SOURCE /path/to/install-complete.sql;
--
-- =============================================================================

-- =============================================================================
-- SECTION 1: DATABASE CREATION
-- =============================================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS tulima_solar 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE tulima_solar;

-- =============================================================================
-- SECTION 2: DROP EXISTING TABLES (in reverse dependency order)
-- =============================================================================
-- Uncomment these lines if you want to completely reset the database
-- WARNING: This will DELETE ALL DATA!

-- DROP TABLE IF EXISTS product_analytics;
-- DROP TABLE IF EXISTS notifications;
-- DROP TABLE IF EXISTS activity_log;
-- DROP TABLE IF EXISTS page_views;
-- DROP TABLE IF EXISTS faqs;
-- DROP TABLE IF EXISTS legal_pages;
-- DROP TABLE IF EXISTS office_locations;
-- DROP TABLE IF EXISTS impact_numbers;
-- DROP TABLE IF EXISTS partners;
-- DROP TABLE IF EXISTS testimonials;
-- DROP TABLE IF EXISTS media;
-- DROP TABLE IF EXISTS contact_submissions;
-- DROP TABLE IF EXISTS contact_info;
-- DROP TABLE IF EXISTS benefits;
-- DROP TABLE IF EXISTS product_categories;
-- DROP TABLE IF EXISTS products;
-- DROP TABLE IF EXISTS page_section_items;
-- DROP TABLE IF EXISTS page_sections;
-- DROP TABLE IF EXISTS page_templates;
-- DROP TABLE IF EXISTS pages;
-- DROP TABLE IF EXISTS site_settings;
-- DROP TABLE IF EXISTS admins;
-- DROP VIEW IF EXISTS page_hierarchy;
-- DROP VIEW IF EXISTS v_category_stats;

-- =============================================================================
-- SECTION 3: CORE TABLES (no foreign key dependencies)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Table: admins
-- Purpose: Admin user accounts with advanced role management
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    is_locked TINYINT(1) DEFAULT 0,
    is_disabled TINYINT(1) DEFAULT 0,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    INDEX idx_admins_role (role),
    INDEX idx_admins_is_locked (is_locked),
    INDEX idx_admins_is_disabled (is_disabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Admin user accounts with role-based access';

-- -----------------------------------------------------------------------------
-- Table: site_settings
-- Purpose: Global site configuration (key-value pairs)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    label VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Site-wide settings and configuration';

-- -----------------------------------------------------------------------------
-- Table: page_templates
-- Purpose: Available page templates for dynamic pages
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    template_file VARCHAR(100) NOT NULL,
    thumbnail_path VARCHAR(500) DEFAULT NULL,
    supports_sections TINYINT(1) DEFAULT 1,
    supports_hero TINYINT(1) DEFAULT 1,
    supports_sidebar TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_templates_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Page templates for dynamic page rendering';

-- -----------------------------------------------------------------------------
-- Table: pages
-- Purpose: Dynamic pages with hero sections and SEO metadata
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    parent_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    meta_description TEXT,
    meta_title VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    show_in_footer TINYINT(1) DEFAULT 1,
    template_type ENUM('fullwidth', 'with_sidebar', 'landing', 'custom') DEFAULT 'fullwidth',
    custom_template VARCHAR(100) DEFAULT NULL,
    layout_file VARCHAR(100) DEFAULT 'default',
    requires_auth TINYINT(1) DEFAULT 0,
    hero_title VARCHAR(255) NULL,
    hero_subtitle TEXT NULL,
    hero_cta_text VARCHAR(120) NULL,
    hero_cta_url VARCHAR(500) NULL,
    hero_cta_secondary_text VARCHAR(120) NULL,
    hero_cta_secondary_url VARCHAR(500) NULL,
    hero_video_path VARCHAR(500) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pages_slug (slug),
    INDEX idx_pages_parent_slug (parent_id, slug),
    INDEX idx_pages_active (is_active),
    FOREIGN KEY (parent_id) REFERENCES pages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dynamic pages with flexible content sections';

-- -----------------------------------------------------------------------------
-- Table: product_categories
-- Purpose: Product category hierarchy (self-referencing for subcategories)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    parent_id INT UNSIGNED NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_categories_slug (slug),
    INDEX idx_product_categories_parent (parent_id),
    INDEX idx_product_categories_active (is_active),
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Product categories with hierarchy support';

-- -----------------------------------------------------------------------------
-- Table: products
-- Purpose: Products and services offered
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    category_id INT UNSIGNED NULL,
    short_description TEXT,
    description TEXT,
    image_path VARCHAR(500),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_category (category_id),
    INDEX idx_products_featured (featured),
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Products and services catalog';

-- -----------------------------------------------------------------------------
-- Table: page_sections
-- Purpose: Flexible content blocks per page
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    section_key VARCHAR(100) NOT NULL,
    title VARCHAR(255),
    content TEXT,
    content_extra TEXT,
    image_path VARCHAR(500),
    sort_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY uk_page_section (page_id, section_key),
    INDEX idx_section_sort (page_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Page content sections';

-- -----------------------------------------------------------------------------
-- Table: page_section_items
-- Purpose: Individual items within page sections (cards, steps, etc.)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_section_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL DEFAULT '',
    content TEXT,
    content_extra TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES page_sections(id) ON DELETE CASCADE,
    KEY idx_section_sort (section_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Items within page sections for flexible content';

-- -----------------------------------------------------------------------------
-- Table: benefits
-- Purpose: Reusable benefits/features list
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS benefits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    icon_or_image VARCHAR(500),
    sort_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Benefits and features list';

-- =============================================================================
-- SECTION 4: CONTENT TABLES
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Table: contact_info
-- Purpose: Contact information (address, phone, email, WhatsApp)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(80) NOT NULL UNIQUE,
    value VARCHAR(500),
    label VARCHAR(255),
    sort_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Site contact information';

-- -----------------------------------------------------------------------------
-- Table: contact_submissions
-- Purpose: Contact form submissions from visitors
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(100) NULL DEFAULT NULL,
    subject VARCHAR(255) NULL DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    replied_at TIMESTAMP NULL,
    reply_notes TEXT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_submitted (submitted_at),
    KEY idx_read (is_read),
    KEY idx_status (status),
    KEY idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Contact form submissions';

-- -----------------------------------------------------------------------------
-- Table: media
-- Purpose: Media library for images and videos
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    type ENUM('image','video') NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    alt_text VARCHAR(500) DEFAULT NULL,
    caption TEXT DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_file_path (file_path(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Media library for images and videos';

-- -----------------------------------------------------------------------------
-- Table: testimonials
-- Purpose: Customer testimonials
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote TEXT NOT NULL,
    author VARCHAR(255) NOT NULL DEFAULT '',
    role VARCHAR(255) NOT NULL DEFAULT '',
    image_path VARCHAR(500) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Customer testimonials';

-- -----------------------------------------------------------------------------
-- Table: partners
-- Purpose: Partner logos and information
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    image_path VARCHAR(500) NULL DEFAULT NULL,
    url VARCHAR(500) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Partner organizations';

-- -----------------------------------------------------------------------------
-- Table: impact_numbers
-- Purpose: Impact statistics for homepage
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS impact_numbers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL DEFAULT '',
    value VARCHAR(100) NOT NULL DEFAULT '',
    sublabel VARCHAR(500) NOT NULL DEFAULT '',
    icon VARCHAR(50) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Impact statistics and numbers';

-- -----------------------------------------------------------------------------
-- Table: office_locations
-- Purpose: Office locations with map coordinates
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS office_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    address TEXT,
    phone VARCHAR(100) NULL DEFAULT NULL,
    email VARCHAR(255) NULL DEFAULT NULL,
    region_or_city VARCHAR(255) NULL DEFAULT NULL,
    map_url VARCHAR(500) NULL DEFAULT NULL,
    latitude DECIMAL(10, 8) NULL DEFAULT NULL,
    longitude DECIMAL(11, 8) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Office locations with map support';

-- -----------------------------------------------------------------------------
-- Table: legal_pages
-- Purpose: Legal pages (Privacy Policy, Terms, etc.)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS legal_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL DEFAULT '',
    content LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Legal pages content';

-- -----------------------------------------------------------------------------
-- Table: faqs
-- Purpose: Frequently Asked Questions
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS faqs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Frequently Asked Questions';

-- =============================================================================
-- SECTION 5: ANALYTICS TABLES
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Table: page_views
-- Purpose: Page view tracking for analytics
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NULL,
    page_slug VARCHAR(100),
    page_title VARCHAR(255),
    visitor_hash VARCHAR(64) COMMENT 'MD5 hash of IP+UserAgent for unique visitors',
    ip_address VARCHAR(45) COMMENT 'IPv4 or IPv6 address',
    user_agent TEXT COMMENT 'Browser user agent string',
    referrer VARCHAR(500) COMMENT 'HTTP referrer if available',
    session_id VARCHAR(64) COMMENT 'Session identifier',
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    time_on_page INT DEFAULT 0 COMMENT 'Time spent on page in seconds',
    INDEX idx_page_slug (page_slug),
    INDEX idx_viewed_at (viewed_at),
    INDEX idx_visitor_hash (visitor_hash),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Page views tracking for analytics';

-- -----------------------------------------------------------------------------
-- Table: activity_log
-- Purpose: Admin activity audit trail
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL COMMENT 'Type of action: created, updated, deleted, viewed, etc.',
    entity_type VARCHAR(50) COMMENT 'Type of entity: page, product, media, etc.',
    entity_id INT UNSIGNED NULL COMMENT 'ID of the affected entity',
    entity_title VARCHAR(255) COMMENT 'Human-readable title of entity',
    details JSON COMMENT 'Additional action details as JSON',
    ip_address VARCHAR(45) COMMENT 'IP address of admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Admin activity audit log';

-- -----------------------------------------------------------------------------
-- Table: notifications
-- Purpose: Admin notifications and alerts
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NULL COMMENT 'NULL for all admins',
    type VARCHAR(50) NOT NULL COMMENT 'Type: contact_submission, system_alert, etc.',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    data JSON COMMENT 'Additional notification data',
    link_url VARCHAR(500) COMMENT 'URL to navigate to related content',
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_read (admin_id, is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Admin notifications and alerts';

-- -----------------------------------------------------------------------------
-- Table: product_analytics
-- Purpose: Daily product view and inquiry statistics
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_analytics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    view_count INT DEFAULT 0 COMMENT 'Daily view count',
    inquiry_count INT DEFAULT 0 COMMENT 'Daily inquiry count',
    date_recorded DATE NOT NULL COMMENT 'Date for this record',
    UNIQUE KEY uk_product_date (product_id, date_recorded),
    INDEX idx_date (date_recorded),
    INDEX idx_product (product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Daily analytics for products';

-- =============================================================================
-- SECTION 6: VIEWS
-- =============================================================================

-- -----------------------------------------------------------------------------
-- View: page_hierarchy
-- Purpose: Easy querying of page tree structure
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW page_hierarchy AS
SELECT 
    p.id,
    p.slug,
    p.parent_id,
    p.title,
    p.is_active,
    p.template_type,
    CASE 
        WHEN p.parent_id IS NULL THEN p.slug
        ELSE CONCAT(COALESCE(parent.slug, ''), '/', p.slug)
    END AS full_path,
    CASE 
        WHEN p.parent_id IS NULL THEN 0
        ELSE 1
    END AS depth
FROM pages p
LEFT JOIN pages parent ON p.parent_id = parent.id;

-- -----------------------------------------------------------------------------
-- View: v_category_stats
-- Purpose: Category statistics with product counts
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_category_stats AS
SELECT 
    c.id,
    c.name,
    c.slug,
    c.parent_id,
    c.is_active,
    COUNT(p.id) AS product_count,
    SUM(CASE WHEN p.is_active = 1 THEN 1 ELSE 0 END) AS active_product_count
FROM product_categories c
LEFT JOIN products p ON c.id = p.category_id
GROUP BY c.id, c.name, c.slug, c.parent_id, c.is_active;

-- =============================================================================
-- SECTION 7: SEED DATA - ADMIN USERS
-- =============================================================================

-- Default admin user
-- Username: admin
-- Password: password (CHANGE IN PRODUCTION!)
INSERT INTO admins (username, password_hash, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tulimasolar.com', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- =============================================================================
-- SECTION 8: SEED DATA - SITE SETTINGS
-- =============================================================================

-- Core site settings
INSERT INTO site_settings (`key`, value, label) VALUES
('site_name', 'Tulima Solar', 'Site name'),
('tagline', 'Financed Solutions for Agriculture', 'Tagline'),
('footer_text', 'Tulima Solar – Solar-Powered Equipment for Agriculture.', 'Footer text'),
('whatsapp_number', '', 'WhatsApp number (with country code)'),
('whatsapp_message', 'Hello, I would like to know more about Tulima Solar.', 'Default WhatsApp message')
ON DUPLICATE KEY UPDATE value = VALUES(value);

INSERT INTO site_settings (`key`, value, label) VALUES
('footer_description', 'Empowering African farmers with sustainable solar solutions since 2019. We provide professional, reliable, and cost-effective solar-powered equipment for modern agriculture.', 'Footer long description'),
('business_hours', 'Mon - Fri: 8:00 AM - 6:00 PM', 'Business hours (footer)'),
('facebook_url', '', 'Facebook URL'),
('twitter_url', '', 'Twitter/X URL'),
('linkedin_url', '', 'LinkedIn URL'),
('instagram_url', '', 'Instagram URL'),
('youtube_url', '', 'YouTube URL'),
('analytics_enabled', '1', 'Enable Analytics Tracking'),
('analytics_retention_days', '90', 'Analytics Data Retention (Days)')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- =============================================================================
-- SECTION 9: SEED DATA - PAGE TEMPLATES
-- =============================================================================

INSERT INTO page_templates (name, display_name, description, template_file, supports_sections, supports_hero, supports_sidebar) VALUES
('fullwidth', 'Full Width', 'A full-width page layout with no sidebar, ideal for landing pages and showcasing content.', 'fullwidth.php', 1, 1, 0),
('with_sidebar', 'With Sidebar', 'A page layout with a sidebar for navigation or additional content.', 'sidebar.php', 1, 1, 1),
('landing', 'Landing Page', 'A conversion-focused landing page template with hero section and call-to-action areas.', 'landing.php', 1, 1, 0),
('custom', 'Custom Template', 'Use a custom PHP template file for advanced layouts.', 'custom.php', 1, 1, 0)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- =============================================================================
-- SECTION 10: SEED DATA - PAGES
-- =============================================================================

INSERT INTO pages (slug, title, meta_description, sort_order, is_active, show_in_footer, template_type, hero_title, hero_subtitle, hero_cta_text, hero_cta_url, hero_cta_secondary_text, hero_cta_secondary_url, hero_video_path) VALUES
('home', 'Home', 'Tulima Solar – Financed Solutions for Agriculture. Solar-powered irrigation and agricultural processing.', 1, 1, 1, 'fullwidth', 'Solar-Powered Equipment.', 'Tulima Solar makes farming productive with affordable, solar-powered irrigation systems and agricultural processing machines.', 'Contact Us', 'index.php?page=contact', 'WhatsApp us', '', 'static/videos/hero.mp4'),
('about', 'About Us', 'About Tulima Solar – customized solar irrigation and farming solutions since 2019.', 2, 1, 1, 'fullwidth', 'About Tulima Solar', 'Customized solar irrigation and farming solutions since 2019.', 'Contact Us', 'index.php?page=contact', '', '', NULL),
('products', 'Products & Solutions', 'Solar water pumps, irrigation systems, and agricultural equipment from Tulima Solar.', 3, 1, 1, 'landing', 'Products & Solutions', 'Solar water pumps, irrigation systems, and agricultural equipment for your farm.', 'View Solar Pumps', 'index.php?page=solar-pumps', 'Contact Us', 'index.php?page=contact', NULL),
('solar-pumps', 'Solar Water Pumps', 'Solar pumps for irrigation, livestock, fish ponds, and water selling.', 4, 1, 1, 'landing', 'Solar Water Pumps', 'Solar pumps for irrigation, livestock, fish ponds, and water selling.', 'Contact Us', 'index.php?page=contact', 'View Products', 'index.php?page=products', NULL),
('contact', 'Contact', 'Contact Tulima Solar – local offices, warranty, toll-free support.', 5, 1, 1, 'fullwidth', 'Contact Us', 'Get in touch for solar irrigation solutions, financing, and support.', 'WhatsApp us', '', 'Call or Email', 'index.php?page=contact', NULL),
('testimonials', 'Testimonials', 'What our clients say about Tulima Solar – farmer and partner testimonials.', 6, 1, 1, 'fullwidth', 'What Our Clients Say', 'Hear from farmers and agricultural professionals who trust Tulima Solar.', 'Contact Us', 'index.php?page=contact', '', '', NULL),
('privacy', 'Privacy Policy', 'Tulima Solar privacy policy – how we collect, use and protect your information.', 7, 1, 0, 'with_sidebar', 'Privacy Policy', 'How we collect, use and protect your information.', 'Contact Us', 'index.php?page=contact', '', '', NULL),
('terms', 'Terms & Conditions', 'Tulima Solar terms and conditions of use.', 8, 1, 0, 'with_sidebar', 'Terms & Conditions', 'Terms of use for our website and services.', 'Contact Us', 'index.php?page=contact', '', '', NULL),
('sitemap', 'Sitemap', 'Tulima Solar – full sitemap of all pages.', 9, 1, 0, 'with_sidebar', 'Sitemap', 'Find your way around our website.', '', NULL, '', '', NULL)
ON DUPLICATE KEY UPDATE
  title = VALUES(title), 
  meta_description = VALUES(meta_description),
  hero_title = VALUES(hero_title), 
  hero_subtitle = VALUES(hero_subtitle),
  hero_cta_text = VALUES(hero_cta_text), 
  hero_cta_url = VALUES(hero_cta_url),
  hero_cta_secondary_text = VALUES(hero_cta_secondary_text), 
  hero_cta_secondary_url = VALUES(hero_cta_secondary_url),
  hero_video_path = VALUES(hero_video_path);

-- =============================================================================
-- SECTION 11: SEED DATA - PRODUCT CATEGORIES
-- =============================================================================

-- Parent Categories
INSERT INTO product_categories (name, slug, description, parent_id, sort_order, is_active) VALUES
('Solar Water Pumps', 'solar-water-pumps', 'Solar-powered water pumping solutions for irrigation and domestic use.', NULL, 1, 1),
('Agricultural Processing Machines', 'agricultural-processing-machines', 'Machines for processing agricultural products after harvest.', NULL, 2, 1),
('Solar Systems', 'solar-systems', 'Complete solar energy systems for homes and businesses.', NULL, 3, 1),
('Accessories', 'accessories', 'Accessories and spare parts for solar and agricultural equipment.', NULL, 4, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Get parent IDs for subcategories
SET @pump_id = (SELECT id FROM product_categories WHERE slug = 'solar-water-pumps');
SET @agri_id = (SELECT id FROM product_categories WHERE slug = 'agricultural-processing-machines');

-- Subcategories for Solar Water Pumps
INSERT INTO product_categories (name, slug, description, parent_id, sort_order, is_active) VALUES
('Submersible Pumps', 'submersible-pumps', 'Solar-powered submersible pumps for deep wells and boreholes.', @pump_id, 1, 1),
('Surface Pumps', 'surface-pumps', 'Solar-powered surface pumps for shallow water sources.', @pump_id, 2, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Subcategories for Agricultural Processing Machines
INSERT INTO product_categories (name, slug, description, parent_id, sort_order, is_active) VALUES
('Milling Machines', 'milling-machines', 'Machines for milling grains and cereals.', @agri_id, 1, 1),
('Threshing Machines', 'threshing-machines', 'Machines for threshing grains from their husks.', @agri_id, 2, 1),
('Chaff Cutters', 'chaff-cutters', 'Machines for cutting straw and hay for animal feed.', @agri_id, 3, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- =============================================================================
-- SECTION 12: SEED DATA - PRODUCTS
-- =============================================================================

INSERT INTO products (title, slug, short_description, description, sort_order, is_active) VALUES
('Surface Pumps', 'surface-pumps', 'For rivers, ponds and lakes near the surface.', 'Surface pumps operate from rivers, ponds and lakes. They draw water and push it to the desired location. Options: Kilimo Kwanza, Kilimo Bora, Kilimo Pesa, Faidika, Jiwezeshe, Masika.', 1, 1),
('Submersible Pumps', 'submersible-pumps', 'For wells and boreholes.', 'Submersible pumps are installed inside the well or borehole. Options: Kilimo Tosha, Mkulima 3, Kisima 3, Tija, Mto Plus, Kilimo Fursa, Chapakazi, Chemchem Plus, Kina Plus.', 2, 1),
('Solar Panels', 'solar-panels', 'Power for your pump.', 'Solar panels provide power to the pump. Panel capacity should match pump requirements for best performance.', 3, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title), short_description = VALUES(short_description), description = VALUES(description), sort_order = VALUES(sort_order);

-- =============================================================================
-- SECTION 13: SEED DATA - PAGE SECTIONS
-- =============================================================================

-- Get page IDs for sections
SET @home_id = (SELECT id FROM pages WHERE slug = 'home' LIMIT 1);
SET @about_id = (SELECT id FROM pages WHERE slug = 'about' LIMIT 1);
SET @products_id = (SELECT id FROM pages WHERE slug = 'products' LIMIT 1);
SET @pumps_id = (SELECT id FROM pages WHERE slug = 'solar-pumps' LIMIT 1);
SET @contact_id = (SELECT id FROM pages WHERE slug = 'contact' LIMIT 1);

-- Home page sections
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'hero_title', 'Solar-Powered Equipment.', 'Tulima Solar makes farming productive with affordable, solar-powered irrigation systems and agricultural processing machines. Our customized solutions enhance efficiency from irrigation to post-harvest. Since 2019, we have empowered farmers to grow, harvest, and process independently of rain and fuel.', NULL, 10),
(@home_id, 'full_service', 'Taking care of our customers with a full-service solution.', 'Customized Irrigation System Design to meet the needs of YOUR farm. Pricing includes everything needed for your SWP system: Wire and Pipes, Control system, Array mounting structure, Solar Panel Array, Solar Water Pump.', NULL, 20),
(@home_id, 'support', 'Support & Service', 'Local Offices in 3 Regions to support farmers. After-sales service backed by a 2-year Warranty. Toll-free Customer Service Line. Complete System Delivery and Installation Service.', NULL, 30),
(@home_id, 'benefits_irrigation', 'Benefits of Solar-Powered Irrigation', 'Irrigation can bring many benefits to farmers, increasing income by: Cultivate and harvest crops even during the dry season; Reduce labor requirements in carrying water; Increase the quality and yield of harvests; Reducing the risk of unpredictable rains with a reliable water supply.', NULL, 40),
(@home_id, 'benefits_switch', 'If you use a fuel-powered pump', 'If you are using a fuel-powered pump for irrigation now, switching to solar power means you: Eliminate downtime while awaiting engine repairs; Eliminate maintenance of fuel-powered irrigation equipment; Eliminate the hassle of getting fuel to your farm; Eliminate the cost of fuel used to run your pump.', NULL, 50)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- About sections
INSERT INTO page_sections (page_id, section_key, title, content, sort_order) VALUES
(@about_id, 'about_intro', 'About Tulima Solar', 'Tulima Solar makes farming productive with affordable, solar-powered irrigation systems and agricultural processing machines. Our customized solutions enhance efficiency from irrigation to post-harvest. Since 2019, we have empowered farmers to grow, harvest, and process independently of rain and fuel.', 10),
(@about_id, 'mission', 'Our Mission', 'Taking care of our customers with a full-service solution. We provide customized irrigation system design to meet the needs of your farm.', 20)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Products / Solar Pumps sections
INSERT INTO page_sections (page_id, section_key, title, content, sort_order) VALUES
(@pumps_id, 'solar_pumps', 'Solar Pumps', 'Solar Pumps can be used for Irrigation in Agriculture to help increase productivity as they allow the user to irrigate without concern for the operating costs of electricity/fuel. This will help farmers to increase productivity and income. They can also help the Farmer to be able to cultivate throughout the year without waiting for rain or the weather.', 10),
(@pumps_id, 'animal_keeping', 'Animal Keeping', 'Solar Pumps increase the productivity of pastoralists as livestock depend on water and access to water is a key element in productive animal keeping. The use of solar power will help the pastoralist to water livestock even when they do not have the money to buy fuel, thus increasing productivity.', 20),
(@pumps_id, 'fish_fishing', 'Fish / Fishing', 'Our customers also use Solar Pumps to keep water in the Fish Ponds thus increasing the income for the profits from Aquaculture.', 30),
(@pumps_id, 'water_selling', 'Water Selling Business', 'Due to the Solar Pump being cost-effective our customers with these Pumps are also using them as an additional opportunity to increase income by selling water to the people around them in the community. This also helps to increase community well-being by making it easier to access water services in close proximity.', 40),
(@pumps_id, 'intro_system', 'A basic introduction to solar water pumping systems', 'A solar pumping system involves using solar energy to pump water from a source on the surface (lakes, rivers, streams, reservoirs) or from inside a well, to your fields to irrigate your crops. Solar water pumps are widely used to supply well water and upstream sources to livestock/crops. In Africa, the use of solar-powered water pumps has become increasingly popular in commercial farms due to the high cost of fuels for running pumps. The cost of pumping water using solar energy is very low compared to fuel-powered pumps.', 50),
(@pumps_id, 'components', 'Components of a solar water pumping system', 'Source of water: Wells or Boreholes (hand-dug or drilled); Surface water such as rivers, springs, ponds, lakes. Pump: Submersible pumps for wells or boreholes; Surface Pumps for surface water sources. The solar panels provide the power to run the pump.', 60),
(@pumps_id, 'surface_pumps', 'Surface pumps', 'Surface pumps operate from the water sources of rivers, ponds and lakes near the surface of the earth and are used primarily for moving water through the pipe. They have a suction and water exhaust component. They are often placed near a water source and draw water over it and push it to the desired location. They do not enter the water.', 70),
(@pumps_id, 'submersible_pumps', 'Submersible pumps', 'Submersible pumps are installed in the water within the well or borehole, and are submerged within the reservoir. Simusolar has several options depending on the size and characteristics of the farm: Kilimo Tosha, Mkulima 3, Kisima 3, Tija, Mto Plus, Kilimo Fursa, Chapakazi, Chemchem Plus, Kina Plus.', 80),
(@pumps_id, 'solar_panels', 'Solar Panels', 'Solar panels provide power to the pump. It is important the level of electrical power of the pump be consistent with the electrical power of the panels. Having a larger solar panel allows the pump to start earlier and add more power as the sun gets more intense.', 90),
(@pumps_id, 'considerations', 'Things to consider when using a solar water pump', 'Never use soil, sandy and grass waste water in running any pump. The water source should be clean and not less than 3 feet (1 meter deep) for surface pumps. The farmer should not try to open a pump system and try to make repairs but contact Simusolar customer service to get immediate help.', 100),
(@pumps_id, 'design', 'Design of solar water pumping system', 'In order to select the right pump for a particular field, consider: Source of water; Daily water needs (crops grown, size of farm, total farm size); Variation of elevation from water source to the highest point of field (TDH); Lowest depth of water in summer (if borehole); Distance from source to the farthest point; Distance from pump to solar panel or array.', 110),
(@pumps_id, 'advantages', 'Advantages of Simusolar Solar Pumps for Farmers', 'Environmental friendly. Easily accessed through 22-month financing. Increase production area. Reduce energy costs by using free sunlight as fuel. Low cost of maintenance and repairs (2 years warranty). Reduce labor costs and production time. Increase income by cultivating all seasons.', 120),
(@pumps_id, 'simusolar_services', 'Simusolar services', 'Business development services by connecting with other trusted stakeholders in the agricultural sector. Two-year equipment warranty. Customer service by calling a toll-free number. Free install of the solar pump system. Financing for solar pumps by monthly repayments for 22 months. Free farm survey, pump consultation and design.', 130),
(@pumps_id, 'financing', 'Financing Program', 'Digital systems that track system use and turn on following payments. Monthly payments by mobile money with no additional charges or deductions. Monthly payments for 22 months, or other financing terms available. Rapid credit approval system. Financing system with solar water pump as the only collateral.', 140)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Contact page sections
INSERT INTO page_sections (page_id, section_key, title, content, sort_order) VALUES
(@contact_id, 'contact_intro', 'Contact Us', 'Get in touch with Tulima Solar for solar irrigation solutions, financing, and support. Local offices in 3 regions, 2-year warranty, toll-free customer service.', 10)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- =============================================================================
-- SECTION 14: SEED DATA - CONTACT INFO
-- =============================================================================

INSERT INTO contact_info (`key`, value, label, sort_order) VALUES
('phone', '+256 800 388 883', 'Toll-free / Phone', 1),
('email', 'info@tulimasolar.com', 'Email', 2),
('address', 'Plot 483, Kaduyu road, Ntinda - Kiwatule', 'Address', 3),
('whatsapp', '', 'WhatsApp', 4)
ON DUPLICATE KEY UPDATE value = VALUES(value), label = VALUES(label);

-- =============================================================================
-- SECTION 15: SEED DATA - LEGAL PAGES
-- =============================================================================

INSERT INTO legal_pages (page_key, title, content) VALUES
('privacy', 'Privacy Policy', '<p>At Tulima Solar we respect your privacy. This policy describes how we collect, use and protect your personal information when you use our website or services.</p><h3>Information we collect</h3><p>We may collect your name, email address, phone number, and other details when you contact us, request a quote, or subscribe to updates.</p><h3>How we use it</h3><p>We use this information to respond to your enquiries, provide services, send relevant updates, and improve our offerings. We do not sell your data to third parties.</p><h3>Cookies</h3><p>Our website may use cookies to improve your experience. You can disable cookies in your browser settings.</p><h3>Contact</h3><p>For questions about this policy, contact us via the Contact page.</p>'),
('terms', 'Terms & Conditions', '<p>Welcome to Tulima Solar. By using our website you agree to these terms.</p><h3>Use of website</h3><p>You may use this website for lawful purposes only. You must not misuse the site or attempt to gain unauthorized access to our systems.</p><h3>Content</h3><p>Content on this site is for general information. We do not guarantee that product or service descriptions are error-free. Contact us for accurate, up-to-date information.</p><h3>Links</h3><p>We may link to third-party sites. We are not responsible for their content or privacy practices.</p><h3>Changes</h3><p>We may update these terms from time to time. Continued use of the site after changes constitutes acceptance.</p><h3>Contact</h3><p>For questions, please use our Contact page.</p>'),
('sitemap', 'Sitemap', '')
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content);

-- =============================================================================
-- SECTION 16: SEED DATA - FAQs
-- =============================================================================

INSERT INTO faqs (question, answer, sort_order, is_active) VALUES
('How do I get started?', 'Simply contact us through this form, call our toll-free number, or visit any of our offices. We''ll schedule a free farm survey and consultation to design the perfect system for your needs.', 10, 1),
('How long does installation take?', 'Most solar irrigation systems can be installed within 1-3 days depending on the size and complexity of your farm setup.', 20, 1),
('What warranty do you provide?', 'We offer a 2-year warranty on our solar equipment. Extended warranty options may be available for specific products.', 30, 1),
('Do you offer financing options?', 'Yes. We offer flexible financing so you can pay in monthly instalments. Contact us for current terms and eligibility.', 40, 1),
('What maintenance is required?', 'Solar systems require minimal maintenance. We recommend periodic panel cleaning and an annual check. Our team can guide you.', 50, 1)
ON DUPLICATE KEY UPDATE question = VALUES(question), answer = VALUES(answer), sort_order = VALUES(sort_order);

-- =============================================================================
-- SECTION 17: SEED DATA - TESTIMONIALS
-- =============================================================================

INSERT INTO testimonials (quote, author, role, sort_order, is_active) VALUES
('Opio Tonny, the owner of Otobai Investment Co. Ltd Tree Nursery in Lira City, Northern Uganda, found a solution to his irrigation challenges with a solar water pump from Tulima Solar. Referred by another satisfied entrepreneur, he invested in the technology to replace a manual watering can. The solar pump provided a consistent and efficient water supply, reducing physical labor for Opio and his workers. This improved irrigation led to more effective watering and supported the expansion of his one-acre tree nursery. Opio credits Tulima Solar''s technology and support for empowering his business growth and helping him combat issues like climate change, poverty, and unemployment. He now considers them a key partner in his business''s future.', 'Opio Tonny', 'Farmer', 10, 1),
('Rebecca Wakesa, a nursery and vegetable grower in Nashibiswa Village, Mbale, aimed to improve her one-acre garden. She sought to reduce high costs, save energy, and increase her crop yield and earnings. Following a sales officer''s guidance, she installed a solar water pump from Tulima Solar. This new irrigation system drastically cut her previous expenses, allowing her to invest the savings back into her business and expand her crop variety. Rebecca highly recommends the solar water pump to other farmers, highlighting its energy efficiency and cost-effectiveness as key benefits.', 'Rebecca Wakesa', 'Farmer', 20, 1)
ON DUPLICATE KEY UPDATE author = VALUES(author);

-- =============================================================================
-- SECTION 18: SEED DATA - IMPACT NUMBERS
-- =============================================================================

INSERT INTO impact_numbers (label, value, sublabel, icon, sort_order, is_active)
SELECT * FROM (
  SELECT 'Farmers Empowered' AS label, '0' AS value, 'Lives transformed through solar power' AS sublabel, 'farmers' AS icon, 10 AS sort_order, 1 AS is_active
  UNION ALL SELECT 'Acres Irrigated', '0', 'Land brought to life sustainably', 'acres', 20, 1
  UNION ALL SELECT 'Income Increase', '0%', 'Average boost in farmer earnings', 'income', 30, 1
  UNION ALL SELECT 'Regional Offices', '0', 'Serving communities across Africa', 'offices', 40, 1
) t
WHERE (SELECT COUNT(*) FROM impact_numbers) = 0;

-- =============================================================================
-- SECTION 19: SEED DATA - OFFICE LOCATIONS
-- =============================================================================

INSERT INTO office_locations (name, region_or_city, address, phone, email, sort_order, is_active)
SELECT * FROM (
  SELECT 'Head Office' AS name, 'Kampala' AS region_or_city, '' AS address, NULL AS phone, NULL AS email, 10 AS sort_order, 1 AS is_active
) t
WHERE (SELECT COUNT(*) FROM office_locations) = 0;

-- =============================================================================
-- SECTION 20: UPDATE META TITLES
-- =============================================================================

-- Add default meta titles for existing pages if empty
UPDATE pages SET meta_title = CONCAT(title, ' | Tulima Solar') WHERE meta_title IS NULL OR meta_title = '';

-- =============================================================================
-- COMPLETION MESSAGE
-- =============================================================================

SELECT 'Tulima Solar CMS database installation completed successfully!' AS status;
SELECT CONCAT('Total tables created: ', COUNT(*)) AS table_count 
FROM information_schema.tables 
WHERE table_schema = 'tulima_solar' AND table_type = 'BASE TABLE';

-- =============================================================================
-- END OF INSTALLATION SCRIPT
-- =============================================================================
