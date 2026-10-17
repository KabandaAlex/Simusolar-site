-- Run this on existing database to add hero columns to pages (skip if already run)
USE tulima_solar;

ALTER TABLE pages
  ADD COLUMN hero_title VARCHAR(255) NULL AFTER is_active,
  ADD COLUMN hero_subtitle TEXT NULL AFTER hero_title,
  ADD COLUMN hero_cta_text VARCHAR(120) NULL AFTER hero_subtitle,
  ADD COLUMN hero_cta_url VARCHAR(500) NULL AFTER hero_cta_text,
  ADD COLUMN hero_cta_secondary_text VARCHAR(120) NULL AFTER hero_cta_url,
  ADD COLUMN hero_cta_secondary_url VARCHAR(500) NULL AFTER hero_cta_secondary_text,
  ADD COLUMN hero_video_path VARCHAR(500) NULL AFTER hero_cta_secondary_url;
