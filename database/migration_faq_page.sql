-- Add FAQ page entry so it appears in admin Pages list and dynamic routing
USE tulima_solar;

INSERT INTO pages (slug, title, meta_description, sort_order, is_active, hero_title, hero_subtitle, hero_cta_text, hero_cta_url)
VALUES
('faq', 'Frequently Asked Questions', 'Tulima Solar FAQ – common questions about our solar irrigation and energy solutions.', 11, 1,
 'Frequently Asked Questions', 'Quick answers about our solar products, financing, and support.', 'Contact Us', 'index.php?page=contact')
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  meta_description = VALUES(meta_description),
  sort_order = VALUES(sort_order),
  hero_title = VALUES(hero_title),
  hero_subtitle = VALUES(hero_subtitle),
  hero_cta_text = VALUES(hero_cta_text),
  hero_cta_url = VALUES(hero_cta_url);

