-- Migration: Contact Inquiry Reasons
-- Description: Dynamic inquiry reasons for contact form that can be managed from admin
-- Date: 2026-02-26

-- ============================================
-- CONTACT INQUIRY TYPES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS contact_inquiry_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_inquiry_sort (sort_order),
    INDEX idx_inquiry_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Contact form inquiry types for dynamic dropdown';

-- ============================================
-- CONTACT INQUIRY REASONS TABLE (Sub-options)
-- ============================================
CREATE TABLE IF NOT EXISTS contact_inquiry_reasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_type_id INT UNSIGNED NOT NULL,
    reason VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reason_type (inquiry_type_id),
    INDEX idx_reason_sort (inquiry_type_id, sort_order),
    FOREIGN KEY (inquiry_type_id) REFERENCES contact_inquiry_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Contact form inquiry reasons linked to inquiry types';

-- ============================================
-- SEED DEFAULT INQUIRY TYPES
-- ============================================
INSERT INTO contact_inquiry_types (name, slug, description, icon, sort_order, is_active) VALUES
('General Inquiry', 'general', 'General questions and information requests', 'help-circle', 1, 1),
('Request a Quote', 'quote', 'Get a customized quote for solar solutions', 'file-text', 2, 1),
('Technical Support', 'support', 'Technical assistance and troubleshooting', 'tool', 3, 1),
('Partnership', 'partnership', 'Business partnership opportunities', 'users', 4, 1),
('Careers', 'careers', 'Job opportunities and career inquiries', 'briefcase', 5, 1);

-- ============================================
-- SEED DEFAULT INQUIRY REASONS
-- ============================================
-- General Inquiry Reasons
INSERT INTO contact_inquiry_reasons (inquiry_type_id, reason, sort_order, is_active) VALUES
(1, 'Product Information', 1, 1),
(1, 'Service Details', 2, 1),
(1, 'General Question', 3, 1),
(1, 'Feedback', 4, 1),
(1, 'Other', 5, 1);

-- Quote Reasons
INSERT INTO contact_inquiry_reasons (inquiry_type_id, reason, sort_order, is_active) VALUES
(2, 'Residential Solar System', 1, 1),
(2, 'Commercial Solar Installation', 2, 1),
(2, 'Solar Water Pumping', 3, 1),
(2, 'Solar Maintenance', 4, 1),
(2, 'Battery Storage', 5, 1),
(2, 'Full Energy Audit', 6, 1);

-- Support Reasons
INSERT INTO contact_inquiry_reasons (inquiry_type_id, reason, sort_order, is_active) VALUES
(3, 'System Not Working', 1, 1),
(3, 'Performance Issues', 2, 1),
(3, 'Warranty Claim', 3, 1),
(3, 'Maintenance Request', 4, 1),
(3, 'Technical Question', 5, 1);

-- Partnership Reasons
INSERT INTO contact_inquiry_reasons (inquiry_type_id, reason, sort_order, is_active) VALUES
(4, 'Distribution Partnership', 1, 1),
(4, 'Installation Partnership', 2, 1),
(4, 'Supplier Inquiry', 3, 1),
(4, 'Investment Opportunity', 4, 1);

-- Careers Reasons
INSERT INTO contact_inquiry_reasons (inquiry_type_id, reason, sort_order, is_active) VALUES
(5, 'Job Application', 1, 1),
(5, 'Internship Inquiry', 2, 1),
(5, 'CV Submission', 3, 1),
(5, 'Recruitment Question', 4, 1);

-- ============================================
-- ADD NEWSLETTER SUBSCRIPTION TO CONTACT SUBMISSIONS
-- ============================================
ALTER TABLE contact_submissions
ADD COLUMN subscribe_newsletter TINYINT(1) DEFAULT 0 AFTER message;

-- ============================================
-- ADD PREFERRED CONTACT METHOD TO CONTACT SUBMISSIONS
-- ============================================
ALTER TABLE contact_submissions
ADD COLUMN preferred_contact ENUM('email', 'phone', 'whatsapp') DEFAULT 'email' AFTER subscribe_newsletter;

-- Migration completed
SELECT 'Migration completed: Contact inquiry types and reasons tables created' AS status;
