-- Tulima Solar - Seed data (run after schema.sql)
USE tulima_solar;

-- Site settings
INSERT INTO site_settings (`key`, value, label) VALUES
('site_name', 'Tulima Solar', 'Site name'),
('tagline', 'Financed Solutions for Agriculture', 'Tagline'),
('footer_text', 'Tulima Solar – Solar-Powered Equipment for Agriculture.', 'Footer text'),
('whatsapp_number', '', 'WhatsApp number (with country code)'),
('whatsapp_message', 'Hello, I would like to know more about Tulima Solar.', 'Default WhatsApp message')
ON DUPLICATE KEY UPDATE value = VALUES(value);

INSERT INTO site_settings (`key`, value, label) VALUES
('footer_description', 'Empowering African farmers with sustainable solar solutions since 2019. We provide professional, reliable, and cost-effective solar-powered equipment for modern agriculture.', 'Footer long description'),
('business_hours', 'Mon - Fri: 8:00 AM - 6:00 PM', 'Business hours (footer)'),
('facebook_url', '', 'Facebook URL'),
('twitter_url', '', 'Twitter/X URL'),
('linkedin_url', '', 'LinkedIn URL'),
('instagram_url', '', 'Instagram URL'),
('youtube_url', '', 'YouTube URL')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Pages (hero columns optional; set via UPDATE below for new/updated installs)
INSERT INTO pages (slug, title, meta_description, sort_order, hero_title, hero_subtitle, hero_cta_text, hero_cta_url, hero_cta_secondary_text, hero_cta_secondary_url, hero_video_path) VALUES
('home', 'Home', 'Tulima Solar – Financed Solutions for Agriculture. Solar-powered irrigation and agricultural processing.', 1, 'Solar-Powered Equipment.', 'Tulima Solar makes farming productive with affordable, solar-powered irrigation systems and agricultural processing machines.', 'Contact Us', 'index.php?page=contact', 'WhatsApp us', '', 'static/videos/hero.mp4'),
('about', 'About Us', 'About Tulima Solar – customized solar irrigation and farming solutions since 2019.', 2, 'About Tulima Solar', 'Customized solar irrigation and farming solutions since 2019.', 'Contact Us', 'index.php?page=contact', '', '', NULL),
('products', 'Products & Solutions', 'Solar water pumps, irrigation systems, and agricultural equipment from Tulima Solar.', 3, 'Products & Solutions', 'Solar water pumps, irrigation systems, and agricultural equipment for your farm.', 'View Solar Pumps', 'index.php?page=solar-pumps', 'Contact Us', 'index.php?page=contact', NULL),
('solar-pumps', 'Solar Water Pumps', 'Solar pumps for irrigation, livestock, fish ponds, and water selling.', 4, 'Solar Water Pumps', 'Solar pumps for irrigation, livestock, fish ponds, and water selling.', 'Contact Us', 'index.php?page=contact', 'View Products', 'index.php?page=products', NULL),
('contact', 'Contact', 'Contact Tulima Solar – local offices, warranty, toll-free support.', 5, 'Contact Us', 'Get in touch for solar irrigation solutions, financing, and support.', 'WhatsApp us', '', 'Call or Email', 'index.php?page=contact', NULL),
('testimonials', 'Testimonials', 'What our clients say about Tulima Solar – farmer and partner testimonials.', 6, 'What Our Clients Say', 'Hear from farmers and agricultural professionals who trust Tulima Solar.', 'Contact Us', 'index.php?page=contact', '', '', NULL)
ON DUPLICATE KEY UPDATE
  title = VALUES(title), meta_description = VALUES(meta_description),
  hero_title = VALUES(hero_title), hero_subtitle = VALUES(hero_subtitle),
  hero_cta_text = VALUES(hero_cta_text), hero_cta_url = VALUES(hero_cta_url),
  hero_cta_secondary_text = VALUES(hero_cta_secondary_text), hero_cta_secondary_url = VALUES(hero_cta_secondary_url),
  hero_video_path = VALUES(hero_video_path);

-- Get page IDs for sections (use after pages exist)
SET @home_id = (SELECT id FROM pages WHERE slug = 'home' LIMIT 1);
SET @about_id = (SELECT id FROM pages WHERE slug = 'about' LIMIT 1);
SET @products_id = (SELECT id FROM pages WHERE slug = 'products' LIMIT 1);
SET @pumps_id = (SELECT id FROM pages WHERE slug = 'solar-pumps' LIMIT 1);
SET @contact_id = (SELECT id FROM pages WHERE slug = 'contact' LIMIT 1);

-- Home page sections
INSERT INTO page_sections (page_id, section_key, title, content, content_extra, sort_order) VALUES
(@home_id, 'hero_title', 'Solar-Powered Equipment.', 'Tulima Solar makes farming productive with affordable, solar-powered irrigation systems and agricultural processing machines. Our customized solutions enhance efficiency from irrigation to post-harvest. Since 2019, we have empowered farmers to grow, harvest, and process independently of rain and fuel.', NULL, 10),
(@home_id, 'full_service', 'Taking care of our customers with a full-service solution.', 'Customized Irrigation System Design to meet the needs of YOUR farm. Pricing includes everything needed for your SWP system: Wire and Pipes, Control system, Array mounting structure, Solar Panel Array, Solar Water Pump.', NULL, 20),
(@home_id, 'support', 'Support & Service', 'Local Offices in 3 Regions to support farmers. After-sales service backed by a 2-year Warranty. Toll-free Customer Service Line. Complete System Delivery and Installation Service.', NULL, 30),
(@home_id, 'benefits_irrigation', 'Benefits of Solar-Powered Irrigation', 'Irrigation can bring many benefits to farmers, increasing income by: Cultivate and harvest crops even during the dry season; Reduce labor requirements in carrying water; Increase the quality and yield of harvests; Reducing the risk of unpredictable rains with a reliable water supply.', NULL, 40),
(@home_id, 'benefits_switch', 'If you use a fuel-powered pump', 'If you are using a fuel-powered pump for irrigation now, switching to solar power means you: Eliminate downtime while awaiting engine repairs; Eliminate maintenance of fuel-powered irrigation equipment; Eliminate the hassle of getting fuel to your farm; Eliminate the cost of fuel used to run your pump.', NULL, 50)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), content_extra = VALUES(content_extra), sort_order = VALUES(sort_order);

-- About sections
INSERT INTO page_sections (page_id, section_key, title, content, sort_order) VALUES
(@about_id, 'about_intro', 'About Tulima Solar', 'Tulima Solar makes farming productive with affordable, solar-powered irrigation systems and agricultural processing machines. Our customized solutions enhance efficiency from irrigation to post-harvest. Since 2019, we have empowered farmers to grow, harvest, and process independently of rain and fuel.', 10),
(@about_id, 'mission', 'Our Mission', 'Taking care of our customers with a full-service solution. We provide customized irrigation system design to meet the needs of your farm.', 20)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Products / Solar Pumps sections
INSERT INTO page_sections (page_id, section_key, title, content, sort_order) VALUES
(@pumps_id, 'solar_pumps', 'Solar Pumps', 'Solar Pumps can be used for Irrigation in Agriculture to help increase productivity as they allow the user to irrigate without concern for the operating costs of electricity/fuel. This will help farmers to increase productivity and income. They can also help the Farmer to be able to cultivate throughout the year without waiting for rain or the weather.', 10),
(@pumps_id, 'animal_keeping', 'Animal Keeping', 'Solar Pumps increase the productivity of pastoralists as livestock depend on water and access to water is a key element in productive animal keeping. The use of solar power will help the pastoralist to water livestock even when they do not have the money to buy fuel, thus increasing productivity.', 20),
(@pumps_id, 'fish_fishing', 'Fish / Fishing', 'Our customers also use Solar Pumps to keep water in the Fish Ponds thus increasing the income for the profits from Aquaculture.', 30),
(@pumps_id, 'water_selling', 'Water Selling Business', 'Due to the Solar Pump being cost-effective our customers with these Pumps are also using them as an additional opportunity to increase income by selling water to the people around them in the community. This also helps to increase community well-being by making it easier to access water services in close proximity.', 40),
(@pumps_id, 'intro_system', 'A basic introduction to solar water pumping systems', 'A solar pumping system involves using solar energy to pump water from a source on the surface (lakes, rivers, streams, reservoirs) or from inside a well, to your fields to irrigate your crops. Solar water pumps are widely used to supply well water and upstream sources to livestock/crops. In Africa, the use of solar-powered water pumps has become increasingly popular in commercial farms due to the high cost of fuels for running pumps. The cost of pumping water using solar energy is very low compared to fuel-powered pumps.', 50),
(@pumps_id, 'components', 'Components of a solar water pumping system', 'Source of water: Wells or Boreholes (hand-dug or drilled); Surface water such as rivers, springs, ponds, lakes. Pump: Submersible pumps for wells or boreholes; Surface Pumps for surface water sources. The solar panels provide the power to run the pump.', 60),
(@pumps_id, 'surface_pumps', 'Surface pumps', 'Surface pumps operate from the water sources of rivers, ponds and lakes near the surface of the earth and are used primarily for moving water through the pipe. They have a suction and water exhaust component. They are often placed near a water source and draw water over it and push it to the desired location. They do not enter the water.', 70),
(@pumps_id, 'submersible_pumps', 'Submersible pumps', 'Submersible pumps are installed in the water within the well or borehole, and are submerged within the reservoir. Simusolar has several options depending on the size and characteristics of the farm: Kilimo Tosha, Mkulima 3, Kisima 3, Tija, Mto Plus, Kilimo Fursa, Chapakazi, Chemchem Plus, Kina Plus.', 80),
(@pumps_id, 'solar_panels', 'Solar Panels', 'Solar panels provide power to the pump. It is important the level of electrical power of the pump be consistent with the electrical power of the panels. Having a larger solar panel allows the pump to start earlier and add more power as the sun gets more intense.', 90),
(@pumps_id, 'considerations', 'Things to consider when using a solar water pump', 'Never use soil, sandy and grass waste water in running any pump. The water source should be clean and not less than 3 feet (1 meter deep) for surface pumps. The farmer should not try to open a pump system and try to make repairs but contact Simusolar customer service to get immediate help.', 100),
(@pumps_id, 'design', 'Design of solar water pumping system', 'In order to select the right pump for a particular field, consider: Source of water; Daily water needs (crops grown, size of farm, total farm size); Variation of elevation from water source to the highest point of field (TDH); Lowest depth of water in summer (if borehole); Distance from source to the farthest point; Distance from pump to solar panel or array.', 110),
(@pumps_id, 'advantages', 'Advantages of Simusolar Solar Pumps for Farmers', 'Environmental friendly. Easily accessed through 22-month financing. Increase production area. Reduce energy costs by using free sunlight as fuel. Low cost of maintenance and repairs (2 years warranty). Reduce labor costs and production time. Increase income by cultivating all seasons.', 120),
(@pumps_id, 'simusolar_services', 'Simusolar services', 'Business development services by connecting with other trusted stakeholders in the agricultural sector. Two-year equipment warranty. Customer service by calling a toll-free number. Free install of the solar pump system. Financing for solar pumps by monthly repayments for 22 months. Free farm survey, pump consultation and design.', 130),
(@pumps_id, 'financing', 'Financing Program', 'Digital systems that track system use and turn on following payments. Monthly payments by mobile money with no additional charges or deductions. Monthly payments for 22 months, or other financing terms available. Rapid credit approval system. Financing system with solar water pump as the only collateral.', 140)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Contact page sections
INSERT INTO page_sections (page_id, section_key, title, content, sort_order) VALUES
(@contact_id, 'contact_intro', 'Contact Us', 'Get in touch with Tulima Solar for solar irrigation solutions, financing, and support. Local offices in 3 regions, 2-year warranty, toll-free customer service.', 10)
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), sort_order = VALUES(sort_order);

-- Contact info
INSERT INTO contact_info (`key`, value, label, sort_order) VALUES
('phone', '+256 800 388 883', 'Toll-free / Phone', 1),
('email', 'info@tulimasolar.com', 'Email', 2),
('address', 'Plot 483, Kaduyu road, Ntinda - Kiwatule', 'Address', 3),
('whatsapp', '', 'WhatsApp', 4)
ON DUPLICATE KEY UPDATE value = VALUES(value), label = VALUES(label);

-- Products (pump types etc.)
INSERT INTO products (title, slug, short_description, description, sort_order) VALUES
('Surface Pumps', 'surface-pumps', 'For rivers, ponds and lakes near the surface.', 'Surface pumps operate from rivers, ponds and lakes. They draw water and push it to the desired location. Options: Kilimo Kwanza, Kilimo Bora, Kilimo Pesa, Faidika, Jiwezeshe, Masika.', 1),
('Submersible Pumps', 'submersible-pumps', 'For wells and boreholes.', 'Submersible pumps are installed inside the well or borehole. Options: Kilimo Tosha, Mkulima 3, Kisima 3, Tija, Mto Plus, Kilimo Fursa, Chapakazi, Chemchem Plus, Kina Plus.', 2),
('Solar Panels', 'solar-panels', 'Power for your pump.', 'Solar panels provide power to the pump. Panel capacity should match pump requirements for best performance.', 3)
ON DUPLICATE KEY UPDATE title = VALUES(title), short_description = VALUES(short_description), description = VALUES(description), sort_order = VALUES(sort_order);
