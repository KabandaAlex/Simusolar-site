-- Media library table (WordPress-style attachments)
-- Run once to add the media table.

USE tulima_solar;

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
