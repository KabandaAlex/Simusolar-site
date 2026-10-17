-- Tulima Solar - Analytics Tables Migration
-- Run this to add analytics tracking capabilities

USE tulima_solar;

-- Page views tracking for traffic analytics
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
COMMENT='Tracks page views for analytics';

-- Activity log for admin actions
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
COMMENT='Logs all admin actions for audit trail';

-- Notifications system
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

-- Product analytics for tracking views and inquiries
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

-- Add status tracking to contact_submissions
ALTER TABLE contact_submissions 
ADD COLUMN IF NOT EXISTS status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new' AFTER submitted_at,
ADD COLUMN IF NOT EXISTS replied_at TIMESTAMP NULL AFTER status,
ADD COLUMN IF NOT EXISTS reply_notes TEXT NULL AFTER replied_at,
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_submitted_at (submitted_at);

-- Add analytics_enabled flag to site_settings
INSERT INTO site_settings (`key`, value, label) VALUES
('analytics_enabled', '1', 'Enable Analytics Tracking'),
('analytics_retention_days', '90', 'Analytics Data Retention (Days)')
ON DUPLICATE KEY UPDATE `key` = `key`;
