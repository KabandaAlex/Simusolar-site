-- Legal and utility pages: Privacy Policy, Terms & Conditions, Sitemap (admin-editable content)
USE tulima_solar;

CREATE TABLE IF NOT EXISTS legal_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL DEFAULT '',
    content LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Privacy, Terms, Sitemap to pages table so routing and hero work
INSERT INTO pages (slug, title, meta_description, sort_order, is_active, hero_title, hero_subtitle, hero_cta_text, hero_cta_url) VALUES
('privacy', 'Privacy Policy', 'Tulima Solar privacy policy – how we collect, use and protect your information.', 7, 1, 'Privacy Policy', 'How we collect, use and protect your information.', 'Contact Us', 'index.php?page=contact'),
('terms', 'Terms & Conditions', 'Tulima Solar terms and conditions of use.', 8, 1, 'Terms & Conditions', 'Terms of use for our website and services.', 'Contact Us', 'index.php?page=contact'),
('sitemap', 'Sitemap', 'Tulima Solar – full sitemap of all pages.', 9, 1, 'Sitemap', 'Find your way around our website.', '', NULL),
('cookies', 'Cookie Policy', 'Tulima Solar cookie policy – how we use cookies and similar technologies.', 10, 1, 'Cookie Policy', 'How we use cookies and how you can control them.', '', NULL)
ON DUPLICATE KEY UPDATE title = VALUES(title), meta_description = VALUES(meta_description), sort_order = VALUES(sort_order), hero_title = VALUES(hero_title), hero_subtitle = VALUES(hero_subtitle), hero_cta_text = VALUES(hero_cta_text), hero_cta_url = VALUES(hero_cta_url);

INSERT INTO legal_pages (page_key, title, content) VALUES
('privacy', 'Privacy Policy', '<p>At Tulima Solar we respect your privacy. This policy describes how we collect, use and protect your personal information when you use our website or services.</p><h3>Information we collect</h3><p>We may collect your name, email address, phone number, and other details when you contact us, request a quote, or subscribe to updates.</p><h3>How we use it</h3><p>We use this information to respond to your enquiries, provide services, send relevant updates, and improve our offerings. We do not sell your data to third parties.</p><h3>Cookies</h3><p>Our website may use cookies to improve your experience. You can disable cookies in your browser settings.</p><h3>Contact</h3><p>For questions about this policy, contact us via the Contact page.</p>'),
('terms', 'Terms & Conditions', '<p>Welcome to Tulima Solar. By using our website you agree to these terms.</p><h3>Use of website</h3><p>You may use this website for lawful purposes only. You must not misuse the site or attempt to gain unauthorized access to our systems.</p><h3>Content</h3><p>Content on this site is for general information. We do not guarantee that product or service descriptions are error-free. Contact us for accurate, up-to-date information.</p><h3>Links</h3><p>We may link to third-party sites. We are not responsible for their content or privacy practices.</p><h3>Changes</h3><p>We may update these terms from time to time. Continued use of the site after changes constitutes acceptance.</p><h3>Contact</h3><p>For questions, please use our Contact page.</p>'),
('sitemap', 'Sitemap', ''),
('cookies', 'Cookie Policy', '')
ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content);
