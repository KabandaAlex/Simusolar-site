-- About page: Mission and Vision (admin-editable, displayed on About page)
USE tulima_solar;

CREATE TABLE IF NOT EXISTS about_mission_vision (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    block_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'mission | vision',
    title VARCHAR(255) NOT NULL DEFAULT '',
    content TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default mission and vision (matching current about page copy)
INSERT INTO about_mission_vision (block_key, title, content, sort_order) VALUES
('mission', 'Our mission', 'To provide affordable, reliable, and sustainable solar energy solutions to homes and businesses across Africa, empowering communities to thrive with clean energy.', 1),
('vision', 'Our vision', 'A future where every African community has access to clean, affordable energy — where sustainable power drives economic growth, education, and quality of life.', 2)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);
