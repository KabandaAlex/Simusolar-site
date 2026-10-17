-- Section items: one row per card/step so each can be edited separately in Admin
USE tulima_solar;

CREATE TABLE IF NOT EXISTS page_section_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL DEFAULT '',
    content TEXT,
    content_extra TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES page_sections(id) ON DELETE CASCADE,
    KEY idx_section_sort (section_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
