-- Migration: Fix Blog Menu URL
-- Description: Updates the blog menu item URL to point directly to /blog/ 
-- instead of using incorrect query parameters
-- Created: 2026-02-25

-- Fix blog menu item URL (various possible incorrect patterns)
UPDATE menu_items 
SET url = 'blog/' 
WHERE menu_type = 'main' 
AND (title LIKE '%blog%' OR title LIKE '%Blog%')
AND url != 'blog/';

-- Alternative fix for any blog-related URL with query params
UPDATE menu_items 
SET url = 'blog/' 
WHERE menu_type = 'main' 
AND url LIKE '%blog%page=%';

-- Fix if URL is set to index.php?page=blog
UPDATE menu_items 
SET url = 'blog/' 
WHERE menu_type = 'main' 
AND url = 'index.php?page=blog';

-- Fix if URL contains blog directory with query string
UPDATE menu_items 
SET url = 'blog/' 
WHERE menu_type = 'main' 
AND url LIKE 'blog/%?%';

-- Verification query (run this after to confirm the fix)
-- SELECT id, title, url, menu_type FROM menu_items WHERE menu_type = 'main' AND url = 'blog/';
