-- Tulima Solar - Team Members Seed Data
-- Run this after migration_team_members.sql to populate sample team member data
-- 
-- Usage:
--   Via MySQL CLI: mysql -u root -p tulima_solar < database/seed_team_members.sql
--   Via phpMyAdmin: Copy/paste this file's content into the SQL tab
--   Via WAMP: Use MySQL console or phpMyAdmin from http://localhost/phpmyadmin
--
-- Note: This will add sample data. Run TRUNCATE first if you need to reset.
-- Warning: TRUNCATE will delete ALL existing team members!

USE tulima_solar;

-- Clear existing data (optional - uncomment if needed)
-- TRUNCATE TABLE team_members;

-- =====================================================
-- DIRECTORS (Board Members & C-Level Executives)
-- =====================================================
INSERT INTO team_members (name, job_title, biography, profile_image, category, display_order, is_active) VALUES
('David Mukasa', 'Chief Executive Officer', 'David is a visionary leader with over 15 years of experience in the renewable energy sector across East Africa. He founded Tulima Solar with a mission to bring sustainable energy solutions to smallholder farmers.', 'media/team_members/David-800x800.png', 'director', 1, 1),

('Eric Nkata', 'Chief Operations Officer', 'Eric oversees all operational aspects of Tulima Solar, ensuring efficient delivery of solar solutions to rural communities. His background in logistics and supply chain management has been instrumental in scaling operations.', 'media/team_members/Eric-1_1.jpg', 'director', 2, 1),

('Canary Bogere', 'Chief Technology Officer', 'Canary leads the technical innovation at Tulima Solar, developing cutting-edge solar pump systems optimized for agricultural use. He holds multiple patents in renewable energy technology.', 'media/team_members/Canary-Bogere-150x150.png', 'director', 3, 1),

('Byron Sserubiri', 'Chief Financial Officer', 'Byron brings extensive experience in impact investing and financial management. He previously worked with major development finance institutions and now ensures sustainable growth for Tulima Solar.', 'media/team_members/Byron-Sserubiri.jpg', 'director', 4, 1);

-- =====================================================
-- ADVISORS (Advisory Board & Consultants)
-- =====================================================
INSERT INTO team_members (name, job_title, biography, profile_image, category, display_order, is_active) VALUES
('Dr. Amara Okonkwo', 'Agricultural Development Advisor', 'Dr. Okonkwo is a renowned agricultural economist with expertise in smallholder farming systems. She advises Tulima Solar on market strategies and farmer engagement programs.', 'media/team_members/Image_Place_holder_1.jpg', 'advisor', 1, 1),

('James Peterson', 'Renewable Energy Policy Advisor', 'James has over 20 years of experience in energy policy across Sub-Saharan Africa. He guides Tulima Solar through regulatory frameworks and helps identify growth opportunities.', 'media/team_members/Image_Place_holder_1.jpg', 'advisor', 2, 1),

('Sarah Chen', 'Impact Investment Advisor', 'Sarah is a seasoned impact investor who has funded numerous clean energy startups. She provides strategic guidance on fundraising and impact measurement frameworks.', 'media/team_members/Image_Place_holder_1.jpg', 'advisor', 3, 1),

('Prof. Michael Ochieng', 'Technical Advisory Board Member', 'Professor Ochieng leads the Renewable Energy Research Center at Makerere University. He advises on product development and quality assurance standards.', 'media/team_members/Image_Place_holder_1.jpg', 'advisor', 4, 1),

('Angela Wambui', 'Gender & Social Inclusion Advisor', 'Angela specializes in gender mainstreaming in agricultural value chains. She ensures Tulima Solar products and programs are accessible to women farmers across the region.', 'media/team_members/ANGIOLO-LAVIZIANO-1_1.jpg', 'advisor', 5, 0);

-- =====================================================
-- STAFF (Regular Team Members)
-- =====================================================
INSERT INTO team_members (name, job_title, biography, profile_image, category, display_order, is_active) VALUES
('Grace Namugga', 'Sales Manager - Central Region', 'Grace leads the central region sales team, building relationships with farming cooperatives and individual customers. She has helped over 500 farmers adopt solar irrigation.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 1, 1),

('Robert Ssempala', 'Technical Support Specialist', 'Robert provides technical support and training to customers on solar pump installation and maintenance. His expertise ensures farmers get the most from their investment.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 2, 1),

('Florence Achieng', 'Customer Success Coordinator', 'Florence manages customer relationships and ensures satisfaction throughout the customer journey. She coordinates after-sales support and warranty services.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 3, 1),

('Daniel Kiprop', 'Field Technician - Northern Region', 'Daniel is responsible for installations and maintenance in Northern Uganda. His dedication to quality service has earned him recognition from farming communities.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 4, 1),

('Ruth Nakamya', 'Marketing & Communications Officer', 'Ruth develops marketing campaigns and manages communications across digital and traditional channels. She tells the stories of farmers transformed by solar energy.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 5, 1),

('Peter Okello', 'Logistics Coordinator', 'Peter manages inventory and distribution logistics, ensuring solar products reach even the most remote farming communities on time.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 6, 1),

('Esther Katushabe', 'Finance & Administration Officer', 'Esther handles day-to-day financial operations and administrative functions. Her attention to detail keeps the organization running smoothly.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 7, 1),

('Brian Mugisha', 'Data Analyst', 'Brian analyzes sales data and customer feedback to identify trends and opportunities. His insights drive strategic decision-making across the organization.', 'media/team_members/Image_Place_holder_1.jpg', 'staff', 8, 0);

-- =====================================================
-- Summary:
-- - Directors: 4 entries (all active)
-- - Advisors: 5 entries (4 active, 1 inactive for testing)
-- - Staff: 8 entries (7 active, 1 inactive for testing)
-- - Total: 17 team members
-- =====================================================
