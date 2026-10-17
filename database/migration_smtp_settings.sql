-- Tulima Solar - SMTP & Email Settings Migration
-- 
-- Adds initial SMTP-related keys to the site_settings table.
-- Run once after deploy:
--   mysql -u root -p tulima_solar < database/migration_smtp_settings.sql

INSERT INTO site_settings (`key`, value, label) VALUES
  ('smtp_enabled', '0', 'Enable SMTP (1 = enabled, 0 = disabled)'),
  ('smtp_host', '', 'SMTP host (e.g. smtp.mailtrap.io)'),
  ('smtp_port', '587', 'SMTP port (e.g. 587, 465)'),
  ('smtp_security', 'tls', 'SMTP security (none, tls, ssl)'),
  ('smtp_username', '', 'SMTP username'),
  ('smtp_password', '', 'SMTP password'),
  ('smtp_from_email', '', 'Default From email address'),
  ('smtp_from_name', 'Tulima Solar', 'Default From name'),
  ('smtp_test_recipient', '', 'Test email recipient'),
  ('smtp_contact_recipients', '', 'Contact form recipients (comma-separated)'),
  ('smtp_job_recipients', '', 'Job application recipients (comma-separated)')
ON DUPLICATE KEY UPDATE
  label = VALUES(label);

