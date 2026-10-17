-- =============================================================================
-- Tulima Solar CMS - Blog System Seed Data
-- =============================================================================
-- Version: 1.0
-- Date: 2026-02-24
-- Description: Seed data for the blog system with agriculture/farming content
--
-- This seed file includes:
--   - Blog categories (agriculture-focused)
--   - Blog tags for content organization
--   - 10 blog posts with realistic agriculture content
--   - Post-tag relationships
--   - Sample comments from readers
--   - Sample post likes
--
-- Usage:
--   mysql -u root -p tulima_solar < seed_blogs.sql
--
-- Or run in MySQL client:
--   SOURCE /path/to/seed_blogs.sql;
--
-- =============================================================================

USE tulima_solar;

-- =============================================================================
-- SECTION 1: BLOG CATEGORIES
-- =============================================================================
-- Agriculture-focused categories for Tulima Solar blog
-- Note: These use INSERT IGNORE to avoid duplicates if already exists

INSERT IGNORE INTO blog_categories (id, name, slug, description, image_path, parent_id, sort_order, is_active) VALUES
-- Main categories
(1, 'Solar Energy', 'solar-energy', 'Articles about solar energy solutions and technologies for agriculture', 'media/blog_images/DJI_0663.JPG', NULL, 1, 1),
(2, 'Agriculture', 'agriculture', 'Farming tips, techniques, and agricultural innovations', 'media/blog_images/Untitled-design-7-1_1.png', NULL, 2, 1),
(3, 'Company News', 'company-news', 'Latest updates and news from Tulima Solar', NULL, NULL, 3, 1),
(4, 'Product Updates', 'product-updates', 'New product announcements and updates', NULL, NULL, 4, 1),
(5, 'Customer Stories', 'customer-stories', 'Success stories and testimonials from our customers', 'media/blog_images/IMG-20220803-WA0014-768x1024-1-576x400.jpg', NULL, 5, 1),
(6, 'Irrigation', 'irrigation', 'Modern irrigation techniques and systems', NULL, 2, 1, 1),
(7, 'Solar Pumps', 'solar-pumps', 'Solar-powered water pumping solutions', NULL, 1, 2, 1),
(8, 'Farming Tips', 'farming-tips', 'Practical advice for modern farmers', NULL, 2, 2, 1);

-- =============================================================================
-- SECTION 2: BLOG TAGS
-- =============================================================================
-- Tags for flexible content organization

INSERT IGNORE INTO blog_tags (id, name, slug, description) VALUES
(1, 'Tips', 'tips', 'Helpful tips and guides'),
(2, 'Tutorial', 'tutorial', 'Step-by-step tutorials'),
(3, 'News', 'news', 'News and announcements'),
(4, 'Innovation', 'innovation', 'Innovative technologies and solutions'),
(5, 'Sustainability', 'sustainability', 'Sustainable practices and environmental impact'),
(6, 'Africa', 'africa', 'Content related to African markets'),
(7, 'Uganda', 'uganda', 'Content specific to Uganda'),
(8, 'Case Study', 'case-study', 'In-depth case studies'),
(9, 'How-To', 'how-to', 'How-to guides and instructions'),
(10, 'Solar Panels', 'solar-panels', 'Solar panel technology and installation'),
(11, 'Water Management', 'water-management', 'Water conservation and management'),
(12, 'Dry Season', 'dry-season', 'Farming during dry seasons'),
(13, 'Financing', 'financing', 'Financing options and payment plans'),
(14, 'Success Story', 'success-story', 'Customer success stories'),
(15, 'Maintenance', 'maintenance', 'Equipment maintenance and care');

-- =============================================================================
-- SECTION 3: BLOG POSTS
-- =============================================================================
-- 10 blog posts with realistic agriculture/farming content
-- Using admin user ID 1 as the default author (assumes admin exists)

INSERT INTO blog_posts (id, title, slug, content, excerpt, category_id, meta_title, meta_description, featured_image_path, featured_image_alt, status, publish_at, published_at, view_count, like_count, comment_count, created_by, updated_by, created_at, updated_at, deleted_at) VALUES
-- Post 1: Published - Solar Irrigation Guide
(1, 
'Complete Guide to Solar-Powered Irrigation Systems',
'complete-guide-solar-powered-irrigation-systems',
'<p>Solar-powered irrigation is revolutionizing agriculture across Africa. With abundant sunlight and the need for reliable water sources, solar pumps offer an ideal solution for farmers looking to increase productivity while reducing operational costs.</p>

<h2>Why Choose Solar Irrigation?</h2>
<p>Traditional irrigation methods often rely on diesel or petrol pumps, which come with significant ongoing costs:</p>
<ul>
    <li>Fuel expenses that fluctuate with market prices</li>
    <li>Maintenance and repair costs for engines</li>
    <li>Environmental impact from fossil fuel emissions</li>
    <li>Downtime when equipment fails</li>
</ul>

<p>Solar irrigation systems eliminate these concerns by harnessing free energy from the sun. Once installed, the operating costs are minimal, and with proper maintenance, solar panels can last 25 years or more.</p>

<h2>Components of a Solar Irrigation System</h2>
<p>A typical solar irrigation system consists of:</p>
<ol>
    <li><strong>Solar Panels:</strong> Convert sunlight into electrical energy</li>
    <li><strong>Pump Controller:</strong> Manages power flow to the pump</li>
    <li><strong>Water Pump:</strong> Moves water from source to fields</li>
    <li><strong>Piping System:</strong> Delivers water to crops</li>
    <li><strong>Storage Tanks:</strong> Optional but recommended for water storage</li>
</ol>

<h2>Getting Started</h2>
<p>At Tulima Solar, we provide customized irrigation solutions designed specifically for your farm''s needs. Our team conducts free farm surveys to assess your water requirements, field size, and water source characteristics.</p>

<p>Contact us today to learn how solar irrigation can transform your farming operation.</p>',
'Solar-powered irrigation is revolutionizing agriculture across Africa. Learn about the components, benefits, and how to get started with solar irrigation systems.',
2,
'Complete Guide to Solar-Powered Irrigation | Tulima Solar',
'Discover everything you need to know about solar-powered irrigation systems for African farmers. Components, benefits, and getting started guide.',
'media/blog_images/DJI_0663.JPG',
'Solar panels in a farm field with irrigation system',
'published',
NULL,
'2026-02-20 09:00:00',
245,
18,
5,
1,
1,
'2026-02-18 10:30:00',
'2026-02-20 08:45:00',
NULL),

-- Post 2: Published - Customer Success Story
(2,
'How Farmer John Increased His Yield by 300% with Solar Pumps',
'farmer-john-increased-yield-300-percent-solar-pumps',
'<p>John Mukasa, a small-scale farmer in Mukono District, Uganda, struggled with inconsistent rainfall and the high cost of fuel for his diesel pump. His maize yields were unpredictable, and he often lost crops during dry spells.</p>

<h2>The Challenge</h2>
<p>Before switching to solar, John faced several challenges:</p>
<ul>
    <li>Spending over 200,000 UGX monthly on fuel</li>
    <li>Frequent breakdowns of his diesel pump</li>
    <li>Limited irrigation during critical growing periods</li>
    <li>Unreliable crop yields season to season</li>
</ul>

<h2>The Solution</h2>
<p>In 2024, John partnered with Tulima Solar to install a Kilimo Bora surface pump system. The installation included:</p>
<ul>
    <li>4 solar panels (400W each)</li>
    <li>Kilimo Bora surface pump</li>
    <li>Complete piping system for 2 acres</li>
    <li>Training on system operation and maintenance</li>
</ul>

<h2>The Results</h2>
<p>Within one season, John saw remarkable improvements:</p>
<ul>
    <li>Eliminated fuel costs completely</li>
    <li>Increased maize yield from 800kg to 2,400kg per season</li>
    <li>Expanded cultivated area from 1.5 to 3 acres</li>
    <li>Started a second growing season during the dry period</li>
</ul>

<blockquote>"The solar pump changed everything for my family. I now farm throughout the year and my income has tripled." - John Mukasa</blockquote>

<h2>Financing That Works</h2>
<p>John accessed our 22-month financing program, paying affordable monthly installments. The savings from eliminated fuel costs more than covered his monthly payments.</p>',
'Read how Farmer John Mukasa increased his maize yield by 300% after switching to solar-powered irrigation from Tulima Solar. A real customer success story.',
5,
'Farmer Success Story: 300% Yield Increase with Solar Pumps',
'Real story of a Ugandan farmer who transformed his agricultural business with solar-powered irrigation from Tulima Solar.',
'media/blog_images/IMG-20220803-WA0014-768x1024-1-576x400.jpg',
'Farmer standing in healthy maize field',
'published',
NULL,
'2026-02-18 10:00:00',
189,
24,
8,
1,
1,
'2026-02-16 14:00:00',
'2026-02-18 09:30:00',
NULL),

-- Post 3: Published - Dry Season Farming Tips
(3,
'10 Tips for Successful Dry Season Farming',
'10-tips-successful-dry-season-farming',
'<p>Dry season farming presents unique challenges, but with the right strategies and tools, you can maintain productive harvests year-round. Here are our top 10 tips for successful dry season farming.</p>

<h2>1. Invest in Reliable Irrigation</h2>
<p>A consistent water source is the foundation of dry season farming. Solar-powered irrigation systems provide reliable water without fuel costs.</p>

<h2>2. Choose Drought-Resistant Varieties</h2>
<p>Select crop varieties bred for drought tolerance. Many improved varieties perform well with minimal water once established.</p>

<h2>3. Practice Mulching</h2>
<p>Apply organic mulch around plants to retain soil moisture and suppress weeds. This can reduce water needs by up to 50%.</p>

<h2>4. Time Your Planting</h2>
<p>Plant early in the dry season when residual soil moisture is still available. This gives crops a head start before conditions become more challenging.</p>

<h2>5. Use Conservation Tillage</h2>
<p>Minimize soil disturbance to preserve moisture and soil structure. Zero-tillage or minimum tillage practices help retain water.</p>

<h2>6. Implement Drip Irrigation</h2>
<p>Drip systems deliver water directly to plant roots, minimizing evaporation and waste. They pair excellently with solar pumps.</p>

<h2>7. Monitor Soil Moisture</h2>
<p>Regularly check soil moisture levels to avoid over or under-watering. Simple tools like moisture meters can help.</p>

<h2>8. Provide Windbreaks</h2>
<p>Plant windbreaks or install barriers to reduce evaporation caused by hot, dry winds common in the dry season.</p>

<h2>9. Apply Fertilizer Wisely</h2>
<p>Use fertilizers efficiently and consider slow-release options. Plants under water stress are more sensitive to fertilizer burn.</p>

<h2>10. Plan for Water Storage</h2>
<p>Install water storage tanks to ensure supply during cloudy days or periods of high demand.</p>

<p>At Tulima Solar, we help farmers implement these strategies with our range of solar irrigation solutions. Contact us to learn more.</p>',
'Master dry season farming with these 10 essential tips from Tulima Solar. From irrigation to crop selection, learn how to maintain productive harvests year-round.',
2,
'10 Essential Tips for Dry Season Farming Success',
'Expert tips for successful farming during the dry season. Learn about irrigation, crop selection, and water management strategies.',
'media/blog_images/Untitled-design-7-1_1.png',
'Dry season farming with irrigation',
'published',
NULL,
'2026-02-15 08:00:00',
312,
31,
3,
1,
1,
'2026-02-14 11:00:00',
'2026-02-15 07:30:00',
NULL),

-- Post 4: Published - Solar Pump Maintenance
(4,
'Solar Pump Maintenance: A Complete Care Guide',
'solar-pump-maintenance-complete-care-guide',
'<p>Proper maintenance of your solar pump system ensures optimal performance and longevity. With regular care, your system can operate efficiently for 20+ years. This guide covers essential maintenance tasks for solar irrigation systems.</p>

<h2>Daily Maintenance</h2>
<ul>
    <li><strong>Visual Inspection:</strong> Check for any visible damage to panels, pipes, or connections</li>
    <li><strong>Monitor Performance:</strong> Note any changes in water flow or pressure</li>
    <li><strong>Clean Panel Surface:</strong> Remove dust and debris from solar panels</li>
</ul>

<h2>Weekly Tasks</h2>
<ul>
    <li><strong>Check Connections:</strong> Ensure all electrical connections are secure and corrosion-free</li>
    <li><strong>Inspect Filters:</strong> Clean or replace intake filters as needed</li>
    <li><strong>Test Safety Features:</strong> Verify that shutdown mechanisms work properly</li>
</ul>

<h2>Monthly Maintenance</h2>
<ul>
    <li><strong>Deep Clean Panels:</strong> Use water and soft cloth to thoroughly clean solar panels</li>
    <li><strong>Check Pump Oil:</strong> For pumps requiring lubrication, check and top up oil levels</li>
    <li><strong>Inspect Seals:</strong> Look for wear on pump seals and gaskets</li>
</ul>

<h2>Seasonal Tasks</h2>
<ul>
    <li><strong>Professional Inspection:</strong> Schedule a technician visit for thorough system check</li>
    <li><strong>Check Mounting:</strong> Ensure panel mounting structures are secure</li>
    <li><strong>Review Wiring:</strong> Inspect all wiring for damage from weather or animals</li>
</ul>

<h2>Troubleshooting Common Issues</h2>

<h3>Low Water Output</h3>
<p>Possible causes include dirty panels, clogged filters, or air in the system. Clean panels and filters first, then check for air locks.</p>

<h3>System Not Starting</h3>
<p>Check panel cleanliness, electrical connections, and controller settings. Morning startup issues often relate to panel orientation or shading.</p>

<h3>Unusual Noise</h3>
<p>New sounds may indicate pump wear, cavitation, or debris in the pump. Stop the system and investigate before continuing operation.</p>

<h2>Warranty Support</h2>
<p>All Tulima Solar pumps come with a 2-year warranty. Contact our customer service team for warranty claims or technical support.</p>',
'Learn how to maintain your solar pump system for optimal performance and longevity. Daily, weekly, and seasonal maintenance tasks explained.',
7,
'Solar Pump Maintenance Guide | Tulima Solar',
'Complete guide to maintaining your solar water pump system. Daily, weekly, and seasonal tasks plus troubleshooting tips.',
'media/products/KILIMO-BORA-1.png',
'Solar pump maintenance inspection',
'published',
NULL,
'2026-02-12 14:00:00',
156,
12,
4,
1,
1,
'2026-02-11 09:00:00',
'2026-02-12 13:30:00',
NULL),

-- Post 5: Published - Financing Options
(5,
'Understanding Our 22-Month Financing Program',
'understanding-22-month-financing-program',
'<p>At Tulima Solar, we believe that cost should never prevent farmers from accessing modern irrigation technology. Our 22-month financing program makes solar irrigation systems affordable for small and medium-scale farmers across Uganda.</p>

<h2>How the Financing Works</h2>
<p>Our program is designed with farmers in mind:</p>
<ul>
    <li><strong>No Collateral Required:</strong> The solar system itself serves as collateral</li>
    <li><strong>Affordable Monthly Payments:</strong> Spread the cost over 22 months</li>
    <li><strong>Mobile Money Payments:</strong> Pay conveniently via mobile money</li>
    <li><strong>Quick Approval:</strong> Credit decisions within 48 hours</li>
</ul>

<h2>Eligibility Requirements</h2>
<p>To qualify for financing, you''ll need:</p>
<ul>
    <li>Valid national ID</li>
    <li>Proof of land access (ownership or lease)</li>
    <li>Active mobile money account</li>
    <li>Ability to make monthly payments</li>
</ul>

<h2>The Application Process</h2>
<ol>
    <li><strong>Free Consultation:</strong> Our team visits your farm to assess needs</li>
    <li><strong>System Design:</strong> We recommend the right system for your farm</li>
    <li><strong>Application:</strong> Complete simple financing application</li>
    <li><strong>Approval:</strong> Receive decision within 48 hours</li>
    <li><strong>Installation:</strong> Professional installation at your farm</li>
    <li><strong>Training:</strong> Learn to operate and maintain your system</li>
</ol>

<h2>What''s Included</h2>
<p>Every financed system includes:</p>
<ul>
    <li>Complete solar pump system</li>
    <li>Professional installation</li>
    <li>2-year warranty</li>
    <li>Free training</li>
    <li>After-sales support</li>
</ul>

<h2>Success Stories</h2>
<p>Over 500 farmers have already benefited from our financing program. Many report that fuel savings alone cover their monthly payments, making the system essentially free to operate.</p>

<h2>Get Started Today</h2>
<p>Contact our team to schedule your free farm assessment and learn about financing options available for your farm.</p>',
'Learn about Tulima Solar''s affordable 22-month financing program for solar irrigation systems. No collateral required with quick approval.',
3,
'Solar Irrigation Financing Options | Tulima Solar',
'Affordable financing for solar irrigation systems. 22-month payment plans with no collateral required. Quick approval process.',
'media/partners/Kiva-logo_1.png',
'Farmer reviewing financing documents',
'published',
NULL,
'2026-02-10 11:00:00',
203,
15,
2,
1,
1,
'2026-02-09 15:00:00',
'2026-02-10 10:30:00',
NULL),

-- Post 6: Published - Product Announcement
(6,
'Introducing the New Kilimo Fursa Submersible Pump',
'introducing-new-kilimo-fursa-submersible-pump',
'<p>We''re excited to announce the latest addition to our product lineup: the Kilimo Fursa submersible pump. Designed specifically for deep wells and boreholes, this powerful pump delivers exceptional performance for farmers with challenging water sources.</p>

<h2>Key Features</h2>
<ul>
    <li><strong>Maximum Head:</strong> Up to 80 meters lift capacity</li>
    <li><strong>Flow Rate:</strong> Up to 15,000 liters per hour</li>
    <li><strong>Solar Compatible:</strong> Works with standard solar configurations</li>
    <li><strong>Durable Construction:</strong> Stainless steel body for long life</li>
    <li><strong>Smart Controller:</strong> Built-in protection and monitoring</li>
</ul>

<h2>Ideal Applications</h2>
<p>The Kilimo Fursa excels in:</p>
<ul>
    <li>Deep boreholes (30-70 meters)</li>
    <li>Medium to large-scale irrigation (3-10 acres)</li>
    <li>Livestock watering operations</li>
    <li>Water supply for communities</li>
</ul>

<h2>Technical Specifications</h2>
<table>
    <tr><td>Model</td><td>Kilimo Fursa</td></tr>
    <tr><td>Type</td><td>Submersible Pump</td></tr>
    <tr><td>Maximum Head</td><td>80m</td></tr>
    <tr><td>Maximum Flow</td><td>15,000 L/hr</td></tr>
    <tr><td>Recommended Solar</td><td>1.5kW - 3kW</td></tr>
    <tr><td>Outlet Size</td><td>2 inches</td></tr>
    <tr><td>Warranty</td><td>2 years</td></tr>
</table>

<h2>Pricing and Availability</h2>
<p>The Kilimo Fursa is now available at all Tulima Solar locations. Pricing starts from 4,500,000 UGX for the complete system including solar panels and controller. As always, our 22-month financing is available.</p>

<h2>Book a Consultation</h2>
<p>Contact our team to determine if the Kilimo Fursa is right for your farm. We offer free site assessments and customized system designs.</p>',
'Announcing the new Kilimo Fursa submersible pump from Tulima Solar. Ideal for deep wells with 80m head capacity and 15,000 L/hr flow rate.',
4,
'New Product: Kilimo Fursa Submersible Pump | Tulima Solar',
'Introducing the Kilimo Fursa submersible pump - designed for deep wells with exceptional performance. Now available with financing.',
'media/products/KILIMO-FURSA-1-Copy.png',
'Kilimo Fursa submersible pump product image',
'published',
NULL,
'2026-02-08 09:00:00',
278,
22,
6,
1,
1,
'2026-02-07 10:00:00',
'2026-02-08 08:30:00',
NULL),

-- Post 7: Published - Water Management
(7,
'Water Management Strategies for Smallholder Farmers',
'water-management-strategies-smallholder-farmers',
'<p>Effective water management is crucial for farm success, especially in regions with irregular rainfall. This guide covers practical strategies that smallholder farmers can implement to maximize water efficiency.</p>

<h2>Understanding Your Water Needs</h2>
<p>Before implementing any water management strategy, assess your farm''s needs:</p>
<ul>
    <li><strong>Crop water requirements:</strong> Different crops need different amounts of water</li>
    <li><strong>Soil type:</strong> Sandy soils drain faster than clay soils</li>
    <li><strong>Climate conditions:</strong> Temperature and humidity affect evaporation</li>
    <li><strong>Growth stage:</strong> Water needs change throughout crop development</li>
</ul>

<h2>Efficient Irrigation Methods</h2>

<h3>Drip Irrigation</h3>
<p>Drip systems deliver water directly to plant roots, reducing waste by up to 60% compared to flood irrigation. They work exceptionally well with solar pumps.</p>

<h3>Furrow Irrigation</h3>
<p>For row crops, properly designed furrows can be efficient and low-cost. Ensure furrows follow field contours to prevent runoff.</p>

<h3>Sprinkler Systems</h3>
<p>Overhead sprinklers work well for close-spaced crops. Choose low-pressure options to reduce energy requirements.</p>

<h2>Water Conservation Techniques</h2>

<h3>Rainwater Harvesting</h3>
<p>Capture rainfall using gutters, tanks, and ponds. Even small catchment areas can collect significant water during rainy seasons.</p>

<h3>Mulching</h3>
<p>Apply 5-10cm of organic mulch to reduce evaporation by up to 70%. Use crop residues, straw, or composted materials.</p>

<h3>Cover Crops</h3>
<p>Plant cover crops during off-seasons to protect soil and improve water retention. Legumes also add nitrogen to the soil.</p>

<h2>Monitoring and Scheduling</h2>
<p>Smart irrigation scheduling saves water and improves yields:</p>
<ul>
    <li>Water early morning to minimize evaporation</li>
    <li>Use soil moisture sensors for accurate timing</li>
    <li>Adjust irrigation based on weather forecasts</li>
    <li>Keep records to optimize future scheduling</li>
</ul>

<h2>Tulima Solar Solutions</h2>
<p>Our solar irrigation systems pair perfectly with efficient water management practices. Contact us to design a system that maximizes your water resources.</p>',
'Practical water management strategies for smallholder farmers. Learn about efficient irrigation methods, conservation techniques, and smart scheduling.',
6,
'Water Management for Small Farms | Tulima Solar',
'Essential water management strategies for smallholder farmers. Irrigation methods, conservation techniques, and scheduling tips.',
'media/blog_images/DJI_0663_278Dd2P.JPG',
'Farm water management irrigation channels',
'published',
NULL,
'2026-02-05 10:00:00',
167,
14,
3,
1,
1,
'2026-02-04 12:00:00',
'2026-02-05 09:30:00',
NULL),

-- Post 8: Draft - Upcoming Event
(8,
'Upcoming Farmer Training Workshop in Mbale',
'upcoming-farmer-training-workshop-mbale',
'<p>We are pleased to announce a comprehensive farmer training workshop scheduled for March 2026 in Mbale District. This free event is open to all farmers interested in learning about solar irrigation and modern farming techniques.</p>

<h2>Event Details</h2>
<ul>
    <li><strong>Date:</strong> March 15, 2026</li>
    <li><strong>Time:</strong> 9:00 AM - 4:00 PM</li>
    <li><strong>Venue:</strong> Mbale District Farmers Association Hall</li>
    <li><strong>Admission:</strong> Free</li>
</ul>

<h2>Workshop Topics</h2>
<ul>
    <li>Introduction to solar irrigation systems</li>
    <li>System selection and sizing for your farm</li>
    <li>Installation and maintenance basics</li>
    <li>Financing options explained</li>
    <li>Success stories from local farmers</li>
    <li>Q&A session with our technical team</li>
</ul>

<h2>Who Should Attend</h2>
<p>This workshop is ideal for:</p>
<ul>
    <li>Small and medium-scale farmers</li>
    <li>Agricultural cooperatives</li>
    <li>Farm managers and extension workers</li>
    <li>Anyone interested in solar irrigation</li>
</ul>

<h2>Registration</h2>
<p>While admission is free, registration is required due to limited space. Register by:</p>
<ul>
    <li>Calling our toll-free line: 0800 388 883</li>
    <li>Sending WhatsApp to our business number</li>
    <li>Visiting any Tulima Solar office</li>
</ul>

<p>Refreshments and lunch will be provided. All participants receive a certificate of attendance.</p>',
'Join our free farmer training workshop in Mbale on March 15, 2026. Learn about solar irrigation systems, financing, and hear success stories.',
3,
'Farmer Training Workshop - Mbale | Tulima Solar',
'Free farmer training workshop in Mbale District. Learn about solar irrigation, system selection, and financing options.',
NULL,
'Farmers attending training workshop',
'draft',
NULL,
NULL,
45,
3,
0,
1,
1,
'2026-02-24 08:00:00',
'2026-02-24 08:00:00',
NULL),

-- Post 9: Published - Solar Benefits
(9,
'The Environmental Benefits of Solar-Powered Agriculture',
'environmental-benefits-solar-powered-agriculture',
'<p>As climate change intensifies, the agricultural sector faces increasing pressure to adopt sustainable practices. Solar-powered agriculture offers a pathway to productive farming while reducing environmental impact.</p>

<h2>Reducing Carbon Emissions</h2>
<p>Traditional diesel and petrol pumps release significant carbon dioxide:</p>
<ul>
    <li>Average diesel pump: 2.7 kg CO2 per liter of fuel</li>
    <li>Typical small farm uses 60-100 liters per month</li>
    <li>Annual emissions: 1,944 - 3,240 kg CO2 per farm</li>
</ul>
<p>Solar pumps produce zero direct emissions during operation.</p>

<h2>Decreasing Fossil Fuel Dependence</h2>
<p>Solar energy reduces reliance on imported fossil fuels, contributing to energy independence and price stability for farmers.</p>

<h2>Reducing Noise and Air Pollution</h2>
<p>Unlike combustion engines, solar pumps operate silently and produce no exhaust fumes, improving the farm environment for workers and neighboring communities.</p>

<h2>Water Conservation</h2>
<p>Solar irrigation enables precise water delivery:</p>
<ul>
    <li>Pairs with efficient drip irrigation systems</li>
    <li>Reduces water waste compared to flood irrigation</li>
    <li>Enables irrigation during cooler parts of the day</li>
</ul>

<h2>Sustainable Land Use</h2>
<p>Solar panels can be mounted on unproductive land or integrated with farming:</p>
<ul>
    <li>Agrivoltaics: Growing crops beneath elevated panels</li>
    <li>Panel shading reduces water needs for some crops</li>
    <li>Multiple land use increases overall productivity</li>
</ul>

<h2>Long-Term Sustainability</h2>
<p>Solar systems are built to last:</p>
<ul>
    <li>Panels typically last 25+ years</li>
    <li>Minimal maintenance requirements</li>
    <li>Recyclable components at end of life</li>
</ul>

<h2>Join the Solar Revolution</h2>
<p>Every farmer who switches to solar contributes to a more sustainable future. Contact Tulima Solar to learn how you can reduce your environmental impact while increasing productivity.</p>',
'Discover the environmental benefits of solar-powered agriculture. From reducing carbon emissions to water conservation, solar irrigation is sustainable farming.',
2,
'Environmental Benefits of Solar Agriculture | Tulima Solar',
'How solar-powered agriculture reduces carbon emissions, conserves water, and promotes sustainable farming practices in Africa.',
'media/blog_images/Untitled-design-7-1_1.png',
'Solar panels on farm with blue sky',
'published',
NULL,
'2026-02-01 11:00:00',
234,
28,
5,
1,
1,
'2026-01-30 14:00:00',
'2026-02-01 10:30:00',
NULL),

-- Post 10: Published - Choosing the Right Pump
(10,
'How to Choose the Right Solar Pump for Your Farm',
'how-to-choose-right-solar-pump-your-farm',
'<p>Selecting the correct solar pump is essential for efficient irrigation and long-term satisfaction. This guide walks you through the key factors to consider when choosing a solar pump system.</p>

<h2>Assess Your Water Source</h2>
<p>The type of water source determines the pump category:</p>

<h3>Surface Sources (Rivers, Lakes, Ponds)</h3>
<ul>
    <li>Use surface pumps</li>
    <li>Consider suction lift limits (typically 7 meters maximum)</li>
    <li>Account for seasonal water level changes</li>
</ul>

<h3>Wells and Boreholes</h3>
<ul>
    <li>Use submersible pumps</li>
    <li>Know your well depth and water level</li>
    <li>Consider well diameter for pump fit</li>
</ul>

<h2>Calculate Your Water Needs</h2>
<p>Determine daily water requirements:</p>
<ul>
    <li><strong>Crop needs:</strong> 20-50 liters per square meter per week depending on crop</li>
    <li><strong>Livestock:</strong> 30-50 liters per animal per day for cattle</li>
    <li><strong>Household:</strong> Additional needs for domestic use</li>
</ul>

<h2>Understand Total Dynamic Head (TDH)</h2>
<p>TDH is the total height water must be lifted plus friction losses:</p>
<ul>
    <li>Vertical lift from water source to discharge point</li>
    <li>Pressure required at discharge</li>
    <li>Friction losses in pipes (typically 10-20% added)</li>
</ul>

<h2>Match Pump to Solar Panels</h2>
<p>Solar array sizing depends on:</p>
<ul>
    <li>Pump power requirements</li>
    <li>Daily operating hours needed</li>
    <li>Local solar insolation (average sun hours)</li>
    <li>Seasonal variations in sunlight</li>
</ul>

<h2>Our Pump Selection Guide</h2>

<table>
    <tr>
        <th>Pump Model</th>
        <th>Type</th>
        <th>Best For</th>
        <th>Max Head</th>
        <th>Flow Rate</th>
    </tr>
    <tr>
        <td>Kilimo Bora</td>
        <td>Surface</td>
        <td>Small farms (0.5-2 acres)</td>
        <td>30m</td>
        <td>6,000 L/hr</td>
    </tr>
    <tr>
        <td>Mto Plus</td>
        <td>Surface</td>
        <td>Medium farms (2-5 acres)</td>
        <td>40m</td>
        <td>10,000 L/hr</td>
    </tr>
    <tr>
        <td>Kilimo Tosha</td>
        <td>Submersible</td>
        <td>Shallow wells (10-30m)</td>
        <td>50m</td>
        <td>8,000 L/hr</td>
    </tr>
    <tr>
        <td>Kilimo Fursa</td>
        <td>Submersible</td>
        <td>Deep wells (30-70m)</td>
        <td>80m</td>
        <td>15,000 L/hr</td>
    </tr>
</table>

<h2>Get Expert Help</h2>
<p>Choosing the right pump can be complex. Our team provides free farm assessments to ensure you get the perfect system for your needs. Contact Tulima Solar today.</p>',
'Complete guide to choosing the right solar pump for your farm. Learn about water source assessment, calculating needs, and pump selection.',
7,
'Solar Pump Selection Guide | Tulima Solar',
'How to select the right solar water pump for your farm. Assessment guide covering water sources, needs calculation, and pump specifications.',
'media/products/ChaffCutter.png',
'Various solar pump options displayed',
'published',
NULL,
'2026-01-28 09:00:00',
298,
26,
7,
1,
1,
'2026-01-26 11:00:00',
'2026-01-28 08:30:00',
NULL);

-- =============================================================================
-- SECTION 4: POST-TAG RELATIONSHIPS
-- =============================================================================
-- Assign relevant tags to each post

INSERT INTO blog_post_tags (post_id, tag_id) VALUES
-- Post 1: Solar Irrigation Guide
(1, 1), (1, 2), (1, 4), (1, 6), (1, 9), (1, 11),
-- Post 2: Farmer John Success Story
(2, 6), (2, 7), (2, 8), (2, 13), (2, 14),
-- Post 3: Dry Season Farming Tips
(3, 1), (3, 9), (3, 12), (3, 6),
-- Post 4: Solar Pump Maintenance
(4, 2), (4, 9), (4, 15),
-- Post 5: Financing Program
(5, 3), (5, 6), (5, 7), (5, 13),
-- Post 6: New Product Announcement
(6, 3), (6, 4), (6, 10),
-- Post 7: Water Management
(7, 1), (7, 2), (7, 9), (7, 11),
-- Post 8: Farmer Training Workshop (draft)
(8, 3), (8, 6), (8, 7),
-- Post 9: Environmental Benefits
(9, 4), (9, 5), (9, 6),
-- Post 10: Choosing the Right Pump
(10, 1), (10, 2), (10, 9), (10, 10);

-- =============================================================================
-- SECTION 5: SAMPLE COMMENTS
-- =============================================================================
-- Realistic comments from readers on various posts

INSERT INTO blog_comments (id, post_id, parent_id, user_id, author_name, author_email, author_url, content, status, ip_address, created_at) VALUES
-- Comments on Post 1: Solar Irrigation Guide
(1, 1, NULL, NULL, 'Sarah Namugga', 'sarah.namugga@example.com', NULL, 'This guide is exactly what I needed! I''ve been considering solar irrigation for my vegetable farm. How do I schedule a farm assessment?', 'approved', '192.168.1.100', '2026-02-21 10:30:00'),
(2, 1, 1, NULL, 'Tulima Team', 'info@tulimasolar.com', 'https://tulimasolar.com', 'Hi Sarah! You can call our toll-free line 0800 388 883 or send us a WhatsApp message to schedule your free farm assessment.', 'approved', '192.168.1.1', '2026-02-21 11:45:00'),
(3, 1, NULL, NULL, 'Robert Ssempala', 'r.ssempala@example.com', NULL, 'Great article. I installed a Kilimo Bora last year and it has transformed my farm. No more fuel costs!', 'approved', '192.168.1.101', '2026-02-22 08:15:00'),

-- Comments on Post 2: Farmer John Success Story
(4, 2, NULL, NULL, 'Grace Achieng', 'grace.farmer@example.com', NULL, 'This is so inspiring! I have 2 acres in Gulu and have been struggling with dry season farming. I will definitely contact Tulima.', 'approved', '192.168.1.102', '2026-02-19 09:00:00'),
(5, 2, NULL, NULL, 'Michael Ochieng', 'm.ochieng@example.com', NULL, 'John''s story sounds just like mine. I was spending so much on fuel. Now I''m saving over 300,000 UGX monthly with my solar pump.', 'approved', '192.168.1.103', '2026-02-19 14:30:00'),
(6, 2, NULL, NULL, 'Florence Nalubega', 'florence.n@example.com', NULL, 'What financing options are available for farmers in Jinja area? I''m very interested but concerned about upfront costs.', 'approved', '192.168.1.104', '2026-02-20 16:00:00'),
(7, 2, 6, NULL, 'Tulima Team', 'info@tulimasolar.com', 'https://tulimasolar.com', 'Florence, our 22-month financing is available nationwide! We have an office in Jinja. Please visit us or call to learn more.', 'approved', '192.168.1.1', '2026-02-20 17:30:00'),

-- Comments on Post 3: Dry Season Tips
(8, 3, NULL, NULL, 'David Kato', 'd.kato@example.com', NULL, 'The mulching tip is gold! I reduced my watering by half after applying grass mulch around my tomatoes.', 'approved', '192.168.1.105', '2026-02-16 07:45:00'),
(9, 3, NULL, NULL, 'Agnes Nambi', 'agnes.nambi@example.com', NULL, 'Can you recommend specific drought-resistant tomato varieties for central Uganda?', 'approved', '192.168.1.106', '2026-02-16 11:20:00'),

-- Comments on Post 4: Maintenance Guide
(10, 4, NULL, NULL, 'Christopher Mukasa', 'c.mukasa@example.com', NULL, 'I wish I had this guide earlier! My pump had issues because I wasn''t cleaning the filters regularly. Now I follow a weekly schedule.', 'approved', '192.168.1.107', '2026-02-13 15:00:00'),
(11, 4, NULL, NULL, 'Ruth Nalwadda', 'ruth.n@example.com', NULL, 'How often should I have a professional inspection? The article mentions seasonal but I want to be sure.', 'approved', '192.168.1.108', '2026-02-13 18:30:00'),

-- Comments on Post 6: Product Announcement
(12, 6, NULL, NULL, 'Geoffrey Ssentongo', 'g.ssentongo@example.com', NULL, 'The Kilimo Fursa looks perfect for my 60-meter borehole. Is this available in Mbarara branch?', 'approved', '192.168.1.109', '2026-02-09 10:00:00'),
(13, 6, 12, NULL, 'Tulima Team', 'info@tulimasolar.com', 'https://tulimasolar.com', 'Yes Geoffrey! The Kilimo Fursa is available at all our branches including Mbarara. Visit us for a demonstration.', 'approved', '192.168.1.1', '2026-02-09 11:30:00'),
(14, 6, NULL, NULL, 'Harriet Nakamya', 'h.nakamya@example.com', NULL, 'Excited about this new pump! The 80m head capacity is impressive. Will there be any launch promotions?', 'approved', '192.168.1.110', '2026-02-10 09:15:00'),

-- Comments on Post 9: Environmental Benefits
(15, 9, NULL, NULL, 'Peter Okello', 'p.okello@example.com', NULL, 'It''s great to see companies thinking about the environment. Every farmer switching to solar makes a difference!', 'approved', '192.168.1.111', '2026-02-02 14:00:00'),
(16, 9, NULL, NULL, 'Jane Amongin', 'jane.amongin@example.com', NULL, 'I didn''t realize how much CO2 my diesel pump was producing. This article convinced me to make the switch.', 'approved', '192.168.1.112', '2026-02-03 08:45:00'),

-- Comments on Post 10: Pump Selection Guide
(17, 10, NULL, NULL, 'Solomon Wamala', 's.wamala@example.com', NULL, 'This comparison table is very helpful! I was confused between surface and submersible options. Now I know I need a submersible.', 'approved', '192.168.1.113', '2026-01-29 11:30:00'),
(18, 10, NULL, NULL, 'Catherine Nabukeera', 'c.nabukeera@example.com', NULL, 'What if my well is 90 meters deep? Do you have options for very deep boreholes?', 'approved', '192.168.1.114', '2026-01-29 16:00:00'),
(19, 10, 18, NULL, 'Tulima Team', 'info@tulimasolar.com', 'https://tulimasolar.com', 'Catherine, we have specialized pumps for deeper applications. Please contact us for a custom solution - we can go up to 120m with the right configuration.', 'approved', '192.168.1.1', '2026-01-30 09:00:00'),

-- A pending comment (for moderation demo)
(20, 5, NULL, NULL, 'Anonymous', 'anon@test.com', NULL, 'This is a pending comment for demonstration.', 'pending', '192.168.1.200', '2026-02-24 06:00:00');

-- =============================================================================
-- SECTION 6: SAMPLE POST LIKES
-- =============================================================================
-- Sample likes from users on various posts

INSERT INTO blog_post_likes (post_id, user_id, reaction_type, created_at) VALUES
-- Admin user (ID 1) likes several posts
(1, 1, 'like', '2026-02-20 10:00:00'),
(2, 1, 'like', '2026-02-18 12:00:00'),
(3, 1, 'like', '2026-02-15 09:00:00'),
(6, 1, 'like', '2026-02-08 10:00:00'),
(9, 1, 'like', '2026-02-01 12:00:00'),
(10, 1, 'like', '2026-01-28 10:00:00');

-- =============================================================================
-- SECTION 7: UPDATE COUNTERS
-- =============================================================================
-- Ensure denormalized counters match actual data
-- (These are normally maintained by triggers, but we set them explicitly for seed data)

UPDATE blog_posts p SET 
    comment_count = (SELECT COUNT(*) FROM blog_comments c WHERE c.post_id = p.id AND c.status = 'approved'),
    like_count = (SELECT COUNT(*) FROM blog_post_likes l WHERE l.post_id = p.id);

-- =============================================================================
-- SECTION 8: UPDATE CATEGORY COUNTERS (Optional View Data)
-- =============================================================================
-- Verify the seed data with summary queries

SELECT 'Blog System Seed Data Complete!' AS Status;
SELECT COUNT(*) AS TotalPosts FROM blog_posts WHERE deleted_at IS NULL;
SELECT COUNT(*) AS PublishedPosts FROM blog_posts WHERE status = 'published' AND deleted_at IS NULL;
SELECT COUNT(*) AS DraftPosts FROM blog_posts WHERE status = 'draft' AND deleted_at IS NULL;
SELECT COUNT(*) AS TotalCategories FROM blog_categories WHERE is_active = 1;
SELECT COUNT(*) AS TotalTags FROM blog_tags;
SELECT COUNT(*) AS TotalComments FROM blog_comments WHERE status = 'approved';

-- =============================================================================
-- END OF SEED FILE
-- =============================================================================
