-- Home page sections from test.tulimasolar.com (run after schema + main seed)
USE tulima_solar;

-- Update home page hero to match test site
UPDATE pages SET
  hero_title = 'Solar Productive Systems',
  hero_subtitle = 'Empowering economies across East Africa with cutting edge solar powered productive equipment.',
  hero_cta_text = 'Get Started Today',
  hero_cta_url = 'index.php?page=contact',
  hero_cta_secondary_text = 'Watch Demo',
  hero_cta_secondary_url = ''
WHERE slug = 'home';

SET @home_id = (SELECT id FROM pages WHERE slug = 'home' LIMIT 1);

-- Remove old home-only section keys so we can replace with new structure (optional: keeps old keys if you want to keep them)
DELETE FROM page_sections WHERE page_id = @home_id AND section_key IN ('hero_title', 'full_service', 'support', 'benefits_irrigation', 'benefits_switch');

-- Featured products (title + subtitle; products come from products table)
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'featured_products', 'Solar Solutions for Your Success', 'Discover our most popular and reliable solar-powered equipment', NULL, 10)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Transform your farm: intro + 4 benefit cards (content_extra: one line per benefit "Title|Description")
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'transform_farm', 'Transform Your Farm with Solar Power', 'Experience the revolutionary benefits of sustainable, cost-effective solar solutions that enhance productivity while protecting the environment for future generations.', 'Significant Cost Savings|Eliminate fuel costs and reduce operational expenses by up to 80% with our efficient solar solutions
Environmental Sustainability|Zero emissions and sustainable energy solutions for eco-friendly farming practices
Unmatched Reliability|Consistent performance with minimal maintenance requirements and long-term durability
Increased Productivity|Year-round irrigation capabilities leading to higher crop yields and improved farm efficiency', 20)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- How it works: 4 steps (content_extra: blocks separated by --- ; each block: Line1=Step title, Line2=subtitle, rest=bullets)
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'how_it_works', 'How It Works - Simple Steps to Solar Success', 'From initial consultation to ongoing support, we make your transition to solar power seamless and stress-free', 'Free Consultation|Contact us for a no-obligation consultation. Our experts will assess your needs, site conditions, and energy requirements to design the perfect solution.|Site Assessment|Energy Analysis|Custom Quote
---
System Design|Our engineering team creates a detailed system design tailored to your specific requirements, ensuring optimal performance and efficiency.|Technical Design|Component Selection|Performance Guarantee
---
Professional Installation|Our certified technicians handle the complete installation process, ensuring your system is properly set up and optimized for maximum performance.|Expert Installation|Quality Testing|Safety Compliance
---
Ongoing Support|Enjoy peace of mind with our comprehensive support services, including maintenance, monitoring, and technical assistance during our business hours and online support when needed.|8am-5pm Mon-Fri, 9am-2pm Sat|Online Support Available|Regular Maintenance', 30)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- Impact numbers (content_extra: label|value per line)
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'impact_numbers', 'Our Impact in Numbers', 'Since 2019, we''ve made a significant difference in the lives of African farmers', 'Farmers Empowered|0|Lives transformed through solar power
Acres Irrigated|0|Land brought to life sustainably
Income Increase|0%|Average boost in farmer earnings
Regional Offices|0|Serving communities across Africa', 40)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- Comprehensive solar solutions: 4 cards (content_extra: blocks separated by --- ; each block: title, description, then bullets)
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'solar_solutions', 'Comprehensive Solar Solutions for Modern Agriculture', 'From water pumping to processing equipment, we provide end-to-end solar solutions designed specifically for African agricultural needs', 'Solar Water Pumps|Professional-grade solar water pumping systems for irrigation, livestock, and aquaculture applications.|High efficiency pumps|Smart monitoring|Low maintenance
---
Solar Panels|High-efficiency solar panel systems with professional mounting solutions optimized for African climate.|Premium quality|Weather resistant|25-year warranty
---
Control Systems|Advanced monitoring and control systems for automated operation with real-time data tracking.|Remote monitoring|Mobile app control|Performance analytics
---
Processing Equipment|Solar-powered agricultural processing machines for value addition and post-harvest solutions.|Grinding machines|Drying systems|Milling equipment', 50)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- Applications (3 columns: title + bullets per block, --- separated)
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'applications', 'Applications for Every Need', 'Our solar solutions are designed for diverse agricultural applications across East Africa', 'Crop Irrigation|Drip irrigation systems|Sprinkler systems|Furrow irrigation|Greenhouse irrigation
---
Livestock Watering|Cattle watering systems|Poultry water supply|Fish farming|Automatic waterers
---
Domestic Use|Household water supply|Community water projects|School water systems|Emergency water supply', 60)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- Testimonials (content_extra: blocks --- separated; each block: quote (multi-line ok) then |Author|Role)
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'testimonials', 'What Our Clients Say', 'Hear from farmers and agricultural professionals who trust Tulima Solar for their sustainable energy solutions', 'Opio Tonny, the owner of Otobai Investment Co. Ltd Tree Nursery in Lira City, Northern Uganda, found a solution to his irrigation challenges with a solar water pump from Tulima Solar. Referred by another satisfied entrepreneur, he invested in the technology to replace a manual watering can. The solar pump provided a consistent and efficient water supply, reducing physical labor for Opio and his workers. This improved irrigation led to more effective watering and supported the expansion of his one-acre tree nursery. Opio credits Tulima Solar''s technology and support for empowering his business growth and helping him combat issues like climate change, poverty, and unemployment. He now considers them a key partner in his business''s future.|Opio Tonny|Farmer
---
Rebecca Wakesa, a nursery and vegetable grower in Nashibiswa Village, Mbale, aimed to improve her one-acre garden. She sought to reduce high costs, save energy, and increase her crop yield and earnings. Following a sales officer''s guidance, she installed a solar water pump from Tulima Solar. This new irrigation system drastically cut her previous expenses, allowing her to invest the savings back into her business and expand her crop variety. Rebecca highly recommends the solar water pump to other farmers, highlighting its energy efficiency and cost-effectiveness as key benefits.|Rebecca Wakesa|Farmer', 70)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- Partners
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'partners', 'Our Partners', 'Trusted by leading organizations and institutions across East Africa', NULL, 80)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Office locations
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'office_locations', 'Our Office Locations', 'Find our branches and get directions to any of our offices', NULL, 90)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Final CTA
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'cta_final', 'Ready to Transform Your Farm with Solar?', 'Join thousands of successful farmers across East Africa who have already revolutionized their agricultural operations with our professional solar solutions. Get your free consultation and personalized quote today.', 'Free Consultation|Custom Solar Solutions|Installation Support|Ongoing Maintenance', 100)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- How it works: one row per step (editable one-by-one in Admin → Section items). Insert only if section has no items yet.
SET @how_it_works_section_id = (SELECT id FROM page_sections WHERE page_id = @home_id AND section_key = 'how_it_works' LIMIT 1);
INSERT INTO page_section_items (section_id, sort_order, title, content, content_extra)
SELECT t.section_id, t.sort_order, t.title, t.content, t.content_extra FROM (
  SELECT @how_it_works_section_id AS section_id, 10 AS sort_order, 'Free Consultation' AS title, 'Contact us for a no-obligation consultation. Our experts will assess your needs, site conditions, and energy requirements to design the perfect solution.' AS content, 'Site Assessment\nEnergy Analysis\nCustom Quote' AS content_extra
  UNION ALL SELECT @how_it_works_section_id, 20, 'System Design', 'Our engineering team creates a detailed system design tailored to your specific requirements, ensuring optimal performance and efficiency.', 'Technical Design\nComponent Selection\nPerformance Guarantee'
  UNION ALL SELECT @how_it_works_section_id, 30, 'Professional Installation', 'Our certified technicians handle the complete installation process, ensuring your system is properly set up and optimized for maximum performance.', 'Expert Installation\nQuality Testing\nSafety Compliance'
  UNION ALL SELECT @how_it_works_section_id, 40, 'Ongoing Support', 'Enjoy peace of mind with our comprehensive support services, including maintenance, monitoring, and technical assistance during our business hours and online support when needed.', '8am-5pm Mon-Fri, 9am-2pm Sat\nOnline Support Available\nRegular Maintenance'
) t
WHERE (SELECT COUNT(*) FROM page_section_items WHERE section_id = @how_it_works_section_id) = 0;

-- Transform your farm: one row per benefit card
SET @transform_farm_section_id = (SELECT id FROM page_sections WHERE page_id = @home_id AND section_key = 'transform_farm' LIMIT 1);
INSERT INTO page_section_items (section_id, sort_order, title, content, content_extra)
SELECT t.section_id, t.sort_order, t.title, t.content, t.content_extra FROM (
  SELECT @transform_farm_section_id AS section_id, 10 AS sort_order, 'Significant Cost Savings' AS title, 'Eliminate fuel costs and reduce operational expenses by up to 80% with our efficient solar solutions' AS content, '' AS content_extra
  UNION ALL SELECT @transform_farm_section_id, 20, 'Environmental Sustainability', 'Zero emissions and sustainable energy solutions for eco-friendly farming practices', ''
  UNION ALL SELECT @transform_farm_section_id, 30, 'Unmatched Reliability', 'Consistent performance with minimal maintenance requirements and long-term durability', ''
  UNION ALL SELECT @transform_farm_section_id, 40, 'Increased Productivity', 'Year-round irrigation capabilities leading to higher crop yields and improved farm efficiency', ''
) t
WHERE (SELECT COUNT(*) FROM page_section_items WHERE section_id = @transform_farm_section_id) = 0;

-- Impact numbers: one row per stat (title=label, content=value, content_extra=sublabel)
SET @impact_numbers_section_id = (SELECT id FROM page_sections WHERE page_id = @home_id AND section_key = 'impact_numbers' LIMIT 1);
INSERT INTO page_section_items (section_id, sort_order, title, content, content_extra)
SELECT t.section_id, t.sort_order, t.title, t.content, t.content_extra FROM (
  SELECT @impact_numbers_section_id AS section_id, 10 AS sort_order, 'Farmers Empowered' AS title, '0' AS content, 'Lives transformed through solar power' AS content_extra
  UNION ALL SELECT @impact_numbers_section_id, 20, 'Acres Irrigated', '0', 'Land brought to life sustainably'
  UNION ALL SELECT @impact_numbers_section_id, 30, 'Income Increase', '0%', 'Average boost in farmer earnings'
  UNION ALL SELECT @impact_numbers_section_id, 40, 'Regional Offices', '0', 'Serving communities across Africa'
) t
WHERE (SELECT COUNT(*) FROM page_section_items WHERE section_id = @impact_numbers_section_id) = 0;

-- Solar solutions: one row per solution card
SET @solar_solutions_section_id = (SELECT id FROM page_sections WHERE page_id = @home_id AND section_key = 'solar_solutions' LIMIT 1);
INSERT INTO page_section_items (section_id, sort_order, title, content, content_extra)
SELECT t.section_id, t.sort_order, t.title, t.content, t.content_extra FROM (
  SELECT @solar_solutions_section_id AS section_id, 10 AS sort_order, 'Solar Water Pumps' AS title, 'Professional-grade solar water pumping systems for irrigation, livestock, and aquaculture applications.' AS content, 'High efficiency pumps\nSmart monitoring\nLow maintenance' AS content_extra
  UNION ALL SELECT @solar_solutions_section_id, 20, 'Solar Panels', 'High-efficiency solar panel systems with professional mounting solutions optimized for African climate.', 'Premium quality\nWeather resistant\n25-year warranty'
  UNION ALL SELECT @solar_solutions_section_id, 30, 'Control Systems', 'Advanced monitoring and control systems for automated operation with real-time data tracking.', 'Remote monitoring\nMobile app control\nPerformance analytics'
  UNION ALL SELECT @solar_solutions_section_id, 40, 'Processing Equipment', 'Solar-powered agricultural processing machines for value addition and post-harvest solutions.', 'Grinding machines\nDrying systems\nMilling equipment'
) t
WHERE (SELECT COUNT(*) FROM page_section_items WHERE section_id = @solar_solutions_section_id) = 0;
