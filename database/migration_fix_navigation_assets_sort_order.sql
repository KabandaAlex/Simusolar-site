-- Migration: Fix navigation_assets table - Add sort_order column
-- Date: 2026-02-24
-- Description: Adds the missing sort_order column to navigation_assets table
--              which is required by navigation-builder.php

-- Add sort_order column to navigation_assets table
ALTER TABLE navigation_assets 
ADD COLUMN sort_order INT DEFAULT 0 AFTER is_active;

-- Add index for better sorting performance
ALTER TABLE navigation_assets 
ADD INDEX idx_nav_sort_order (sort_order);

-- Update existing records to have a default sort order based on id
UPDATE navigation_assets SET sort_order = id WHERE sort_order = 0;

-- Migration completed
SELECT 'Migration completed: sort_order column added to navigation_assets' AS status;
