-- ============================================================================
-- Menu Items Migration/Seed
-- ============================================================================
-- This migration seeds the menu_items table with navigation data for:
-- - Main Navigation (header)
-- - Mobile Navigation
-- - Footer Navigation (with parent-child structure)
--
-- Usage: Run this SQL file to populate/refresh menu items
-- Can be run multiple times without duplicating data (uses TRUNCATE + INSERT)
-- ============================================================================

-- Disable foreign key checks temporarily to allow TRUNCATE
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing menu items
TRUNCATE TABLE menu_items;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- MAIN NAVIGATION (menu_type = 'main')
-- ============================================================================
-- These items appear in the main header navigation

INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type) VALUES
(NULL, 'Home', '/', NULL, NULL, '_self', 1, 1, 'main'),
(NULL, 'About', 'about', NULL, NULL, '_self', 2, 1, 'main'),
(NULL, 'Services', 'services', NULL, NULL, '_self', 3, 1, 'main'),
(NULL, 'Projects', 'projects', NULL, NULL, '_self', 4, 1, 'main'),
(NULL, 'Products', 'products', NULL, NULL, '_self', 5, 1, 'main'),
(NULL, 'Blog', 'blog', NULL, NULL, '_self', 6, 1, 'main'),
(NULL, 'Careers', 'careers', NULL, NULL, '_self', 7, 1, 'main'),
(NULL, 'Contact', 'contact', NULL, NULL, '_self', 8, 1, 'main');

-- ============================================================================
-- MOBILE NAVIGATION (menu_type = 'mobile')
-- ============================================================================
-- These items appear in the mobile hamburger menu
-- Same structure as main navigation but for mobile devices

INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type) VALUES
(NULL, 'Home', '/', NULL, NULL, '_self', 1, 1, 'mobile'),
(NULL, 'About', 'about', NULL, NULL, '_self', 2, 1, 'mobile'),
(NULL, 'Services', 'services', NULL, NULL, '_self', 3, 1, 'mobile'),
(NULL, 'Projects', 'projects', NULL, NULL, '_self', 4, 1, 'mobile'),
(NULL, 'Products', 'products', NULL, NULL, '_self', 5, 1, 'mobile'),
(NULL, 'Blog', 'blog', NULL, NULL, '_self', 6, 1, 'mobile'),
(NULL, 'Careers', 'careers', NULL, NULL, '_self', 7, 1, 'mobile'),
(NULL, 'Contact', 'contact', NULL, NULL, '_self', 8, 1, 'mobile');

-- ============================================================================
-- FOOTER NAVIGATION (menu_type = 'footer')
-- ============================================================================
-- Footer has a two-level structure with parent categories and child links
-- Parents are inserted first, then children reference parent IDs

-- Step 1: Insert parent items (column headers in footer)
INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type) VALUES
(NULL, 'Quick Links', '#', NULL, NULL, '_self', 1, 1, 'footer'),
(NULL, 'Services', '#', NULL, NULL, '_self', 2, 1, 'footer');

-- Step 2: Insert child items under "Quick Links" (sort_order = 1, id will be used)
-- Using subquery to get the parent_id dynamically
INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type)
SELECT 
    (SELECT id FROM menu_items WHERE title = 'Quick Links' AND menu_type = 'footer' AND parent_id IS NULL),
    'Home', '/', NULL, NULL, '_self', 1, 1, 'footer';

INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type)
SELECT 
    (SELECT id FROM menu_items WHERE title = 'Quick Links' AND menu_type = 'footer' AND parent_id IS NULL),
    'About', 'about', NULL, NULL, '_self', 2, 1, 'footer';

INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type)
SELECT 
    (SELECT id FROM menu_items WHERE title = 'Quick Links' AND menu_type = 'footer' AND parent_id IS NULL),
    'Services', 'services', NULL, NULL, '_self', 3, 1, 'footer';

INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type)
SELECT 
    (SELECT id FROM menu_items WHERE title = 'Quick Links' AND menu_type = 'footer' AND parent_id IS NULL),
    'Contact', 'contact', NULL, NULL, '_self', 4, 1, 'footer';

-- Step 3: Insert child items under "Services" (sort_order = 2)
INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type)
SELECT 
    (SELECT id FROM menu_items WHERE title = 'Services' AND menu_type = 'footer' AND parent_id IS NULL AND url = '#'),
    'Projects', 'projects', NULL, NULL, '_self', 1, 1, 'footer';

INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type)
SELECT 
    (SELECT id FROM menu_items WHERE title = 'Services' AND menu_type = 'footer' AND parent_id IS NULL AND url = '#'),
    'Products', 'products', NULL, NULL, '_self', 2, 1, 'footer';

INSERT INTO menu_items (parent_id, title, url, page_id, icon, target, sort_order, is_active, menu_type)
SELECT 
    (SELECT id FROM menu_items WHERE title = 'Services' AND menu_type = 'footer' AND parent_id IS NULL AND url = '#'),
    'Blog', 'blog', NULL, NULL, '_self', 3, 1, 'footer';

-- ============================================================================
-- VERIFICATION QUERIES (Optional - uncomment to verify)
-- ============================================================================
-- SELECT 'Main Navigation' AS menu_type, COUNT(*) AS item_count FROM menu_items WHERE menu_type = 'main'
-- UNION ALL
-- SELECT 'Mobile Navigation', COUNT(*) FROM menu_items WHERE menu_type = 'mobile'
-- UNION ALL
-- SELECT 'Footer Navigation', COUNT(*) FROM menu_items WHERE menu_type = 'footer'
-- UNION ALL
-- SELECT 'Footer Parents', COUNT(*) FROM menu_items WHERE menu_type = 'footer' AND parent_id IS NULL
-- UNION ALL
-- SELECT 'Footer Children', COUNT(*) FROM menu_items WHERE menu_type = 'footer' AND parent_id IS NOT NULL;

-- ============================================================================
-- EXPECTED RESULTS AFTER RUNNING THIS MIGRATION:
-- ============================================================================
-- Main Navigation:    8 items
-- Mobile Navigation:  8 items
-- Footer Navigation: 11 items (2 parents + 9 children)
-- Total:             27 items
-- ============================================================================
