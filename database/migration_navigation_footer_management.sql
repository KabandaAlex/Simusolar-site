-- Migration: Navigation and Footer Management System
-- Description: Comprehensive navigation and footer management with hierarchical structure
-- Date: 2025-02-24

-- ============================================
-- MENU ITEMS TABLE (Hierarchical Navigation)
-- ============================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    page_id INT UNSIGNED DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    target ENUM('_self', '_blank', '_parent', '_top') DEFAULT '_self',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    menu_type ENUM('main', 'mobile', 'footer') DEFAULT 'main',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_menu_parent (parent_id),
    INDEX idx_menu_type (menu_type),
    INDEX idx_menu_active (is_active),
    INDEX idx_menu_sort (menu_type, sort_order),
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Hierarchical menu items for navigation and footer';

-- ============================================
-- FOOTER SECTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS footer_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    section_type ENUM('brand', 'links', 'products', 'contact', 'custom') DEFAULT 'custom',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_footer_sort (sort_order),
    INDEX idx_footer_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Footer sections for organizing footer content';

-- ============================================
-- FOOTER LINKS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS footer_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    page_id INT UNSIGNED DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    target ENUM('_self', '_blank', '_parent', '_top') DEFAULT '_self',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_footer_link_section (section_id),
    INDEX idx_footer_link_sort (section_id, sort_order),
    INDEX idx_footer_link_active (is_active),
    FOREIGN KEY (section_id) REFERENCES footer_sections(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual links within footer sections';

-- ============================================
-- NAVIGATION ASSETS TABLE (Logos, Images)
-- ============================================
CREATE TABLE IF NOT EXISTS navigation_assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_type ENUM('logo', 'favicon', 'mobile_logo', 'footer_logo') NOT NULL,
    asset_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    width INT DEFAULT NULL,
    height INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nav_asset_type (asset_type),
    INDEX idx_nav_asset_active (is_active),
    INDEX idx_nav_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Navigation and footer assets (logos, images)';

-- ============================================
-- SOCIAL MEDIA LINKS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS social_media_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform ENUM('facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'tiktok', 'pinterest', 'whatsapp') NOT NULL,
    url VARCHAR(500) NOT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_social_platform (platform),
    INDEX idx_social_sort (sort_order),
    INDEX idx_social_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Social media links for navigation and footer';

-- ============================================
-- INSERT DEFAULT FOOTER SECTIONS
-- ============================================
INSERT INTO footer_sections (title, section_type, sort_order, is_active) VALUES
('Brand', 'brand', 1, 1),
('Quick Links', 'links', 2, 1),
('Products', 'products', 3, 1),
('Contact Info', 'contact', 4, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- ============================================
-- INSERT DEFAULT SOCIAL MEDIA LINKS
-- ============================================
INSERT INTO social_media_links (platform, url, sort_order, is_active) VALUES
('facebook', 'https://facebook.com/tulimasolar', 1, 1),
('twitter', 'https://twitter.com/tulimasolar', 2, 1),
('linkedin', 'https://linkedin.com/company/tulimasolar', 3, 1),
('instagram', 'https://instagram.com/tulimasolar', 4, 1),
('youtube', 'https://youtube.com/@tulimasolar', 5, 1)
ON DUPLICATE KEY UPDATE url=VALUES(url);

-- ============================================
-- MIGRATE EXISTING PAGES TO MENU ITEMS
-- ============================================
-- Create menu items from existing pages for main navigation
INSERT INTO menu_items (title, url, page_id, sort_order, is_active, menu_type)
SELECT 
    title,
    CONCAT('index.php?page=', slug),
    id,
    sort_order,
    is_active,
    'main'
FROM pages 
WHERE is_active = 1 AND slug != 'home'
ON DUPLICATE KEY UPDATE 
    title=VALUES(title),
    url=VALUES(url),
    sort_order=VALUES(sort_order),
    is_active=VALUES(is_active);

-- Create menu items from existing pages for footer
INSERT INTO menu_items (title, url, page_id, sort_order, is_active, menu_type)
SELECT 
    title,
    CONCAT('index.php?page=', slug),
    id,
    sort_order,
    show_in_footer,
    'footer'
FROM pages 
WHERE show_in_footer = 1
ON DUPLICATE KEY UPDATE 
    title=VALUES(title),
    url=VALUES(url),
    sort_order=VALUES(sort_order),
    is_active=VALUES(is_active);

-- ============================================
-- ADD COLUMNS FOR MENU SYNC
-- ============================================
-- Add column to track if page is synced with menu
ALTER TABLE pages ADD COLUMN IF NOT EXISTS sync_with_menu TINYINT(1) DEFAULT 1 AFTER show_in_footer;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS sync_with_footer TINYINT(1) DEFAULT 1 AFTER sync_with_menu;

-- ============================================
-- CREATE INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX IF NOT EXISTS idx_pages_sync_menu ON pages(sync_with_menu, is_active);
CREATE INDEX IF NOT EXISTS idx_pages_sync_footer ON pages(sync_with_footer, show_in_footer);

-- ============================================
-- COMPLETION NOTIFICATION
-- ============================================
-- Migration completed successfully
-- Tables created: menu_items, footer_sections, footer_links, navigation_assets, social_media_links
-- Default data inserted: footer_sections, social_media_links
-- Existing pages migrated to menu_items
