-- Tulima Solar - Dynamic Pages Migration
-- Run this migration to enable dynamic page creation and routing
-- Version: 1.0 | Date: 2026-02-23

-- Add new columns to pages table for dynamic routing and SEO
ALTER TABLE pages ADD COLUMN IF NOT EXISTS template_type ENUM('fullwidth', 'with_sidebar', 'landing', 'custom') DEFAULT 'fullwidth' AFTER is_active;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS meta_title VARCHAR(255) DEFAULT NULL AFTER meta_description;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS requires_auth TINYINT(1) DEFAULT 0 AFTER template_type;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS parent_id INT UNSIGNED DEFAULT NULL AFTER slug;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS custom_template VARCHAR(100) DEFAULT NULL AFTER template_type;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS layout_file VARCHAR(100) DEFAULT 'default' AFTER custom_template;

-- Add show_in_footer column if it doesn't exist
ALTER TABLE pages ADD COLUMN IF NOT EXISTS show_in_footer TINYINT(1) DEFAULT 0 AFTER is_active;

-- Add foreign key for parent_id (self-referencing for page hierarchy)
ALTER TABLE pages ADD CONSTRAINT fk_pages_parent FOREIGN KEY IF NOT EXISTS (parent_id) REFERENCES pages(id) ON DELETE SET NULL;

-- Add index for faster slug lookups including nested slugs
CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug);
CREATE INDEX IF NOT EXISTS idx_pages_parent_slug ON pages(parent_id, slug);
CREATE INDEX IF NOT EXISTS idx_pages_active ON pages(is_active);

-- Create page_templates table for custom template management
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default templates
INSERT INTO page_templates (name, display_name, description, template_file, supports_sections, supports_hero, supports_sidebar) VALUES
('fullwidth', 'Full Width', 'A full-width page layout with no sidebar, ideal for landing pages and showcasing content.', 'fullwidth.php', 1, 1, 0),
('with_sidebar', 'With Sidebar', 'A page layout with a sidebar for navigation or additional content.', 'sidebar.php', 1, 1, 1),
('landing', 'Landing Page', 'A conversion-focused landing page template with hero section and call-to-action areas.', 'landing.php', 1, 1, 0),
('custom', 'Custom Template', 'Use a custom PHP template file for advanced layouts.', 'custom.php', 1, 1, 0)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- Create page_hierarchy view for easy querying of page tree
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

-- Add audit logging for page changes (if activity_log table exists)
-- This trigger logs when a new page is created
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_page_insert
AFTER INSERT ON pages
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'activity_log') THEN
        INSERT INTO activity_log (user_type, user_id, action, entity_type, entity_id, details, created_at)
        VALUES ('admin', @current_admin_id, 'create', 'page', NEW.id, CONCAT('Created page: ', NEW.title, ' (', NEW.slug, ')'), NOW());
    END IF;
END//
DELIMITER ;

-- This trigger logs when a page is updated
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_page_update
AFTER UPDATE ON pages
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'activity_log') THEN
        INSERT INTO activity_log (user_type, user_id, action, entity_type, entity_id, details, created_at)
        VALUES ('admin', @current_admin_id, 'update', 'page', NEW.id, CONCAT('Updated page: ', NEW.title, ' (', NEW.slug, ')'), NOW());
    END IF;
END//
DELIMITER ;

-- Update existing pages with default template types based on their slugs
UPDATE pages SET template_type = 'fullwidth' WHERE slug IN ('home', 'about', 'impact', 'contact', 'testimonials');
UPDATE pages SET template_type = 'landing' WHERE slug IN ('products', 'solar-water-pumps', 'surface-pumps', 'submersible-pumps', 'agricultural-processing-machines');
UPDATE pages SET template_type = 'with_sidebar' WHERE slug IN ('privacy', 'terms', 'sitemap');

-- Add default meta titles for existing pages if empty
UPDATE pages SET meta_title = CONCAT(title, ' | Tulima Solar') WHERE meta_title IS NULL OR meta_title = '';

-- Create index for faster template lookups
CREATE INDEX IF NOT EXISTS idx_templates_active ON page_templates(is_active);

-- Migration complete message
SELECT 'Dynamic pages migration completed successfully!' AS status;
