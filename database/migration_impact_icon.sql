-- Add optional icon key to impact_numbers (farmers, acres, income, offices)
USE tulima_solar;

ALTER TABLE impact_numbers ADD COLUMN icon VARCHAR(50) NULL DEFAULT NULL AFTER sublabel;
