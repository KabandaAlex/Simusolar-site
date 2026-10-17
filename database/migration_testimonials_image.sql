-- Add optional image to testimonials (run after migration_testimonials.sql)
USE tulima_solar;

ALTER TABLE testimonials ADD COLUMN image_path VARCHAR(500) NULL DEFAULT NULL AFTER role;
