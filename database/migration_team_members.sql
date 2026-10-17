-- Tulima Solar - Team Members Migration
-- Run this to add team members support for the Who-we-are page
-- Categories: Directors, Advisors, Staff (hierarchy levels)

USE tulima_solar;

-- =====================================================
-- Team Members: one row per team member
-- Used on the Who-we-are page to display organizational hierarchy
-- =====================================================
CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    job_title VARCHAR(255) NOT NULL DEFAULT '',
    biography TEXT NULL,
    profile_image VARCHAR(500) NULL DEFAULT NULL,
    category ENUM('director', 'advisor', 'staff') NOT NULL DEFAULT 'staff',
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, display_order),
    KEY idx_category (category),
    KEY idx_category_active_sort (category, is_active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Indexes explained:
-- - idx_active_sort: For listing active members in order
-- - idx_category: For filtering by category
-- - idx_category_active_sort: For listing active members within a category in order
-- =====================================================

-- =====================================================
-- Sample data (optional - uncomment to use)
-- =====================================================
-- INSERT INTO team_members (name, job_title, biography, profile_image, category, display_order, is_active) VALUES
-- ('John Doe', 'Executive Director', 'John has over 20 years of experience in renewable energy...', 'media/team_members/john-doe.jpg', 'director', 1, 1),
-- ('Jane Smith', 'Board Advisor', 'Jane brings expertise in sustainable agriculture...', 'media/team_members/jane-smith.jpg', 'advisor', 1, 1),
-- ('Bob Johnson', 'Operations Manager', 'Bob oversees daily operations...', 'media/team_members/bob-johnson.jpg', 'staff', 1, 1);

-- =====================================================
-- Rollback commands (if needed)
-- =====================================================
-- DROP TABLE IF EXISTS team_members;
