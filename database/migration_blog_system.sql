-- =============================================================================
-- Tulima Solar CMS - Blog Management System Migration
-- =============================================================================
-- Version: 1.0
-- Date: 2026-02-23
-- Description: Creates all tables, indexes, and relationships for the Blog CMS
--
-- This migration creates:
--   - blog_posts: Main blog post content with SEO metadata
--   - blog_categories: Hierarchical categories for posts
--   - blog_tags: Tags for flexible content organization
--   - blog_post_tags: Many-to-many relationship between posts and tags
--   - blog_comments: Threaded comments with guest support
--   - blog_post_likes: User reactions to posts
--
-- Usage:
--   mysql -u root -p tulima_solar < migration_blog_system.sql
--
-- Or run in MySQL client:
--   SOURCE /path/to/migration_blog_system.sql;
--
-- =============================================================================

USE tulima_solar;

-- =============================================================================
-- SECTION 1: BLOG CATEGORIES
-- =============================================================================
-- Categories support hierarchical structure (parent_id for nesting)
-- Each post belongs to exactly one category

CREATE TABLE IF NOT EXISTS blog_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image_path VARCHAR(500) NULL,
    parent_id INT UNSIGNED NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for fast retrieval
    INDEX idx_blog_categories_slug (slug),
    INDEX idx_blog_categories_parent (parent_id),
    INDEX idx_blog_categories_active (is_active),
    INDEX idx_blog_categories_active_sort (is_active, sort_order),
    
    -- Foreign key for self-referencing hierarchy
    CONSTRAINT fk_blog_categories_parent 
        FOREIGN KEY (parent_id) REFERENCES blog_categories(id) ON DELETE SET NULL
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Blog post categories with hierarchical support';


-- =============================================================================
-- SECTION 2: BLOG POSTS
-- =============================================================================
-- Main content table with SEO metadata, status tracking, and denormalized counters

CREATE TABLE IF NOT EXISTS blog_posts (
    -- Primary key
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Core content fields
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT NULL COMMENT 'Short summary for listings and SEO',
    
    -- Category relationship
    category_id INT UNSIGNED NULL,
    
    -- SEO metadata
    meta_title VARCHAR(255) NULL COMMENT 'SEO title - defaults to title if null',
    meta_description VARCHAR(500) NULL COMMENT 'SEO meta description',
    canonical_url VARCHAR(500) NULL COMMENT 'Canonical URL for duplicate content',
    
    -- Featured image
    featured_image_path VARCHAR(500) NULL,
    featured_image_alt VARCHAR(255) NULL,
    
    -- Post status and scheduling
    status ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft',
    publish_at TIMESTAMP NULL COMMENT 'Scheduled publish datetime',
    published_at TIMESTAMP NULL COMMENT 'Actual publish datetime',
    
    -- Denormalized counters for performance
    view_count INT UNSIGNED DEFAULT 0,
    like_count INT UNSIGNED DEFAULT 0,
    comment_count INT UNSIGNED DEFAULT 0 COMMENT 'Only approved comments',
    
    -- Author tracking
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    -- Indexes for fast retrieval
    INDEX idx_blog_posts_slug (slug),
    INDEX idx_blog_posts_status (status),
    INDEX idx_blog_posts_category (category_id),
    INDEX idx_blog_posts_created_by (created_by),
    INDEX idx_blog_posts_publish_at (publish_at),
    INDEX idx_blog_posts_published_at (published_at),
    INDEX idx_blog_posts_created_at (created_at),
    INDEX idx_blog_posts_deleted_at (deleted_at),
    
    -- Composite indexes for common queries
    INDEX idx_blog_posts_status_published (status, published_at),
    INDEX idx_blog_posts_status_category (status, category_id),
    INDEX idx_blog_posts_category_status_date (category_id, status, published_at),
    
    -- Full-text search index
    FULLTEXT INDEX ft_blog_posts_search (title, content, excerpt),
    
    -- Foreign key constraints
    CONSTRAINT fk_blog_posts_category 
        FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_blog_posts_created_by 
        FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    CONSTRAINT fk_blog_posts_updated_by 
        FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Blog posts with SEO metadata and content management';


-- =============================================================================
-- SECTION 3: BLOG TAGS
-- =============================================================================
-- Simple tag table for flexible content organization

CREATE TABLE IF NOT EXISTS blog_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_blog_tags_slug (slug),
    UNIQUE KEY uk_blog_tags_name (name)
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Blog tags for flexible content organization';


-- =============================================================================
-- SECTION 4: BLOG POST TAGS (Junction Table)
-- =============================================================================
-- Many-to-many relationship between posts and tags

CREATE TABLE IF NOT EXISTS blog_post_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Unique constraint to prevent duplicate tag assignments
    UNIQUE KEY uk_blog_post_tags (post_id, tag_id),
    
    -- Indexes for fast lookups
    INDEX idx_blog_post_tags_post (post_id),
    INDEX idx_blog_post_tags_tag (tag_id),
    
    -- Foreign key constraints
    CONSTRAINT fk_blog_post_tags_post 
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_blog_post_tags_tag 
        FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Junction table for many-to-many post-tag relationship';


-- =============================================================================
-- SECTION 5: BLOG COMMENTS
-- =============================================================================
-- Threaded comments with support for both authenticated users and guests

CREATE TABLE IF NOT EXISTS blog_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Post relationship
    post_id INT UNSIGNED NOT NULL,
    
    -- Parent comment for threading (NULL for top-level comments)
    parent_id INT UNSIGNED NULL,
    
    -- User association (for authenticated users)
    user_id INT UNSIGNED NULL COMMENT 'References admins table for now',
    
    -- Guest comment support (when user_id is NULL)
    author_name VARCHAR(100) NULL,
    author_email VARCHAR(255) NULL,
    author_url VARCHAR(500) NULL COMMENT 'Website URL for guest commenters',
    
    -- Comment content
    content TEXT NOT NULL,
    
    -- Status management
    status ENUM('pending', 'approved', 'spam', 'trash') DEFAULT 'pending',
    
    -- Spam detection and moderation
    ip_address VARCHAR(45) NULL COMMENT 'IPv4 or IPv6 address',
    user_agent TEXT NULL COMMENT 'Browser user agent for spam detection',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for common queries
    INDEX idx_blog_comments_post (post_id),
    INDEX idx_blog_comments_parent (parent_id),
    INDEX idx_blog_comments_user (user_id),
    INDEX idx_blog_comments_status (status),
    INDEX idx_blog_comments_email (author_email),
    INDEX idx_blog_comments_ip (ip_address),
    INDEX idx_blog_comments_created_at (created_at),
    
    -- Composite indexes for comment moderation and display
    INDEX idx_blog_comments_post_status (post_id, status),
    INDEX idx_blog_comments_post_status_date (post_id, status, created_at),
    INDEX idx_blog_comments_status_created (status, created_at),
    
    -- Foreign key constraints
    CONSTRAINT fk_blog_comments_post 
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_blog_comments_parent 
        FOREIGN KEY (parent_id) REFERENCES blog_comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_blog_comments_user 
        FOREIGN KEY (user_id) REFERENCES admins(id) ON DELETE SET NULL
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Blog comments with threading and guest support';


-- =============================================================================
-- SECTION 6: BLOG POST LIKES/REACTIONS
-- =============================================================================
-- User-to-post like relationship with duplicate prevention

CREATE TABLE IF NOT EXISTS blog_post_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Post relationship
    post_id INT UNSIGNED NOT NULL,
    
    -- User who liked (references admins for now, can be extended)
    user_id INT UNSIGNED NOT NULL,
    
    -- Optional: Reaction type for future extensibility
    reaction_type VARCHAR(20) DEFAULT 'like' COMMENT 'like, love, wow, etc.',
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Unique constraint: one like per user per post
    UNIQUE KEY uk_blog_post_likes (post_id, user_id),
    
    -- Indexes for aggregation and lookups
    INDEX idx_blog_post_likes_post (post_id),
    INDEX idx_blog_post_likes_user (user_id),
    INDEX idx_blog_post_likes_created_at (created_at),
    
    -- Foreign key constraints
    CONSTRAINT fk_blog_post_likes_post 
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_blog_post_likes_user 
        FOREIGN KEY (user_id) REFERENCES admins(id) ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User likes/reactions on blog posts';


-- =============================================================================
-- SECTION 7: USEFUL VIEWS
-- =============================================================================

-- View: Published posts with category and author info
CREATE OR REPLACE VIEW v_published_posts AS
SELECT 
    p.id,
    p.title,
    p.slug,
    p.excerpt,
    p.featured_image_path,
    p.featured_image_alt,
    p.view_count,
    p.like_count,
    p.comment_count,
    p.published_at,
    p.created_at,
    c.id AS category_id,
    c.name AS category_name,
    c.slug AS category_slug,
    a.username AS author_name,
    a.email AS author_email
FROM blog_posts p
LEFT JOIN blog_categories c ON p.category_id = c.id
LEFT JOIN admins a ON p.created_by = a.id
WHERE p.status = 'published' 
  AND p.deleted_at IS NULL
  AND (p.published_at IS NULL OR p.published_at <= NOW())
ORDER BY p.published_at DESC;


-- View: Comment statistics per post
CREATE OR REPLACE VIEW v_blog_comment_stats AS
SELECT 
    post_id,
    COUNT(*) AS total_comments,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_comments,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_comments,
    SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) AS top_level_comments,
    SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) AS reply_comments
FROM blog_comments
GROUP BY post_id;


-- View: Tag usage statistics
CREATE OR REPLACE VIEW v_blog_tag_stats AS
SELECT 
    t.id,
    t.name,
    t.slug,
    COUNT(pt.post_id) AS post_count
FROM blog_tags t
LEFT JOIN blog_post_tags pt ON t.id = pt.tag_id
LEFT JOIN blog_posts p ON pt.post_id = p.id AND p.status = 'published' AND p.deleted_at IS NULL
GROUP BY t.id, t.name, t.slug
ORDER BY post_count DESC;


-- =============================================================================
-- SECTION 8: TRIGGERS FOR AUTOMATIC COUNTER UPDATES
-- =============================================================================

DELIMITER //

-- Trigger: Update comment_count on blog_posts when a comment is approved
CREATE TRIGGER trg_blog_comment_count_insert
AFTER INSERT ON blog_comments
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' THEN
        UPDATE blog_posts 
        SET comment_count = comment_count + 1 
        WHERE id = NEW.post_id;
    END IF;
END//

-- Trigger: Update comment_count when comment status changes
CREATE TRIGGER trg_blog_comment_count_update
AFTER UPDATE ON blog_comments
FOR EACH ROW
BEGIN
    -- If status changed from non-approved to approved, increment
    IF OLD.status != 'approved' AND NEW.status = 'approved' THEN
        UPDATE blog_posts 
        SET comment_count = comment_count + 1 
        WHERE id = NEW.post_id;
    -- If status changed from approved to non-approved, decrement
    ELSEIF OLD.status = 'approved' AND NEW.status != 'approved' THEN
        UPDATE blog_posts 
        SET comment_count = GREATEST(comment_count - 1, 0) 
        WHERE id = NEW.post_id;
    END IF;
END//

-- Trigger: Update comment_count when a comment is deleted
CREATE TRIGGER trg_blog_comment_count_delete
AFTER DELETE ON blog_comments
FOR EACH ROW
BEGIN
    IF OLD.status = 'approved' THEN
        UPDATE blog_posts 
        SET comment_count = GREATEST(comment_count - 1, 0) 
        WHERE id = OLD.post_id;
    END IF;
END//

-- Trigger: Update like_count when a like is added
CREATE TRIGGER trg_blog_like_count_insert
AFTER INSERT ON blog_post_likes
FOR EACH ROW
BEGIN
    UPDATE blog_posts 
    SET like_count = like_count + 1 
    WHERE id = NEW.post_id;
END//

-- Trigger: Update like_count when a like is removed
CREATE TRIGGER trg_blog_like_count_delete
AFTER DELETE ON blog_post_likes
FOR EACH ROW
BEGIN
    UPDATE blog_posts 
    SET like_count = GREATEST(like_count - 1, 0) 
    WHERE id = OLD.post_id;
END//

DELIMITER ;


-- =============================================================================
-- SECTION 9: SAMPLE SEED DATA
-- =============================================================================

-- Sample Categories
INSERT INTO blog_categories (name, slug, description, parent_id, sort_order, is_active) VALUES
('Solar Energy', 'solar-energy', 'Articles about solar energy solutions and technologies', NULL, 1, 1),
('Agriculture', 'agriculture', 'Farming tips, techniques, and agricultural innovations', NULL, 2, 1),
('Company News', 'company-news', 'Latest updates and news from Tulima Solar', NULL, 3, 1),
('Product Updates', 'product-updates', 'New product announcements and updates', NULL, 4, 1),
('Customer Stories', 'customer-stories', 'Success stories and testimonials from our customers', NULL, 5, 1),
-- Subcategories
('Solar Panels', 'solar-panels', 'Solar panel technology and installation guides', 1, 1, 1),
('Solar Pumps', 'solar-pumps', 'Solar-powered water pumping solutions', 1, 2, 1),
('Irrigation', 'irrigation', 'Modern irrigation techniques and systems', 2, 1, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);


-- Sample Tags
INSERT INTO blog_tags (name, slug, description) VALUES
('Tips', 'tips', 'Helpful tips and guides'),
('Tutorial', 'tutorial', 'Step-by-step tutorials'),
('News', 'news', 'News and announcements'),
('Innovation', 'innovation', 'Innovative technologies and solutions'),
('Sustainability', 'sustainability', 'Sustainable practices and environmental impact'),
('Africa', 'africa', 'Content related to African markets'),
('Uganda', 'uganda', 'Content specific to Uganda'),
('Case Study', 'case-study', 'In-depth case studies'),
('How-To', 'how-to', 'How-to guides and instructions')
ON DUPLICATE KEY UPDATE name = VALUES(name);


-- =============================================================================
-- SECTION 10: STORED PROCEDURES FOR COMMON OPERATIONS
-- =============================================================================

DELIMITER //

-- Procedure: Publish scheduled posts (can be run via cron job)
CREATE PROCEDURE sp_publish_scheduled_posts()
BEGIN
    UPDATE blog_posts
    SET 
        status = 'published',
        published_at = COALESCE(published_at, NOW())
    WHERE status = 'scheduled'
      AND publish_at <= NOW()
      AND deleted_at IS NULL;
    
    SELECT ROW_COUNT() AS posts_published;
END//

-- Procedure: Get posts by category with pagination
CREATE PROCEDURE sp_get_posts_by_category(
    IN p_category_slug VARCHAR(100),
    IN p_page INT,
    IN p_per_page INT
)
BEGIN
    DECLARE v_offset INT;
    SET v_offset = (p_page - 1) * p_per_page;
    
    SELECT 
        p.id,
        p.title,
        p.slug,
        p.excerpt,
        p.featured_image_path,
        p.featured_image_alt,
        p.view_count,
        p.like_count,
        p.comment_count,
        p.published_at,
        c.name AS category_name,
        c.slug AS category_slug
    FROM blog_posts p
    INNER JOIN blog_categories c ON p.category_id = c.id
    WHERE c.slug = p_category_slug
      AND p.status = 'published'
      AND p.deleted_at IS NULL
      AND (p.published_at IS NULL OR p.published_at <= NOW())
    ORDER BY p.published_at DESC
    LIMIT v_offset, p_per_page;
END//

-- Procedure: Search posts with full-text search
CREATE PROCEDURE sp_search_posts(
    IN p_search_term VARCHAR(255),
    IN p_page INT,
    IN p_per_page INT
)
BEGIN
    DECLARE v_offset INT;
    SET v_offset = (p_page - 1) * p_per_page;
    
    SELECT 
        p.id,
        p.title,
        p.slug,
        p.excerpt,
        p.featured_image_path,
        p.view_count,
        p.like_count,
        p.comment_count,
        p.published_at,
        c.name AS category_name,
        MATCH(p.title, p.content, p.excerpt) AGAINST(p_search_term IN NATURAL LANGUAGE MODE) AS relevance
    FROM blog_posts p
    LEFT JOIN blog_categories c ON p.category_id = c.id
    WHERE p.status = 'published'
      AND p.deleted_at IS NULL
      AND (p.published_at IS NULL OR p.published_at <= NOW())
      AND MATCH(p.title, p.content, p.excerpt) AGAINST(p_search_term IN NATURAL LANGUAGE MODE)
    ORDER BY relevance DESC
    LIMIT v_offset, p_per_page;
END//

-- Procedure: Increment view count for a post
CREATE PROCEDURE sp_increment_post_view(IN p_post_id INT UNSIGNED)
BEGIN
    UPDATE blog_posts 
    SET view_count = view_count + 1 
    WHERE id = p_post_id;
END//

DELIMITER ;


-- =============================================================================
-- SECTION 11: GRANT PERMISSIONS (Optional - uncomment if needed)
-- =============================================================================

-- GRANT SELECT, INSERT, UPDATE, DELETE ON tulima_solar.blog_* TO 'tulima_user'@'localhost';
-- FLUSH PRIVILEGES;


-- =============================================================================
-- MIGRATION COMPLETE
-- =============================================================================
-- 
-- Tables created:
--   - blog_categories (8 columns + indexes)
--   - blog_posts (22 columns + indexes + full-text)
--   - blog_tags (5 columns + indexes)
--   - blog_post_tags (junction table)
--   - blog_comments (15 columns + indexes)
--   - blog_post_likes (5 columns + indexes)
--
-- Views created:
--   - v_published_posts
--   - v_blog_comment_stats
--   - v_blog_tag_stats
--
-- Triggers created:
--   - trg_blog_comment_count_insert
--   - trg_blog_comment_count_update
--   - trg_blog_comment_count_delete
--   - trg_blog_like_count_insert
--   - trg_blog_like_count_delete
--
-- Stored procedures:
--   - sp_publish_scheduled_posts
--   - sp_get_posts_by_category
--   - sp_search_posts
--   - sp_increment_post_view
--
-- =============================================================================
