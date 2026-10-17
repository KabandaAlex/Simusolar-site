-- Impact Page: content, environmental impact items, journey milestones
-- Reuse: impact_numbers (stats), testimonials, office_locations
USE tulima_solar;

-- Key-value content for hero, section titles, CTAs, community bullets
CREATE TABLE IF NOT EXISTS impact_page_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Environmental impact items (Carbon Reduction, Water Conservation, etc.)
CREATE TABLE IF NOT EXISTS impact_environmental (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journey / milestones timeline
CREATE TABLE IF NOT EXISTS impact_journey (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year VARCHAR(50) NOT NULL DEFAULT '',
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed impact_page_content (from live site)
INSERT INTO impact_page_content (`key`, value) VALUES
('hero_heading', 'Transforming Farming and Livelihoods Across Africa'),
('hero_subtitle', 'Through solar power, we\'ve created lasting positive change in communities, economies, and the environment.'),
('hero_cta_text', 'View Statistics'),
('hero_cta_url', '#impact-numbers'),
('hero_cta_secondary_text', 'Success Stories'),
('hero_cta_secondary_url', '#testimonials'),
('stats_heading', 'Our Impact in Numbers'),
('stats_subtitle', 'Since 2019, we\'ve made a significant difference in the lives of African farmers'),
('testimonials_heading', 'What Our Clients Say'),
('testimonials_subtitle', 'Hear from farmers and agricultural professionals who trust Tulima Solar for their sustainable energy solutions'),
('join_heading', 'Join Our Success Stories'),
('join_subtitle', 'Be part of the transformation. Start your journey with solar-powered farming today.'),
('join_primary_text', 'Get Started'),
('join_primary_url', '/contact'),
('join_secondary_text', 'Solutions'),
('join_secondary_url', '/products'),
('environmental_heading', 'Environmental Impact'),
('environmental_subtitle', 'Our solar solutions are helping protect the environment while improving livelihoods'),
('offices_heading', 'Our Office Locations'),
('offices_subtitle', 'Find our branches and get directions to any of our offices'),
('offices_intro', 'With regional offices across Uganda and East Africa, we bring solar solutions and after-sales support closer to farmers. Our presence on the ground helps us understand local needs, deliver installations, and provide training and maintenance—demonstrating our commitment to lasting impact in the communities we serve.'),
('community_heading', 'Community Impact'),
('community_subtitle', 'How solar power is transforming communities across Africa'),
('economic_benefits', 'Increased farm productivity by up to 300%\nReduced operating costs by eliminating fuel expenses\nCreated new income opportunities through year-round farming\nImproved market access with consistent crop production'),
('social_benefits', 'Reduced manual labor, especially for women and children\nImproved food security and nutrition\nEnhanced education opportunities with increased income\nStrengthened community resilience to climate change'),
('journey_heading', 'Our Journey'),
('journey_subtitle', 'Key milestones in our mission to transform African agriculture'),
('cta_heading', 'Be Part of Our Impact'),
('cta_subtitle', 'Join thousands of farmers who have already transformed their lives with solar power.'),
('cta_primary_text', 'Get Started Today'),
('cta_primary_url', '/contact'),
('cta_secondary_text', 'View Our Solutions'),
('cta_secondary_url', '/products')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Seed impact_environmental
INSERT INTO impact_environmental (title, description, sort_order, is_active) VALUES
('Carbon Reduction', 'Reduced carbon emissions by replacing fuel-powered pumps with clean solar energy.', 10, 1),
('Water Conservation', 'Efficient irrigation systems that conserve water while maximizing crop yields.', 20, 1),
('Sustainable Farming', 'Promoting sustainable agricultural practices that protect soil and biodiversity.', 30, 1),
('Renewable Energy', 'Harnessing abundant African sunshine for clean, renewable energy solutions.', 40, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), sort_order = VALUES(sort_order);

-- Seed impact_journey
INSERT INTO impact_journey (year, title, description, sort_order, is_active) VALUES
('2019', 'Company Founded', 'Tulima Solar was established with a mission to empower African farmers with solar solutions.', 10, 1),
('2020', 'First 100 Farmers', 'Reached our first milestone of 100 farmers using solar-powered irrigation systems.', 20, 1),
('2021', 'Regional Expansion', 'Opened offices in Lira and Obongi to serve farmers across Uganda.', 30, 1),
('2022', '1000+ Farmers', 'Celebrated reaching over 1000 farmers with solar-powered solutions.', 40, 1),
('2023', '5000+ Farmers', 'Reached 5000+ farmers and expanded product range to include processing machines.', 50, 1),
('2024', 'Future Vision', 'Continuing to expand and innovate to serve more farmers across Africa.', 60, 1);
