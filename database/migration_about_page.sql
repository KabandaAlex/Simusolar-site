-- About page: key-value content (hero, story, section titles, CTA) and Our Values (separate table)
-- Run after migration_about_mission_vision.sql; office_locations and team_members already exist.
USE tulima_solar;

-- Key-value content for hero, story, values, offices, team, CTA (aligned with live Who We Are)
CREATE TABLE IF NOT EXISTS about_page_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Our Values: admin-managed list (title + description), displayed in Values section
CREATE TABLE IF NOT EXISTS about_values (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed about_page_content from live https://test.tulimasolar.com/who-we-are/
INSERT INTO about_page_content (`key`, value) VALUES
('hero_heading', 'Empowering African Farmers with Sustainable Solar Solutions'),
('hero_subtitle', 'Since 2019, we\'ve been transforming agriculture across Africa through innovative solar-powered irrigation systems and processing machines.'),
('hero_cta_primary_text', 'Our Story'),
('hero_cta_primary_url', '#our-story'),
('hero_cta_secondary_text', 'Meet Our Team'),
('hero_cta_secondary_url', '#our-team'),
('story_heading', 'Our Story'),
('story_subtitle', 'From humble beginnings to becoming a leading solar solutions provider in Africa'),
('story_paragraph_1', 'Tulima Solar was founded in 2019 with a clear mission: to empower African farmers with affordable, sustainable solar-powered irrigation systems and agricultural processing machines.'),
('story_paragraph_2', 'We recognized that traditional fuel-powered farming equipment was not only expensive to operate but also unreliable and environmentally harmful. Our solution was to harness the abundant African sunshine to provide clean, reliable, and cost-effective farming solutions.'),
('story_paragraph_3', 'Today, we\'ve helped thousands of farmers across Uganda and beyond to increase their productivity, reduce costs, and farm more sustainably. Click the button below to see our impact in numbers and discover how we\'re transforming agriculture across Africa.'),
('story_cta_text', 'See Our Impact'),
('story_cta_url', '/our-impact'),
('mission_vision_heading', 'Mission & Vision'),
('mission_vision_subtitle', 'Driving sustainable change in African agriculture'),
('values_heading', 'Our Values'),
('values_subtitle', 'The principles that guide everything we do'),
('offices_heading', 'Our Office Locations'),
('offices_subtitle', 'Visit us at any of our regional offices across Uganda'),
('directions_heading', 'Get Directions'),
('directions_subtitle', 'Enter your starting location to get directions to our office'),
('directions_form_heading', 'Find Your Route'),
('directions_form_subtitle', 'Get personalized directions to any of our office locations'),
('team_heading', 'Our Staff'),
('team_subtitle', 'Our dedicated team of professionals working to serve you'),
('directors_heading', 'Board of Directors'),
('directors_subtitle', 'Our leadership team guiding the company\'s vision and strategy'),
('advisors_heading', 'Our Advisors'),
('advisors_subtitle', 'Expert advisors providing strategic guidance and industry insights'),
('cta_heading', 'Join Our Mission to Transform African Agriculture'),
('cta_subtitle', 'Be part of the solar farming revolution and help us empower thousands more farmers across Africa with sustainable, cost-effective solar solutions. Together, we can create a brighter future for African agriculture.'),
('cta_primary_text', 'Get Started Today'),
('cta_primary_url', '/contact'),
('cta_secondary_text', 'See Our Impact'),
('cta_secondary_url', '/our-impact')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Seed about_values from live (Customer-Centric, Communication, etc.). Run once; admin can edit after.
INSERT IGNORE INTO about_values (id, title, description, sort_order, is_active) VALUES
(1, 'Customer-Centric', 'We focus on customer needs and provide positive customer experience.', 1, 1),
(2, 'Communication', 'We actively listen to our customers and provide clear and concise communication.', 2, 1),
(3, 'Data-Driven', 'We use data to make informed decisions and improve our products and services.', 3, 1),
(4, 'Collaboration', 'We work together to achieve our goals and provide better solutions for our customers.', 4, 1),
(5, 'Commitment', 'We are committed to our customers, employees, and the community.', 5, 1);
