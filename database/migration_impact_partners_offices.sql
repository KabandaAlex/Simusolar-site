-- Impact Numbers, Partners, and Office Locations – dedicated tables for home page sections
-- Section title/subtitle stay in page_sections (impact_numbers, partners, office_locations); data comes from these tables.
USE tulima_solar;

-- Impact Numbers: one row per stat (label, value, sublabel) – e.g. "Farmers Empowered", "0", "Lives transformed..."
CREATE TABLE IF NOT EXISTS impact_numbers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL DEFAULT '',
    value VARCHAR(100) NOT NULL DEFAULT '',
    sublabel VARCHAR(500) NOT NULL DEFAULT '',
    icon VARCHAR(50) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partners: one row per partner (name, optional logo, optional link)
CREATE TABLE IF NOT EXISTS partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    image_path VARCHAR(500) NULL DEFAULT NULL,
    url VARCHAR(500) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Office Locations: one row per office (name, address, phone, email, region, optional map link)
CREATE TABLE IF NOT EXISTS office_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    address TEXT,
    phone VARCHAR(100) NULL DEFAULT NULL,
    email VARCHAR(255) NULL DEFAULT NULL,
    region_or_city VARCHAR(255) NULL DEFAULT NULL,
    map_url VARCHAR(500) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
