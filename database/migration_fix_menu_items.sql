-- Fix menu_items table structure
-- This migration fixes missing columns in the menu_items table

-- Add sort_order column if it doesn't exist
ALTER TABLE menu_items
ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0 AFTER target;

-- Add menu_type column if it doesn't exist
ALTER TABLE menu_items
ADD COLUMN IF NOT EXISTS menu_type ENUM('main', 'mobile', 'footer') DEFAULT 'main' AFTER is_active;

-- Add created_at and updated_at columns if they don't exist
ALTER TABLE menu_items
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_menu_parent ON menu_items(parent_id);
CREATE INDEX IF NOT EXISTS idx_menu_type ON menu_items(menu_type);
CREATE INDEX IF NOT EXISTS idx_menu_active ON menu_items(is_active);
CREATE INDEX IF NOT EXISTS idx_menu_sort ON menu_items(menu_type, sort_order);

-- Add foreign key constraint if it doesn't exist
ALTER TABLE menu_items
ADD CONSTRAINT IF NOT EXISTS fk_menu_items_parent
FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE;

ALTER TABLE menu_items
ADD CONSTRAINT IF NOT EXISTS fk_menu_items_page
FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL;
