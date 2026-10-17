-- Tulima Solar - Database Schema
-- Run this once to create the database and tables.

CREATE DATABASE IF NOT EXISTS tulima_solar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tulima_solar;

-- Admin users
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Site settings (key-value for global content)
CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    label VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Pages (one row per page slug; content in sections; hero config for each page)
CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    meta_description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    hero_title VARCHAR(255) NULL,
    hero_subtitle TEXT NULL,
    hero_cta_text VARCHAR(120) NULL,
    hero_cta_url VARCHAR(500) NULL,
    hero_cta_secondary_text VARCHAR(120) NULL,
    hero_cta_secondary_url VARCHAR(500) NULL,
    hero_video_path VARCHAR(500) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Page sections (flexible content blocks per page)
CREATE TABLE IF NOT EXISTS page_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    section_key VARCHAR(100) NOT NULL,
    title VARCHAR(255),
    content TEXT,
    content_extra TEXT,
    image_path VARCHAR(500),
    sort_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    UNIQUE KEY uk_page_section (page_id, section_key)
) ENGINE=InnoDB;

-- Products / services (for Products page)
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    short_description TEXT,
    description TEXT,
    image_path VARCHAR(500),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Benefits / features (reusable list items)
CREATE TABLE IF NOT EXISTS benefits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    icon_or_image VARCHAR(500),
    sort_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Contact info (address, phone, email, WhatsApp, etc.)
CREATE TABLE IF NOT EXISTS contact_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(80) NOT NULL UNIQUE,
    value VARCHAR(500),
    label VARCHAR(255),
    sort_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Media library (images and videos; metadata for alt/title/caption)
CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    type ENUM('image','video') NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    alt_text VARCHAR(500) DEFAULT NULL,
    caption TEXT DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_file_path (file_path(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: username 'admin', password 'password' (change in production!)
INSERT INTO admins (username, password_hash, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tulimasolar.com')
ON DUPLICATE KEY UPDATE username = username;
