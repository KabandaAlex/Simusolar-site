-- ============================================================================
-- Migration: Job Portal Enhancements
-- Description: Adds currency, category, and location management along with
--              repost functionality for the job portal
-- Created: 2026-02-24
-- ============================================================================

-- ============================================================================
-- SECTION 1: NEW TABLES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: currencies
-- Purpose: Store supported currencies for salary display
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    exchange_rate DECIMAL(15,6) DEFAULT 1.000000,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_currencies_active (is_active),
    INDEX idx_currencies_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: job_categories
-- Purpose: Organize jobs into categories with hierarchical support
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    icon VARCHAR(50),
    meta_title VARCHAR(255),
    meta_description TEXT,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES job_categories(id) ON DELETE SET NULL,
    INDEX idx_job_categories_slug (slug),
    INDEX idx_job_categories_active (is_active),
    INDEX idx_job_categories_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Add missing columns to job_categories if they don't exist
-- This handles cases where the table already existed from base migration
-- ----------------------------------------------------------------------------

-- Add parent_id column if not exists
SET @dbname = DATABASE();
SET @tablename = 'job_categories';
SET @column_name = 'parent_id';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @column_name) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @column_name, ' INT DEFAULT NULL')
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add display_order column if not exists
SET @column_name = 'display_order';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @column_name) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @column_name, ' INT DEFAULT 0')
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add color column if not exists
SET @column_name = 'color';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @column_name) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @column_name, ' VARCHAR(7) DEFAULT ''#3498db''')
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add meta_title column if not exists
SET @column_name = 'meta_title';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @column_name) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @column_name, ' VARCHAR(255)')
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add meta_description column if not exists
SET @column_name = 'meta_description';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @column_name) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @column_name, ' TEXT')
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on parent_id if not exists
SET @index_name = 'idx_job_categories_parent';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @index_name) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD INDEX ', @index_name, ' (parent_id)')
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- Table: job_locations
-- Purpose: Store job locations with remote work support
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    region VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Kenya',
    postal_code VARCHAR(20),
    meta_title VARCHAR(255),
    meta_description TEXT,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_remote TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_job_locations_slug (slug),
    INDEX idx_job_locations_active (is_active),
    INDEX idx_job_locations_remote (is_remote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SECTION 2: MODIFY JOBS TABLE
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Add foreign key columns for category, location, and currency
-- ----------------------------------------------------------------------------

-- Add category_id column
SET @exist_category := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'category_id');
SET @sql_category := IF(@exist_category = 0, 
    'ALTER TABLE jobs ADD COLUMN category_id INT DEFAULT NULL AFTER status', 
    'SELECT "Column category_id already exists"');
PREPARE stmt FROM @sql_category;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add location_id column
SET @exist_location := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'location_id');
SET @sql_location := IF(@exist_location = 0, 
    'ALTER TABLE jobs ADD COLUMN location_id INT DEFAULT NULL AFTER category_id', 
    'SELECT "Column location_id already exists"');
PREPARE stmt FROM @sql_location;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add currency_id column
SET @exist_currency := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'currency_id');
SET @sql_currency := IF(@exist_currency = 0, 
    'ALTER TABLE jobs ADD COLUMN currency_id INT DEFAULT NULL AFTER location_id', 
    'SELECT "Column currency_id already exists"');
PREPARE stmt FROM @sql_currency;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- Add repost functionality columns
-- ----------------------------------------------------------------------------

-- Add original_job_id column (for tracking reposted jobs)
SET @exist_original := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'original_job_id');
SET @sql_original := IF(@exist_original = 0, 
    'ALTER TABLE jobs ADD COLUMN original_job_id INT DEFAULT NULL AFTER currency_id', 
    'SELECT "Column original_job_id already exists"');
PREPARE stmt FROM @sql_original;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add repost_count column
SET @exist_repost_count := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'repost_count');
SET @sql_repost_count := IF(@exist_repost_count = 0, 
    'ALTER TABLE jobs ADD COLUMN repost_count INT DEFAULT 0 AFTER original_job_id', 
    'SELECT "Column repost_count already exists"');
PREPARE stmt FROM @sql_repost_count;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add last_reposted_at column
SET @exist_last_reposted := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'last_reposted_at');
SET @sql_last_reposted := IF(@exist_last_reposted = 0, 
    'ALTER TABLE jobs ADD COLUMN last_reposted_at TIMESTAMP NULL AFTER repost_count', 
    'SELECT "Column last_reposted_at already exists"');
PREPARE stmt FROM @sql_last_reposted;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add last_reposted_by column
SET @exist_reposted_by := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'last_reposted_by');
SET @sql_reposted_by := IF(@exist_reposted_by = 0, 
    'ALTER TABLE jobs ADD COLUMN last_reposted_by INT DEFAULT NULL AFTER last_reposted_at', 
    'SELECT "Column last_reposted_by already exists"');
PREPARE stmt FROM @sql_reposted_by;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- Add indexes for new columns
-- ----------------------------------------------------------------------------

-- Add index for category_id
SET @exist_idx_category := (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_category');
SET @sql_idx_category := IF(@exist_idx_category = 0, 
    'ALTER TABLE jobs ADD INDEX idx_jobs_category (category_id)', 
    'SELECT "Index idx_jobs_category already exists"');
PREPARE stmt FROM @sql_idx_category;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for location_id
SET @exist_idx_location := (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_location');
SET @sql_idx_location := IF(@exist_idx_location = 0, 
    'ALTER TABLE jobs ADD INDEX idx_jobs_location (location_id)', 
    'SELECT "Index idx_jobs_location already exists"');
PREPARE stmt FROM @sql_idx_location;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for currency_id
SET @exist_idx_currency := (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_currency');
SET @sql_idx_currency := IF(@exist_idx_currency = 0, 
    'ALTER TABLE jobs ADD INDEX idx_jobs_currency (currency_id)', 
    'SELECT "Index idx_jobs_currency already exists"');
PREPARE stmt FROM @sql_idx_currency;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for original_job_id
SET @exist_idx_original := (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_original');
SET @sql_idx_original := IF(@exist_idx_original = 0, 
    'ALTER TABLE jobs ADD INDEX idx_jobs_original (original_job_id)', 
    'SELECT "Index idx_jobs_original already exists"');
PREPARE stmt FROM @sql_idx_original;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for repost_count
SET @exist_idx_repost := (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND INDEX_NAME = 'idx_jobs_repost_count');
SET @sql_idx_repost := IF(@exist_idx_repost = 0, 
    'ALTER TABLE jobs ADD INDEX idx_jobs_repost_count (repost_count)', 
    'SELECT "Index idx_jobs_repost_count already exists"');
PREPARE stmt FROM @sql_idx_repost;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- Add foreign key constraints
-- Note: Using SET FOREIGN_KEY_CHECKS to handle potential existing constraints
-- ----------------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- Add foreign key for category_id
SET @exist_fk_category := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND CONSTRAINT_NAME = 'fk_jobs_category');
SET @sql_fk_category := IF(@exist_fk_category = 0, 
    'ALTER TABLE jobs ADD CONSTRAINT fk_jobs_category FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE SET NULL', 
    'SELECT "Foreign key fk_jobs_category already exists"');
PREPARE stmt FROM @sql_fk_category;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for location_id
SET @exist_fk_location := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND CONSTRAINT_NAME = 'fk_jobs_location');
SET @sql_fk_location := IF(@exist_fk_location = 0, 
    'ALTER TABLE jobs ADD CONSTRAINT fk_jobs_location FOREIGN KEY (location_id) REFERENCES job_locations(id) ON DELETE SET NULL', 
    'SELECT "Foreign key fk_jobs_location already exists"');
PREPARE stmt FROM @sql_fk_location;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for currency_id
SET @exist_fk_currency := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND CONSTRAINT_NAME = 'fk_jobs_currency');
SET @sql_fk_currency := IF(@exist_fk_currency = 0, 
    'ALTER TABLE jobs ADD CONSTRAINT fk_jobs_currency FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE SET NULL', 
    'SELECT "Foreign key fk_jobs_currency already exists"');
PREPARE stmt FROM @sql_fk_currency;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for original_job_id (self-referencing)
SET @exist_fk_original := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND CONSTRAINT_NAME = 'fk_jobs_original');
SET @sql_fk_original := IF(@exist_fk_original = 0, 
    'ALTER TABLE jobs ADD CONSTRAINT fk_jobs_original FOREIGN KEY (original_job_id) REFERENCES jobs(id) ON DELETE SET NULL', 
    'SELECT "Foreign key fk_jobs_original already exists"');
PREPARE stmt FROM @sql_fk_original;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SECTION 3: SEED DATA
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Seed: Currencies
-- Default currency is KES (Kenyan Shilling)
-- ----------------------------------------------------------------------------
INSERT INTO currencies (code, name, symbol, exchange_rate, is_default, is_active, display_order) VALUES
('KES', 'Kenyan Shilling', 'KSh', 1.000000, 1, 1, 1),
('USD', 'US Dollar', '$', 153.500000, 0, 1, 2),
('EUR', 'Euro', '€', 167.250000, 0, 1, 3),
('GBP', 'British Pound', '£', 194.750000, 0, 1, 4)
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    symbol = VALUES(symbol),
    exchange_rate = VALUES(exchange_rate),
    is_default = VALUES(is_default),
    is_active = VALUES(is_active),
    display_order = VALUES(display_order);

-- ----------------------------------------------------------------------------
-- Seed: Job Categories
-- Common job categories for the Kenyan market
-- ----------------------------------------------------------------------------
INSERT INTO job_categories (name, slug, description, color, icon, display_order, is_active) VALUES
('Engineering', 'engineering', 'Engineering and technical roles including software, mechanical, and civil engineering', '#e74c3c', 'fa-cogs', 1, 1),
('Marketing', 'marketing', 'Marketing, advertising, and communications positions', '#3498db', 'fa-bullhorn', 2, 1),
('Sales', 'sales', 'Sales, business development, and account management roles', '#27ae60', 'fa-chart-line', 3, 1),
('Finance', 'finance', 'Finance, accounting, and financial planning positions', '#f39c12', 'fa-money-bill-wave', 4, 1),
('Human Resources', 'human-resources', 'HR, recruitment, and people management roles', '#9b59b6', 'fa-users', 5, 1),
('Operations', 'operations', 'Operations, logistics, and supply chain management', '#1abc9c', 'fa-truck', 6, 1),
('Customer Service', 'customer-service', 'Customer support, success, and experience roles', '#e67e22', 'fa-headset', 7, 1),
('IT', 'it', 'Information technology, software development, and IT support', '#2c3e50', 'fa-laptop-code', 8, 1),
('Legal', 'legal', 'Legal, compliance, and regulatory affairs positions', '#8e44ad', 'fa-balance-scale', 9, 1),
('Administration', 'administration', 'Administrative, executive assistant, and office management roles', '#16a085', 'fa-clipboard', 10, 1)
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    color = VALUES(color),
    icon = VALUES(icon),
    display_order = VALUES(display_order),
    is_active = VALUES(is_active);

-- ----------------------------------------------------------------------------
-- Seed: Job Locations
-- Major Kenyan cities and remote work option
-- ----------------------------------------------------------------------------
INSERT INTO job_locations (name, slug, region, country, display_order, is_active, is_remote) VALUES
('Nairobi', 'nairobi', 'Nairobi County', 'Kenya', 1, 1, 0),
('Mombasa', 'mombasa', 'Coast Region', 'Kenya', 2, 1, 0),
('Kisumu', 'kisumu', 'Nyanza Region', 'Kenya', 3, 1, 0),
('Nakuru', 'nakuru', 'Rift Valley Region', 'Kenya', 4, 1, 0),
('Remote', 'remote', 'Work from Anywhere', 'Kenya', 5, 1, 1)
ON DUPLICATE KEY UPDATE 
    region = VALUES(region),
    country = VALUES(country),
    display_order = VALUES(display_order),
    is_active = VALUES(is_active),
    is_remote = VALUES(is_remote);

-- ============================================================================
-- SECTION 4: VERIFICATION QUERIES (Optional - for testing)
-- ============================================================================

-- Uncomment the following lines to verify the migration:
-- SELECT * FROM currencies;
-- SELECT * FROM job_categories;
-- SELECT * FROM job_locations;
-- DESCRIBE jobs;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
