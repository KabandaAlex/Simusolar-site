-- Tulima Solar - Product Categories Migration
-- Run this to add category support to products
-- Created for Phase 8: Product Categories System

-- =====================================================
-- 1. Create product_categories table
-- =====================================================
CREATE TABLE IF NOT EXISTS product_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    parent_id INT UNSIGNED NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for slug lookups
CREATE INDEX idx_product_categories_slug ON product_categories(slug);
CREATE INDEX idx_product_categories_parent ON product_categories(parent_id);
CREATE INDEX idx_product_categories_active ON product_categories(is_active);

-- =====================================================
-- 2. Add category_id and featured columns to products table
-- =====================================================
ALTER TABLE products 
ADD COLUMN category_id INT UNSIGNED NULL AFTER slug,
ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER is_active,
ADD CONSTRAINT fk_products_category 
    FOREIGN KEY (category_id) REFERENCES product_categories(id) 
    ON DELETE SET NULL;

-- Add index for category lookups
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_featured ON products(featured);

-- =====================================================
-- 3. Insert default categories based on existing product types
-- =====================================================

-- Parent Categories
INSERT INTO product_categories (name, slug, description, parent_id, sort_order, is_active) VALUES
('Solar Water Pumps', 'solar-water-pumps', 'Solar-powered water pumping solutions for irrigation and domestic use.', NULL, 1, 1),
('Agricultural Processing Machines', 'agricultural-processing-machines', 'Machines for processing agricultural products after harvest.', NULL, 2, 1),
('Solar Systems', 'solar-systems', 'Complete solar energy systems for homes and businesses.', NULL, 3, 1),
('Accessories', 'accessories', 'Accessories and spare parts for solar and agricultural equipment.', NULL, 4, 1);

-- Get parent IDs for subcategories
SET @pump_id = (SELECT id FROM product_categories WHERE slug = 'solar-water-pumps');
SET @agri_id = (SELECT id FROM product_categories WHERE slug = 'agricultural-processing-machines');

-- Subcategories for Solar Water Pumps
INSERT INTO product_categories (name, slug, description, parent_id, sort_order, is_active) VALUES
('Submersible Pumps', 'submersible-pumps', 'Solar-powered submersible pumps for deep wells and boreholes.', @pump_id, 1, 1),
('Surface Pumps', 'surface-pumps', 'Solar-powered surface pumps for shallow water sources.', @pump_id, 2, 1);

-- Subcategories for Agricultural Processing Machines
INSERT INTO product_categories (name, slug, description, parent_id, sort_order, is_active) VALUES
('Milling Machines', 'milling-machines', 'Machines for milling grains and cereals.', @agri_id, 1, 1),
('Threshing Machines', 'threshing-machines', 'Machines for threshing grains from their husks.', @agri_id, 2, 1),
('Chaff Cutters', 'chaff-cutters', 'Machines for cutting straw and hay for animal feed.', @agri_id, 3, 1);

-- =====================================================
-- 4. Update existing products with category assignments
-- =====================================================

-- Assign pumps to Solar Water Pumps category
UPDATE products SET category_id = @pump_id 
WHERE slug LIKE '%pump%' OR title LIKE '%Pump%';

-- Assign threshing machines
UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'threshing-machines')
WHERE slug LIKE '%thresh%' OR title LIKE '%Thresh%';

-- Assign milling machines
UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'milling-machines')
WHERE slug LIKE '%mill%' OR title LIKE '%Mill%';

-- Assign chaff cutters
UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'chaff-cutters')
WHERE slug LIKE '%chaff%' OR title LIKE '%Chaff%' OR slug LIKE '%cutter%' OR title LIKE '%Cutter%';

-- =====================================================
-- 5. Create view for category product counts
-- =====================================================
CREATE OR REPLACE VIEW v_category_stats AS
SELECT 
    c.id,
    c.name,
    c.slug,
    c.parent_id,
    c.is_active,
    COUNT(p.id) AS product_count,
    SUM(CASE WHEN p.is_active = 1 THEN 1 ELSE 0 END) AS active_product_count
FROM product_categories c
LEFT JOIN products p ON c.id = p.category_id
GROUP BY c.id, c.name, c.slug, c.parent_id, c.is_active;

-- =====================================================
-- 6. Add sample category images (optional - update paths as needed)
-- =====================================================
-- UPDATE product_categories SET image = 'media/categories/solar-pumps.jpg' WHERE slug = 'solar-water-pumps';
-- UPDATE product_categories SET image = 'media/categories/agri-machines.jpg' WHERE slug = 'agricultural-processing-machines';
-- UPDATE product_categories SET image = 'media/categories/solar-systems.jpg' WHERE slug = 'solar-systems';
-- UPDATE product_categories SET image = 'media/categories/accessories.jpg' WHERE slug = 'accessories';

-- =====================================================
-- Rollback commands (if needed)
-- =====================================================
-- DROP VIEW IF EXISTS v_category_stats;
-- ALTER TABLE products DROP FOREIGN KEY fk_products_category;
-- ALTER TABLE products DROP COLUMN category_id;
-- ALTER TABLE products DROP COLUMN featured;
-- DROP TABLE IF EXISTS product_categories;
