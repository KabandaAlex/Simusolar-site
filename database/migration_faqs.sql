-- FAQs for Contact page accordion
USE tulima_solar;

CREATE TABLE IF NOT EXISTS faqs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO faqs (question, answer, sort_order, is_active) VALUES
('How do I get started?', 'Simply contact us through this form, call our toll-free number, or visit any of our offices. We''ll schedule a free farm survey and consultation to design the perfect system for your needs.', 10, 1),
('How long does installation take?', 'Most solar irrigation systems can be installed within 1-3 days depending on the size and complexity of your farm setup.', 20, 1),
('What warranty do you provide?', 'We offer a 2-year warranty on our solar equipment. Extended warranty options may be available for specific products.', 30, 1),
('Do you offer financing options?', 'Yes. We offer flexible financing so you can pay in monthly instalments. Contact us for current terms and eligibility.', 40, 1),
('What maintenance is required?', 'Solar systems require minimal maintenance. We recommend periodic panel cleaning and an annual check. Our team can guide you.', 50, 1)
ON DUPLICATE KEY UPDATE question = VALUES(question), answer = VALUES(answer), sort_order = VALUES(sort_order);
